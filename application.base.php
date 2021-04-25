<?php
//获取$_GET的指定数据
function _G($key, $type = 'string', $def = '') {
	if (isset($_GET[$key]) && is_array($_GET[$key])) {
		$val = $_GET[$key];
	} else {
		$def = ($type == 'int') ? (int) $def : $def;
		$val = isset($_GET[$key]) ? $_GET[$key] : $def;
	}
	return $val;
}
//获取$_POST的指定数据
function _P($key, $type = 'string', $def = '') {
	if (isset($_POST[$key]) && is_array($_POST[$key])) {
		$val = $_POST[$key];
	} else {
		$def = ($type == 'int') ? (int) $def : $def;
		$val = isset($_POST[$key]) ? $_POST[$key] : $def;
	}
	return $val;
}
//获取$_SESSION的指定数据
function _S($key, $type = 'string', $def = '') {
	$def = ($type == 'int') ? (int) $def : $def;
	$val = isset($_SESSION[$key]) ? $_SESSION[$key] : $def;
	return $val;
}
//获取$_SESSION的指定数据
function _R($key, $type = 'string', $def = '') {
	$def = ($type == 'int') ? (int) $def : $def;
	$val = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $def;
	return $val;
}
//指获取指定变量数据
function getValue($fields, $method = 'POST') {
	$arrField = explode(',', $fields);
	$data = array();
	foreach ($arrField as $field) {
		switch ($method) {
		case 'POST':
			$data[$field] = _P($field);
			break;
		case 'GET':
			$data[$field] = _G($field);
			break;
		case 'SESSION':
			$data[$field] = _S($field);
			break;
		}
		if (!is_array($data[$field])) {
			$data[$field] = trim($data[$field]);
		} else {
			$data[$field] = implode(',', $data[$field]);
		}
	}
	return $data;
}
/**
 * 获得网站的URL地址
 *
 * @return  string
 */
function rwGetThisPageFullURL() {
	$QueryString = isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] != '' ? '?' . $_SERVER["QUERY_STRING"] : '';
	return rwGetHttpStr() . $_SERVER['HTTP_HOST'] . $_SERVER["PHP_SELF"] . $QueryString;
}
/*
获取中文首字母
 */
function rwFirstENChar($s0) {
	if (empty($s0)) {
		return '';
	}

	$fchar = ord($s0[0]);
	if ($fchar >= ord("A") and $fchar <= ord("z")) {
		return strtoupper($s0[0]);
	}

	$s1 = iconv("UTF-8", "gb2312", $s0);
	$s2 = iconv("gb2312", "UTF-8", $s1);
	if ($s2 == $s0) {$s = $s1;} else { $s = $s0;}
	$asc = ord($s[0]) * 256 + ord($s[0]) - 65536;
	if ($asc >= -20319 and $asc <= -20284) {
		return "A";
	}

	if ($asc >= -20283 and $asc <= -19776) {
		return "B";
	}

	if ($asc >= -19775 and $asc <= -19219) {
		return "C";
	}

	if ($asc >= -19218 and $asc <= -18711) {
		return "D";
	}

	if ($asc >= -18710 and $asc <= -18527) {
		return "E";
	}

	if ($asc >= -18526 and $asc <= -18240) {
		return "F";
	}

	if ($asc >= -18239 and $asc <= -17923) {
		return "G";
	}

	if ($asc >= -17922 and $asc <= -17418) {
		return "H";
	}

	if ($asc >= -17417 and $asc <= -16475) {
		return "J";
	}

	if ($asc >= -16474 and $asc <= -16213) {
		return "K";
	}

	if ($asc >= -16212 and $asc <= -15641) {
		return "L";
	}

	if ($asc >= -15640 and $asc <= -15166) {
		return "M";
	}

	if ($asc >= -15165 and $asc <= -14923) {
		return "N";
	}

	if ($asc >= -14922 and $asc <= -14915) {
		return "O";
	}

	if ($asc >= -14914 and $asc <= -14631) {
		return "P";
	}

	if ($asc >= -14630 and $asc <= -14150) {
		return "Q";
	}

	if ($asc >= -14149 and $asc <= -14091) {
		return "R";
	}

	if ($asc >= -14090 and $asc <= -13319) {
		return "S";
	}

	if ($asc >= -13318 and $asc <= -12839) {
		return "T";
	}

	if ($asc >= -12838 and $asc <= -12557) {
		return "W";
	}

	if ($asc >= -12556 and $asc <= -11848) {
		return "X";
	}

	if ($asc >= -11847 and $asc <= -11056) {
		return "Y";
	}

	if ($asc >= -11055 and $asc <= -10247) {
		return "Z";
	}

	return '';
}

/**
 * 获得当前的域名
 *
 * @return  string
 */
function rwGetDomain($url = '') {
	$url = $url != '' ? $url : $_SERVER['HTTP_HOST'];
	$host = strtolower($url);
	if (strpos($host, '/') !== false) {
		$parse = @parse_url($host);
		$host = $parse['host'];
	}
	$topleveldomaindb = array('com', 'edu', 'ink', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me');
	$str = '';
	foreach ($topleveldomaindb as $v) {
		$str .= ($str ? '|' : '') . $v;
	}
	$matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
	if (preg_match("/" . $matchstr . "/ies", $host, $matchs)) {
		$domain = $matchs['0'];
	} else {
		$domain = $host;
	}
	return $domain;
}

//取一级域名
function rwGetTopDomain() {
	$domain = rwGetDomain();
	$data = parse_url($domain);
	$data = isset($data['path']) ? $data['path'] : $data['host'];
	$data = explode('.', $data);
	$data = $data[count($data) - 2] . '.' . $data[count($data) - 1];
	return $data;
}

function rwGetHttpStr() {
	return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
}

/**
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
function rwSubStr($string, $length = 0, $append = true) {
	if (strlen($string) <= $length) {
		return $string;
	}
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut = '';
	if (strtolower(CHARSET) == 'utf-8') {
		$n = $tn = $noc = 0;
		while ($n < strlen($string)) {
			$t = ord($string[$n]);
			if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1;
				$n++;
				$noc++;
			} elseif (194 <= $t && $t <= 223) {
				$tn = 2;
				$n += 2;
				$noc += 2;
			} elseif (224 <= $t && $t < 239) {
				$tn = 3;
				$n += 3;
				$noc += 2;
			} elseif (240 <= $t && $t <= 247) {
				$tn = 4;
				$n += 4;
				$noc += 2;
			} elseif (248 <= $t && $t <= 251) {
				$tn = 5;
				$n += 5;
				$noc += 2;
			} elseif ($t == 252 || $t == 253) {
				$tn = 6;
				$n += 6;
				$noc += 2;
			} else {
				$n++;
			}
			if ($noc >= $length) {
				break;
			}
		}
		if ($noc > $length) {
			$n -= $tn;
		}
		$strcut = substr($string, 0, $n);
	} else {
		for ($i = 0; $i < $length; $i++) {
			$strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
		}
	}
	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	if ($append && $string != $strcut) {
		$strcut .= '...';
	}
	return $strcut;

}

/**
 * 获得用户的真实IP地址
 *
 * @return  string
 */
function rwGetIP() {
	static $realip = NULL;
	if ($realip !== NULL) {
		return $realip;
	}
	if (isset($_SERVER)) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			/* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
			foreach ($arr AS $ip) {
				$ip = trim($ip);
				if ($ip != 'unknown') {
					$realip = $ip;
					break;
				}
			}
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$realip = $_SERVER['HTTP_CLIENT_IP'];
		} else {
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$realip = $_SERVER['REMOTE_ADDR'];
			} else {
				$realip = '0.0.0.0';
			}
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$realip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$realip = getenv('HTTP_CLIENT_IP');
		} else {
			$realip = getenv('REMOTE_ADDR');
		}
	}

	preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
	$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
	return $realip;
}

/**
 * 获取服务器的ip
 *
 * @access      public
 *
 * @return string
 **/
function getServerIP() {
	static $serverip = NULL;

	if ($serverip !== NULL) {
		return $serverip;
	}
	if (isset($_SERVER)) {
		if (isset($_SERVER['SERVER_ADDR'])) {
			$serverip = $_SERVER['SERVER_ADDR'];
		} else {
			$serverip = '0.0.0.0';
		}
	} else {
		$serverip = getenv('SERVER_ADDR');
	}
	return $serverip;
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
if (!function_exists('addslashes_deep')) {
	function addslashes_deep($value) {
		if (empty($value)) {
			return $value;
		} else {
			return is_array($value) ? array_map('addslashes_deep', $value) : str_replace('</script>', '', str_replace('<script>', '', addslashes($value)));
		}
	}
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 * @author   Xuan Yan
 *
 * @return   mix                  对象或者数组
 */
if (!function_exists('addslashes_deep_obj')) {
	function addslashes_deep_obj($obj) {
		if (is_object($obj) == true) {
			foreach ($obj AS $key => $val) {
				if (($val) == true) {
					$obj->$key = addslashes_deep_obj($val);
				} else {
					$obj->$key = addslashes_deep($val);
				}
			}
		} else {
			$obj = addslashes_deep($obj);
		}

		return $obj;
	}
}
/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep($value) {
		if (empty($value)) {
			return $value;
		} else {
			return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
		}
	}
}
/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string       $str         待转换字串
 *
 * @return  string       $str         处理后字串
 */
function rwMakeSemiangle($str) {
	$arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
		'５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
		'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
		'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
		'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
		'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
		'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
		'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
		'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
		'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
		'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
		'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
		'ｙ' => 'y', 'ｚ' => 'z',
		'（' => '(', '）' => ')', '［' => '[', '］' => ']', '【' => '[',
		'】' => ']', '〖' => '[', '〗' => ']', '「' => '[', '」' => ']',
		'『' => '[', '』' => ']', '｛' => '{', '｝' => '}', '《' => '<',
		'》' => '>',
		'％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
		'：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
		'；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
		'＂' => '"', '＇' => '`', '｀' => '`', '｜' => '|', '〃' => '"',
		'　' => ' ');
	return strtr($str, $arr);
}

/**
 * 获得用户操作系统的换行符
 *
 * @access  public
 * @return  string
 */
function rwGetCrlf() {
/* LF (Line Feed, 0x0A, \N) 和 CR(Carriage Return, 0x0D, \R) */
	if (stristr($_SERVER['HTTP_USER_AGENT'], 'Win')) {
		$the_crlf = "\r\n";
	} elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Mac')) {
		$the_crlf = "\r"; // for old MAC OS
	} else {
		$the_crlf = "\n";
	}
	return $the_crlf;
}

/**
 * 创建目录（如果该目录的上级目录不存在，会先创建上级目录）
 * 依赖于 ROOT_PATH 常量，且只能创建 ROOT_PATH 目录下的目录
 * 目录分隔符必须是 / 不能是 \
 *
 * @param   string  $absolute_path  绝对路径
 * @param   int     $mode           目录权限
 * @return  bool
 */
function rwMkDir($absolute_path, $mode = 0777) {
	if (is_dir($absolute_path)) {
		return true;
	}
	$root_path = ROOT_PATH;
	$relative_path = str_replace($root_path, '', $absolute_path);
	$each_path = explode('/', $relative_path);
	$cur_path = $root_path; // 当前循环处理的路径
	foreach ($each_path as $path) {
		if ($path) {
			$cur_path = $cur_path . '/' . $path;
			if (!is_dir($cur_path)) {
				if (@mkdir($cur_path, $mode)) {
					fclose(fopen($cur_path . '/index.htm', 'w'));
				} else {
					return false;
				}
			}
		}
	}

	return true;
}

//带条件删除目录
//只删除文件时，不支持删除目录，$fileExt不为*时，$onlyFiles自动改为true,同时$deleteMe自动改为flase;
function rwDelTree($dirInfo, $fileExt = '*', $onlyFiles = true, $deleteMe = false) {
	if ($onlyFiles == true && $deleteMe == true) {
		return false;
	}
	if ($fileExt != "*") {
		$onlyFiles = true;
		$deleteMe = false;
	}
	$retVal = false;
	if (substr($dirInfo, -1) == "/") {
		//判断最后一个字符为/，则去掉
		$dirInfo = substr($dirInfo, 0, -1);
	}
	if (!is_file($dirInfo) || !is_dir($dirInfo)) {
//判断是否为一个存在的目录
		return false;
	} elseif (!is_readable($dirInfo)) {
//判断是否为只读
		return false;
	} else {
		$dirInfoHandle = opendir($dirInfo);
		while ($contents = readdir($dirInfoHandle)) {
			if ($contents != '.' && $contents != '..') {
				$path = $dirInfo . "/" . $contents;
				if (is_dir($path)) {
					rwDelTree($path, $fileExt, $onlyFiles, $deleteMe);
				} else {
					if ($fileExt == "*") {
						@unlink($path);
					} else {
						//echo $path;
						//echo strrchr($path,'.');
						$thisFileExt = strrchr($path, '.');
						if ($thisFileExt == '.' . $fileExt) {
							@unlink($path);
						}
					}
				}
			}
		}
		closedir($dirInfoHandle);
		if ($onlyFiles == false && $deleteMe == true) {
			if (!rmdir($dirInfo)) {
				return false;
			}
		}
		return true;
	}
}

/**
 *  设置COOKIE
 *
 *  @access public
 *  @param  string $key     要设置的COOKIE键名
 *  @param  string $value   键名对应的值
 *  @param  int    $expire  过期时间
 *  @return void
 */
function rwSetCookie($key, $value, $expire = 0, $cookie_path = '/', $cookie_domain = COOKIE_DOMAIN) {
	setcookie($key, $value, $_SERVER['REQUEST_TIME'] + 86400, $cookie_path, $cookie_domain);
}
function rwGetCookie($key) {
	return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
}

//加密数据
function rwEncode($data, $key = '') {
	if ($key == '') {
		$config = include CONF_PATH . 'comm.php';
		$key = $config['PRIVATE_KEY'];
	}
	return base64_encode(openssl_encrypt(json_encode($data, JSON_NUMERIC_CHECK), 'des-ecb', $key, true));
}
//解密数据
function rwDecode($data, $key = '') {
	if ($key == '') {
		$config = include CONF_PATH . 'comm.php';
		$key = $config['PRIVATE_KEY'];
	}
	if ($data == '') {
		return $data;
	}

	return json_decode(openssl_decrypt(base64_decode($data), 'des-ecb', $key, true), true);
}

//根据扩展名给出文件header类型
function rwGetContentType($fileType) {
	$fileType = strtolower($fileType);
	switch ($fileType) {
	case '.jpg':
		return 'image/jpeg';
		break;
	case '.jpeg':
		return 'image/jpeg';
		break;
	case '.gif':
		return 'image/gif';
		break;
	case '.png':
		return 'image/x-png';
		break;
	case '.js':
		return 'text/javascript';
		break;
	case '.json':
		return 'text/json';
		break;
	case ".exe":
		return "application/octet-stream";
		break;
	case ".zip":
		return "application/x-zip-compressed";
		break;
	case ".rar":
		return "application/x-rar";
		break;
	case ".dot":
		return "application/msword";
		break;
	case ".mp3":
		return "audio/mpeg";
		break;
	case ".mp4":
		return "audio/mp4";
		break;
	case ".mpeg":
		return "video/mpeg";
		break;
	case ".mpg":
		return "video/mpeg";
		break;
	case ".pdf":
		return "application/pdf";
		break;
	case ".ppt":
		return "application/vnd.ms-powerpoint";
		break;
	case ".rtf":
		return "application/rtf";
		break;
	case ".txt":
		return "text/plain";
		break;
	case ".wps":
		return "application/vnd.ms-works";
		break;
	case ".xlc":
		return "application/vnd.ms-excel";
		break;
	case ".xls":
		return "application/vnd.ms-excel";
		break;
	case ".xml":
		return "text/xml";
		break;
	}
}
//验证指定长度字符
function rwCheckString($min, $max, $str) {
	return (preg_match("/^[a-zA-Z0-9]{" . $min . "," . $max . "}$/", $str)) ? true : false;
}

/**
 * 验证指定长度和编码的字符串
 *
 * @Time        :   2015-07-13 15:12
 * @author      :   梁德杰
 * @param   none
 */
function rwCheckText($min, $max, $str, $charset = 'utf-8') {
	$len = mb_strlen($str, $charset);
	return ($len >= $min && $len <= $max) ? true : false;
}

//验证是否为指定长度数字
function rwCheckInt($num1, $num2, $str) {
	return (preg_match("/^[0-9]{" . $num1 . "," . $num2 . "}$/i", $str)) ? true : false;
}

/**
 * 验证是否浮点数
 *
 * @Time        :   2016-10-21 11:08
 * @author      :   许旭
 * @param   $str    要判断的数据
 */
function rwCheckFloat($str) {
	return (preg_match("/^\-?\d+(\.\d+)?$/", $str)) ? true : false;
}

/* 检查是否为一个合法的时间格式
 *
 * @param   string  $time
 * @return  void
 */
function rwCheckTime($time) {
	$pattern = '/[\d]{4}-[\d]{1,2}-[\d]{1,2}\s[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/';
	return preg_match($pattern, $time);
}
//验证是否为指定长度汉字
function rwCheckCnChar($num1, $num2, $str) {
	return (preg_match("/^([\x81-\xfe][\x40-\xfe]){" . $num1 . "," . $num2 . "}$/", $str)) ? true : false;
}
function rwCheckEnChar($num1, $num2, $str) {
	return (preg_match("/^([a-zA-Z]){" . $num1 . "," . $num2 . "}$/", $str)) ? true : false;
}
//验证身份证号码
function rwCheckIDCard($str) {
	return (preg_match('/(^\d{15}$/)|(\d{17}(?:\d|x|X)$/', $str)) ? true : false;
}
//验证邮件地址
function rwCheckEmail($str) {
	return (preg_match('/^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$/', $str)) ? true : false;
}
//验证电话号码
function rwCheckTel($str) {
	return (preg_match("/^((\(\d{3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}$/", $str)) ? true : false;
}
//验证手机号码
function rwCheckMobile($Mobile) {
	return (preg_match('/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9]|9[0-9])[0-9]{8}$/A', $Mobile)) ? true : false;
}
//验证邮编
function rwCheckPostCode($str) {
	return (preg_match("/^[1-9]\d{5}$/", $str)) ? true : false;
}
//验证url地址
function rwCheckURL($str) {
	return (preg_match("/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/", $str)) ? true : false;
}
//验证密码有效性
function rwCheckPassword($value, $minLen = 5, $maxLen = 16) {
	//$match='/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{'.$minLen.','.$maxLen.'}$/';
	//2016-11-16 17:07:48 Xux
	$match = '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"]{' . $minLen . ',' . $maxLen . '}$/';
	$v = trim($value);
	if (empty($v)) {
		return false;
	}

	return preg_match($match, $v);
}
/**
 * 验证是否符合昵称标准
 * @time        :   2016-11-28 17:13:22
 * @author      :   许旭
 * @param   $str    要判断的数据
 */
function rwCheckNikeName($str, $minLen = 2, $maxLen = 10) {
	return (preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]{' . $minLen . ',' . $maxLen . '}$/u', $str)) ? true : false;
}

/**
 * 验证是否符合账户/用户名标准
 * @time        :   2016-11-28 17:13:22
 * @author      :   许旭
 * @param   $str    要判断的数据
 */
function rwCheckAccount($str, $minLen = 5, $maxLen = 15) {
	return (preg_match('/^[a-zA-Z]{1}[0-9a-zA-Z]{' . ($minLen - 1) . ',' . ($maxLen - 1) . '}$/', $str)) ? true : false;
}

// 数据入库 转义 特殊字符 传入值可为字符串 或 一维数组
function rwDataIn(&$data) {
	if (get_magic_quotes_gpc() == false) {
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$data[$k] = addslashes($v);
			}
		} else {
			$data = addslashes($data);
		}
	}
	return $data;
}
// 数据出库 还原 特殊字符 传入值可为字符串 或 一/二维数组
function rwDataOut(&$data) {
	if (is_array($data)) {
		foreach ($data as $k1 => $v1) {
			if (is_array($v1)) {
				foreach ($v1 as $k2 => $v2) {
					$data[$k1][$k2] = stripslashes($v2);
				}
			} else {
				$data[$k1] = stripslashes($v1);
			}
		}
	} else {
		$data = stripslashes($data);
	}
	return $data;
}
// 数据显示 还原 数据格式 主要用于内容输出 传入值可为字符串 或 一/二维数组
// 执行此方法前应先data_revert()，表单内容无须此还原
function rwDataShow(&$data) {
	if (is_array($data)) {
		foreach ($data as $k1 => $v1) {
			if (is_array($v1)) {
				foreach ($v1 as $k2 => $v2) {
					$data[$k1][$k2] = nl2br(htmlspecialchars($data[$k1][$k2]));
					$data[$k1][$k2] = str_replace(" ", " ", $data[$k1][$k2]);
					$data[$k1][$k2] = str_replace("\n", "<br>\n", $data[$k1][$k2]);
				}
			} else {
				$data[$k1] = nl2br(htmlspecialchars($data[$k1]));
				$data[$k1] = str_replace(" ", " ", $data[$k1]);
				$data[$k1] = str_replace("\n", "<br>\n", $data[$k1]);
			}
		}
	} else {
		$data = nl2br(htmlspecialchars($data));
		$data = str_replace(" ", " ", $data);
		$data = str_replace("\n", "<br>\n", $data);
	}
	return $data;
}

function getFormaterFunc($keyName, $keyType = 'text') {
	switch ($keyType) {
	case 'css':
		$showTitle = 'css';
		break;
	case 'img':
		$showTitle = 'img';
		break;
	default:
		$showTitle = 'text';
	}
	$jsStr = "(function(value,row,index){
			return rwJS.formatParam(value,'" . $keyName . "','" . $keyType . "');
		})";
	return $jsStr;
	//
}
function getFormaterTextFunc($type, $params1 = '', $params2 = '') {
	$functionStr = "( function(value,row,index){
				try{ ";
	switch ($type) {
	case 'numberFormat':
		$functionStr .= '	return fnumber(value);';
		break;
	case 'moneyFormat':
		$functionStr .= '	return fmoney(value,' . $params1 . ');';
		break;
	case 'dateFormater':
		$functionStr .= '	return fDate(value,' . $params1 . ');';
		break;
	default:

	};
	$functionStr .= "}catch(e){
					return value;
				}
				})";
	return $functionStr;
}

function arrayToTree($sourceArr, $key, $parentKey, $childrenKey, $pid = 0) {
	$ret = array();
	foreach ($sourceArr as $k => $v) {
		if ($v[$parentKey] == $pid) {
			$tmp = $sourceArr[$k];unset($sourceArr[$k]);
			$tmp[$childrenKey] = arrayToTree($sourceArr, $key, $parentKey, $childrenKey, $v[$key]);
			if (empty($tmp[$childrenKey])) {
				unset($tmp[$childrenKey]);
			}

			$ret[] = $tmp;
		}
	}
	return $ret;
}
/**
 * 取得输入目录所包含的所有文件
 * 以数组形式返回
 * author: flynetcn
 */
function rwGetFiles($dir, $baseURL, $scanSubDir = true, $fileExt = '') {
	if ($fileExt != '') {
		$arrFileExt = explode(',', $fileExt);
	} else {
		$arrFileExt = '';
	}
	if (is_file($dir)) {
		return array($dir);
	}
	$files = array();
	if (is_dir($dir) && ($dir_p = opendir($dir))) {
		while (($filename = readdir($dir_p)) !== false) {
			if ($filename == '.' || $filename == '..') {continue;}
			$realPath = $dir;
			$fileName = $filename;
			$realFile = $realPath . DIRECTORY_SEPARATOR . $fileName;
			$filetype = filetype($dir . DIRECTORY_SEPARATOR . $filename);
			if (is_array($arrFileExt)) {
				$fExt = strtolower(strrchr($fileName, '.'));
				if (!in_array($fExt, $arrFileExt)) {
					continue;
				}

			}
			if ($filetype == 'dir') {
				if ($scanSubDir) {
					$files = array_merge($files, rwGetFiles($dir . $ds . $filename, $baseURL . $filename . '/', $scanSubDir, $fileExt));
				} else {
					continue;
				}
			} elseif ($filetype == 'file') {
				$files[] = array(
					'fileName' => $fileName,
					'url' => $baseURL . $fileName,
				);
			}
		}
		closedir($dir_p);
	}
	return $files;

}

function rwGetFileURL($files, $w = 0, $h = 0, $overTime = 0, $watermark = false, $isDownload = false, $downNewName = '') {
	if ($files == '') {
		return false;
	}
	$arrFiles = explode(',', $files);
	unset($arrReturnURL);
	foreach ($arrFiles as $FileID) {
		if ((int) $FileID == 0) {
			continue;
		}

		unset($FileInfo);
		$FileInfo['FileID'] = (int) $FileID;
		$FileInfo['w'] = (int) $w;
		$FileInfo['h'] = (int) $h;
		$FileInfo['OverTime'] = (int) $overTime;
		$arrReturnURL[] = rwEncode($FileInfo);
	}
	return $arrReturnURL;
}

/*
 * 生成二维码
 * params   $data string 需要放进二维码的内容
 * params   $type int 默认1
 *                      为1时直接输出图片至浏览器
 *                      为2时返回图片路径（此功能未开发）
 *                      为3时返回文件数据流
 * params   $size int 默认4(长宽100px) 范围1-28 这个值与像素的比例是 1:23
 * params   $magrin int 边距 默认为5 单位：像素 最好是5的整数倍
 * params   $errorCorrectionLevel string 错误处理等级 'L','M','Q','H' 默认'L'
 * */
function rwQrcode($data, $type = 1, $size = 4, $magrin = 5, $errorCorrectionLevel = 'L') {
	if (!empty($data)) {
		include FRAME_PATH . 'lib/phpqrcode/qrlib.php';

		if (!in_array($errorCorrectionLevel, array('L', 'M', 'Q', 'H'))) {
			exit('请设置正确的错误处理等级');
		}
		if (isset($size)) {
			$size = min(max((int) $size, 1), 28);
		}

		$magrin = $magrin / 5;

		if ($type == 1) {
			$filename = false;
			ob_end_clean();
			QRcode::png($data, $filename, $errorCorrectionLevel, $size, $magrin);
		}
		//TODO 文件存放路径，以后需要用到此功能在此加代码
		/*if($type == 2){
	            $filename = '图片路径';
*/
		if ($type == 3) {
			$filename = 'dataStream';
			return QRcode::data($data, $filename, $errorCorrectionLevel, $size, $magrin);
		}

		if ($type == 2) {
			return $filename;
		}

	}
}

/*
 * 下载
 * params   $file_url string 文件地址
 * params   $new_name string 文件下载重命名
 * params   $ext string 自定义后缀名 不用带.符符号
 */
function rwDownload($file_url, $new_name = '', $ext = '') {
	if (!isset($file_url) || trim($file_url) == '') {
		return '500';
	}

	$file_name = basename($file_url);
	if (empty($ext)) {
		$file_type = explode('.', $file_url);
		$file_type = $file_type[count($file_type) - 1];
	} else {
		$file_type = $ext;
	}
	$file_name = trim($new_name == '') ? $file_name : $new_name . '.' . $file_type;

	//下载远程文件
	if (rwCheckURL($file_url)) {
		$content = file_get_contents($file_url); //取得远程文件流
		$length = strlen($content); //取得字符串长度，即文件大小，单位是字节
		header('Content-Type: application/octet-stream'); //告诉浏览器输出内容类型，必须
		header("Accept-Ranges: bytes");
		header("Content-Length: " . $length); //告诉浏览器文件大小，可选
		header('Content-Disposition: attachment; filename="' . $file_name . '"');
		echo $content;
	}
	//下载本地文件
	else {
		if (!is_file($file_url)) {
			return '404';
		}
		$file = fopen($file_url, 'r'); //打开文件
		//输入文件标签
		header("Content-type: application/octet-stream");
		header("Accept-Ranges: bytes");
		header("Accept-Length: " . filesize($file_url));
		header("Content-Disposition: attachment; filename=" . $file_name);
		//输出文件内容
		echo fread($file, filesize($file_url));
		fclose($file);
	}
}

/*
 * 补全字符
 * @param   string  $str 需要补全的字符串
 * @param   string  $def 补全的字符
 * @param   int     $lenght 总长度
 * @param   string  $way    补全的方向 left从左补 right从右补
 *
 * */
function ContactCharacter($str, $def = '0', $length = 4, $way = 'left') {
	$str_len = strlen($str);
	$real_str = $str;
	$plus = $length - $str_len;
	$zero = '';
	if ($plus > 0) {
		for ($i = 0; $i < ($length - $str_len); $i++) {
			$zero .= $def;
		}
		if ($way == 'left') {
			$real_str = $zero . $str;
		}
		if ($way == 'right') {
			$real_str = $str . $zero;
		}
	}
	return $real_str;
}

/*
生成随机串，传入参数
num,字串长度
type
0区别难识别的字符串
1	//纯数字
2	//纯字母
3	//数字字母组合
 */
function rwRandStr($num, $type = '0') {
	if ($type == 0) {
		$charList = 'acdefghjkmnprstuvwxy34578';
	} elseif ($type == 1) {
		$charList = '1234567890';
	} elseif ($type == '2') {
		$charList = 'abcdefghijklmnopqrstuvwxyz';
	} elseif ($type == '3') {
		$charList = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz';
	}
	$returnStr = '';
	$num = (int) $num;
	for ($i = 0; $i < $num; $i++) {
		$returnStr .= $charList[mt_rand(0, strlen($charList) - 1)];
	}
	return $returnStr;
}

/**
 * 格式化文件大小显示
 * @param int $size
 * @return string
 */
function rwFormatSize($size) {
	$prec = 3;
	$size = round(abs($size));
	$units = array(
		0 => "B",
		1 => "KB",
		2 => "MB",
		3 => "GB",
		4 => "TB",
	);
	if ($size == 0) {
		return str_repeat(" ", $prec) . "0$units[0]";
	}

	$unit = min(4, floor(log($size) / log(2) / 10));
	$size = $size * pow(2, -10 * $unit);
	$digi = $prec - 1 - floor(log($size) / log(10));
	$size = round($size * pow(10, $digi)) * pow(10, -$digi);
	return $size . $units[$unit];
}

/*
 * 增强var_dump
 * */
function rwDump($data) {
	echo '<pre>';
	var_dump($data);
}

/*
 * 图片裁剪 缩略图
 * @params  $srcFile    string  源文件路径
 * @params  $destFile   string  目标文件路径
 * @params  $ImgWH      int     默认缩略图宽度高度
 * @params  $ImgW       int     缩略图宽度
 * @params  $ImgH       int     缩略图高度
 * @params  $Qtype      int     1为没设置宽度高度时候取默认宽度 2为取指定宽度高度值
 * @params  $ImgGD      string  图形组件版本 GD1 或者 GD2
 * */
function rwMakeThumbImages($srcFile, $destFile, $ImgWH = 120, $ImgW = "", $ImgH = "", $Qtype = 1, $ImgGD = "GD2") {
	$ImgInfo = GetImageSize($srcFile);
	$srcWidth = $ImgInfo[0];
	$srcHeight = $ImgInfo[1];
	if ($Qtype == 1) {
		if ($ImgW != "") {
			$ratio = min($srcWidth, $srcHeight) / max($ImgW, $ImgH);
		} else {
			$ratio = max($srcWidth, $srcHeight) / $ImgWH;
		}

		$ratio = max($ratio, 1.0);
		$destWidth = (int) ($srcWidth / $ratio);
		$destHeight = (int) ($srcHeight / $ratio);

	} elseif ($Qtype == 2) {
		$destWidth = $ImgW;
		$destHeight = $ImgH;
	}
	switch ($ImgInfo[2]) {
	case 1:
		$SrcImg = ImageCreateFromGIF($srcFile);
		break;
	case 2:
		$SrcImg = imagecreatefromjpeg($srcFile);
		break;
	case 3:
		$SrcImg = ImageCreateFromPNG($srcFile);
		break;
	}
#### 使用图形组件版本 2.0以下
	if ($ImgGD == "GD1") {
		$DstImg = imagecreate($destWidth, $destHeight);
		imagecopyresized($DstImg, $SrcImg, 0, 0, 0, 0, $destWidth, (int) $destHeight, $srcWidth, $srcHeight);
		switch ($ImgInfo[2]) {
		case 1:
			imagegif($DstImg, $destFile);
			break;
		case 2:
			imagejpeg($DstImg, $destFile, 100);
			break;
		case 3:
			imagepng($DstImg, $destFile);
			break;
		}
		imagedestroy($SrcImg);
		imagedestroy($DstImg);
	}
#### 使用图形组件版本 2.0以上
	if ($ImgGD == "GD2") {
		$DstImg = imagecreatetruecolor($destWidth, $destHeight);
		imagealphablending($DstImg, false);
		imagesavealpha($DstImg, true);
		imagecopyresampled($DstImg, $SrcImg, 0, 0, 0, 0, $destWidth, (int) $destHeight, $srcWidth, $srcHeight);
		switch ($ImgInfo[2]) {
		case 1:
			imageGIF($DstImg, $destFile);
			break;
		case 2:
			imageJPEG($DstImg, $destFile, 100);
			break;
		case 3:
			imagePNG($DstImg, $destFile);
			break;
		}
	}

}

/* 图片处理
 * @author Xux
 * @time 2016-12-7 20:41:26
 * @param int $imgID  图片ID
 * @param int $width   输出图片规格
 * @param int $height   输出图片规格
 * @return string $imgUrl 图片链接
 */
function makeImage($imgID, $width = 0, $height = 0, $defaultImage = "") {
	$width = (int) $width;
	$height = (int) $height;
	if ($width == 0 && $height == 0) {
		$otherPath = '';
	} else {
		$otherPath = '_' . $width . '_' . $height;
	}

	$cacheFiles = FILES_PATH . 'upload/cache/' . $imgID . $otherPath . '.cache';
	if (!file_exists($cacheFiles)) {
		$FileInfoCode = array(
			'FileID' => $imgID,
			'w' => $width,
			'h' => $height,
			'o' => 0,
		);
		$path = rwEncode($FileInfoCode);
		$path = urlencode($path);
		$imgUrl = FILES_URL . '?f=' . $path;
	} else {
		$imgUrl = file_get_contents($cacheFiles);
	}
	return $imgUrl;
}

/* 附件标签处理
 * @author Zhangjw
 * @time 2018-01-10 18:11:11
 * @param int $imgID  图片ID
 * @param int $size   输出图片规格
 * @param int $overTime 过期时间戳
 * @return string $imgUrl 图片链接
 */
function rwAttachToHtml($Content) {
	if (strpos($Content, '<rwattach>')) {
		preg_match_all('/<rwattach>(.*?)<\/rwattach>/s', $Content, $match);
		foreach ($match[1] as $key => $value) {
			$Content = str_replace(trim($match[0][$key]), '<img src="' . makeImage($value) . '"/>', $Content);
		}
	}
	return $Content;
}

//获取内容分页
/*
 * Author:Zhucp
 * Date:2016-12-01
 * @params $totalCount  内容总数
 * @params $page  目标页数,代表要显示哪一页
 * @params $rows  每一页要显示的内容个数
 * @params $labelNum 分页栏的按钮数量,显示10页或者显示5页,自由设定
 *@params  $keyArr url要附带的参数数组,在url上组合出 key=value 的字符串
 */
function pagination($totalCount, $page = 1, $rows = 20, $labelNum = 10, $keyArr = array()) {
	//如果总数是0 直接返回空数组
	$result['total'] = 0;
	$result['rows'] = array();
	if ($totalCount < 1 || $totalCount <= $rows) {
		return $result;
	}
	//算出有多少页,向上取整数

	$pageCount = ceil($totalCount / $rows);
	//最后一页就是总数
	$lastPage = $pageCount;
	$i = 1;
	//可显示页数超过要求的页数,则缩小为要求的页数
	$pageCount = ($pageCount > $labelNum) ? $labelNum : $pageCount;
	if ($page > $labelNum - 1) {
//目标页按钮超出要求的页数按钮-1,则需要重新计算按钮显示范围
		$beforeCount = floor($labelNum / 2); //向下取整,这样可以使目标页按钮处于中间位置
		$i = $page - $beforeCount;
		$pageCount = $i + $labelNum;
		if ($pageCount > $lastPage) {
			$pageCount = $lastPage;
			$i = $lastPage - $labelNum;
		}
	}
	if ($i <= 0) {
		$i = 1;
	}
	$pagination = array();
	$url = '';
	foreach ($keyArr as $key => $value) {
		$url .= $key . '=' . $value . '&';
	}
	for ($i; $i <= $pageCount; $i++) {
		$pagination[$i]['labelName'] = $i;
		$pagination[$i]['disabled'] = '';
		$pagination[$i]['active'] = '';
		if ($page == $i) {
			$pagination[$i]['active'] = 'active';
		}
		$pagination[$i]['url'] = '?' . $url . 'page=' . $i;
	}
	$preLabel['labelName'] = '上一页';
	$prePage = $page - 1;
	$preLabel['url'] = '?' . $url . 'page=' . $prePage;
	$preLabel['disabled'] = '';
	if ($page == 1) {
		$preLabel['disabled'] = 'disabled';
		$preLabel['url'] = 'javascript:;';
	}
	$preLabel['active'] = '';
	array_unshift($pagination, $preLabel);
	$nextLabel['labelName'] = '下一页';
	$nextPage = $page + 1;
	$nextLabel['url'] = '?' . $url . 'page=' . $nextPage;
	$nextLabel['disabled'] = '';
	if ($page == $lastPage) {
		$nextLabel['disabled'] = 'disabled';
		$nextLabel['url'] = 'javascript:;';
	}
	$nextLabel['active'] = '';
	$pagination[] = $nextLabel;
	$result['total'] = $pageCount;
	$result['rows'] = $pagination;
	return $result;
}
//判断是否在手机端
function inMobile() {
	if (defined('IN_MOBILE')) {
		return IN_MOBILE;
	}

	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		$is_mobile = false;
	} elseif (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
// 先检查是否为wap代理，准确度高
		$is_mobile = true;
	} elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML") > 0) {
// 检查浏览器是否接受 WML.
		$is_mobile = true;
	} elseif (preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
//检查USER_AGENT
		$is_mobile = true;
	} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false// many mobile devices (all iPhone, iPad, etc.)
		 || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
		$is_mobile = true;
	} else {
		$is_mobile = false;
	}
	define('IN_MOBILE', $is_mobile);
	return $is_mobile;
}
//判断是否Ajax请求
function inAJAX() {
	if (isset($this->isAJAXrequest)) {
		return $this->isAJAXrequest;
	}

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		$this->isAJAXrequest = true;
	} else {
		$this->isAJAXrequest = false;
	}

	return $this->isAJAXrequest;
}
function inWechat() {
	if (defined('IN_WECHAT')) {
		return IN_WECHAT;
	}

	//不带$_GET[fromWXID]或$_SESSION['fromWXID'] 也视为不在微信端
	if (empty($_GET['fromWXID']) && empty($_SESSION['fromWXID'])) {
		define('IN_WECHAT', false);
		return false;
	}
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	if (strpos($user_agent, 'MicroMessenger') === false) {
		define('IN_WECHAT', false);
		return false;
	} else {
		define('IN_WECHAT', true);
		return true;
	}
}
function rwPostEmail($toEmail, $Title = '', $body = '', $arrAttachment = array()) {
	require_once $this->getConf('email', 'EMAILER_PATH') . 'PHPMailerAutoload.php';
	$mail = new PHPMailer();
//	$mail->SMTPDebug = 3;
	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->Host = $this->getConf('email', 'SMTP_SERVER'); // sets the SMTP server
	$mail->Port = $this->getConf('email', 'SMTP_PORT'); // set the SMTP port for the GMAIL server
	$mail->SetFrom($this->getConf('email', 'MAIL_FROM'), $this->getConf('email', 'MAIL_NAME'));

	$mail->CharSet = $this->getConf('email', 'EMAIL_CHARSET');
	if ($this->getConf('email', 'SMTP_AUTH')) {
		$mail->SMTPAuth = $this->getConf('email', 'SMTP_AUTH'); // enable SMTP authentication
		$mail->Username = $this->getConf('email', 'SMTP_USERNAME'); // SMTP account username
		$mail->Password = $this->getConf('email', 'SMTP_PASSWORD'); // SMTP account password
	}
	//$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
	if ($this->getConf('email', 'SMTP_SSL')) {
		$mail->SMTPSecure = "ssl";
	}

	$mail->Subject = $Title;
	$mail->AltBody = '请使用HTML兼容的电子邮件查看器！'; // optional, comment out and test
	$mail->isHTML(true);
	$mail->MsgHTML($body);
	$mail->AddAddress($toEmail, '');
	foreach ($arrAttachment as $arrAttachment) {
		$mail->AddAttachment($arrAttachment); // attachment
	}
	if (!$mail->Send()) {
		return 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		return true;
	}
}
function getCurlData($url, $data = '') {
	// 初始化curl
	$ch = curl_init();
	//设置超时
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	if ($data != '') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	//运行curl，结果以json形式返回
	$res = curl_exec($ch);
	curl_close($ch);
	return $res;

}
function rwExitJSON($val, $type = JSON_UNESCAPED_SLASHES) {
	ob_end_clean();
	header('Content-type: application/json');
	header("Access-Control-Allow-Origin: *");
	echo json_encode($val, $type);
	exit;
}
function rwResultJSON($val) {
	return json_encode($val, JSON_NUMERIC_CHECK);
}
function isCrawler() {
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if (!empty($agent)) {
		$spiderSite = array(
			"TencentTraveler",
			"Baiduspider+",
			"BaiduGame",
			"Googlebot",
			"msnbot",
			"Sosospider+",
			"Sogou web spider",
			"ia_archiver",
			"Yahoo! Slurp",
			"YoudaoBot",
			"Yahoo Slurp",
			"MSNBot",
			"Java (Often spam bot)",
			"BaiDuSpider",
			"Voila",
			"Yandex bot",
			"BSpider",
			"twiceler",
			"Sogou Spider",
			"Speedy Spider",
			"Google AdSense",
			"Heritrix",
			"Python-urllib",
			"Alexa (IA Archiver)",
			"Ask",
			"Exabot",
			"Custo",
			"OutfoxBot/YodaoBot",
			"yacy",
			"SurveyBot",
			"legs",
			"lwp-trivial",
			"Nutch",
			"StackRambler",
			"The web archive (IA Archiver)",
			"Perl tool",
			"MJ12bot",
			"Netcraft",
			"MSIECrawler",
			"WGet tools",
			"larbin",
			"Fish search",
		);
		foreach ($spiderSite as $val) {
			$str = strtolower($val);
			if (strpos($agent, $str) !== false) {
				return true;
			}
		}
	} else {
		return false;
	}
}
//判断输入框不能为空
//$dataType=P,G,S,R对应着$_POST,$_GET,$_SESSION,$_REQUEST
//checkType见下面具体方法 ，暂未写全，需要后补
function checkForm($title, $field, $dataVal, $checkType, $param1 = '', $param2 = '', $param3 = '') {
	$rwResult['status'] = 1;
	$rwResult['errorField'] = $field;
	$rwResult['errorMsg'] = '';
	switch ($checkType) {
	case 'isEmpty':
		if ((is_array($dataVal) && !count($dataVal)) || is_null($dataVal) || @trim($dataVal) === '') {
			if ($param1) {
				$rwResult['errorMsg'] .= $param1;
			} else {
				$rwResult['errorMsg'] .= $title . '不能为空';
			}
			rwExitJSON($rwResult);
		}
		break;
	case 'isPwd':
		$param1 = (int) (empty($param1) ? 8 : $param1);
		$param2 = (int) (empty($param2) ? 16 : $param2);
		if (!rwCheckPassword($dataVal, $param1, $param2)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须由' . $param1 . '至' . $param2 . '个字母、数字或常用符号混合组成。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isString':
		$param1 = !empty($param1) ? $param1 : '1';
		$param2 = !empty($param2) ? $param2 : '255';
		if (!rwCheckString($param1, $param2, $dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是' . $param1 . '至' . $param2 . '个字符的字母或者数字';
			rwExitJSON($rwResult);
		}
		break;
	case 'isText': // 梁德杰 2015-07-13 14:58
		$param1 = !empty($param1) ? $param1 : '1';
		$param2 = !empty($param2) ? $param2 : '255';
		if (!rwCheckText($param1, $param2, $dataVal, $param3 = 'utf-8')) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是' . $param1 . '至' . $param2 . '个字符';
			rwExitJSON($rwResult);
		}
		break;
	case 'isInt':
		$param1 = !empty($param1) ? $param1 : '0';
		$param2 = !empty($param2) ? $param2 : '10';
		if (!rwCheckInt($param1, $param2, $dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是' . pow(10, $param1) . '至' . pow(10, $param2) . '位的数字';
			rwExitJSON($rwResult);
		}
		break;
	case 'isFloat': //许旭 2016-10-21 11:12

		if (!rwCheckFloat($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是浮点数。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isDateTime':
		if (!rwCheckTime($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”格式不对，示例：' . date('Y-m-d H:i:s');
			rwExitJSON($rwResult);
		}
		break;
	case 'isDate':
		break;
	case 'isChinese':
		$param1 = !empty($param1) ? $param1 : '1';
		$param2 = !empty($param2) ? $param2 : '255';

		if (!rwCheckCnChar($param1, $param2, $dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是' . $param1 . '至' . $param2 . '位的中文';
			rwExitJSON($rwResult);
		}
		break;
	case 'isEnglish':
		$param1 = !empty($param1) ? $param1 : '1';
		$param2 = !empty($param2) ? $param2 : '255';

		if (!rwCheckEnChar($param1, $param2, $dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须是' . $param1 . '至' . $param2 . '位的半角大小写英文字母';
			rwExitJSON($rwResult);
		}
		break;
	case 'isTel':
		if (!rwCheckTel($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isIDCard':
		if (!rwCheckIDCard($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isEmail':
		if (!rwCheckEmail($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isURL':
		if (!rwCheckURL($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法，必须是http:开头。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isPostCode':
		if (!rwCheckPostCode($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法，必须是6位数字。';
			rwExitJSON($rwResult);
		}
		break;
	case 'isMobile':
		if (!rwCheckMobile($dataVal)) {
			$rwResult['errorMsg'] .= '“' . $title . '”不合法。';
			rwExitJSON($rwResult);
		}

		break;
	case 'isAccount':
		$param1 = !empty($param1) ? $param1 : '5';
		$param2 = !empty($param2) ? $param2 : '12';
		if (!rwCheckAccount($dataVal, $param1, $param2)) {
			$rwResult['errorMsg'] .= '“' . $title . '”必须由' . $param1 . '至' . $param2 . '个字母开头的字母或者数字组成';
			rwExitJSON($rwResult);
		}
		break;
	default:
		$rwResult['status'] = 0;
		$rwResult['errorField'] = $field;
		$rwResult['errorMsg'] = '验证成功';
		return;
	}
}
//获取本地文件，并加入304缓存
function getFile($fileName, $contentType) {
	$contentSize = filesize($fileName);
	$contentType = rwGetContentType($contentType);
	$contentChangeTime = filemtime($fileName);
	$clientTime = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
	ob_clean();
	header('Pragma: ');
	header('Cache-control:private');
	header('Content-Type: ' . $contentType);
	header('Content-disposition:inline; filename=UTF8*"' . urlencode($fileName) . '"');
	if ($clientTime == $contentChangeTime) {
		header("HTTP/1.1 304 Not Modified"); //服务器发出文件不曾修改的指令
		exit();
	}
	$content = file_get_contents($fileName);
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $contentChangeTime) . ' GMT');
	header('Content-Length: ' . $contentSize);
	echo $content;
	exit;
}

function rwChineseNameEncode($user_name) {
	$strlen = mb_strlen($user_name, 'utf-8');
	$firstStr = mb_substr($user_name, 0, 1, 'utf-8');
	$lastStr = mb_substr($user_name, -1, 1, 'utf-8');
	return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}

/**
 * 断点调试
 * @param  mix $data   数据
 * @return mix $reutrn 结果集
 */
function p($data = '') {
	echo '<pre>';
	var_dump($data);
	exit;
}

function check_data($arr, $v) {
	foreach ($arr as $key => $value) {
		if (!is_array($key)) {check($key, $v);} else {check_data($key, $v);}

		if (!is_array($value)) {check($value, $v);} else {check_data($value, $v);}
	}
}

function check($str, $v) {
	foreach ($v as $key => $value) {
		if (preg_match("/" . $value . "/is", $str) == 1 || preg_match("/" . $value . "/is", urlencode($str)) == 1) {
			//W_log();
			print "您的提交带有不合法参数,谢谢合作";
			$this->log("<br>IP: " . $_SERVER["REMOTE_ADDR"] . "<br>时间: " . strftime("%Y-%m-%d %H:%M:%S") . "<br>页面:" . $_SERVER["PHP_SELF"] . "<br>提交方式: " . $_SERVER["REQUEST_METHOD"] . "<br>提交数据: " . $str);
			exit();
		}
	}
}

/**
 * [_formatNumber 价格格式化数据，不进行四舍五入]
 * @param  integer $number        [数值]
 * @param  string  $number_format [格式化]
 * @return [type]                 [description]
 */
function rwPriceFormat($number = 0, $length = 4) {
	if (stripos($number, 'e') !== false) {
		return scientificNumToString($number, $length);
	}

	$max = strstr($number, '.', true);
	if ($max === false) {
		return $number;
	}

	$min = substr(strstr($number, '.'), 1, $length);
	$newNum = $max . '.' . $min;
	return $newNum;
}

/**
 * 科学计数法转数字字符串
 *
 * @param integer $num 科学计数法数字，比如：4.0E-6
 * @param integer $decimals 保留小数位数
 * @return string
 * @author Wenhui Shen <swh@admpub.com>
 */
function scientificNumToString($num, $decimals = 5) {
	$a = explode('e', strtolower($num));
	return bcmul($a[0], bcpow(10, $a[1], $decimals), $decimals);
}

/**
 * 整理id字符串,
 * @param  string  $idstr  如1，2，3
 * @return string
 */
function rwTidyIds($idstr) {
	$idarr = explode(',', $idstr);

	foreach ($idarr as $key => $value) {
		$idarr[$key] = (int) $value;
	}

	return implode(',', $idarr);
}
?>