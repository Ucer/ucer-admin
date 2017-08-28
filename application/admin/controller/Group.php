<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/1/9
 * Time: 13:51
 */
namespace app\admin\controller;
use app\admin\model\AdminGroup;
use think\Db;

class Group extends Base{
    protected $adminGroup;
    /*初始化操作*/
    public function _initialize()
    {
        parent::_initialize();
        $this->adminGroup = new AdminGroup();

    }
    /*分组列表*/
    public function index()
    {
        return view('group/index');
    }
    /*分组列表*/
    public function ajaxIndex()
    {
        list($list,$page) = $this->adminGroup->getPageList(1,$this->page,'*');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('group/ajax_index', [
            'lists'=>$list,
            'page'=>$ajax_page
        ]);
    }
    /*添加|修改分组列表*/
    public function groupHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->adminGroup->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adminGroup->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Group/index')):$this->error($rs['msg']);
        }
        $info = $this->adminGroup->get($id);
        return view('group/_group',['info'=>$info,'id'=>$id]);
    }
    /*删除分组*/
    public function delGroup()
    {
        return $this->adminGroup->del(input('post.ids/a'));
    }
}