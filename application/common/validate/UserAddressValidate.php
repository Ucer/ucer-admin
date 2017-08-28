<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/30
 * Time: 11:57
 */
namespace app\common\validate;
use think\Validate;

class UserAddressValidate extends Validate{
    protected $rule = [
//        '__token__'=>'require|token',
        'consignee' => 'require|unique:user_address',
        'province_id'=>'require',
        'city_id'=>'gt:0',
        'area_id'=>'gt:0',
        'address'=>'require|length:3,100',
        'mobile'=>['regex'=>'/(^1[3|4|5|7|8][0-9]{9}$)/'],

    ];
    protected $message = [
//        '__token__'=>'为防止重复操作，请刷新后重试',
        'consignee.require'=>'收货人不能为空',
        'consignee.unique'=>'收货地址已经存在',
        'province_id.require'=>'请选择省份',
        'city_id.gt'=>'请选择城市',
        'area_id.gt'=>'请选择区域',
        'address.require'=>'详细地址不能为空',
        'address.length'=>'详细地址只能在3-300个字符之间',
        'mobile.regex'=>'请填写正确的手机号码',
    ];
}