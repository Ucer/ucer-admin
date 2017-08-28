<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/2
 * Time: 20:39
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class AdminRightMenu extends  Model{
    /**
     * 获取权限菜单
     *@param $where 查询条件
     *@param $filed 要查询的字段
     */
    public function getAllRight($where=1)
    {
       return $this->alias('m')->field('m.*,g.name as gname')->join('pc_admin_group g','m.group_id=g.id')->where($where)->order(['created_at'=>'desc'])->select();
    }
    /**
     * 根据act_list获取权限菜单
     *@param $act_list 权限菜单
     *@param $all_menu 所有菜单
     */
   public function getRightList($act_list=0,$all_menu){
        if(strtolower($act_list) !='all'){
            //权限菜单列表
            $right_menu_list = $this->getRights($act_list);
            if(empty($right_menu_list)){
                $right_menu_list = [];
            }
            foreach($all_menu as $k=>$v){
              if(isset($v['sun'])){
                  foreach($v['sun'] as $kk=>$vv){
                      if(!in_array(strtolower($vv['control']).'@'.strtolower($vv['action']),$right_menu_list)){
                          unset($all_menu[$k]['sun'][$kk]);
                      }
                  }
              }
            }
        }
       return $all_menu;
   }
    /**
     * 根据act_list获取权限数组
     *@$act_list
     */
    public function getRights($act_list=0){
        $right_menu = $this->where(['id'=>['in',$act_list],'delete_at'=>['eq',0]])->column('right');
        if(empty($right_menu)) return '';//如果一条权限都没有，返回空
        $right_menu_list='';
        foreach($right_menu as $k=>$v){
            $right_menu_list .= strtolower($v).',';
        }
        return explode(',',$right_menu_list);
    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);
        $data['right'] = implode(',',$data['right']);
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminRightMenuValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改权限[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'权限修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminRightMenuValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加权限[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'权限添加成功'];
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
    public function del($ids)
    {
        //检查是否可以删除
        $all_role = Db::name('admin_role')->Column('right_menu_ids');
        foreach($all_role as $v) {
            $role_right = explode(',',$v);
            foreach ($ids as $vv) {
                if(in_array($vv,$role_right)){
                    $can_del = 1;
                }
            }
        }
        if(isset($can_del)){
            return ['code'=>0,'data'=>'','msg'=>'有角色正在使用权限，不允许删除'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }

}