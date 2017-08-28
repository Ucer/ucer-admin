<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/13
 * Time: 15:34
 */
namespace app\admin\controller;
use app\admin\model\Comment;
use app\admin\model\OrderGoods;
use think\Config;
use think\Db;
use think\Url;

class Order extends Base{
    protected $order;
    protected $orderGoods;
    protected $comment;
    public  $order_status;
    public  $pay_status;
    public  $shipping_status;

    public function _initialize()
    {
        parent::_initialize();
        $this->order = new \app\admin\model\Order();
        $this->orderGoods = new OrderGoods();
        $this->comment = new Comment();
        $this->order_status = Config::get('ORDER_STATUSS');
        $this->pay_status = Config::get('PAY_STATUS');
        $this->shipping_status = Config::get('SHIPPING_STATUS');
        // 订单 支付 发货状态
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }
    /*会员列表*/
    public function orderList()
    {
        return $this->fetch('order/order_list');
    }
    /*订单列表*/
    public function ajaxOrderList()
    {
        $keywords = trimall($this->request->param('keywords'));//订单号
        $keywords1 = trimall($this->request->param('keywords1'));//收货人
        $pay_status = $this->request->param('pay_status');
        $pay_code = $this->request->param('pay_code');
        $shipping_status = $this->request->param('shipping_status');
        $order_status = $this->request->param('order_status');
        $stime = $this->request->param("start_time");
        $etime = $this->request->param("end_time");

        $order_by = $this->request->param("order_by")?:'add_time';
        $sort = $this->request->param("sort");
        if($sort=='asc'){
            $sort = 'desc';
        }else{
            $sort = 'asc';
        }
        $ssort = $order_by.' '.$sort;

        $where=1;
        if($keywords){//编号
            $where .= " AND order_sn like '%$keywords%'";
        }
        if($keywords1){//收货人
            $where .= " AND consignee like '%$keywords1%'";
        }
        if($stime){//下单时间
            $where .= " AND add_time >=".strtotime($stime);
        }
        if($etime){//下单时间
            $where .= " and add_time <=".strtotime($etime);
        }
        if($pay_status>0 || $pay_status ===0){//支付状态
            $where .= " AND (`pay_status` = $pay_status)";
        }
        if($pay_code){//支付方式
            $where .= " AND (`pay_code` = '$pay_code')";
        }
        if($shipping_status){//物流状态
            $where .= " AND (`shipping_status` = $shipping_status)";
        }
        if($order_status === 0 || $order_status >0){//物流状态
            $where .= " AND (`order_status` = $order_status)";
        }
        list($list,$page) = $this->order->getPageList($where,$this->page,'*',$ssort);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('order/ajax_order_list', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'order_by'=>$order_by,
            'sort'=>$sort,
        ]);
    }
    /*订单详情页面*/
    public function orderDetail()
    {
        $id = $this->request->param("id");
        $order_info = $this->order->getOrderInfo($id);
        $order_goods = $this->order->getOrderGoods($id);
        $order_btn = $this->order->getOrderButton($order_info);

        $action_log = Db::name('order_action')->where(['order_id'=>$id])->order(['log_time'=>'desc'])->select();
        if($action_log){
            foreach($action_log as $k=>$v){
                $action_log[$k]['action_user_name'] = '超级管理员';
                if($v['action_user_id'] >0){
                    $action_log[$k]['action_user_name'] = Db::name('users')->where(['id'=>$v['action_user_id']])->value('nickname');
                }
            }
        }

        $this->assign('order',$order_info);
        $this->assign('action_log',$action_log);
        $this->assign('order_goods',$order_goods);

        $split = count($order_goods) >1 ? 1 : 0;
        foreach ($order_goods as $val){
            if($val['goods_num']>1){
                $split = 1;
            }
        }
        $this->assign('split',$split);
        $this->assign('button',$order_btn);
        return $this->fetch('order/order_detail');
    }
    /*管理员操作订单*/
    public function orderAction()
    {
        $dt = $this->request->param("");
        $order_id = $dt['order_id'];$note =  $dt['note'];$action =  $dt['type'];
        if(empty($order_id)) $this->error('参数错误');
        $res='';
        if($action && $order_id){
            $a = $this->order->orderProcessHandle($order_id,$action);//各种状态操作 TODO
            if($action =='remove'){//如果是移除订单
                $url = Url::build('orderlist');
            }else{
                $res = $this->order->orderActionLog($order_id,$action,$note);//记录入order_action表中
                $url = Url::build('Order/orderDetail',['id'=>$order_id]);
            }
            $a&&$res ? $this->success('操作成功',$url):$this->error('出错了，请稍后再试');
        }else{
            $this->error('参数错误');
        }
    }
    /*订单取消付款*/
    public function payCancel()
    {
        $dt = $this->request->param("");
        $order_id = $dt['order_id'];
        $order = $this->order->find($order_id);
        if(empty($order)) $this->error('出错了');

        if($this->request->isPost()){
            $data = $this->request->param('');
            $note = array('退款到用户余额','已通过其他方式退款','点错了');

            if($data['refundType'] < 1 && $data['amount']>=0){
                accountLog($order['users_id'], $data['amount'], 0,  '退款到用户余额');//如果是返回用户余额
            }

            $a = $this->order->orderProcessHandle($order_id,'pay_cancel');//各种状态操作
            $d = $this->order->orderActionLog($order_id,'pay_cancel',$note[$data['refundType']]);//记录入order_action表中
            $d?$this->success('操作成功',Url::build('orderDetail',['id'=>$order_id])):$this->error('操作失败');
        }

//        if($d){
//            exit("<script>window.parent.pay_callback(1);</script>");
//        }else{
//            exit("<script>window.parent.pay_callback(0);</script>");
//        }
        $this->assign('order',$order);
        return $this->fetch('order/pay_cancel');

    }
    /*去发货*/
    public function deliveryInfo()
    {
        $order_id = $this->request->param("order_id");
        if(!$order_id) $this->error('出错了');

        $order = $this->order->getOrderInfo($order_id);
        $orderGoods = $this->order->getOrderGoods($order_id);

        $delivery_record = Db::name('ship_order')->alias('s')->join('pc_admin_user a','s.admin_id=a.id','left')->field('s.*,a.user_name as admin_user')->where(['s.order_id'=>$order['id']])->select();

        if($delivery_record){
            $order['invoice_number'] = $delivery_record['0']['invoice_number'];
        }else{
            $order['invoice_number'] = '';
        }
        $this->assign('order_goods',$orderGoods);
        $this->assign('delivery_record',$delivery_record);//发货记录
        $this->assign('order',$order);

        return $this->fetch('order/delivey_info');
    }
    /*生成发货单*/
    public function deliveryHandle()
    {
        if($this->request->isGet()){
            $this->error('无权访问');
        }
        $dt = $this->request->param("");
        if($dt['order_id'] <1) $this->error('参数错误');
        $res = $this->order->deliveryHandle($dt);
        $res?$this->success('操作成功',Url::build('deliveryInfo',['order_id'=>$dt['id']])):$this->error('操作失败');

    }
    /*订单价格修改*/
    public function editPrice()
    {
        $order_id = $this->request->param("order_id");
        if(!$order_id) $this->error('出错了');

        $order = $this->order->getOrderInfo($order_id);
        if($this->request->isPost()){
            $data = $this->request->param('');
            $admin_id = $this->admin_id;
            if($admin_id <1){
                $this->error('非法操作');
            }
            $update['discount'] =$data['discount'];
            $update['shipping_price'] = $data['shipping_price'];
            $update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $r = $this->order->where(['id'=>$order_id])->update($update);
            $r?$this->success('操作成功',Url::build('Order/orderDetail',['id'=>$order_id])):$this->error('操作失败');
        }
        $this->assign('order',$order);
        return $this->fetch('order/edit_price');
    }
   
}