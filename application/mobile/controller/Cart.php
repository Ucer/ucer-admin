<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/27
 * Time: 9:07
 */
namespace app\mobile\controller;
use app\admin\model\FormValidate;
use app\home\logic\CartLogic;
use think\Config;
use think\Cookie;
use think\Db;
use think\Session;
use think\Url;

class Cart extends Base{
    protected $cartLogic;
    protected $user_info;
    protected $user_id;
    protected function _initialize()
    {
        parent::_initialize();
        $this->user_info = Session::get('user_info');//登录用户
        $this->user_id = Cookie::get('user_id');
        $this->cartLogic = new CartLogic();
        //如果是已登录的会员--修改购物车中的商品价格为会员折扣
        if($this->user_id){
//            $user = Db::name('users')->find($this->user_id);
            $user = Db::name('users')->alias('u')->field('u.*,ul.name as level_name,ul.discount as level_discount,ul.amount as level_amount')->join('pc_user_level ul','u.level_id = ul.id AND u.id='.$this->user_id)->find();
            $user['level'] = Db::name('user_level')->where(['id'=>$user['level_id']])->value('name');
            Session::set('user_info',$user);  //覆盖session 中的 user
            $this->assign('user_info',$user); //存储用户信息

            // 给用户计算会员价 登录前后不一样
            $user['discount'] = (empty($user['discount'])) ? 1 : $user['discount'];
            $sql = "UPDATE pc_cart SET discount_price = shop_price * {$user['discount']} WHERE (users_id={$user['id']} OR session_id='{$this->session_id}') AND prom_type=0";
            Db::execute($sql);
        }

    }

    /*将商品加入购物车*/
    public function ajaxAddCart()
    {
        $goods_id = $this->request->param('goods_id');
        $goods_num = $this->request->param('goods_num');
        $goods_spec = $this->request->param('goods_spec/a');
        //表单令牌
//        $data = $this->request->param("");
//        $form = new FormValidate();
//        $v_res = $form->formValidate($data);
//        if($v_res['code'] ==0) return ['code'=>0,'msg'=>$v_res['msg'],'url'=>'','data'=>''];
        //将商品加入购物车
        $result = $this->cartLogic->addCart($goods_id,$goods_num,$goods_spec,$this->session_id,$this->user_id);
        return $result;
    }

    /*ajax请求购物车列表*/
    public function headerCartList()
    {

    }

    /*购物车页面*/
    public function cart()
    {
        return view('cart/cart');
    }
    /*获取购物车中的商品列表*/
    public function ajaxCartList()
    {
        //修改处理
        $post_goods_num = $this->request->param('goods_num/a');//goods_num 购物车商品数量
        $post_cart_select = $this->request->param('cart_select/a');//选中状态
        $where = " session_id = '$this->session_id' "; // 默认按照 session_id 查询
        $this->user_id && $where = " users_id = ".$this->user_id; // 如果这个用户已经等了则按照用户id查询
        $cartList = Db::name('cart')->where($where)->column("id,goods_num,selected,prom_type,goods_prom_id");
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val){
                $data['goods_num'] = $val < 1 ? 1 : $val;
                if(isset($cartList[$key])){//不存在则说明是删除购物车
                    if($cartList[$key]['prom_type'] == 1) //限时抢购 不能超过购买数量
                    {
                        $flash_sale = Db::name('flash_sale')->where(["id" => $cartList[$key]['goods_prom_id']])->find();
                        $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                    }
                }
                $data['selected'] = isset($post_cart_select[$key]) ? 1 : 0 ;
                if(isset($cartList[$key])) {
                    if (($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) {
                        Db::name('cart')->where(["id" => $key])->update($data);
                    }
                }
            }
            $this->assign('select_all', $this->request->param('select_all')); // 全选框
        }

        //页面渲染
        $result = $this->cartLogic->cartList($this->user_info,$this->session_id,1,1);
        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0, 'atotal_fee' =>0, 'acut_fee' =>0, 'anum' => 0);
        }
      if(!isset($result['cartList'])) $result['cartList']=[];
       return view("cart/ajax_cart_list",[
           'cartList'=>$result['cartList'],
           'total_price'=>$result['total_price'],//总计
       ]);
    }
    /*删除购物车中的商品*/
    public function ajaxDelCart()
    {
        $ids = $this->request->param('ids/a');
        if($ids) $ids = implode(',',$ids);
        $result = Db::name("cart")->where(['id'=>['in',$ids]])->delete(); // 删除
        $result?$this->success('删除成功'):$this->error('出错了,请稍后再试');
    }
    /*去结算前的表单令牌验证*/
    public function validatea()
    {
        //表单令牌
        $data = $this->request->param("");
        $form = new FormValidate();
        $v_res = $form->formValidate($data);
        if($v_res['code'] ==0){
            return ['code'=>0,'msg'=>$v_res['msg'],'url'=>'','data'=>''];
        }else{
            return ['code'=>1,'msg'=>$v_res['msg'],'url'=>'','data'=>''];
        }
    }
    /*点击结算后的页面*/
    public function aplayInfo()
    {
        //前置验证方法
        $id = $this->request->param("address_id");
        if($id){
            $address = Db::name('user_address')->where(['users_id'=>$this->user_id,'id'=>$id])->find();//默认收货地址
        }else{
            $address = Db::name('user_address')->where(['users_id'=>$this->user_id,'is_default'=>1])->find();//默认收货地址
        }
        $this->beforeCart($address);

        $result = $this->cartLogic->cartList($this->user_info, $this->session_id,1,1); // 获取购物车商品
        $shippingList = Db::name('plugin')->where(['type' => 'shipping','status' => 1])->select();// 物流公司

        //找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的优惠券列表
        $coupon_list = Db::name('coupon')->alias('c')->field('c.name,c.money,c.condition,cl.*')->join('pc_coupon_list cl','c.id=cl.coupon_id AND c.type in (0,1,2,3) AND order_id=0')->where(['cl.users_id'=>$this->user_id,'c.use_end_time'=>['gt',time()],'c.condition'=>['elt',$result['total_price']['total_fee']]])->select();
        return view("cart/applay_info",[
            'source'=>$this->request->param("source"),
            'address'=>$address,
            'cart_list'=>$result,
            'coupon_list'=>$coupon_list,
            'total_price'=>$result['total_price'],//总计
            'shippingList'=>$shippingList,
        ]);
    }
    /*aplayInfo前置验证方法*/
    private function beforeCart($adress){
        if($this->user_id <1){
            $this->error('请先登录',Url::build('mobile/Users/login'));
        }
        if(empty($adress)){
            header("Location: ".Url::build('mobile/Users/addAddress',array('source'=>'cart2')));
            exit;
        }
       if($this->cartLogic->cartCount($this->user_id,1)==0){
           $this->error ('您还没选中任何商品',Url::build('mobile/Cart/cart'));
       }
    }
    /* ajax 获取订单商品价格 或者提交 订单*/
    public function cart3()
    {
        if($this->user_id <1){
           return  ['code'=>-10,'msg'=>'登录失效请重新登录','url'=>'','data'=>''];
        }
        $data = $this->request->param("");
        if($data['address_id'] <1){
            $this->error('请先填写收货人信息');
        }
        if($this->cartLogic->cartCount($this->user_id,1) <1) $this->error('你的购物车没有选中商品');

        $address = Db::name('user_address')->where(['users_id'=>$this->user_id,'id'=>$data['address_id']])->find();//收货地址
        $order_goods =  Db::name('cart')->where(['users_id'=>$this->user_id,'selected'=>1])->select();//购物车中被选中的商品
        $couponCode='';//直接输入优惠券码
        $shipping_price=0;//物流价格
        $invoice_title='';//发票
        $result = calculatePrice($this->user_id,$order_goods,$data['shipping_code'],$shipping_price,$address['province_id'],$address['city_id'],$address['area_id'],$data['pay_points'],$data['user_money'],$data['coupon_id'],$couponCode);
        if($result['code'] !==1) return $result;

        // 订单满额优惠活动 TODO
//        $order_prom = getOrderPromotion($result['data']['order_amount']);
//        $result['data']['order_amount'] = 0;
        $result['data']['order_prom_id'] = 0 ;
        $result['data']['order_prom_amount'] = 0 ;
        $datas = $result['data'];
        $car_price = array(
            'postFee'      => $datas['shipping_price'], // 物流费
            'couponFee'    => $datas['coupon_price'], // 优惠券
            'balance'      => $datas['user_money'], // 使用用户余额
            'pointsFee'    => $datas['integral_money'], // 积分支付
            'payables'     => $datas['order_amount'], // 应付金额
            'goodsFee'     => $datas['goods_price'],// 商品价格
            'order_prom_id' => $datas['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $datas['order_prom_amount'], // 订单优惠活动优惠了多少钱
        );
        // 提交订单
        if($this->request->isPost() && ($this->request->param("act") =='submit_order' ) ){
            if(!$data['coupon_id'] && !empty($couponCode)){
                // 根据优惠码查找优惠券id TODO
            }
            $result = $this->cartLogic->addOrder($this->user_id,$data['address_id'],$data['shipping_code'],$invoice_title,$data['coupon_id'],$car_price); // 添加订单
            return $result;
        }
        return ['code'=>1,'msg'=>'计算价钱成功','url'=>'','data'=>$car_price];

    }
    /*订单支付页面*/
    public function cart4()
    {
        $order_id = $this->request->param('order_id');
        $order = Db::name('order')->where(['id' => $order_id])->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){ //TODO
//            $order_detail_url = url("mobile/Users/order_detail",array('id'=>$order_id));
//            header("Location: $order_detail_url");
        }
        $paymentList = Db::name('plugin')->where(['type'=>'payment','status'=>1,'code'=>['in',['weixin','cod']]])->column('*','code');//支付插件列表

        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $paymentList = Db::name('plugin')->where(['type'=>'payment','status'=>1,'code'=>['in',['weixin','cod']]])->column('*','code');//支付插件列表
        }
        $bankCodeList=[];

        if($paymentList){
            foreach($paymentList as $key => $val){
                $val['config_value'] = unserialize($val['config_value']);
                if($val['config_value']['is_bank'] == 2 && (isset($val['config_value']['is_bank']))) {
                    $bankCodeList[$val['code']] = unserialize($val['bank_code']);
                }
            }
        }
        $bank_img = Config::get('bank_img'); // 银行对应图片
        return view("cart/cart4",[
            'order'=>$order,//订单详细
            'pay_date'=>date('Y-m-d', strtotime("+1 day")),//订单过期时间
            'paymentList'=>$paymentList,//支付插件列表
            'bank_img',$bank_img,//银行对应图片
        ]);
    }
}