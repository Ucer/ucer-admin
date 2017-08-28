<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/27
 * Time: 10:09
 */
namespace app\mobile\controller;
use app\home\logic\UserLogic;
use app\mobile\model\Order;
use app\mobile\model\Region;
use app\mobile\model\UserAddress;
use think\Config;
use think\Cookie;
use think\Db;
use think\Session;
use think\Url;

class Users extends Base{
    protected $user_info;
    protected $user_id;
    protected $userAddress;
    protected $region;
    protected $userLogic;
    protected $order;
    protected $mpage;
    protected function _initialize()
    {
        parent::_initialize();
        $this->user_info = Session::get('user_info');//登录用户id
        $this->user_id = Cookie::get('user_id');
        $this->userAddress = new UserAddress();
        $this->region = new Region();
        $this->userLogic = new UserLogic();
        $this->order = new Order();
        $this->mpage = Config::get('mobile_page')?:5;
        //登录状态判断
        $no_login = [
            'login', 'popLogin', 'doLogin', 'logout', 'verify', 'setPwd', 'finished',
            'verifyHandle', 'reg', 'sendSmsRegCode', 'findPwd', 'checkValidateCode',
            'forgetPwd', 'checkCaptcha', 'checkUsername', 'sendValidateCode', 'express',
        ];

        if(!$this->user_id  && !in_array(req('action'),$no_login)){
            header("location:".Url::build('mobile/Users/login'));
            exit;
        }
        $this->assign('user',$this->user_info);
    }
    /*用户中心*/
    public function index()
    {
        $where['users_id'] = $this->user_id;
        $order_count = Db::name('order')->where($where)->count(); // 我的订单数
        $goods_collect_count = Db::name('goods_collect')->where($where)->count(); // 我的商品收藏
        $comment_count = Db::name('comment')->where($where)->count();//  我的评论数
        $coupon_count = Db::name('coupon_list')->where($where)->count(); // 我的优惠券数量
        $user_level = Db::name('user_level')->where(['id'=>$this->user_info['level_id']])->value('name');//用户等级
        return view('users/index',[
            'user_level'=>$user_level,
            'goods_collect_count'=>$goods_collect_count,
            'order_count'=>$order_count,
            'comment_count'=>$comment_count,
            'coupon_count'=>$coupon_count
        ]);
    }
    /*用户登录*/
    public function login()
    {
        if($this->user_id > 0 ){
            header("location:".Url::build('mobile/User/index'));
            exit;
        }
        return view('users/login');
    }
    /*添加用户收货地址*/
    public function addAddress()
    {
        if($this->request->isPost()){
            $data = $this->request->param("");
            $rs = $this->userAddress->handle($data,$this->user_id,0);
            if($rs['code'] ==1 && ($this->request->param("source") == 'cart2')){//如果是从结算页面来，要回到结算页面去
//                header('Location:'.url('/mobile/Cart/aplayInfo', array('address_id' => $data['data'])));
                $this->success($rs['msg'],Url::build('/mobile/Cart/aplayInfo',['address_id'=>$data['data']]));
            }
            $rs['code'] ==1 ? $this->success($rs['msg'],Url::build('Users/addressList')):$this->error($rs['msg']);
        }
        $province = $this->region->where(['parent_id'=>0,'level'=>1])->cache(true)->select();
        return view("users/add_address",[
            'province'=>$province,
            'source'=>$this->request->param("source"),
        ]);
    }
    /*收货地址列表*/
    public function addressList()
    {
        $user_address = getUserAddress($this->user_id);
        $region_list = getRegionList();
        return view("users/address_list",[
            'user_address'=>$user_address,
            'region_list'=>$region_list,
            'source'=>$this->request->param("source")
        ]);
    }
    /*编辑收货地址*/
    public function editAddress()
    {
        $id = $this->request->param("id");
        $source = $this->request->param("source");
        $address = $this->userAddress->where(['id'=>$id,'users_id'=>$this->user_id])->find();
        if(!$address){
            $this->error('收货地址不存在');
        }
        if($this->request->isPost()){
            $data = $this->request->param("");
            $rs = $this->userAddress->handle($data,$this->user_id,$id,$address['is_default']);
            if($rs['code'] == 1 && ($source == 'cart2')){//如果是从结算页面来，要回到结算页面去
                $this->success($rs['msg'],Url::build('/mobile/Cart/aplayInfo',['address_id'=>$id]));
            }
            $rs['code'] ==1 ? $this->success($rs['msg'],Url::build('Users/addressList')):$this->error($rs['msg']);
        }
        //获取地区
        $list = getRegionList();
        $p = regionHandle($list,1,0);
        $c = regionHandle($list,2,$address['province_id']);
        $a = regionHandle($list,3,$address['city_id']);
        $t = [];
        if ($address['twon_id'] > 0) {
            $t = regionHandle($list,4,$address['area_id']);
        }
        return view("users/edit_address",[
            'source'=>$source,
            'province'=>$p,
            'city'=>$c,
            'area'=>$a,
            'twon'=>$t,
            'address'=>$address,
        ]);
    }
    /*设置为默认收货地址*/
    public function setDefault()
    {
        $data = $this->request->param("");
        $id =$data['id'];
        $this->userAddress->where(['users_id'=>$this->user_id])->update(['is_default'=>0]);
        $this->userAddress->where(['users_id'=>$this->user_id,'id'=>$id])->update(['is_default'=>1]);
        if(($this->request->param("source") == 'cart2')){//如果是从结算页面来，要回到结算页面去
            header('Location:' . url('/mobile/Cart/aplayInfo', array('address_id' =>$id)));
            exit;
        }
        $this->success('操作成功',Url::build('Users/addressList'));
    }
    /*删除收货地址*/
    public function delAddress()
    {
        $id = $this->request->param("id");
        return $this->userAddress->del($id,$this->user_id);
    }
    /*订单列表*/
    public function orderList()
    {
        $where = "users_id = ".$this->user_id;
        $type = $this->request->param("type")?strtoupper($this->request->param("type")):'';
        //搜索条件
       if($type){
            $where .= Config::get($type);
        }
        $per = $this->request->param('p')?:'1';
        $query = ['type'=>$type];
        $order_list = $this->order->getOrderList($where,$this->mpage,$query,$per);
        $this->assign('order_status', Config::get('ORDER_STATUS'));
        $this->assign('shipping_status', Config::get('SHIPPING_STATUS'));
        $this->assign('pay_status', Config::get('PAY_STATUS'));
        $this->assign('list', $order_list['data']);
        $this->assign('active', 'order_list');
        $this->assign('active_status', $type);
        $this->assign('type', $type);

        if(input('param.is_ajax')> 0){
            return $this->fetch('users/ajax_order_list');
        }else{
            return $this->fetch('users/order_list');
        }

    }
    /*订单详细*/
    public function orderInfo()
    {
        $id = $this->request->param("order_id");
        $order_info = Db::name('order')->where(['users_id'=>$this->user_id,'id'=>$id])->find();
        $order_info = setBtnStatus($order_info);
        if(!$order_info) $this->error('没有找到此订单相关信息');

        $region_list = getRegionList();
        $order_info['invoice_number'] = Db::name('ship_order')->where(['order_id'=>$id])->value('invoice_number');//物流单号

        $logic = new UserLogic();
        $data = $logic->getOrderGoods($id);
        $order_info['goods_list'] = $data['data']?:[];

        //获取订单操作记录
        $order_action = Db::name('order_action')->where(array('order_id' => $id))->select();

        $this->assign('order_status', Config::get('ORDER_STATUS'));
        $this->assign('shipping_status', Config::get('SHIPPING_STATUS'));
        $this->assign('pay_status', Config::get('PAY_STATUS'));

        return view('users/order_info',[
            'order_info'=>$order_info,
            'region_list'=>$region_list,
            'order_action'=>$order_action,
        ]);
    }
}