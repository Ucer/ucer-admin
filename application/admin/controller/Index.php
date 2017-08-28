<?php
namespace app\admin\controller;
use app\admin\model\AdminAllMenu;
use app\admin\model\AdminRightMenu;
use think\Db;

class Index extends Base
{
    /*初始化操作*/
    public function _initialize()
    {
        parent::_initialize();
        $this->adminAllMenu = new AdminAllMenu();
        $this->adminRightMenu = new AdminRightMenu();
    }
    /* 首页*/
    public function index()
    {
        //菜单列表
        $all_menu = $this->adminAllMenu->getAllMenu(['is_menu'=>1]);
        $rignt_menu = $this->adminRightMenu->getRightList($this->auth['act_list'], $all_menu);

        return view('index/index',['menu_list'=>$rignt_menu]);
    }
    /*欢迎页*/
    public function welcome()
    {
        return view('index/welcome',['sys_info'=>$this->getSystemInfo()]);
    }
    /*获取系统信息*/
    protected function getSystemInfo(){
        $sys_info['os']             = PHP_OS;//服务器操作系统
        $sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
        $sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off
        $sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';//curl支持
        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];//服务器环境
        $sys_info['phpv']           = phpversion();//PHP 版本
        $sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);//服务器域名/IP
        $sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';//文件上传限制
        $sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sys_info['domain'] 		= $_SERVER['HTTP_HOST'];//服务器域名/IP
        $sys_info['memory_limit']   = ini_get('memory_limit');//最大占用内存
        $sys_info['version']   	    = file_get_contents('static/../version.txt');//程序版本
        $sys_info['think_v'] =        THINK_VERSION;//tp 版本$_SERVER['SERVER_SOFTWARE']
        $mysqlinfo =         Db::query("SELECT VERSION() as version");//Mysql 版本：'think_v'    => THINK_VERSION,
        $sys_info['mysql_version']  = $mysqlinfo[0]['version'];
        if(function_exists("gd_info")){//>GD 版本
            $gd = gd_info();
            $sys_info['gdinfo'] 	= $gd['GD Version'];
        }else {
            $sys_info['gdinfo'] 	= "未知";
        }
        return $sys_info;
    }
    /*vip视频解析*/
    public function vipVideo()
    {
//        dd($this->request('url'));
        return view("index/vip_video",['url'=>input("param.urli")]);
    }

}
