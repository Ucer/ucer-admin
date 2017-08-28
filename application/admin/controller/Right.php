<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/1/21
 * Time: 16:30
 */
namespace app\admin\controller;
use app\admin\model\AdminGroup;
use app\admin\model\AdminRightMenu;
use app\admin\model\AdminRole;
use think\Controller;

class Right extends Base{
    protected $adminRole;
    protected $adminRightMenu;
    protected $adminGroup;

    public function _initialize()
    {
        parent::_initialize();
        $this->adminGroup = new AdminGroup();
        $this->adminRightMenu = new AdminRightMenu();
        $this->adminRole = new AdminRole();
    }
    /*权限资源列表*/
    public function rightMenuList()
    {
        /*搜索条件*/
        $where = 1;
        $gid = input('post.group');
        $keywords = input('post.keywords');
        if(request()->isPost()){
            if($gid) $where .= " and (m.group_id = $gid)";
            if($keywords) $where .= " and (m.name like '%$keywords%')";
        }
        return view('right/right_menu_list',[
            'group'=>$this->adminGroup->getAllList(),
            'lists'=>$this->adminRightMenu->getAllRight($where),
            'groups'=>$gid,
            'keywords'=>$keywords
        ]);
    }
    /*是否禁用权限菜单开关*/
    public function powerSwitch()
    {
        $res = $this->adminRightMenu->save(['delete_at'=>input('param.val')],['id'=>input('param.id')]);
        (input('param.val')==0)? $this->success('已启用'):$this->error('已禁用');
    }
    /*添加|修改权限列表*/
    public function rightMenuHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            $dt['delete_at'] = isset($dt['delete_at']) ?0:1;
            if($id >0){//修改
                $rs = $this->adminRightMenu->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adminRightMenu->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Right/rightMenuList')):$this->error($rs['msg']);
        }
        $info = $this->adminRightMenu->get($id);
        if($info){
            $info['right'] = explode(',',$info['right']);
        }
        //获取控制器列表
        $fh = opendir(APP_PATH.'admin/controller');
        $cList = [];
        while($dir =readdir($fh)){
            if(!in_array(strtolower($dir),['.','..','svn','git','base.php'])){
                $cList[] = basename($dir,'.php');
            }
        }

        return view('right/_right_menu',[
            'info'=>$info,'id'=>$id,
            'group'=>$this->adminGroup->getAllList(),
            'cList'=>$cList
        ]);
    }
    /*获取控制器下面的方法*/
    public function ajaxAction()
    {
        $ctrol = input('post.ctrol');
        $ctrolList = get_class_methods( "app\admin\controller\\".$ctrol);
        $ctrolBase = get_class_methods( "app\admin\controller\Base");
        $ctrolList = array_diff($ctrolList,$ctrolBase);
        $html = '';
        foreach($ctrolList as $v){
            $html .= "<option value='".$v."'>".$v."</option>";
        }
       exit($html);
    }
    /*删除权限菜单*/
    public function delRightMenu()
    {
        return $this->adminRightMenu->del(input('post.ids/a'));
    }
    /*角色列表*/
    public function roleList()
    {
        return view('right/role_list',[
            'lists'=>$this->adminRole->getColumn(),
        ]);
    }
    /*添加|修改权限列表*/
    public function roleHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->adminRole->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adminRole->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Right/roleList')):$this->error($rs['msg']);
        }
        $info = $this->adminRole->get([$id]);
        //所拥有的权限
        $right = $this->adminRightMenu->getAllRight("m.delete_at =0");
        foreach($right as $v){
            if($info){
                $v['default'] = in_array($v['id'],explode(',',$info['right_menu_ids']))?1:0;
            }else{
                $v['default'] = 0;
            }
            $modules[$v['group_id']][] = $v;
        }
        return view('right/_role',[
            'id'=>$id,
            'modules'=>$modules,
            'group'=>$this->adminGroup->column('id,name'),
            'info'=>$info
        ]);
    }
    /*删除角色*/
    public function delRole()
    {
        return $this->adminRole->delRole(input('post.ids/a'));
    }
}