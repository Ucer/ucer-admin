<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/4/4
 * Time: 14:48
 */
namespace app\home\controller;
use think\Controller;
use think\Db;

class Api extends Controller{
    /*获取地区的子地区*/
    public function getRegion()
    {
        $pid =$this->request->param("pid");
        $selected = $this->request->param('selected',0);
        $list = Db::name('region')->where(['parent_id'=>$pid])->select();
        $html = "";
        if($list){
            foreach($list as $h){
                if($h['id'] == $selected){
                    $html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
                }
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        exit($html);
    }
    /*区域下面的乡镇*/
    public function getTwon(){
        $pid =$this->request->param("pid");
        $list = Db::name('region')->where(['parent_id'=>$pid])->select();
        $html = '';
        if($list){
            foreach($list as $h){
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        if(empty($html)){
            echo '0';
        }else{
            echo $html;
        }
    }
}