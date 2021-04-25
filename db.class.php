<?php
namespace rwFramework;
//独家首创，直接扩展PDO类，作为应用程序的一部分
$rwDBConn = array();
class C_DB {
	var $pageSize = 0;
	var $page = 1;
	var $Langs;
	var $debug;
	var $dbPre = '';
	var $dbType = '';
	var $tableName = '';
	var $fullTableName = '';
	var $handle;
	function __construct($dbConf) {
		$this->logFile = DATA_PATH . 'logs/sqlLog' . date('Ymd') . '.log';
		$StartTime = microtime(true);
		$page = _R('page', 'int', 1);
		$this->page = $page < 1 ? 1 : $page;
		$this->pageSize = _R('rows', 'int', 10);
		$this->dbPre = $dbConf['DB_PREFIX'];
		$dbConfHandle = md5(serialize($dbConf));
		global $rwDBConn;

		if (isset($rwDBConn[$dbConfHandle])) {
			$this->handle = $rwDBConn[$dbConfHandle];
		} else {

			//try{
			$this->dbType = $dbConf['DB_TYPE'];
			set_exception_handler([$this, 'exception_handler']);

			switch ($this->dbType) {
			case 'pdo_mysql':
				$dsn = 'mysql:dbname=' . $dbConf['DB_NAME'] . ';host=' . $dbConf['DB_HOST'] . ';port=' . $dbConf['DB_PORT'];
				if (defined(PDO::ATTR_PERSISTENT)) {
					$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $dbConf['DB_CHARSET'],
						PDO::ATTR_PERSISTENT => $dbConf['ATTR_PERSISTENT'],
					);
				} else {
					$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $dbConf['DB_CHARSET']);
				}

				break;
			case 'pdo_sqlsrv':
				if (PATH_SEPARATOR == ':') {
					$dsn = 'dblib:host=' . $dbConf['DB_HOST'] . ':' . $dbConf['DB_PORT'] . ';dbname=' . $dbConf['DB_NAME'];
					$options = array();
				} else {
					$dsn = 'sqlsrv:Server=' . $dbConf['DB_HOST'] . ',' . $dbConf['DB_PORT'] . ';Database=' . $dbConf['DB_NAME'];
					$options = array();
				}
				break;
			case 'pdo_pgsql':
				$dsn = 'pgsql:host=' . $dbConf['DB_HOST'] . ';port=' . $dbConf['DB_PORT'] . ';dbname=' . $dbConf['DB_NAME'];
				$options = array(); //array(PDO::MYSQL_ATTR_INIT_COMMAND=>	'SET NAMES '.$dbConf['DB_CHARSET']	);

				break;
			default:
				die('数据库类型不存在');

			}
			try {
				$dbConnHandle = new PDO($dsn, $dbConf['DB_USERNAME'], $dbConf['DB_PASSWORD'], $options);
				$dbConnHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$dbConnHandle->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
				if ($this->dbType == 'pdo_mysql') {
					$dbConnHandle->setAttribute(PDO::ATTR_PERSISTENT, true);
				}
				$rwDBConn[$dbConfHandle] = $dbConnHandle;
				$this->handle = $rwDBConn[$dbConfHandle];
				unset($dsn, $dbConf, $options);
				//$this->log('数据库连接用时：' . (microtime(true) - $StartTime));
			} catch (PDOException $e) {
				echo '数据库连接错误: ' . $e->getMessage();
			}

		}

		// $this->log($this->handle->getAttribute());

		//PDO::ATTR_PERSISTENT=>true,
		//数据库长连接目前还有些问题，还不知道问题在哪里

//		}catch(PDOException $e){
		//			//echo ($e->getMessage());
		//			return false;
		//		}

		restore_exception_handler();
	}
	static function exception_handler($exception) {
		// Output the exception details.

		$errorMsg = "SQL出错：\r\n" . $exception->getMessage();
		$logFile = DATA_PATH . 'logs/sqlLog' . date('Ymd') . '.log';
		error_log(date('Y-m-d H:i:s') . ' :  ' . $errorMsg . "\n", 3, $logFile);
		exit($errorMsg);
		//die($$errorMsg);
	}
	static function proc_error($errno, $errstr, $errfile, $errline) {
		$e = new ErrorException($errstr, 0, $errno, $errfile, $errline);
		if (EXIT_ON_ALL_PHP_ERRORS) {
			throw $e; // This will halt your script.
		} else {
			$this->exception_handler($e); // This will let it continue.
		}
	}

	/**
	 * 记录日志
	 * @param string/array $msg 	需要记录的信息
	 */
	function log($msg) {
		$logSource = debug_backtrace();
		$isArray = is_array($msg) ? true : false;
		error_log(date("Y-m-d H:i:s", PAGE_TIME) . " : \r\n", 3, $this->logFile);

		foreach ($logSource as $logInfo) {
			if (isset($logInfo["file"])) {
				error_log($logInfo["file"] . " 行：" . $logInfo["line"] . "  \r\n ", 3, $this->logFile);
			}

		}
		error_log(($isArray ? print_r($msg, true) : $msg) . " \r\n \r\n", 3, $this->logFile);
		if ($isArray) {
			error_log("Total Time ：" . (microtime(true) - START_TIME) . "\r\n \r\n", 3, $this->logFile);
		}

	}

	/**
	 * 设置日志文件
	 * @param string $file 	文件路径
	 */
	function setLogFile($file) {
		$this->logFile = $file;
	}

	function __destruct() {
		if ($this->handle->errorCode() != 0) {
			$this->log('__destruct 方法返回');
			$this->log($this->errorInfo());
		}
	}
	//切换表数据，目前只能替换Y/N,0/1
	function switchCell($tableName, $idField, $idValue, $Field, $Value) {
		$idValue = (int) $idValue;

		if ($idValue == 0) {
			$this->log('idValue为0');
			return false;
		}
		//允许值
		$values = array('0', '1', 'Y', 'N');
		if (!in_array($Value, $values)) {
			$this->log('values非法');
			return false;
		}
		$oldField = $Field . '  oldValue';
		$whereStr = '`' . $idField . '`=' . $idValue;
		$oldValue = $this->getOne($tableName, $oldField, $whereStr);
		if ($oldValue == $Value) {
			switch ($Value) {
			case '0':
				$newValue = '1';
				break;
			case '1':
				$newValue = '0';
				break;
			case 'Y':
				$newValue = 'N';
				break;
			case 'N':
				$newValue = 'Y';
				break;
			default:
				$this->log('values非法');
				return false;
			}
			$data[$Field] = $newValue;
			$this->update($tableName, $data, $whereStr);
			return true;
		}
		$this->log('数据过期');
		return false;
	}
	//此函数需要升级
	function check($str) {
		return $str;
	}

	/**
	 * kzwms特有表前缀，在配置文件中设置
	 * @param string $tableName 	表名
	 * @return string 				返回值
	 */
	function Prefix($tableName) {
		return $this->dbPre . $tableName;
	}
	function existsTable($tableName) {
		$sql = 'SHOW TABLES LIKE "' . $this->Prefix($tableName) . '"';
		//$sql='SHOW TABLES LIKE "rw_*"' ;
		if ($this->debug) {
			$this->log($sql);
		}
		try {
			$Rs = $this->query($sql);
			$Row = $Rs->fetch(PDO::FETCH_NUM);

			if ($Row) {
				return $Row[0];
			} else {
				return false;
			}

		} catch (PDOException $e) {
			echo " existsTable SQL 出错，请检查";
			$this->log($sql);
			$this->log($e);
			return false;
		}
	}
	//设置当前类的表名
	function setTableName($tableName) {
		$fullTableName = $this->existsTable($tableName);
		if ($fullTableName != '') {
			$this->tableName = $tableName;
			$this->fullTableName = $fullTableName;
			return true;
		} else {
			return false;
		}

	}
	function selfPrefix($tableName) {}
	function getTableInfo($tableName) {
		$sql = 'SELECT COLUMN_COMMENT,COLUMN_NAME,DATA_TYPE,COLUMN_TYPE,COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE table_name = "' . $this->Prefix($tableName) . '"';
		return $this->getArray($sql);
	}

	/**
	 * 获取一个表的字段名称
	 * @param unknown $tableName 	表名
	 * @param string $columns 		字段（默认：COLUMN_NAME,DATA_TYPE,COLUMN_COMMENT,COLUMN_TYPE）
	 * @param string $whereStr 		筛选条件
	 * @return array 				返回数据（字段名）
	 */
	function getTableFields($tableName, $columns = 'COLUMN_NAME,DATA_TYPE,COLUMN_COMMENT,COLUMN_TYPE,COLUMN_DEFAULT', $whereStr = '') {
		if ($whereStr != '') {
			$whereStr = ' AND ' . $whereStr;
		}
		$sql = 'SELECT ' . $columns . ' FROM information_schema.COLUMNS WHERE table_name = "' . $this->Prefix($tableName) . '" ' . $whereStr;
		if ($this->debug) {
			$this->log($sql);
		}
		try {
			$rs = $this->query($sql);
			return $rs->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			echo "SQL 出错，请检查";
			$this->log($e);
			return false;
		}
	}

	/**
	 * 根据某表名及条件获取总记录条数；
	 * @param string $tableName 	表名
	 * @param string $whereStr 		筛选条件
	 * @return 						返回值
	 */

	function getCount($tableName, $whereStr = "") {
		return $this->getOne($tableName, 'COUNT(1)', $whereStr);
	}

	/**
	 * 获取单列数据，并直接返回给变量
	 * @param string $tableName 	表名
	 * @param string $field 		查询字段名
	 * @param string $where 		筛选条件
	 * @return string 				返回字段值
	 */
	function getOne($tableName, $field, $where, $orderStr = '', $limitStr = '') {
		$whereStr = ($where == '') ? '' : ' WHERE ' . $where;
		$sql = 'SELECT ' . $field . ' FROM ' . $this->Prefix($tableName) . $whereStr;
		if ($orderStr != '') {
			$sql .= ' ORDER BY ' . $orderStr;
		}
		if ($limitStr != '') {
			$sql .= ' LIMIT ' . $limitStr;
		}
		if ($this->debug) {
			$this->log($sql);
		}
		try {
			$Rs = $this->query($sql);
			$Row = $Rs->fetch(PDO::FETCH_NUM);
			if ($Row) {
				return $Row[0];
			} else {
				return "";
			}
		} catch (PDOException $e) {
			echo " getOne SQL 出错，请检查";
			$this->log($sql);
			$this->log($e);
			return "";
		}
	}
	function getTableFieldParamType($fieldInfo) {
		switch ($fieldInfo['DATA_TYPE']) {
		case 'tinyint':
		case 'smallint':
		case 'mediumint':
		case 'int':
		case 'integer':
		case 'bigint':
		case 'mediumint':
		case 'mediumint':
			return PDO::PARAM_INT;
			break;
		case 'char':
		case 'text':
		case 'longtext':
		case 'char':
		case 'varchar':
		case 'decimal':
		case 'json':
			return PDO::PARAM_STR;
			break;
		case 'boolean':
			return PDO::PARAM_BOOL;
			break;
		default:
			$this->log("未处理类型：" . $fieldInfo['DATA_TYPE'] . '请在db.class.php中308对应行处理');
			return;
		}
	}
	/**
	 * 获取一行数据
	 * @param string $tableName 	表名
	 * @param string $fields 		查询字段名
	 * @param string $whereStr 		筛选条件
	 * @return array 				返回数据
	 */
	function getInfo($tableName, $fields, $whereStr) {
		$sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . ' WHERE ' . $whereStr;
		//echo $sql;
		if ($this->debug) {
			$this->log($sql);
		}
		try {
			$rs = $this->query($sql);
			$rows = $rs->fetch(PDO::FETCH_ASSOC);
			return ($rows ? $rows : 0);
		} catch (PDOException $e) {
			echo " getInfo SQL 出错，请检查";
			$this->log($sql);
			$this->log($e);
			return false;
		}
	}

	/**
	 * 获取一列数据
	 * @param string $tableName 	表名
	 * @param string $fields 		查询字段名
	 * @param string $whereStr 		筛选条件
	 * @return array 				返回数据
	 */
	function getCol($tableName, $fields = '*', $whereStr = '', $orderBy = false) {
		$where = '';
		$firstField = explode(',', $fields)[0];
		$whereStr && $where .= ' WHERE ' . $whereStr . ' group by ' . $firstField;
		$orderBy && $where .= ' order By ' . $orderBy;

		if ($this->pageSize == 0) {
			$sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . $where;
		} else {
			$sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . $where . $this->getLimit();
		}
		// p($sql);
		//echo $sql;
		if ($this->debug) {
			$this->log($sql, 1);
		}
		try {
			$rs = $this->query($sql);
			$rows = $rs->fetchAll(PDO::FETCH_ASSOC);
			$returnData = [];
			if (strpos($fields, ',') === false) {
				// 只查询一个字段的数组
				foreach ($rows as $key => $value) {
					$returnData[] = $value[$fields];
				}
			} else {

				foreach ($rows as $key => $value) {
					$returnData[$value[$firstField]] = $value;
				}
				// 查询二维数组，且第一个字段作为键值
			}
			return $returnData;
		} catch (PDOException $e) {
			echo " getInfo SQL 出错，请检查";
			$this->log($sql);
			$this->log($e);
			return false;
		}
	}

	//分页函数
	function getLimit() {
		return ' LIMIT ' . ($this->page - 1) * $this->pageSize . ' , ' . $this->pageSize;
	}

	//自己写的select 语句，自定义表名，字符列，条件和排序，支撑多表查询
	/**
	 * 查询数据
	 * @param string/array $tableName 	表名（如果是数组，则键是表名，值是别名）
	 * @param string $fields 			查询字段名
	 * @param string $where 			筛选条件
	 * @param string $order 			排序条件
	 * @return array 					返回数据
	 */
	function select($tableName, $fields = '*', $where = '', $order = '') {
		if (is_string($tableName)) {
			$realTableName = $this->Prefix($tableName);
		} else {
			$realTableName = '';
			foreach ($tableName as $key => $val) {
				$realTableName .= $this->Prefix($key) . ' `' . trim($val, '`') . '`,';
			}
			$realTableName = ' ' . substr($realTableName, 0, -1) . ' ';
		}
		$whereStr = (trim($where) == '') ? '' : ' WHERE ' . $where;
		$orderStr = (trim($order) == '') ? '' : ' ORDER BY  ' . $order;

		try {
			if ($this->pageSize == 0) {
				$sql = 'SELECT ' . $fields . ' FROM ' . $realTableName . $whereStr . ' ' . $orderStr;
				if ($this->debug) {
					$this->log($sql);
				}
				$Rs = $this->query($sql, PDO::FETCH_ASSOC);
				return $Rs->fetchAll(PDO::FETCH_ASSOC);
			} else {
				if (is_string($tableName)) {
					$total = $this->getCount($tableName, $where);
				} else {
					$sql = 'SELECT COUNT(0) FROM ' . $realTableName . $whereStr;
					//echo $sql;
					if ($this->debug) {
						$this->log($sql);
					}
					$rs = $this->query($sql);
					$row = $rs->fetch(PDO::FETCH_NUM);
					$total = $row[0];
				}
				$sql = 'SELECT ' . $fields . ' FROM ' . $realTableName . $whereStr . '  ' . $orderStr . ' ' . $this->getLimit();
				//echo $sql;
				if ($this->debug) {
					$this->log($sql);
				}
				$Rs = $this->query($sql, PDO::FETCH_ASSOC);
				return array('total' => $total, 'rows' => $Rs->fetchAll(PDO::FETCH_ASSOC));
			}
		} catch (PDOException $e) {
			echo " select SQL 出错，请检查";
			echo $sql;

			$this->log($e);
			$this->log($sql);
			return array('total' => 0, 'rows' => array(), 'rwErrorNo' => '1');
		}

	}

	/**
	 * 根据数组更新内容
	 * @param string $tableName 	表名
	 * @param array $arrInfo 		更新数据（键是字段名，值是字段对应的值）
	 * @param string $whereStr 		筛选条件
	 * @return number 				返回值（影响的行数）
	 */
	function update($tableName, $arrData, $whereStr) {
		$this->log($arrData);
		if (!is_array($arrData) && count($arrData) == 0) {
			$this->log('传入参数修改的内容为空');
			return false;
		}
		try {
			$tableInfo = $this->getTableInfo($tableName);
			$tableKeyInfo = array();
			foreach ($tableInfo as $Info) {
				$tableKeyInfo[$Info['COLUMN_NAME']] = $Info;
			}
			$sql = 'UPDATE ' . $this->Prefix($tableName) . ' SET ';
			$arrFields = array();
			$errorFields = '';
			foreach ($arrData as $fieldInfo => $fieldValue) {
				if (!array_key_exists($fieldInfo, $tableKeyInfo)) {
					$errorFields .= $fieldInfo . ',';
				} else {
					array_push($arrFields, $fieldInfo);
					$sql .= $fieldInfo . '=:' . $fieldInfo . ',';
					$this->log($sql);
				}
			}
			$sql = substr($sql, 0, strlen($sql) - 1);
			if ($errorFields != '') {
				$this->log('提交的多余字段有：' . $errorFields);
			}
			if ($whereStr !== '') {
				$sql .= ' WHERE ' . $whereStr;
			}

			if ($this->debug) {
				$this->log($sql);
			}
			$rs = $this->prepare($sql);
			foreach ($arrFields as $field) {
				if (is_null($arrData[$field] || $arrData[$field] == '') && $tableKeyInfo[$field]['COLUMN_DEFAULT'] == NULL) {
					$rs->bindParam(':' . $fieldInfo, PDO::PARAM_NULL);
				} else {
					$rs->bindParam(':' . $field, $arrData[$field], $this->getTableFieldParamType($tableKeyInfo[$field]));
				}
				//
			}
			$rs->execute();
			return $rs->rowCount();
		} catch (PDOException $e) {
			$this->log("update SQL 出错，请检查");
			$this->log($sql);
			$this->log($field . '==' . $arrData[$field] . '==' . $tableKeyInfo[$field]['DATA_TYPE'] . '===' . $this->getTableFieldParamType($tableKeyInfo[$field]) . '===' . $tableKeyInfo[$field]['COLUMN_DEFAULT']);
			$this->log($e);
			return false;
		}

	}

	/**
	 * 将数据插入某表
	 * @param string $tableName 	表名
	 * @param array $arrData 		插入数据（键是字段名，值是字段对应的值）
	 * @return number 				返回值（此记录的ID）
	 */
	function insert($tableName, $arrData) {
		if (!is_array($arrData) && count($arrData) == 0) {
			$this->log('传入参数修改的内容为空');
			return false;
		}
		try {
			$tableInfo = $this->getTableInfo($tableName);
			$tableKeyInfo = array();
			foreach ($tableInfo as $Info) {
				$tableKeyInfo[$Info['COLUMN_NAME']] = $Info;
			}
			$sql = 'INSERT INTO  ' . $this->Prefix($tableName);
			$arrFields = array();
			$arrValues = array();
			$errorFields = '';
			foreach ($arrData as $fieldInfo => $fieldValue) {
				if (!array_key_exists($fieldInfo, $tableKeyInfo)) {
					$errorFields .= $fieldInfo . ',';
				} else {
					array_push($arrFields, $fieldInfo);
					array_push($arrValues, ':' . $fieldInfo);
				}
			}
			if ($errorFields != '') {
				$this->log('提交的多余字段有：' . $errorFields);
			}

			$sql .= ' (' . implode(',', $arrFields) . ') VALUES (' . implode(',', $arrValues) . ')';

			if ($this->debug) {
				$this->log($sql);
			}
			$rs = $this->prepare($sql);

			foreach ($arrFields as $field) {
				if (is_null($arrData[$field] || $arrData[$field] == '') && $tableKeyInfo[$field]['COLUMN_DEFAULT'] == NULL) {
					$rs->bindParam(':' . $fieldInfo, PDO::PARAM_NULL);
				} elseif ($arrData[$field] === '' && $this->getTableFieldParamType($tableKeyInfo[$fieldInfo]) == 1) {
					$rs->bindParam(':' . $field, $tableKeyInfo[$field]['COLUMN_DEFAULT'], $this->getTableFieldParamType($tableKeyInfo[$field]));
				} else {
					$rs->bindParam(':' . $field, $arrData[$field], $this->getTableFieldParamType($tableKeyInfo[$field]));
				}
				//$this->log($field.'=='.$arrData[$field].'=='.$tableKeyInfo[$field]['DATA_TYPE'].'==='.$this->getTableFieldParamType($tableKeyInfo[$field]).'==='.$tableKeyInfo[$field]['COLUMN_DEFAULT']);
			}
			$rs->execute();
			return $this->lastInsertId();

		} catch (PDOException $e) {
			$this->log($sql);
			echo " insert SQL 出错，请检查";
			$this->log($e);
			return false;
		}

	}

	/**
	 * 删除内容
	 * @param string $tableName 	表名
	 * @param string $whereStr 		删除条件
	 * @return number|boolean 		返回值（0/1;true/false）
	 */
	function delete($tableName, $whereStr) {
		try {
			$sql = 'DELETE FROM ' . $this->Prefix($tableName) . ' WHERE ' . $whereStr;
			if ($this->debug) {
				$this->log($sql);
			}
			return $this->exec($sql);
		} catch (PDOException $e) {
			echo "DELETE SQL 出错，请检查";
			$this->log($e);
			return false;
		}
	}

	/**
	 * 执行自定义SQL语句
	 * @param string $sql 	自定义的SQL语句
	 * @return multitype: 	返回值
	 */
	function getArray($sql) {
		if ($this->debug) {
			$this->log($sql);
		}
		try {
			$rs = $this->query($sql, PDO::FETCH_ASSOC);
			return $rs->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			echo "getArray SQL 出错，请检查";
			$this->log($e);
			return false;
		}
	}

	/**
	 * 更新/插入数据
	 * @param string $tableName 	表名
	 * @param array $PostData 		更新/插入的数据
	 * @param string $idField 		字段名
	 * @param number $idValue 		字段值（和字段名对应）
	 * @param string $whereStr 		更新数据的条件
	 * @return Ambigous <string, number>
	 */
	function edit($tableName, $PostData, $idField = '', $idValue = 0, $whereStr = '') {
		$returnInfo = array();

		if ($idValue === 0) {
			$newID = $this->insert($tableName, $PostData);
			if (!$newID) {
				$returnInfo['errorno'] = 1;
				$returnInfo['ActionID'] = 0;
			} else {
				$returnInfo['errorno'] = 0;
				$returnInfo['ActionID'] = $newID;
			}
		} else {
			$returnInfo['ActionID'] = $idValue;
			if ($whereStr == '') {
				$whereStr = '`' . $idField . '`=' . '"' . $idValue . '"';
			} else {
				$whereStr .= ' AND ' . '`' . $idField . '`=' . '"' . $idValue . '"';
			}
			if (!$this->update($tableName, $PostData, $whereStr)) {
				$returnInfo['errorno'] = 1;
				$returnInfo['ActionID'] = 0;
			} else {
				$returnInfo['ActionID'] = $idValue;
				$returnInfo['errorno'] = 0;
			}
		}

		return $returnInfo;
	}

	/**
	 * 判断值是否存在
	 * @param string $tableName 	表名
	 * @param string $idField 		字段名,字段ID
	 * @param string $idValue 		字段值，字段ID值，如果为0，表示为新增时判断，如果不为0，表示修改时判断
	 * @param string $whereStr 		筛选条件
	 * @return boolean 				返回值（true/false）
	 */
	function exists($tableName, $idField, $idValue, $whereStr = '') {
		$idStr = '';
		if ($idValue != 0) {
			$idStr = '`' . $idField . '`!=' . '"' . $idValue . '"';
		}
		if ($whereStr == '') {
			$whereStr = $idStr;
		} else {
			if ($idStr != '') {
				$whereStr .= ' AND ' . $idStr;
			}

		}
		if ($this->getCount($tableName, $whereStr) >= 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 树形数据
	 * @param string $tableName 		表名
	 * @param string $idField 			主键字段名
	 * @param string $textField 		查询字段名
	 * @param string $parendIDField 	上级ID字段名
	 * @param number $parendID 			上级字段值
	 * @param string $whereStr 			条件
	 * @param string $orderStr 			排序
	 * @param string $children 			子名
	 * @param string $textname 			标题别名
	 * @return array 					返回数据
	 */
	function getTreeByTable($tableName, $idField, $textField, $parendIDField, $parendID = 0, $whereStr = '', $orderStrs = '', $children = 'children', $textname = 'text') {
		if ($whereStr != '') {
			$sqlWhere = ' AND ' . $whereStr;
		} else {
			$sqlWhere = '';
		}
		$orderStr = '';
		if (trim($orderStrs) != '') {
			$orderStr = ' ORDER BY ' . $orderStrs;
		}
		if (strpos($textField, ',')) {
			$textString = $textField;
		} else {
			$textString = $textField . ' ' . $textname;
		}
		$Sql = 'SELECT ' . $idField . ' id ,' . $textString . ' FROM ' . $this->Prefix($tableName) . ' WHERE  ' . $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr;
		$this->pageSize = 0;
		if ($this->debug) {
			$this->log($Sql);
		}
		$Rs = $this->query($Sql, PDO::FETCH_ASSOC);
		$Rows = $Rs->fetchAll(PDO::FETCH_ASSOC);
		$returnData = array();
		//if (count($Rows)>0){
		foreach ($Rows as $row) {
			$chrenTotal = $this->getCount($tableName, $parendIDField . '=' . $row['id']);
			if ($chrenTotal > 0) {
				$chrenRows = $this->getTreeByTable($tableName, $idField, $textField, $parendIDField, $row['id'], $whereStr, $orderStrs, $children, $textname);
				$row[$children] = $chrenRows;
			}
			$returnData[] = $row;
		}
		return $returnData;
		//}
	}

	function getTreeDataList($tableName, $idField, $fields, $parendIDField, $parendID = 0, $whereStr = '', $orderStrs = '', $children = 'children') {
		if ($whereStr != '') {
			$sqlWhere = ' AND ' . $whereStr;
		} else {
			$sqlWhere = '';
		}
		$orderStr = '';
		if (trim($orderStrs) != '') {
			$orderStr = ' ORDER BY ' . $orderStrs;
		}

		if ($parendID == 0) {
			$limit = $this->getLimit();
		} else {
			$limit = '';
		}

		$Sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . ' WHERE  ' . $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr . '' . $limit;
		//$this->pageSize=0;
		if ($this->debug) {
			$this->log($Sql);
		}
		$Rs = $this->query($Sql, PDO::FETCH_ASSOC);
		$Rows = $Rs->fetchAll(PDO::FETCH_ASSOC);
		$returnData = array();

		foreach ($Rows as $row) {
			$chrenTotal = $this->getCount($tableName, $parendIDField . '=' . $row['id']);
			if ($chrenTotal > 0) {
				$chrenRows = $this->getTreeDataList($tableName, $idField, $fields, $parendIDField, $row['id'], $whereStr, $orderStrs, $children);
				$row[$children] = $chrenRows;
			}
			if ($parendID == 0) {
				$returnData['rows'][] = $row;
			} else {
				$returnData[] = $row;
			}

		}
		if ($parendID == 0) {
			$where = $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr;
			$returnData['total'] = $this->getCount($tableName, $where);
		}

		return $returnData;

	}
	// 获取数据树 用于 element ui 的级联选择器
	// function getTreeDataListToCascader($tableName, $idField, $fields, $parendIDField, $parendID = 0, $whereStr = '', $orderStrs = '', $children = 'children') {
	
	// 	$sqlWhere = $whereStr != '' ? ' AND ' . $whereStr : '';
	// 	$orderStr = trim($orderStrs) != '' ? $orderStr = ' ORDER BY ' . $orderStrs : '';
	// 	$limit = $parendID == 0 && $this->pageSize != 0 ? $this->getLimit() : '';

	// 	//先读取所有符合条件的，然后再按树形结构返回
	// 	$Sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . ' WHERE  ' . $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr . '' . $limit;
	// 	if ($this->debug) {
	// 		$this->log($Sql);
	// 	}

	// 	$Rs = $this->query($Sql, PDO::FETCH_ASSOC);
	// 	$Rows = $Rs->fetchAll(PDO::FETCH_ASSOC);
	// 	$returnData = array();
	// 	foreach ($Rows as $row) {
	// 		$chrenRows = $this->getTreeDataListToCascader($tableName, $idField, $fields, $parendIDField, $row[$idField], $whereStr, $orderStrs, $children);
	// 		if (empty($chrenRows)) {

	// 		} else {
	// 			$row[$children] = $chrenRows;
	// 		}
	// 		if ($parendID == 0) {
	// 			$returnData['rows'][] = $row;
	// 		} else {
	// 			$returnData[] = $row;
	// 		}

	// 	}
	// 	if ($parendID == 0) {
	// 		$where = $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr;
	// 		$returnData['total'] = $this->getCount($tableName, $where);
	// 		if (!isset($returnData['rows'])) {
	// 			$returnData['rows'] = array();
	// 		}
 
	// 	}
	
	// 	return $returnData;

	// }

	// 获取数据树 用于 element ui 的级联选择器
	function getTreeDataListToCascader($tableName, $idField, $fields, $parendIDField, $parendID = 0, $whereStr = '', $orderStrs = '', $children = 'children') {
		
		$sqlWhere = $whereStr != '' ? ' AND ' . $whereStr : '';
		$orderStr = trim($orderStrs) != '' ? $orderStr = ' ORDER BY ' . $orderStrs : '';
		$limit = $parendID == 0 && $this->pageSize != 0 ? $this->getLimit() : '';

		//先读取所有符合条件的，然后再按树形结构返回
		$Sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . ' WHERE  1=1'  . $sqlWhere . ' ' . $orderStr . '' . $limit;
		if ($this->debug) {
			$this->log($Sql);
		}
 
		$Rs = $this->query($Sql, PDO::FETCH_ASSOC);
		$Rows = $Rs->fetchAll(PDO::FETCH_ASSOC);
		// if($Rows['SiteLogo']){
		// 	$Rows['SiteLogo'] = makeImage($Rows[0]['SiteLogo']);
		// }
		$returnData = array();
  
		//转换成树形
		$chrenRows = arrayToTree($Rows, $idField, $parendIDField, $children,  $parendID);

		$returnData['rows'] = $chrenRows;
	  
		$where = $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr;
		$returnData['total'] = $this->getCount($tableName, $where);
		if (!isset($returnData['rows'])) {
			$returnData['rows'] = array();
		}
		return $returnData;

	}

	function getlazyTreeDataList($tableName, $idField, $fields, $parendIDField, $parendID = 0, $whereStr = '', $orderStrs = '', $children = 'children') {

		if ($whereStr != '') {
			$sqlWhere = ' AND ' . $whereStr;
		} else {
			$sqlWhere = '';
		}
		
		$orderStr = '';
		if (trim($orderStrs) != '') {
			$orderStr = ' ORDER BY ' . $orderStrs;
		}

		if ($parendID == 0 || IsMobile) {
			$limit = $this->getLimit();
		} else {
			$limit = '';
		}
		// if($whereStr != ''&&$whereStr!='1=1'){
		// 	$sqlWhere = ' WHERE ' . $whereStr . ' ' . $orderStr . '' . $limit;
		// }else{
		// 	$sqlWhere = ' WHERE  ' . $parendIDField . '=' . $parendID . ' AND ' . $whereStr . ' ' . $orderStr . '' . $limit;
		// }
		$Sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . ' WHERE  ' . $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr . '' . $limit;
		// $Sql = 'SELECT ' . $fields . ' FROM ' . $this->Prefix($tableName) . $sqlWhere;
		//$this->pageSize=0;
		if ($this->debug) {
			$this->log($Sql);
		}
		$Rs = $this->query($Sql, PDO::FETCH_ASSOC);
		$Rows = $Rs->fetchAll(PDO::FETCH_ASSOC);
		$returnData = array(
			'rows' => [],
			'total' => 0
		);

		foreach ($Rows as $row) {
			$chrenTotal = $this->getCount($tableName, $parendIDField . '=' . $row['id']);
			if ($chrenTotal > 0) {
				//$chrenRows=$this->getTreeDataList($tableName,$idField,$fields,$parendIDField,$row['id'],$whereStr,$orderStrs,$children);
				$row['hasChildren'] = true;
			}
			$returnData['rows'][] = $row;
			// if($parendID == 0){
			// 	$returnData['rows'][]=$row;
			// }else{
			// 	$returnData[] = $row;
			// }

		}
		if ($parendID == 0 || IsMobile) {
			$where = $parendIDField . '=' . $parendID . $sqlWhere . ' ' . $orderStr;
			$returnData['total'] = $this->getCount($tableName, $where);
		}

		return $returnData;

	}
	//面包屑数据
	function getNavByTable($tableName, $ouputFields, $parendIDField, $idField, $idValue = 0, $EndID = 0) {
		$navList = array();
		if ($idValue == $EndID || $idValue == 0) {
			return $navList;
		}
		$fields = $parendIDField . ' ParendID ,' . $idField . ' id ,' . $ouputFields . ' text';
		$whereStr = $idField . '=' . $idValue;
		$Info = $this->getInfo($tableName, $fields, $whereStr);
		$Info['SiteLogo'] = makeImage($Info['SiteLogo']);
		$navList[] = array('id' => $Info['id'], 'text' => $Info['text'],'SiteLogo' => $Info['SiteLogo'],'Contacter' => $Info['Contacter'],'SiteTel' => $Info['SiteTel']);
		if ($Info['ParendID'] != $EndID || $Info['ParendID'] != 0) {
			$returnList = $this->getNavByTable($tableName, $ouputFields, $parendIDField, $idField, $Info['ParendID'], $EndID);
			$navList = array_merge($returnList, $navList);
		}	
		return $navList;
	}
	//查询结果转换为树
	// $tableName --表名,
	// $ifields-- 显示字段，用，号隔开, 如果使用*号时，加上t.*
	// $idname-- 主键id字段名,
	// $pidname --父id字段名,
	// $textname -- 显示名称字段名
	// $WhereStr --查询条件
	// $children --儿子健名
	function getSearchToTree($tableName, $ifields, $idname, $pidname, $WhereStr = '1=1', $children = 'children', $treetype = true) {
		$returnData = array();
		$tableName = $this->Prefix($tableName);
		$stmt = $this->prepare("call get_search_tree(?,?,?,?)");
		$stmt->bindParam(1, $tableName);
		$stmt->bindParam(2, $ifields);
		$stmt->bindParam(3, $idname);
		$stmt->bindParam(4, $WhereStr);
		$stmt->execute();
		while ($row = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
			if ($row) {
				$returnData = $row;
			}
		}
		if ($treetype) {
			$returnData = arrayToTree($returnData, $idname, $pidname, $children);
		}
		return $returnData;
	}

// 以下防真pso_mysql函数
	function query(...$args) {
		$arrNum = func_num_args();
		switch ($arrNum) {
		case 1:
			return $this->handle->query(func_get_arg(0));
			break;
		case 2:
			return $this->handle->query(func_get_arg(0), func_get_arg(1));
			break;

		case 3:
			return $this->handle->query(func_get_arg(0), func_get_arg(1), func_get_arg(2));
			break;
		default:
			return false;

		}

	}

	function beginTransaction() {
		return $this->handle->beginTransaction();
	}
	function commit() {
		return $this->handle->commit();
	}
	function rollBack() {
		return $this->handle->rollBack();
	}
	function errorInfo() {
		return $this->handle->errorInfo();
	}
	function exec($sql) {
		return $this->handle->exec($sql);
	}
	function getAttribute($attribute) {
		return $this->handle->getAttribute($attribute);
	}
	function inTransaction() {
		return $this->handle->inTransaction();
	}
	function lastInsertId($name = NULL) {
		return $this->handle->lastInsertId($name);
	}
	function prepare($statement, $driver_options = array()) {
		return $this->handle->prepare($statement, $driver_options);
	}
	function quote($string, $parameter_type = PDO::PARAM_STR) {
		return $this->handle->quote($string, $parameter_type);
	}
	function setAttribute($attribute, $value) {
		return $this->handle->setAttribute($attribute, $value);
	}

}
?>