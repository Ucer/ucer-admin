<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/16
 * Time: 20:23
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class ArticleCategory extends Model{
    /**
     * 获取排好序的节点列表
     *@param
     *@param
     */
    public function getSortList()
    {
        $all_menu = $this->where(['is_show'=>0])->order(['sort'=>'asc'])->column('id,cat_name,id,parent_id');
        $all_menu = subTree($all_menu);
        foreach($all_menu as $k=>$v){
            $all_menu[$k] = $v;
        }
        return $all_menu;
    }

    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['cat_name'] = trimall($data['cat_name']);
        $data['alias_name'] = trimall($data['alias_name']);
        $rules =  [
            ['cat_name','unique:article_category','分类名称已经存在'],
            ['alias_name','unique:article_category','分类别名称已经存在'],
        ];
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改文章分类[".$data['cat_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'文章分类修改成功'];
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
                    adminLog("添加文章分类[".$data['cat_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'文章分类添加成功'];
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
        $all_menu = $this->where(1)->column('id,cat_name,parent_id');
        $sun_ids = findSunTree($all_menu,$ids);
        if(count($sun_ids) >0){
            return ['code'=>0,'data'=>'','msg'=>'有下级，不允许删除'];
        }
        //检查下面有没有文章
        $sun_art = Db::name('article')->where(['cat_id'=>$ids])->find();
        if($sun_art){
            return ['code'=>0,'data'=>'','msg'=>'分类下面有文章，不允许删除'];
        }
        $rs = $this->where(['id'=>['eq',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}