<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/3
 * Time: 15:49
 */
namespace app\common\validate;
use think\Validate;

class FormValidate extends Validate{
    protected $rule = [
        '__token__'=>'require|token',
    ];
    protected $message = [
        '__token__'=>'为防止重复操作，请刷新后重试',
    ];
}