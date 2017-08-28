<?php
/**
 * Created by PhpKiller.
 * User: Across The Pacific
 * Date: 2017/1/9
 * Time: 13:51
 */
namespace app\admin\controller;
use think\Db;
use think\Session;

class DataBase extends Base{

    /*数据库表*/
    public function index()
    {
        $data_tables = array_map('array_change_key_case',Db::query("SHOW TABLE STATUS"));
        $total = 0;
        foreach($data_tables as $k=>$v){
            $data_tables[$k]['size'] =formatBytes($v['data_length'] + $v['index_length']);
            $total += $v['data_length'] + $v['index_length'];
        }
        return view("data/index",['lists'=>$data_tables,'total'=>formatBytes($total),'table_num'=>count($data_tables)]);
    }
    /*数据库备份*/
    public function backUp()
    {
        function_exists('set_time_limit') && set_time_limit(0);//防止超时
        $tables = input('param.tables/a',[]);
        if(empty($tables)) {
            $this->error('请选择要备份的数据表');
        }
        $stime = time();//开始时间
        if(!file_exists('./uploads/sql_data')){
            mkdir('./uploads/sql_data');
        }
        $path = './uploads/sql_data/pc_tables_'.date('YmdHis').getRandStr(3);
        $pre = "/* -----------------------------------------------------------*/\n";

        //取得表结构信息
        //1，表示表名和字段名会用``包着的,0 则不用``
        Db::query("SET SQL_QUOTE_SHOW_CREATE = 0");
        $outstr = '';
        foreach($tables as $k=>$v){
            $outstr .="/* 表的结构 {$v}*/ \n";
            $outstr .="DROP TABLE IF EXISTS {$v};\n";
            $tmp = Db::query("SHOW CREATE TABLE {$v}");
            $outstr .= $tmp[0]['Create Table'] . ";\n\n";
        }
        $sqlTable = $outstr;//表结构--建表语句
        $file_n =1 ;
        $outstr = "";
        $backed_table = [];//备份的表
        //表中的数据
        foreach($tables as $k=>$v){//循环出表名
            $backed_table[] = $v;
            $outstr .="\n\n/* 转存表中的数据:{$v}*/ \n";//表中的数据
//            $table_info = Db::query("SHOW TABLE STATUS LIKE '{$v}'");
            $one_table = Db::query("SELECT * FROM {$v}");//查出每一个表的所有数据
            foreach($one_table as $kk=>$vv){
                $tn = 0 ;//表中的第几条数据
                $tem_sql = '';//将每一张表的每条数据拼接起来
                foreach($vv as $vvv){
                    $tem_sql .= $tn==0?"":",";
                    $tem_sql .= $v==''?"''":"'{$vvv}'";
                    $tn++;
                }
                $tem_sql = "INSERT INTO `{$v}` VALUES ({$tem_sql});\n";
                $sql_no = "\n/* Time: " . date("Y-m-d H:i:s")."*/\n" .
                    "/* -----------------------------------------------------------*/\n" .
                    "/* SQLFile Label：#{$file_n}*/\n/* -----------------------------------------------------------*/\n\n\n";
                if ($file_n == 1) {
                    $sql_no = "/* Description:备份的数据表[结构]：" . implode(",", $tables)."*/\n".
                        "/* Description:备份的数据表[数据]：" . implode(",", $backed_table).'*/' . $sql_no;
                } else {//如果不是第一个文件
                    $sql_no = "/* Description:备份的数据表[数据]：" . implode(",", $backed_table).'*/' . $sql_no;
                }
                if (strlen($pre) + strlen($sql_no) + strlen($sqlTable) + strlen($outstr) + strlen($tem_sql) > (1024*1024*config("CFG_SQL_FILESIZE"))) {//如果超出了每个sql文件的限制
                    $file = $path . "_" . $file_n . ".sql";
                    if($file_n ==1){
                        $outstr =$pre . $sql_no . $sqlTable . $outstr;
                    }else{
                        $outstr =$pre . $sql_no  . $outstr;
                    }
                    if (!file_put_contents($file, $outstr, FILE_APPEND)) {
                        $this->error("备份文件写入失败！", url('DataBase/index'));
                    }
                    $sqlTable = $outstr = "";
                    $backed_table = array();
                    $backed_table[] = $v;
                    $file_n++;
                }
                $outstr.=$tem_sql;
            }
        }
        if (strlen($sqlTable . $outstr) > 0 ) {
            $sql_no = "\n/* Time: " . date("Y-m-d H:i:s")."*/\n" .
                "/* -----------------------------------------------------------*/\n" .
                "/* SQLFile Label：#{$file_n}*/\n/* -----------------------------------------------------------*/\n\n\n";
            if ($file_n == 1) {
                $sql_no = "/* Description:备份的数据表[结构]：" . implode(",", $tables)."*/\n".
                    "/* Description:备份的数据表[数据]：" . implode(",", $backed_table).'*/' . $sql_no;
            } else {//如果不是第一个文件
                $sql_no = "/* Description:备份的数据表[数据]：" . implode(",", $backed_table).'*/' . $sql_no;
            }
            $file = $path . "_" .$file_n. ".sql";
            if($file_n==1){
                $outstr =$pre . $sql_no . $sqlTable . $outstr;
            }else{
                $outstr =$pre . $sql_no  . $outstr;
            }
            if (!file_put_contents($file, $outstr, FILE_APPEND)) {
                $this->error("备份文件写入失败！", url('DataBase/index'));
            }
            $file_n++;
        }
        $etime = time() - $stime;
        adminLog('数据库备份',req('url'));
        $this->success("成功备份数据表，本次备份共生成了" . ($file_n-1) . "个SQL文件。耗时：{$etime} 秒");
    }
    /*数据库表优化*/
    public function optimize()
    {
        $num = 1;
        if(request()->isPost()){
            $table = input('param.tables/a');
            $num = count($table);
            $table = implode(',',$table);
        }
        if(!Db::query("OPTIMIZE TABLE {$table} ")){
            $this->error('操作失败请重试');
        }
        $this->success("共计{$num}张表,优化成功");
    }
    /*数据库修复*/
    public function repair()
    {
        $num = 1;
        if(request()->isPost()){
            $table = input('param.tables/a');
            $num = count($table);
            $table = implode(',',$table);
        }
        if(!Db::query("REPAIR TABLE {$table} ")){
            $this->error('操作失败请重试');
        }
        $this->success("共计{$num}张表,修复成功");
    }
    /*数据库备份列表*/
    public function bakList()
    {
        $all_file = glob('./uploads/sql_data/*.sql');
        $final = [];
        $size = 0;
        if(count($all_file) > 0){
            foreach($all_file as $v){
                if(is_file($v)){
                    $size += filesize($v);
                }
                $final[] = [
                    'name'=>basename($v),
                    'size'=>filesize($v),
                    'time'=>filemtime($v),
                    'pre'=>substr(explode('.',basename($v))[0],0,-2),
                    'number'=>str_replace('_','',substr(basename($v),-6,2))
                ];
            }
        }
        krsort($final);
        return view('data/bak_list',['lists'=>$final,'total'=>formatBytes($size),'num'=>count($final)]);
    }
    /*恢复备份数据*/
    public function restoreData()
    {
//        Session::delete('cacheRestore');die;
        function_exists('set_time_limit') && set_time_limit(0);//防止超时
        //取得需要导入的sql文件
        if(!isset(session('cacheRestore')['files'])){
            Session::set('cacheRestore',['time'=>time(),'files'=>$this->getRestoreFiles()]);
        }
//        dd( session('cacheRestore'));
        $files = session('cacheRestore')['files'];
        //取得上次文件导入到sql的句柄位置
        $position = isset(session('cacheRestore')['position']) ? session('cacheRestore')['position'] : 0;
        $execute = 0;
        foreach($files as $k=>$v){
            $file = './uploads/sql_data/'.$v;
            if(!file_exists($file)) continue;
            $fh = fopen($file,'r');
            $sql = "";
            fseek($fh,$position);//将文件指针指向上次的位置
            while(!feof($fh)){
                $tem = trim(fgets($fh));
                //过滤,去掉空行、注释行(#,--)
                if (empty($tem) || (($tem[0] == '/') && ($tem[1] == '*')) || ($tem[0] == '-' && $tem[1] == '-'))
                    continue;
                //统计一行字符串的长度
                $end = (int) (strlen($tem) - 1);
                //检测一行字符串最后有个字符是否是分号，是分号则一条sql语句结束，否则sql还有一部分在下一行中
                $sql .= $tem;
                if($tem[$end] == ';'){
                    Db::query($sql);
                    $sql = '';
                    $execute++;
//                    if($execute > 1){
//                        session('cacheRestore')['position'] = ftell($fh);
//                        $imported = isset(session('cacheRestore')['imported']) ? session('cacheRestore')['imported'] : 0;
//                        $imported += $execute;
//                        session('cacheRestore.imported',$imported);
//                        dd( session('cacheRestore'));
//                        $this->success('如果SQL文件卷较大(多),则可能需要几分钟甚至更久,<br/>请耐心等待完成，<font color="red">请勿刷新本页</font>，<br/>当前导入进度：<font color="red">已经导入' . $imported . '条Sql</font>', url('restoreData', array('rad' => getRandStr(5,0))));
//                    }
                }
            }
            //错误位置结束
            fclose($fh);
            unset($files[$k]);
            session('cacheRestore.files',$files);
            $position = 0;
        }
        $time = time() - session('cacheRestore')['time'];
        Session::delete('cacheRestore');
        $this->success("导入成功，耗时：{$time} 秒钟", url('bakList'));
    }
    /*读取要导入的sql文件列表并按卷号排序*/
    private function getRestoreFiles(){
        $file = input('param.sqlfile');
        $sql_file = glob('./uploads/sql_data/'.$file.'_*.sql');
        if(count($sql_file) <1){
            $this->error("对应的sql文件不存在");
        }
        //将要还原的sql文件按顺序组成数组，防止先导入不带表结构的sql文件
        $files = [];
        foreach($sql_file as $v){
            $k=str_replace('_','',substr(basename($v),-6,2));
            $files[$k] = basename($v);
        }
        unset($file,$sql_file);
        ksort($files);
        return $files;
    }
    /*下载*/
    public function downFile()
    {
        $file = './uploads/sql_data/'.input('param.file');
        if(!file_exists($file)){
            $this->error("该文件不存在，可能是已经被删除");
        }
        $filename = basename($file);
        header("Content-type: application/octet-stream");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Length: " . filesize($file));
        readfile($file);
    }
    /*删除备份*/
    public function delSqlFile()
    {
       $sql_file = glob('./uploads/sql_data/'.input('param.ids').'_*.sql');
//        dd($sql_file);
        foreach($sql_file as $k=>$v){
            unlink($v);
        }
        $this->success("删除成功,共删除".count($sql_file)."个文件");
    }
}