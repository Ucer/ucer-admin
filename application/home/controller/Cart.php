<?php
///**
// * Created by PhpKiller.
// * User: Across The Pacific
// * Date: 2017/3/27
// * Time: 9:07
// */
//namespace app\home\controller;
//use app\home\logic\CartLogic;
//use think\Cookie;
//use think\Session;
//
//class Cart extends Base{
//    protected $cartLogic;
//    protected $user_info;
//    protected $user_id;
//    protected function _initialize()
//    {
//        parent::_initialize();
//        $this->user_info = Session::get('user_info');//登录用户id
//        $this->user_id = Cookie::get('user_id');
//        $this->cartLogic = new CartLogic();
//        if($this->user_id){
//
//        }
//    }
//
//    /*将商品加入购物车*/
//    public function ajaxAddCart()
//    {
//        $goods_id = $this->request->param('goods_id');
//        $goods_num = $this->request->param('goods_num');
//        $goods_spec = $this->request->param('goods_spec/a');
//        //表单令牌
//        $data = $this->request->param("");
//        $validate = $this->validate($data,[['name','token']]);
//        if(true !== $validate) return ['code'=>0,'msg'=>$validate,'url'=>'','data'=>''];
//        //将商品加入购物车
//        $result = $this->cartLogic->addCart($goods_id,$goods_num,$goods_spec,$this->session_id,$this->user_id);
//        return $result;
//    }
//}