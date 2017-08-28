<?php
use think\Db;
/**
 * 管理员日志写入
 *@param
 *@param
 */
function adminLog($info,$url){
    $add['admin_user_id'] = session('admin_user_id');
    $add['log_ip'] = req('ip');
    $add['log_info'] = $info;
    $add['log_url'] = $url;
    $add['created_at'] =date('Y-m-d H:i:s',time());
    Db::name('admin_log')->insert($add);
    \think\Cache::clear();
}
/**
 * 递归菜单
 *@param $data 菜单列表
 */
function subTree($data,$pid=0,$lev=0){
    static $res = [];
    foreach($data as $k=>$v){
        $v['lev']=$lev;
        if($v['parent_id'] ==$pid){
            $res[] = $v;
            subTree($data,$v['id'],$lev+1);
        }
    }
    return $res;
}
/**
 * 整理菜单树方法
 *@param $data 菜单列表
 */
function prePareMenu($data){
    $parent = [];//父类
    $child =[];//子类
    foreach($data as $k=>$v){
        if($v['parent_id'] ==0){
            $parent[] = $v;
        }else{
            $v['href'] = url($v['control'].'/'.$v['action']);
            $child[] = $v;
        }
    }
    foreach($parent as $k=>$v){
        foreach($child as $kk=>$vv){
            if($vv['parent_id']==$v['id']){
                $parent[$k]['sun'][] = $vv;
            }
        }
    }
    unset($child);
    return $parent;
}
/**
 * 迭代法找家谱树
 *@param $arr 树所在的数组(特别注意，$arr的key值必须为其在数据库表中的主键id)
 *@param $id 要找家谱树的对象的id
 */
function findFamilyTree($arr,$id){
    $tree = [];
    while($id !==0){
        foreach($arr as $k=>$v){
            if($v['id'] == $id){
                $tree[$k] = $v;
                $id = $v['parent_id'];
                break;
            }
        }
    }
    $ids = [];
    if(empty($tree)) return $ids;
    foreach($tree as $k=>$v){
        $ids[] = $k;
    }
    return $ids;
}
/**
 * 递归找子孙树
 *@param $arr 树所在的数组(特别注意，$arr的key值必须为其在数据库表中的主键id)
 *@param $id 要找子孙树的对象的id
 */
function findSunTree($arr,$id=0){
    static $tree = [];
    foreach($arr as $k=>$v){
        if($v['parent_id'] == $id){
            $tree[$k] = $v;
            findSunTree($arr,$k);
        }
    }
    $ids = [];
    if(empty($tree)) return $ids;
    foreach($tree as $k=>$v){
        $ids[] = $k;
    }
    return $ids;
}
/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function formatBytes($size,$delimiter = ''){
    $units = ['B','KB','MB','GB','TB','PB'];
    for($i = 0;$size >= 1024 && $i < 5 ;$i++) $size /=1024;
    return round($size,2) . $delimiter . $units[$i];
}
/**
 * 获取微信菜单类型
 *@param $type
 *@param $is_str 0返回$str,1返回$str1
 */
function getWxType($type=0,$is_str=0){
    $str = '';
    $str1 = '';
    switch($type){
        case 0:
            $str = 'view';
            $str1 = '链接';
            break;
        case 1:
            $str = 'click';
            $str1 = '触发关键字';
            break;
        case 2:
            $str = 'scancode_push';
            $str1 = '扫码';
            break;
        case 3:
            $str = 'scancode_waitmsg';
            $str1 = '扫码（等待信息）';
            break;
        case 4:
            $str = 'pic_sysphoto';
            $str1 = '系统拍照发图';
            break;
        case 5:
            $str = 'pic_photo_or_album';
            $str1 = '拍照或者相册发图';
            break;
        case 6:
            $str = 'pic_weixin';
            $str1 = '微信相册发图';
            break;
        case 7:
            $str = 'location_select';
            $str1 = '地理位置';
            break;
        default:
            $str = 'view';
            $str1 = '链接';
            break;
    }
    return $is_str?$str1:$str;
}
/**
 * 根据id获取规格项
 *@param $spec_id 规格表id
 *@param $ext 用什么来拆分
 *@param $type 0为返回字符串，1为返回数组
 */
function getItems($spec_id,$type=0,$ext=PHP_EOL){
    $return = Db::name('goods_spec_item')->where(['goods_spec_id'=>['eq',$spec_id]])->column('id,item');
    if(!$return){
        return $type?[]:'';
    }
    if($type < 1){
        $return = implode($ext,$return);
    }
    return $return;
}

/**
 * 多个数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function combineDika() {
    $data = func_get_args();
    $data = current($data);
    $result = array();
    $arr1 = array_shift($data);
    foreach($arr1 as $key=>$item)
    {
        $result[] = array($item);
    }

    foreach($data as $key=>$item)
    {
        $result = combineArray($result,$item);
    }
    return $result;
}

/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
 */
function combineArray($arr1,$arr2) {
    $result = array();
    foreach ($arr1 as $item1)
    {
        foreach ($arr2 as $item2)
        {
            $temp = $item1;
            $temp[] = $item2;
            $result[] = $temp;
        }
    }
    return $result;
}
/**
 * 拆分枚举类型配置项
 *@param $str item字段值
 */
function parsItem($str){
    if(empty($str)){
        return [];
    }
    $str = str_replace(['：','：','：'],[':',':',':'],$str);
    $arr = explode("\r\n",$str);
    $value = [];
    foreach($arr as $k=>$v){
        if(strpos($v,':')){
            list($a,$b) = explode(':',$v);
            $value[$a] = $b;
        }else{
            $value = $arr;
        }
    }
    return $value;
}
/**
 * 拆分数组类型配置项
 *@param $str value字段值
 */
function parsItemArr($str){
    if(empty($str)){
        return [];
    }
    return str_replace([',','，','，'],"\r\n",$str);
}


/**
 * 处理富文本编辑内图片
 *@param $str  图片的项目地址
 *@param $mulu  要移动到的目录
 */
 function imgHandle($str,$mulu='images')
{
    preg_match_all('/<img.*?src="(.*?)".*?>/is',$str,$match1);
    $dir = $match1[1];
     $root = ROOT_PATH . 'public';//public目录
     $path = '/uploads/'.$mulu.'/'.date('ym',time()).'/';//移动到新目录
    $root = str_replace('\\','/',$root);
    if($dir){
        foreach($dir as $k=>$v){
            if(!file_exists($root.$v)) continue;
            $npath = $root.$path.basename($v);//新路径
            if(!is_dir($root.$path)) mkdir($root.$path,0700,true);
            rename($root.$v,$npath);//文件移动
            $str = str_replace($v,$path.basename($v),$str);
        }
    }
    return $str;
}


/**
 * 获取系统配置类型或分组
 *@param $id 数组id键
 *@param $type  1:获取类型数组，2获取分组配置
 */
function getConfigA($id=1,$type=1){
    if($type ==1){
        $data = config('system_type_list');
    }else{
        $data = config('system_config_group');
    }
    return $data[$id]['title'];
}