<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/2
 * Time: 11:23
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class AdminRole extends  Model{
    /**
     * 按条件查找
     *@param $where 查询条件
     *@param $field 查询字段
     *@param $isinfo 1表示查询一条数据
     */
    public function getColumn($where=1,$field="*",$isinfo=0){
        $result = $this->where($where)->column($field);
        if(count($result)>0){
            if(1 == $isinfo)
                return reset($result);
        }
        return $result;
    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['role_name'] = trimall($data['role_name']);
        $data['right_menu_ids'] = isset($data['right_menu_ids'])?implode(',',$data['right_menu_ids']):'';
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminRoleValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改权限[".$data['role_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'角色修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminRoleValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加角色[".$data['role_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'角色添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除角色
     *@param $ids
     */
    public function delRole($ids)
    {
        //检查是否可以删除
        $ids = implode(',',$ids);
        //检查是否可以删除
        $sun_ids = Db::name('admin_user')->where(['role_id'=>['in',$ids]])->select();
        if($sun_ids){
            return ['code'=>0,'data'=>'','msg'=>'有用户正在使用该角色，不允许删除'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}