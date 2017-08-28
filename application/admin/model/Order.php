<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/13
 * Time: 15:35
 */
namespace app\admin\model;
use think\Db;
use think\image\Exception;
use think\Model;
use think\Session;

class Order extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order="id desc"){
        $count = $this->where($where)->count();
        $group_list = $this->where($where)->field($field)->order($order)->paginate($per,$count,[
            'page'=>input('param.page'),
            'list_rows'=>$per
        ]);
//        dd($this->getLastSql());
        return [$group_list,$group_list->render()];
    }
    /**
     * 获取订单的详细信息
     *@param $id 订单id
     *@param
     * return object
     */
    public function getOrderInfo($id)
    {
        $order = $this->get($id);
        $order_address_2 = getRegionList();
        $order['address2'] = $order_address_2[$order['province_id']]['name'].'、'.$order_address_2[$order['city_id']]['name'].'、'.$order_address_2[$order['twon_id']]['name'].$order['address'];
        return $order;
    }
    /**
     * 获取订单下面的商品
     *@param $id 订单id
     *@param
     */
    public function getOrderGoods($id)
    {
        $list = Db::name('order_goods')->alias('og')->join('goods g',"og.goods_id =g.id")->field('og.*,(og.goods_num * og.discount_price) AS goods_total')->where(['og.order_id'=>$id])->select();//主要是过滤被删除的商品
//        $list = Db::name('order_goods')->alias('og')->join('goods g',"og.goods_id =g.id")->field('og.*,(og.goods_num * og.discount_price) AS goods_total')->where(['og.order_id'=>$id])->select();
        return $list;
    }
    /**
     * 获取订单的可操作菜单
     * 操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
     *@param $order 订单详细信息
     *@param
     */
    public function getOrderButton($order)
    {
        $os = $order['order_status'];//订单状态
        $ss = $order['shipping_status'];//发货状态
        $ps = $order['pay_status'];//支付状态

        if($order['pay_code'] == 'cod') {//货到付
            if($os == 0 && $ss == 0){ //待确认0-未发货0->去确认(os=1)
                $btn['confirm'] = '确认';
            }elseif($os == 1 && $ss == 0 ){//已确认1-未发货0->去发货(ss=1)|取消确认(os=0)
                $btn['delivery'] = '去发货';
                $btn['cancel'] = '取消确认';
            }elseif($ss == 1 && $os == 1 && $ps == 0){//已发货(1)-已确认(1)-未支付(0)->付款(ps=1)
                $btn['pay'] = '付款';
            }elseif($ps == 1 && $ss == 1 && $os == 1){//已发货(1)-已确认(1)-已支付(1)->设为未付款(ps=0)
                $btn['pay_cancel'] = '设为未付款';
            }
        }else{
            if($ps == 0 && $os == 0){//未支付(0)-未确认(0)->设为已付款(ps=1)
                $btn['pay'] = '付款';
            }elseif($os == 0 && $ps == 1){//未确认(0)-已支付(1)->设为未付款(ps=0)|确认(os=1)
                $btn['pay_cancel'] = '设为未付款';
                $btn['confirm'] = '确认';
            }elseif($os == 1 && $ps == 1 && $ss==0){//未发货(0)-已确认(1)-已支付(1)->取消确认(os=0)|去发货(ss=1)
                $btn['cancel'] = '取消确认';
                $btn['delivery'] = '去发货';
            }
        }

        if($ss == 1 && $os == 1 && $ps == 1){
            $btn['delivery_confirm'] = '确认收货';
            $btn['refund'] = '申请退货';
        }elseif($os == 2 || $os == 4){//已收货|已完成
            $btn['refund'] = '申请退货';
        }elseif($os == 3 || $os == 5){//取消|作废
            $btn['remove'] = '移除';
        }
        if($os != 5){
            $btn['invalid'] = '无效';
        }
        return $btn;
    }
    /**
     * 管理员操作订单
     *@param $order_id 订单id
     *@param $act 操作btn
     */
    public function orderProcessHandle($order_id,$act){
        $updata = array();
        switch ($act){
            case 'pay': //付款
                $order_sn = $this->where(["id" => $order_id])->value("order_sn");
                updatePayStatus($order_sn); // 调用确认收货按钮
                return true;
            case 'pay_cancel': //取消付款
                $updata['pay_status'] = 0;
                $this->ordePayCancel($order_id);
                return true;
            case 'confirm': //确认订单
                $updata['order_status'] = 1;
                break;
            case 'cancel': //取消确认
                $updata['order_status'] = 0;
                break;
            case 'invalid': //作废订单
                $updata['order_status'] = 5;
                break;
            case 'remove': //移除订单
                $this->delOrder($order_id);
                return true;
            case 'delivery_confirm'://确认收货
                confirmOrder($order_id); // 调用确认收货按钮//TODO
                return true;
            default:
                return true;
        }
        return $this->where(["id" => $order_id])->update($updata);//改变订单状态
    }
    /**
     * 管理员取消付款
     *@param $order_id 订单id
     *@param
     */
    private function ordePayCancel($order_id){
        Db::startTrans();
        try {
            //如果这笔订单已经取消付款过了
            $map['id'] = $order_id;
            $count = $this->where(["id" => $order_id, 'pay_status' => 1])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
            if ($count == 0) return false;

            // 增加对应商品的库存
            $orderGoodsArr = Db::name('order_goods')->where(["order_id" => $order_id])->select();

            foreach ($orderGoodsArr as $key => $val) {
                if (!empty($val['spec_key'])) {// 有选择规格的商品/先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
                    Db::name('goods_spec_price')->where(["goods_id" => $val['goods_id'], 'key_ids' => $val['spec_key']])->setInc('store_num', $val['goods_num']);
                    refreshStock($val['goods_id']);
                } else {//直接修改商品表的库存
                    Db::name('goods')->where(["id" => $val['goods_id']])->setInc('store_count', $val['goods_num']); // 增加商品总数量-回退
                }
                Db::name('goods')->where(["id" => $val['goods_id']])->setDec('sales_sum', $val['goods_num']); // 减少商品销量

                //更新活动商品购买量
                if ($val['prom_type'] == 1 || $val['prom_type'] == 2) {
                    $prom = getGoodsPromotion($val['goods_id']);
                    if ($prom['is_end'] == 0) {//正常促销中-减少促销活动的销量和下单量
                        $tb = $val['prom_type'] == 1 ? 'flash_sale' : 'group_buy';
                        Db::name($tb)->where("id=" . $val['prom_id'])->setDec('buy_num', $val['goods_num']);
                        Db::name($tb)->where("id=" . $val['prom_id'])->setDec('order_num');
                    }
                }
            }

            // 找出对应的订单
            $order = $this->where($map)->find();
            // 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
            $this->where($map)->update(array('pay_status' => 0));
            updateUserLevel($order['users_id']);
            // 记录订单操作日志
            logOrder($order['id'], '订单取消付款', '付款取消', $order['users_id']);
            Db::commit();
        }catch (\think\Exception $e){
            dd($e->getMessage());
            Db::rollback();
        }
    }

    /**
     * 管理员移除订单
     *@param $order_id 订单id
     *@param
     */
    public function delOrder($order_id){
        Db::startTrans();
        try{
            $a = $this->where(["id" => $order_id])->delete();
            $b = Db('order_goods')->where(["order_id" => $order_id])->delete();
            Db::commit();
            return $a && $b;
        }catch (Exception $e){
            dd($e->getMessage());
            Db::rollback();
        }
    }

    /*
 * 订单操作记录
 */
    public function orderActionLog($order_id,$action,$note=''){
        $order = $this->where(["id" => $order_id])->find();
        $data['order_id'] = $order_id;
        $data['action_user_id'] = 0;//0为超级管理员操作
        $data['action_note'] = $note;//备注
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        switch($action){
            case 'confirm';
                $msg = '确认';
                break;
            case 'cancel';
                $msg = '取消确认';
                break;
            case 'delivery_confirm';
                $msg = '确认收货';
                break;
            case 'pay';
                $msg = '付款';
                break;
            case 'remove';
                $msg = '移除';
                break;
            case 'invalid';
                $msg = '无效';
                break;
            //以下三个是单独分开操作
            case 'pay_cancel';
                $msg = '设为未付款';
                break;
            case 'delivery';
                $msg = '去发货';
                break;
            case 'refund';
                $msg = '申请退货';
                break;
            default:
                $msg =  '未知操作';
                break;
        }

        $data['status_desc'] = $msg;
        return Db::name('order_action')->insert($data);//订单操作记录
    }
    /**
     * 发货处理
     *@param $pagesize 每一页的总数
     *@param
     */
    public function deliveryHandle($data)
    {
        $oid = $data['order_id'];
        $order = $this->getOrderInfo($oid);
        $orderGoods = $this->getOrderGoods($oid);
        $selectgoods = $data['goods'];//要发货的商品
        $data1['order_sn'] = $order['order_sn'];
//        $data['delivery_sn'] = $this->get_delivery_sn();
        $data1['zipcode'] = '';
        $data1['users_id'] = $order['users_id'];
        $data1['admin_id'] = Session::get('admin_user_id');
        $data1['consignee'] = $order['consignee'];
        $data1['mobile'] = $order['mobile'];
        $data1['province_id'] = $order['province_id'];
        $data1['city_id'] = $order['city_id'];
        $data1['twon_id'] = $order['twon_id'];
        $data1['address'] = $order['address'];
        $data1['shipping_code'] = $order['shipping_code'];
        $data1['shipping_name'] = $order['shipping_name'];
        $data1['shipping_price'] = $order['shipping_price'];
        $data1['create_time'] = time();
        $data1['invoice_number'] = $data['invoice_number'];
        $data1['note'] = $data['note'];
        $data1['order_id'] = $data['order_id'];

        $addid = Db::name('ship_order')->insertGetId($data1);
        $is_delivery = 0;
        $r ='';
        foreach ($orderGoods as $k=>$v){
            if($v['is_send'] == 1){
                $is_delivery++;
            }
            if($v['is_send'] == 0 && in_array($v['goods_id'],$selectgoods)){ //为未发化并且订单下面的商品在传过来的商品id数组中
                $res['is_send'] = 1;
                $res['ship_order_id'] = $addid;
                $r = Db::name('order_goods')->where(["id"=>$v['id']])->update($res);//改变订单商品发货状态
                $is_delivery++;
            }
        }

        $updata['shipping_time'] = time();
        if($is_delivery == count($orderGoods)){
            $updata['shipping_status'] = 1;
        }else{
            $updata['shipping_status'] = 2;//不完全发货
        }
        $this->where(['id'=>$oid])->update($updata);//改变订单状态
        $s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志

        return $s && $r;
    }
}