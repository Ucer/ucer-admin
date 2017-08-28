<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/8
 * Time: 16:19
 */
namespace app\admin\validate;
use think\Validate;

class AdminGroupValidate extends Validate{
    protected $rule = [
        ['name','unique:admin_group','分组已经存在'],
    ];
}