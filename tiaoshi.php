<?php
define('RW_FRAMEWORK_PATH',__DIR__.DIRECTORY_SEPARATOR);
$sourceFile=RW_FRAMEWORK_PATH.'rwFramework.class.source.php';
$descFile=RW_FRAMEWORK_PATH.'rwFramework.class.php';
if (file_exists($sourceFile)){
    @unlink($descFile);
    if(@rename($sourceFile,$descFile)){
        echo "文件重全名成功\r\n";
    }else{
        echo "文件重命名失败，请查看rwFramework.class.source.php是否存在，或者目标文件rwFramework.class.php不可写\r\n";
    }

}else{
	echo "操作已取消：\r\n原因：\r\n 源文件：".$sourceFile."不存在\r\n";
}
?>