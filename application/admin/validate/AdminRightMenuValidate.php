<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/8
 * Time: 16:19
 */
namespace app\admin\validate;
use think\Validate;

class AdminRightMenuValidate extends Validate{
    protected $rule = [
        ['name','unique:admin_right_menu','权限菜单已经存在'],
    ];
}