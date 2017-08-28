<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/21
 * Time: 21:56
 */
namespace app\admin\controller;
use app\admin\model\AdPosition;

class Ad extends Base{
    protected $ad;
    protected $adPosition;
    protected $position_list;
    protected $uplodify;
    protected function _initialize()
    {
       parent::_initialize();
        $this->ad = new \app\admin\model\Ad();
        $this->adPosition = new AdPosition();
        $this->position_list =$this->adPosition->where(['is_show'=>0])->field('id,name')->select();
        $this->uplodify = new Uploadify();
    }
    /*广告位列表*/
    public function positionList()
    {
        return view('ad/position_list');
    }
    /*广告位列表*/
    public function ajaxPositionList()
    {
        $keywords =$this->request->param('keywords');
        $is_show =$this->request->param('is_show');
        $where=1;
        if($is_show){
            $where .= " and (is_show=$is_show)";
        }
        if($keywords){
            $where .= " and (name like '%$keywords%')";
        }
        list($list,$page) = $this->adPosition->getPageList($where,$this->page,'*',['created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('ad/ajax_position_list', [
            'lists'=>$list,
            'page'=>$ajax_page,
        ]);
    }
    /*添加、修改广告位*/
    public function positionHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->adPosition->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->adPosition->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Ad/positionList')):$this->error($rs['msg']);
        }
        $info = $this->adPosition->get($id);
        return view('ad/_position',[
            'info'=>$info,
            'id'=>$id,
        ]);
    }
    /*删除节点*/
    public function delPosition()
    {
        return $this->adPosition->del(input('param.ids'));
    }
    /*广告列表*/
    public function adList()
    {
        return view('ad/ad_list',['p_list'=>$this->position_list]);
    }
    /*广告位列表*/
    public function ajaxAdList()
    {
        $keywords =$this->request->param('keywords');
        $position =$this->request->param('ad_position_id');
        $status =$this->request->param('status');
        $where=1;
        if($position){
            $where .= " AND (ad_position_id=$position)";
        }
        if($status ==1){//正常显示的
            $where .= " AND ( (is_show=0) AND (start_time >= now()) AND (end_time >= now()) )";
        }elseif($status == 2){//不显示的
            $where .= " AND ( (is_show=1) OR (start_time < now()) OR (end_time < now()) )";
        }
        if($keywords){
            $where .= " AND (ad_name like '%$keywords%')";
        }
        list($list,$page) = $this->ad->getPageList($where,$this->page,'*',['sort'=>'asc','created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('ad/ajax_ad_list', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'p_list'=>$this->position_list
        ]);
    }
    /*添加、修改广告*/
    public function adHandle()
    {
        $id = input('param.id');
        if(request()->isPost()){
            $dt = input("param.");

            /*裁剪图片*/
            if($dt['ad_code']){
                $dt['ad_code'] = $this->uplodify->imgHandle($dt['ad_code'],'ad','1','900','900');
            }
            if($dt['start_time'] > $dt['end_time']){
                $this->error('结束时间不能小于开始时间');
            }
            if($id >0){//修改
                $rs = $this->ad->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->ad->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Ad/adList')):$this->error($rs['msg']);
        }
        $info = $this->ad->get($id);
        return view('ad/_ad',[
            'info'=>$info,
            'id'=>$id,
            'p_list'=>$this->position_list
        ]);
    }
    /*删除节点*/
    public function delAd()
    {
        return $this->ad->del(input('param.ids'));
    }
}