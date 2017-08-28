<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/11
 * Time: 14:24
 */
namespace app\admin\controller;
use app\admin\model\GoodsAttribute;
use app\admin\model\GoodsBrand;
use app\admin\model\GoodsCategory;
use app\admin\model\GoodsImages;
use app\admin\model\GoodsSpec;
use app\admin\model\GoodsType;
use think\Db;

class Goods extends Base{
    protected $goodsCategory;
    protected $goodsType;
    protected $goodsAttribute;
    protected $goodsSpec;
    protected $goodsBrand;
    protected $goods;
    protected $goodsImages;
    protected $uploadify;
    /*商品分类列表*/
    protected function _initialize()
    {
        parent::_initialize();
        $this->goodsCategory = new GoodsCategory();
        $this->goodsType = new GoodsType() ;
        $this->goodsAttribute = new GoodsAttribute();
        $this->goodsSpec = new GoodsSpec();
        $this->goodsBrand = new GoodsBrand();
        $this->goods = new \app\admin\model\Goods();
        $this->goodsImages = new GoodsImages();
        $this->uploadify = new Uploadify();
    }
    /*商品分类列表*/
    public function categoryList()
    {
        return view('goods/category_list',[
            'lists'=>$this->goodsCategory->goodsCateList(),
        ]);
    }
    /*添加|修改商品分类*/
    public function categoryHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            $dt['pid'] = ($dt['pid1'] < 1) ?$dt['pid']:$dt['pid1'];
            if($id >0){//修改
                $pinfo = $this->goodsCategory->get($dt['pid']);
                if($pinfo){
                    if(in_array($id,explode('_',$pinfo['pid_path']))){
                        $this->error('上级节点不能是自己或自己的后代');
                    }
                }
                $rs = $this->goodsCategory->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsCategory->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Goods/categoryList')):$this->error($rs['msg']);
        }
        $info = $this->goodsCategory->get($id);
        $clevel = $this->goodsCategory->findCurCate($id);
        return view('goods/_category',[
            'info'=>$info,
             'id'=>$id,
             'p_list'=>$this->goodsCategory->where(['pid'=>0])->column('id,cat_name,pid'),
             'c_level'=> $clevel
        ]);
    }
    /*获取多级联动的商品分类*/
    public function ajaxGetNext()
    {
        $list = $this->goodsCategory->where(['pid'=>input('param.pid')])->column('id,cat_name,pid');
        $htm = '';
        if($list){
            foreach($list as $k=>$v){
                $htm .= "<option value='{$k}'>{$v['cat_name']}</option>";
            }
        }
        exit($htm);
    }
    /*删除商品分类*/
    public function delCategory()
    {
        return $this->goodsCategory->del(input('param.ids'));
    }
    /*商品类型列表*/
    public function goodsTypeList()
    {
        return view('goods/type_list');
    }
    /*ajax商品类型列表*/
    public function ajaxTypeList()
    {
        $keywords = input('param.keywords');
        $where =1;
        if($keywords){
            $where = "type_name like '%$keywords%'";
        }
        list($list,$page) = $this->goodsType->getPageList($where,$this->page,'*','desc');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('goods/ajax_type_list',[
            'lists'=>$list,
            'page'=>$ajax_page
        ]);
    }
    /*添加|修改商品类型*/
    public function goodsTypeHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->goodsType->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsType->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Goods/goodsTypeList')):$this->error($rs['msg']);
        }
        $info = $this->goodsType->get($id);
        return view('goods/_type',[
            'info'=>$info,
            'id'=>$id,
        ]);
    }
    /*删除商品类型*/
    public function delGoodsType()
    {
        return $this->goodsType->del(input('param.ids'));
    }
    /*商品规格列表*/
    public function goodsSpecList()
    {
        return view('goods/spec_list',[
            'type_list'=>$this->goodsType->column('id,type_name')
        ]);
    }
    /*ajax商品规格列表*/
    public function ajaxSpecList()
    {
        $keywords = input('param.goods_type_id');
        $where =1;
        if($keywords){
            $where = "goods_type_id = $keywords";
        }
        list($list,$page) = $this->goodsSpec->getPageList($where,$this->page,'*','asc','goods_type_id');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('goods/ajax_spec_list',[
            'lists'=>$list,
            'page'=>$ajax_page
        ]);
    }
    /*添加|修改商品类型*/
    public function goodsSpecHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->goodsSpec->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsSpec->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Goods/goodsSpecList')):$this->error($rs['msg']);
        }
        $info = $this->goodsSpec->get($id);
        return view('goods/_spec',[
            'info'=>$info,
            'id'=>$id,
            'type_list'=>$this->goodsType->column('id,type_name')
        ]);
    }
    /*删除商品规格*/
    public function delGoodsSpec()
    {
        return $this->goodsSpec->del(input('param.ids'));
    }
    /*商品规格列表*/
    public function attrList()
    {
        return view('goods/attr_list',[
            'type_list'=>$this->goodsType->column('id,type_name')
        ]);
    }
    /*ajax商品规格列表*/
    public function ajaxAttrList()
    {
        $keywords = input('param.goods_type_id');
        $where =1;
        if($keywords){
            $where = "goods_type_id = $keywords";
        }
        list($list,$page) = $this->goodsAttribute->getPageList($where,$this->page,'*','asc','goods_type_id');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('goods/ajax_attr_list',[
            'lists'=>$list,
            'page'=>$ajax_page
        ]);

    }
    /*添加|修改商品属性*/
    public function goodsAttrHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            if($id >0){//修改
                $rs = $this->goodsAttribute->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsAttribute->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Goods/attrList')):$this->error($rs['msg']);
        }
        $info = $this->goodsAttribute->get($id);
        return view('goods/_attr',[
            'info'=>$info,
            'id'=>$id,
            'type_list'=>$this->goodsType->column('id,type_name')
        ]);
    }
    /*删除商品属性*/
    public function delGoodsAttr()
    {
        return $this->goodsAttribute->del(input('param.ids'));
    }
    /*商品品牌列表*/
    public function brandList()
    {

        return view('goods/brand_list',[
            'cat_list'=>$this->goodsCategory->goodsCateList()
        ]);
    }
    /*ajax商品规格列表*/
    public function ajaxBrandList()
    {
        $keywords = input('param.goods_category_id');
        $where =1;
        if($keywords){
            $where = "goods_category_id = $keywords";
        }
        list($list,$page) = $this->goodsBrand->getPageList($where,$this->page,'*','asc','goods_category_id');
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        return view('goods/ajax_brand_list',[
            'lists'=>$list,
            'page'=>$ajax_page
        ]);
    }
    /*添加|修改商品品牌*/
    public function goodsBrandHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            /*裁剪图片*/
            if($dt['logo']){
                $dt['logo'] = $this->uploadify->imgHandle($dt['logo'],'goods','1','300','100');
            }

            if($id >0){//修改
                $rs = $this->goodsBrand->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goodsBrand->handle($dt);
            }
            $rs['code'] ==1 ? $this->redirect(url('Goods/brandList')):$this->error($rs['msg']);
        }
        $info = $this->goodsBrand->get($id);
        return view('goods/_brand',[
            'info'=>$info,
            'id'=>$id,
            'p_list'=>$this->goodsCategory->goodsCateList(),
        ]);
    }
    /*删除商品品牌*/
    public function delGoodsBrand()
    {
        return $this->goodsBrand->del(input('param.ids'));
    }
    /*商品列表*/
    public function goodsList()
    {
        return view('goods/goods_list',[
            'cat_list'=>$this->goodsCategory->goodsCateList(),
            'brand_list'=>$this->goodsBrand->column('id,name'),
        ]);
    }
    /*ajax商品列表*/
    public function ajaxGoodsList()
    {
        $goods_category_id = input('param.goods_category_id');
        $goods_brand_id = input('param.goods_brand_id');
        $is_on_sale = input('param.is_on_sale');
        $new_or_hot = input('param.new_or_hot');
        $keywords = input('param.keywords');
        $where =1;
        if($goods_category_id){//分类
            $where .= " and (goods_category_id = $goods_category_id)";
        }
        if($goods_brand_id){//品牌
            $where .= " and (goods_brand_id = $goods_brand_id)";
        }
        if($is_on_sale ){//上架|下架
            $where .= " and (is_on_sale = $is_on_sale)";
        }
        if($new_or_hot){//新品或推荐
            if($new_or_hot ==1)
                $where .= " and (is_new=0)";
            else
                $where .= " and (is_recommend=1)";
        }
        if($keywords){
            $where .= " and ( (goods_sn like '%$keywords%') OR (goods_name like '%$keywords%') )";
        }
        list($list,$page) = $this->goods->getPageList($where,$this->page,'*',['sort'=>'asc','created_at'=>'desc']);
        $ajax_page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a data-p=$1 href='javascript:void($1);'>$2</a>",$page);
        if($list){
            foreach($list as $k=>$v){
                $v['goods_category_id'] = getTableColumn('goods_category','cat_name',['id'=>$v['goods_category_id']]);
                $v['goods_brand_id'] = getTableColumn('goods_brand','name',['id'=>$v['goods_brand_id']]);
            }
        }
        return view('goods/ajax_goods_list',[
            'lists'=>$list,
            'page'=>$ajax_page,
        ]);
    }
    /*添加|修改商品*/
    public function goodsHandle()
    {
        $id = (int) input('param.id');
        if(request()->isPost()){
            $dt = input("param.");
            $dt['goods_content'] = imgHandle($_POST['goods_content'],'goods');
//            $dt['goods_content'] = htmlspecialchars($goods_content);
            if(!isset($dt['goods_images'])) $dt['goods_images']=[];
            /*裁剪图片*/
            if($dt['original_img']){
                $dt['original_img'] = $this->uploadify->imgHandle($dt['original_img'],'goods','1','400','400');
            }
            if($id >0){//修改
                $rs = $this->goods->handle($dt,$id);
            }else{//添加
                unset($dt['id']);
                $rs = $this->goods->handle($dt);
            }
            $rs['code'] ==1 ? $this->success('操作成功',url('Goods/goodsList')):$this->error($rs['msg']);
        }
        $info = $this->goods->get($id);
        $type_list = $this->goodsType->column('id,id,type_name');//商品属性--所属商品类型

        return view('goods/_goods',[
            'info'=>$info,
            'id'=>$id,
            'p_list'=>$this->goodsCategory->goodsCateList(),//所属商品分类
            'brand' => $brand = $this->goodsBrand->column('id,name'),//商品通用信息-品牌
            'type_list'=>$this->sortArr($type_list),
            'images'=>$this->goodsImages->where(['goods_id'=>$id])->column('id,url'),//商品相册
        ]);
    }
    /*异步获取商品属性的值列表*/
    public function ajaxAttrValue()
    {
        $str = $this->goodsAttribute->getAttrValue(input('param.gid'),input('param.goods_type_id'));
        exit($str);
    }
    /*删除商品*/ //TODO 删除商品
    public function delGoods()
    {

    }
    /* 动态获取商品规格选择框 根据不同的数据返回不同的选择框 */
    public function ajaxSpecValue()
    {
        $goods_id = (int) input('param.goods_id');
        $spec_type_id = input('param.spec_type_id');
        $spec_list = $this->goodsSpec->where(['goods_type_id'=>$spec_type_id])->order(['sort'=>'desc'])->select();
        $item_ids = [];
        if($spec_list){
            foreach($spec_list as $k=>$v){
                $spec_list[$k]['spec_item'] = Db::name('goods_spec_item')->where(['goods_spec_id'=>$v['id']])->column('id,item');
            }
            //规格价格表中以key_ids分组，把key_ids用 '_'拼接起来
            $item_ids = Db::name('goods_spec_price')->where(['goods_id'=>$goods_id])->value("GROUP_CONCAT(key_ids SEPARATOR '_') AS item_ids");
            if($item_ids) $item_ids = explode('_',$item_ids);
        }
        $spec_images = Db::name('goods_spec_images')->where(['goods_id'=>$goods_id])->column('goods_spec_item_id,url');

        return view('goods/ajax_spec_select',[
            'lists'=>$spec_list,
            'spec_images'=>$spec_images,
            'item_ids'=>$item_ids?:[''],
            'goods_id'=>$goods_id
        ]);
    }
    /*动态获取商品规格详细 根据不同的数据返回输入框*/
    public function ajaxGetSpecInput()
    {
        $goods_id = (int) input('param.goods_id');
        $spec_arr = input('param.spec_arr/a');
        $str = $this->goodsSpec->GetSpecInput($goods_id,$spec_arr);
        exit($str);

    }
    /*数组排序方法*/
    private function sortArr($arr){
        if($arr){
            $narr = [];
            foreach($arr as $k=>$v){
                $narr[] = $v = getFirstCharter($v).'--'.$v;
                $arr[$k] = $v;
            }
//            array_multisort($arr,SORT_STRING,$narr);
        }
        return $arr;
    }
}