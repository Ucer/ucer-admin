<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/16
 * Time: 20:22
 */
namespace app\admin\controller;
use app\admin\model\ArticleCategory;
use think\Controller;

class Article extends Base{
    protected  $articleCategory;
    protected  $article;
    protected $uploadify;
    protected function _initialize()
    {
       parent::_initialize();
        $this->articleCategory = new ArticleCategory();
        $this->article = new \app\admin\model\Article();
        $this->uploadify = new Uploadify();
    }
    /*文章分类*/
    public function articleCategory()
    {
        $all_menu = $this->articleCategory->order(['sort'=>'asc'])->select();
        return view('article/article_category',[
            'lists'=>subTree(objToArray($all_menu))
        ]);
    }
    /*添加|修改文章分类*/
    public function categoryHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            $dt['content'] = imgHandle($_POST['content'],'article');

            if($id >0){//修改
                if($dt['parent_id'] >0){
                    //找出家谱树，上级不能是自己或自己的后代
                    $all_cat = $this->articleCategory->order(['sort'=>'asc'])->column('id,cat_name,parent_id');
                    $family_tree_ids = findFamilyTree($all_cat,$dt['parent_id']);
                    if(in_array($id,$family_tree_ids)){//如果id在选中父id的家谱树中
                        $this->error('上级节点不能是自己或自己的后代');
                    }
                }
                $rs = $this->articleCategory->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->articleCategory->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Article/articleCategory')):$this->error($rs['msg']);
        }
        $info = $this->articleCategory->get($id);
        return view('article/_category',[
            'info'=>$info,
            'id'=>$id,
            'cat_list' =>$this->articleCategory->getSortList(),//所属文章分类
        ]);
    }
    /*删除节点*/
    public function delCategory()
    {
        return $this->articleCategory->del(input('param.ids'));
    }
    /*文章列表*/
    public function articleList()
    {
        return view('article/article_list',['cat_list'=>$this->articleCategory->getSortList()]);

    }
    /*文章列表*/
    public function ajaxArticleList()
    {
        $keywords = input('param.keywords');
        $cat_id = input('param.cat_id');
        $is_home = input('param.is_home');
        $where=1;
        if($is_home){
            $where .= " and (`is_home` = 1)";
        }
        if($cat_id){
            $where .= " and (`cat_id` = $cat_id)";
        }
        if($keywords){
            $where .= " and ( (title like '%$keywords%') OR (keywords like '%$keywords%') )";
        }
        list($list,$page) = $this->article->getPageList($where,$this->page,'*',['sort'=>'asc','created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('article/ajax_article', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'cat_list'=>$this->articleCategory->column('id,cat_name')
        ]);
    }
    /*添加、修改文章*/
    public function articleHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if(!trimall($dt['publish_time'])){
                $dt['publish_time'] = date('Y-m-d H:i:s',time());
            }
            $dt['content'] = imgHandle($_POST['content'],'article');
            /*裁剪图片*/
            if($dt['thumb']){
                $dt['thumb'] = $this->uploadify->imgHandle($dt['thumb'],'article','1','300','300');
            }

            if($id >0){//修改
                $rs = $this->article->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->article->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Article/articleList')):$this->error($rs['msg']);
        }
        $info = $this->article->get($id);
        return view('article/_article',[
            'info'=>$info,
            'id'=>$id,
            'cat_list' =>$this->articleCategory->getSortList(),//所属文章分类
        ]);
    }
    /*删除节点*/
    public function delArticle()
    {
        return $this->article->del(input('param.ids'));
    }
}