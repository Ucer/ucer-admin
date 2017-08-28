<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/8
 * Time: 21:19
 */
namespace app\admin\model;
use think\Model;

class WxText extends Model{
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['keyword'] = trimall($data['keyword']);
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('WxTextValidate')->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改文本关键词[".$data['keyword']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'关键词修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate('WxTextValidate')->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加文本关键词[".$data['keyword']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'关键词添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 删除关键词
     *@param $ids
     */
    public function delText($ids)
    {
        $ids = implode(',',$ids);
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}