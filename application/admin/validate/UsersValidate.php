<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/3/21
 * Time: 9:59
 */
namespace app\admin\validate;
use think\Validate;

class UsersValidate extends Validate{
    protected $rule = [
        'nickname' => 'require',
        'mobile' => 'unique:users',
        'email' => 'require|email|unique:users',
    ];
    protected $message = [
        'name.require' => '昵称不能为空',
        'mobile.unique' => '手机号已存在',
        'email.require' => '邮箱不能为空',
        'email.email' => '邮箱格式错误',
        'email.unique' => '邮箱已存在',
    ];
}