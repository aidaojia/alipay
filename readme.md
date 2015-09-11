Alipay
======

支付宝SDK在Laravel5封装包。

该拓展包想要达到在Laravel5框架下，便捷使用支付宝的目的。（网页支付未整合进来）

## 安装

```
composer require waymen/alipay dev-master
```

更新你的依赖包 ```composer update``` 或者全新安装 ```composer install```。


## 使用

要使用支付宝SDK服务提供者，你必须自己注册服务提供者到Laravel服务提供者列表中。

找到 `config/app.php` 配置文件中，key为 `providers` 的数组，在数组中添加服务提供者。

```php
    'providers' => [
        // ...
        'Waymen\Alipay\AlipayServiceProvider',
    ]
```

运行 `php artisan vendor:publish` 命令，发布配置文件到你的项目中。

配置文件 `config/alipay.php` 为支付宝配置信息文件， `config/key` 文件夹下为支付宝需要用到的商户私钥、支付宝公钥、ca证书

## 例子

### 支付申请

```php
	// 创建移动支付单。
	$alipay = app('alipay');
	$alipay->setSignType('RSA');//要用RSA签名
	$alipay->setOutTradeNo('order_id');
	$alipay->setTotalFee('order_price');
	$alipay->setSubject('goods_name');
	$alipay->setBody('goods_description');

	// 返回签名后的支付参数给支付宝移动端的SDK。
	return $alipay->getPayPara();
```
### 发起退款
```php
    public function postRefund()
    {
        //
        $alipay = app('alipay');
        $alipay->setRefundDate($refund_date);
        $alipay->setBatchNo($batch_no);
        $alipay->setBatchNum($batch_num);
        $alipay->setDetailData($detail_data);
        $alipay->setSellerId($seller_id);
        $alipay->setSellerEmail($seller_email);
        return $alipay->getRefundUrl();  //退款请求url
    }
```

### 结果通知


#### 手机端

```php
	/**
	 * 支付宝异步通知
	 */
	public function alipayNotify()
	{
		// 验证请求。
		if (! app('alipay')->verify()) {
			Log::notice('Alipay notify post data verification fail.', [
				'data' => Request::instance()->getContent()
			]);
			return 'fail';
		}

		// 判断通知类型。
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: 支付成功，取得订单号进行其它相关操作。
				Log::debug('Alipay notify get data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}

		return 'success';
	}
```

#### 退款通知
```php
	/**
	 * 支付宝退款异步通知
	 */
	public function refundNotity()
	{
        if (! app('alipay')->verify()) {
            Log::notice('Alipay refund notify post data verification fail.', [
                'data' => Request::all()
            ]);
            return 'fail';
        } else {
            Log::info('sucess! The refund notify data', [
                'data' => Request::all(),
            ]);
            return 'sucess';
        }		
	}
```

