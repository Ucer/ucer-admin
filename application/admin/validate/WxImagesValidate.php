<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/8
 * Time: 16:19
 */
namespace app\admin\validate;
use think\Validate;

class WxImagesValidate extends Validate{
    protected $rule = [
    ['keyword','unique:wx_images','关键词已存在']

];
}