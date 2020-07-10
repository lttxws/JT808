<?php
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/Workerman/Autoloader.php';
$worker = new Worker();
$status = true;
$worker->onWorkerStart = function () {
	// 以websocket协议连接远程websocket服务器 ws://IP:端口
	$ws_connection = new AsyncTcpConnection('tcp://192.168.1.5:8095');

	// 连接成功
	$ws_connection->onConnect = function ($connection) {

		//注册
		$msg = "7e0100002d019501137397001800000000383838383800000000000000000000000000000000000000003131333733393702d4c1423030303030327e";
		//位置信息上报
		$msg1 = '7e0002000001453409748303c8477e7e0200003a0145340972520bdb00000000000000030205d4b406b9efe800ed00000120200430194209010400000716eb16000c00b28986043103188076978800060089fffffffff77e';
		$msg2 = '7e01020004019041377305006131313131f77e';
		$msg3 = '7e0200003a01453408683900b600000000000000030205b1f006b97930015e00000111200430182601010400000018eb16000c00b28986043103188076975700060089ffffffffb67e';
		$data = hex2bin($msg);
		$data1 = hex2bin($msg1);
		$data2 = hex2bin($msg2);
		$data3 = hex2bin($msg3);

		$data1 = bin2hex($data);
		//定时3秒
		$time_interval = 3;
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