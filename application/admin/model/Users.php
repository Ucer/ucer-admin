<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/20
 * Time: 18:54
 */
namespace app\admin\model;
use think\Loader;
use think\Model;

class Users extends Model{
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
        $data['nickname'] = trimall($data['nickname']);
        if(empty(trimall($data['password']))){
            unset($data['password']);
        }else{
            $data['password'] = encrypt(trimall($data['password']));
        }
        $validate = Loader::validate('UsersValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                    $this->allowField(true)->save($data,['id'=>$id]);
                    adminLog("修改会员[".$data['nickname']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'会员修改成功'];
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                    $this->allowField(true)->save($data);
                    adminLog("添加会员[".$data['nickname']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'会员添加成功'];
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
}