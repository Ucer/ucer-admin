<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/8
 * Time: 16:19
 */
namespace app\admin\validate;
use think\Validate;

class AdminRoleValidate extends Validate{
    protected $rule = [
        ['role_name','unique:admin_role','角色已存在']

];
}