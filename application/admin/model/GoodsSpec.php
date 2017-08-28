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

class GoodsSpec extends Model{
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
        $data['item'] = array_unique(explode(PHP_EOL,$data['item']));
        $rules =  [
            ['name','unique:goods_spec','规格名称已经存在'],
        ];
        if($id >0){
            $data['update_at'] = date('Y-m-d H:i:s',time());
            try{
                $result = $this->allowField(true)->validate($rules)->save($data,['id'=>$id]);
                if(false ===$result){
                    return ['code'=>0,'data'=>'','msg'=>$this->getError()];
                }else{
                    $this->nextUpdate($id,$data['item']);
                    adminLog("修改商品规格[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品规格修改成功'];
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
                    $this->nextUpdate($this->id,$data['item']);
                    adminLog("添加商品规格[".$data['name']."]",req('url'));
                    return ['code'=>1,'data'=>'','msg'=>'商品规格添加成功'];
                }
            }catch(\PDOException $e){
                return ['code'=>0,'data'=>'','msg'=>$e->getMessage()];
            }
        }
    }
    /**
     * 后置方法，用于插入|更新规格项
     *@param $id 规格表的id
     *@param $items array 要插入的规格项
     */
    public function nextUpdate($id=1,$items=[]){
        foreach($items as $k=>$v){
            $v = str_replace('@','',$v);//替换特殊字符串
            $v = str_replace('_','',$v);//替换特殊字符串

            $v = trimall($v);
            if(empty($v)){
                unset($items[$k]);
            }else{
                $items[$k] = $v;
            }
        }
        $db_list = Db::name('goods_spec_item')->where(['goods_spec_id'=>$id])->column('id,item');
        //两边比较 两次
        $data_list = [];
        /* 提交过来的 跟数据库中比较 不存在 插入*/
            foreach($items as $k=>$v){
                if(!in_array($v,$db_list))
                    $data_list[] = ['goods_spec_id'=>$id,'item'=>$v];
            }
        //批量插入数据
        $data_list && Db::name('goods_spec_item')->insertAll($data_list);

        /* 数据库中的 跟提交过来的比较 不存在删除*/
            foreach($db_list as $k=>$v){
                if(!in_array($v,$items)){
                    //TODO删除规格项价格表

                    //删除规格项
                    Db::name('goods_spec_item')->delete($k);
                }
            }
    }
    /**
     * 删除商品类型规格
     *@param $ids
     */
    public function del($ids)
    {
        Db::startTrans();
        try{
            Db::name('goods_spec_item')->where(['goods_spec_id'=>$ids])->delete();
            $info = $this->get($ids);
            $this->where(['id'=>['in',$ids]])->delete();
            Db::commit();
            adminLog("删除商品规格[".$info['name']."]",req('url'));
            return ['code'=>1,'data'=>'','msg'=>'删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code'=>0,'data'=>'','msg'=>'删除失败，请重试'];
        }
    }
    /**
     * 获取规格的信息
     *@param $goods_id 商品id
     *@param $spec_arr 规格笛卡尔积  每组规格id=>下面的规格项数组 goods_spec_id=>[]
     */
    public function GetSpecInput($goos_id,$spec_arr){
/*
$spec_arr:-----------------------
Array
(
[2] => Array//goods_spec.id
    (
        [0] => 8//goods_spec_item.id
        [1] => 9
    )
)
$spec_arr_sort:-----------------------
Array
(
    [2] => 2 //goods_spec.id=>count(goods_spec_item)
)
$spec_arr2:-----------------------
Array
(
[2] => Array//goods_spec.id
    (
        [0] => 8//goods_spec_item.id
        [1] => 9
    )
)
$clo_name:----------------
Array
(
    [0] => 2
)
$spec_arr2:笛卡尔积-------------
Array
(
    [0] => Array
        (
            [0] => 10 //windows7.id goods_spec_item.id
            [1] => 7 //酷睿i5-4210.id goods_spec_item.id
        )

    [1] => Array
        (
            [0] => 10 //windows7.id goods_spec_item.id
            [1] => 8 //酷睿i7-4210.id goods_spec_item.id
        )

)
$spec_item:规格项--------------
Array
(
    [4] => Array
        (
            [id] => 4
            [goods_spec_id] => 1
            [item] => 24寸
        )
)
$spec_price:规格项价格表
[8_9] => Array
    (
        [key_ids] => 8_9
        [goods_id] => 3
        [key_name] => 处理器:6210
    )

[10] => Array
    (
        [key_ids] => 10
        [goods_id] => 3
        [key_name] => 操作系统:windows7 windows8
    )

$item_key_name------------------
Array
(
    [10] => 操作系统:windows7 goods_spec_item.id
    [8] => cpu:酷睿i5-6210
)
*
*/
        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        if($spec_arr) {
            foreach ($spec_arr as $k => $v) {
                $spec_arr_sort[$k] = count($v);
            }
            asort($spec_arr_sort);//每组规格有几个规格项 规格id=>下面的规格项;根据规格id排序
            foreach ($spec_arr_sort as $k => $v) {
                $spec_arr2[$k] = $spec_arr[$k];//排序后的spec_arr
            }
            $clo_name = array_keys($spec_arr2);//返回键
            $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积
            $spec = $this->column('id,name');//规格表
            $spec_item = Db::name('goods_spec_item')->column('id,goods_spec_id,item');//规格项
            $spec_price = Db::name('goods_spec_price')->where(['goods_id' => $goos_id])->column('key_ids,goods_id,key_name,price,store_num,code');//规格项价格表
            // 显示第一行的数据
            foreach ($clo_name as $k => $v) {
                $str .= " <td><b>{$spec[$v]}</b></td>";
            }
            $str .= "<td><b>价格</b></td>
               <td><b>库存</b></td>
             </tr>";
            // 显示第二行开始
            foreach ($spec_arr2 as$v) {
                $str .= "<tr>";
                $item_key_name = array();
                foreach ($v as $kk => $vv) {
                    $str .= "<td>{$spec_item[$vv]['item']}</td>";
                    $item_key_name[$vv] = $spec[$spec_item[$vv]['goods_spec_id']] . ':' . $spec_item[$vv]['item'];
                }
              ksort($item_key_name);
             $item_key = implode('_', array_keys($item_key_name));
             $item_name = implode(' ', $item_key_name);
            if(!isset($spec_price[$item_key])){
                $spec_price[$item_key]['price'] = 0; // 价格默认为0
                $spec_price[$item_key]['store_num'] = 0; //库存默认为0
            }else{
                $spec_price[$item_key]['price'] ? false : $spec_price[$item_key]['price'] = 0; // 价格默认为0
                $spec_price[$item_key]['store_num'] ? false : $spec_price[$item_key]['store_num'] = 0; //库存默认为0
            }

            $str .="<td><input name='item[$item_key][price]' value='{$spec_price[$item_key]["price"]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_num]' value='{$spec_price[$item_key]["store_num"]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
                $str .= "</tr>";
            }
        }else{
            $str .="<td colspan='12' >还没有添加任何规格项噢</td></tr>";
        }
        $str .= "</table>";
        return $str;
    }
}