<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/26
 * Time: 14:06
 */
namespace app\admin\model;
use app\admin\controller\Uploadify;
use think\Db;
use think\Model;

class Goods extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order=['sort'=>'asc']){
        $goods_list = $this->where($where)->field($field)->order($order)->paginate($per,false,[
            'page'=>input('param.page'),
            'list_rows'=>$per
        ]);
        return [$goods_list,$goods_list->render()];
    }

    /**
     * 添加|修改
     *@param $data数据
     *@param $id >0表示修改、否则表示添加
     */
    public function handle($data,$id=0)
    {
        $data['goods_name'] = trimall($data['goods_name']);
        $rules =  [
            ['goods_name','unique:goods','商品已经存在'],
        ];

        if($id >0){//TODO // 修改商品后购物车的商品价格也修改一下
            $old_type_id = $this->where(['id'=>$id])->value('goods_type_id');//旧的属性id
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    $this->afterSave($id,$data['goods_images']);
                    $this->afterSaveAttr($id,$data,$old_type_id);
                    adminLog("修改商品[".$data['goods_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品修改成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }else{
            $data['created_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    $this->afterSave($this->id,$data['goods_images']);
                    $this->afterSaveAttr($this->id,$data);
                    adminLog("添加商品[".$data['goods_name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     * @param  $img 商品相册
     */
    protected function afterSave($goods_id=0,$img){
        //生成商品货号
        $goods_sn = 'PC'.str_pad($goods_id,7,'0',STR_PAD_LEFT);
        $this->where(['id'=>$goods_id,'goods_sn'=>''])->update(['goods_sn'=>$goods_sn]);
        //重新生成商品相册
        $nprefix = '/uploads/goods/thumb/photo_'.$goods_id;//要上传到哪里
        $all = str_replace('\\','/',ROOT_PATH.'public');//硬盘上文件目录
        foreach($img as $k=>$v){
            $new =$nprefix.'/'.basename($v);
            if(!is_dir($all.$nprefix)) mkdir($all.$nprefix,0700,true);
            $img[$k] = $new;
            rename($all.$v,$all.$new);//文件移动
        }
//        if(count($img) >0){
            $has_imgs =  Db::name('goods_images')->where(['goods_id'=>$goods_id])->column('id,url');
            //删除图片
            foreach($has_imgs as $k=>$v){
                if(!in_array($v,$img)){
                    Db::name('goods_images')->delete($k);
                    file_exists($all.$v) && unlink($all.$v);
                }
            }
            $now = date('Y-m-d H:i:s',time());
            //添加图片
            foreach($img as $k=>$v){
//                if(!$v) continue;
                if(!in_array($v,$has_imgs)){
                   Db::name('goods_images')->insert(['goods_id'=>$goods_id,'url'=>$v,'created_at'=>$now]);
                }
            }
    }
    /**
     * 给指定商品添加属性 或修改属性 更新到 详细属性表
     *@param $goods_id 商品的id
     *@param $data post过来的表单数据
     */
    protected function afterSaveAttr($goods_id,$data)
    {
        $GoodsAttrList =  Db::name('goods_attr_info')->where(['goods_id'=>$goods_id])->select();

        // 数据库中的的属性  以 goods_attribute_id _ 和值的 组合为键名
        $old_goods_attr = array();
        foreach($GoodsAttrList as $k => $v)
        {
            $old_goods_attr[$v['goods_attribute_id'].'_'.$v['value']] = $v;
        }
        // post 提交的属性  以 attr_id _ 和值的 组合为键名
        foreach($data as $k=>$v){
            $attr_id = str_replace('attr_','',$k);
            if(!strstr($k, 'attr_') || strstr($k, 'attr_price')) continue;
            foreach($v as $kk=>$vv){
                $vv = str_replace('_', '', $vv); // 替换特殊字符
                $vv = str_replace('@', '', $vv); // 替换特殊字符
                $vv = trim($vv);
                if(empty($vv)) continue;
                $tmp_key = $attr_id."_".$vv;

                if(!array_key_exists($tmp_key , $old_goods_attr)) { // 这个属性 数据库中不存在 说明要做删除操作
                    Db::name('goods_attr_info')->insert(['goods_id'=>$goods_id,'goods_attribute_id'=>$attr_id,'value'=>$vv]);
                }
                unset($old_goods_attr[$tmp_key]);
            }
        }
        foreach($old_goods_attr as $k => $v) {
            Db::name('goods_attr_info')->where(['goods_attribute_id'=>$v['goods_attribute_id']])->delete();
        }
        //商品规格和规格图片入库
        Db::name('goods_spec_price')->where(['goods_id'=>$goods_id])->delete();//删除与该商品有关的价格项
        isset($data['item'])?false:$data['item']='';
        if($data['item']) {
            //批量添加数据
            foreach ($data['item'] as $k => $v) {
                $adlist[] = [
                    'price' => trimall($v['price'])?:'0.00',
                     'store_num'=> trimall($v['store_num'])?:'0.00',
                     'key_ids' => $k,
                     'goods_id' => $goods_id,
                      'key_name'=>$v['key_name'],
                ];
                //TODO  修改商品后购物车的商品价格也修改一下
            }
            if($adlist) Db::name('goods_spec_price')->insertAll($adlist);
        }
        //商品规格图片入库
        if($data){
            $adlist2 = [];
            Db::name('goods_spec_images')->where(['goods_id'=>$goods_id])->delete();//删除与该商品有关的价格项
            //批量添加数据
            foreach($data as $k=>$v){
                if(!strstr($k, 'spec_img_')) continue;
                $attr_id = str_replace('spec_img_','',$k);
                if(!$v) continue;
                /*裁剪图片*/
                $uploadify = new Uploadify();
                if($v){
                    $v = $uploadify->imgHandle($v,'goods'.DS.'spec','1','400','400');
                }
                $adlist2[] = [
                    'goods_id' =>$goods_id,
                    'goods_spec_item_id' => $attr_id,
                    'url' => $v,
                ];
            }
            if($adlist2) Db::name('goods_spec_images')->insertAll($adlist2);
        }
        //刷新商品库存
        refreshStore($goods_id);
    }
}