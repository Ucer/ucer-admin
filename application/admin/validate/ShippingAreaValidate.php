<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/5
 * Time: 15:13
 */
namespace app\admin\validate;
use think\Validate;

class ShippingAreaValidate extends Validate{
    protected $rule = [
        'shipping_area_name' => 'require|unique:shipping_area',
    ];
    protected $message = [
        'shipping_area_name.require'=>'区域名称必须填写',
        'shipping_area_name.unique'=>'区域名称已经存在',
    ];
}