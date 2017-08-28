<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/8
 * Time: 16:03
 */
namespace app\home\controller;
use think\Controller;
use think\Cookie;
use think\Session;

class Base extends Controller{
    protected $session_id;
    protected function _initialize()
    {
        Session::start();
        $this->session_id = session_id();
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        needCacheConfig('system_config_data');//判断是否需要缓存配置文件

        //判断当前用户是否为手机
        if(isMobile()){
            Cookie::set('is_mobile','1',3600);// 设置Cookie 有效期为 3600秒
        }else{
            Cookie::set('is_mobile','0',3600);// 设置Cookie 有效期为 3600秒
        }
    }
}