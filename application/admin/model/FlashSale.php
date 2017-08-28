<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/23
 * Time: 10:16
 */
namespace app\admin\model;
use think\Db;
use think\Loader;
use think\Model;

class FlashSale extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['title'] = trimall($data['title']);
        $validate = Loader::validate('FlashSaleValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        if($id >0){
            Db::startTrans();

            try{
                $this->allowField(true)->save($data,['id'=>$id]);
                adminLog("修改限时抢购[".$data['title']."]",req('url'));
                Db::name("goods")->where(["goods_prom_id"=>$id,"prom_type"=>1])->update(array('goods_prom_id'=>0,'prom_type'=>0));//初始化
                Db::name("goods")->where(['id'=>['eq',$data['goods_id']]])->update(array('goods_prom_id'=>$id,'prom_type'=>1));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'限时抢购修改成功'];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            Db::startTrans();
            try{
                $this->allowField(true)->save($data);
                adminLog("添加限时抢购[".$data['title']."]",req('url'));
                Db::name("goods")->where(['id'=>['eq',$data['goods_id']]])->update(array('goods_prom_id'=>$this->id,'prom_type'=>1));//更新goods表
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'限时抢购添加成功'];
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
        if( (strtotime($info['end_time']) >time())){
            return ['code'=>0,'data'=>'','msg'=>'活动还没过期，不允许删除'];
        }
        $rs = $this->where(['id'=>$ids])->delete();
        Db::name("goods")->where(["goods_prom_id"=>$ids,"prom_type"=>1])->update(array('goods_prom_id'=>0,'prom_type'=>0));//初始化
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}