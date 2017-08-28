<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/20
 * Time: 18:53
 */
namespace app\admin\controller;
use app\admin\model\UserLevel;
use think\Controller;

class Users extends Base{
    protected $userLevel;
    protected $users;
    protected $uploadify;
    protected function _initialize(){
        parent::_initialize();
        $this->userLevel = new UserLevel();
        $this->users = new \app\admin\model\Users();
        $this->uploadify = new Uploadify();
    }
    /*用户组列表*/
    public function userLevel()
    {
        $list = $this->userLevel->order(['sort'=>'asc','created_at'=>'desc'])->select();
        return view('user/user_level', [
            'list'=>$list,
        ]);
    }
    /*用户组处理*/
    public function levelHandle()
    {
        $id = (int) $this->request->param('id');

        if(request()->isPost()){
            $dt = $this->request->param();

            if($id >0){//修改
                $rs = $this->userLevel->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->userLevel->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Users/userLevel')):$this->error($rs['msg']);
        }
        $info = $this->userLevel->get($id);
        return view('user/_user_level',[
            'info'=>$info,
            'id'=>$id,
        ]);
    }
    /*删除用户组*/
    public function delLevel()
    {
        return $this->userLevel->del(input('param.ids'));
    }
    /*会员列表*/
    public function userList()
    {
        return view('user/user_list',['level_list'=>$this->userLevel->field('id,name')->order(['sort'=>'asc','created_at'=>'desc'])->select()]);
    }
    /*会员列表*/
    public function ajaxuserList()
    {
        $keywords = $this->request->param('keywords');
        $is_lock = $this->request->param('is_lock');
        $cat_id = $this->request->param('cat_id');
        $where=1;
        if($is_lock){
            $where .= " AND (`is_lock` = $is_lock)";
        }
        if($keywords){
            $where .= " AND ( (mobile like '%$keywords%') OR (email like '%$keywords%') )";
        }
        if($cat_id){
            $where .= " AND (level_id = ".$cat_id.")";
        }
        list($list,$page) = $this->users->getPageList($where,$this->page,'*',['created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('user/ajax_userList', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'level_list'=>$this->userLevel->field('id,name')->order(['sort'=>'asc','created_at'=>'desc'])->select()
        ]);
    }
    /*添加、修改会员*/
    public function userHandle()
    {
        $id = (int) $this->request->param('id');

        if(request()->isPost()){
            $dt = $this->request->param();
            /*裁剪图片*/
            if($dt['head_pic']){
                $dt['head_pic'] = $this->uploadify->imgHandle($dt['head_pic'],'ulogo','1','200','200');
            }

            if($id >0){//修改
                $rs = $this->users->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->users->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Users/userList')):$this->error($rs['msg']);
        }
        $info = $this->users->get($id);
        return view('user/_user',[
            'info'=>$info,
            'id'=>$id,
            'level_list'=>$this->userLevel->field('id,name')->order(['sort'=>'asc','created_at'=>'desc'])->select()
        ]);
    }
    /*删除会员*/ //TODO
    public function delUsers()
    {
//        return $this->users->del(input('param.ids'));
    }
}