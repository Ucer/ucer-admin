<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/8
 * Time: 16:19
 */
namespace app\admin\validate;
use think\Validate;

class AdminAllMenuValidate extends Validate{
    protected $rule = [
        ['name','unique:admin_all_menu','节点已经存在'],
    ];
}