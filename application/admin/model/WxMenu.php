<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/2/6
 * Time: 16:34
 */
namespace app\admin\model;
use think\Model;

class WxMenu extends Model{
    /**
     * 根据条件获取数据
     *@param $where
     */
    public function getByWhere($where='1')
    {
        $list =  $this->where($where)->order(['sort'=>'asc'])->select();
        if(empty($list)){
            return $list;
        }
        return convertArrayKey($list,'id');
    }
}