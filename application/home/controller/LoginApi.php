<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/28
 * Time: 13:54
 */
namespace app\home\controller;
use app\home\logic\UserLogic;
use think\Cookie;
use think\Db;
use think\Session;

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
    /*第三方登录回调函数*/
    public function callback(){
        $data = $this->classObj->respon();
        $logic = new UserLogic();
        $data = $logic->thirdLogin($data);
        if($data['status'] != 1)
            $this->error($data['msg']);
        //session存储
        Session::set('user_info',$data['data']);
        Cookie::set('user_id',$data['data']['id']);

        // 登录后将购物车的商品的 user_id 改为当前登录的id
        Db::name('cart')->where(['session_id'=>$this->session_id])->update(array('users_id'=>Cookie::get('user_id')));

        $this->success('登陆成功',url('mobile/Users/index'));

//        if(isMobile()){
//            $this->success('登陆成功',url('mobile/Users/index'));
//
//        } else{
//            $this->success('登陆成功',url('Users/index'));
//        }
    }
}