<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 * 参考地址 http://www.cnblogs.com/txw1958/p/weixin-js-sharetimeline.html
 * http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html  微信JS-SDK说明文档
 */

namespace app\mobile\logic;

use think\Db;
use think\model\Merge;
use think\Session;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class Jssdk extends Merge
{
 
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  // 签名
  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();
    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();//获取随机字符串

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
        "appId"     => $this->appId,
        "nonceStr"  => $nonceStr,
        "timestamp" => $timestamp,
        "url"       => $url,
        "rawString" => $string,
        "signature" => $signature

    );
    return $signPackage;

  }
  // 随机字符串
  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }
  /**
   * 根据 access_token 获取 icket
   * @return type
   */
  public function getJsApiTicket(){

    $ticket = Session::get('ticket');
    if(!empty($ticket)) return $ticket;

    $access_token = $this->getAccessToken();

    $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
    $return = httpRequest($url,'GET');
    $return = json_decode($return,1);
    Session::set('ticket',$return['ticket']);
    return $return['ticket'];
  }

  // 获取一般的 access_token
  private function getAccessToken(){
    //判断是否过了缓存期
    $wechat = Db::name('wxAccount')->find();
    $expire_time = $wechat['web_expires'];
    if($expire_time > time()){
      return $wechat['web_access_token'];
    }
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wechat['appid']}&secret={$wechat['appsecret']}";
    $return = httpRequest($url,'GET');
    $return = json_decode($return,1);
    $web_expires = time() + 7000; // 提前200秒过期
    Db::name('wxAccount')
        ->where(['id'=>$wechat['id']])
        ->update(['web_access_token'=>$return['access_token'], 'web_expires'=>$web_expires]);
    return $return['access_token'];
  }
  /*
 * 向用户推送消息
 */
  public function pushMsg($openid,$content){
    $access_token = $this->getAccessToken();
    $url ="https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
    $post_arr = array(
        'touser'=>$openid,
        'msgtype'=>'text',
        'text'=>array(
            'content'=>$content,
        )
    );
    $post_str = json_encode($post_arr,JSON_UNESCAPED_UNICODE);
    $return = httpRequest($url,'POST',$post_str);
    $return = json_decode($return,true);
  }
}