<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/3
 * Time: 8:45
 */
namespace app\mobile\controller;
use app\common\logic\Goods;
use app\mobile\model\GoodsAttribute;
use app\mobile\model\GoodsImages;
use app\mobile\model\GroupBuy;
use think\Config;
use think\Db;

class Activity extends Base{
    protected $groupBuy;
    protected $goodsLogic;
    protected $goods;
    protected $goodsImages;
    protected $goodsAttribute;
    public function _initialize()
    {
        parent::_initialize();
        $this->groupBuy = new GroupBuy();
        $this->goodsLogic = new Goods();
        $this->goods = new \app\mobile\model\Goods();
        $this->goodsImages = new GoodsImages();
        $this->goodsAttribute = new GoodsAttribute();

    }
    /*团购列表*/
    public function groupList()
    {
        $where ="start_time <= ".time()." AND end_time >=".time();
        $list = $this->groupBuy->getPageList($where,Config::get('wap_pagesize'),'*',['created_at'=>'desc']);
        return view('activity/group_list',['list'=>$list]);
    }
    /*团购商品详情*/
    public function groupInfo()
    {
        $id = $this->request->param('id');//团购活动的id
        $group_buy_info = Db::name('group_buy')->where(['id'=>$id,'start_time'=>['lt',time()],'end_time'=>['gt',time()]])->find(); // 找出这个团购活动
        if(empty($group_buy_info)) $this->error('活动已经过期，请刷新');
        $goods = $this->goods->where(['id'=>$group_buy_info['goods_id'],'is_on_sale'=>1])->find();

        $map['goods_id']=$group_buy_info['goods_id'];

        if(empty($goods)) $this->error('商品不存在或已下架');
        $imgList = $this->goodsImages->where($map)->field('url')->select();//商品相册

        $goods_attribute = $this->goodsAttribute->column('id,name');//商品属性表
        $goods_attr_list = Db::name('goods_attr_info')->where($map)->select();//详细属性表
        $filter_spec =$this->goodsLogic->getGoodsSpec($group_buy_info['goods_id']);//规格参数

        $spec_goods_price = Db::name('goods_spec_price')->where($map)->column('key_ids,price,store_num'); // 规格 对应 价格 库存表
        $this->goods->where(['id'=>$group_buy_info['goods_id']])->setInc('click_count'); // 统计点击数+1
        //TODO 评论、已出售数量


        return view("activity/group_info",[
            'group_buy_info'=>$group_buy_info,
            'imgList'=>$imgList,
            'goods'=>$goods,
            'goods_attribute'=>$goods_attribute,
            'goods_attr_list'=>$goods_attr_list,
            'filter_spec'=>$filter_spec,
            'spec_goods_price'=>json_encode($spec_goods_price),
        ]);

    }
}