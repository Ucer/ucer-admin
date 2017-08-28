<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/19
 * Time: 16:42
 */
namespace app\admin\controller;
use app\admin\model\CouponList;
use think\Config;
use think\Db;
use think\Exception;

class Coupon extends Base{
    protected $coupon;
    protected $couponList;
    protected $coupon_type;
    protected function _initialize(){
        parent::_initialize();
        $this->coupon = new \app\admin\model\Coupon();
        $this->couponList = new  CouponList();
        $this->coupon_type = Config::get('coupon_type');
    }
    /*优惠券列表*/
    public function couponList()
    {
        return view('coupon/coupon_list',['coupon_type' =>$this->coupon_type ]);
    }
    /*优惠券列表*/
    public function ajaxCouponList()
    {
        $keywords = $this->request->param('keywords');
        $type = $this->request->param('type');
        $where=1;
        if($type >= '0'){
            $where .= " and (`type` = $type)";
        }
        if($keywords){
            $where .= " and (`name` like '%$keywords%')";
        }
        list($list,$page) = $this->coupon->getPageList($where,$this->page,'*',['created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        $list = $this->coupon->handleCouponList($list);
        return view('coupon/ajax_coupon', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'coupon_type' =>$this->coupon_type
        ]);
    }

    /*添加、修改优惠券*/
    public function couponHandle()
    {
        $id = (int) $this->request->param('id');

        if(request()->isPost()){
            $dt = $this->request->param();

            //时间处理
            $dt['send_start_time'] = $this->timeHandle($dt['send_start_time']);
            $dt['send_end_time'] = $this->timeHandle($dt['send_end_time']);
            $dt['use_start_time'] = $this->timeHandle($dt['use_start_time']);
            $dt['use_end_time'] = $this->timeHandle($dt['use_end_time']);
            if($dt['send_end_time'] < time() || ($dt['use_end_time'] < time()) ){
                $this->error('结束时间不能小于当前时间');
            }
            if( ($dt['send_start_time']-$dt['send_end_time'])>0 || ($dt['use_start_time']-$dt['use_end_time'])>0 ){
                $this->error('结束时间必须大于开始时间');
            }

            if($id >0){//修改
                $rs = $this->coupon->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->coupon->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Coupon/couponList')):$this->error($rs['msg']);
        }
        $info = $this->coupon->get($id);
        return view('coupon/_coupon',[
            'info'=>$info,
            'id'=>$id,
            'coupon_type' =>$this->coupon_type,//所属文章分类
        ]);
    }
    /*删除优惠券*/
    public function delCoupon()
    {
        return $this->coupon->del(input('param.ids'));
    }
    /*线下发放优惠券*/
    public function sendCoupon()
    {
        $id =  (int) $this->request->param('id');
        if($id ==0) $this->error('请别瞎搞',url('/'));

        $info = $this->coupon->get($id);
        if(request()->isPost()){
            $dt = $this->request->param();
            $info['send_num'] += $dt['send_num'];
            //安全防范
            list($stus,$msg) = $this->coupon->handleCouponList($info,1);
            if($stus !=1){
                $this->error($msg);
            }
            if($dt['send_num'] <=0) $this->error('发放数量不能小于1');
            $num = $dt['send_num'];
            Db::startTrans();
            try{
                for($i=0;$i<$num;$i++){
                    do{
                        $code =getRandStr1(8,0,1);
                        $check_exist = $this->couponList->where(['code'=>$code])->count();
                    }while($check_exist);//唯一红包码
                    $this->couponList->allowField(true)->insert(['coupon_id'=>$id,'code'=>$code,'send_time'=>time()]);
                }
                $this->coupon->where(['id'=>$id])->setInc('send_num',$num);
                Db::commit();
                adminLog("发放'".$num."'张'".$info['name']."'",req('url'));
                $this->success('优惠券发放成功');
            }catch (Exception $e){
                echo $e;
                $this->error('出错了，请稍后再试');
                Db::rollback();
            }

        }

        return view('coupon/send_coupon',[
            'id'=>$id,
            'info'=>$info,
        ]);
    }

    /*按用户发放优惠券*/  //TODO 未完成
    public function sendUserCoupon()
    {
        $id = (int) $this->request->param('id');
        return view('coupon/send_user_coupon',[
            'id'=>$id,
        ]);
    }
    
    /*查看优惠券详细*/
    public function showCoupon()
    {
        $id =  (int) $this->request->param('id');
        $keywords =  $this->request->param('keywords');
        $type =  $this->request->param('type');
        if($id ==0) $this->error('请别瞎搞',url('/'));

        $where ="coupon_id =$id";

        if($type ==1){//已经使用
            $where .= " and (`order_id` > 0)";
        }
        if($type ==2){//未使用
            $where .= " and (`order_id` = 0)";
        }
        if($keywords){
            $where .= " and (`code` like '%$keywords%')";
        }

        list($list,$page) = $this->couponList->getPageList($where,$this->page,'*',['send_time'=>'desc'],['type'=>$type,'keywords'=>$keywords]);
        return view('coupon/show_coupon',[
            'id'=>$id,
            'lists'=>$list,
            'page'=>$page,
            'keywords'=>$keywords,
            'type'=>$type,
            'name'=> $this->coupon->where(['id'=>$id])->value('name'),
        ]);
    }
    /*删除优惠券*/
    public function delCouponList()
    {
        return $this->couponList->del(input('param.ids'));
    }
    /*处理时间，如果为空默认为现在*/
    private function timeHandle($time){
        if(!trimall($time)){
            return time();
        }
        return strtotime($time);
    }
}