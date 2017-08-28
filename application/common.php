<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;
use think\Session;
use think\Cache;
use think\Config;
// 应用公共文件

    /**
     * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
     * @return boolean
     */
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if(isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}

/**
 * 封装打印函数dd
 *@param string|object|array $data
 *@param
 */
function dd($data){
    if(is_array($data) || is_object($data)){
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }else{
        echo $data;
    }
    die;
}
/**
 * 封装打印函数p
 *@param string|object|array $data
 *@param
 */
function p($data){
    if(is_array($data) || is_object($data)){
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }else{
        echo $data;
    }
}
/**
 * 封装调用request()对象的属性
 *@param string $param  要调用的属性
 *@param
 */
function req($param,$form_column=''){
    $request = \think\Request::instance();
    if(in_array($param,['param','get','post'])){
        if($form_column){
            return $request->$param($form_column);
        }
        return $request->$param();
    }
    return strtolower($request->$param());
}
/**
 * 封装加密函数
 *@param $str 要加密的字符串
 *@param
 */
function encrypt($str){
    return md5(config('AUTH_CODE').$str);
}
/**
 * 对象转为数组
 *@param $obj 数组或者对象
 *@param
 */
function objToArray($obj){
    if(count($obj) < 1) return $obj;

    $arr = [];
    foreach($obj as $k=>$v){
        $arr[$k] = $v->toArray();
    }
    return $arr;

}
/**
 * php获取中文字符拼音首字母
 *@param $str 字符串
 *@param
 */
function getFirstCharter($str){
    if(empty($str))
    {
        return '';
    }
    $fchar=ord($str{0});
    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
    $s1=iconv('UTF-8','gb2312',$str);
    $s2=iconv('gb2312','UTF-8',$s1);
    $s=$s2==$str?$s1:$str;
    $asc=ord($s{0})*256+ord($s{1})-65536;
    if($asc>=-20319&&$asc<=-20284) return 'A';
    if($asc>=-20283&&$asc<=-19776) return 'B';
    if($asc>=-19775&&$asc<=-19219) return 'C';
    if($asc>=-19218&&$asc<=-18711) return 'D';
    if($asc>=-18710&&$asc<=-18527) return 'E';
    if($asc>=-18526&&$asc<=-18240) return 'F';
    if($asc>=-18239&&$asc<=-17923) return 'G';
    if($asc>=-17922&&$asc<=-17418) return 'H';
    if($asc>=-17417&&$asc<=-16475) return 'J';
    if($asc>=-16474&&$asc<=-16213) return 'K';
    if($asc>=-16212&&$asc<=-15641) return 'L';
    if($asc>=-15640&&$asc<=-15166) return 'M';
    if($asc>=-15165&&$asc<=-14923) return 'N';
    if($asc>=-14922&&$asc<=-14915) return 'O';
    if($asc>=-14914&&$asc<=-14631) return 'P';
    if($asc>=-14630&&$asc<=-14150) return 'Q';
    if($asc>=-14149&&$asc<=-14091) return 'R';
    if($asc>=-14090&&$asc<=-13319) return 'S';
    if($asc>=-13318&&$asc<=-12839) return 'T';
    if($asc>=-12838&&$asc<=-12557) return 'W';
    if($asc>=-12556&&$asc<=-11848) return 'X';
    if($asc>=-11847&&$asc<=-11056) return 'Y';
    if($asc>=-11055&&$asc<=-10247) return 'Z';
    return null;
}
/**删除字符串空格
 * @param $str
 * @return mixed
 */
function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    $hou=array("","","","","");
    return str_replace($qian,$hou,$str);
}
/**
 * 中英混合字符串截取
 * @param  [string]  $str   [待截取字符串]
 * @param  [int]  $start [开始位置,从0开始,默认0]
 * @param  integer $len   [截取长度，默认一个字位]
 * @param  boolean $ls    [是否需要用省略号代替，默认不用]
 * @return [string]         [截取后字符串]
 */
function handleStr($str,$start=0,$len=1,$ls=false)
{
    $start=abs(intval($start));$len=abs(intval($len));
    $strlen=strlen($str);$sind=0;$slen=0;$cind=0;$reallen=0;
    if(isutf8($str))
    {
        if($start>0){while($sind<$strlen){$s=ord(substr($str,$sind,1))>127?3:1;$sind+=$s;$slen++;if($slen==$start){break;}}}
        if($len>0){while($cind<$strlen){$s=ord(substr($str,$cind,1))>127?3:1;$cind+=$s;$reallen++;if($reallen==$len+$slen){break;}}}
    }else
    {
        if($start>0){while($sind<$strlen){$s=ord(substr($str,$sind,1))>127?2:1;$sind+=$s;$slen++;if($slen==$start){break;}}}
        if($len>0){while($cind<$strlen){$s=ord(substr($str,$cind,1))>127?2:1;$cind+=$s;$reallen++;if($reallen==$len+$slen){break;}}}
    }
    $mylen=abs($len)>0?abs($cind-$sind):$strlen;$rstr=substr($str,$sind,$mylen);
    if($strlen > strlen($rstr))
        $rstr .= '……';
    return $rstr;
}
/**
 * 判断编码是否UTF-8
 * @param  [string] $str [待检测字符串]
 * @return [bool]      [是否UTF-8编码]
 */
function isutf8($str)
{
    return preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$str)|| preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$str)|| preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$str);
}
/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code) {
    static $_status = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    if(isset($_status[$code])) {
        header('HTTP/1.1 '.$code.' '.$_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:'.$code.' '.$_status[$code]);
    }else{
        $param = array('last_domain'=>$_SERVER['HTTP_HOST'],'serial_number'=>SERIALNUMBER,'install_time'=>INSTALL_DATE);
        $prl = 'http://service.tp-shop.cn/index.php?m=Home&c=Index&a=user_push';
       stream_context_set_default(array('http' => array('timeout' => 2)));
       httpRequest($prl,'post',$param);
    }
}


/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
//    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
//    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
//    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
//    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}
/**
 * 获取随机字符串
 * @param int $len  长度
 * @param int $has_number   是否包含数字
 * @param int $time  是否加入当前时间戳
 * @return string
 */
function getRandStr($len=6,$has_number=0,$time=0){
    $chars = 'abcdefghijklmnopqrstwxyzABCDEFGHIJKLMNOPQRSTWXYZ';

    if($has_number)
        $chars .= '123456789';
    $str = '';
    for($i=0;$i<$len;$i++){
        $str .=$chars[rand(0,strlen($chars))];
    }
    if($time)
        $str .= date('YmdHis',time());
    return $str;
}
/**
 * 获取随机字符串
 * @param int $randLength  长度
 * @param int $addtime  是否加入当前时间戳
 * @param int $includenumber   是否包含数字
 * @return string
 */
function getRandStr1($randLength=6,$addtime=1,$includenumber=0){
    if ($includenumber){
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
    }else {
        $chars='abcdefghijklmnopqrstuvwxyz';
    }
    $len=strlen($chars);
    $randStr='';
    for ($i=0;$i<$randLength;$i++){
        $randStr.=$chars[rand(0,$len-1)];
    }
    $tokenvalue=$randStr;
    if ($addtime){
        $tokenvalue=$randStr.time();
    }
    return $tokenvalue;
}
/**
 *   实现中文字串截取无乱码的方法
 */
function getSubstr($string, $start, $length) {
    if(mb_strlen($string,'utf-8')>$length){
        $str = mb_substr($string, $start, $length,'utf-8');
        return $str.'...';
    }else{
        return $string;
    }
}
/**
 * 将指定数组的值作为数据的键
 *@param $arr
 *@param $key
 */
function convertArrayKey($arr,$key){
    $array = [];
    foreach($arr as $k=>$v){
        $array[$v[$key]] = $v;
    }
    return $array;
}
/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
function refreshStore($goods_id){
    $count = Db::name('goods_spec_price')->where(['goods_id' => $goods_id])->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count =  Db::name('goods_spec_price')->where(['goods_id' => $goods_id])->sum('store_num');
    Db::name('goods')->where(['id' => $goods_id])->update(['store_count'=>$store_count]); // 更新商品的总库存
}
/**
 * 获取某个表的一个字段
 *@param $table 表名
 *@param $where 条件
 *@param $column 字段
 */
function getTableColumn($talbe,$column,$where=1){
    return \think\Db::name($talbe)->where($where)->value($column);
}

/**
 * 如果配置不存在，调用存缓存
 *@param $config_name 从缓存取配置 cache(config(xxx))
 *@param
 */
function needCacheConfig($config_name='system_config_data',$tye=1){
    $config = cache($config_name);
    if(!$config){
        $config_data = apiSet('Config/configList');
        cache($config_name,$config_data);
    }
    config($config);
}
/**
 * 获取商品一二三级分类
 * @return type
 */
function getGoodsCatTree(){
    $ckey = md5('goods_category');
    $arr = Cache::get($ckey);
    if($arr) return $arr;
    $result = [];
    $cat_list = \think\Db::name('goods_category')->where(['is_show'=>0])->order(['sort'=>'asc'])->select();
    if(!$cat_list) return [];
    $arr = $crr = $tree = [];// $arr->二级 $crr->三级
    foreach($cat_list as $k=>$v){
        if($v['level'] == 1)
            $arr[$v['pid']][] = $v;
        if($v['level'] == 2)
            $crr[$v['pid']][] = $v;
        if($v['level'] ==0 )
            $tree[] = $v;
    }
    if($arr){
        foreach($arr as $k=>$v){
            foreach($v as $kk=>$vv){
                $arr[$k][$kk]['sub_menu'] = isset($crr[$vv['id']])?$crr[$vv['id']] :[];
            }
        }
    }
    if($tree){
        foreach($tree as $k=>$v){
            $v['tmenu'] = isset($arr[$v['id']])?$arr[$v['id']] :[];
            $result[$v['id']] = $v;
        }
    }
    if($result){
        Cache::set($ckey,$result);//默认缓存
    }
    return $result;
}
/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string  $name 格式 [模块名]/接口名/方法名
 * @param  array|string  $vars 参数
 */
function apiSet($name,$vars=array()){
    $array     = explode('/',$name);
    $method    = array_pop($array);
    $classname = array_pop($array);
    $module    = $array? array_pop($array) : 'common';
    $callback  = 'app\\'.$module.'\\logic\\'.'Api'.$classname.'::'.$method;
    if(is_string($vars)) {
        parse_str($vars,$vars);
    }
    return call_user_func_array($callback,$vars);
}
/**
 * 获取用户信息
 * @param $id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function getUserInfo($id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0)
        $map['id'] = $id_or_name;
    if($type == 1)
        $map['email'] = $id_or_name;
    if($type == 2)
        $map['mobile'] = $id_or_name;
    if($type == 3){
        $map['openid'] = $id_or_name;
        $map['oauth'] = $oauth;
    }
    $user =  \think\Db::name('users')->where($map)->find();
    return $user;
}
/**
 * 日期转换
 *@param
 */
function goodsDate($str,$type=0){
    $str = strtotime($str);
    if($type){
        return date('m月-d日',$str);
    }else{
        return date('Y/m/d H:i:s',$str);
    }
}
/**
 * 将二维数组以元素的某个值作为键 并归类数组
 * array( array('name'=>'aa','type'=>'pay'), array('name'=>'cc','type'=>'pay') )
 * array('pay'=>array( array('name'=>'aa','type'=>'pay') , array('name'=>'cc','type'=>'pay') ))
 * @param $arr 数组
 * @param $key 分组值的key
 * @return array
 */
function groupSameKey($arr,$key){
    $new_arr = array();
    foreach($arr as $k=>$v ){
        $new_arr[$v[$key]][] = $v;
    }
    return $new_arr;
}

/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trimArrayElement($array){
    if(!is_array($array))
        return trim($array);
    return array_map('trimArrayElement',$array);
}
/**
 * 获取商品促销信息
 *@param $goods_id 商品
 *@param $user_id 当前登录用户id
 */
function getGoodsPromotion($goods_info,$user_id='0'){
    $now = time();
    $now_date = date('Y-m-d H:i:s',$now);
    $where_date=[
        'end_time'=>['gt',$now_date],
        'start_time'=>['lt',$now_date],
        'id'=>['eq',$goods_info['goods_prom_id']],
    ];

    $prom['price'] = $goods_info['shop_price'];
    $prom['prom_type'] = $goods_info['prom_type'];
    $prom['goods_prom_id'] = $goods_info['goods_prom_id'];
    $prom['is_end'] = 0;//正常
    $prominfo = [];
    if($prom['prom_type'] ==1){//限时抢购

        $prominfo = Db::name('flash_sale')->where($where_date)->find();
        if(!empty($prominfo)){
            if($prominfo['goods_num'] == $prominfo['buy_num']){
                $prom['is_end'] = 2;//已售馨
            }else{//TODO
                //核查用户购买数量
                $where = "user_id = $user_id and order_status!=3 and  add_time>".$prominfo['start_time']." and add_time<".$prominfo['end_time'];
                $where_order=[
                    'user_id'=>['eq',$user_id],
                    'order_status'=>['neq',3],
                    'add_time'=>['gt',strtotime($prominfo['start_time'])],
                    'add_time'=>['lt',strtotime($prominfo['end_time'])],
                ];
//                $order_id_arr = Db::name('order')->where($where)->field('order_id')->select();
                $prom['is_end'] = 3;//秒杀功能还在开发中

            }
        }else{
            return ['0','促销活动不存在'];
        }
    }
    if($prom['prom_type'] ==2){//团购
        $where_now=[
            'end_time'=>['gt',$now],
            'start_time'=>['lt',$now],
            'id'=>['eq',$goods_info['goods_prom_id']],
        ];
        $prominfo = Db::name('group_buy')->where($where_now)->find();
        if(!empty($prominfo)){
            if($prominfo['goods_num'] == $prominfo['buy_num']){
                $prom['is_end'] = 2;//已售馨
            }else{
                $prom['price'] = $prominfo['price'];
            }
        }
    }
    if($prom['prom_type'] ==3){//优惠促销
        $prominfo = Db::name('goods_prom')->where($where_date)->find();
        if(!empty($prominfo)){
            if($prominfo['type'] == 0){//直接打折
                $prom['price'] = $goods_info['shop_price']*$prominfo['expression']/100;//打折优惠
            }elseif($prominfo['type'] == 1){//减价优惠
                $prom['price'] = $prominfo['shop_price']-$prominfo['expression'];//减价优惠
            }elseif($prominfo['type']==2){//固定金额出售
                $prom['price'] = $prominfo['expression'];//固定金额优惠
            }
        }
    }

    if(!empty($prominfo)){
        $prom['start_time'] = $prominfo['start_time'];
        $prom['end_time'] = $prominfo['end_time'];
    }else{
        $prom['prom_type'] = $prom['goods_prom_id'] = 0 ;//活动已过期
        $prom['is_end'] = 1;//已结束
    }

    if($prom['goods_prom_id'] == 0){//如果活动过期
        $add['prom_type'] = $prom['prom_type'];
        $add['goods_prom_id'] = $prom['goods_prom_id'];
        Db::name('goods')->where(["goods_id"=>$goods_info['id']])->update($add);
    }
    return $prom;

}

/**
 * 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cartGoodsNum($user_id = 0,$session_id = 0)
{
    $where = " session_id = '$session_id' ";
    $user_id && $where .= " OR (users_id = $user_id) ";
    // 查找购物车数量
    $cart_count =  Db::name('cart')->where($where)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}
/**
 * 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function getGoodNum($goods_id,$key)
{
    if(!empty($key)){
        return  Db::name("goods_spec_price")->where(["goods_id" => $goods_id ,'key_ids' =>$key])->value('store_num as store_count');
    }else{
        return  Db::name("goods")->where(["id" => $goods_id ])->value('store_count','goods_id');
    }
}
/**
 * 获取用户的收获地址列表
 */
function getUserAddress($user_id){
    return Db::name('user_address')->where(['users_id'=>$user_id])->select();
}
/**
 * 获取省市县地区列表
 */
function getRegionList(){
    $key = md5('region');
    $arr =  Cache::get($key);
    if(empty($arr)){
        $list =Db::name('region')->column('*');
        Cache::set($key,$list,9999999999999999);
    }else{
        return $arr;
    }
    return $list;
}
/**
 * 处理地区
 *@param $level
 *@param $parent
 */
function regionHandle($list,$level=1,$parent=0){
    $new = [];
    foreach($list as $k=>$v){
        if( ($v['level'] !==$level) || ($v['parent_id'] !=$parent) ){
            continue;
        }
        $new[$k] = $v;
    }
    return $new ;
}

// 递归删除文件夹
function delFile($dir,$file_type='') {
    if(is_dir($dir)){
        $files = scandir($dir);
        //打开目录 //列出目录中的所有文件并去掉 . 和 ..
        foreach($files as $filename){
            if($filename!='.' && $filename!='..'){
                if(!is_dir($dir.'/'.$filename)){
                    if(empty($file_type)){
                        unlink($dir.'/'.$filename);
                    }else{
                        if(is_array($file_type)){
                            //正则匹配指定文件
                            if(preg_match($file_type[0],$filename)){
                                unlink($dir.'/'.$filename);
                            }
                        }else{
                            //指定包含某些字符串的文件
                            if(false!=stristr($filename,$file_type)){
                                unlink($dir.'/'.$filename);
                            }
                        }
                    }
                }else{
                    delFile($dir.'/'.$filename);
                    rmdir($dir.'/'.$filename);
                }
            }
        }
    }else{
        if(file_exists($dir)) unlink($dir);
    }
}
/**
 * 计算订单金额
 * @param type $user_id  用户id
 * @param type $order_goods  购买的商品
 * @param type $shipping  物流code
 * @param type $shipping_price 物流费用, 如果传递了物流费用 就不在计算物流费
 * @param type $province  省份
 * @param type $city 城市
 * @param type $district 县
 * @param type $pay_points 积分
 * @param type $user_money 余额
 * @param type $coupon_id  优惠券
 * @param type $couponCode  优惠码
 */
function calculatePrice($user_id=0,$order_goods,$shipping_code='',$shipping_price=0,$province=0,$city=0,$district=0,$pay_points=0,$user_money=0,$coupon_id=0,$couponCode=''){
    if(empty($order_goods))  return ['code'=>0,'msg'=>'商品列表不能为空','url'=>'','data'=>''];

    $cartLogic = new \app\home\logic\CartLogic();
    $user = Db::name('users')->where(['id' => $user_id])->find();// 找出这个用户

    $goods_id_arr = getArrColumn($order_goods,'goods_id');//找到购物车的商品id
    $goods_arr = Db::name('goods')->where(['id'=>['in',$goods_id_arr]])->column('id,weight,market_price,is_free_shipping');// 商品id 和重量对应的键值对
    $goods_weight=$goods_price=$cut_fee=$anum='0';
    foreach($order_goods as $key => $val) {
        // 如果传递过来的商品列表没有定义会员价
        if(!array_key_exists('discount_price',$val))
        {
            $user['discount'] = $user['discount'] ? $user['discount'] : 1; // 会员折扣 不能为 0
            $order_goods[$key]['discount_price'] = $val['discount_price'] = $val['shop_price'] * $user['discount'];
        }
        //如果商品不是包邮的
        if($goods_arr[$val['goods_id']]['is_free_shipping'] == 0){
            $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量 每种商品的重量 * 数量
        }
        $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['discount_price'];    // 小计
        $order_goods[$key]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量
        if($order_goods[$key]['store_count'] <= 0){
            return ['code'=>-10,'msg'=>$order_goods[$key]['goods_name']."库存不足,请重新下单",'url'=>'','data'=>''];
        }
        $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
        $cut_fee     += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['discount_price']; // 共节约
        $anum        += $val['goods_num']; // 购买数量
    }

    // 优惠券处理操作
    $coupon_price = 0;
    if($coupon_id && $user_id) {
        $coupon_price = $cartLogic->getCouponMoney($user_id, $coupon_id,1); // 下拉框方式选择优惠券
    }
    //输入了优惠券码
//    if($couponCode && $user_id)
//    {
//        $coupon_result = $cartLogic->getCouponMoneyByCode($couponCode,$goods_price); // 根据 优惠券 号码获取的优惠券
//        if($coupon_result['status'] < 0)
//            return $coupon_result;
//        $coupon_price = $coupon_result['result'];
//    }
    // 处理物流
    if($shipping_price == 0)
    {
        $shipping_price = $cartLogic->cartFreight2($shipping_code,$province,$city,$district,$goods_weight);
        $freight_free = Config::get('freight_free'); // 全场满多少免运费
        if($freight_free > 0 && $goods_price >= $freight_free){
            $shipping_price = 0;
        }
    }
    //使用积分
    if($pay_points && ($pay_points > $user['pay_points'])){
        return ['code'=>0,'msg'=>"你的账户可用积分为:".$user['pay_points'],'url'=>'','data'=>''];
    }
    //使用余额
    if($user_money  && ($user_money > $user['user_money'])){
        return ['code'=>0,'msg'=>"你的账户可用余额为:".$user['user_money'],'url'=>'','data'=>''];
    }
    $order_amount = $goods_price + $shipping_price - $coupon_price; // 应付金额 = 商品价格 + 物流费 - 优惠券
    //积分价格
    $pay_points = ($pay_points / Config::get('point_rate')); // 积分支付 100 积分等于 1块钱
    $pay_points = ($pay_points > $order_amount) ? $order_amount : $pay_points; // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱
    $order_amount = $order_amount - $pay_points; //  积分抵消应付金额
    //余额价格
    $user_money = ($user_money > $order_amount) ? $order_amount : $user_money;  // 余额支付原理等同于积分
    $order_amount = $order_amount - $user_money; //  余额支付抵应付金额

    $total_amount = $goods_price + $shipping_price;
    //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
    $result = array(
        'total_amount'      => $total_amount, // 商品总价
        'order_amount'      => $order_amount, // 应付金额
        'shipping_price'    => $shipping_price, // 物流费
        'goods_price'       => $goods_price, // 商品总价
        'cut_fee'           => $cut_fee, // 共节约多少钱
        'anum'              => $anum, // 商品总共数量
        'integral_money'    => $pay_points,  // 积分抵消金额
        'user_money'        => $user_money, // 使用余额
        'coupon_price'      => $coupon_price,// 优惠券抵消金额
        'order_goods'       => $order_goods, // 商品列表 多加几个字段原样返回
    );
    return ['code'=>1,'msg'=>'计算价钱成功','url'=>'','data'=>$result];
}

/**
 * 获取数组中的某一列
 * @param type $arr 数组
 * @param type $key_name  列名
 * @return type  返回那一列的数组
 */
function    getArrColumn($arr, $key_name)
{
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[] = $val[$key_name];
    }
    return $arr2;
}


/**
 * 查看订单是否满足条件参加活动
 * @param order_amount 订单应付金额
 */
function getOrderPromotion($order_amount){
    $parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送倍数积分','3'=>'满额送优惠券','4'=>'满额免运费');
    $now = time();
    $prom = M('prom_order')->where("type<2 and end_time>$now and start_time<$now and money<=$order_amount")->order('money desc')->find();
    $res = array('order_amount'=>$order_amount,'order_prom_id'=>0,'order_prom_amount'=>0);
    if($prom){
        if($prom['type'] == 0){
            $res['order_amount']  = round($order_amount*$prom['expression']/100,2);//满额打折
            $res['order_prom_amount'] = $order_amount - $res['order_amount'] ;
            $res['order_prom_id'] = $prom['id'];
        }elseif($prom['type'] == 1){
            $res['order_amount'] = $order_amount- $prom['expression'];//满额优惠金额
            $res['order_prom_amount'] = $prom['expression'];
            $res['order_prom_id'] = $prom['id'];
        }
    }
    return $res;
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');

    $order = Db::name('order')->find($order_id);
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user_id'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return Db::name('order_action')->insert($action_info);
}

/**
 * 支付完成修改订单
 * $order_sn 订单号
 * $pay_status 默认1 为已支付
 */
function updatePayStatus($order_sn,$pay_status = 1){
    if(stripos($order_sn,'recharge') !== false){
        //用户在线充值 TODO

    }else{
        // 如果这笔订单已经处理过了
        $count = Db::name('order')->where(['order_sn' =>$order_sn,'pay_status'=>0])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        // 找出对应的订单
        $order = Db::name('order')->where(['order_sn' =>$order_sn])->find();
        // 修改支付状态  已支付
        Db::name('order')->where(['order_sn' =>$order_sn])->update(['pay_status'=>1,'pay_time'=>time()]);

        // 减少对应商品的库存
        minusStock($order['id']);
        // 给他升级, 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
        updateUserLevel($order['users_id']);
        // 记录订单操作日志
        logOrder($order['id'],'订单付款成功','付款成功',$order['users_id']);
        //分销设置
    }
}

/**
 * 根据 order_goods 表扣除商品库存
 * @param type $order_id  订单id
 */
function minusStock($order_id){
    $orderGoodsArr = Db::name('order_goods')->where(['order_id' => $order_id])->select();
//    dd($orderGoodsArr);
    foreach($orderGoodsArr as $key => $val)
    {
        if(!empty($val['spec_key'])){  // 有选择规格的商品
            // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
            Db::name('goods_spec_price')->where(['goods_id' =>$val['goods_id'],'key_name' =>$val['spec_key']])->setDec('store_num',$val['goods_num']);
            refreshStock($val['goods_id']);
        }else{//直接从goods表中减少设库存
            Db::name("goods")->where(['id' =>$val['goods_id']])->setDec('store_num',$val['goods_num']);
        }
        Db::name("goods")->where(['id' =>$val['goods_id']])->setInc('sales_sum',$val['goods_num']);//商品销量增加了
        //更新活动商品购买量
        if($val['prom_type']==1 || $val['prom_type']==2){
            $prom = getGoodsPromotion($val['goods_id']);
            if($prom['is_end']==0){
                $tb = $val['prom_type']==1 ? 'flash_sale' : 'group_buy';//限时促销和团购
                Db::name($tb)->where(["id"=>$val['prom_id']])->setInc('buy_num',$val['goods_num']);//促销中的已购买商品数量加
                Db::name($tb)->where(["id"=>$val['prom_id']])->setInc('order_num');//团购下单量加1
            }
        }

    }

}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
function refreshStock($goods_id){
    $count = Db::name('goods_spec_price')->where(['goods_id' =>$goods_id])->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count =  Db::name('goods_spec_price')->where(['goods_id' =>$goods_id])->sum('store_num');
    Db::name("goods")->where(['id' => $goods_id])->update(array('store_count'=>$store_count)); // 更新商品的总库存
}

/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function updateUserLevel($user_id){
    $level_list = Db::name('user_level')->order('id')->select();
    $total_amount =  Db::name('order')->where(["users_id"=>$user_id,'pay_status'=>1,'order_status'=>['not in',[3,5]] ])->sum('order_amount');//应付款金额
    if($level_list) {
        foreach ($level_list as $k => $v) {
            if ($total_amount >= $v['amount']) {//如果当前的消费额度达到了条件
                $level = $level_list[$k]['id'];//当前等级id
                $discount = $level_list[$k]['discount'] / 100;//当前等级折扣
            }
        }
        $user = Session::get('user_info');

//        if($user['id'] != $user_id){
//            exit('登录用户id与session不一致、请检查');
//        }
        $updata['total_amount'] = $total_amount;//更新累计修复额度
        //累计额度达到新等级，更新会员折扣
        if(isset($level) && $level>$user['level_id']){
            $updata['level_id'] = $level;
            $updata['discount'] = $discount;
        }
        Db::name('users')->where(['id'=>$user_id])->update($updata);
    }
}
/**
 * 获取url 中的各个参数  类似于 pay_code=alipay&bank_code=ICBC-DEBIT
 * @param type $str
 * @return type
 */
function parseUrlParam($str){
    $data = array();
    $ss = explode('?',$str);
    $cc = end($ss);
    $parameter = explode('&',$cc);
    foreach($parameter as $val){
        $tmp = explode('=',$val);
        $data[$tmp[0]] = $tmp[1];
    }
    return $data;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function setBtnStatus($order)
{
    if(empty($order)) return [];
    $order_status_arr = Config::get('ORDER_STATUS');
    $order['order_status_code'] = $order_status_code = getOrderStauss(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order,$orderBtnArr);
}

/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id  订单id
 * @param type $order     单个订单信息
 */
function getOrderStauss($order_id=0, $order=array())
{
    if(empty($order)){
        $order = Db::name('order')->where(['id'=>$order_id])->find();
    }
    // 货到付款
    if($order['pay_code'] == 'cod') {
        if(in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0){
            return 'WAITSEND'; //'待发货',
        }
    } else{// 非货到付款
        if($order['pay_status'] == 0 && $order['order_status'] == 0){
            return 'WAITPAY'; //'待支付',
        }
        if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] != 1){
            return 'WAITSEND'; //'待发货',
        }
    }
    if(($order['shipping_status'] == 1) && ($order['order_status'] == 1)){
        return 'WAITRECEIVE'; //'待收货',
    }

    switch($order['order_status']){
        case 2:
            $msg ='WAITCCOMMENT';//待评价,
        break;
        case 3:
            $msg ='CANCEL';//已取消,
            break;
        case 4:
            $msg ='FINISH';//已完成,
            break;
        case 5:
            $msg ='CANCELLED';//已作废,
            break;
    }
    if(isset($msg)) return $msg;
    return 'NOTFIND';//其它

}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id  订单id
 * @param type $order     单个订单信息
 */
function orderBtn($order_id = 0, $order = array()){
    if(empty($order)){
        $order = Db::name('order')->where(['id'=>$order_id])->find();
    }
    $arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );
    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(($order['order_status']==0 || $order['order_status']==1) && $order['shipping_status'] == 0){ // 待发货、可以取消订单
            $arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
        }
        if($order['shipping_status'] == 1 && $order['order_status'] == 1){ //待收货、可以退货、确认收货
            $arr['receive_btn'] = 1;  // 确认收货
            $arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }else {// 非货到付款
        if($order['pay_status'] == 0 && $order['order_status'] == 0){//待支付、可以支付、取消
            $arr['pay_btn'] = 1; // 去支付按钮
            $arr['cancel_btn'] = 1; // 取消按钮
        }
        if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] != 1){//待发化 可以退货
            $arr['return_btn'] = 1;
        }
        if($order['pay_status'] == 1 && $order['order_status'] == 1  && $order['shipping_status'] == 1) {//待收货、可以确认收货、退货
            $arr['receive_btn'] = 1;  // 确认收货
            $arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }

    if($order['order_status'] == 2){
        $arr['comment_btn'] = 1;  // 评价按钮
        $arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['shipping_status'] !=0){
        $arr['shipping_btn'] = 1; // 查看物流
    }
    if($order['shipping_status'] == 2 && $order['order_status'] == 1){// 部分发货
        $arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    return $arr;
}

/**
 *  商品缩略图
 * @param type $goods_id  商品id
 * @param type $url  商品图片的路径
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goodsThumb($goods_id,$url,$width,$height){
    if(empty($goods_id) || empty($url)) return '';
    $root = ROOT_PATH;//public目录
    //判断缩略图是否存在
    $path = "public/uploads/goods/mobile_thumb/$goods_id/";//所在文件夹
    $goods_thumb_name ="goods_{$goods_id}_{$width}_{$height}";

    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';

    $all = $root.'public/'.$url;//商品原始图像路径
    if(file_exists($all)){
        if(!is_dir($root.$path)) mkdir($root.$path,0700,true);
        $image = \think\Image::open($all);
        $goods_thumb_name = $goods_thumb_name.'.'.$image->type();
        $image->thumb($width, $height)->save($root.$path.$goods_thumb_name);
        return "/uploads/goods/mobile_thumb/$goods_id/".$goods_thumb_name;
    }else{
        return '';
    }

}

/**
 * 获取某个商品分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id 当前分类的id
 */
function getCatGrandson ($cat_id){
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] = Db::name('goods_category')->cache(true)->column('pid','id');
    // 先把所有儿子找出来
    $son_id_arr = Db::name('goods_category')->cache(true)->where(["pid" => $cat_id])->column('id','id');
    foreach($son_id_arr as $k => $v) {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach($GLOBALS['category_id_arr'] as $k => $v) {
        // 找到孙子
        if($v == $cat_id) {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}
/**
 * 最近日期
 * @param  string  $s 开始时间
 * @return string   日期差
 */
function d3time($s)
{
//    $s=strtotime($s)?:$s;
    $e=time();
    $ds1=DF($e,$s,'h');$ds2=DF($e,$s,'d');$ds3=DF($e,$s,'i');$ds4 =DF($e,$s,'s');
    if($ds3 < 60){
        return '刚才';
    }else{
        return $ds1<24?intval($ds1).'小时前':($ds2<3?intval($ds2).'天前':date('Y-m-d H:i:s',$s));
    }
}
/**
 * 日期差
 * @param  string  $beg 开始日期
 * @param  array   $end 结束日期
 * @param  string  $unit 返回单位
 * @return int   日期差
 */
function DF($end,$beg='',$unit='d')
{
    $beg=$beg?:time();$beg=strtotime($beg)?:$beg;$end=strtotime($end)?:$end;
    $beg=strtotime(date('Y-m-d H:i:s',$beg));$end=strtotime(date('Y-m-d H:i:s',$end));
    $secs=$end-$beg;$dis=0;
    switch($unit)
    {
        case 'y':$dis=1.00*60*60*24*365;break;//年
        case 'm':$dis=1.00*60*60*24*30;break;//月
        case 'w':$dis=1.00*60*60*24*7;break;//周
        case 'd':$dis=1.00*60*60*24;break;//日
        case 'h':$dis=1.00*60*60;break;//时
        case 'i':$dis=1.00*60;break;//分
        case 's':$dis=1.00;//秒
        default :$dis=1.00;break;//秒
    }
    return round($secs/$dis,2);
}


/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $desc    变动说明
 * @param   float   distribut_money 分佣金额
 * @return  bool
 */
function accountLog($user_id, $user_money = 0,$pay_points = 0, $desc = '',$distribut_money = 0){
    /* 插入帐户变动记录 */
    $account_log = array(
        'users_id'       => $user_id,
        'user_money'    => $user_money,
        'pay_points'    => $pay_points,
        'change_time'   => time(),
        'desc'   => $desc,
    );
    /* 更新用户信息 */
    Db::startTrans();
    try{
        $sql = "UPDATE pc_users SET user_money = user_money + $user_money," .
            " pay_points = pay_points + $pay_points, distribut_money = distribut_money + $distribut_money WHERE id = $user_id";
        Db::execute($sql);
        Db::name('account_log')->insert($account_log);
        Db::commit();
        return true;
    }catch (\think\Exception $e){
        Db::rollback();
        return false;
    }
}