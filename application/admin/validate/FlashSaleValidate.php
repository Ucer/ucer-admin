<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/21
 * Time: 9:59
 */
namespace app\admin\validate;
use think\Validate;

class FlashSaleValidate extends Validate{
    protected $rule = [
        'title' => 'require|unique:flash_sale',
        'desc'=>'require',
        'goods_id'=>'gt:0',
        'start_time'=>'require',
        'end_time'=>'require',
        'price'=>'egt:5',
        'goods_num'=>'gt:0',
        'buy_limit'=>'gt:0'
    ];
    protected $message = [
        'title.require'=>'活动标题必须填写',
        'title.unique'=>'活动已经存在',
        'desc'=>'活动描述必须填写',
        'goods_id.gt'=>'请选择活动商品',
        'start_time.require'=>'开始时间不能为空',
        'end_time.require'=>'结束时间不能为空',
        'price.egt'=>'活动商品价格不能少于5元',
        'goods_num'=>'活动商品数量不能少于1个',
        'buy_limit'=>'每人限购数量不能少于1个',
    ];
}