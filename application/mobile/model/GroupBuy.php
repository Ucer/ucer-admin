<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/30
 * Time: 11:16
 */
namespace app\mobile\model;
use think\Db;
use think\Loader;
use think\Model;

class GroupBuy extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order=['sort'=>'asc']){
        $group_list = $this->where($where)->field($field)->order($order)->paginate($per,true);
        if($group_list){
            foreach($group_list as $k=>$v){
                $goods_info = Db::name('goods')->find($v['goods_id']);
                $group_list[$k]['goods_img'] = $goods_info['original_img'];
                $group_list[$k]['market_price'] = $goods_info['market_price'];
                $group_list[$k]['discount'] = round($v['price']/$goods_info['market_price'],2)*10;
            }
        }
        return $group_list;
    }
}