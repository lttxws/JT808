<?php
namespace lttxws;
class JT808 {
	public static $sequenceNumber = 0; //流水号初始值
	/**
	 * description  转成10进制字符串数组
	 * @param $string 16进制字符串
	 * @return array   10进制数组
	 */
	public function get10Bytes($string) {
		$bytes = array();
		$len = strlen($string);
		for ($i = 0; $i < $len; $i++) {
			array_push($bytes, ord($string[$i]));
		}
		return $bytes;
	}

	/**
	 * description  10进制字符串数组转成16进制字符串数组
	 * @param $data 10进制字符串数组
	 * @return mixed 16进制字符串数组
	 */
	public function getTo16Bytes($data) {
		//$get10Bytes = $this->get10Bytes($data);
		$content = bin2hex($data);
		$res = explode('7e7e', $content);
		$array = [];
		//解决粘包
		if (count($res) > 1) {
			foreach ($res as $k => $v) {
				if ($k == reset($res)) {
					$array[$k] = str_split($v . '7e', 2);
				} else if ($k == end($res)) {
					$array[$k] = str_split('7e' . $v, 2);
				} else {
					$array[$k] = str_split('7e' . $v . '7e', 2);
				}
			}
		} else {
			$array[] = str_split($res[0], 2);
		}
		return $array;

		/*$array = [];
			foreach ($get10Bytes as $k => $v) {
				$array[$k] = base_convert($v, 10, 16);
			}
		*/
		//return $array;
	}

	/**
	 * description   接受到的16进制字符补0  例如：01=>0x01
	 * @param $data 16进制数组
	 * @return array  补0之后的16进制数组
	 */
	public function supplementZero($data) {
		$len = count($data);
		$res = [];
		for ($j = 0; $j < $len; $j++) {
			if (strlen($data[$j]) == 1) {
				$res[$j] = "0x" . "0" . $data[$j];
			} else {
				$res[$j] = "0x" . $data[$j];
			}
		}
		return $res;
	}

	/**
	 * description  把一个4位的数组转化位整形
	 * @param array  接受数组
	 * @return int  返回int
	 */
	public function bytesToInt($data) {
		$res = [];
		foreach ($data as $k => $v) {
			$res[$k] = intval(base_convert($v, 16, 10));
		}
		$temp0 = $res[0] & 0xFF;
		$temp1 = $res[1] & 0xFF;
		$temp2 = $res[2] & 0xFF;
		$temp3 = $res[3] & 0xFF;
		return (($temp0 << 24) + ($temp1 << 16) + ($temp2 << 8) + $temp3);
	}

	/**
	 * description  BCD码转字符串
	 * @param array  数组
	 * @return bool|string  返回字符串
	 */
	public function bcdToString($data) {
		$len = count($data);
		$temp = "";
		for ($i = 0; $i < $len; $i++) {
			// 高四位
			$temp .= (($data[$i] & 0xf0) >> 4);
			// 低四位
			$temp .= ($data[$i] & 0x0f);
		}
		return (substr($temp, 0, 1) == 0) ? substr($temp, 1) : $temp;
	}

	/**
	 * description  从接受到的16进制数组中获取设备号数组
	 * @param $data  接受到的16进制数组
	 * @return string 设备号id
	 */
	public function getSensorId($data) {
		$sensorArray = array_slice($data, 3, 6);
		$sensorArrayZero = $this->supplementZero($sensorArray);
		$res = [];
		foreach ($sensorArrayZero as $k => $v) {
			$res[$k] = intval(base_convert($v, 16, 10));
		}
		$string = $this->bcdToString($res);
		return $string;
	}

	/**
	 * description   把一个二字节数组转化成整型
	 * @param $data  二字节数组
	 * @return int   整型
	 */
	public function twoBytesToInteger($data) {
		$res = [];
		foreach ($data as $k => $v) {
			$res[$k] = intval(base_convert($v, 16, 10));
		}
		$temp0 = $res[0] & 0xFF;
		$temp1 = $res[1] & 0xFF;
		return (($temp0 << 8) + $temp1);
	}

	/**
	 * description  接受内容中4字节数组转成int
	 * @param $data 16进制字节数组
	 * @param int $a 开始位
	 * @return int   int值
	 */
	public function getNum($data, $a = 0) {
		$numArray = array_slice($data, $a, 4);
		$res = $this->bytesToInt($numArray);
		return $res;
	}

	/**
	 * description  按位异或
	 * @param $data
	 * @return int
	 */
	public function getEveryXor($data) {
		$len = count($data);
		$rew = 0;
		for ($i = 1; $i < $len; $i++) {
			$rew = $rew ^ $data[$i];
		}
		return $rew;
	}
	/**
	 * [checkCode 生成验证码带开头7e]
	 * @author litaotxws@163.com
	 * @DateTime 2020-05-01T00:19:06+0800
	 * @param    [array]                   $data [数组]
	 * @return   [array]                         [返回2位数数组]
	 */
	public function checkCode($data) {
		$sum = 0;
		//去掉开头的7e
		unset($data[0]);
		//for ($i = 0; $i < count($arr); $i++) {
		foreach ($data as $k => $v) {
			$sum = $sum ^ hexdec($v);
		}
		return str_pad(dechex($sum), 2, "0", STR_PAD_LEFT);
	}
	/**
	 * description   将字节数组转为字符串
	 * @param array   字节数组
	 * @return string   返回字符串
	 */
	public function bytesArrayToString($data) {
		$str = '';
		foreach ($data as $ch) {
			$str .= chr($ch);
		}
		return $str;
	}
	/**
	 * [arrayToBytes 返回二进制内容]
	 * @author litaotxws@163.com
	 * @DateTime 2020-05-01T00:34:33+0800
	 * @param    [type]                   $data [数组]
	 * @return   [type]                         [二进制内容]
	 */
	public function arrayToBytes($data) {
		$ret = implode($data);
		return hex2bin($ret);
	}
	/**
	 * description 拼接字符串
	 * @param $str
	 * @param int $n
	 * @param string $char
	 * @return string
	 */
	public function getTurnStr($str, $n = 1, $char = '0') {
		for ($i = 0; $i < $n; $i++) {
			$str = $char . $str;
		}
		return $str;

		//return str_pad($str, $n, $char, STR_PAD_LEFT);
	}

	/**
	 * description  转成二进制字符串
	 * @param $data array 16进制数组
	 * @return string  字符串
	 */
	public function getTwoStr($data) {
		//转成2进制
		$str = array();
		$req = array();
		foreach ($data as $key => $value) {
			$str[$key] = base_convert($data[$key], 16, 2);
			$leng = 8 - strlen($str[$key]);
			$req[] = $this->getTurnStr($str[$key], $leng, "0");
		}
		//拼接字符串
		$rtq = implode("", $req);
		return $rtq;
	}
	/**
	 * description  获取设备号
	 * @param $data array 16进制数组
	 * @param $length num 补全长度
	 * @return bool|string   返回字符串设备号
	 */
	public function getEquipmentNumber($data, $length = 12) {
		$equipmentArray = array_slice($data, 5, 6);
		$res = [];
		foreach ($equipmentArray as $k => $v) {
			$res[$k] = base_convert($v, 16, 10);
		}
		$equipmentNumber = $this->bcdToString($res);
		return str_pad($equipmentNumber, $length, "0", STR_PAD_LEFT);
	}
	/**
	 * description  获取16进制数组来计算出设备号
	 * @param $data 16进制数组
	 * @return array  返回设备号数组
	 */
	public function getEquipmentNumberArray($data) {
		$num_array = array_slice($data, 5, 6);
		/*$res = [];
			foreach ($num_array as $k => $v) {
				$res[$k] = base_convert($v, 16, 10);
			}
		*/
		return $num_array;
	}
	/**
	 * description  获取报警信息
	 * @param $data array 16进制数组
	 * @param $index  数组索引13
	 * @return int  返回数字代表报警信息
	 */
	public function getAlarmMessage($data, $index, $type = false) {
		$alarmArray = $this->getTwoStr(array_slice($data, $index, 4));
		if ($type == true) {
			if (substr($alarmArray, -8, 1) == 1) {
				//主电源断电
				$alarm = "主电源断电";
			} elseif (substr($alarmArray, -30, 1) == 1) {
				//碰撞预警
				$alarm = "碰撞预警";
			} elseif (substr($alarmArray, -31, 1) == 1) {
				//侧翻预警
				$alarm = "侧翻预警";
			}
//        elseif (substr($alarmArray, -26, 1) == 1) {
			//            //脱落(光感)报警
			//            $alarm = 4;
			//        }
			else {
				//正常
				$alarm = "一切正常";
			}
		}
		// return $alarm;
		return $alarmArray;
	}

	/**
	 * description   获取状态信息
	 * @param $data array 16进制数组
	 * @param $index   数组索引
	 * @param $type   返回数据还是原本信息
	 * @return array  状态信息数组
	 */
	public function getPositionStatus($data, $index, $type = false) {
		$positionArray = $this->getTwoStr(array_slice($data, $index, 4));
		if ($type == true) {
			//判断是否定位，0定位，1未定位
			$isPosition = substr($positionArray, -2, 1) == 0 ? $isPosition = "未定位" : $isPosition = "定位";
			//判断南北纬，0北纬，1南纬
			$isNorSou = substr($positionArray, -3, 1) == 0 ? $isNorSou = "北纬" : $isNorSou = "南纬";
			//判断东西经，0东经，1西经
			$isEasWes = substr($positionArray, -4, 1) == 0 ? $isEasWes = "东经" : $isEasWes = "西经";
			//判断定位方式
			if (substr($positionArray, -19, 1) == 1 && substr($positionArray, -20, 1) == 0) {
				//北斗定位
				$positionMethod = "北斗定位";
			} elseif (substr($positionArray, -19, 1) == 0 && substr($positionArray, -20, 1) == 1) {
				//GPS定位
				$positionMethod = "GPS定位";
			} elseif (substr($positionArray, -19, 1) == 1 && substr($positionArray, -20, 1) == 1) {
				//北斗GPS双定位
				$positionMethod = "北斗GPS双定位";
			} else {
				//北斗GPS都未定位
				$positionMethod = "北斗GPS都未定位";
			}
			$positionStatusArray = array(
				'position' => $isPosition,
				'ns' => $isNorSou,
				'ew' => $isEasWes,
				'gps' => $positionMethod,
			);
			return $positionStatusArray;
		} else {
			return $positionArray;
		}
	}

	/**
	 * description   获取纬度
	 * @param $data array  16进制数组
	 * @param $index  数组索引
	 * @param $type  返回字符类型 i整形 f浮点形
	 * @return float|int   纬度
	 */
	public function getLatitude($data, $index, $type = 'f') {
		$latitudeBytes = array_slice($data, $index, 4);
		$latitude = $this->bytesToInt($latitudeBytes);
		if ($type == 'f') {
			$number = $latitude / pow(10, 6);
		}
		if ($type == 'i') {
			$number = $latitude;
		}
		return $number;
	}

	/**
	 * description  获取经度
	 * @param $data array  16进制数组
	 * @param $index  数组索引
	 * @param $type  返回字符类型 i整形 f浮点形
	 * @return float|int  经度
	 */
	public function getLongitude($data, $index, $type = 'f') {
		$longitudeBytes = array_slice($data, $index, 4);
		$longitude = $this->bytesToInt($longitudeBytes);
		if ($type == 'f') {
			$number = $longitude / pow(10, 6);
		}
		if ($type == 'i') {
			$number = $longitude;
		}
		return $number;
	}
	/**
	 * [getHeight 获取高度]
	 * @author litaotxws@163.com
	 * @DateTime 2020-04-30T14:12:30+0800
	 * @param    [type]                   $data  [16进制数组]
	 * @param    [type]                   $index [数组索引29]
	 * @return   [type]                          [高度]
	 */
	public function getHeight($data, $index) {
		$heightBytes = array_slice($data, $index, 2);
		$height = $this->twoBytesToInteger($heightBytes);
		return $height;
	}
	/**
	 * [getSpeed 获取速度]
	 * @author litaotxws@163.com
	 * @DateTime 2020-04-30T14:14:55+0800
	 * @param    [type]                   $data  [16进制数组]
	 * @param    [type]                   $index [数组索引31]
	 * @return   [type]                          [速度]
	 */
	public function getSpeed($data, $index) {
		$speedBytes = array_slice($data, $index, 2);
		$speed = $this->twoBytesToInteger($speedBytes);
		return $speed;
	}
	/**
	 * [getDirection 获取方向]
	 * @author litaotxws@163.com
	 * @DateTime 2020-04-30T14:15:33+0800
	 * @param    [type]                   $data  [16进制数组]
	 * @param    [type]                   $index [数组索引33]
	 * @return   [type]                          [方向]
	 */
	public function getDirection($data, $index) {
		$directionBytes = array_slice($data, $index, 2);
		$direction = $this->twoBytesToInteger($directionBytes);
		return $direction;
	}
	/**
	 * description  获取日期时间
	 * @param $data array  16进制数组
	 * @param $index  数组索引35
	 * @return string   日期时间字符串
	 */
	public function getDatetime($data, $index) {
		$datetimeArray = array_slice($data, $index, 6);
		$res = [];
		foreach ($datetimeArray as $k => $v) {
			$res[$k] = base_convert($v, 16, 10);
		}
		$datetime = $this->bcdToString($res);
		$datetimeStr = "20" . substr($datetime, 0, 2) . "-" . substr($datetime, 2, 2) . "-" . substr($datetime, 4, 2) . " " . substr($datetime, 6, 2) . ":" . substr($datetime, 8, 2) . ":" . substr($datetime, 10, 2);
		return $datetimeStr;
	}

	/**
	 * description   获取平台流水号
	 * @return array   返回流水号数组
	 */
	public function getSequenceNumberArray() {
		//计算流水号
		$number = $this->$sequenceNumber++;
		if ($number > 65025) {
			// 255 * 255 -1
			$number = 1;
		}
		//将十进制流水号换算成16进制流水号
		$get16Number = base_convert($number, 10, 16);
		$af = substr($get16Number, 0, 2);
		$bf = substr($get16Number, 2);
		$systemNumber = [];
		//判断
		if ($number > 0xff) {
			$systemNumber = array('0x' . $af, '0x' . $bf);
		} else {
			$systemNumber = array('0x00', '0x' . $get16Number);
		}
		foreach ($systemNumber as $k => $v) {
			$systemNumber[$k] = intval(base_convert($v, 16, 10));
		}
		return $systemNumber;
	}

	/**
	 * description  获取消息流水号
	 * @param $data 16进制数组
	 * @return array  消息流水号数组
	 */
	public function getMessageNumberArray($data) {
		$messageNumber = array_slice($data, 11, 2);
		//$messageNumber = $this->supplementZero($messageNumber);
		return $messageNumber;
	}

	/**
	 * description  获取消息id
	 * @param $data 16进制数组
	 * @return array  消息id数组
	 */
	public function getMessageIdArray($data) {
		$messageId = array_slice($data, 1, 2);
		//$messageId = $this->supplementZero($messageId);
		return $messageId;
	}
	/**
	 * description  获取消息id
	 * @param $data array 16进制数组
	 * @param $length num 消息体长度 前位补0
	 * @return bool|string   消息id字符串
	 */
	public function getMessageIdNumber($data, $length = 4) {
		$messageArray = array_slice($data, 1, 2);
		$res = [];
		foreach ($messageArray as $k => $v) {
			$res[$k] = base_convert($v, 16, 10);
		}
		$messageNumber = $this->bcdToString($res);
		return str_pad($messageNumber, $length, "0", STR_PAD_LEFT);
	}
	/**
	 * description   获取消息体
	 * @param $data 16进制数组
	 * @return array   消息体数组
	 */
	public function getMessageBodyArray($data) {
		//消息体 = 消息流水号 + 消息id
		$messageNumber = $this->getMessageNumberArray($data);
		//$messageId = $this->getMessageIdArray($data);
		//$messageBody = array_merge($messageNumber, $messageId);
		/*foreach ($messageBody as $k => $v) {
				$res[$k] = intval(base_convert($v, 16, 10));
			}
		*/
		return $messageNumber;
	}

	/**
	 * description  发送给客户端的回传数据
	 * @param $data 16进制数组
	 * @return string   返回客户端字符串
	 */
	public function getVerifyNumberArray($data, $auth = '31313131') {
		//数组开始五位
		//$arrayStartFiveBytes = array('7E', '80', '01');
		//消息ID
		$messageId = $this->getMessageIdArray($data);
		$messageid = implode($messageId);
		//消息体
		$messageBody = $this->getMessageBodyArray($data);
		if ($messageid == '0100') {
			$arrayStartFiveBytes = array('7E', '81', '00');
			$jianquan = str_split($auth, 2);
			$messageBody = array_merge($messageBody, $jianquan);
		} else {
			$arrayStartFiveBytes = array('7E', '80', '01');
			$jianquan = [];
			$messageBody = array_merge($messageBody, $messageId, $jianquan);
		}
		//设备号
		$equipmentNumber = $this->getEquipmentNumberArray($data);
		//平台流水号
		//$systemNumber = $this->getSequenceNumberArray();
		$systemNumbers = $this->getMessageNumberArray($data);
		//注册应答结果0
		$ret = array('00');

		//消息体长度
		/*if ($messageid == '0100') {
			$msglength = count(array_merge($systemNumbers, $messageId, $ret, $jianquan));
		} else {
			$msglength = count(array_merge($systemNumbers, $messageId, $ret));
		}*/
		$msglength = count(array_merge($systemNumbers, $messageId, $ret, $jianquan));
		$msglength = decbin($msglength);

		//补齐16位，不加密，无版本号
		$attr = sprintf("%016d", $msglength); //消息体属性
		//前置补0
		$attr_str = str_pad(dechex(bindec($attr)), 4, '0', STR_PAD_LEFT);
		//$attr_str = dechex(bindec($attr));
		//分割字符
		$attrarray = str_split($attr_str, 2);
		//与消息体合并
		$array_attr = array_merge($arrayStartFiveBytes, $attrarray);
		//数组开始5位和设备号合并
		$arrayStartAndEquipmentNumber = array_merge($array_attr, $equipmentNumber);
		//接上一步继续与平台流水号合并
		$startEquipmentAndSystemNumber = array_merge($arrayStartAndEquipmentNumber, $systemNumbers);
		//接上一步继续与消息体合并
		$startEquipmentSystemAndMessageBody = array_merge($startEquipmentAndSystemNumber, $messageBody);
		//接上一步应答结果
		$dataAndRet = array_merge($startEquipmentSystemAndMessageBody, $ret);
		//生成校验码
		//$dataAndRetXor = $this->getEveryXor($dataAndRet);
		$dataAndRetXor = $this->checkCode($dataAndRet);
		//数组末尾两位
		$arrayEndTwoBytes = array($dataAndRetXor, '7E');
		//整个数组
		$completeArray = array_merge($dataAndRet, $arrayEndTwoBytes);
		//发送给客户端的字符串
		$sendClientStr = $this->arrayToBytes($completeArray);

		return $sendClientStr;
	}
}
?>