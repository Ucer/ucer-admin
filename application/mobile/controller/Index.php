<?php
namespace app\mobile\controller;

use think\Config;
use think\Cookie;
use think\Db;

class Index extends Base
{
    /*首页*/
    public function index()
    {
        return view('index/index');
    }
}
