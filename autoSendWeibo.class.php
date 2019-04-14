<?php
/*
内容：使用Bing每日图自动发微博
作者：leafrainy
网站：leafrainy.cc
时间：2019年04月14日14:27:17

食用方式：
1.前往微博开放平台申请应用，微连接，审核通过后获得三个必要参数。appkey（对应下方的client_id），appsecret（对应下方的client_secret）,填写正确来源连接（对应下方的redirect_uri）。获取位置为【我的应用-选择应用，左侧网站信息-基本信息】

2.填写信息，实例化类
$config = array(
	'client_id' =>"你的appkey" , 
	'client_secret' =>"你的appsecret" , 
	'redirect_uri' =>"你的来源网址" 
);
$weibo = new autoSendWeibo($config);

3.获取code,顺次执行下面的方法，获得一个连接，复制连接到任意浏览器访问，按照提示登录你的微博，点击授权后，会跳转到一个新的连接，后面会附带一个code参数，将该参数的值拿到，填写到类文件中的 private $code=”“；中

$weibo->getCode();

4.获取access_token，顺次执行下面的方法，获得access_token.将获取到的access_token填写到类文件中的private $access_token="";中

$weibo->getAccessToken();

5.以上就获得了必要的所有信息，填写进类中后，第三步和第四部即可注释。后面按照个人喜好玩耍。注意，上述步骤必须依次来，先配置-->得code（有效期十几分钟）-->得access_token（有效期理论30天）

6.获取必应图片并下载至本地【存储路径为 img/】

$weibo->getBingPic();

7.发送纯文字的微博
$weibo->sendTextWeibo("你好啊");

8.发送图文微博（这里图片以必应的每日图片为例）

$weibo->sendPicWeibo("配字","配图");

比如用第六步的必应图片发送
$picInfo = $weibo->getBingPic(); 
$weibo->sendPicWeibo("来自bing的图片",$picInfo['localUrl'])

9.其他说明

关于 sendMsg 方法，如果需要及时通知的，苹果商店下载Bark，将自己的key替换即可。不需要的注释掉对应方法即可

*/

class autoSendWeibo{


	private $client_id = "";
	private $client_secret = "";
	private $redirect_uri = "";
	private $bark_key = "";
	private $code = "";
	private $access_token = "";
	private $bingUrl = "https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&nc=";
	private $getCodeUrl = "https://api.weibo.com/oauth2/authorize?response_type=code&";
	private $getAccessTokenUrl = "https://api.weibo.com/oauth2/access_token";
	private $sendWeiboUrl = "https://api.weibo.com/2/statuses/share.json";

	
	//构造函数，获取全局配置信息
	public function __construct($config = array()){
		$this->client_id = $config['client_id'];
		$this->client_secret = $config['client_secret'];
		$this->redirect_uri = $config['redirect_uri'];
	}

	//获取每日bing图
	public function getBingPic(){
		$picStr = $this->get($this->bingUrl.time());
		$picArray = json_decode($picStr,1);
		$res = array(
			"date" => $picArray['images'][0]['startdate'], 
			"imageUrl" => "https://cn.bing.com".$picArray['images'][0]['url'], 
			"localUrl" => "img/".$picArray['images'][0]['startdate'].".jpg",
			"copyright" => $picArray['images'][0]['copyright']
		);
		
		$this->downloadPic($res['imageUrl'],$res['localUrl']);
		if(file_exists($res['localUrl'])){
			
			$this->wlog("文件下载成功",1);
			echo "文件下载成功";

			return $res;
		}else{
			$this->wlog("文件下载失败",0);
			echo "文件下载失败";

			exit;
		}
			
		
	}

	//获取微博code
	public function getCode(){
		$url = $this->getCodeUrl."client_id=".$this->client_id."&redirect_uri=".$this->redirect_uri;

		$this->wlog("获取codeUrl成功",1);

		echo "需要复制访问的url为：".$url;

	}

	//获取微博access_token
	public function getAccessToken(){
		$tokenUrl = $this->getAccessTokenUrl;

		$tokenData = array(
				'client_id' =>$this->client_id , 
				'client_secret' => $this->client_secret, 
				'grant_type' =>"authorization_code" , 
				'code' =>$this->code , 
				'redirect_uri' =>$this->redirect_uri
			);

		$tokenJson = $this->post($tokenUrl,http_build_query($tokenData));

		$tokenArray = json_decode($tokenJson,1);

		$this->wlog("获取access_token成功,你的access_token为：".$tokenArray['access_token'],1);

		echo  "你的access_token为：".$tokenArray['access_token'];
	}

	//发送纯文字微博
	public function sendTextWeibo($text){
		$textWeiboUrl = $this->sendWeiboUrl;
		$textWeiboData = array(
			"access_token"=>$this->access_token,
			"status"=>$text.$this->redirect_uri,
			);
		
		$textResJson = $this->post($textWeiboUrl,http_build_query($textWeiboData));
		
		$textResArray = json_decode($textResJson,1);
		$weiboId = $textResArray['id'];
		
		if($weiboId){
			$this->wlog("一条纯文字微博发送成功",1);
			$this->sendMsg("一条纯文字微博发送成功");
		}else{
			$this->wlog("一条纯文字微博发送失败，错误信息为".$textResJson,0);
			$this->sendMsg("一条纯文字微博发送失败,错误信息查看日志");
		}
		

	}

	//发送文字配一图微博
	public function sendPicWeibo($text,$picPath){
		$picWeiboUrl = $this->sendWeiboUrl;
		$picWeiboData = array(
				"access_token"=>$this->access_token,
				"status"=>$text.$this->redirect_uri,
				"pic"=>$picPath

			);
		$picResJson = $this->post($picWeiboUrl,$picWeiboData,1);
		
		$picResArray = json_decode($picResJson,1);

		$weiboId = $picResArray['id'];
		
		if($weiboId){
			$this->wlog("一条图文微博发送成功",1);
			$this->sendMsg("一条图文微博发送成功");
		}else{
			$this->wlog("一条图文微博发送失败，错误信息为".$picResJson,0);
			$this->sendMsg("一条图文微博发送失败,错误信息查看日志");
		}
		
	}

	//发送通知
	private function sendMsg($msg){

		$this->get("https://api.day.app/".$this->bark_key."/".$msg);
	}

	//写日志
	private function wlog($info,$status){
		if($status){
			$status = "SUCESS";
		}else{
			$status = "ERROR";
		}
		file_put_contents('weibo_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').'【'.$status.'】'.json_encode($info,JSON_UNESCAPED_UNICODE)."\r\n",FILE_APPEND);
	}

	//下载图片
	private function downloadPic($fileUrl, $savePath){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 0); 
		curl_setopt($ch,CURLOPT_URL,$fileUrl); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$file_content = curl_exec($ch);
		curl_close($ch);
		$downloaded_file = fopen($savePath, 'w');
		fwrite($downloaded_file, $file_content);
		fclose($downloaded_file);
		
	}

	//get方法
    private function get($url, $timeoutMs = 3000) {
        $options = array(
            CURLOPT_URL                 => $url,
            CURLOPT_RETURNTRANSFER      => TRUE,
            CURLOPT_HEADER              => 0,
            CURLOPT_CONNECTTIMEOUT_MS   => $timeoutMs,
            CURLOPT_USERAGENT			=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36"
        );
        $ch = curl_init();
        curl_setopt_array( $ch, $options);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }

    //post方法
    private function post($url, $data,$isPic=0,$timeoutMs = 3000) {
        
    	if($isPic){
    		$array = explode('?', basename($data['pic'])); 
            $filename = $array[0]; 
            $filecontent = file_get_contents($data['pic']); 
            $boundary = uniqid('------------------'); 
            $MPboundary = '--' . $boundary; 
            $endMPboundary = $MPboundary . '--'; 
			$multipartbody = ''; 
            $multipartbody.= $MPboundary . "\r\n"; 
            $multipartbody.= 'content-disposition: form-data; name="access_token' . "\"\r\n\r\n";  
            $multipartbody.= $data['access_token'] . "\r\n";  
            $multipartbody.= $MPboundary . "\r\n";  
            $multipartbody.= 'content-disposition: form-data; name="status' . "\"\r\n\r\n";  
            $multipartbody.= $data['status'] . "\r\n";  
            $multipartbody.= $MPboundary . "\r\n";  
            $multipartbody.= 'Content-Disposition: form-data; name="pic"; filename="' . $filename . '"' . "\r\n";  
            $multipartbody.= "Content-Type: image/unknown\r\n\r\n";  
            $multipartbody.= $filecontent . "\r\n";  
            $multipartbody.= $endMPboundary;  
        	$header = array("content-Type: multipart/form-data; boundary=" . $boundary);
        	$data = $multipartbody;
        }else{
        	$header = array("Content-Type:application/x-www-form-urlencoded");
        }

        $options = array(
            CURLOPT_URL                 => $url,
            CURLOPT_CUSTOMREQUEST       =>"POST",
            CURLOPT_HTTPHEADER			=>$header,

            CURLOPT_POSTFIELDS          =>$data,
            CURLOPT_RETURNTRANSFER      => 1,
            CURLOPT_HEADER              => 0,
            CURLOPT_CONNECTTIMEOUT_MS   => $timeoutMs,
            CURLOPT_SSL_VERIFYPEER      => 0,
            CURLOPT_SSL_VERIFYHOST      => 0
        );
       
        $ch = curl_init();
        curl_setopt_array( $ch, $options);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }


}

$config = array(
	'client_id' =>"xx" , 
	'client_secret' =>"xx" , 
	'redirect_uri' =>"http://blog.gt520.com/" 
);

$weibo = new autoSendWeibo($config);
//$weibo->getCode();
//$weibo->getAccessToken();
//$weibo->sendTextWeibo("这是一条通过share.json自动发送的微博");
$picInfo = $weibo->getBingPic(); 
$weibo->sendPicWeibo($picInfo['copyright'],$picInfo['localUrl'])


?>