<?php
namespace Waymen\Alipay;

use Log;

class AlipayPaymentSdk
{
	private $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';

	private $__https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

	private $__http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

	private $service = 'refund_fastpay_by_platform_pwd';

	private $partner;

	private $_input_charset = 'UTF-8';

	private $sign_type = 'MD5';

	private $private_key_path;

	private $public_key_path;

	private $notify_url;

	private $out_trade_no;//商户网站唯一订单号

	private $subject;//商品名称

	private $payment_type = 1;//支付类型

	private $total_fee;//总金额

	private $body;//商品详情

	private $show_url;

	private $it_b_pay = '30m';

	// private $anti_phishing_key;

	// private $exter_invoke_ip;	

	private $seller_email;//卖家支付宝帐户

	private $seller_id;//支付宝唯一用户号(以2088开头的纯16位数字)

	private $refund_date;//退款当天日期

	private $batch_no;//批次号

	private $batch_num;//退款笔数

	private $detail_data;//退款详细数据

	private $key;

	private $transport;

	private $cacert;

	public function __construct()
	{
		// $this->cacert = getcwd() . '\\cacert.pem';
	}

	/**
	 * 取得支付链接参数
	 * @return String 
	 */
	public function getPayPara()
	{
		$parameter = array(
			'service' => $this->service,
			'partner' => trim($this->partner),
			'payment_type' => $this->payment_type,
			'notify_url' => $this->notify_url,
			'seller_id' => $this->seller_id,
			'out_trade_no' => $this->out_trade_no,
			'subject' => $this->subject,
			'total_fee' => $this->total_fee,
			'body' => $this->body,
			'show_url' => $this->show_url,
			'it_b_pay' => $this->it_b_pay,
			// 'anti_phishing_key' => $this->anti_phishing_key,
			// 'exter_invoke_ip' => $this->exter_invoke_ip,
			'_input_charset' => trim(strtolower($this->_input_charset))
		);

		$para = $this->buildRequestPara($parameter);

		return $this->createLinkstringUrlencode($para);
	}

	/**
	 * 取得退款请求参数
	 * @return  String 退款需请求的url
	 */
	public function getRefundUrl()
	{		
		$parameter = array(
				"service" => $this->service,
				"sign_type" => $this->sign_type,
				"partner" => trim($this->partner),
				"notify_url" => $this->notify_url,
				"seller_id"	=> $this->seller_id,
				"seller_email" => $this->seller_email,
				"refund_date"	=> $this->refund_date,
				"batch_no"	=> $this->batch_no,
				"batch_num"	=> $this->batch_num,
				"detail_data"	=> $this->detail_data,
				"_input_charset"	=> trim(strtolower($this->_input_charset))
		);			

		$para = $this->buildRequestPara($parameter);
		return $this->alipay_gateway_new.$this->createLinkstringUrlencode($para);
	}

	/**
	 * 验证消息是否是支付宝发出的合法消息
	 */
	public function verify()
	{
		// 判断请求是否为空
		if (empty($_POST) && empty($_GET)) {
			return false;
		}

		$data = $_POST ?  : $_GET;

		// 生成签名结果
		$is_sign = $this->getSignVeryfy($data, $data['sign']);
		Log::notice('rsa debug', [
                'is_sign' => $is_sign
        ]);
		// 获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
		$response_txt = 'true';
		if (! empty($data['notify_id'])) {
			$response_txt = $this->getResponse($data['notify_id']);
		}

		// 验证
		// $response_txt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
		// isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
		if (preg_match('/true$/i', $response_txt) && $is_sign) {
			return true;
		} else {
			return false;
		}
	}

	public function setKey($key)
	{
		$this->key = $key;
		return $this;
	}

	//退款用
	public function setRefundDate($refund_date)
	{
		$this->refund_date = $refund_date;
		return $this;
	}

	//退款用
	public function setBatchNo($batch_no)
	{
		$this->batch_no = $batch_no;
		return $this;
	}

	//退款用
	public function setBatchNum($batch_num)
	{
		$this->batch_num = $batch_num;
		return $this;
	}

	//退款用
	public function setDetailData($detail_data)
	{
		$this->detail_data = $detail_data;
		return $this;
	}			

	public function setNotifyUrl($notify_url)
	{
		// $this->notify_url = urlencode($notify_url);
		$this->notify_url = $notify_url;
		return $this;
	}

	public function setReturnUrl($return_url)
	{
		// $this->return_url = urlencode($return_url);
		$this->return_url = $return_url;
		return $this;
	}	

	public function setShowUrl($show_url)
	{
		$this->show_url = $show_url;
		return $this;
	}

	public function setPartner($partner)
	{
		$this->partner = $partner;
		return $this;
	}

	public function setPrivateKeyPath($private_key_path)
	{
		$this->private_key_path = $private_key_path;
		return $this;
	}

	public function setPublicKeyPath($public_key_path)
	{
		$this->public_key_path = $public_key_path;
		return $this;
	}

	public function setCacert($cacert)
	{
		$this->cacert = $cacert;
		return $this;
	}

	public function setSellerId($seller_id)
	{
		$this->seller_id = $seller_id;
		return $this;
	}

	public function setSignType($sign_type)
	{
		$this->sign_type = $sign_type;
		return $this;
	}

	public function setSellerEmail($seller_email)
	{
		$this->seller_email = $seller_email;
		return $this;
	}	

	//手机付款用
	public function setOutTradeNo($out_trade_no){
		$this->out_trade_no = $out_trade_no;
	}

	//手机付款用
	public function setTotalFee($total_fee){
		$this->total_fee = $total_fee;
	}

	//手机付款用
	public function setSubject($subject){
		$this->subject = $subject;
	}

	//手机付款用
	public function setBody($body){
		$this->body = $body;
	}			

	public function setService($service)
	{
		$this->service = $service;
	}

	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	private function buildRequestPara($para_temp)
	{
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);

		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->sign_type));
		return $para_sort;
	}

	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildRequestMysign($para_sort)
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		$mysign = '';
		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$mysign = $this->md5Sign($prestr, $this->key);
				break;
			case 'RSA':
				$mysign = $this->rsaSign($prestr, trim($this->private_key_path));
				break;
			default:
				$mysign = '';
		}

		return $mysign;
	}

	/**
	 * 获取返回时的签名验证结果
	 * @param $para_temp 通知返回来的参数数组
	 * @param $sign 返回的签名结果
	 * @return 签名验证结果
	 */
	function getSignVeryfy($para_temp, $sign)
	{
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);

		$is_sgin = false;
		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$is_sgin = $this->md5Verify($prestr, $sign, $this->key);
				break;
			case 'RSA':
				$is_sgin = $this->rsaVerify($prestr, $this->public_key_path, $sign);
				break;
			default:
				$is_sgin = false;
		}

		return $is_sgin;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function paraFilter($para)
	{
		$para_filter = array();
		while ((list ($key, $val) = each($para)) == true) {
			if ($key == 'sign' || $key == 'sign_type' || $val == '') {
				continue;
			} else {
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	private function argSort($para)
	{
		ksort($para);
		reset($para);
		return $para;
	}

	/**
	 * RSA验签
	 * @param $data 待签名数据
	 * @param $ali_public_key_path 支付宝的公钥文件路径
	 * @param $sign 要校对的的签名结果
	 * return 验证结果
	 */
	private function rsaVerify($data, $public_key_path, $sign)
	{
		$pubKey = file_get_contents($public_key_path);
		$res = openssl_get_publickey($pubKey);
		$result = (bool) openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}

	/**
	 * RSA签名
	 * @param $data 待签名数据
	 * @param $private_key_path 商户私钥文件路径
	 * return 签名结果
	 */
	private function rsaSign($data, $private_key_path)
	{
		$priKey = file_get_contents($private_key_path);
		$res = openssl_pkey_get_private($priKey);
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		//base64编码
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	private function createLinkstring($para)
	{
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . $val . '&';
		}
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);

		//如果存在转义字符，那么去掉转义
		if (get_magic_quotes_gpc()) {
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	private function createLinkstringUrlencode($para)
	{
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . urlencode($val) . '&';
		}
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);

		//如果存在转义字符，那么去掉转义
		if (get_magic_quotes_gpc()) {
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notify_id 通知校验ID
	 * @return 服务器ATN结果
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	private function getResponse($notify_id)
	{
		$transport = strtolower(trim($this->transport));
		$partner = trim($this->partner);
		$veryfy_url = '';
		if ($transport == 'https') {
			$veryfy_url = $this->__https_verify_url;
		} else {
			$veryfy_url = $this->__http_verify_url;
		}
		$veryfy_url = $veryfy_url . 'partner=' . $partner . '&notify_id=' . $notify_id;
		$response_txt = $this->getHttpResponseGET($veryfy_url, $this->cacert);

		return $response_txt;
	}

	/**
	 * 远程获取数据，GET模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * return 远程输出的数据
	 */
	private function getHttpResponseGET($url, $cacert_url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
		curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);

		return $responseText;
	}

	/**
	 * 远程获取数据，POST模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * @param $para 请求的数据
	 * @param $input_charset 编码格式。默认值：空值
	 * return 远程输出的数据
	 */
	function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {
		if (trim($input_charset) != '') {
			$url = $url."_input_charset=".$input_charset;
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl,CURLOPT_POST,true); // post传输数据
		curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
		$responseText = curl_exec($curl);
		// var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);
		
		return $responseText;
	}

	/**
	 * 签名字符串
	 * @param $prestr 需要签名的字符串
	 * @param $key 私钥
	 * return 签名结果
	 */
	function md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}

	/**
	 * 验证签名
	 * @param $prestr 需要签名的字符串
	 * @param $sign 签名结果
	 * @param $key 私钥
	 * return 签名结果
	 */
	function md5Verify($prestr, $sign, $key) {
		$prestr = $prestr . $key;
		$mysgin = md5($prestr);

		if($mysgin == $sign) {
			return true;
		}
		else {
			return false;
		}
	}	
}
