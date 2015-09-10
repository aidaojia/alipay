<?php
return [
	'key' => env('ali_key', ''),// 安全检验码，以数字和字母组成的32位字符。

	'partner_id' => env('partner_id', ''),//合作身份者id，以2088开头的16位纯数字。

	'seller_id' => env('seller_id', ''),//卖家支付宝帐户。

	'seller_email' => env('seller_email', ''),//卖家支付宝邮箱。
	
	'private_key_path' => __DIR__ . '/key/private_key.pem',// 商户私钥。

	'public_key_path' => __DIR__ . '/key/public_key.pem',// 阿里公钥。

	'cacert' => __DIR__.'/key/cacert.pem',	//ca证书

	'sign_type' => 'MD5',// 签名方式

	'notify_url' => '', // 异步通知连接。	

	'return_url' => '',// 页面跳转同步通知页面路径。
];
