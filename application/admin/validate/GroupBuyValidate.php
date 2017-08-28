<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/30
 * Time: 11:57
 */
namespace app\admin\validate;
use think\Validate;

class GroupBuyValidate extends Validate{
    protected $rule = [
        '__token__'=>'require|token',
        'title' => 'require|unique:flash_sale',
        'desc'=>'require',
        'goods_id'=>'gt:0',
        'start_time'=>'require',
        'end_time'=>'require',
        'price'=>'egt:5',
        'goods_num'=>'gt:0',
    ];
    protected $message = [
        '__token__'=>'为防止重复操作，请刷新后重试',
        'title.require'=>'团购标题必须填写',
        'title.unique'=>'团购已经存在',
        'desc'=>'描述必须填写',
        'goods_id.gt'=>'请选择团购商品',
        'start_time.require'=>'开始时间不能为空',
        'end_time.require'=>'结束时间不能为空',
        'price.egt'=>'团购商品价格不能少于5元',
        'goods_num'=>'团购商品数量不能少于1个',
    ];
}