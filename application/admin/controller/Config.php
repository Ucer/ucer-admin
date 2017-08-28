<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/14
 * Time: 9:52
 */
namespace app\admin\controller;
use app\admin\model\AdminConfig;
use think\Controller;

class Config extends Base{
    private $adminConfig;
    protected function _initialize(){
        parent::_initialize();
        $this->adminConfig = new AdminConfig();
    }
    /*配置列表*/
    public function configList()
    {
        return view('config/index',['group_list'=>config('system_config_group')]);
    }
    /*配置列表*/
    public function ajaxConfigList()
    {
        $keywords = input('param.keywords');
        $group = input('param.group');
        $where=1;
        if($group){
            $where .= " and (`group` = $group)";
        }
        if($keywords){
            $where .= " and ( (name like '%$keywords%') OR (title like '%$keywords%') )";
        }
        list($list,$page) = $this->adminConfig->getPageList($where,$this->page,'*');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('config/ajax_index', [
            'lists'=>$list,
            'page'=>$ajax_page,
        ]);
    }
    /*添加、修改配置*/
    public function configHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->adminConfig->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adminConfig->handle($dt);
            }
            cache('system_config_data',null);
            $rs['code'] ==1 ? $this->success('操作成功',url('Config/configList')):$this->error($rs['msg']);
        }
        $info = $this->adminConfig->get($id);

        return view('config/_config',[
            'info'=>$info,
            'type_list'=>config('system_type_list'),
            'group_list'=>config('system_config_group'),
            'id'=>$id,
        ]);
    }
    /*系统配置*/
    public function systemConfig()
    {
        $id = input('param.group_id',1);
        $list = $this->adminConfig->where(['status'=>0,'group'=>$id])->order(['sort'=>'asc'])->select();
        if(request()->isPost()){
            $dt = input('param.');
           if($dt['config']){
               foreach($dt['config'] as $k=>$v){
                   if($v){
                       $v = str_replace("\r\n",',',$v);
                   }
                   $this->adminConfig->where(['name'=>$k])->update(['value'=>$v]);
               }
           }
            cache('system_config_data',null);
            $this->success('操作成功');
        }
        return view('config/system_config',[
            'type_list'=>config('system_type_list'),
            'group_list'=>config('system_config_group'),
            'group_id'=>$id,
            'list'=>$list
        ]);
    }
    /*删除配置文件*/
    public function delConfig()
    {
        return $this->adminConfig->del(input('post.ids'));
    }
}