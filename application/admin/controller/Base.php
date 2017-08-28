<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2016/12/28
 * Time: 18:55
 */
namespace app\admin\controller;
use app\admin\model\AdminRightMenu;
use app\admin\model\AdminRole;
use app\admin\model\AdminUser;
use think\Controller;
use think\Db;

class Base extends Controller{
    protected $auth;
    protected $adminUser;
    protected $adminRole;
    protected $page;
    protected $admin_id;
    /*
     * 初始化操作
     * */
    protected function _initialize()
    {
        parent::_initialize();
        $this->adminUser = new AdminUser();
        $this->adminRole = new AdminRole();
        $this->adminRightMenu = new AdminRightMenu();
        needCacheConfig('system_config_data');//判断是否需要缓存配置文件
        $this->page = config('list_pages')?:'20';//分页大小
        $this->admin_id = session('admin_user_id');
//        $this->page = 3;
        //登录检测及主页权限
        if(in_array(req('action'),['login','logout','checkverify','getverify']) || in_array(req('controller'),['ueditor','uploadify'])){//过滤不需要登录即可操作的行为
            return true;
        }else{
            if(session('admin_user_id') >0){//检查管理员菜单操作权限
                $admin_info = $this->adminUser->getColumn(['id'=>session('admin_user_id')],'*',1);
                $admin_role = $this->adminRole->getColumn(['id'=>$admin_info['role_id']],'id,right_menu_ids,role_name',1);
                $admin_info['act_list'] = $admin_role['right_menu_ids'];
                $admin_info['role_name'] = $admin_role['role_name'];
                $this->auth = $admin_info;
                $this->checkPrivilege();
            }else{//跳转到登录页面
                $this->redirect(url('Admin/login'));
            }
        }
        if(config('web_site_status') == 1 && $this->admin_id !=1 ){
            $this->error('站点已经关闭，请稍后访问~');
        }
        if(config('deny_ip') && $this->admin_id !=1 ){
            if(in_array(req('ip'),config('deny_ip'))){
                $this->error('403:禁止访问');
            }
        }

        $this->assign([
                'admin_info'=>$this->auth,
        ]);
    }
    /*
     * 后台权限检测
     * */
    protected function checkPrivilege(){
        $controller = req('controller');
        $action = req('action');
        $act_list = $this->auth['act_list'];
        //无需验证权限的操作
        $uneed_check = ['login','loginout','upload'];
        if(($controller == 'index') || ($act_list=='all')){//后台首页或超级管理员
            return true;
        }elseif(strpos('ajax',$action) || in_array($action,$uneed_check)){//所有Ajax请求无需验证
            return true;
        }else{
            $right_arr=$this->adminRightMenu->getRights($act_list);
            if(empty($right_arr)){
                $right_arr = [];
            }
            if(!in_array(strtolower($controller).'@'.strtolower($action),$right_arr)){
                $this->error('您没有操作权限,请联系超级管理员分配权限',url('Index/welcome'));
            }
        }
    }
}