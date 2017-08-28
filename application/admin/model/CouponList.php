<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/19
 * Time: 16:57
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class CouponList extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order=['sort'=>'asc'],$search=""){
        $group_list = $this->where($where)->field($field)->order($order)->paginate($per,false,[
           'query'=>[$search]
        ]);
        if($group_list){
            foreach($group_list as $k=>$v){
                if($v['order_id']){//已经使用
                    $v['users_name']=Db::name('users')->where(['id'=>$v['users_id']])->value('nickname');
                    $v['order_sn']='现在是写死的';//TODO
                }
            }
        }
        return [$group_list,$group_list->render()];
    }
    /**
     * 删除
     *@param $ids
     */
    public function del($ids)
    {
        //检查是否可以删除
        $rs = $this->where(['id'=>$ids])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}