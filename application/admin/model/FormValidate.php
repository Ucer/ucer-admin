<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/3
 * Time: 15:51
 */
namespace app\admin\model;
use think\Loader;
use think\Model;

class FormValidate extends Model{
    /**
     * 公用表单令牌验证方法
     */
    public function formValidate($data)
    {
        $validate = Loader::validate('FormValidate');
        if(!$validate->check($data)){
            return ['code'=>0,'data'=>'','msg'=>$validate->getError()];
        }
        return ['code'=>1,'data'=>'','msg'=>'success'];
    }
}