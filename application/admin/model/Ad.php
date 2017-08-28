<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/22
 * Time: 9:19
 */
namespace app\admin\model;
use think\Model;

class Ad extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order=['sort'=>'asc']){
        $group_list = $this->where($where)->field($field)->order($order)->paginate($per,false,[
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
        $data['ad_name'] = trimall($data['ad_name']);
        $rules =  [
            ['ad_name','unique:ad','广告已经存在'],
        ];
        if($id >0){
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改广告[".$data['ad_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'广告修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加广告[".$data['ad_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'广告添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除
     *@param $ids
     */
    public function del($ids)
    {
        //检查是否可以删除
        $info = $this->get($ids);
        if($info['is_show'] ==0){
            if( (strtotime($info['end_time']) >time())){
                return ['code'=>0,'data'=>'','msg'=>'广告还没过期，不允许删除'];
            }
        }
        $rs = $this->where(['id'=>$ids])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}