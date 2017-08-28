<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/2
 * Time: 13:01
 */
namespace app\admin\controller;
use app\admin\model\WxAccount;
use app\admin\model\WxImages;
use app\admin\model\WxMenu;
use app\admin\model\WxText;
use think\Db;
class Wechat extends Base{
    protected $wxAccount;
    protected $wxMenu;
    private $wxToken;
    private $wxInfo;
    protected $wxText;
    protected $wxImages;
    protected $uploadify;
    public function _initialize()
    {
        parent::_initialize();
        $this->wxAccount = new WxAccount();
        $this->wxMenu = new WxMenu();
        $this->wxText = new WxText();
        $this->wxImages = new WxImages();
        $this->wxToken = $this->wxAccount->value('token');
        $this->wxInfo = $this->wxAccount->column('*');
        $this->uploadify = new Uploadify();
    }
    /*公众号列表*/
    public function index()
    {
        return view('wechat/index',[
            'lists'=>Db::name('wx_account')->select()
        ]);
    }
    /*添加|修改权限列表*/
    public function accountHandle()
    {
        $id = input('param.id');
        $find = $this->wxAccount->count();
        ($id < 1) && $find && $this->error('只能添加一个公众号');

        if(request()->isPost()){
            $dt = input("param.");
            /*裁剪图片*/
            if($dt['header_pic']){
                $dt['header_pic'] = $this->uploadify->imgHandle($dt['header_pic'],'wechat','1','100','100');
            }
            if($dt['qr']){
                $dt['qr'] = $this->uploadify->imgHandle($dt['qr'],'wechat','1','100','100');
            }

            if($id >0){//修改
                $rs = $this->wxAccount->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->wxAccount->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Wechat/index')):$this->error($rs['msg']);
        }
        $info = Db::name('wx_account')->find([$id]);
        return view('wechat/_account',[
            'id'=>$id,
            'info'=>$info,
            'url'=>'http://'.request()->host().'/home/WeiXin/index',
        ]);
    }
    /*删除分组*/
    public function delAccount()
    {
        $rs = $this->wxAccount->where(['id'=>['in',implode(',',input('post.ids/a'))]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
    /*公众号菜单列表*/
    public function menuList()
    {
        $p_list = $this->wxMenu->getByWhere(['pid'=>0]);
        $s_list = $this->wxMenu->getByWhere(['pid'=>['gt',0]]);
        $table_info = Db::query("SHOW TABLE STATUS WHERE NAME = 'pc_wx_menu'");
        $max_id = $table_info[0]['Auto_increment']?:0;

        //微信菜单处理
        if(request()->isPost()){
            $dt = input('post.menu/a');
            Db::startTrans();//开启事物
            try{
                foreach($dt as $k=>$v){
                    if( in_array($k,$this->wxMenu->Column('id')) ){//更新
                        if(!trimall($v['name'])) $this->error('菜单名称不能为空');
                        $this->wxMenu->allowField(true)->save($v,['id'=>$k]);
                    }else{//新增
                        if(trimall($v['name'])){
                            $this->wxMenu->allowField(true)->insert($v);
                        }
                    }
                }
                Db::commit();
                $this->success('操作成功,正在生成微信菜单',url('Wechat/pubMenu'));
            }catch (\PDOException $e){
                $this->error($e->getMessage());
                Db::rollback();
            }
        }

        return  view('wechat/menu_list',[
            'p_lists'=>$p_list,
            's_lists'=>$s_list,
            'max_id'=>$max_id
        ]);
    }
    /*微信菜单删除*/
    public function delWxMenu()
    {
        $id = input('param.id');
        $one = $this->wxMenu->get($id);
        if($one){
            if($one['pid'] > 0 )
                $this->wxMenu->where(['pid'=>$id])->delete();
            $this->wxMenu->where(['id'=>$id])->delete();
        }
         $this->success('删除成功');
    }
    /*生成微信菜单*/
    public function pubMenu()
    {
        $p_list = $this->wxMenu->getByWhere(['pid'=>0]);
        if(count($p_list) < 1){
            $this->error('没有可发布的菜单',url('Wechat/menuList'));
        }
        $poster = $this->convertMenu($p_list);
        $wx = reset($this->wxInfo);
        $access_token = $this->getAccessToken($wx['appid'],$wx['appsecret']);
        if(!$access_token){
            $this->error('获取access_token失败',url('Wechat/menuList'));
        }
        $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
        $return = httpRequest($url,'POST',$poster);
        $return = json_decode($return,1);
        if($return['errcode'] == 0){
            $this->success('微信菜单已成功生成',url('Wechat/menuList'));
        }else{
            $this->error("错误代码;".$return['errcode']);
        }

        // http post请求

    }
    /*微信菜单转换*/
    protected function convertMenu($p_list){
        /**示例菜单
        $menu = array();
        $menu['button'][] = array(
        'name'=>'测试',
        'type'=>'view',
        'url'=>'http://wwwtp-shhop.cn'
        );
        $menu['button'][] = array(
        'name'=>'测试',
        'sub_button'=>array(
        array(
        "type"=> "scancode_waitmsg",
        "name"=> "系统拍照发图",
        "key"=> "rselfmenu_1_0",
        "sub_button"=> array()
        )
        )
        );
         * **/
        $p_arr = [];
        $key = 0;
        foreach($p_list as $k=>$v){
            $p_arr[$key]['name'] = $v['name'];
            $v['type'] = getWxType($v['type']);

            //获取子菜单
            $s_list = $this->wxMenu->getByWhere(['pid'=>$k]);
            if($s_list){
                foreach($s_list as $kk=>$vv){
                    $s_arr = [];
                    $s_arr['name'] = $vv['name'];
                    $s_arr['type']= getWxType($vv['type']);
                    // click类型
                    if($s_arr['type'] == 'click'){
                        $s_arr['key'] = $vv['value'];
                    }elseif($s_arr['type'] == 'view'){
                        $s_arr['url'] = $vv['value'];
                    }else{
                        $s_arr['key'] = $vv['value'];
                    }
                    $s_arr['sub_button'] = array();
                    if($s_arr['name']){
                        $p_arr[$key]['sub_button'][] = $s_arr;
                    }
                }
            }else{
                $p_arr[$key]['type'] = $v['type'];
                // click类型
                if($p_arr[$key]['type'] == 'click'){
                    $p_arr[$key]['key'] = $v['value'];
                }elseif($p_arr[$key]['type'] == 'view'){
                    //跳转URL类型
                    $p_arr[$key]['url'] = $v['value'];
                }else{
                    //其他事件类型
                    $p_arr[$key]['key'] = $v['value'];
                }
            }
            $key++;
        }
        return json_encode(array('button'=>$p_arr),JSON_UNESCAPED_UNICODE);
    }
    /*先 从数据库获取access_token*/
    public function getAccessToken($appid,$appsecret){
        //判断是否过了缓存期
        $wechat = reset($this->wxInfo);
        $expire_time = $wechat['web_expires'];
        if($expire_time > time()){//没有期
            return $wechat['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7000; // 提前200秒过期
        $this->wxAccount->allowField(true)->save([
            'web_access_token'=>$return['access_token'],
            'web_expires'=>$web_expires
        ],['id'=>$wechat['id']]);
        return $return['access_token'];
    }
    /*文本回复*/
    public function textList()
    {
        return view('wechat/text_list',[
            'lists'=>$this->wxText->order(['created_at'=>'asc'])->column('*')
        ]);
    }
    /*添加|修改文本菜单列表*/
    public function textHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->wxText->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->wxText->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Wechat/textList')):$this->error($rs['msg']);
        }
        return view('wechat/_text',[
            'id'=>$id,
            'info'=>$this->wxText->get($id),
        ]);
    }
    /*删除文本关键词*/
    public function delText()
    {
        return $this->wxText->delText(input('post.ids/a'));
    }
    /*图文关键词*/
    public function imagesList()
    {
        return view('wechat/images_list',[
            'lists'=>$this->wxImages->order(['created_at'=>'asc'])->column('*')
        ]);
    }
    /*添加|修改图文关键词列表*/
    public function imagesHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            //图片裁剪
            if($dt['pic']){
                $dt['pic'] = $this->uploadify->imgHandle($dt['pic'],'wechat','1','500','400');
            }
            if($id >0){//修改
                $rs = $this->wxImages->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->wxImages->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Wechat/imagesList')):$this->error($rs['msg']);
        }
        return view('wechat/_images',[
            'id'=>$id,
            'info'=>$this->wxImages->get($id),
        ]);
    }
    /*删除图文关键词*/
    public function delImages()
    {
        return $this->wxImages->delImages(input('post.ids/a'));
    }
}