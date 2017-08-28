<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/20
 * Time: 20:13
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class UserLevel extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);
        $rules =  [
            ['name','unique:user_level','会员头衔已经存在'],
        ];
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改会员头衔[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'会员头衔修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加会员头衔[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'会员头衔添加成功'];
                }
            }catch(\PDOException $e){
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
       $suns = Db::name('users')->where(['level_id'=>$ids])->count();
        if($suns >0){
            return ['code'=>0,'data'=>'','msg'=>'下面有会员，不允许删除'];
        }
        $rs = $this->where(['id'=>$ids])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}