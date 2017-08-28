<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/28
 * Time: 19:29
 */
namespace app\home\logic;
use app\mobile\logic\Jssdk;
use think\Config;
use think\Cookie;
use think\Db;
use think\Model;

class CartLogic extends Model{
    /**
     * 加入购物车方法
     * @param type $goods_id  商品id
     * @param type $goods_num   商品数量
     * @param type $goods_spec  选择规格
     * @param type $user_id 当前用户id
     */
    public function addCart($goods_id,$goods_num,$goods_spec,$session_id,$user_id = 0)
    {
        $where = "session_id = '$session_id' ";
        if($user_id){
            $where .= "  OR users_id= $user_id ";
        }
        $goods_info =Db::name('goods')->where(['is_on_sale'=>1,'id'=>$goods_id])->find();

        $check = $this->beforeAddCare($goods_num,$user_id,$where,$goods_info);
        if($check['code'] ==0){
            return ['code'=>0,'msg'=>$check['msg'],'url'=>$check['url']];
        }
        $specGoodsPriceList = Db::name('goods_spec_price')->where(['goods_id'=>$goods_id])->column('key_ids,goods_id,id,key_name,price,store_num,code');//获取商品对应的规格价钱 库存 条码
        if(!empty($specGoodsPriceList) && empty($goods_spec)){  // 有商品规格 但是前台没有传递过来
            return ['code'=>-1,'msg'=>'必须选择商品规格','url'=>'','data'=>''];
        }

        //限时抢购 不能超过购买数量
        if($goods_info['prom_type'] == 1){
            $now = date('Y-m-d H:i:s',time());
            $flash_sale = Db::name('flash_sale')->where(['id'=>$goods_info['goods_prom_id'],'start_time'=>['lt',$now],'end_time'=>['gt',$now],'goods_num'=>['gt','buy_num']])->find();//抢购活动
            if(!$flash_sale){
                return ['code'=>0,'msg'=>'商品已经被抢完了','url'=>'','data'=>''];
            }

            $cart_goods_num = Db::name('cart')->where("($where) AND goods_id=$goods_id")->count(); // 查找购物车商品总数量
            // 如果购买数量 大于每人限购数量
            if($flash_sale['buy_limit'] < ($cart_goods_num+$goods_num)){
                $error_msg = '';
                $cart_goods_num && $error_msg = "您当前购物车已有 $cart_goods_num 件!";
                return ['code'=>0,'msg'=>"每人限购 {$flash_sale['buy_limit']}件 $error_msg",'url'=>'','data'=>''];
            }
            // 如果剩余数量 不足 限购数量, 就只能买剩余数量
            $surplus_stock =$flash_sale['goods_num'] - $flash_sale['buy_num'];
            if($surplus_stock < $flash_sale['buy_limit']){
                return ['code'=>0,'msg'=>"库存不足,你只能购买".($surplus_stock)."件了.",'url'=>'','data'=>''];
            }
        }

        //处理商品规格
        $spec_item = [];
        $spec_key = $spec_price = '0';//初始化
        if($goods_spec){// ['cpu' => 9]
            foreach($goods_spec as $k=>$v){
                $spec_item[] = $v; // 所选择的规格项id
            }
            sort($spec_item);
            $spec_key = implode('_', $spec_item);
            if($specGoodsPriceList[$spec_key]['store_num'] < $goods_num){
                return ['code'=>0,'msg'=>"该规格的商品库存不足,请选择其它规格或联系客服",'url'=>'','data'=>''];
            }
            $spec_price = $specGoodsPriceList[$spec_key]['price']; // 获取规格指定的价格
        }

        $price = $spec_price ? $spec_price : $goods_info['shop_price']; // 如果商品规格没有指定价格则用商品原始价格

        // 商品参与促销
        if($goods_info['prom_type'] > 0) {
            $prom = getGoodsPromotion($goods_info,$user_id);//获取促销信息
            if($prom['is_end'] >0){
                switch($prom['is_end']){
                    case 1 :
                        $msg = '活动已经结束';
                        break;
                    case 2 :
                        $msg = '商品已售馨';
                        break;
                    case 3 :
                        $msg = '秒杀功能还在开发中';
                        break;
                }
                return ['code'=>0,'msg'=>$msg,'url'=>''];
            }
            $price = $prom['price'];
            $goods_info['prom_type'] = $prom['prom_type'];
            $goods_info['goods_prom_id']   = $prom['goods_prom_id'];
        }

        $data = array(
            'users_id'         => $user_id?:'0',   // 用户id
            'session_id'      => $session_id,   // sessionid
            'goods_id'        => $goods_id,   // 商品id
            'goods_sn'        => $goods_info['goods_sn'],   // 商品货号
            'goods_name'      => $goods_info['goods_name'],   // 商品名称
            'market_price'    => $goods_info['market_price'],   // 市场价
            'shop_price'     => $price,  // 购买价
            'discount_price' => $price,  // 会员折扣价 默认为 购买价
            'goods_num'       => $goods_num, // 购买数量
            'spec_key'        => "{$spec_key}", // 规格key
            'spec_key_name'   => "{$specGoodsPriceList[$spec_key]['key_name']}", // 规格 key_name
            'bar_code'        => "{$specGoodsPriceList[$spec_key]['code']}", // 商品条形码
            'add_time'        => time(), // 加入购物车时间
            'prom_type'       => $goods_info['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'goods_prom_id'         => $goods_info['goods_prom_id'],   // 活动id
        );
        $wherea = " goods_id = $goods_id AND (spec_key = '$spec_key') AND (shop_price = {$price})"; // 查询购物车是否已经存在这商品

        if($user_id > 0){
            $wherea .= " AND ( (session_id = '$session_id') OR (users_id = $user_id) ) ";
        }else{
            $wherea .= " AND (session_id = '$session_id') ";
        }
        $catr_goods = Db::name('cart')->where($wherea)->find(); // 查找购物车是否已经存在该商品
        // 如果商品购物车已经存在
        if($catr_goods){
            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if(($catr_goods['goods_num'] + $goods_num) > $goods_info['store_count']){
                $goods_num = 0;
                return array('code'=>0,'msg'=>'库存不足','url'=>'','data'=>'');
            }
            $result = Db::name('cart')->where(['id'=>$catr_goods['id']])->setInc("goods_num",$goods_num);
            $cart_count = cartGoodsNum($user_id,$session_id); // 查找购物车数量
//            Cookie::set('cn',$cart_count,['expire'=>111186400,'path'=>'/','secure'=>true]);
            Cookie::set('cn',$cart_count,36003600);
            return array('code'=>1,'msg'=>'成功加入购物车','url'=>'','data'=>$cart_count);
        }else{//插入购物车
            Db::name('cart')->insertGetId($data);
            $cart_count = cartGoodsNum($user_id,$session_id); // 查找购物车数量
            Cookie::set('cn',$cart_count,36003600);
            return array('code'=>1,'msg'=>'成功加入购物车','url'=>'','data'=>$cart_count);
        }
        $cart_count = cartGoodsNum($user_id,$session_id); // 查找购物车数量
        return array('code'=>0,'msg'=>'加入购物车失败','url'=>'','data'=>$cart_count);

    }
    /**
     * 商品加入购物车前先作验证
     */
    private function beforeAddCare($goods_num,$user_id,$where,$goods_info){
        if($goods_num <1) {
            return ['code'=>0,'msg'=>'商品数量不能少于1个','url'=>'','data'=>''];
        }
        if(empty($goods_info)) {
            return ['code'=>0,'msg'=>'商品不存在或已下架','url'=>'','data'=>''];
        }
        if($goods_info['store_count'] <$goods_num) {
            return ['code'=>0,'msg'=>'商品库存不足','url'=>'','data'=>''];
        }
        if($goods_info['goods_prom_id'] >0 && ($user_id ==0)) {
            return ['code'=>0,'msg'=>'活动商品需要登录后才能购买','url'=>'','data'=>''];
        }
        $catr_count = Db::name('cart')->where($where)->count(); // 查找购物车商品总数量
        if($catr_count >=20) {
            return ['code'=>0,'msg'=>'购物车最多只能放20个商品','url'=>'','data'=>''];
        }
        return  ['code'=>1,'msg'=>'验证通过','url'=>'','data'=>''];
    }
    /**
     * 购物车列表
     * @param type $user_info   用户
     * @param type $session_id  session_id
     * @param type $selected  是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cartList($user_info,$session_id='',$selected=0,$mode=0)
    {
        $where = "1";
        if($user_info['id']){//如果用户已经登录则按照用户的id来查
            $where .=" AND users_id = ".$user_info['id'];
            // 给用户计算会员价 登录前后不一样
        }else{
            $where .= " AND session_id = '$session_id'";
            $user['user_id'] = 0;
        }
        $cartList = Db::name('cart')->where($where)->select();  // 获取购物车商品
        $anum = $total_price =  $cut_fee = 0;
//        if(empty($cartList)){//购物车没有商品、直接返回
//            return array('status'=>1,'msg'=>'','result'=>array('cartList' =>$cartList, 'total_price' => $total_price));
//        }
        foreach($cartList as $k=>$val){
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['discount_price'];//每价商品的单价*数量
            $cartList[$k]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量
            $anum += $val['goods_num'];
            $cartList[$k]['goods_img'] = Db::name('goods')->where(['id'=>$val['goods_id']])->value('original_img');

            // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
            if($selected == 1 && $val['selected'] == 0){
                continue;
            }
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['discount_price'];//省多少钱
            $total_price += $val['goods_num'] * $val['discount_price'];//商品价格
        }
        $total_price = array('total_fee' =>$total_price , 'cut_fee' => $cut_fee,'num'=> $anum,); // 总计
        $result = array('cartList' => $cartList, 'total_price' => $total_price);
        Cookie::set('cn',$anum,36003600);

        if($mode == 1) return $result;
        return array('code'=>1,'msg'=>'','url'=>'','data'=>$result);
    }
    /**
     * 查看购物车的商品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cartCount($user_id,$mode = 0){
        $count = Db::name('cart')->where(['users_id' => $user_id,'selected'=>1])->count();
        if($mode == 1) return  $count;

        return array('code'=>1,'msg'=>'','data'=>$count);
    }

    /**
     * 获取用户可以使用的优惠券
     * @param type $user_id  用户id
     * @param type $coupon_id 优惠券id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function getCouponMoney($user_id, $coupon_id,$mode)
    {
        $couponlist = Db::name('coupon_list')->where(['users_id' => $user_id ,'id'=>$coupon_id])->find(); // 获取用户的优惠券
        if(empty($couponlist)) {
            if($mode == 1) return 0;
            return array('code'=>1,'msg'=>'','data'=>0);
        }

        $coupon = Db::name('coupon')->where(['id' =>$couponlist['coupon_id']])->find(); // 获取 优惠券类型表
        $coupon['money'] = $coupon['money'] ? $coupon['money'] : 0;

        if($mode == 1) return $coupon['money'];
        return array('code'=>1,'msg'=>'','data'=>$coupon['money']);

    }

    /**
     * 计算商品的的运费
     * @param type $shipping_code 物流 编号
     * @param type $province  省份
     * @param type $city     市
     * @param type $district  区
     * @return int  //TODO
     */
    function cartFreight2($shipping_code,$province,$city,$district,$weight){
        if($weight == 0) return 0; // 商品没有重量
        if($shipping_code == '') return 0;//没有选物流

        // 如果选了物流

        // 先根据 镇 县 区找 shipping_area_id
        $shipping_area_ida = Db::name('shipping_area')->where(['shipping_code'=> $shipping_code])->column('id');//先找到物流的配送区域id

        $shipping_area_id = $this->getSid($shipping_area_ida,$district);
        // 先根据市区找 shipping_area_id
        if(!$shipping_area_id){
            $shipping_area_id = $this->getSid($shipping_area_ida,$city);
        }
        // 市区找不到 根据省份找shipping_area_id
        if(!$shipping_area_id){
            $shipping_area_id = $this->getSid($shipping_area_ida,$province);
        }
        // 省份找不到 找默认配置全国的物流费
        if(!$shipping_area_id){
            $shipping_area_id =  Db::name('shipping_area')->where(['shipping_code'=>$shipping_code,'is_default'=>1])->value('id');
        }
        if(!$shipping_area_id) return 0;

        /// 找到了 shipping_area_id  找config
        $shipping_config = Db::name('shipping_area')->where(['shipping_code'=> $shipping_code])->value('config');
        $shipping_config  = unserialize($shipping_config);
        $shipping_config['money'] = $shipping_config['money'] ? $shipping_config['money'] : 0;

        // 1000 克以内的 只算个首重费
        if($weight < $shipping_config['first_weight'])
        {
            return $shipping_config['money'];
        }

        // 超过 1000 克的计算方法
        $weight = $weight - $shipping_config['first_weight']; // 续重
        $weight = ceil($weight / $shipping_config['second_weight']); // 续重不够取整
        $freight = $shipping_config['money'] +  $weight * $shipping_config['add_money']; // 首重 + 续重 * 续重费

        return $freight;

    }
    /*公共获取shipping_area_id*/
    private function getSid($shipping_area_id,$district){
        $shipping_area_id = Db::name('area_region')->where(['shipping_area_id'=>['in',$shipping_area_id],'region_id'=>$district])->value('shipping_area_id');
        return $shipping_area_id;
    }
    /**
     *  添加一个订单
     * @param type $user_id  用户id
     * @param type $address_id 地址id
     * @param type $shipping_code 物流编号
     * @param type $invoice_title 发票
     * @param type $coupon_id 优惠券id
     * @param type $car_price 各种价格
     * @return type $order_id 返回新增的订单id
     */
    public function addOrder($user_id,$address_id,$shipping_code,$invoice_title,$coupon_id = 0,$car_price)
    {
        // 防灌水 1天只能下 50 单
        $order_count =Db::name('order')->where(['users_id'=>$user_id,'order_sn'=>['like',date('Ymd').'%']])->count() ;// 查找订单商品总数量
        if($order_count >= 50) return ['code'=>0,'msg'=>'一天只能下50个订单','url'=>'','data'=>''];

        // step.0插入订单 order
        $address =Db::name('user_address')->where(['id'=>$address_id])->find();
        $shipping_name = Db::name('plugin')->where(['code'=>$shipping_code])->value('name');
        $data = array(
            'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
            'users_id'          =>$user_id, // 用户id
            'consignee'        =>$address['consignee'], // 收货人
            'province_id'         =>$address['province_id'],//'省份id',
            'city_id'             =>$address['city_id'],//'城市id',
            'area_id'         =>$address['area_id'],//'县',
            'twon_id'             =>$address['twon_id'],// '街道',
            'address'          =>$address['address'],//'详细地址',
            'mobile'           =>$address['mobile'],//'手机',
            'shipping_code'    =>$shipping_code,//'物流编号',
            'shipping_name'    =>$shipping_name, //'物流名称',
            'invoice_title'    =>$invoice_title, //'发票抬头',
            'goods_price'      =>$car_price['goodsFee'],//'商品价格',
            'shipping_price'   =>$car_price['postFee'],//'物流价格',
            'user_money'       =>$car_price['balance'],//'使用余额',
            'coupon_price'     =>$car_price['couponFee'],//'使用优惠券',
            'integral'         =>($car_price['pointsFee'] * Config::get('point_rate')), //'使用积分',
            'integral_money'   =>$car_price['pointsFee'],//'使用积分抵多少钱',
            'total_amount'     =>($car_price['goodsFee'] + $car_price['postFee']),// 订单总额
            'order_amount'     =>$car_price['payables'],//'应付款金额',
            'add_time'         =>time(), // 下单时间
            'order_prom_id'    =>$car_price['order_prom_id'],//'订单优惠活动id',
            'order_prom_amount'=>$car_price['order_prom_amount'],//'订单优惠活动优惠了多少钱',
        );
        $order_id = Db::name('order')->insertGetId($data);
        if(!$order_id)  return ['code'=>0,'msg'=>'添加订单失败','url'=>'','data'=>''];
        // 记录订单操作日志
        logOrder($order_id,'您提交了订单，请等待系统确认','提交订单',$user_id);

        $order = Db::name('order')->find($order_id);//订单详情
        // step.1 插入order_goods表
        $cartList =  Db::name('cart')->where(['users_id' => $user_id ,'selected'=> 1])->select();
        if($cartList){
            foreach($cartList as $key => $val) {
                $goods = Db::name('goods')->find($val['goods_id']);
                $data2['order_id']           = $order_id; // 订单id
                $data2['goods_id']           = $val['goods_id']; // 商品id
                $data2['goods_name']         = $val['goods_name']; // 商品名称
                $data2['goods_sn']           = $val['goods_sn']; // 商品货号
                $data2['goods_num']          = $val['goods_num']; // 购买数量
                $data2['market_price']       = $val['market_price']; // 市场价
                $data2['goods_price']        = $val['shop_price']; // 商品价
                $data2['spec_key']           = $val['spec_key']; // 商品规格
                $data2['spec_key_name']      = $val['spec_key_name']; // 商品规格名称
                $data2['discount_price'] = $val['discount_price']; // 会员折扣价
                $data2['cost_price']         = $goods['cost_price']; // 成本价
                $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分
                $data2['prom_type']          = $val['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $data2['goods_prom_id']      = $val['goods_prom_id']; // 活动id
                $order_goods_id              = Db::name("order_goods")->insertGetId($data2);
                // 扣除商品库存  扣除库存移到 付完款后扣除
                //M('Goods')->where("goods_id = ".$val['goods_id'])->setDec('store_count',$val['goods_num']); // 商品减少库存
            }
        }
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
        if($data['order_amount'] == 0) {
            return ['code'=>1,'msg'=>'支付完成后的操作还未未完成了','data'=>$order_id]; // 返回新增的订单id
            updatePayStatus($order['order_sn'], 1);
        }
        // step.2修改优惠券状态
        if($coupon_id > 0){
            $data3['users_id'] = $user_id;
            $data3['order_id'] = $order_id;
            $data3['use_time'] = time();
            Db::name('coupon_list')->where(['id' => $coupon_id])->update($data3);
            $cid =  Db::name('coupon_list')->where(['id' => $coupon_id])->value('coupon_id');
            Db::name('coupon')->where(['id'=>$cid])->setInc('use_num'); // 优惠券的使用数量加一
        }
        // step.3 扣除积分 扣除余额
        if($car_price['pointsFee']>0)
            Db::name('users')->where(['id'=>$user_id])->setDec('pay_points',($car_price['pointsFee'] * Config::get('point_rate'))); // 消费积分
        if($car_price['balance']>0)
            Db::name('Users')->where(['id'=>$user_id])->setDec('user_money',$car_price['balance']); // 抵扣余额
        // step.4 删除已提交订单商品
        Db::name('cart')->where(['users_id'=>$user_id,'selected'=>1])->delete();

        // step.5 记录log 日志
        $data4['users_id'] = $user_id;
        $data4['user_money'] = -$car_price['balance'];
        $data4['pay_points'] = -($car_price['pointsFee'] * Config::get('point_rate'));
        $data4['change_time'] = time();
        $data4['desc'] = '下单消费';
        $data4['order_sn'] = $order['order_sn'];
        $data4['order_id'] = $order_id;
        // 如果使用了积分或者余额才记录
        ($data4['user_money'] || $data4['pay_points']) && Db::name("account_log")->insert($data4);
        // 如果有微信公众号 则推送一条消息到微信
        $user = Db::name('users')->where(['id'=>$user_id])->find();
        if($user['oauth']== 'weixin')
        {
            $wx_user = Db::name('wx_account')->find();
            $jssdk = new Jssdk($wx_user['appid'],$wx_user['appsecret']);
            $wx_content = "您刚刚下了一笔订单:{$order['order_sn']} 尽快支付,过期失效!";
            $jssdk->pushMsg($user['openid'],$wx_content);
        }
        return ['code'=>1,'msg'=>'提交订单成功','data'=>$order_id]; // 返回新增的订单id
    }

}