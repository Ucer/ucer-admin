<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/30
 * Time: 11:16
 */
namespace app\admin\model;
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
        $data['title'] = trimall($data['title']);
        $validate = Loader::validate('GroupBuyValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        if($id >0){
            Db::startTrans();

            try{
                $this->allowField(true)->save($data,['id'=>$id]);
                adminLog("修改团购活动[".$data['title']."]",req('url'));
                Db::name("goods")->where(["goods_prom_id"=>$id,"prom_type"=>2])->update(array('goods_prom_id'=>0,'prom_type'=>0));//初始化
                Db::name("goods")->where(['id'=>['eq',$data['goods_id']]])->update(array('goods_prom_id'=>$id,'prom_type'=>2));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'团购活动修改成功'];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            Db::startTrans();
            try{
                $this->allowField(true)->save($data);
                adminLog("添加团购活动[".$data['title']."]",req('url'));
                Db::name("goods")->where(['id'=>['eq',$data['goods_id']]])->update(array('goods_prom_id'=>$this->id,'prom_type'=>2));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'团购活动添加成功'];
            }catch(\PDOException $e){
                Db::rollback();
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
        if( ($info['end_time'] >time())){
            return ['code'=>0,'data'=>'','msg'=>'团购活动还没过期，不允许删除'];
        }
        $rs = $this->where(['id'=>$ids])->delete();
        Db::name("goods")->where(["goods_prom_id"=>$ids,"prom_type"=>2])->update(array('goods_prom_id'=>0,'prom_type'=>0));//初始化
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}