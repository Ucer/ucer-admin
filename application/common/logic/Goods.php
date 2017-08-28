<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/24
 * Time: 13:03
 */
namespace app\common\logic;
use think\Cache;
use think\Db;
use think\Exception;
use think\Model;
use think\Url;

class Goods extends Model{
    /**
     * 获取商品规格
     *@param $goods_id 商品id
     */
    public function getGoodsSpec($goods_id=0)
    {
        $keys = Db::name('goods_spec_price')->where(['goods_id'=>$goods_id])->column("id,GROUP_CONCAT(`key_ids` SEPARATOR '_') ");//规格价格表
        $filter_spec = array();
        if($keys){
            $specImage = Db::name('goodsSpecImages')->where(['goods_id'=>$goods_id])->column('id,url');//规格对应的图片表
            $keys = str_replace('_',',',$keys);
            if($keys) $keys = implode(',',$keys);
            $filter_spec2 = Db::table('pc_goods_spec_item')->alias('b')->join('pc_goods_spec a','a.id=b.goods_spec_id')
                ->where(['b.id'=>['in',$keys]])
                ->order('b.id')
                ->field('a.*,b.item,b.id as item_id')
                ->select();
            if($filter_spec2){
                foreach ($filter_spec2 as $key => $val) {
                    $filter_spec[$val['name']][] = array(
                        'item_id' => $val['item_id'],
                        'item' => $val['item'],
                        'src' => isset($specImage[$val['id']])?$specImage[$val['id']]:'',
                    );
            }
            }
        }
        return $filter_spec;
    }
    /**
     * 商品收藏
     *@param $user_id 用户id
     *@param $goods_id 商品id
     */
    public function collectGoods($user_id,$goods_id)
    {
        if(!is_numeric($user_id) || $user_id <1){
            return ['code'=>-1,'msg'=>'请先登录','url'=>'','data'=>''];
        }
        //查看是否已收藏过
        $count = Db::name('goods_collect')->where(['users_id'=>$user_id,'goods_id'=>$goods_id])->count();
        if($count >0){
            return ['code'=>0,'msg'=>'您已经收藏过该商品了','url'=>'','data'=>''];
        }
        $rs = Db::name('goods_collect')->insert(['users_id'=>$user_id,'goods_id'=>$goods_id,'add_time'=>time()]);
        if($rs)  return ['code'=>1,'msg'=>'收藏成功，请前往个人中心查看','url'=>'','data'=>''];
        return ['code'=>0,'msg'=>'系统出错了','url'=>'','data'=>''];

    }
    /**
     * 根据条件获取商品id
     * @param  $brand_id 帅选品牌id
     * @param  $price 帅选价格
     * @return array|mixed
     */
    public function getGoodsIdByBrandOePrice($brand_id, $price)
    {
        if (empty($brand_id) && empty($price)) return array();
        $where="is_on_sale = 1";
        if($brand_id){
            $brand_id_arr = explode('_', $brand_id);
            $where .= " AND goods_brand_id IN(" . implode(',', $brand_id_arr) . ")";
        }
        if ($price){// 价格查询
            $price = explode('-', $price);
            $sprice = $price[0];
            $eprice = isset($price[1])?$price[1]:0;
            if($sprice) $where .= " AND shop_price >=$sprice";
            if($eprice) $where .= " AND shop_price <=$eprice";
        }
        return Db::name('goods')->where($where)->column('id');
    }
    /**
     * @return array|\type
     * 根据规格 查找 商品id
     * @param $spec 规格
     */
    public function getGoodsIdBySpec($spec)
    {
        if(empty($spec)) return array();
        $spec_group = explode('@', $spec);
        $where = " 1 ";
        foreach ($spec_group as $k => $v) {
            $spec_group2 = explode('_', $v);
            array_shift($spec_group2);//移除goods_spec_id只剩下goods_spec_item_id
            $like = array();
            foreach($spec_group2 as $k2 => $v2) {
                $like[] = " key2  LIKE '%\_$v2\_%' ";
            }
            $where .= " AND (" . implode('OR', $like) . ") ";
        }
        $sql = "SELECT * FROM(
                 SELECT *,CONCAT('_',`key_ids`,'_') AS key2 FROM pc_goods_spec_price as a
              ) b  WHERE $where";//1 AND ( key2 LIKE '%\_17\_%' ) //所有的key_ids前后加上_作为key2;SELECT *,CONCAT('_',`key_ids`,'_') AS key2 FROM pc_goods_spec_price as a
        $result = Db::query($sql);
        $arr = getArrColumn($result, 'goods_id');  // 只获取商品id 那一列
        return ($arr ? $arr : array_unique($arr));
    }
    /**
     * @param $attr 属性
     * @return array|mixed
     * 根据属性 查找 商品id
     * 59_直板_翻盖
     * 80_BT4.0_BT4.1
     */
    public function getGoodsIdByAttr($attr)
    {
        if (empty($attr)) return array();
        $attr_group = explode('@', $attr);
        $attr_id = $attr_value = array();
        foreach ($attr_group as $k => $v) {
            $attr_group2 = explode('_', $v);
            $attr_id[] = array_shift($attr_group2);//只剩下attr_name
            $attr_value = array_merge($attr_value, $attr_group2);
        }
        $c = count($attr_id) - 1;
        if($c >0){
            $arr =  Db::name('goods_attr_info')->where(['goods_attribute_id'=>['in',$attr_id],'value'=>['in',$attr_value]])->having(' COUNT(goods_is)')->column('goods_id');
        }else{
            $arr = Db::name('goods_attr_info')->where(['goods_attribute_id'=>['in',$attr_id],'value'=>['in',$attr_value]])->column('goods_id');
        }
        return ($arr ? $arr : array_unique($arr));//返回商品id
    }
    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选品牌
     */
    public function getFilterBrand($goods_id_arr, $filter_param, $action, $mode = 0)
    {
//        if (!empty($filter_param['goods_brand_id'])) return array();
        if(empty($goods_id_arr)) return [];

        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $where = "id IN (select goods_brand_id FROM pc_goods WHERE goods_brand_id > 0 AND id in($goods_id_str))";
        $list_brand = Db::name('goods_brand')->where($where)->limit('30')->select();//goods_brand品牌表
        if($list_brand){
            foreach ($list_brand as $k => $v){
                // 帅选参数
                $filter_param['goods_brand_id'] = $v['id'];
                $list_brand[$k]['href'] = Url::build("Goods/$action", $filter_param, '');
            }
        }

        if ($mode == 1) return $list_brand;
        return array('status' => 1, 'msg' => '', 'data' => $list_brand);

    }

    /**
     * * 帅选的价格期间
     * @param $goods_id_arr 帅选的分类id
     * @param $filter_param
     * @param $action
     * @param int $c 分几段 默认分5 段
     * @return array
     */
    public function getFilterPrice($goods_id_arr, $filter_param, $action, $c = 5)
    {
//        if(!empty($filter_param['price'])) return array();
        if(empty($goods_id_arr)) return [];
        $priceList = Db::name('goods')->where(['id'=>['in',$goods_id_arr]])->column('shop_price','id');
        rsort($priceList);
        $max_price = (int)$priceList[0];
        $psize = ceil($max_price / $c); // 进一法取整
        $parr = array();
        for($i = 0; $i < $c; $i++){
            $start = $i * $psize;
            $end = $start + $psize;

            // 如果没有这个价格范围的商品则不列出来
            $in = false;
            foreach ($priceList as $k => $v) {
                if ($v > $start && $v < $end)
                    $in = true;
            }
            if ($in == false)
                continue;

            $filter_param['price'] = "{$start}-{$end}";
            if ($i == 0)
                $parr[] = array('value' => "{$end}元以下", 'href' => Url::build("mobile/Goods/$action", $filter_param, ''));
            elseif ($i == ($c - 1))
                $parr[] = array('value' => "{$end}元以上", 'href' => Url::build("mobile/Goods/$action", $filter_param, ''));
            else
                $parr[] = array('value' => "{$start}-{$end}元", 'href' => Url::build("mobile/Goods/$action", $filter_param, ''));
        }
        return $parr;
    }
    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode  0  返回数组形式  1 直接返回result
     * @return array 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选规格
     */
    public function getFilterSpec($goods_id_arr, $filter_param, $action, $mode = 0)
    {
        if(empty($goods_id_arr)) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'data' => array());
        }
        $spec_key = Db::name('goods_spec_price')->field("GROUP_CONCAT(key_ids SEPARATOR '_') as key_ids")->where(['goods_id'=>['in',$goods_id_arr]])->select();
        if(empty($spec_key)) return array('status' => 1, 'msg' => '', 'data' => array());
        $spec_key = explode('_', $spec_key[0]['key_ids']);
        $spec_key = array_unique($spec_key);
        $spec_key = array_filter($spec_key);

        if (empty($spec_key)) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'data' => array());
        }

        $spec = Db::name('goods_spec')->where(array('is_search'=>1))->column('name','id');//检索的规格项
        if(empty($spec)) return array('status' => 1, 'msg' => '', 'data' => array());
        $spec_item = Db::name('goods_spec_item')->where(array('goods_spec_id'=>array('in',array_keys($spec))))->column('id,goods_spec_id,item','id');

        $list_spec = array();
        $old_spec = isset($filter_param['spec'])?$filter_param['spec']:'';
        foreach ($spec_key as $k => $v) {  //$v是规格项详细id即goods_spec_item表的id
            if(strpos($old_spec, $spec_item[$v]['goods_spec_id'] . '_') === 0 || strpos($old_spec, '@' . $spec_item[$v]['goods_spec_id'] . '_')) continue;

            $list_spec[$spec_item[$v]['goods_spec_id']]['goods_spec_id'] = $spec_item[$v]['goods_spec_id'];
            $list_spec[$spec_item[$v]['goods_spec_id']]['name'] = $spec[$spec_item[$v]['goods_spec_id']];

            // 筛选参数
            if (!empty($old_spec)){
                $filter_param['spec'] = $old_spec . '@' . $spec_item[$v]['goods_spec_id'] . '_' . $v;
            }else{
                $filter_param['spec'] = $spec_item[$v]['goods_spec_id'] . '_' . $v;
            }
            //key是item的父规格id
            $list_spec[$spec_item[$v]['goods_spec_id']]['item'][] = array('key' => $spec_item[$v]['goods_spec_id'], 'val' => $v, 'item' => $spec_item[$v]['item'], 'href' => Url::build("mobile/Goods/$action", $filter_param, ''));
        }


        if ($mode == 1) return $list_spec;
        return array('status' => 1, 'msg' => '', 'result' => $list_spec);
    }
    /**
     * @param array $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array
     * 获取商品列表页帅选属性
     */
    public function getFilterAttr($goods_id_arr = array(), $filter_param, $action, $mode = 0)
    {
        if(empty($goods_id_arr)){
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'data' => array());
        }
        $goods_attr = Db::name('goods_attr_info')->where(['goods_id'=>['in',$goods_id_arr],'value'=>['neq','']])->select();
        $goods_attribute = Db::name('goods_attribute')->column('id,name,search_type','id');
        if (empty($goods_attr)) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'data' => array());
        }
        $list_attr = $attr_value_arr = array();
        if (!isset($filter_param['attr'])) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'data' => array());
        }
        $old_attr = $filter_param['attr'];


        foreach ($goods_attr as $k => $v) {
            // 存在的帅选不再显示
            if (strpos($old_attr, $v['goods_attribute_id'] . '_') === 0 || strpos($old_attr, '@' . $v['goods_attribute_id'] . '_')) continue;
            if ($goods_attribute[$v['goods_attribute_id']]['search_type'] == 0) continue;

            $v['value'] = trim($v['value']);
            // 如果同一个属性id 的属性值存储过了 就不再存贮
            if(isset($attr_value_arr[$v['goods_attribute_id']])){
                if (in_array($v['goods_attribute_id'] . '_' . $v['value'], $attr_value_arr[$v['goods_attribute_id']])) continue;
            }
            $attr_value_arr[$v['goods_attribute_id']][] = $v['goods_attribute_id'] . '_' . $v['value'];

            $list_attr[$v['goods_attribute_id']]['attr_id'] = $v['goods_attribute_id'];
            $list_attr[$v['goods_attribute_id']]['attr_name'] = $goods_attribute[$v['goods_attribute_id']]['name'];

            // 筛选参数
            if (!empty($old_attr)){
                $filter_param['attr'] = $old_attr . '@' . $v['goods_attribute_id'] . '_' . $v['value'];
            } else{
                $filter_param['attr'] = $v['goods_attribute_id'] . '_' . $v['value'];
            }
        }

        if ($mode == 1) return $list_attr;
        return array('status' => 1, 'msg' => '', 'data' => $list_attr);

    }

    /**
     * 传入当前分类 如果当前是 2级 找一级
     * 如果当前是 3级 找2 级 和 一级
     * @param  $goodsCate 分类详细信息
     */
    public function getGoodsCate($goodsCate)
    {
        if (empty($goodsCate)) return array();
        $cateAll = getGoodsCatTree();//所有分类

        if ($goodsCate['level'] == 0) {//顶级
            $cateArr = $cateAll[$goodsCate['id']]['tmenu'];
            $goodsCate['parent_name'] = $goodsCate['cat_name'];
            $goodsCate['select_id'] = 0;
        }elseif($goodsCate['level'] == 1){//1级
            $cateArr = $cateAll[$goodsCate['pid']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$goodsCate['pid']]['cat_name'];//顶级分类名称
            $goodsCate['open_id'] = $goodsCate['id'];//默认展开分类
            $goodsCate['select_id'] = 0;
        }else{
            $parent = Db::name('goods_category')->where(["id"=> $goodsCate['pid']])->order('sort desc')->find();//父类
            $cateArr = $cateAll[$parent['pid']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$parent['pid']]['cat_name'];//顶级分类名称
            $goodsCate['open_id'] = $parent['id'];
            $goodsCate['select_id'] = $goodsCate['id'];//默认选中分类
        }
        return $cateArr;

    }
}