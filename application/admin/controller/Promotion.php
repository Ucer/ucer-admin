<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/23
 * Time: 9:20
 */
namespace app\admin\controller;
use app\admin\model\FlashSale;
use app\admin\model\Goods;
use app\admin\model\GoodsProm;
use app\admin\model\GroupBuy;
use think\Config;
use think\Db;

class Promotion extends Base{
    protected $flashSale;
    protected $goods;
    protected $goodsProm;
    protected $promType;
    protected $groupBuy;
    protected function _initialize()
    {
         parent::_initialize();
        $this->flashSale = new FlashSale();
        $this->goods = new Goods();
        $this->goodsProm = new GoodsProm();
        $this->promType = Config::get('goods_prom_type');
        $this->groupBuy = new GroupBuy();
    }

    /*限时抢购*/
    public function flashSale()
    {
        $goods_list = $this->goods->where(['store_count'=>['gt',0]])->order(['sort'=>'asc','created_at'=>'desc'])->column('id,goods_name,store_count');
        $list =$this->flashSale->order(['sort'=>'asc','created_at'=>'desc'])->select();
        return view('prom/flash_list',[
            'list'=>$list,
            'goods_list'=>$goods_list
        ]);
    }
    /*添加、修改限时抢购*/
    public function flashHandle()
    {
        $id = input('param.id');
        $where = "goods_prom_id=0 OR ( (goods_prom_id=$id) AND (prom_type=1))";
        $goods_list = $this->goods->where(['store_count'=>['gt',0]])->where($where)->order(['sort'=>'asc','created_at'=>'desc'])->column('id,goods_name,store_count');
        if(request()->isPost()){
            $dt = input("param.");

            if(!$dt['start_time']) $this->error('开始时间不能为空');
            if($dt['start_time'] > $dt['end_time']) $this->error('结束时间不能小于开始时间');
            if($dt['goods_num'] > $goods_list[$dt['goods_id']]['store_count']) $this->error('库存已不足');
            if($dt['goods_num'] < $dt['buy_limit']) $this->error('个人限购量不能大于限购商品数量');

            if($id >0){//修改
                $rs = $this->flashSale->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->flashSale->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Promotion/flashSale')):$this->error($rs['msg']);
        }
        $info = $this->flashSale->get($id);
        return view('prom/_flash',[
            'info'=>$info,
            'id'=>$id,
            'goods_list'=>$goods_list
        ]);
    }
    /*删除抢购*/
    public function delFlash()
    {
        return $this->flashSale->del(input('param.ids'));
    }

    /*商品促销活动*/
    public function goodsProm()
    {
        $list =$this->goodsProm->order(['created_at'=>'desc'])->select();
        return view('prom/goods_prom',[
            'list'=>$list,
            'type'=>$this->promType,
        ]);
    }
    /*添加、修改商品促销活动*/
    public function goodsPromHandle()
    {
        $id = input('param.id');
        $where = "goods_prom_id=0 OR ( (goods_prom_id=$id) AND (prom_type=3))";
        $goods_list = $this->goods->where(['store_count'=>['gt',0]])->where($where)->column('id,goods_name,store_count,shop_price');//所有商品
        $level_list =Db::name('user_level')->order(['sort'=>'asc','created_at'=>'desc'])->column('id,name');//所有会员等级
        $where = "type=0  AND (create_num=0 OR (create_num > send_num))" ;
        $coupon = Db::name('coupon')->where($where)->column('id,name');//面额模板类型的--优惠券列表

        if(request()->isPost()){
            $dt = input("param.");

            if(!$dt['start_time']) $this->error('开始时间不能为空');
            if($dt['start_time'] > $dt['end_time']) $this->error('结束时间不能小于开始时间');
            if($id >0){//修改
                $rs = $this->goodsProm->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsProm->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Promotion/goodsProm')):$this->error($rs['msg']);
        }
        $info = $this->goodsProm->get($id);
        if($info){//是编辑的话
            if($info['user_group_id']){
                $info['user_group_ids'] = explode(',',$info['user_group_id']);//将适合会员等级写成数组形式--该活动针对的
            }else{
                $info['user_group_ids'] =array_keys($level_list);//不填写则表示所有等级的会员都适用
            }
            $goods_listt = $this->goods->where(['goods_prom_id'=>$id,'prom_type'=>3])->column('id,goods_name');//参与了该活动的商品
            $ll = array_keys($goods_listt);
            $info['goods_ids'] = $ll;
            $info['goods_id'] = implode(',',$ll);
        }
        return view('prom/_goods_prom',[
            'info'=>$info,
            'id'=>$id,
            'goods_list'=>$goods_list,
            'level_list'=>$level_list,
            'type'=>$this->promType,
            'coupon'=>$coupon,
        ]);
    }
    /*删除促销活动*/ //TODO
    public function delGoodsProm()
    {
//        return $this->goodsProm->del(input('param.ids'));
    }
    /*团购活动列表*/
    public function groupList()
    {
        return view('prom/group_list');
    }
    /*团购活动列表*/
    public function ajaxGroupList()
    {
        $goods_list = $this->goods->column('id,goods_name,store_count');
        $where=1;
        $stime = $this->request->param("start_time");
        $etime = $this->request->param("end_time");
        if($stime){
            $where .= " and end_time >=".strtotime($stime);
        }
        if($etime){
            $where .= " and end_time <=".strtotime($etime);
        }
        list($list,$page) = $this->groupBuy->getPageList($where,$this->page,'*',['created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('prom/ajax_group_list', [
            'lists'=>$list,
            'page'=>$ajax_page,
            'goods_list'=>$goods_list
        ]);
    }
    /*添加、修改团购活动*/
    public function groupHandle()
    {
        $id = (int) $this->request->param('id');
        $where = "goods_prom_id=0 OR ( (goods_prom_id=$id) AND (prom_type=2))";
        $goods_list = $this->goods->where(['store_count'=>['gt',0]])->where($where)->order(['sort'=>'asc','created_at'=>'desc'])->column('id,goods_name,store_count,market_price,store_count,goods_name,is_recommend');
        if(request()->isPost()){
            $dt = $this->request->param();
            $goods_info = $goods_list[$dt['goods_id']];
            $cha_price = $goods_info['market_price'] - $dt['price'];
            $cha_store = $goods_info['store_count'] - $dt['goods_num'];
            if($cha_price <0) $this->error('团购价格不能高于市场价');
            if($cha_store <0) $this->error('商品库存不足');

            $dt['start_time'] = strtotime($dt['start_time']);
            $dt['end_time'] = strtotime($dt['end_time']);
            $dt['rebate'] = round($dt['price']/$goods_info['market_price'],2)*10;
            $dt['goods_name'] =$goods_info['goods_name'];
            $dt['recommended'] =$goods_info['is_recommend'];
            //时间处理
            if($dt['end_time'] < time() ){
                $this->error('结束时间不能小于当前时间');
            }
            if( ($dt['start_time']>$dt['end_time'])){
                $this->error('结束时间必须大于开始时间');
            }

            if($id >0){//修改
                $rs = $this->groupBuy->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->groupBuy->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Promotion/groupList')):$this->error($rs['msg']);
        }
        $info = $this->groupBuy->get($id);
        return view('prom/_group',[
            'info'=>$info,
            'id'=>$id,
            'goods_list'=>$goods_list
        ]);
    }
    /*删除团购*/
    public function delGroupBuy()
    {
        return $this->groupBuy->del(input('param.ids'));
    }

}