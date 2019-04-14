# autoSendWeibo
调用微博API实现，自动发自定义纯文字，自定义图文微博，内含通知和必应每日一图快捷功能
# 使用方法

#### 1.前往微博开放平台申请应用，微连接，审核通过后获得三个必要参数。appkey（对应下方的client_id），appsecret（对应下方的client_secret）,填写正确来源连接（对应下方的redirect_uri）。获取位置为【我的应用-选择应用，左侧网站信息-基本信息】

#### 2.填写信息，实例化类
```
$config = array(
	'client_id' =>"你的appkey" , 
	'client_secret' =>"你的appsecret" , 
	'redirect_uri' =>"你的来源网址" 
);
$weibo = new autoSendWeibo($config);
```

#### 3.获取code,顺次执行下面的方法，获得一个连接，复制连接到任意浏览器访问，按照提示登录你的微博，点击授权后，会跳转到一个新的连接，后面会附带一个code参数，将该参数的值拿到，填写到类文件中的 private $code=”“；中
```
$weibo->getCode();
```

#### 4.获取access_token，先注释掉第三步的方法，然后顺次执行下面的方法，获得access_token.将获取到的access_token填写到类文件中的private $access_token="";中
```
$weibo->getAccessToken();
```

#### 5.以上就获得了必要的所有信息，填写进类中后，第三步和第四步即可注释。后面按照个人喜好玩耍。注意，上述步骤必须依次来，先配置-->得code（有效期十几分钟）-->得access_token（有效期理论30天）

#### 6.获取必应图片并下载至本地【存储路径为 img/】
```
$weibo->getBingPic();
```

#### 7.发送纯文字的微博
```
$weibo->sendTextWeibo("你好啊");
```

#### 8.发送图文微博（这里图片以必应的每日图片为例）

```
$weibo->sendPicWeibo("配字","配图");
```
比如用第六步的必应图片发送
```
$picInfo = $weibo->getBingPic(); 
$weibo->sendPicWeibo("来自bing的图片",$picInfo['localUrl'])
```

#### 9.其他说明

关于 sendMsg 方法，如果需要及时通知的，苹果商店下载Bark，将自己的key替换即可。不需要的注释掉对应方法即可
