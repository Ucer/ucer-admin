<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/19
 * Time: 16:57
 */
namespace app\admin\model;
use think\Db;
use think\image\Exception;
use think\Model;

class Coupon extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order=['sort'=>'asc']){
        $group_list = $this->where($where)->field($field)->order($order)->paginate($per,false,[
            'page'=>input('param.page'),
            'list_rows'=>$per
        ]);
        return [$group_list,$group_list->render()];
    }
    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['name'] = trimall($data['name']);

        if($id >0){
            try{
                $result = $this->allowField(true)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改优惠券[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'优惠券修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("添加优惠券[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'优惠券添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 处理代金券类型
     *@param $pagesize 每一页的总数
     *@param $data 数据
     *@param $type 0为多维数组，1为判断一维数组、直接返回状态
     */
    public function handleCouponList($data,$type=0){
        if(empty($data)) return $data;

        if($type){
            $can_use_num = $data['create_num'] - $data['send_num'];//剩余优惠券数量---大于0可发放、否则提示:该代金券已经发放完了
            $send_time = $data['send_end_time'] - time();//大于0可发放、否则提示:该代金券已经停止发放
            $use_time = $data['use_end_time'] - time();//大于0可发放、否则提示:该代金券已经过期，不可再发放

            $status = 1;//默认为1，正常:可发放
            $msg = '正常';
            if($data['type'] ==0){ //如果是面额模板-没有固定的发放期限
                if( ($can_use_num < 0) && ($data['create_num'] > 0)){
                    $status = 4;//已经发放完、数量不足
                    $msg = '已发放完';
                }
            }else{
                if($use_time  <= 0){ //已经过期,不可发放
                    $status = 2;
                    $msg = '已过期';
                }else{//未过期
                    if($send_time <= 0 ){//已经停止发放
                        $status = 3;
                        $msg = '不在发放时间内';
                    }else{
                        if( ($can_use_num < 0) && ($data['create_num'] > 0)){
                            $status = 4;//已经发放完、数量不足
                            $msg = '已发放完';
                        }
                    }
                }
            }
            return [$status,$msg];
        }

       $data = $this->handArray($data);
        return $data;
    }
    /**
     * 处理代金券类型
     */
    protected function handArray($data){
        foreach($data as $k=>$v){
            $can_use_num = $v['create_num'] - $v['send_num'];//剩余优惠券数量---大于0可发放、否则提示:该代金券已经发放完了
            $send_time = $v['send_end_time'] - time();//大于0可发放、否则提示:该代金券已经停止发放
            $use_time = $v['use_end_time'] - time();//大于0可发放、否则提示:该代金券已经过期，不可再发放

            $v['status'] = 1;//默认为1，正常:可发放
            $v['msg'] = '正常';
            if($v['type'] ==0) { //如果是面额模板-没有固定的发放期限
                if( ($can_use_num <= 0) && ($v['create_num'] >0) ){
                    $v['status'] = 4;//已经发放完、数量不足
                    $v['msg'] = '已发放完';
                }
            }else{
                if($use_time  <= 0){ //已经过期,不可发放
                    $v['status'] = 2;
                    $v['msg'] = '已过期';
                }else{//未过期
                    if($send_time <= 0 ){//已经停止发放
                        $v['status'] = 3;
                        $v['msg'] = '不在发放时间内';
                    }else{
                        if( ($can_use_num <= 0) && ($v['create_num'] >0) ){
                            $v['status'] = 4;//已经发放完、数量不足
                            $v['msg'] = '已发放完';
                        }
                    }
                }
            }

        }
        return $data;
    }
    /**
     * 删除
     *@param $ids
     */
    public function del($ids)
    {
        //检查是否可以删除
       $this->where(['id'=>$ids])->delete();
        Db::startTrans();
        try{
            $rs = $this->where(['id'=>$ids])->delete();
            Db::name('coupon_list')->where(['coupon_id'=>$ids])->delete();
            Db::commit();
        }catch (Exception $e){
            Db::rollback();
        }
        return['code'=>1,'data'=>'','msg'=>'删除成功'];
    }
}