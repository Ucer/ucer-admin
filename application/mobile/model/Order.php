<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/23
 * Time: 18:20
 */
namespace app\mobile\model;
use app\home\logic\UserLogic;
use think\Model;

class Order extends Model{
    public function getOrderList($where,$mpage,$query,$per)
    {
        $order_list = $this->order(['id'=>'desc'])->where($where)->paginate($mpage,false,[
            'query'=>$query,
            'page'=>$per
        ]);
        $order_list = $order_list->toArray();
        //获取订单商品
        if($order_list['data']){
            foreach($order_list['data'] as $k=>$v){
                $order_list['data'][$k] = setBtnStatus($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
                $logic = new UserLogic();
                $data = $logic->getOrderGoods($v['id']);
                $order_list['data'][$k]['goods_list'] = $data['data']?:[];
            }
        }
        return $order_list;
    }
}