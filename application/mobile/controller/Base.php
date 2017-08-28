<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/13
 * Time: 9:36
 */
namespace app\mobile\controller;
use app\home\logic\UserLogic;
use app\mobile\logic\Jssdk;
use think\Config;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
use think\Session;

class Base extends Controller{
    protected $session_id;
    protected $wx_config;
    protected $cateTree;
    protected $mpage;
    protected function _initialize()
    {
        parent::_initialize();
        Session::start();//启动session以获取session_id
        $this->session_id = session_id();
        $this->cateTree = [];
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        needCacheConfig('system_config_data');//判断是否需要缓存配置文件

        //判断当前用户是否为手机
        if(isMobile()){
            Cookie::set('is_mobile','1',3600);// 设置Cookie 有效期为 3600秒
        }else{
            Cookie::set('is_mobile','0',3600);// 设置Cookie 有效期为 3600秒
        }
        //获取微信配置
        $this->wx_config = Db::name('wx_account')->find();
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') && ($this->wx_config['status'])){
            $openid = Session::get('openid');
            if(!$openid && $this->wx_config ){
                //获取openid
                $wx_user = $this->getOpenid();
                Session::set('subscribe', $wx_user['subscribe']);// 当前这个用户是否关注了微信公众号
                //微信自动登录
                $data = array(
                    'openid'=>$wx_user['openid'],//支付宝用户号
                    'oauth'=>'weixin',
                    'nickname'=>trim($wx_user['nickname']) ? trim($wx_user['nickname']) : '微信用户',
                    'sex'=>$wx_user['sex'],
                    'head_pic'=>$wx_user['headimgurl'],
                );
                $logic = new UserLogic();
                //登录，获取用户信息
                $dataa = $logic->thirdLogin($data);
                if($dataa['status'] ==1){//如果登录成功
                    //session存储
                    Session::set('user_info',$dataa['data']);
                    Cookie::set('user_id',$dataa['data']['id']);
                    // 登录后将购物车的商品的 user_id 改为当前登录的id
                    Db::name('cart')->where(['session_id'=>$this->session_id])->update(array('users_id'=>Cookie::get('user_id')));
                }

            }
            // 微信Jssdk 操作类 用分享朋友圈 JS
            $jssdk = new Jssdk($this->wx_config['appid'], $this->wx_config['appsecret']);
            $signPackage = $jssdk->getSignPackage();
            $this->assign('signPackage', $signPackage);
        }
        $this->publicAssign();

    }
    /*渲染变量到模板方法*/ //TODO
    private function publicAssign(){
        $this->assign('wx_config',$this->wx_config); //微信配置
        $this->cateTree  = getGoodsCatTree();
        $this->assign(['goods_category_tree'=>$this->cateTree]);


    }
    /*网页授权登录获取openid*/
    private function getOpenid(){
        $openid = Session::get('openid');
        if($openid) return $openid;
        //通过code获取openid
        if(!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->getUrl());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        }else{
            // 上面跳转, 这里跳了回来
            //获取code码，以获取openid
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);
            $data2 = $this->getUserInfo($data['access_token'],$data['openid']);
            $data['nickname'] = $data2['nickname'];
            $data['sex'] = $data2['sex'];//1为男
            $data['headimgurl'] = $data2['headimgurl'];
            $data['subscribe'] = $data2['subscribe'];
            Session::set('openid',$data['openid']);
            return $data;
        }
    }
    /**
     *
     * 通过access_token openid 从工作平台获取UserInfo
     * @return openid
     */
    private function getUserInfo($access_token,$openid)
    {
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);//取出openid access_token
        curl_close($ch);

        // 获取看看用户是否关注了 你的微信公众号， 再来判断是否提示用户 关注
        $access_token2 = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token2&openid=$openid";
        $subscribe_info = httpRequest($url,'GET');
        $subscribe_info = json_decode($subscribe_info,true);
        $data['subscribe'] = $subscribe_info['subscribe'];

        return $data;
    }
    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
    }
    private function getAccessToken(){
        //判断是否过了缓存期
        $expire_time = $this->wx_config['web_expires'];
        if($expire_time > time()){//没有期
            return $this->wx_config['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->wx_config['appid']}&secret={$this->wx_config['appsecret']}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7000; // 提前200秒过期
        Db::name('wxAccount')
            ->where(['id'=>$this->wx_config['id']])
            ->update(['web_access_token'=>$return['access_token'], 'web_expires'=>$web_expires]);
        return $return['access_token'];
    }
    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    private function getOpenidFromMp($code)
    {
        //通过code换取网页授权access_token  和 openid
        $url = $this->__CreateOauthUrlForOpenid($code);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);//取出openid access_token
        curl_close($ch);

        return $data;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->wx_config['appid'];
        $urlObj["secret"] = $this->wx_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }
    /**
     * 获取当前的url 地址
     * @return type
     */
    private function getUrl() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        $url = $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
        return $url;
    }
    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->wx_config['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->toUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function toUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
}