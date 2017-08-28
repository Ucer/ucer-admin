<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/4
 * Time: 21:01
 */
namespace app\admin\model;
use think\Model;

class WxAccount extends Model{
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
                $result = $this->allowField(true)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改公众号[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'公众号修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加公众号[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'公众号添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
}