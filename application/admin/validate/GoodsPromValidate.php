<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/21
 * Time: 9:59
 */
namespace app\admin\validate;
use think\Validate;

class GoodsPromValidate extends Validate{
    protected $rule = [
        'name' => 'require|unique:goods_prom',
        'start_time'=>'require',
        'end_time'=>'require',
    ];
    protected $message = [
        'name.require'=>'活动名称必须填写',
        'name.unique'=>'活动名称已经存在',
        'start_time.require'=>'开始时间不能为空',
        'end_time.require'=>'结束时间不能为空',
    ];
}