<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/8
 * Time: 16:03
 */
namespace app\home\controller;
use app\admin\model\WxAccount;
use app\admin\model\WxImages;
use app\admin\model\WxText;

class WeiXin extends Base{
    protected $wxAccount;
    protected $wxText;
    protected $wxImages;
    private $wxInfo;
    protected function _initialize()
    {
        parent::_initialize();
        $this->wxAccount = new WxAccount();
        $this->wxText = new WxText();
        $this->wxImages = new WxImages();
        $this->wxInfo = $this->wxAccount->column('*');
    }
    /*微信接入*/
    public function index()
    {
       $info = reset($this->wxInfo);
        if($info['status'] == 0){
            exit(input('get.echostr'));
        }else{
            $this->responseMsg();
        }
    }
    /*微信相关事件*/
    protected function responseMsg(){
        $postStr = file_get_contents("php://input");//$GLOBALS["HTTP_RAW_POST_DATA"];
        if(empty($postStr)) exit('error');

        /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
           the best way is to check the validity of xml by yourself */
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        //点击菜单拉取消息时的事件推送
        /*
         * 1、click：点击推事件
         * 用户点击click类型按钮后，微信服务器会通过消息接口推送消息类型为event的结构给开发者（参考消息接口指南）
         * 并且带上按钮中开发者填写的key值，开发者可以通过自定义的key值与用户进行交互；
         */
        if($postObj->MsgType == 'event' && $postObj->Event == 'CLICK')
        {
            $keyword = trim($postObj->EventKey);
        }
//        if(empty($keyword)) exit("Input something...");
        if(empty($keyword)){//关注时间？？
            // 其他文本回复
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";

            $contentStr = '欢迎来到太平洋后台管理系统!';
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
            exit($resultStr);
        }
        // 图文回复
//        $wx_img = $this->wxImages->where("keyword like '%$keyword%'")->find();
        $wx_img = $this->wxImages->where(['keyword'=>$keyword])->find();

        if($wx_img)
        {
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[%s]]></Title>
                                        <Description><![CDATA[%s]]></Description>
                                        <PicUrl><![CDATA[%s]]></PicUrl>
                                        <Url><![CDATA[%s]]></Url>
                                    </item>
                                </Articles>
                                </xml>";
            $resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,'news','1',$wx_img['title'],$wx_img['desc']
                , 'http://zhjaa.online'.$wx_img['pic'], $wx_img['url']);
            exit($resultStr);
        }

        // 文本回复
//        $wx_text = $this->wxText->where("keyword like '%$keyword%'")->find();
        $wx_text = $this->wxText->where(['keyword'=>$keyword])->find();
        if($wx_text)
        {
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
            $contentStr = $wx_text['content'];
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
            exit($resultStr);
        }
        // 其他文本回复
        $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";

        $contentStr = '太平洋后台管理系统无法识别!';
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
        exit($resultStr);
    }
}