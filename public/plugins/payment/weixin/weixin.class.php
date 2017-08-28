<?php
/**
 * tpshop 微信支付插件
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */

class weixin extends \think\Model
{    
    public $tableName = 'plugin'; // 插件表        
    public $alipay_config = array();// 支付宝支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();
                
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");

        $paymentPlugin = \think\Db::name('plugin')->where(['code'=>'weixin','type' => 'payment'])->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
        WxPayConfig::$appid = $config_value['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        WxPayConfig::$mchid = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        WxPayConfig::$key = $config_value['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        WxPayConfig::$appsecret = $config_value['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，                                      
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {       
            $notify_url = \think\Url::build('home/payment/notifyUrl/pay_code/weixin');// 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            //$notify_url = C('site_url').U('Home/Payment/notifyUrl',array('pay_code'=>'weixin')); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            //$notify_url = C('site_url')."/index.php?m=Home&c=Payment&a=notifyUrl&pay_code=weixin";
            $input = new WxPayUnifiedOrder();
            $input->SetBody("TPshop商品"); // 商品描述
            $input->SetAttach("weixin"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetOut_trade_no($order['order_sn'].time()); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
            $input->SetTotal_fee($order['order_amount']*100); // 订单总金额，单位为分，详见支付金额
            $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            $input->SetTrade_type("NATIVE"); // 交易类型   取值如下：JSAPI，NATIVE，APP，详细说明见参数规定    NATIVE--原生扫码支付
            $input->SetProduct_id("123456789"); // 商品ID trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
            $notify = new NativePay();
            $result = $notify->GetPayUrl($input); // 获取生成二维码的地址
            $url2 = $result["code_url"];
            return '<img alt="模式二扫码支付" src="/index.php?m=Home&c=Index&a=qr_code&data='.urlencode($url2).'" style="width:110px;height:110px;"/>';        
    }    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {                        
        require_once("example/notify.php");  
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);       
    }
    
    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        // 微信扫码支付这里没有页面返回
    }

    function getJSAPI($order){
    	if(stripos($order['order_sn'],'recharge') !== false){
    		$go_url = U('Mobile/User/points',array('type'=>'recharge'));
    		$back_url = U('Mobile/User/recharge',array('order_id'=>$order['order_id']));
    	}else{
    		$go_url = U('Mobile/User/order_detail',array('id'=>$order['order_id']));
    		$back_url = U('Mobile/Cart/cart4',array('order_id'=>$order['order_id']));
    	}
        //①、获取用户openid
        $tools = new JsApiPay();
        //$openId = $tools->GetOpenid();
        $openId = $_SESSION['openid'];
        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'].time());
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("tp_wx_pay");
        $input->SetNotify_url(SITE_URL.'/index.php/Home/Payment/notifyUrl/pay_code/weixin');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = WxPayApi::unifiedOrder($input);
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);exit;  
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        $html = <<<EOF
	<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',$jsApiParameters,
			function(res){
				//WeixinJSBridge.log(res.err_msg);
				 if(res.err_msg == "get_brand_wcpay_request:ok") {
				    location.href='$go_url';
				 }else{
				 	alert(res.err_code+res.err_desc+res.err_msg);
				    location.href='$back_url';
				 }
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	callpay();
	</script>
EOF;
        
    return $html;

    }

}