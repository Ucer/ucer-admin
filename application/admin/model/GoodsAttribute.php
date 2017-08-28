<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/20
 * Time: 21:36
 */
namespace app\admin\model;
use think\Db;
use think\Model;

class GoodsAttribute extends Model{
    /**
     * 列表页
     *@param $where 条件
     *@param $per  每页有几条
     *@param $field 要查找的字段
     *@param $order_cloumn 排序字段
     *@param $order_type   升序或降序
     */
    public function getPageList($where=1,$per=20,$field="*",$order_type='asc',$order='created_at'){
        $group_list = $this->where($where)->field($field)->order([$order=>$order_type,'sort'=>'asc'])->paginate($per,false,[
            'page'=>input('param.page'),
            'list_rows'=>$per
        ]);
        if($group_list){
            foreach($group_list as $k=>$v){
                $v['goods_type_id'] = Db::name('goods_type')->where(['id'=>$v['goods_type_id']])->value('type_name');
                $v['items'] = getItems($v['id'],0,'、');
            }
        }
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
        $data['value'] = str_replace('_', '', $data['value']); // 替换特殊字符
        $data['value'] = str_replace('@', '', $data['value']); // 替换特殊字符
        $data['value'] = str_replace(' ', '', $data['value']); // 替换特殊字符
        $data['value'] = trim($data['value']);
        $rules =  [
            ['name','unique:goods_spec','属性名称已经存在'],
        ];
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    adminLog("修改商品属性[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品属性修改成功'];
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
                    adminLog("添加商品属性[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品属性添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 动态获取商品属性输入框根据不同的数据返回不同的输入框类型
     *@param $goods_id 商品id 新增时为0
     *@param $goods_type_id 商品类型id
     */
    public function getAttrValue($goods_id=0,$goods_type_id =0)
    {
        $attr_list = $this->where(['goods_type_id'=>$goods_type_id])->order(['sort'=>'asc'])->column('*');//选中商品类型下的所有属性列表
        $str = '';
        if($attr_list){//有数据，往下去
            foreach($attr_list as $k=>$v){
                $curAttrVal = $this->getGoodsAttrVal($goods_id,$v['id']);

                //如果没有数据，手动造一个数组促使循环
               if(count($curAttrVal) < 1) $curAttrVal[] = ['id'=>'','goods_id'=>'','goods_attribute_id'=>'','value'=>'','attr_price'=>'','reated_at'=>''];

                foreach($curAttrVal as $kk=>$vv){//循环出每一个属性详细
                    $str .= "<tr class='attr_{$v['id']}'>";
                    $addDelAttr = ''; // 加减符号

                    //单选或复选属性
                    if($v['attribute_type'] == 1 || ($v['attribute_type'] ==2 )){
                        if($kk = 0)//没有详细属性时
                            $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                        else
                            $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";

                    }
                    $str .= "<td>$addDelAttr {$v['name']}</td> <td>";

                    // 手工录入-----input框
                    if($v['in_type'] == 0)
                    {
                        $str .= " <div class='col-sm-4'>";
                        $str .= "<input type='text' class='form-control' value='{$vv['value']}' name='attr_{$v['id']}[]' />";
                        $str .= "</div>";
                    }

                    // 从下面的列表中选择（一行代表一个可选值）----select下拉框
                    if($v['in_type'] == 1)
                    {
                        $str .= " <div class='col-sm-2'>";
                        $str .= "<select class='form-control m-b' name='attr_{$v['id']}[]'>";
                        $tmp_option_val = explode(PHP_EOL, $v['value']);
                        foreach($tmp_option_val as $k2=>$v2){
                            // 编辑的时候 有选中值
                            $v2 = preg_replace("/\s/","",$v2);
                            if($vv['value'] == $v2)
                                $str .= "<option selected='selected'  value='{$v2}'>{$v2}</option>";
                            else
                                $str .= "<option value='{$v2}'>{$v2}</option>";
                        }
                        $str .= "</select>";
                        $str .= "</div>";
                    }
                    // 多行文本框 -----textarea
                    if($v['in_type'] == 2){
                        $str .= " <div class='col-sm-4'>";
                        $str .= "<textarea cols='50' rows='4' name='attr_{$v['id']}[]'>{$vv['value']}</textarea>";
                        $str .= "</div>";
                    }
                    $str .= "</td></tr>";
                }

            }
        }
        return $str;
    }
    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     *@param $goods_id 商品id
     *@param $goods_attribute_id 商品属性id
     */
    protected function getGoodsAttrVal($goods_id=0,$goods_attribute_id=0){
        return Db::name('goods_attr_info')->where(['goods_id'=>$goods_id,'goods_attribute_id'=>$goods_attribute_id])->select();
    }
    /**
     * 删除商品属性
     *@param $ids
     */
    public function del($ids)
    {
        //检查商品规格
        $sun_spec = Db::name('goods_attr_info')->where(['goods_attribute_id'=>$ids])->find();
        if($sun_spec){
            return ['code'=>0,'data'=>'','msg'=>'有商品在使用该属性，不允许删除'];
        }
        $rs = $this->where(['id'=>['in',$ids]])->delete();
        return $rs ? ['code'=>1,'data'=>'','msg'=>'删除成功']:['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
    }
}