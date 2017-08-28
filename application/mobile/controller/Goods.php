<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/21
 * Time: 15:55
 */
namespace app\mobile\controller;
use app\mobile\model\GoodsAttribute;
use app\mobile\model\GoodsBrand;
use app\mobile\model\GoodsCategory;
use app\mobile\model\GoodsImages;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Db;
use think\exception\HttpException;
use think\View;

class Goods extends Base{
    protected $goods;
    protected $goodsImages;
    protected $goodsCategory;
    protected $goodsBrand;
    protected $goodsAttribute;
    protected $goodsLogic;
    protected $mpage;
    protected function _initialize(){
        parent::_initialize();
        $this->goods = new \app\mobile\model\Goods();
        $this->goodsImages = new GoodsImages();
        $this->goodsCategory = new GoodsCategory();
        $this->goodsBrand = new GoodsBrand();
        $this->goodsAttribute = new GoodsAttribute();
        $this->goodsLogic = new \app\common\logic\Goods();
        $this->mpage = Config::get('mobile_page')?:5;
    }
    /*分类列表导航*/
    public function categoryList()
    {
        return view('goods/category_list');
    }
    /*商品详情*/
    public function goodsInfo()
    {
        $goods_id = $this->request->param('id');
        $map['goods_id']=$goods_id;

        $imgList = $this->goodsImages->where($map)->field('url')->select();//商品相册
        $goods = $this->goods->where(['id'=>$goods_id,'is_on_sale'=>1])->find();
        if(empty($goods)){
            throw new HttpException(404,'商品不存在或已经下架');
        }
        if($goods['goods_brand_id']){
            $brand = $this->goodsBrand->get($goods['goods_brand_id']);
            $goods['brand_name'] = $brand['name'];
        }
        $goods_attribute = $this->goodsAttribute->column('id,name');//商品属性表
        $goods_attr_list = Db::name('goods_attr_info')->where($map)->select();//详细属性表
        $filter_spec =$this->goodsLogic->getGoodsSpec($goods_id);//规格参数

        $spec_goods_price = Db::name('goods_spec_price')->where($map)->column('key_ids,price,store_num'); // 规格 对应 价格 库存表
        $this->goods->where(['id'=>$goods_id])->setInc('click_count'); // 统计点击数+1
        //TODO 评论、已出售数量

       $goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;//折扣率
        $goods['flash_sale']=['price'=>0];
        //促销商品
        $prom_goods =$flash_goods =[];
        $now_time = date('Y-m-d H:i:s',time());
        if($goods['prom_type'] === 3){//促销
            $prom_goods = Db::name('goods_prom')->where(['id'=>$goods['goods_prom_id'],'start_time'=>['lt',$now_time],'end_time'=>['gt',$now_time]])->find();//促销商品
        }
        if($goods['prom_type'] == 1){//限时秒杀
            $flash_goods = Db::name('flash_sale')->where(['id'=>$goods['goods_prom_id'],'start_time'=>['lt',$now_time],'end_time'=>['gt',$now_time]])->find();//限时秒杀商品
            $goods['flash_sale'] = $flash_goods;
        }
        return view('goods/goods_info',[
            'imgList'=>$imgList,
            'goods'=>$goods,
            'goods_attribute'=>$goods_attribute,
            'goods_attr_list'=>$goods_attr_list,
            'filter_spec'=>$filter_spec,
            'spec_goods_price'=>json_encode($spec_goods_price),
            'prom_goods'=>$prom_goods,
            'flash_goods'=>$flash_goods,
        ]);
    }
    /*收藏商品*/
    public function collectGoods()
    {
        $goods_id = $this->request->param("goods_id");
        $result = $this->goodsLogic->collectGoods(Cookie::get('user_id'),$goods_id);
        exit(json_encode($result));
    }
    /*商品搜索、筛选页面*/
    public function search()
    {
        $filter_param = []; // 筛选数组
        $id = $this->request->param('id')?:0; // 当前分类id
        $brand_id = $this->request->param('goods_brand_id')?:0;

        $sort = $this->request->param('sort')?:'id'; // 排序
        $sort_asc = $this->request->param('sort_asc')?:'asc'; // 排序

        $price = $this->request->param('price')?:''; // 价钱
        $keywords = trimall($this->request->param('keywords'))?:'';
        $start_price = trimall($this->request->param('start_price'))?:'0'; // 输入框价钱
        $end_price = trimall($this->request->param('end_price'))?:'0'; // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        //筛选条件
        $filter_param['id'] = $id;
        $brand_id  && ($filter_param['goods_brand_id'] = $brand_id);
        $price  && ($filter_param['price'] = $price);
        $keywords  && ($filter_param['keywords'] = $keywords);

        $map['is_on_sale'] =1;
        if($keywords) $map['goods_name'] =['like',"%$keywords%"];
        $filter_goods_id = Db::name('goods')->where($map)->column("id");

        if($brand_id || $price){// 存在品牌或者价格筛选时
            $goods_id_1 = $this->goodsLogic->getGoodsIdByBrandOePrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
//        $filter_menu  = $this->goodsLogic->getFilterMenu($filter_param,'search'); // 获取显示的筛选菜单
        $filter_price = $this->goodsLogic->getFilterPrice($filter_goods_id,$filter_param,'search'); // 筛选的价格期间
        $filter_brand = $this->goodsLogic->getFilterBrand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的筛选品牌

        $count = count($filter_goods_id);
        $per = $this->request->param('p')?:'1';
        $goods_list =$goods_images= [];
        if($count > 0 ){
            $goods_list = Db::name('goods')->where(['id'=>['in',$filter_goods_id]])->order([$sort=>$sort_asc])->paginate($this->mpage,$count,[
                'page'=>$per
            ]);

            $filter_goods_id2 = getArrcolumn($goods_list, 'id');
            if($filter_goods_id2){
                $goods_images = Db::name('goods_images')->where(['goods_id'=>['in',$filter_goods_id2]])->select();
            }
        }
        $goods_category = Db::name('goods_category')->where(['is_show'=>1])->cache(true)->column('id,cat_name,pid,level'); // 键值分类数组
        $this->assign('goods_list',$goods_list);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
//        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_brand',$filter_brand);// 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('sort_ascnow', $sort_asc);
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');//排序规则
        $this->assign('sort', $sort);
        $this->assign('keywords', $keywords);
        if(input('param.is_ajax')> 0){
            return $this->fetch('goods/ajax_goods_list');
        }else{
            return $this->fetch('goods/search');
        }
    }
    /*商品列表*/
    public function goodsList()
    {
        $filter_param = array(); // 筛选数组
        $id = $this->request->param('id')?:0; // 当前分类id
        $brand_id = $this->request->param('goods_brand_id')?:0;

        $keywords = trimall($this->request->param('keywords'))?:'';

        $spec = $this->request->param('spec',0); // 规格
        $attr = $this->request->param('attr',''); // 属性

        $sort = $this->request->param('sort')?:'id'; // 排序
        $sort_asc = $this->request->param('sort_asc')?:'asc'; // 排序

        $price = $this->request->param('price')?:''; // 价钱
        $start_price = trimall($this->request->param('start_price'))?:'0'; // 输入框价钱
        $end_price = trimall($this->request->param('end_price'))?:'0'; // 输入框价钱

        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        //筛选条件
        $filter_param['id'] = $id;
        $brand_id  && ($filter_param['goods_brand_id'] = $brand_id);
        $price  && ($filter_param['price'] = $price);
        $attr  && ($filter_param['attr'] = $attr);
        $spec  && ($filter_param['spec'] = $spec);

        // 分类菜单
        $goodsCate = Db::name('goods_category')->where(['is_show'=>0,'id'=>$id])->find();// 当前分类
        $cateArr = $this->goodsLogic->getGoodsCate($goodsCate);//查找当前分类的上级、上上级分类

        $cat_id_arr = getCatGrandson ($id);//找到当前分类的下级、下下级
        $map['is_on_sale'] =1;
        $map['goods_category_id'] =['in',$cat_id_arr];
        $filter_goods_id = Db::name('goods')->where($map)->cache(true)->column("id");//当前选中分类下面的商品id都找出来

        if($brand_id || $price){// 存在品牌或者价格筛选时
            $goods_id_1 = $this->goodsLogic->getGoodsIdByBrandOePrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec){// 规格
            $goods_id_2 = $this->goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
        }
        if($attr){// 属性
            $goods_id_3 =  $this->goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个帅选条件的结果 的交集
        }

        //        $filter_menu  = $this->goodsLogic->getFilterMenu($filter_param,'search'); // 获取显示的筛选菜单
        $filter_price = $this->goodsLogic->getFilterPrice($filter_goods_id,$filter_param,'search'); // 筛选的价格期间
        $filter_brand = $this->goodsLogic->getFilterBrand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的筛选品牌
        $filter_spec  = $this->goodsLogic->getFilterSpec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选规格
        $filter_attr  = $this->goodsLogic->getFilterAttr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选属性

        $count = count($filter_goods_id);
        $per = $this->request->param('p')?:'1';
        $goods_list =$goods_images= [];
        if($count > 0 ){
            $goods_list = Db::name('goods')->where(['id'=>['in',$filter_goods_id]])->order([$sort=>$sort_asc])->paginate($this->mpage,$count,[
                'page'=>$per
            ]);

            $filter_goods_id2 = getArrcolumn($goods_list, 'id');
            if($filter_goods_id2){
                $goods_images = Db::name('goods_images')->where(['goods_id'=>['in',$filter_goods_id2]])->select();
            }
        }
        $goods_category = Db::name('goods_category')->where(['is_show'=>1])->cache(true)->column('id,cat_name,pid,level'); // 键值分类数组

        $this->assign('goods_list',$goods_list);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
//        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_spec',$filter_spec);  // 帅选规格
        $this->assign('filter_attr',$filter_attr);  // 帅选属性
        $this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('cat_id',$id);
        $this->assign('sort_ascnow', $sort_asc);
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');

        $this->assign('sort', $sort);
        $this->assign('keywords', $keywords);
        if(input('param.is_ajax')> 0){
            return $this->fetch('goods/ajax_goods_list');
        }else{
            return $this->fetch('goods/goods_list');
        }



    }

}