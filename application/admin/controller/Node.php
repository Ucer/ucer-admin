<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/1/5
 * Time: 14:44
 */
namespace app\admin\controller;
use app\admin\model\AdminAllMenu;

class Node extends Base{
    protected $adminAllMenu;
    /*初始化操作*/
    public function _initialize()
    {
        parent::_initialize();
        $this->adminAllMenu = new AdminAllMenu();
    }
    /*节点列表页*/
    public function index()
    {
        $all_menu = $this->adminAllMenu->order(['sort'=>'asc'])->select();
        return view('node/index',['node_list'=>subTree(objToArray($all_menu))]);
    }
    /*添加|修改菜单列表*/
    public function nodeHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            $dt['is_menu'] = isset($dt['is_menu']) ?1:2;
            if($id >0){//修改
                if($dt['parent_id'] >0){
                    //找出家谱树，上级不能是自己或自己的后代
                    $all_menu = $this->adminAllMenu->order(['sort'=>'asc'])->column('id,name,parent_id');
                    $family_tree_ids = findFamilyTree($all_menu,$dt['parent_id']);
                    if(in_array($id,$family_tree_ids)){//如果id在选中父id的家谱树中
                        $this->error('上级节点不能是自己或自己的后代');
                    }
                }
                $rs = $this->adminAllMenu->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adminAllMenu->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Node/index')):$this->error($rs['msg']);
        }
        $menu_list = $this->adminAllMenu->getSortList();
        $info = $this->adminAllMenu->get($id);
        return view('node/_node',['info'=>$info,'menu_list'=>$menu_list,'id'=>$id]);
    }
    /*是否为菜单开关*/
    public function powerSwitch()
    {
        $res = $this->adminAllMenu->save(['is_menu'=>input('param.val')],['id'=>input('param.id')]);
        (input('param.val')==1)? $this->success('已显示'):$this->error('已隐藏');
    }
    /*节点图标*/
    public function getIcons()
    {
        return view('node/icons');
    }
    /*删除节点*/
    public function delNode()
    {
        return $this->adminAllMenu->del(input('param.ids'));
    }
}