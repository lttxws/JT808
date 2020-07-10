<?php
require_once __DIR__ . '/Workerman/Autoloader.php';
require_once __DIR__ . '/JT808_class.php';
use Common\JT808;
use Workerman\Worker;

// 创建一个Worker监听8095端口，不使用任何应用层协议
$tcp_worker = new Worker("tcp://192.168.1.5:8095");
// 启动12个进程对外提供服务
$tcp_worker->count = 12;
// 当客户端发来数据时
$tcp_worker->onMessage = function ($connection, $data) {
	$sql = new Db(mysqlConfig::load());
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
			//echo '1212';
			//报警信息
			$AlarmMessage = $JT808->getAlarmMessage($data16Array, 13);
			//echo $AlarmMessage, PHP_EOL;
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
