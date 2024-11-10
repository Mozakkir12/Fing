<?php
require "main/sendobject.php";
require "main/db/pdo.php";
header("Access-Control-Allow-Origin: *");

use FingerPrint\sendObject;
error_reporting(E_ALL);

set_time_limit(0);
ob_implicit_flush();

//date_default_timezone_set('Asia/Calcutta');
//date_default_timezone_set('PRC');

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if(socket_bind($socket, SERVER_IP, SERVER_PORT)==false)
{
	echo " bind Failed ".SERVER_IP.":".SERVER_PORT;;
	exit;
}
$sendObject =new sendObject();

socket_listen($socket, MAX_THREADS);
//socket_set_nonblock($socket);

$machines = array($socket); //所有Socket列表
$unread = array(); //当前正在读取的Socket列表
$data = array(); //每个Socket未处理完的数据。
$machineInfo = array(); // id  对应的 IP端口
$id = array();  //IP端口对应的 ID
$constat = array(); //
$userData=array(); //用户列表

//图片命名
$startData = "his ".date('md_His_');
$imgID=1;
$sendObject->startData=$startData;
$sendObject->imgID=$imgID;

echo " start...$startData </br>";
ob_flush();

$bConnect=false;
$startTime=microtime(true);
$getList=true;
$dataTime=microtime(true);;
do {
	$unread = $machines;
	if(socket_select($unread, $write, $except, 0,100)>0)
	{
		foreach ($unread as $mark => $ready) {
			if ($ready === $socket) {
				$accept = socket_accept($socket);
				socket_getpeername($accept, $address, $port);
				echo "</br>accept : $address:$port ".date('His');
				ob_flush();
				
				$machines[] = $accept; //添加
				$data["id{$address}_{$port}"]=''; //清空数据
				$constat["id{$address}_{$port}"]=false;
				/*
				$headers = socket_read($accept, 4096, PHP_BINARY_READ);
				preg_match_all('/Sec-WebSocket-Key:\s*(.*?)\r\n/', $headers, $key);
				$key = base64_encode(SHA1($key[1][0].'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
				//$buffer = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $key\r\n\r\n";
				$buffer = "HTTP/1.1
			 Upgrade: WebSocket
			 Connection: Upgrade";
				socket_write($accept, $buffer, strlen($buffer));
				socket_set_nonblock($accept);
				if($bConnect===false)
				{
					$bConnect=true;
				}
				*/
			} else {
				//$packet = @socket_read($ready, 4096, PHP_NORMAL_READ);
				socket_getpeername($ready, $address, $port);
				$frame = @socket_read($ready, 4096, PHP_BINARY_READ);
				if ($frame) {
					
					$dataTime=microtime(true);;
					if($constat["id{$address}_{$port}"]==false) //握手
					{
						if(woshou($ready,$frame))
						{
							$constat["id{$address}_{$port}"]=true;
							echo "</br>connected : $address:$port ".date('His');
							
						}
						else 
							echo "</br>shakehand failed ".$frame;
						ob_flush();
						continue;
					}
					
					$DataRec=''; //收到数据
					if(isset($data["id{$address}_{$port}"]))
						$DataRec=$data["id{$address}_{$port}"];
					$DataLen=strlen($DataRec); //数据长度
					
					$size = strlen($frame);
					$DataRec.=$frame;
					$DataLen+=$size;
					
					$NeedLen=2; //数据头
					while($DataLen>$NeedLen)
					{
						//取长度
						$optcode=ord($DataRec[0]) & 15;
						
						//echo "</br> onframe:".ord($DataRec[0]).ord($DataRec[1])." len:".$DataLen;
						
						$b2 = ord($DataRec[1]);
						$mask = ($b2 &128) != 0;
						$payloadlength = $b2&127;
						
						if($payloadlength === 126)
							$NeedLen+=2;
						else if($payloadlength>126)
							$NeedLen+=8;
						if($DataLen<$NeedLen)
							break;
							
						$nDataPos=2;
						$maxpacketsize=$DataLen;
						if (!($payloadlength >= 0 && $payloadlength <= 125))
						{
							if ($payloadlength === 126) // && (optcode != PING && optcode != PONG &&  optcode != CLOSING)
							{					
								$payloadlength=ord($DataRec[$nDataPos+1]);
								$payloadlength+=ord($DataRec[$nDataPos])<<8;
								$nDataPos+=2;

							} else {					
								$payloadlength=ord($DataRec[$nDataPos+7]);
								$payloadlength+=ord($DataRec[$nDataPos+6])<<8;
								$payloadlength+=ord($DataRec[$nDataPos+5])<<16;
								$payloadlength+=ord($DataRec[$nDataPos+4])<<24;
								//....
								$nDataPos+=8;
							}
						}
						if($payloadlength>=0 && $payloadlength<1024*1024*2)
						{
							$NeedLen += ($mask ? 4 : 0);
							$NeedLen += $payloadlength;
							
							if($maxpacketsize < $NeedLen)
							{
								//echo($maxpacketsize." < ".$NeedLen." | ");
								//ob_flush();
								break;
							}
							//echo "</br>";
							
							$packet = '';
							if ($mask) {
								$maskPos=$nDataPos;
								$nDataPos+=4;

							  for ($i = 0; $i < $payloadlength; $i++) {
								$packet .=($DataRec[$i+$nDataPos] ^ $DataRec[$maskPos+$i % 4]);
							  }
							  $nDataPos+=$payloadlength;
							} else {
								$packet.=substr($DataRec,$nDataPos,$maxpacketsize-$nDataPos);
								$nDataPos=$maxpacketsize;
							}
							
							onFrame($packet,$ready,$address,$port,$optcode);
						
							if($nDataPos===$DataLen)
							{
								$DataRec='';
								$DataLen=0;
								//echo "</br>end:".$nDataPos;
							}
							else
							{
								$DataRec=substr($DataRec,$nDataPos,$DataLen-$nDataPos);
								$DataLen-=$nDataPos;
								echo "</br>next:".$DataLen;
							}
							
							ob_flush();
						}
						else //长度错误
						{
							echo "</br>err len:".$payloadlength;
							break;
						}
						$NeedLen=2;
					}
					$data["id{$address}_{$port}"]=$DataRec;
					
				}else{
					//if($frame===false)
					{
						echo "</br>disconnected : $address:$port ".date('His')."</br>";
						ob_flush();
						socket_close($ready);
						unset($id["id{$address}_{$port}"]);
						unset($data["id{$address}_{$port}"]);
						unset($machines[$mark]);
					}
				}
			}
		}
	}
	$curTime=microtime(true);
	$tihstiime=$curTime-$dataTime;
	if($tihstiime>15) //ping
	{
		sendPingToAll();
		$dataTime=$curTime;
	}
	$tihstiime=$curTime-$startTime;
	if($tihstiime>1 || $sendObject->bUpdated) //1秒检查一次。
	{
		$sendObject->bUpdated=false;
		$startTime=$curTime;

		/****************Check  for new command to be send **************/	
		if(count($machines)>1)
		{
			$sendObject->checkCommand($machines,$socket,$id);
			if($tihstiime>1) //1秒检查一次退出命令
			{
				//旧版
				if(file_exists("./commands/cmd.txt")){
					$packetCommand = file_get_contents("./commands/cmd.txt");
					unlink("./commands/cmd.txt");
					echo "</br></br>cmd:".$packetCommand;
					
					if($packetCommand==='exit')
					{
						break;
					}
					/*
					else if($packetCommand==='getList')
					{
						$retTxt='{"cmd":"getuserlist","stn":true}';
						sendCmdToAll($retTxt);
					}
					else if($packetCommand==='getInfo')
					{
						$uerIndex=0;
						$uerIndex=sendGetUserInfo($uerIndex);
					}*/
				}
			}
		}
	}
	
} while (true);
socket_close($socket);

function woshou($socket,$buffer){
	//截取Sec-WebSocket-Key的值并加密，其中$key后面的一部分258EAFA5-E914-47DA-95CA-C5AB0DC85B11字符串应该是固定的
	$buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
	$key  = trim(substr($buf,0,strpos($buf,"\r\n")));
	$new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
		
	//按照协议组合信息进行返回
	$new_message = "HTTP/1.1 101 Switching Protocols\r\n";
	$new_message .= "Upgrade: websocket\r\n";
	$new_message .= "Sec-WebSocket-Version: 13\r\n";
	$new_message .= "Connection: Upgrade\r\n";
	$new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
	socket_write($socket,$new_message,strlen($new_message));
	return true;
}
	
function sendGetUserInfo($uerIndex)
{
	global $userData;
	if($uerIndex<0)
		return -1;
		
	$count=count($userData);
	while($uerIndex<$count)
	{
		$record=$userData[$uerIndex];
		if(isset($record['enrollid']) && isset($record['backupnum']))
		{
			$eid=$record['enrollid'];
			$bknum=$record['backupnum'];
			if($eid!=null && $bknum!=null)
			{
				//更新列表
				$retTxt='{"cmd":"getuserinfo","enrollid":'.$eid.',"backupnum":'.$bknum.'}';
				sendCmdToAll($retTxt);
				return $uerIndex+1;
			}
		}
		$uerIndex++;
	}
	return -1;
}
function sendCmdToAll($retTxt)
{
	global $socket;
	global $machines;
	global $id;
	$size = strlen($retTxt);
	if($size>0)
	{
		$code = 129;
		$bufferCommand = ($size<126?pack('CC', $code, $size):($size<65536?pack('CCn', $code, 126, $size):pack('CCNN', $code, 127,0, $size))).$retTxt;'{"ret":"sendlog", "result":true, "cloudtime":"'.date('Y-m-d H:i:s').'"}';
		$sendCount=0;
		foreach ($machines as $ready) {
			if($ready!=$socket)
			{
				socket_getpeername($ready, $address, $port);
				if(isset($id["id{$address}_{$port}"])) //已经 收到 reg 的
				{
					socket_write($ready, $bufferCommand);
					$sendCount++;
				}
			}
		}
		echo "</br>【sendcmd】".$bufferCommand." cont=".$sendCount."</br>";
		ob_flush();
		return $sendCount;
	}
	return 0;
}
function sendPingToAll()
{
	global $socket;
	global $machines;
	global $id;
	
	$retTxt =chr(119).chr(0);
	$size =strlen($retTxt);
	$code = 129;
	$bufferCommand = ($size<126?pack('CC', $code, $size):($size<65536?pack('CCn', $code, 126, $size):pack('CCNN', $code, 127,0, $size))).$retTxt;

	$sendCount=0;
	foreach ($machines as $ready) {
		if($ready!=$socket)
		{
			socket_getpeername($ready, $address, $port);
			if(isset($id["id{$address}_{$port}"])) //已经 收到 reg 的
			{
				socket_write($ready, $bufferCommand);
				$sendCount++;
			}
		}
	}
	echo "</br>【sendping】".ord($bufferCommand[0]).ord($bufferCommand[1]).$bufferCommand." cont=".$sendCount."/".date('His');;
	ob_flush();
	return $sendCount;
}
function onFrame($packet,$ready,$address, $port,$optcode)
{
	global $userData;
	global $getList;
	global $id;
	global $uerIndex;
	global $sendObject;
	
	if($optcode==1 && $packet != '')
	{
		echo "</br>".$optcode.":";
		echo strlen($packet);
		if(strlen($packet)>200)
			echo substr($packet,0,200);
		else
			echo $packet;
	}
	else
	{
		if($optcode==9) //ping 
		{
			$retTxt =chr(118).chr(0);
			$size =strlen($retTxt);
			$code = 129;
			$bufferCommand = ($size<126?pack('CC', $code, $size):($size<65536?pack('CCn', $code, 126, $size):pack('CCNN', $code, 127,0, $size))).$retTxt;
		
			socket_write($ready, $bufferCommand);
			//echo " ret ping ".date('His');
		}
		else
			echo("</br>type:".$optcode);
		return;
	}
	$bReg=false;
	
	/* PROCESS */
		#$packet = substr(trim($frame),strpos($frame,"{")); // uncomment this line for windows
		$packet = json_decode($packet, true); /////Comment this for windows
		$retTxt='';
		if (isset($packet['cmd'])) {
			
			switch ($packet['cmd']) {
				case 'reg':
					$id["id{$address}_{$port}"] = $packet['sn'];
					$machineInfo[$packet['sn']] = $address.":".$port;
					
					$sendObject->onReg($packet['sn']);
					$retTxt = '{"ret":"reg", "result":true, "cloudtime":"'.date('Y-m-d H:i:s').'"}';
					$bReg=true;
					
					break;
				case 'sendlog':
					//$packet = str_replace('},]}','}]}',$packet); // extra
					$retTxt=$sendObject->onSendLog($packet);
					break;
					
				case 'sendqrcode':
					$retTxt = "{\"ret\":\"sendqrcode\",\"result\":true,\"access\":1,\"enrollid\":10,\"username\":\"test\"}";
					break;
				case 'senduser':
					$retTxt=$sendObject->onSendUser($packet);
					break;
				default:
					$retTxt = '{"ret":"$packet["cmd"]", "result":true, "cloudtime":"'.date('Y-m-d H:i:s').'"}';
					break;
			}
		}
		else if (isset($packet['ret'])) {
			$sn=$packet['sn'];
			switch ($packet['ret']) {
				
				case 'getalllog': //获取全部打卡记录
					//$packet = str_replace('},]}','}]}',$packet); // extra

					$sendObject->retgetAllLog($packet);
					$sendObject->updateCommandStatus($sn,'getalllog');

					$count=$packet['count'];
					if($count>0) //继续
						$retTxt = "{\"cmd\":\"getalllog\",\"stn\":false}";
					else
						return;
					break;
				case 'getnewlog':
					//$packet = str_replace('},]}','}]}',$packet); // extra
					
					//
					$sendObject->retgetNewLog($packet);
					$sendObject->updateCommandStatus($sn,'getnewlog');

					$count=$packet['count'];
					if($count>0) //继续
						$retTxt = '{"ret":"getnewlog","stn":false}';
					else
						return;
					break;
				case 'getuserlist':	//返回列表
					
					$sendObject->retgetUserList($packet);
					//缓存用户列表。
					$records =$packet['record'];
					$userData=array_merge($userData,$records); 
					echo "<br>count:".count($userData)." new:".count($records);
					//next
					$count=$packet['count'];
					if($count>0) //继续
						$retTxt='{"cmd":"getuserlist","stn":false}';
					else
						return ;

					break;

				case 'getuserinfo': //获取用户信息
					//$packet = str_replace('},]}','}]}',$packet); // extra
					$sendObject->retgetUserInfo($packet);
					return;

				case 'setuserinfo': //下发数据
				case 'deleteuser': //删除人员
				case 'initsys': 	//初始化系统	
				case 'setdevlock': //设置天时间段
				case 'setuserlock': //门禁授权
				case 'getdevinfo':	//设备信息
				case 'setusername':	//下发姓名
				case 'reboot':
				case 'getdevlock':
				case 'getuserlock':
				default:
					$sendObject->updateCommandStatus($sn,$packet['ret']);	
					$sendObject->updateDevice($sn,1);
					return ;
				}

		} else {
			var_dump("error", $packet);
		}

		/* PROCESS */
		$size = strlen($retTxt);
		if($size>0)
		{
			$code = 129;
			$buffer = ($size<126?pack('CC', $code, $size):($size<65536?pack('CCn', $code, 126, $size):pack('CCNN', $code, 127,0, $size))).$retTxt;
			socket_write($ready, $buffer);
			echo "</br>write:".ord($buffer[0]).ord($buffer[1]).":".$retTxt;
		}
}

?>
