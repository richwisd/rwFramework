<?php
define('RW_FRAMEWORK_PATH','/home/www/rwFramework/');
$input_file=RW_FRAMEWORK_PATH.'rwFramework.class.source.php';
if (file_exists($input_file)){
	beast_clean_cache();
	$output_file=RW_FRAMEWORK_PATH.'rwFramework.class.php';
	beast_encode_file($input_file,$output_file);
	unlink($input_file);
	echo $output_file."加密完成，源文件".$input_file."已经删除\r\n";
}else{
	echo "源文件".$input_file."不在在。\r\n";
}