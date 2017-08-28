<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/25
 * Time: 9:56
 */
namespace app\admin\model;
use think\Db;
use think\Loader;
use think\Model;

class GoodsProm extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);
        $data['goods_ids'] = trimall($data['goods_ids']);
        $data['user_group_id'] = trimall($data['user_group_id']);
        $validate = Loader::validate('GoodsPromValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        if($id >0){
            Db::startTrans();
            try{
                $this->allowField(true)->save($data,['id'=>$id]);
                adminLog("修改促销活动[".$data['name']."]",req('url'));
                Db::name("goods")->where(["goods_prom_id"=>$id,"prom_type"=>3])->update(array('goods_prom_id'=>0,'prom_type'=>0));//初始化
                Db::name("goods")->where(['id'=>['in',$data['goods_ids']]])->update(array('goods_prom_id'=>$id,'prom_type'=>3));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'促销活动修改成功'];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            Db::startTrans();
            try{
                $this->allowField(true)->save($data);
                adminLog("添加促销活动[".$data['name']."]",req('url'));
                Db::name("goods")->where(['id'=>['in',$data['goods_ids']]])->update(array('goods_prom_id'=>$this->id,'prom_type'=>3));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'促销活动添加成功'];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
}