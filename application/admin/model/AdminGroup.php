<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/1/18
 * Time: 11:22
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class AdminGroup extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order_type='asc',$order='created_at'){
        $group_list = $this->where($where)->field($field)->order([$order=>$order_type])->paginate($per,false,[
            'page'=>input('param.page'),
            'list_rows'=>$per
        ]);
        return [$group_list,$group_list->render()];
    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminGroupValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改分组[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'分组修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminGroupValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加分组[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'分组添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除分组
     *@param $ids
     */
    public function del($ids)
    {
        $ids = implode(',',$ids);
        //检查是否可以删除
        $sun_ids = Db::name('admin_right_menu')->where(['group_id'=>['in',$ids]])->select();
        if($sun_ids){
            return ['code'=>0,'data'=>'','msg'=>'请先删除分组下面的权限菜单'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
    /**
     * 所有分组
     */
    public function getAllList()
    {
        return $this->field('id,name')->select();
    }
}