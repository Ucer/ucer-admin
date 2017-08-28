<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/4
 * Time: 13:00
 */
namespace app\mobile\model;
use think\Db;
use think\Exception;
use think\Loader;
use think\Model;
use think\Url;

class UserAddress extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     *@param $is_default  修改之前是否为默认地址
     */
    public function handle($data,$user_id,$id=0,$is_default=0)
    {
        $data['consignee'] = trimall($data['consignee']);
        $data['is_default'] = 0;
        $validate = Loader::validate('UserAddressValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        $address_num = $this->where(['users_id'=>$user_id,'is_default'=>1])->count();
        $address_nums = $this->where(['users_id'=>$user_id])->count();
        if($address_nums >=5){
            return ['code'=>0,'data'=>'','msg'=>'最多只能添加5个收货地址','data'=>''];
        }
        if($id >0){
            if(isset($data['is_default'])) unset($data['is_default']);//此处无法修改默认值
            Db::startTrans();
            try{
                $this->allowField(true)->save($data,['id'=>$id]);
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'收货地址修改成功','data'=>''];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage(),'data'=>''];
            }
        }else{
            $data['users_id'] = $user_id;
            //如果当前只有一个收货地址则设置为默认地址
            if($address_num == 0){
                $data['is_default'] = 1;
            }
            Db::startTrans();
            try{
                $this->allowField(true)->save($data);
                $a_id = $this->id;
                if($data['is_default'] == 1){
                    $this->where(['users_id'=>$user_id,'id'=>['neq',$a_id]])->update(['is_default'=>0]);
                }
                Db::commit();
                return ['code'=>1,'data'=>'','msg'=>'收货地址添加成功','data'=>$a_id];
            }catch(\PDOException $e){
                Db::rollback();
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage(),'data'=>''];
            }
        }
    }
    /**
     * 删除
     *@param $ids
     */
    public function del($ids,$user_id)
    {
        //检查是否可以删除
        $info = $this->get($ids);
        Db::startTrans();
        try{
            if($info['is_default'] ==1){
                $first = $this->where(['users_id'=>$user_id])->find();
                if($first){
                    $this->where(['id'=>$first['id']])->update(['is_default'=>1]);
                }
            }
            $rs = $this->where(['id'=>$ids])->delete();
            Db::commit();
            return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功','url'=>Url::build('Users/addressList')]:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
        }catch (Exception $e){
            Db::rollback();
            return ['code'=>0,'data'=>'','msg'=>$e->getMessage(),'data'=>''];
        }

    }
}