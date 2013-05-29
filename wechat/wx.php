<?php
/**
  * wechat publication platform
  * author -> zhangchengyu
  * school -> Harbin Institutue of Technology
  * lab    -> nature-computing lab
  * version -> 1.1
  */

include("calc.php");	//调用计算器php文件
include("../renren/RenrenRestApiService.class.php");	//调用RenrenApi接口
  
  
//define your token
define("TOKEN", "hitnclab");
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();



class wechatCallbackapiTest
{
	//此函数和checkSignature()函数结合初次验证用户自己填入的token
	public function valid()
    {
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    //此函数和valid()函数结合初次验证用户自己填入的token
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	//主处理函数
	public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
				//my code 
				$RX_TYPE = trim($postObj->MsgType);
				switch ($RX_TYPE)			//在此判断用户发来的消息类型
				{
					case "text":
						$resultStr = $this->receiveText($postObj);
						break;
					case "event":
						$resultStr = $this->receiveEvent($postObj);
						break;
					case "location":
						$resultStr = $this->receiveLocation($postObj);
						break;
					default :
						$resultStr = "unknown msg type: ". $RX_TYPE;
						break;
			
				}
				echo $resultStr;
			
        }else {
        	echo "";
        	exit;
        }
    }
	//将用户在微信上发来的$keyword传到虚拟服务器端，事先运行好server.php
	private function postToServer($temp)
	{
		set_time_limit(0);
		$host = "127.0.0.1";  
		$port = 2046;  
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create socket\n"); // 创建一个Socket     
		$connection = socket_connect($socket, $host, $port) or die("Could not connect server\n");    //  连接
		
		socket_write($socket, $temp) or die("Write failed\n"); // 数据传送 向服务器发送消息	
		$receive = socket_read($socket,8192);
		//echo "close client socket!\n";
		//socket_close($socket);
		return $receive;
	}
	//如果用户发来的消息类型是text，则调用此函数处理，包括计算器模式和点歌模式
	private function receiveText($object)
    {
        $funcFlag = 0;
        $keyword = trim($object->Content);
		//$type = trim($object->MsgType);
        $resultStr = "";
        $menu = 
	"请回复下列数字了解本实验室！
	[1]简介
	[2]主要研究方向
	[3]联系方式
	[4]实验室主页信息
	彩蛋：
	回复'#(2*pi-8)'算表达式值
	回复位置信息返回二维坐标
	回复'点歌江南'返回歌曲";
	$contentStr1 = 	"	自然计算研究室成立于2005年，隶属于哈工大计算机科学与技术学院计算机科学技术研究所。\n	该室致力于研究受自然现象启发的计算，一方面，开展计算生物学和生物信息学这一新兴、交叉学科研究，即用“计算”手段认识“自然”现象;\n	另一方面，本室研究新型机器学习算法，即用“自然”机理指导“计算”研究，例如，模拟退火算法来源于物理学的固体退火原理，遗传算法是模拟生物进化的智能优化算法，人工神经网络是对人脑基本特性的抽象和模拟，等等，均是受自然现象启发得到新算法的成功例子。";
		
	$contentStr2 = 	"主要研究方向:\n（1）生物信息学：二十一世纪最具挑战和发展迅速的热点领域之一，从基因、蛋白质角度，利用组合算法、人工智能算法等手段，研究分子生物学中的计算机科学问题。\n（2）机器学习及其应用：人工智能（AI）理论研究与应用研究的桥梁，AI中最具活力的研究分支。本室深入研究半监督学习、主动学习等归纳学习及其在图像理解方面的应用。";
		
	$contentStr3 = 	"联系方式如下\n联系地址:综合实验楼320室\n联系电话:0451-86402407\n联系邮箱:maozuguo@hit.edu.cn\n联系人:\n郭茂祖:13654646103\n刘扬:18645101673";
 
        if ( strpos($keyword,'#') !== FALSE)	//计算器模式
		{
			$expression = ltrim($keyword,'#');		//用户输入的字符去除#号
			$finalword = $this->calculator($expression);//计算输入的中缀表达式
            $resultStr = $this->transmitText($object, $finalword, $funcFlag);
        }
		else if( substr($keyword,0,6) == "点歌" )	//点歌模式
			$resultStr = $this->post_music($object,$keyword);	//送入post_music函数处理
	    else if ($keyword == "1")
			$resultStr = $this->transmitText($object, $contentStr1, $funcFlag);
		else if ($keyword == "2")
			$resultStr = $this->transmitText($object, $contentStr2, $funcFlag);
		else if ($keyword == "3")
			$resultStr = $this->transmitText($object, $contentStr3, $funcFlag);
		else if ($keyword == "4")
			$resultStr = $this->transmitPicture($object,$keyword);
        else
		{
			//$temp = $this->postToRenren($keyword);
			//$this->postToServer($keyword);// post to server.php...
			$resultStr = $this->transmitText($object,$menu,$funcFlag);
		}
		return $resultStr;
    }
	
	//将微信上的信息同步到人人，并且返回发送结果
	private function postToRenren($keyword)
	{
		$rrObj = new RenrenRestApiService;
		$accesstoken='234030|6.8565308d239d907521e9ff1540bd2c78.2592000.1370757600-524041877';
		$params = array('method'=>"status.set",
						'status'=>$keyword,
						'access_token'=>$accesstoken);
		$res = $rrObj->rr_post_curl('status.set', $params);//curl函数发送请求
			
		if($res['result'] == 1)
			$hint = "同步到人人成功！";
		else
			$hint = "同步到人人失败！";
		return $hint;
	}
	
	//如果用户发来的消息类型是event,则调用此函数处理，首次订阅信息在此打印
	private function receiveEvent($object)
	{
		$contentStr = "";
		switch ($object->Event)
		{
			case "subscribe":
				$contentStr = 
	"欢迎订阅zcy的测试平台！
	请回复下列数字了解本实验室！
	[1]简介
	[2]主要研究方向
	[3]联系方式
	[4]实验室主页信息
	彩蛋：
	回复'#(2*pi-8)'算表达式值
	回复位置信息返回二维坐标
	回复'点歌江南'返回歌曲";	
				break;
	
		}
		$resultStr = $this->transmitText($object, $contentStr);
		return $resultStr;
	
	}
	//如果用户发来的消息类型是location，则调用此函数处理。。。
	private function receiveLocation($object)
	{
	
		$resultStr = $this->transmitLocation($object);
		return $resultStr;
	
	
	}
	
	//此函数是文本封装函数，任何文本字符串都需要此函数的封装才能显示
	private function transmitText($object, $content, $flag = 0)
    {
		$fromUsername = trim($object->FromUserName);	//发送方帐号
        $toUsername = trim($object->ToUserName);		//开发者微信号
        //$keyword = trim($object->Content);
        $time = time();
		//$MsgType = trim($object->MsgType);
		//$MsgId = trim($object->MsgId);
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
					
		$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, "text",$content );
        return $resultStr;
		      		
    }
	//此函数是图片封装函数，还没有完善，返回一个封装好的固定图文类消息,要做成不固定的图文。。
	private function transmitPicture($object, $keyword)
    {
		$fromUsername = $object->FromUserName;	//接收到的普通用户id
        $toUsername = $object->ToUserName;  	//公众平台id
        //$keyword = trim($object->Content);
        $time = time();
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>5</ArticleCount>
					<Articles>
					<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>
					<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>
					<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>
					<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>
					<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>
					</Articles>
					<FuncFlag>0</FuncFlag>
					</xml> ";
		$picture1 = "http://nclab.hit.edu.cn/wechat/naicha1.jpg";
		$picture2 = "http://nclab.hit.edu.cn/wechat/naicha2.jpg";
		$picture3 = "http://nclab.hit.edu.cn/wechat/naicha3.jpg";
		$picture4 = "http://nclab.hit.edu.cn/wechat/naicha4.jpg";
		$nclabUrl = "http://nclab.hit.edu.cn/";
		
		$resultStr = sprintf(	$textTpl, 
								$fromUsername, $toUsername, $time,
								"哈工大自然计算研究室主页", "" ,$picture1, $nclabUrl,
								"研究室成员-郭茂祖","",$picture1,$nclabUrl,	
								"研究室成员-刘扬","",$picture2,$nclabUrl,
								"研究室成员-刘晓燕","",$picture3,$nclabUrl,
								"研究室成员-王春宇","",$picture4,$nclabUrl);
        return $resultStr;
		      		
    }
	
	
	//此函数是音乐封装函数，返回一个音乐类消息，但是ios平台上有播放按钮，android平台上要点入链接才可以播放
	private function transmitMusic($object, $entityName,$musicUrl, $funcFlag = 0)
	{
		$fromUsername = trim($object->FromUserName);
        $toUsername = trim($object->ToUserName);
        //$keyword = trim($object->Content);
        $time = time();
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[music]]></MsgType>
					<Music>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<MusicUrl><![CDATA[%s]]></MusicUrl>
					<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
					</Music>
					<FuncFlag>0</FuncFlag>
					</xml>";
					
		$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $entityName,"",$musicUrl,$musicUrl);
        return $resultStr;
	}
	
	//此函数是地理位置封装函数，暂时用文本封装函数来封装测试
	private function transmitLocation($object)
	{
		$fromUsername = trim($object->FromUserName);		//发送方帐号
        $toUsername = trim($object->ToUserName);			//开发者微信号
		$time = time();
		$MsgType = trim($object->MsgType);
		$Location_X = trim($object->Location_X);
		$Location_Y = trim($object->Location_Y);
		$Scale = trim($object->Scale);
		$Label = trim($object->Label);
		$MsgId = trim($object->Label);
		
		$label_x = "您的当前位置x坐标:";
		$label_y = "您的当前位置y坐标:";
		$endl = "\n";
		$temp = $label_x.$Location_X.$endl.$label_y.$Location_Y.$endl.$Label.$endl;
		//$addition = "在思考用这个坐标做点什么。。。";
		
		$resultStr = $this->transmitText($object,$temp,0);
		return $resultStr;
		
		
	}
	
	//调用此函数将#号后的计算表达式(不包括等号)送入calc.php计算并返回结果
	private function calculator($keyword)
	{
		//$keyword = "(255.3*2)+33.2";
		$rpn = new Math_Rpn();
		$infix_expression = $rpn->calculate($keyword,false);
		
		return $infix_expression;
	
	}
	
	//调用此函数返回一个已封装好的字符串，输入：‘点歌可惜不是你’ 类似的字符串
	private function post_music($object,$keyword)   	
	{
		$entityName = trim(substr($keyword,6,strlen($keyword)));	//去掉前面的'点歌’字符，保留剩下的歌名
		//return $entityName;
		
		
		if ($entityName == ""){
			$contentStr = "请发送 点歌+歌名 ，如：点歌可惜不是你";
			$resultStr = $this->transmitText($object, $contentStr, 0);
			//return $contentStr;
		}
		else
		{
		$apihost = "http://api2.sinaapp.com/";
		$apimethod = "search/music/?";
		$apiparams = array('appkey'=>"0020120430", 'appsecert'=>"fa6095e113cd28fd", 'reqtype'=>"music");
		$apikeyword = "&keyword=".urlencode($entityName);
		$apicallurl = $apihost.$apimethod.http_build_query($apiparams).$apikeyword;
		$api2str = file_get_contents($apicallurl);
		$api2json = json_decode($api2str, true);
		$musicUrl = $api2json['music']['hqmusicurl'];
		
		//$resultStr = $this->transmitText($object,$musicUrl,0);

		if ($musicUrl == ""){
			$contentStr = "没有找到音乐，可能不是歌名或者检索失败，换首歌试试吧！";
			$resultStr = $this->transmitText($object,$contentStr,0);}
		else{		
			$resultStr = $this->transmitMusic($object, $entityName,$musicUrl, 0);
			}
		
		}
		//判断音乐链接url是否为空
		
		return $resultStr;

		
	}
	
	
}

?>
