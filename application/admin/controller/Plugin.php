<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/27
 * Time: 15:44
 */
namespace app\admin\controller;

use app\admin\model\ShippingArea;
use think\Config;
use think\Db;
use think\Loader;
use think\Url;

class Plugin extends Base{
    protected $plugin;
    protected $shippingArea;
    protected $uploadify;
    protected function _initialize()
    {
        parent::_initialize();
        $this->plugin = new \app\admin\model\Plugin();
        $this->updateToSql($this->scanPlugins());
        $this->shippingArea = new ShippingArea();
        $this->uploadify = new Uploadify();
    }
    /*插件列表*/
    public function index()
    {
        $plugin_list =$this->plugin->column('*');
        $plugin_list = groupSameKey($plugin_list,'type');
        $plugin_list['login'] = isset($plugin_list['login'])?$plugin_list['login']:[];
        $plugin_list['shipping'] = isset($plugin_list['shipping'])?$plugin_list['shipping']:[];
        $plugin_list['function'] = isset($plugin_list['function'])?$plugin_list['function']:[];
        $plugin_list['payment'] = isset($plugin_list['payment'])?$plugin_list['payment']:[];

       return view('plugin/index',[
           'type'=>$this->request->param('type'),
           'login'=>$plugin_list['login'],
           'shipping'=>$plugin_list['shipping'],
           'function'=>$plugin_list['function'],
           'payment'=>$plugin_list['payment'],
       ]);
    }
    /*插件处理*/
    public function pluginHandle()
    {
        $type = $this->request->param("type");//如login
        $code = $this->request->param("code");//如qq
        $status = $this->request->param("install");//1安装，0御载

        $map['code'] = $code;
        $map['type'] = $type;

        //如果是功能插件
        if($type == 'function'){

        }

        //如果是物流插件，卸载时先判断是否有订单使用该物流公司插件  //TODO
        if($type == 'shipping' && $status ==0){
//            $sun_shop = Db::name('goods')->column("GROUP_CONCAT(shipping_area_ids SEPARATOR ',')");
//                Db::name('shipping_area')->where()

        }

        //卸载插件时 删除配置信息
        if($status==0){
            $res = $this->plugin->where($map)->delete();
        }else{
            $stu = $this->plugin->where($map)->value('status');//0不启用，1启用
            if($stu ==1){
                return json(['status'=>0,'msg'=>'该插件已经安装过了']);
            }else{
                $res = $this->plugin->where($map)->update(['status' => $status]);//0不启用，1启用
            }
        }
        //如果是支付插件，安装时更新配置信息(读取最新的配置)
        if($type == 'payment' && $status ==1){
            $conf = ROOT_PATH.DS.'public'.DS.'plugins'.DS.$type.DS.$code.DS.'config.php';//插件配置目录
            $config = include $conf;
            $this->plugin->where($map)->update(['bank_code'=>serialize($config['bank_code']),'config'=>serialize($config['config'])]);
        }

        if($res){ //安装或卸载成功后
            //如果是物流插件 记录一条默认信息
            $shiping_DB = Db::name('shipping_area');
            $count = $shiping_DB->where(['shipping_code'=>$code])->count();
            if($type == 'shipping'){
                if($status ==1){
                    if($count ==0){
                        $config['first_weight'] = '1000'; // 首重
                        $config['second_weight'] = '2000'; // 续重
                        $config['money'] = '12';
                        $config['add_money'] = '2';
                        $add['shipping_area_name'] ='全国其他地区';
                        $add['shipping_code'] =$code;
                        $add['config'] =serialize($config);
                        $add['is_default'] =1;
                        $shiping_DB->insert($add);
                    }
                }else{
                    $shiping_DB->where(['shipping_code'=>$code])->delete();
                }

            }
            $result['status'] = 1;
            $result['msg'] = $status ? '安装成功!' : '卸载成功!';
        }else{
            $result['status'] = 0;
            $result['msg'] = '系统出错，请重试!';
        }
        return json($result);
    }
    /*扫描插件目录*/
    private function scanPlugins(){
        $plugin_list = [];
        $plugin_list['login'] = $this->dirScan(Config::get('login_plugin_path'));
        $plugin_list['payment'] = $this->dirScan(Config::get('payment_plugin_path'));
        $plugin_list['shipping'] = $this->dirScan(Config::get('shipping_plugin_path'));
        $plugin_list['function'] = $this->dirScan(Config::get('function_plugin_path'));
        foreach($plugin_list as $k=>$v){
            foreach($v as $kk=>$vv){
                $conf = ROOT_PATH.DS.'public'.DS.'plugins'.DS.$k.DS.$vv.DS.'config.php';//插件配置目录
                if(file_exists($conf)){
                    $plugin_list[$k][$vv] = include($conf);
                }
                unset($plugin_list[$k][$kk]);
            }
        }
        return $plugin_list;
    }
    /**
     * 获取插件目录列表
     * @param $dir
     * @return array
     */
    private function dirScan($dir){
        $dir_arr = [];
        if(false != ($fh = opendir($dir))){
            $i = 0;
            while(false != ($file = readdir($fh))){
                //去掉"“.”、“..”以及带“.xxx”后缀的文件
                if($file !="." && $file !=".." && !strpos($file,".")){
                    $dir_arr[$i] = $file;
                    $i++;
                }

            }
            closedir($fh);
        }
        return $dir_arr;
    }
    /**
     * 添加新插件时，自动更新到数据库
     * @param $plugin_list 本地插件数组
     */
    private function updateToSql($plugin_list){
        $d_list =  Db::name('plugin')->field('code,type')->select(); // 数据库

        $local_arr = array(); // 本地
        //插件类型
        foreach($plugin_list as $pt=>$pv){
            foreach($pv as $t=>$v){
                $tmp['code'] = $v['code'];
                $tmp['type'] = $pt;
                $local_arr[] = $tmp;
                // 对比数据库 本地有 数据库没有
                $is_exit = $this->plugin->where(array('type'=>$pt,'code'=>$v['code']))->count();
                if($pt== 'shipping'){
//                    dd($this->plugin->getLastSql());
                }
                if($is_exit ==0){
                    $add['code'] = $v['code'];
                    $add['name'] = $v['name'];
                    $add['version'] = $v['version'];
                    $add['icon'] = $v['icon'];
                    $add['author'] = $v['author'];
                    $add['desc'] = $v['desc'];
                    $add['bank_code'] = serialize($v['bank_code']);
                    $add['type'] = $pt;
                    $add['scene'] = $v['scene'];
                    $add['config'] = empty($v['config']) ? '' : serialize($v['config']);
                    $this->plugin->insert($add);
                }
            }
        }
        //数据库有 本地没有
        foreach($d_list as $k=>$v){
            if(!in_array($v,$local_arr)){
                $this->plugin->where($v)->delete();
            }
        }
    }
    /*插件信息配置*/
    public function setting()
    {
        $type = $this->request->param('type');
        $code = $this->request->param('code');

        $map=['code'=>$code, 'type'=>$type];
        $info = $this->plugin->where($map)->find();
        if(!$info) $this->error('插件不存在');
        $info['config'] = unserialize($info['config']);

        if(request()->isPost()){
            $config = $this->request->param('');
            $config = trimArrayElement($config['config']);//过滤空格
            if($config){
                $config = serialize($config);
                $rs = $this->plugin->where($map)->update(['config_value'=>$config]);
                $rs ?$this->success('操作成功'):$this->error('操作失败，请重试');
            }
        }
        return view('plugin/setting',[
            'info'=>$info,
            'config_value'=>unserialize($info['config_value']),
            'code'=>$code,
            'type'=>$type,
        ]);

    }
    /*物流插件配置*/
    public function shippingList()
    {
        $data = $this->request->param("");
        $row = $this->plugin->where(['type'=>$data['type'],'code'=>$data['code']])->find();//详情
        if(!$row) $this->error("插件不存在");
        $sql = "SELECT sa.is_default,sa.shipping_area_name,sa.id as sa_id,
                (SELECT GROUP_CONCAT(r.name SEPARATOR ',') FROM pc_area_region ar LEFT JOIN pc_region r ON r.id = ar.region_id WHERE ar.shipping_area_id =sa.id ) AS region_ist
                FROM pc_shipping_area sa WHERE sa.shipping_code ='{$row['code']}'";
        $list = Db::query($sql);
        return view('plugin/shipping_list',[
            'shipping_info'=>$row,
            'lists'=>$list,
            'type'=>$data['type'],
            'code'=>$data['code']
        ]);
    }
    /*处理物流配送区域*/
    public function shippingHandle()
    {
        $type = $this->request->param("type");
        $code = $this->request->param("code");
        $id = $this->request->param("id");
        $default = $this->request->param("default");
        $row = $this->plugin->where(['type'=>$type,'code'=>$code])->find();//插件详情
        if(!$row) $this->error("插件不存在");

        $info = $this->shippingArea->get($id);
        if($id && !$info){
            $this->error("插件不存在");
        }

        if(request()->isPost()){
            $dt = input("param.");
            $dt['shipping_code'] = $row['code'];
            $dt['default'] = $info['is_default'];
            if($id >0){//修改
                $rs = $this->shippingArea->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->shippingArea->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',Url::build('Plugin/shippingList',['type'=>$type,'code'=>$code])):$this->error($rs['msg']);
        }


        if($info) $info['config'] =unserialize($info['config']);
        $province = regionHandle(getRegionList(),1,0);//省份
        $sql = "SELECT ar.region_id,r.name FROM pc_area_region ar LEFT JOIN pc_region r ON r.id = ar.region_id WHERE ar.shipping_area_id = {$id}";
        $select_area = Db::query($sql);
        $this->assign('select_area',$select_area);
        $this->assign('province',$province);
        $this->assign('info',$info);
        $this->assign('id',$id);
        $this->assign('row',$row);
        if($default == 1 || $info['is_default'] ==1){//默认
            return $this->fetch('plugin/_shipping_default');
        }
        return  $this->fetch('plugin/_shipping');
    }
    /*删除配送区域*/
    public function delShipping()
    {
        $id = $this->request->param("id");
        return $this->shippingArea->del($id);
    }
    /*添加物流插件*/
    public function addShipping()
    {
        if($this->request->isPost()){

            $data = $this->request->param("");
            $code = strtolower($data['code']); // 编码
            $name = $data['name']; // 物流名字
            $desc = $data['desc'];// 插件描述
            $dir = ROOT_PATH.DS.'public'.DS.'plugins'.DS.'shipping'.DS.$code.DS;
            $dt['code'] = $code; $dt['name'] = $name; $dt['img'] = $data['shipping_img'];
            $rules =$this->validate($dt,[
                ['code', ['regex'=>'/[a-zA-Z]{2,20}/'],'物流编码必须由2-20位小写字母组成'],
                ['code','unique:plugin',"编码 $code 已存在"],
                ['img','require',"必须上传图片"],
            ]);
            if(true !== $rules) $this->error($rules);

            if (!file_exists($dir)) mkdir($dir);
            // icon图片
            $this->uploadify->imgHandleShipping($data['shipping_img'],$dir,300,300);
            $config_html = "<?php
                        return array(
                            'code'=> '$code',
                            'name' => '$name',
                            'version' => '1.0',
                            'author' => '漂过太平洋',
                            'desc' => '$desc ',
                            'icon' => 'logo.jpg',
                            'bank_code' => '',
                            'scene' => '0',
                        );";
            file_put_contents($dir.'config.php', $config_html);
            $this->success("添加成功");
        }

        return $this->fetch('plugin/add_shipping');
    }
    /*删除物流插件*/
    public function delPlugin()
    {
        $code = $this->request->param("code");
        $c = $this->shippingArea->where(["shipping_code" =>$code])->count();
        $c && $this->error('请先卸载该物流插件');
        $dir = ROOT_PATH.DS.'public'.DS.'plugins'.DS.'shipping'.DS.$code.DS;
        delFile($dir); // 删除 物流配置
        rmdir($dir); // 删除 物流配置
        $this->plugin->where(["code" =>$code,'type'=> 'shipping'])->delete();
        $this->success('删除成功');
    }

}