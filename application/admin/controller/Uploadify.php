<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2016/12/28
 * Time: 19:39
 */
namespace app\admin\controller;
use think\Image;
use think\Validate;

class Uploadify extends Base{
    /*webUpload文件上传*/ //商品相册----->存入goods/temp目录，然后录入thumb目录
    public function webUpload()
    {
        $file = request()->file('file');

        $root = ROOT_PATH . 'public';//public目录
        $path = DS.'uploads'.DS.'goods'.DS.'temp'.DS;//图片在项目上的放目录
        $complete = $root.$path;

        $info = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->rule('uniqid')->move($complete);
        if($info){
            $saveName = $info->getSaveName();
            $this->cutImg($complete.$saveName,'600','600');
            return $path.$saveName;
        }else{
            return $file->getEror();
        }
    }
    /*jquery文件上传*/  //常用单图上传----先存入temp目录,入库之前再裁剪
    public function jqUpload()
    {
        $paths = input('param.path')?:'images';
        $file = request()->file('myfile');

        $root = ROOT_PATH . 'public';//public目录
        $path = DS.'uploads'.DS.$paths.DS.'temp'.DS;//图片在项目上的放目录
        $complete = $root.$path;

        $result = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->rule('uniqid')->move($complete);
        if($result){
           return json_encode(['code'=>1,'msg'=>$path.$result->getSaveName()]);
        }else{
            return json_encode(['code'=>0,'msg'=>$file->getError()]);
        }
    }
    /*wangeditor上传图片*/ //和jqUpload方法大体一致,返回的格式不一样而以  文章和图片->先裁剪->入库的时候移动到新目录
    public function editUpload()
    {
        $paths = input('param.path')?:'images';
        $file = request()->file('myfile');

        $root = ROOT_PATH . 'public';//public目录
        $path = '/uploads/'.$paths.'/temp/';//图片在项目上的放目录
        $complete = $root.$path;

        $result = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->rule('uniqid')->move($complete);
        if($result){
            $saveName = $result->getSaveName();
            $this->cutImg($complete.$saveName,'800','800');
            return $path.$saveName;
        }else{
            return json_encode(['code'=>0,'msg'=>$file->getError()]);
        }
    }
    /*删除上传的图片*/
    public function delImg()
    {
        $lu = input('param.path');
        $all = str_replace('\\','/',ROOT_PATH.'public');//硬盘上文件目录
        if(file_exists($all.$lu)){
               unlink($all.$lu);
        }
        exit;
    }
    /**
     * 图片裁剪
     *@param $url 图片的完整路径
     *@param $width 图片的宽
     *@param $height 图片的高
     */
    protected function cutImg($url,$width='400',$height='400'){
        $image = Image::open($url);
        $image->thumb($width, $height)->save($url);
        return $url;
    }
    /**
     * 图片处理方法--将图片从temp移动到真实目录
     *@param $str 图片在服务器上的路径
     *@param $mulu 要移动到哪个文件夹
     *@param $type 值为1则裁剪
     *@param $width 宽
     *@param $height 高
     */
    public function imgHandle($str,$mulu='images',$type=0,$width='200',$height='200')
    {
        $root = ROOT_PATH . 'public';//public目录
        $path = DS.'uploads'.DS.$mulu.DS.date('ym',time()).DS;//移动到新目录

        if(file_exists($root.$str)){
            if(!is_dir($root.$path)) mkdir($root.$path,0700,true);
            $npath = $root.$path.basename($str);
            rename($root.$str,$npath);//文件移动
            if($type ==1){//缩略
                $this->cutImg($npath,$width,$height);
            }
        }else{
            return $str;
        }
        return $path.basename($str);
    }
    /**
     * 图片处理方法--将图片从temp移动到真实目录
     *@param $str 图片在服务器上的路径
     *@param $mulu 要移动到哪个文件夹
     *@param $type 值为1则裁剪
     *@param $width 宽
     *@param $height 高
     */
    public function imgHandleShipping($str,$mulu='shipping',$width='200',$height='200')
    {
        $root = ROOT_PATH . 'public';//public目录
            if(!is_dir($mulu)) mkdir($mulu,0700,true);
            rename($root.$str,$mulu.'logo.jpg');//文件移动
            $this->cutImg($mulu.'logo.jpg',$width,$height);

        return $mulu.'logo.jpg';
    }
}