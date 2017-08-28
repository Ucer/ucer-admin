<?php
namespace app\admin\model;

use think\Model;

class AdminUser extends Model
{
    /**
     * 获取权限菜单
     *@param $where 查询条件
     *@param $filed 要查询的字段
     */
    public function getAllUser($where=1)
    {
        return $this->alias('m')->field('m.*,r.role_name')->join('pc_admin_role r','m.role_id=r.id')->where($where)->order(['id'=>'asc'])->select();
    }
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

    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['user_name'] = trimall($data['user_name']);
        if(empty(trimall($data['password']))){
            unset($data['password']);
        }else{
            $data['password'] = encrypt(trimall($data['password']));
        }
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminUserValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改管理员[".$data['user_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'管理员修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminUserValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加管理员[".$data['user_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'管理员添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除权限菜单
     *@param $ids
     */
    public function delUser($ids)
    {
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}