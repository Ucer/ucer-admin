<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/1
 * Time: 14:24
 */
namespace app\admin\validate;
use think\Validate;

class AdminUserValidate extends Validate{
protected $rule = [
    ['user_name','unique:admin_user','用户已存在'],
];
}