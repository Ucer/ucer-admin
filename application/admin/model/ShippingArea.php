<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/5
 * Time: 13:41
 */
namespace app\admin\model;
use think\Db;
use think\Exception;
use think\Loader;
use think\Model;

class ShippingArea extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['shipping_area_name'] = trimall($data['shipping_area_name']);
        $data['config'] = serialize($data['config']);
       if(!isset($data['area_list'])) $data['area_list'] = [];
        $validate = Loader::validate('ShippingAreaValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        $add2=[];
        if($id >0){
            Db::startTrans();
            try{
                $this->allowField(true)->save($data,['id'=>$id]);
                adminLog("修改物流配置[".$data['shipping_area_name']."]",req('url'));
                //  删除对应地区ID
                Db::name('area_region')->where(array('shipping_area_id'=>$id))->delete();

                if($data['default'] == 1){
                    Db::commit();
                    //默认全国其他地区
                    return ['code'=>1,'data'=>'','msg'=>'物流配置添加成功'];
                }
                // 重新插入对应配送区域id
                if($data['area_list']){
                    foreach($data['area_list'] as $k=>$v){
                        $add2[$k]['shipping_area_id'] = $id;
                        $add2[$k]['region_id'] = $v;
                    }
                    Db::name('area_region')->insertAll($add2);
                }
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'物流配置修改成功'];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            Db::startTrans();
            try{
                $this->allowField(true)->save($data);
                // 插入对应配送区域id
                if($data['area_list']){
                    foreach($data['area_list'] as $k=>$v){
                        $add2[$k]['shipping_area_id'] = $this->id;
                        $add2[$k]['region_id'] = $v;
                    }
                    Db::name('area_region')->insertAll($add2);
                }
                adminLog("添加物流配置[".$data['shipping_area_name']."]",req('url'));
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'物流配置添加成功'];
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
       Db::startTrans();
        try{
            $this->where(['id'=>$ids])->delete();
            Db::name('area_region')->where(['shipping_area_id'=>$ids])->delete();
            Db::commit();
            return ['code'=>1,'data'=>'','msg'=>'删除成功'];
        }catch (Exception $e){
            Db::rollback();
            return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
        }
    }
}