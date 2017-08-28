<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/6
 * Time: 21:31
 */
namespace app\mobile\controller;
use app\common\validate\FormValidate;
use think\Db;
use think\Session;

class Payment extends Base
{
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code

    public function _initialize()
    {
        parent::_initialize();
        $pay_radio = $this->request->param('pay_radio');
        if (!empty($pay_radio)) {
            $pay_radio = parseUrlParam($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        } else {// 第三方 支付商返回
            $data = $this->request->param("");
            $this->pay_code = $data['pay_code'];
            unset($data['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }
        include_once ROOT_PATH . DS . 'public' . DS . 'plugins' . DS . 'payment' . DS . $this->pay_code . DS . $this->pay_code . '.class.php';//插件配置目录
        $code = '\\' . $this->pay_code; //
        $this->payment = new $code(); //实例化对应的登陆插件
    }
    /*网站提交支付方式*/
    public function getCode(){
        header("Content-type:text/html;charset=utf-8");
        $order_id = $this->request->param('order_id'); // 订单id
        // 修改订单的支付方式
        //表单令牌
        $data = $this->request->param("");
        $form = new \app\admin\model\FormValidate();
        $v_res = $form->formValidate($data);
        if($v_res['code'] ==0) $this->error($v_res['msg']);

        $payment_arr = Db::name('plugin')->where(['type' => 'payment'])->column("code,name",'code');
        Db::name('order')->where(['id' => $order_id])->update(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));

        $order = Db::name('order')->find($order_id);
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }
        //tpshop 订单支付提交
        $pay_radio = $this->request->param('pay_radio');
        $config_value = parseUrlParam($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        //微信JS支付
        if($this->pay_code == 'weixin' && Session::get('openid') && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPI($order);
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
        }
      return $this->fetch('payment/payment',[
          'code_str'=>$code_str,
          'order_id'=>$order_id
      ]);
    }
    /*页面跳转*/
    public function returnUrl(){
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        if(stripos($result['order_sn'],'recharge') !== false) {//会员充值
            $order = M('recharge')->where("order_sn = '{$result['order_sn']}'")->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                $this->display('recharge_success');
            else
                $this->display('recharge_error');
            exit();
        }
        $order = Db::name('order')->where(['order_sn' => $result['order_sn']])->find();
        $this->assign('order', $order);
        if($result['status'] == 1){
            return $this->fetch('payment/success');
        }else{
            return $this->fetch('payment/error');
        }
    }
}
