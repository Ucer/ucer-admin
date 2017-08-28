<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/28
 * Time: 13:54
 */
namespace app\mobile\controller;
use think\Db;

class LoginApi extends Base{
    protected $oauth;
    protected $config;
    protected $classObj;
    protected function _initialize()
    {
        parent::_initialize();
        $this->oauth = $this->request->param("oauth");
        $data = Db::name('plugin')->where(['code'=>$this->oauth,'type'=>'login'])->find();
        if($data) $this->config = unserialize($data['config_value']);//反序列化
        if(!$this->oauth) $this->error('非法操作');
        include_once ROOT_PATH.DS.'public'.DS.'plugins'.DS.'login'.DS.$this->oauth.DS.$this->oauth.'.class.php';//插件配置目录
        $class = '\\'.$this->oauth; //
        $this->classObj  = new $class($this->config); //实例化对应的登陆插件

    }
    /*登录*/
    public function login()
    {
        if(!$this->oauth) $this->error('非法操作');
        $this->classObj->login();
    }
}