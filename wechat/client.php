<?php  
/** 
 * File name:client.php 
 * 客户端代码 
 *  
 * @author guisu.huang 
 * @since 2012-04-11 
 */  
set_time_limit(0);  
  
$host = "127.0.0.1";  
$port = 2046;  
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create socket\n"); // 创建一个Socket  
	echo "socket:";
	echo $socket;
	echo "\n";

$connection = socket_connect($socket, $host, $port) or die("Could not connect server\n");    //  连接  
	echo "connection:";
	echo $connection;
	echo "\n";
	
	
while(true)
{
	
	
	// get input from user
	fwrite(STDOUT,"Please input a argument:");
	$arg = trim(fgets(STDIN));
	if($arg == "")
		break;
	else
		socket_write($socket, $arg) or die("Write failed\n"); // 数据传送 向服务器发送消息
	
	//$buff = socket_read($socket, 1024, PHP_NORMAL_READ); 
	//echo("Response from server was:" . $buff . "\n");  
	
}
	
echo "close client socket!\n";
socket_close($socket);
//echo "exe or??";




  
  



?>