# PHP JT808协议 server端

基于[gogocheng/messageAnalysis](https://github.com/gogocheng/messageAnalysis.git "gogocheng/messageAnalysis")版本开发
使用wokrerman来进行数据收发

## 使用方法：

该项目需要通过 composer 来管理依赖包。 先安装 composer 

然后到本项目解压缩后的目录下运行 ，安装依赖包

composer install

composer require lttxws/jt808

分别以命令行的形式启动根目录下 server.php client.php

php server.php start

![server](https://raw.githubusercontent.com/lttxws/JT808/master/image/server.jpg "server")

php client.php start

![client](https://raw.githubusercontent.com/lttxws/JT808/master/image/client.jpg "client")

## client.php中98行，可以更改不同数据，模拟客户端发送不同的报文
## 代码示例：
### 服务端:
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use lttxws\JT808;
use Workerman\Worker;

// 创建一个Worker监听8095端口，不使用任何应用层协议
$tcp_worker = new Worker("tcp://192.168.1.5:8095");
// 启动12个进程对外提供服务
$tcp_worker->count = 12;
// 当客户端发来数据时
$tcp_worker->onMessage = function ($connection, $data) {
	$JT808 = new JT808();
	//16进制数据
	$data16Arrays = $JT808->getTo16Bytes($data);

	foreach ($data16Arrays as $key => $data16Array) {
		//获取消息id
		$MessageId = $JT808->getMessageIdNumber($data16Array);
		//设备号
		$equipmentNumber = $JT808->getEquipmentNumber($data16Array);
		//位置信息上报获取
		if ($MessageId == '0200' && $equipmentNumber) {
			//报警信息  需要自己判断
			$AlarmMessage = $JT808->getAlarmMessage($data16Array, 13);
			//状态
			$status = $JT808->getPositionStatus($data16Array, 17);
			//经度
			$Latitude = $JT808->getLatitude($data16Array, 21, 'i');
			//纬度
			$Longitude = $JT808->getLongitude($data16Array, 25, 'i');
			//高度
			$Height = $JT808->getHeight($data16Array, 29);
			//速度
			$Speed = $JT808->getSpeed($data16Array, 31);
			//方向
			$Direction = $JT808->getDirection($data16Array, 33);
			//时间
			$Datetime = $JT808->getDatetime($data16Array, 35);
			if ($Latitude && $Longitude) {
				//执行你的逻辑
			}

		}
		//发送给客户端
		$sendClientData = $JT808->getVerifyNumberArray($data16Array);
		$connection->send($sendClientData);
	}
};

// 运行worker
Worker::runAll();
```
### 客户端：
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;
use \Workerman\Lib\Timer;
$worker = new Worker();
$status = true;
$worker->onWorkerStart = function () {
	// 以websocket协议连接远程websocket服务器 ws://IP:端口
	$ws_connection = new AsyncTcpConnection('tcp://192.168.1.5:8095');

	// 连接成功
	$ws_connection->onConnect = function ($connection) {

		//注册
		$msg = "7e0100002d019501137397001800000000383838383800000000000000000000000000000000000000003131333733393702d4c1423030303030327e";
		//心跳包
		$msg1 = '7e0002000001453409748303c8477e7e0200003a0145340972520bdb00000000000000030205d4b406b9efe800ed00000120200430194209010400000716eb16000c00b28986043103188076978800060089fffffffff77e';
		//位置信息上报
		$msg2 = '7e0200003a01453408683900b600000000000000030205b1f006b97930015e00000111200430182601010400000018eb16000c00b28986043103188076975700060089ffffffffb67e';
		$data = hex2bin($msg);
		$data1 = hex2bin($msg1);
		$data2 = hex2bin($msg2);
		//定时3秒
		$time_interval = 3;

		//改为data为注册 data1心跳包 data2位置信息上报
		$sendData = $data;
		// 给connection对象临时添加一个timer_id属性保存定时器id
		$connection->timer_id = Timer::add($time_interval, function () use ($connection, $sendData) {
			$connection->send($sendData);
		});

	};

	// 远程websocket服务器发来消息时
	$ws_connection->onMessage = function ($connection, $data) {
		$data1 = bin2hex($data);
		echo "recv: $data1\n";
	};
	// 连接上发生错误时，一般是连接远程websocket服务器失败错误
	$ws_connection->onError = function ($connection, $code, $msg) {
		echo "error: $msg\n";
	};
	// 当连接远程websocket服务器的连接断开时
	$ws_connection->onClose = function ($connection) {
		echo "connection closed\n";
		// 连接关闭时，删除对应连接的定时器
		// 删除定时器
		Timer::del($connection->timer_id);

	};
	// 设置好以上各种回调后，执行连接操作
	$ws_connection->connect();
};
// 执行
Worker::runAll();
```
