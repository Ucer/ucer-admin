<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/19
 * Time: 13:12
 */
namespace app\home\logic;
use think\Config;
use think\Db;
use think\model\Merge;

class UserLogic extends Merge{
    /*第三方登录*/
    public function ThirdLogin($data=array())
    {
        $openid = $data['openid']; //第三方返回唯一标识
        $oauth = $data['oauth']; //来源
        if(!$openid || !$oauth)
            return array('status'=>-1,'msg'=>'参数有误','result'=>'');
        //获取用户信息
        $user = getUserInfo($openid,3,$oauth);

        if(!$user){
            //账户不存在 注册一个
            $map['password'] = '';
            $map['openid'] = $openid;
            $map['nickname'] = $data['nickname'];
            $map['created_at'] = date('Y-m-d H:i:s',time());
            $map['oauth'] = $oauth;
            $map['head_pic'] = isset($data['head_pic'])?$data['head_pic']:'';
            $map['sex'] = isset($data['sex'])?$data['sex']:'0';
            $map['token'] = md5(time().mt_rand(1,99999));
            Db::name('users')->insert($map);
            $user = getUserInfo($openid,3,$oauth);
            // 会员注册送优惠券
            $where = "send_end_time >".time()." and ( (create_num - send_num > 0) OR (create_num = 0) ) and (type=2)";
            $coupon_list = Db::name('coupon')->where($where)->select();//用户注册类型的可用优惠券列表;
            if($coupon_list){
                foreach($coupon_list as $k=>$v){
                    //赠送优惠券
                    do{
                        $code =getRandStr1(8,0,1);
                        $check_exist = Db::name('coupon_list')->where(['code'=>$code])->count();
                    }while($check_exist);//唯一红包码

                    $datas['coupon_id'] = $v['id'];
                    $datas['users_id'] = $user['id'];
                    $datas['send_time'] = time();
                    $datas['code'] = $code;
                    Db::name('coupon_list')->insert($datas);
                    Db::name('coupon')->where(['id'=>$v['id']])->setInc('send_num');//领取数量加1
                }
            }
        }else{
            $user['token'] = md5(time().mt_rand(1,999999999));
            Db::name('users')->where(['id'=>$user['id']])->update(['token'=>$user['token'],'last_login'=>time()]);
        }
        return array('status'=>1,'msg'=>'登陆成功','data'=>$user);
    }
    /*获取当前登录用户的详细令牌*/
    public function getNowUser($uid)
    {
        if( !$uid >0) return array('status'=>0,'msg'=>'缺少参数','data'=>'');

        $user_info = Db::name('users')->find($uid);
        if(!$user_info) return array('status'=>0,'msg'=>'用户不存在','data'=>'');
        $user_info['coupon_count'] = Db::name('coupon_list')->where(['users_id'=>$uid,'use_time'=>0])->count(); //获取未使用的优惠券数量
        $user_info['collect_count'] = Db::name('goods_collect')->where(array('users_id'=>$uid))->count(); //获取收藏数量

        $where="users_id=$uid";
        $user_info['waitPay'] =Db::name('order')->where($where.Config::get('WAITPAY'))->count();//待付款;
        $user_info['waitSend'] =Db::name('order')->where($where.Config::get('WAITSEND'))->count();//待发货数量;
        $user_info['waitReceive'] =Db::name('order')->where($where.Config::get('WAITRECEIVE'))->count();//待收货数量;
        $user_info['order_count'] = $user_info['waitPay'] + $user_info['waitSend'] + $user_info['waitReceive'];
        return array('status'=>1,'msg'=>'数据获取成功','data'=>$user_info);
    }


    /*
     * 获取订单商品
     */
    public function getOrderGoods($order_id){
        $goods_list =  Db::name('order_goods')->alias('og')->join('pc_goods g','g.id=og.goods_id')->field('og.*,g.original_img as goods_img')->where(['og.order_id'=>$order_id])->select();
        return ['status'=>1,'msg'=>'success','data'=>$goods_list];
    }
}