<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/20
 * Time: 21:36
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class GoodsType extends Model{
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
        $data['type_name'] = trimall($data['type_name']);
        $rules =  [
            ['type_name','unique:goods_type','商品类型已经存在'],
        ];
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改商品类型[".$data['type_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品类型修改成功'];
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
                    adminLog("添加商品类型[".$data['type_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品类型添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除商品类型
     *@param $ids
     */
    public function del($ids)
    {
        //检查商品规格
        $sun_spec = Db::name('goods_spec')->where(['goods_type_id'=>$ids])->find();
        if($sun_spec){
            return ['code'=>0,'data'=>'','msg'=>'类型下面有规格，不允许删除'];
        }
        //检查商品属性
        $sun_attr = Db::name('goods_attribute')->where(['goods_type_id'=>$ids])->find();
        if($sun_attr){
            return ['code'=>0,'data'=>'','msg'=>'类型下面有属性，不允许删除'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}