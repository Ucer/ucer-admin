<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/2
 * Time: 20:39
 */
namespace app\admin\model;
use think\Model;

class AdminAllMenu extends  Model{
     /**
     * 获取所有菜单
     *@param
     */
    public function getAllMenu($where='1'){
        $all_menu = $this->where($where)->order(['sort'=>'asc'])->select();
        $menu = prePareMenu(objToArray($all_menu));
        return $menu;
    }
    /**
     * 获取排好序的节点列表
     *@param
     *@param
     */
    public function getSortList()
    {
        $all_menu = $this->where(1)->order(['sort'=>'asc'])->column('id,name,id,parent_id');
        $all_menu = subTree($all_menu);
        $sort_list=[];
        foreach($all_menu as $k=>$v){
                $name = getFirstCharter($v['name']).'  --  '.$v['name'];//前面加上拼音首字母
                $sort_list[] =$v['name']= $name;
                $all_menu[$k] = $v;
        }
//        array_multisort($sort_list,SORT_STRING,SORT_ASC,$all_menu);
        return $all_menu;
    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminAllMenuValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改节点[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'节点修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('AdminAllMenuValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加节点[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'节点添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除节点
     *@param $ids
     */
    public function del($ids)
    {
        //检查是否可以删除
        $all_menu = $this->where(1)->column('id,name,parent_id');
        $sun_ids = findSunTree($all_menu,$ids);
        if(count($sun_ids) >0){
            return ['code'=>0,'data'=>'','msg'=>'有下级，不允许删除'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}