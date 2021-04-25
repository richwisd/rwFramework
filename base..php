<?php
namespace Richwisd;
class Base {
	function __construct($app) {
		//以下为第一次加载时运行，如果再次被加载，将不被运行
		if (defined('RUN_CLIENT')) {
			return;
		}
		$this->logFile = DATA_PATH . 'logs/' . APP_NAME . '_log' . date('Ymd', PAGE_TIME) . '.log';
		defined('COOKIE_DOMAIN') or define('COOKIE_DOMAIN', '.' . SUPER_DOMAIN);
		defined('RUN_CLIENT') or define('RUN_CLIENT', php_sapi_name() == 'cli' ? true : false);
		if (!RUN_CLIENT) {
			define('RUN_DOMAIN', $_SERVER['SERVER_NAME']);
			//如果是在浏览器中运行时，需要防SQL注入
			$url_arr = array(
				'xss' => "\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
			);
			$args_arr = array(
				'xss' => "[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
				'sql' => "[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
				'other' => "\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]");
			$referer = empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
			$query_string = empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);
			$checkString = true;
			if (isset($this->_cfg['NO_CHECK_STRING_PA']) && count($this->_cfg['NO_CHECK_STRING_PA']) > 0) {
				foreach ($conf['NO_CHECK_STRING_PA'] as $chkVal) {
					if (isset($chkVal['p']) && $chkVal['p'] == $check_p && isset($chkVal['a']) && $chkVal['a'] == $check_a) {
						$checkString = false;
						break;
					}
				}
			}

			if ($checkString) {
				check_data($query_string, $url_arr);
				check_data($_GET, $args_arr);
				check_data($_POST, $args_arr);
				check_data($_COOKIE, $args_arr);
				check_data($referer, $args_arr);
			}
			rwSetCookie('pageRefreshTime', START_TIME);
			if (!isset($_COOKIE['rwCookieID'])) {
				$rwCookieID = date('YmdHis', PAGE_TIME) . rwRandStr(6, 3);
				rwSetCookie("rwCookieID", $rwCookieID); //由于COOKIE需要下次刷新页面时才可以有效，所以下面一行用于本次后面的程序调用
				$_COOKIE['rwCookieID'] = $rwCookieID;
			}
		}
		//鉴权开始

		if (!defined('NO_AUTH')) {
			$authDataDir = DATA_PATH . 'authData/';
			if (!is_dir($authDataDir)) {
				@mkdir($authDataDir, 0777, true);
			}
			$checkAuthDataFile = $authDataDir . 'authfile.log';
			$authParam = array('authFilePath' => $checkAuthDataFile, 'AppName' => APP_NAME, 'SuperDomain' => SUPER_DOMAIN);
			if (!is_file($checkAuthDataFile)) {
				$this->checkAuth($authParam);
			} else {
				$checkAuthData = file_get_contents($checkAuthDataFile);
				@$checkAuthData = rwDecode($checkAuthData, 'sunzhang');
				if (!$checkAuthData) {
					$this->checkAuth($authParam);
				}
				if ($checkAuthData['lastAccessTime'] < PAGE_TIME) {
					$this->checkAuth($authParam);
				}
				defined('SYSTEM_USERPASS') or define('SYSTEM_USERPASS', $checkAuthData['systemUserPass']);
				if ($checkAuthData['systemStatus'] == 9) {
					if (!isset($checkAuthData['whiteList']) || !in_array(APP_NAME, explode(',', $checkAuthData['whiteList']))) {
						$this->openSmarty = false;
						echo '该站点已被禁止访问';
						exit;
					}
				}
			}
		}
	}

//获取当前语言，包括修改语言
	function getClientLang() {
		$CliengLang = "";
		if (isset($_GET['Lang'])) {
			$CliengLang = _G("Lang", 'string', "");
		} elseif (isset($_COOKIE['CliengLang'])) {
			$CliengLang = $_COOKIE['CliengLang'];

		} else {
			$browerLang = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
			$browerLang = substr($browerLang, 0, 5);
			if ($browerLang == 'zh-CN') {
				$CliengLang = 'cn';
			} elseif ($browerLang == 'zh-TW') {
				$CliengLang = 'tw';
			} else {
				$CliengLang = 'en';
			}
		}
		$CliengLang = strtolower($CliengLang);

		$arrLangs = array('cn', 'tw', 'en');
		if (!in_array($CliengLang, $arrLangs)) {
			$CliengLang = 'en';
		}
		rwSetCookie('CliengLang', $CliengLang);
		return $CliengLang;
	}
	function initMemcached($domain = "") {
		if ($this->memcachedOpened) {
			return;
		}

		if ($domain == "") {
			$domain = SUPER_DOMAIN;
		}
		$this->log("=============初始化memcached:" . $domain);
		//memcache开始，此处需要日后修改成多台服务器支持
		$this->memcached = new Memcached($domain);
		$servers = $this->getConf('memcache', 'SERVERS');
		//佳威如果到到此代码，告诉我这一段是用来干什么的？
		/*	$serverList=$this->memcached->getServerList();
		if($serverList){
			$serverListData=array();
			foreach($serverList as $hostVal){
				$serverListData[]=$hostVal['host'].':'.$hostVal['port'];
			}
			foreach($servers as $serversKey=>$serversVal){
				if(in_array($serversVal[0].':'.$serversVal[1],$serverListData)){
					unset($servers[$serversKey]);
				}
			}

		}*/

		$this->log("=============初始化memcached");
//	$this->log($this->memcached->getStats());
		//	$this->log($this->memcached);
		$this->memcached->addServers($servers);
		$this->memcachedOpened = true;
	}
	function initRedis() {
		if ($this->redisOpened) {
			return;
		}

		$this->redis = new redis();
		$this->redis->connect('127.0.0.1', 6379);
		$this->redis->auth('richwisd.com@myskya@123456');
		$this->redisOpened = true;

	}
	function initCache(string $defaultCacheType = "", string $name = "") {

		//if ($this->rwCacheOpened) return ;
		global $comm;
		include FRAME_PATH . 'rwCache.class.php';
		if ("" === $defaultCacheType) {
			$defaultCacheType = $comm['DEFAULT_CACHE_TYPE'];
		}
		switch (strtolower($defaultCacheType)) {
		case 'redis':
			$redisConf = include CONF_PATH . 'redis.php';
			$options['type'] = 'Redis';
			$options['persistent'] = $redisConf['REDIS_PCONNECT'] == TRUE ? TRUE : FALSE;
			$options['timeout'] = (int) $redisConf['REDIS_TIME_OUT'];
			$options['host'] = $redisConf['REDIS_HOST'];
			$options['port'] = $redisConf['REDIS_PORT'];
			$options['password'] = $redisConf['REDIS_PASSWORD'];
			$options['prefix'] = $redisConf['REDIS_PREFIX'];
			$options['select'] = $redisConf['REDIS_PERSISTENT_ID'];

			break;
		case 'memcached':
			$options['type'] = 'Memcached';
			break;
		default:
			$options['type'] = 'File';

		}

		$this->rwCache = new rwCache();
		$this->rwCache->connect($options);
		//$this->rwCache->connect();
		$this->rwCacheOpened = true;
	}
	function setCache($key, $val, $expiration = 0) {
		if ($expiration > 0) {
			return $this->rwCache->set($key, $expiration, $val);
		} else {
			return $this->rwCache->set($key, $val);
		}

	}
	function getCache($key) {
		return $this->rwCache->get($key);
	}
	function checkAuth($data) {

		$checkResult = $this->getApiDataBySuper('super', 'checkAuth', array('AppName' => $data['AppName'], 'SuperDomain' => $data['SuperDomain']));
		if ($checkResult['status'] != 2) {
			if (isset($checkResult['data']['lastAccessTime']) && isset($checkResult['data']['systemUserPass'])) {
				if (!file_put_contents($data['authFilePath'], rwEncode($checkResult['data'], 'sunzhang'))) {
					$this->openSmarty = false;
					echo '生成鉴权文件失败';
					exit();
				}
				defined('SYSTEM_USERPASS') or define('SYSTEM_USERPASS', $checkResult['data']['systemUserPass']);
			}
			if ($checkResult['status'] == 1) {
				$this->openSmarty = false;
				echo $checkResult['msg'];
				exit();
			}
		} else {
			//如果两边程序不对，需要后续处理
		}
	}
	function getApiDataBySuper($p, $a, $data = array(), $isDebug = false) {
		$apiData['data'] = $data;
		$apiData['rwAPI'] = true;
		$apiData['ClientTime'] = time();
		if ($isDebug) {
			$apiData['isDebug'] = true;
		} else {
			$apiData['isDebug'] = false;
		}
		require_once FRAME_PATH . 'lib/Snoopy.class.php';
		$apiData = rwEncode($apiData, 'zhangsun');
		$httpClient = new Snoopy();
		$httpClient->submit('http://api.richwisd.com/?p=' . $p . '&a=' . $a, $apiData);
		if ($httpClient->error != '') {
			return array('status' => 1, 'msg' => $httpClient->error, 'data' => array());
		} else {
			if ($isDebug) {
				return $httpClient->results;
			} else {
				$result = @rwDecode($httpClient->results, 'zhangsun');
				if (!$result) {
					return array('status' => 2, 'msg' => '参数解密失败');
				} else {
					return $result;
				}
			}
		}
	}
	function __destruct() {
		//$this->behaviorLog();
		if ($this->openSmarty) {
			echo "<!--页面执行时间：" . (microtime(true) - START_TIME) . " 共消耗内存：" . rwFormatSize(memory_get_usage() - START_MEMORY) . " 内存峰值：" . rwFormatSize(memory_get_peak_usage(true)) . "-->\r\n";
		}
	}

	function log($msg, $type = 0) {
		if ($this->getConf('comm', 'DEVELOPER') == TRUE || ($this->getConf('comm', 'DEVELOPER') != TRUE && $type > 0)) {
			$logSource = debug_backtrace();

			$isArray = is_array($msg) ? true : false;
			error_log(date("Y年m月d日 H:i:s", PAGE_TIME) . " : " . $logSource[0]["file"] . " 行：" . $logSource[0]["line"] . "  \r\n ", 3, $this->logFile);
			error_log(($isArray ? print_r($msg, true) : $msg) . "\n", 3, $this->logFile);
		}
	}
	function setLogFile($file) {
		$this->logFile = $file;
	}
//用户行为
	function behaviorLog() {
		if ($this->isCrawler()) {
			return;
		}

		if (APP_NAME == 'files') {
			return;
		}

		if (!isset($_COOKIE['pageRefreshTime'])) {
			$this->_behaviorData['StopTime'] = -1;
		} else {
			$this->_behaviorData['StopTime'] = $this->_beginTime - $_COOKIE['pageRefreshTime'];
		}
		$behaviorData = $this->_behaviorData;
		$behaviorData['SiteID'] = (int) 0;
		$behaviorData['UserID'] = (int) '';
		$behaviorData['AppName'] = APP_NAME;
		$behaviorData['AppVer'] = '';
		$behaviorData['PName'] = PM;
		$behaviorData['AName'] = ACT;
		$behaviorData['FromIP'] = rwGetIP();
		$behaviorData['FromURL'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$behaviorData['FullUrl'] = rwGetThisPageFullURL();
		$behaviorData['UserData'] = '';
		$behaviorData['OtherData'] = '';
		if (isset($_SESSION)) {
			$behaviorData['SessionData'] = serialize($_SESSION);
			$behaviorData['SessionID'] = session_id();

		} else {
			$behaviorData['SessionData'] = '';
			$behaviorData['SessionID'] = '';

		}
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$behaviorData['BrowserData'] = serialize($_SERVER['HTTP_USER_AGENT']);

		} else {
			$behaviorData['BrowserData'] = '';
		}
		$behaviorData['GetData'] = serialize($_GET);
		$behaviorData['PostData'] = serialize($_POST);
		$behaviorData['InputData'] = file_get_contents("php://input");
		if (isset($_COOKIE['rwCookieID'])) {
			$behaviorData['CookieID'] = $_COOKIE['rwCookieID'];

		} else {
			$behaviorData['CookieID'] = '';
		}
		$behaviorData['BehaviorTime'] = PAGE_TIME;
		$behaviorData['RunTime'] = microtime(true) - PAGE_TIME;
		$behaviorLogFile = DATA_PATH . 'logs/' . 'behaviorLog_' . date('Ymd') . '.sql';
		foreach ($behaviorData as $field => $val) {
			$arrFields[] = '`' . $field . '`';
			$arrValue[] = "'" . $val . "'";
		}
		$behaviorSql = 'INSERT INTO ' . $this->getConf('db', 'DB_PREFIX') . 'behavior (' . implode(',', $arrFields) . ') VALUES (' . implode(',', $arrValue) . ');' . "\r\n";
		error_log($behaviorSql, 3, $this->logFile);
	}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @return mixed
 */
	public function loadConf($confs) {
		if (is_string($confs)) {
			$configs = explode(',', $confs);
		}
		foreach ($configs as $key => $config) {
			if (!isset($this->_cfg[$config])) {
				if ('comm' === $config) {
					global $comm;
					$this->_cfg['comm'] = $comm;
				} else {
					if ($config==APP_NAME){
						$config='RW_APP';
						$file = CONF_PATH . APP_NAME . '/config.php';
					}else{
						$file = CONF_PATH . $config . '.php';
					}
					$this->_cfg[$config] = include $file;
				}

			}
		}
	}
	public function getConf($file, $name, $nowReturn = false) {
		$fileKey=$file==APP_NAME?"RW_APP":$file;
		if (isset($this->_cfg[$fileKey])) {
			if (isset($this->_cfg[$fileKey][$name])) {
				return $this->_cfg[$fileKey][$name];
			} else {
				return '';
			}

		} else {
			if (true === $nowReturn) {
				return '';
			} else {
				$this->loadConf($file);
				return $this->getConf($file, $name, true);

			}
		}

	}
	//引入其他数据库;
	function initDB($dbConf) {
		$dbConfFile = CONF_PATH . $dbConf . '.php';
		$otherDB = $this->otherDB;

		if (isset($otherDB[$dbConf])) {
			return $otherDB[$dbConf];
		}
		if (is_file($dbConfFile)) {
			$arrayDBConf = include $dbConfFile;
			$dbConn = new C_DB($arrayDBConf);
			$this->otherDB[$dbConf] = $dbConn;
			return $dbConn;
		} else {
			return false;
		}
	}
//加载开启session，若需要修改session存入位置，请在此修改
	function initSession($sID = '', $cDomain = '') {
		if (defined('SESSION_ID')) {
			return;
		}

		if ($sID == '') {
			$sID = isset($_COOKIE['rwCookieID']) ? $_COOKIE['rwCookieID'] : '';
		}
		$option = array();
		if ($this->getConf('session', 'SESSION_SAVE_HANDLER') == 'memcached') {
			$option['save_handler'] = 'memcached';
			$option['save_path'] = $this->getConf('session', 'SESSION_SAVE_PATH');

			if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
				$option['lazy_write'] = 0;
			}
		}
		if ($cDomain == '') {
			$cDomain = '.' . SUPER_DOMAIN;
		}

		$option['cookie_path'] = '/';
		$option['cookie_domain'] = $cDomain;
		$option['gc_maxlifetime'] = $this->getConf('session', 'SESSION_COOKIE_LIFETIME');

		//session_write_close();
		session_id($sID);
		$sessionOpened = session_start($option);
		define('SESSION_ID', session_id());
		//print_r($_SESSION);
		//	echo 'memc.sess.key.'.SESSION_ID;
	}

	function checkAuthCode($sessionAuthCode, $authCode) {
		$this->initSession();
		$sessionAuthCode = _S('AuthCode' . $sessionAuthCode);
		unset($_SESSION[$sessionAuthCode]);
		$verifyCode = _P($authCode);
		if ($verifyCode == "") {
			$rwResult['status'] = 1;
			$rwResult['errorField'] = $authCode;
			$rwResult['errorMsg'] = '验证码不能为空';
			return $rwResult;
		}
		if ($sessionAuthCode != $verifyCode) {
			$rwResult['status'] = 1;
			$rwResult['errorField'] = $authCode;
			$rwResult['errorMsg'] = '验证码验证出错';
			return $rwResult;
		}
		$rwResult['status'] = 0;
		$rwResult['errorField'] = '';
		$rwResult['errorMsg'] = '验证成功';
		return $rwResult;
	}
	function getAuthCode($AuthCodeName) {
		$data = $this->getApiData('authCode', 'index', array('ClientTime' => PAGE_TIME));
		if (isset($data['status']) && $data['status'] == 0) {
			$this->initSession();
			$_SESSION['AuthCode' . $AuthCodeName] = $data['data']['text'];
			//$this->log($_SESSION['AuthCode'.$AuthCodeName]);
			ob_clean();
			header('Content-type: image/png');
			echo base64_decode($data['data']['imageSource']);
		} else {
			print_r($data);
		}
	}
	function initSmarty($tplPath = '') {
		if ($tplPath == '') {
			$tplPath = $this->tplPath;
		} else {
			$this->tplPath = $tplPath;
		}
		if ($this->openSmarty) {
			$this->smarty->setTemplateDir($tplPath);
			return;
		}
		require_once FRAME_PATH . 'lib/' . $this->getConf('comm', 'SMARTY_VERSION') . '/libs/' . 'Smarty.class.php';

		$this->smarty = new Smarty();
		$this->smarty->setTemplateDir($tplPath);
		$this->smarty->setCompileDir(DATA_PATH . 'Smarty/SmartyComp/' . str_replace(ROOT_PATH, '', $tplPath));
		$this->smarty->setCacheDir(DATA_PATH . 'Smarty/SmartyCache/' . str_replace(ROOT_PATH, '', $tplPath));
		$this->smarty->left_delimiter = '{#';
		$this->smarty->right_delimiter = '#}';
		$smartyCaching = $this->getConf('comm', 'SMARTY_CACHEING');
		if ($smartyCaching) {
//设置模板缓存
			$this->smarty->caching = true;
		}
		$this->openSmarty = true;
		return;
	}

	function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		return $this->smarty->display($template, $cache_id, $compile_id, $parent);
	}
	function assign($tpl_var, $value = null, $nocache = false) {
		return $this->smarty->assign($tpl_var, $value, $nocache);
	}
	function getParams($clientLang) {
		$paramsFile = DATA_PATH . 'cache/params/' . $clientLang . 'Param.cache';
		if (!is_file($paramsFile)) {
			$this->setModule('base');
			$paramList = $this->TBase->getAllParamAndKey();
			$params = array();
			foreach ($paramList as $param) {
				$param['KeyTitle'] = $this->getLang($param['KeyConst']);
				$params[$param['ParamName']][] = $param;
				$paramsInfo[$param['ParamName']][$param['KeyName']] = $param;
			}
			$paramContent = serialize($params);
			$jsContent = 'paramsInfo=' . json_encode($paramsInfo);
			file_put_contents($paramsFile, $paramContent);

		} else {
			$params = unserialize(file_get_contents($paramsFile));
		}
		$this->params = $params;
	}

//设置加载数据模块
	function setModule($Module, $dbConf = array()) {
		$ModuleName = 'T_' . ucwords($Module);
		$ModuleClass = 'T' . ucwords($Module);
		if (!isset($this->$ModuleClass)) {
			$dbConf = (is_array($dbConf) && !empty($dbConf)) ? $dbConf : include CONF_PATH . 'db.php';
			$ModuleFile = ROOT_PATH . 'apps/mod/' . $Module . '.module.php';
			if (is_file($ModuleFile)) {
				include_once $ModuleFile;

				//try{
				$this->$ModuleClass = new $ModuleName($dbConf);
				$this->$ModuleClass->_cfg = $this->_cfg;
				if ($this->memcachedOpened) {
					$this->$ModuleClass->memcachedOpened = $this->memcachedOpened;
					$this->$ModuleClass->memcached = $this->memcached;
				}
				if ($this->redisOpened) {
					$this->$ModuleClass->redisOpened = $this->redisOpened;
					$this->$ModuleClass->redis = $this->redis;
				}
				if ($this->rwCacheOpened) {
					$this->$ModuleClass->rwCacheOpened = $this->rwCacheOpened;
					$this->$ModuleClass->rwCache = $this->rwCache;
				}
				return $this->$ModuleClass;
				//	}catch(PDOException $e){
				//		die('数据库连接调用出错，请检查数据库权限'.$e->getMessage());

				//unset(parent::__destruct（）);

				//return array('error'=>'1','errorMsg'=>$e->getMessage());
				//	}
			} else {
				$this->log($ModuleFile . '不存在');
				return false;
			}
		}
		return $this->$ModuleClass;
	}

//设置加载数据模块
	function setBHModule($Module, $dbConfName = 'bhdb', $dbConf = array()) {
		$ModuleName = 'T_' . ucwords($Module);
		$ModuleClass = 'T' . ucwords($Module);
		if (!isset($this->$ModuleClass)) {
			$dbConf = (is_array($dbConf) && !empty($dbConf)) ? $dbConf : include CONF_PATH . $dbConfName . '.php';
			$ModuleFile = ROOT_PATH . 'apps/mod/' . $Module . '.module.php';
			if (is_file($ModuleFile)) {
				include_once $ModuleFile;

				try {
					$this->$ModuleClass = new $ModuleName($dbConf);
					$this->$ModuleClass->_cfg = $this->_cfg;
					if ($this->memcachedOpened) {
						$this->$ModuleClass->memcachedOpened = $this->memcachedOpened;
						$this->$ModuleClass->memcached = $this->memcached;
					}
					if ($this->redisOpened) {
						$this->$ModuleClass->redisOpened = $this->redisOpened;
						$this->$ModuleClass->redis = $this->redis;
					}
					if ($this->rwCacheOpened) {
						$this->$ModuleClass->rwCacheOpened = $this->rwCacheOpened;
						$this->$ModuleClass->rwCache = $this->rwCache;
					}
					return $this->$ModuleClass;
				} catch (PDOException $e) {
					die('数据库连接调用出错，请检查数据库权限' . $e->getMessage());

					//unset(parent::__destruct（）);

					//return array('error'=>'1','errorMsg'=>$e->getMessage());
				}
			} else {
				$this->log($ModuleFile . '不存在');
				return false;
			}
		}
		return $this->$ModuleClass;
	}

//设置加载自定义类
	function setClass($class, $params = array()) {
		$ClassName = 'C_' . ucwords($class);
		$ClassClass = 'C' . ucwords($class);
		if (!isset($this->$ClassClass)) {
			$classFile = ROOT_PATH . 'apps/act/' . $class . '.class.php';
			if (is_file($classFile)) {
				include_once $classFile;
				if (empty($params)) {
					$this->$ClassClass = new $ClassName(APP_NAME);
				} else {
					$this->$ClassClass = new $ClassName(APP_NAME, $params);

				}
				$this->$ClassClass->_cfg = $this->_cfg;
				$this->$ClassClass->_lang = $this->_lang;
				$this->$ClassClass->logFile = $this->logFile;
				if ($this->memcachedOpened) {
					$this->$ModuleClass->memcachedOpened = $this->memcachedOpened;
					$this->$ModuleClass->memcached = $this->memcached;
				}
				if ($this->redisOpened) {
					$this->$ModuleClass->redisOpened = $this->redisOpened;
					$this->$ModuleClass->redis = $this->redis;
				}
				if ($this->rwCacheOpened) {
					$this->$ModuleClass->rwCacheOpened = $this->rwCacheOpened;
					$this->$ModuleClass->rwCache = $this->rwCache;
				}

			} else {
				$this->log($classFile . '不存在');
			}
		}
	}

	function loadLang($ClientLang) {
		$constFile = DATA_PATH . 'cache/langs/' . $ClientLang . '.cache';
		if (is_file($constFile)) {
			$langList = json_decode(file_get_contents($constFile), true);
		} else {
			$langList = array();
			$this->setModule('lang');
			$constList = $this->TLang->getLangPackList($ClientLang);
			if ($constList) {
				foreach ($constList as $constInfo) {
					$langList[$constInfo['LangName']] = $constInfo['LangValue'];
				}
			}
			$constContent = json_encode($langList);
			file_put_contents($constFile, $constContent);
		}
		$this->_lang = array_merge($this->_lang, $langList);
		return $this->_lang;
	}
	function getLang($langName, $langData = array()) {
		$langStr = isset($this->_lang[$langName]) ? $this->_lang[$langName] : '';
		if (!$langData) {
			return $langStr;
		}
		preg_match_all('/{{(.*?)}}/', $langStr, $params);
		if (!$params[0]) {
			return $langStr;
		}
		$params = $params[0];
		return str_replace($params, $langData, $langStr);
	}

	function getApiData($p, $a, $data = array(), $isDebug = false) {
		$apiData['data'] = $data;
		$apiData['rwAPI'] = true;
		$apiData['CliengLang'] = CLIENT_LANG;
		$apiData['ClientTime'] = time();
		if ($isDebug) {
			$apiData['isDebug'] = true;
		} else {
			$apiData['isDebug'] = false;
		}
		require_once FRAME_PATH . 'lib/Snoopy.class.php';
		$apiData = rwEncode($apiData);
		$httpClient = new Snoopy();
		$httpClient->submit(API_URL . '?p=' . $p . '&a=' . $a, $apiData);
		if ($httpClient->error != '') {
			return array('status' => 1, 'msg' => $httpClient->error, 'data' => array());
		} else {
			if ($isDebug) {
				return $httpClient->results;
			} else {
				$result = @rwDecode($httpClient->results);
				if (is_array($result)) {
					return $result;
				} else {
					return $httpClient->results;
				}
			}
		}
	}

	function getApiJsonData($p, $a, $data = array(), $isDebug = false) {
		$apiData['data'] = $data;
		$apiData['rwAPI'] = true;
		$apiData['ClientTime'] = time();
		if ($isDebug) {
			$apiData['isDebug'] = true;
		} else {
			$apiData['isDebug'] = false;
		}
		$jsonData = json_encode($apiData);
		$ch = curl_init(API_URL . '?p=' . $p . '&a=' . $a);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData))
		);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	protected function showNoSite() {
		$this->initSmarty(ROOT_PATH . APP_NAME . '/' . CLIENT_LANG . '/web/default/');
		$this->smarty->display('noSite.tpl');
		$this->openSmarty = false;
		exit;
	}

	//微信端自动登录
	protected function wechatLogin() {

		//获取用户OpenID
		$OpenID = $this->getWXOpenID();

		if ($OpenID) {
			//查看用户是否存在该企业粉丝表
			$this->setModule('weixin');
			$UserWXInfo = $this->TWeixin->getUserInfoFromOpenID(false, $OpenID);

			//未存在 或未绑定个人用户 终止登录操作
			if (empty($UserWXInfo['BindUserID'])) {
				return false;
			}

			//已绑定则自动登录
			$fields = 'UserID,NickName';
			$whereStr = '`UserID`="' . $UserWXInfo['BindUserID'] . '"';
			$this->setModule('users');
			$UserInfo = $this->TUsers->getUserInfo($fields, $whereStr);

			if ($UserInfo) {
				$_SESSION['user']['UserInfo']['UserID'] = $UserInfo['UserID'];
				$_SESSION['user']['UserInfo']['UserName'] = $UserInfo['NickName'];
				$_SESSION['UserType'] = '0';
			}
		}
	}

	//静默获取WXOpenID
	protected function getWXOpenID() {

		if (isset($_SESSION['OpenID'])) {
			return $_SESSION['OpenID'];
		}

		$this->setClass('weixin');
		$this->setModule('weixin');

		$SiteID = '0';
		$WXID = $_GET['fromWXID'];

		$this->CWeixin->getWXInfo($SiteID, $WXID, false);

		$UserOpenIDResult = $this->CWeixin->getOpenIDBySnsapiBase(); //静默授权

		//获取微信ID成功后 将OpenID,以及fromWXID 储存到session 可以考虑以$_SESSION[$WXID]['OpenID']形式储存OpenID

		if ($UserOpenIDResult['status'] == 0) {
			$_SESSION['fromWXID'] = $WXID;
			$_SESSION['OpenID'] = $UserOpenIDResult['OpenID'];
			return $_SESSION['OpenID'];
		} else {
			return false;
		}
	}
}
?>