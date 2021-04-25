<?php
namespace Richwisd;
class ModelBase extends AppBase {
	//需要的一些基础变量存在这儿
	var $modelInfo; //主模型
	var $currentModelInfo; //当前模型
	var $parentModelInfo; //父级模型
	var $modelFieldList;
	var $dataFieldList;
	var $fieldListShow; //列表需要需要显示的内容
	var $addFieldList; //添加需要显示的内容
	var $editFieldList; //修改需要的内容
	var $searchFieldList; //搜索需要的内容
	var $tablesFieldsList; //表字段列表
	var $switchFieldList; //状态开关按钮列表
	var $includeJs; //array(RES_URL."vue_".PM.'.js',"","")
	var $ChildrenModelList; //子模型列表
	var $searchAttrList; //搜索数组
	var $tabsList; // 标签列表
	/*
	$tablesFieldsList['users']['TableFieldName']=array(COLUMN_COMMENT,DATA_TYPE,COLUMN_TYPE,COLUMN_DEFAULT)
	*/
	var $ParticipateEditFeildList; //参与修改字段
	var $ParticipateAddFeildList; //参与添加字段

	var $Language; //语言

	var $_modelType; // 模板类型
	var $_IsListShow; // 字段列表显示

	function __construct($app = '') {
		parent::__construct($app);
		$this->logFile = DATA_PATH . 'logs/model_log' . date('Ymd', PAGE_TIME) . '.log';
		//定义多语言类型
		$Language = array(
			'0' => array(
				'text' => 'CN',
				'lang' => array(
					'CN' => '中文',
					'EN' => 'cn',
				),
			),
			'1' => array(
				'text' => 'EN',
				'lang' => array(
					'CN' => '英文',
					'EN' => 'en',
				),
			),
		);

		$this->Language = $Language;

		if (IsMobile) {
			$this->_modelType = 'MobileModelType';
			$this->_IsListShow = 'IsMobileListShow';
		} else {
			$this->_modelType = 'ModelType';
			$this->_IsListShow = 'IsListShow';
		}

		$this->model = PM;
		$ParamA = ACT;

		$tablesFieldsList = array();
		//$model=$this->memcached->get('model');

		// unset($model[$this->model]);
		// !isset($model[$this->model])
		//用户做缓存 ，之后再做
		$cacheModuleInfo = array();
		//$this->initCache();
		$modCacheFile = DATA_PATH . '/';
		if (true) {

			//1去数据库里查询数据;
			$this->setModule('model');
			$str = $this->model . '--' . $ParamA;
			//$this->log($str);

			//获取当前模型信息
			$currentModelInfo = $this->TModel->getModelInfoByModelName($this->model, $ParamA);

			//判断当前模型是否存在
			if (empty($currentModelInfo)) {
				$this->result['status'] = 1;
				$this->result['errorMsg'] = $str . "模型不存在";
				$this->returnResult();
			}
			// $this->log($currentModelInfo);
			//注意，现在的模型结构
			//父模型-》主模型-》子模型
			//主模型关联了所涉及的表的字段信息增删改查 -》不用关联字段信息
			//主模型
			$ModelInfo = array();
			$parentModelInfo = array(); //父模型
			$ChildrenModelInfo = array();
			$ChildrenModelList = array();

			//通过模型id 获取上级模型信息，和下级模型信息（模型按钮信息）

			//判断是否需要处理字段     自定义顶级页面不用处理
			$ProcessingField = true;

			//判断上级模型是什么类型
			//如果是列表内容的话，那么主模型=当前模型
			//如果是单条数据页，1是顶级模型的话，那么这模型关联字段，2如果是其他模型的子模型的话，那么要查询其父模型
			//如果是操作按钮  必然是子模型，需查询父模型作为主模型
			//如果是自定义模   如果是子模型的话需查询父模型   ，有可能是自定义编辑页，可以拿到表字段信息
			switch ($currentModelInfo['ModelTypeClassification']) {
			case 1: //列表内容
				$ModelInfo = $currentModelInfo;

				//判断是否有父级模型
				if ($ModelInfo['PreModelID'] != 0) {
					$this->TModel->getAllParentModelList($currentModelInfo['PreModelID'], $parentModelInfo);
				}

				//查如果有上级的话就查上级，查下级比查
				$ChildrenModelInfo = $this->TModel->getChildrenModelInfo($currentModelInfo['ModelID']);
				break;
			case 2: //单条数据页
				if ($currentModelInfo['PreModelID'] == 0) {
					$ModelInfo = $currentModelInfo;
					$ChildrenModelInfo = $this->TModel->getChildrenModelInfo($currentModelInfo['ModelID']);
				} else {
					$this->TModel->getAllParentModelList($currentModelInfo['PreModelID'], $parentModelInfo);

					$ModelInfo = $this->TModel->getModelInfoByModelID($currentModelInfo['PreModelID']);
				}
				break;
			case 3: //操作按钮
				$ModelInfo = $this->TModel->getModelInfoByModelID($currentModelInfo['PreModelID']);
				break;
			case 4: //自定义
				if ($currentModelInfo['PreModelID'] == 0) {
					$ModelInfo = $currentModelInfo;
					$ProcessingField = false;
				} else {
					$ModelInfo = $this->TModel->getModelInfoByModelID($currentModelInfo['PreModelID']);
					//需要
				}

				break;
			case 5: //可视化图表

				break;
			}

			//当前模型
			$this->currentModelInfo = $currentModelInfo;
			//父类模型
			$this->parentModelInfo = array_reverse($parentModelInfo);

			//定义变量
			$dataFieldList = array(); //列表显示的字段信息
			$addFieldList = array(); //添加表单所涉及的字段 保存FieldID
			$editFieldList = array(); //修改表单所涉及的字段 保存FieldID
			$searchFieldList = array(); //列表页查询所涉及的字段 各种类型
			$modelFieldList = array(); //该模型所涉及字段的详细信息
			$fieldListShow = array(); //列表字段信息 保存FieldID
			$ParticipateEditFeildList = array(); //参与修改字段
			$ParticipateAddFeildList = array(); //参与添加字段
			$editTableData = array(); //编辑添加页的tab选项卡数据

			//搜索类型
			$searchAttrList = array(
				'text' => '',
				'date' => array(),
				'period' => array(),
				'filter' => array(),
				'tabs' => array(),
				'cascade' => array(),
			);

			$this->setClass('system');

			//自定义页面，并且是顶级页面  是否需要处理字段信息
			if ($ProcessingField) {

				//通过位置归类
				foreach ($ChildrenModelInfo as $k => $v) {
					$ChildrenModelList[$v['LayoutPosition']][] = $v;
				}

				//是否规定操作列
				if ($ModelInfo['FixedControl'] == '1') {
					$ModelInfo['FixedControl'] = "right";
				} else {
					$ModelInfo['FixedControl'] = false;
				}

				//根据列表模式拿到字段跟根据表名拿到的字段做对比
				//通过ModelID获取模型所涉及到的字段
				$FieldList = $this->TModel->getAllTableFieldsByModelID($ModelInfo['ModelID']);

				//判断是否有字段信息
				if (empty($FieldList)) {
					$this->result['status'] = 2;
					$this->result['errorMsg'] = "该模型没有字段信息";
					$this->returnResult();
				}

				//取出来的值为系统键值开始
				$tableData = $this->TModel->getArray('SELECT GROUP_CONCAT(DISTINCT (IF(ISNULL(FieldGroup),0,FieldGroup)) SEPARATOR ",") tableData FROM rw_mod_field WHERE ModelID=' . $ModelInfo['ModelID'] . ' ORDER BY FieldGroup');
				$tableData = count($tableData) && $tableData[0]['tableData'] ? explode(',', $tableData[0]['tableData']) : [];

				//如果键值全部都为0的话不进行处理
				$zeroNum = 0;
				foreach ($tableData as $fgKey => $fgValue) {
					$zeroNum = $fgValue == 0 ? $zeroNum++ : $zeroNum;

					$sql = 'select pk.KeyTitle_' . CLIENT_LANG . ' text from ' . $this->TModel->Prefix('parameters') . ' as `p` left join ' . $this->TModel->Prefix('param_keyword') . ' as `pk` on p.ParamID=pk.ParamID where p.ParamName="FieldGroup" and pk.KeyName="' . $fgValue . '"';
					$FieldGroupText = $this->TModel->getArray($sql);

					$FieldGroupText = count($FieldGroupText) && $FieldGroupText['0']['text'] ? $FieldGroupText['0']['text'] : '';
					array_push($editTableData, array('FieldGroupID' => $fgValue, 'FieldGroupText' => $FieldGroupText));
				}
				if ($zeroNum == count($tableData)) {
					$editTableData = [];
				}
				//取出来的值为系统键值  结束

				$columns = "COLUMN_NAME,DATA_TYPE,COLUMN_COMMENT,COLUMN_TYPE,IF(ISNULL(COLUMN_DEFAULT) OR COLUMN_DEFAULT IS NULL OR COLUMN_DEFAULT='NULL',NULL,COLUMN_DEFAULT) COLUMN_DEFAULT,COLUMN_KEY,(case when DATA_TYPE = 'float' or DATA_TYPE = 'double' or DATA_TYPE = 'int' then NUMERIC_PRECISION else CHARACTER_MAXIMUM_LENGTH end ) as COLUMN_LENGTH";

				//获取所有表字段信息

				$TableFields = $this->TModel->getTableFeildsByTableName($ModelInfo['ModelTableNames'], $columns);

				// $this->log("当前表字段");
				//$this->log($TableFields);

				//判断表字段信息
				if (empty($TableFields)) {
					$this->result['status'] = 3;
					$this->result['errorMsg'] = "获取" . $ModelInfo['ModelTableNames'] . "表字段异常";
					$this->returnResult();
				}

				//标识固定前几列
				$num = 1;
				//用于判断字段是否存在
				$addIDFields = false;
				//检测字段是否存在
				// if ($_GET['p'] == 'basicInformation') {
				// 	$this->log("tablesFieldsList");
				// 	$this->log($tablesFieldsList);
				// }
				// $this->log($TableFields);

				foreach ($FieldList as $k => $v) {

					$check = false;
					foreach ($TableFields as $kk => $vv) {
						if (!$addIDFields) {
							if ($vv['COLUMN_NAME'] == $ModelInfo['ModelTableID']) {
								$tablesFieldsList[$ModelInfo['ModelTableNames']][$vv['COLUMN_NAME']] = $vv;
								$addIDFields = true; //id已经添加进去了
							}
						}
						// $this->log('=====================');
						// $this->log($kk.'-'.$vv['COLUMN_NAME']);
						//如果是设置了多语言  要在字段后面添加_CN EN
						$Suffix = $v['MultLang'] == 1 ? '_' . CLIENT_LANG : '';

						//判断组合之后的字段是否匹配
						if ($v['FieldName'] . $Suffix == $vv['COLUMN_NAME']) {

							//匹配后判断是否是多语言， 好像有点重复了，后期可优化 ，主要用户把所有字段信息保存下载  CN,EN 都存在,添加、修改表单匹配
							if ($v['MultLang'] == 1) {
								//原来想ModelTitle_CN跟ModelTitle_CN 匹配，ModelTitle_EN 跟ModelTitle_EN 匹配，
								// 但是ModelTitle_CN跟ModelTitle_CN 匹配 再想 ModelTitle_CN 跟ModelTitle_EN匹配，会有问题，ModelTitle_EN确实匹配到了，但是后台的的字段信息就 就获取不了  后期可优化     所以先做 ModelTitle_CN 保存 ModelTitle_CN 信息 ，ModelTitle_CN也保存 ModelTitle_CN 信息
								foreach ($this->Language as $lang) {
									$tablesFieldsList[$ModelInfo['ModelTableNames']][$v['FieldName'] . '_' . $lang['text']] = $vv;

								}
							} else {
								$tablesFieldsList[$ModelInfo['ModelTableNames']][$vv['COLUMN_NAME']] = $vv;

							}
							//check = true 证明匹配到了

							$check = true;

						}
						if ($check && $addIDFields) {
							break;
						}
						//把id项也添加进去
						//孙广东修改，原来语句如下
						//if (!$addFieldList && $vv['COLUMN_NAME'] == $ModelInfo['ModelTableID']) {

					}

					// if ($_GET['p'] == 'basicInformation') {
					// $this->log("tablesFieldsList");
					// $this->log($tablesFieldsList);
					// }

					//mod_field 有该字段，但是实际表没有灰报 字段信息不一致
					if (!$check) {
						// $this->log($v['FieldName']);
						$this->result['status'] = 4;
						$this->result['errorMsg'] = "mod_field表字段跟" . $ModelInfo['ModelTableNames'] . "的" . $v['FieldName'] . "字段不一致";
						$this->returnResult();
					}

					//保存该模型的详细字段信息   以FieldID作为下标
					$modelFieldList[$v['FieldID']] = $v;
					//是否列表显示
					if ($v[$this->_IsListShow] == 1) {

						$fieldListShow[] = $v['FieldID'];
						//列表表头字段信息
						$dataFieldList[] = array(
							'id' => $v['FieldID'], //主键ID
							'field' => $v['FieldName'], //字段
							'label' => $v['FieldLableName'], //字段名
							'width' => $v['FieldListWidth'], //宽度
							'fixed' => $ModelInfo['FixedColumns'] >= $num ? true : false, //是否固定
							'FormControlType' => $v['FormControlType'], //控件类型
							'optionType' => $v['FormControlContentType'], //控件内容类型
							'optionName' => $v['FormOptionName'], //options
							'showType' => $v['ShowType'], //显示类型
							'IsEditEnable' => $v['IsEditEnable'], //允许修改
							'IsBatchEditEnable' => $v['IsBatchEditEnable'], //批量修改
							'ColumnClass' => $v['ColumnClass'], //列底色
							'HeaderColumnClass' => $v['HeaderColumnClass'], //列头底色
						);
						$num++;
					}
					//是否参与搜索
					if ($v['IsSearch'] == 1) {
						$searchFieldList[$v['SearchType']][] = $v['FieldID'];
						//搜索类型
						switch ($v['SearchType']) {
						case 0: //文字类型

							break;
						case 1: //筛选类型
							$searchAttrList['filter'][$v['FieldID']] = array(
								'field' => $v['FieldName'],
								'label' => $v['FieldLableName'],
								'optionType' => $v['FormControlContentType'], //类型
								'optionName' => $v['FormOptionName'], //optionname
								'value' => '', //用于保存值，搜索查询的时候拼接sql语句
							);
							break;
						case 2: //日期类型
							$searchAttrList['date'][$v['FieldID']] = array(
								'field' => $v['FieldName'],
								'label' => $v['FieldLableName'],
								'value' => '',
							);

							break;
						case 3: //时间段类型
							$searchAttrList['period'][$v['FieldID']] = array(
								'field' => $v['FieldName'],
								'label' => $v['FieldLableName'],
								'value' => '',
							);
							break;
						case 4: //标签页
							// 初始数据处理 可能需要优化
							if (empty($searchAttrList['tabs']) && empty($this->apiData['search'])) {
								$this->setClass('system');
								$res = $this->CSystem->options(['optionName' => $v['FormOptionName'], 'optionType' => $v['FormControlContentType']]);
								if ($res['status'] == 0 && isset($res['data']['selectList'][0])) {
									$this->apiData['search']['tabs'] = array(
										$v['FieldID'] => array(
											'field' => $v['FieldName'],
											'label' => $v['FieldLableName'],
											'optionType' => $v['FormControlContentType'], //类型
											'optionName' => $v['FormOptionName'], //optionname
											'value' => "{$res['data']['selectList'][0]['value']}",
										),
									);
								}
							}
							$searchAttrList['tabs'][$v['FieldID']] = array( 
								'field' => $v['FieldName'],
								'label' => $v['FieldLableName'],
								'optionType' => $v['FormControlContentType'], //类型
								'optionName' => $v['FormOptionName'], //optionname
								'value' => '',
							);
							break;
						case 5:
							$searchAttrList['cascade'][$v['FieldID']] = array(
								'field' => $v['FieldName'],
								'label' => $v['FieldLableName'],
								'optionType' => $v['FormControlContentType'], //类型
								'optionName' => $v['FormOptionName'], //optionname
								'value' => '', //用于保存值，搜索查询的时候拼接sql语句
							);
							break;
						}

					}
					//添加表单显示
					if ($v['IsAddShow'] == 1) {
						$addFieldList[] = $v['FieldID'];
					}
					//修改表单显示
					if ($v['IsEditShow'] == 1) {
						$editFieldList[] = $v['FieldID'];
					}

					//参与添加
					if ($v['IsParticipateAdd'] == 1 && !$v['IsAddShow']) {
						$ParticipateAddFeildList[] = $v['FieldID'];
					}
					//参与修改
					if ($v['IsParticipateEdit'] == 1 && !$v['IsEditShow']) {
						$ParticipateEditFeildList[] = $v['FieldID'];
					}
				}

			}

			//2将数据放入 $settings
			// $model[$this->model]['modelInfo'] = $ModelInfo;
			// $model[$this->model]['modelFieldList'] = $modelFieldList;
			// $model[$this->model]['dataFieldList'] = $dataFieldList;
			// $model[$this->model]['searchFieldList'] = $searchFieldList;
			// $model[$this->model]['editFieldList'] = $editFieldList;
			// $model[$this->model]['addFieldList'] = $addFieldList;
			// $model[$this->model]['fieldListShow'] = $fieldListShow;
			// $this->memcached->set('model',$model);
		}

		// $this->log('========================');
		// $this->log($tablesFieldsList);

		$this->modelInfo = $ModelInfo; //主模型信息
		$this->modelFieldList = $modelFieldList; //模型字段详细信息
		$this->dataFieldList = $dataFieldList; //列表字段信息
		$this->addFieldList = $addFieldList; //添加表单的字段  FieldID
		$this->editFieldList = $editFieldList; //修改表单的字段  FieldID
		$this->searchFieldList = $searchFieldList; //参与查询
		$this->fieldListShow = $fieldListShow; //列表显示的字段
		$this->tablesFieldsList = $tablesFieldsList; //表字段信息
		$this->ParticipateEditFeildList = $ParticipateEditFeildList; //参与修改  UpdTime
		$this->ParticipateAddFeildList = $ParticipateAddFeildList; //参与添加  AddTime

		$this->ChildrenModelList = $ChildrenModelList; //子模型信息
		$this->searchAttrList = $searchAttrList; //搜索的属性信息

		$this->editTableData = $editTableData; //编辑添加页的tab选项卡数据

	}

	//列表 图标  配置项或者是数据
	function listModel() {

		if (method_exists($this, 'listPre')) {
			$this->listPre();
		}

		//模型信息
		$this->result['data']['modelInfo'] = $this->modelInfo;
		//表头字段信息
		$this->result['data']['dataFieldList'] = $this->dataFieldList;
		//子模型信息
		$this->result['data']['ChildrenModelList'] = $this->ChildrenModelList;
		//列表内容
		$this->result['data']['indexData'] = $this->searchData(true);
		//搜索信息
		$this->result['data']['searchAttrList'] = $this->searchAttrList;
		//模型信息
		$this->result['data']['currentModelInfo'] = $this->currentModelInfo;
		//父模型信息
		$this->result['data']['parentModelInfo'] = $this->parentModelInfo;

		if (method_exists($this, 'listLast')) {
			$this->listLast();
		}

		$this->result['status'] = 0;
		$this->result['errorMsg'] = "获取成功";
		$this->returnResult();

	}

	//获取 表格 配置项或者是数据
	function index() {
		$c = _G('c', 'string', '');

		switch ($c) {
		case '': //表格数据   配置，数据
			$this->getIndexInfo();
			break;
		case 'searchData': //搜索数据
			$this->searchData();
			break;
		}

		$this->result['status'] = 1;
		$this->result['errorMsg'] = '操作失败';
		$this->returnResult();

	}

	function getIndexInfo() {
		if (method_exists($this, 'indexPre')) {
			$this->indexPre();
		}

		//如果是app 那就是小程序获取数据
		if (isset($this->apiData['platform']) && $this->apiData['platform'] == 'app') {

			//表头字段信息
			$this->result['data']['dataFieldList'] = $this->dataFieldList;
			//列表内容
			$this->result['data']['indexData'] = $this->searchData(true);

		} else {
			//模型信息
			$this->result['data']['modelInfo'] = $this->modelInfo;
			//模型信息
			$this->result['data']['currentModelInfo'] = $this->currentModelInfo;
			//表头字段信息
			$this->result['data']['dataFieldList'] = $this->dataFieldList;
			//父模型信息
			$this->result['data']['parentModelInfo'] = $this->parentModelInfo;
			//子模型信息
			$this->result['data']['ChildrenModelList'] = $this->ChildrenModelList;
			//列表内容
			$this->result['data']['indexData'] = $this->searchData(true);
			//搜索字段
			$this->result['data']['searchAttrList'] = $this->searchAttrList;
		}

		if (method_exists($this, 'indexLast')) {
			$this->indexLast();
		}

		$this->result['status'] = 0;
		$this->result['errorMsg'] = "获取成功";
		$this->returnResult();
	}

	//仅获取数据
	function searchData($RetData = false, $isExport = false) {
		//列表数据
		if (method_exists($this, 'searchDataPre')) {
			$this->searchDataPre();
		}
		$pid = isset($this->apiData['pid']) ? $this->apiData['pid'] : 0; //分类id
		$ParentID = isset($this->apiData['ParentID']) ? $this->apiData['ParentID'] : 0; //上级id

		$search = isset($this->apiData['search']) ? $this->apiData['search'] : ''; //搜索条件

		$page = isset($this->apiData['page']) ? $this->apiData['page'] : '1'; //页数

		$sortField = isset($this->apiData['sortField']) && $this->apiData['sortField'] != '' ? $this->apiData['sortField'] : ''; //排序字段
		$sortType = isset($this->apiData['sortType']) && $this->apiData['sortType'] != '' ? $this->apiData['sortType'] : ''; //排序类型

		//一页显示多少条数据
		$pageSize = isset($this->apiData['pageSize']) && $this->apiData['pageSize'] > 0 ? $this->apiData['pageSize'] : $this->modelInfo['DefaultPageSize'];

		$pageSize = $isExport ? 0 : $pageSize; //导出所有内容

		$where = "1=1";

		//处理搜索
		if (!empty($search)) {
			//普通文本
			if (isset($search['text']) && $search['text'] != '' && isset($this->searchFieldList[0]) && count($this->searchFieldList[0]) > 0) {
				$has = false;
				foreach ($this->searchFieldList[0] as $k => $v) {
					$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';
					if ($k == 0) {
						$where .= " AND (" . $this->modelFieldList[$v]['FieldName'] . $Suffix . " LIKE '%{$search['text']}%'";
					} else {
						$where .= " OR " . $this->modelFieldList[$v]['FieldName'] . $Suffix . " LIKE '%{$search['text']}%'";
					}
					$has = true;
				}
				if ($has) {
					$where .= ')';
				}
			}
			//下拉筛选
			if (isset($this->searchFieldList[1]) && count($this->searchFieldList[1]) > 0) {
				//有筛序
				foreach ($this->searchFieldList[1] as $k => $v) {
					$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';

					// $this->log('search');
					// $this->log($this->modelFieldList[$v]['FieldName']);
					// $this->log($search['filter'][$v]['value'] !== '');

					if (isset($search['filter'][$v]) && $search['filter'][$v]['value'] !== '') {
						$where .= " AND " . $this->modelFieldList[$v]['FieldName'] . $Suffix . " = '{$search['filter'][$v]['value']}'";
					}
				}
			}
			//时间段
			if (isset($this->searchFieldList[3]) && count($this->searchFieldList[3]) > 0) {
				foreach ($this->searchFieldList[3] as $k => $v) {
					$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';

					if (isset($search['period'][$v]) && is_array($search['period'][$v]['value'])) {
						$startTime = $search['period'][$v]['value'][0] / 1000;
						$endTime = $search['period'][$v]['value'][1] / 1000;
						$where .= " AND {$this->modelFieldList[$v]['FieldName']}{$Suffix} BETWEEN {$startTime} AND {$endTime} ";
					}
				}
			}
			// 标签页
			if (isset($this->searchFieldList[4]) && count($this->searchFieldList[4]) > 0) {
				foreach ($this->searchFieldList[4] as $k => $v) {
					$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';
					if (isset($search['tabs'][$v]) && $search['tabs'][$v]['value'] !== '') {
						$where .= " AND {$this->modelFieldList[$v]['FieldName']}{$Suffix} = '{$search['tabs'][$v]['value']}' ";
					}
				}
			}
			// 级联选择
			if (isset($this->searchFieldList[5]) && count($this->searchFieldList[5]) > 0) {
				foreach ($this->searchFieldList[5] as $k => $v) {
					$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';
					if (isset($search['cascade'][$v]) && $search['cascade'][$v]['value']) {
						$where .= " AND " . $this->modelFieldList[$v]['FieldName'] . $Suffix . " = '{$search['cascade'][$v]['value'][count($search['cascade'][$v]['value']) - 1]}'";
					}
				}
			}
		}

		//处理分类id
		if ($pid != 0) {
			$where .= " AND " . $this->modelInfo['PTableID'] . "= {$pid}";
		}
		//是否针对
		switch ($this->modelInfo['ConditionType']) {
		case 0:

			break;
		case 1: //针对用户
			$id = $_SESSION['UserID'];
			$idField = $this->modelInfo['ConditionField'] != '' ? $this->modelInfo['ConditionField'] : 'UserID';
			$where .= " and {$idField} = {$id}";
			break;
		case 2: //针对平台
			$id = $_SESSION['SiteID'];
			$idField = $this->modelInfo['ConditionField'] != '' ? $this->modelInfo['ConditionField'] : 'SiteID';
			$where .= " and {$idField} = {$id}";
			break;
		case 3:
			$id = $_SESSION['SiteID'];
			$idField = $this->modelInfo['ConditionField'] != '' ? $this->modelInfo['ConditionField'] : 'SiteID';
			$where .= " and {$idField} = {$id}";
			break;
		}

		$fields = "";

		//附加条件   也就是拼接where 条件语句
		if ($this->modelInfo['ConditionStatement'] != null && $this->modelInfo['ConditionStatement'] != " ") {
			$where .= " and {$this->modelInfo['ConditionStatement']}";
		}

		//保存二次操作的字段   图片
		$handleFields = array(
			'img' => array(),
			'ShowEncryption' => array(),
		);

		//字段信息
		foreach ($this->fieldListShow as $k => $v) {
			//判断是否是对语言
			$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';
			//组装fields
			if ($this->modelFieldList[$v]['ShowType'] == '1') {
				//日期格式
				$ShowTypeParams = $this->modelFieldList[$v]['ShowTypeParams'] ? $this->modelFieldList[$v]['ShowTypeParams'] : '%Y-%m-%d %H:%i:%s';
				$fields .= "FROM_UNIXTIME(" . $this->modelFieldList[$v]['FieldName'] . $Suffix . ",'" . $ShowTypeParams . "') AS " . $this->modelFieldList[$v]['FieldName'] . ",";
			} else if ($this->modelFieldList[$v]['ShowType'] == '3') {
				//图片
				$handleFields['img'][] = $this->modelFieldList[$v]['FieldName'];
				$fields .= $this->modelFieldList[$v]['FieldName'] . $Suffix . " as " . $this->modelFieldList[$v]['FieldName'] . ",";
			} else {
				$fields .= $this->modelFieldList[$v]['FieldName'] . $Suffix . " as " . $this->modelFieldList[$v]['FieldName'] . ",";
			}

			if ($this->modelFieldList[$v]['ShowEncryptionType'] > 0) {
				$handleFields['ShowEncryption'][$this->modelFieldList[$v]['FieldName'] . $Suffix] = $this->modelFieldList[$v]['ShowEncryptionType'];
			}

		}

		//如果是配置表的话可以不填id
		if ($this->modelInfo['ModelTableID'] == '') {
			//去掉最右边的，
			$fields = rtrim($fields, ',');
		} else {
			//有id拼接id
			$fields .= $this->modelInfo['ModelTableID'] . " as id";
		}

		$this->setModule('model');
		$orderBy = "";
		//排序
		if ($sortField != '' && $sortType != '') {
			$orderBy .= "{$sortField} {$sortType} ,";
		}

		//模型配置的排序
		$sort = $this->modelInfo['SortType'] == '' ? 'DESC' : $this->modelInfo['SortType'];
		$orderBy .= "{$this->modelInfo['ModelTableID']} {$sort}";

		//模型模板类型
		$DataList = array();
		switch ($this->modelInfo[$this->_modelType]) {
		case 14: //树形结构表格
		case 21: //树形结构表格
			$DataList = $this->TModel->getSearchTreeDataList($pageSize, $page, $this->modelInfo['ModelTableNames'], $this->modelInfo['ModelTableID'], $fields, $this->modelInfo['PreTableIDName'], $ParentID, $where, $orderBy);

		// $DataList = $this->TModel->getSearchTreeDataList($pageSize, $page, $this->modelInfo['ModelTableNames'], $this->modelInfo['ModelTableID'], $fields, $this->modelInfo['PreTableIDName'], $ParentID, $where, $orderBy);
			break;
		case 13: //普通表格
		case 15: //普通表格
		case 16: //普通表格
		case 20: //普通表格
		case 25: //普通表格
			$DataList = $this->TModel->getSearchDataList($this->modelInfo['ModelTableNames'], $fields, $where, $orderBy, $pageSize, $page);
			break;

		}
		// if ($_GET['p'] == 'paramKeyword') {
		// 	$this->log("这儿是测试");
		// 	$this->log($this->_modelType);
		// 	$this->log($this->modelInfo);
		// 	$this->log($DataList);

		// }

		// var_dump($DataList);exit;

		//判断是否有二次处理的字段
		if (count($handleFields['img']) > 0 || count($handleFields['ShowEncryption']) > 0) {
			if (isset($DataList['rows'])) {
				foreach ($DataList['rows'] as $k => $v) {
					//处理图片
					foreach ($handleFields['img'] as $kk => $vv) {
						$DataList['rows'][$k][$vv] = makeImage($v[$vv]);
					}
					//处理加密
					foreach ($handleFields['ShowEncryption'] as $EncrypField => $EncrypType) {
						$DataList['rows'][$k][$EncrypField] = $this->displayFieldEncryption($v[$EncrypField], $EncrypType);
					}
				}
			}

		}

		if (method_exists($this, 'searchDataLast')) {
			$DataList = $this->searchDataLast($DataList);
		}

		//是否return 数据
		if ($RetData) {
			return $DataList;
		}

		$this->result['data'] = $DataList;
		$this->result['status'] = 0;
		$this->result['errorMsg'] = '获取成功';
		$this->returnResult();

	}

	//加密字段
	function displayFieldEncryption($str, $type) {
		$val = $str;
		if ($type == '' || $str == '') {
			return $val;
		}

		switch ($type) {
		case 1:
			//名字加密
			$val = $this->hidestr($str, 1); //**亮
			break;
		case 2:
			$val = $this->hidestr($str, 3, 4);
			break;
		case 3:
			//身份证加密
			$val = $this->hidestr($str, 0, 4);
			$val = $this->hidestr($val, -4, 4);
			break;
		case 4:
			$val = '********';
			break;
		case 5:
			list($name, $domain) = explode('@', $str);
			$val = $this->hidestr($name, 1, -1) . '@' . $this->hidestr($domain, 0, 2); // 9****7@**.com
			break;
		}

		return $val;

	}

	//信息页
	function info() {

		if (method_exists($this, 'infoPre')) {
			$this->infoPre();
		}

		//判断主键类型是int 还是 string
		$hasIntTableID = true;
		// $this->log($this->tablesFieldsList);
		if ($this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelInfo['ModelTableID']]['DATA_TYPE'] == 'int') {
			$hasIntTableID = true;
		} else {
			$hasIntTableID = false;
		}

		//添加修改的字段，以及字段的类型，使用input,select ,checkbox框还是其他
		//如果$TablieID,不大于等于1的时候
		$this->setModule('model');

		//判断是否是配置表

		//判断 添加、修改字段
		switch ($this->modelInfo['ConditionType']) {
		case 0: //id
			$needEditFeilds = isset($this->apiData['id']) && $this->apiData['id'] != '' ? $this->editFieldList : $this->addFieldList;
			break;
		case 1: //针对用户
			//判断是否无参数页
			if ($this->modelInfo[$this->_modelType] == 18) {
				$needEditFeilds = $this->editFieldList;
			} else {
				$needEditFeilds = isset($this->apiData['id']) && $this->apiData['id'] != '' ? $this->editFieldList : $this->addFieldList;
			}
			break;
		case 2: //针对企业
			if ($this->modelInfo[$this->_modelType] == 18) {
				$needEditFeilds = $this->editFieldList;
			} else {
				$needEditFeilds = isset($this->apiData['id']) && $this->apiData['id'] != '' ? $this->editFieldList : $this->addFieldList;
			}
			break;
		}

		$editFeilds = array();

		$needEditFeilds = $this->select_sort($needEditFeilds);

		//添加或删除字段做处理

		$handleFields = array();

		foreach ($needEditFeilds as $k => $v) {
			$Suffix = $this->modelFieldList[$v]['MultLang'] == 1 ? '_' . CLIENT_LANG : '';
			$editFeilds[] = array(
				'FieldLableName' => $this->modelFieldList[$v]['FieldLableName'],
				'FieldLength' => $this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelFieldList[$v]['FieldName'] . $Suffix]['COLUMN_LENGTH'],
				'FieldName' => $this->modelFieldList[$v]['FieldName'],
				'FormControlType' => $this->modelFieldList[$v]['FormControlType'],
				'FormControlContentType' => $this->modelFieldList[$v]['FormControlContentType'],
				'FormOptionName' => $this->modelFieldList[$v]['FormOptionName'],
				'FieldTip' => $this->modelFieldList[$v]['FieldTip'],
				'FieldExplain' => $this->modelFieldList[$v]['FieldExplain'],
				'IsEditEnable' => $this->modelFieldList[$v]['IsEditEnable'],
				'FormColumn' => $this->modelFieldList[$v]['FormColumn'],
				'IsFullLine' => $this->modelFieldList[$v]['IsFullLine'],
				'FieldLabelPosition' => $this->modelFieldList[$v]['FieldLabelPosition'],
			);

			//组装fields
			if ($this->modelFieldList[$v]['ShowType'] == '1') {
				//日期格式
				$ShowTypeParams = $this->modelFieldList[$v]['ShowTypeParams'] ? $this->modelFieldList[$v]['ShowTypeParams'] : '%Y-%m-%d %H:%i:%s';
				$handleFields['date'][$this->modelFieldList[$v]['FieldName']]['param'] = $ShowTypeParams;
			} else if ($this->modelFieldList[$v]['ShowType'] == '3') {
				//图片
				$handleFields['img'][$this->modelFieldList[$v]['FieldName']] = '';
			}

			if ($this->modelFieldList[$v]['ShowEncryptionType'] > 0) {
				$handleFields['ShowEncryption'][$this->modelFieldList[$v]['FieldName'] . $Suffix] = $this->modelFieldList[$v]['ShowEncryptionType'];
			}
		}

		$this->result['data']['editFeilds'] = $editFeilds;

		$Info = $this->getInfo();
		if (isset($this->apiData['platform']) && $this->apiData['platform'] == 'pc') {
			$this->result['data']['ModelInfo'] = $this->modelInfo;
			$this->result['data']['ChildrenModelList'] = $this->ChildrenModelList;
		} else {
			if (!empty($handleFields) && !empty($Info)) {
				foreach ($handleFields as $k => $v) {
					if ($k == 'date') {
						foreach ($v as $kk => $fields) {
							$Info[$kk] = date($fields['param'], $Info[$kk]);
						}
					} else if ($k == 'img') {
						foreach ($v as $kk => $fields) {
							$Info[$kk] = makeImage($Info[$kk]);

						}
					} else if ($k == 'ShowEncryption') {
						foreach ($handleFields['ShowEncryption'] as $EncrypField => $EncrypType) {
							$Info[$EncrypField] = $this->displayFieldEncryption($Info[$EncrypField], $EncrypType);
						}
					}

				}
			}

		}

		$this->result['data']['Info'] = $Info;
		$this->result['status'] = 0;
		$this->result['errorMsg'] = '获取成功';

		if (method_exists($this, 'infoLast')) {
			$this->infoLast();
		}

		$this->returnResult();
	}

	//添加修改复制所需的字段
	function getEditFieldList() {

		// $this->log('===========getEditFieldList============');
		// $this->log($this->tablesFieldsList);
		// $this->log($this->modelInfo);

		//判断主键类型是int 还是 string
		$hasIntTableID = true;
		// $this->log("tablesFieldsList");
		// $this->log($this->tablesFieldsList);

		if ($this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelInfo['ModelTableID']]['DATA_TYPE'] == 'int') {
			$hasIntTableID = true;
		} else {
			$hasIntTableID = false;
		}
		// if ($_GET['p'] == 'addressInformation') {

		// 	$this->log("tablesFieldsList");
		// 	$this->log($this->tablesFieldsList);
		// 	if ($hasIntTableID) {
		// 		$this->log("判断ID为INT");
		// 	}

		// }
		//添加修改的字段，以及字段的类型，使用input,select ,checkbox框还是其他
		//如果$TablieID,不大于等于1的时候
		$this->setModule('model');

		//判断是否是配置表
		$needEditFeilds = array();
		//判断 添加、修改字段
		switch ($this->modelInfo['ConditionType']) {
		case 0: //id
			$needEditFeilds = isset($this->apiData['id']) && $this->apiData['id'] != '' ? $this->editFieldList : $this->addFieldList;
			break;
		case 1: //针对用户
			$needEditFeilds = $this->editFieldList;
			break;
		case 2: //针对企业
			$needEditFeilds = $this->editFieldList;
			break;
		case 3: //企业相关
			$needEditFeilds = isset($this->apiData['id']) && $this->apiData['id'] != '' ? $this->editFieldList : $this->addFieldList;
			break;
		}

		$editFeilds = array();

		$needEditFeilds = $this->select_sort($needEditFeilds);

		//添加或删除字段做处理
		foreach ($needEditFeilds as $k => $v) {

			if ($this->modelFieldList[$v]['MultLang'] == 1) {

				foreach ($this->Language as $lang) {

					$editFeilds[] = array(
						'FieldLableName' => '(' . $lang['lang'][CLIENT_LANG] . ')' . $this->modelFieldList[$v]['FieldLableName'],
						'FieldLength' => $this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelFieldList[$v]['FieldName'] . '_' . $lang['text']]['COLUMN_LENGTH'],
						'FieldName' => $this->modelFieldList[$v]['FieldName'] . "_" . $lang['text'],
						'FormControlType' => $this->modelFieldList[$v]['FormControlType'],
						'FormControlContentType' => $this->modelFieldList[$v]['FormControlContentType'],
						'FormOptionName' => $this->modelFieldList[$v]['FormOptionName'],
						'FieldTip' => $this->modelFieldList[$v]['FieldTip'],
						'FieldExplain' => $this->modelFieldList[$v]['FieldExplain'],
						'IsEditEnable' => $this->modelFieldList[$v]['IsEditEnable'],
						'FormFieldCheck' => $this->modelFieldList[$v]['FormFieldCheck'],
						'FormColumn' => $this->modelFieldList[$v]['FormColumn'],
						'IsFullLine' => $this->modelFieldList[$v]['IsFullLine'],
						'FieldLabelPosition' => $this->modelFieldList[$v]['FieldLabelPosition'],
						'FieldGroup' => count($this->editTableData) && !$this->modelFieldList[$v]['FieldGroup'] ? 0 : $this->modelFieldList[$v]['FieldGroup'],
					);
				}
			} else {
				$editFeilds[] = array(
					'FieldLableName' => $this->modelFieldList[$v]['FieldLableName'],
					'FieldLength' => $this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelFieldList[$v]['FieldName']]['COLUMN_LENGTH'],
					'FieldName' => $this->modelFieldList[$v]['FieldName'],
					'FormControlType' => $this->modelFieldList[$v]['FormControlType'],
					'FormControlContentType' => $this->modelFieldList[$v]['FormControlContentType'],
					'FormOptionName' => $this->modelFieldList[$v]['FormOptionName'],
					'FieldTip' => $this->modelFieldList[$v]['FieldTip'],
					'FieldExplain' => $this->modelFieldList[$v]['FieldExplain'],
					'IsEditEnable' => $this->modelFieldList[$v]['IsEditEnable'],
					'FormFieldCheck' => $this->modelFieldList[$v]['FormFieldCheck'],
					'FormColumn' => $this->modelFieldList[$v]['FormColumn'],
					'IsFullLine' => $this->modelFieldList[$v]['IsFullLine'],
					'FieldLabelPosition' => $this->modelFieldList[$v]['FieldLabelPosition'],
					'FieldGroup' => count($this->editTableData) && !$this->modelFieldList[$v]['FieldGroup'] ? 0 : $this->modelFieldList[$v]['FieldGroup'],
				);
			}

		}

		$this->result['data']['editFeilds'] = $editFeilds;
		$this->result['data']['currentModelInfo'] = $this->currentModelInfo;
		$this->result['data']['parentModelInfo'] = $this->parentModelInfo;
		$this->result['data']['editTableData'] = $this->editTableData;

		$this->result['data']['modelInfo'] = $this->modelInfo;
		$this->result['status'] = 0;
		$this->result['errorMsg'] = '获取成功';

	}

	//添加
	function add() {
		if (method_exists($this, 'addPre')) {
			$this->addPre();
		}

		//获取字段信息
		$this->getEditFieldList();

		$this->result['data']['Info'] = $this->getInfo();
		$this->result['data']['ChildrenModelList'] = $this->ChildrenModelList;

		if (method_exists($this, 'addLast')) {
			$this->addLast();
		}

		$this->returnResult();
	}

	//获取添加修改界面配置项
	function edit() {
		if (method_exists($this, 'editPre')) {
			$this->editPre();
		}

		//获取字段信息
		if ($this->model == 'menu') {

		}

		$this->getEditFieldList();

		$this->result['data']['Info'] = $this->getInfo();
		
		$this->result['data']['ChildrenModelList'] = $this->ChildrenModelList;

		if (method_exists($this, 'editLast')) {
			$this->editLast();
		}
		$this->returnResult();
	}

	//复制内容
	function copy() {
		if (method_exists($this, 'copyPre')) {
			$this->copyPre();
		}

		//获取字段信息
		$this->getEditFieldList();
		$Info = $this->getInfo();
		$Info['id'] = 0;
		$this->result['data']['Info'] = $Info;

		if (method_exists($this, 'copyLast')) {
			$this->copyLast();
		}

		$this->returnResult();
	}

	function getInfo() {
		//根据表的ID
		if (method_exists($this, 'infoPre')) {
			$this->infoPre();
		}

		$idField = '';

		if ($this->modelInfo['ConditionField'] != '') {
			$idField = $this->modelInfo['ConditionField'];
		} else {
			$idField = $this->modelInfo['ModelTableID'];
		}

		$idWhere = "";

		$id = '';

		$IsSite = false;

		// 附加条件
		switch ($this->modelInfo['ConditionType']) {
		case 0:
			$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
			$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id}";
			break;
		case 1:
			//无参页
			if ($this->modelInfo[$this->_modelType] == 18 || $this->modelInfo[$this->_modelType] == 6) {
				$id = $_SESSION['UserID'];
				$idWhere .= "{$idField} = {$id}";
			} else {
				$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
				$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} and {$idField} = {$_SESSION['UserID']}";
			}

			break;
		case 2:
			if ($this->modelInfo[$this->_modelType] == 18 || $this->modelInfo[$this->_modelType] == 6) {
				$id = $_SESSION['SiteID'];
				$idWhere .= "{$idField} = {$id}";
				$IsSite = true;
			} else {
				$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
				$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} and {$idField} = {$_SESSION['SiteID']}";
			}

			break;
		case 3: //针对平台
			$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
			$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} AND {$idField}=0";
			break;
		}

		$Info = array();
		if ($_GET['p'] == 'addressInformation') {
			$this->log("=============查询条件====================");
			$this->log($id);
			$this->log($idWhere);
			$this->log($IsSite);
			$this->log($this->editFieldList);
			$this->log($this->modelInfo['ConditionType']);

			$this->log('where条件');
			$this->log($this->modelInfo);
			$this->log($idWhere);
			$this->log($this->_modelType);
		}
		//添加
		if ($id === '' && !$IsSite) {
			foreach ($this->editFieldList as $k => $v) {
				//$Suffix = $this->modelFieldList[$v]['MultLang']==1?'_'.CLIENT_LANG:'';

				if ($this->modelFieldList[$v]['MultLang'] == 1) {
					//en
					foreach ($this->Language as $lang) {
						$Info[$this->modelFieldList[$v]['FieldName'] . '_' . $lang['text']] = $this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelFieldList[$v]['FieldName'] . '_' . $lang['text']]['COLUMN_DEFAULT'];
					}

				} else {
					$Info[$this->modelFieldList[$v]['FieldName']] = $this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelFieldList[$v]['FieldName']]['COLUMN_DEFAULT'];
				}
			}
			if ($this->modelInfo['ModelTableID'] != '') {
				$Info['id'] = 0;
			}

			// $this->log('============INFO==================');
			// $this->log($Info);
			// $this->log($this->tablesFieldsList);

		} else {

			//修改
			$this->setModule('model');

			$fields = '';
			foreach ($this->editFieldList as $k => $v) {
				//$Suffix = $this->modelFieldList[$v]['MultLang']==1?'_'.CLIENT_LANG:'';
				//$fields .= $this->modelFieldList[$v]['FieldName'].$Suffix." as ".$this->modelFieldList[$v]['FieldName'].",";

				if ($this->modelFieldList[$v]['MultLang'] == 1) {
					foreach ($this->Language as $lang) {
						$fields .= $this->modelFieldList[$v]['FieldName'] . '_' . $lang['text'] . ',';
					}
				} else {
					$fields .= $this->modelFieldList[$v]['FieldName'] . ',';
				}

			}

			//如果是配置表的话可以不填id
			if ($this->modelInfo['ModelTableID'] == '') {
				//去掉最右边的，
				$fields = rtrim($fields, ',');
			} else {
				//有id拼接id
				$fields .= $this->modelInfo['ModelTableID'] . " as id";
			}

			$where = $idWhere;
			if ($this->modelInfo['ConditionStatement'] != null && $this->modelInfo['ConditionStatement'] != "") {
				$where .= " and {$this->modelInfo['ConditionStatement']}";
			}
			//判断有没有附加添加

			$this->TModel->debug = true;
			if ($_GET['p'] == 'basicInformation') {
				$this->log("=============B====================");
				$this->log($fields);
				$this->log($where);
			}
			$Info = $this->TModel->getDataInfo($this->modelInfo['ModelTableNames'], $fields, $where);
			$this->TModel->debug = false;

		}

		if (method_exists($this, 'infoLast')) {
			$this->infoLast();
		}
		return $Info;
	}

	//保存一条记录
	function save() {

		$c = _G('c', 'string', '');

		switch ($c) {
		case '': //普通表单保存
			$this->saveData();
			break;
		case 'batchSave': //批量保存
			$this->batchSave();
			break;
		case 'changeStatus':
			$this->changeStatus();
			break;
		case 'saveSingleField':
			$this->saveSingleField();
			break;

		}

		$this->result['status'] = 1;
		$this->result['errorMsg'] = '操作失败';
		$this->returnResult();

	}
	//批量保存
	function batchSave() {
		//批量的字段
		$batchFieldValue = $this->apiData['batchFieldValue'];

		if (empty($batchFieldValue)) {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = '没有修改';
			$this->returnResult();
		}

		$this->setModule('model');

		foreach ($batchFieldValue as $k => $v) {

			$this->TModel->update($this->modelInfo['ModelTableNames'], $v, "{$this->modelInfo['ModelTableID']} = {$k}");

		}

		$this->result['status'] = 0;
		$this->result['errorMsg'] = '保存成功';
		$this->returnResult();

	}

	//普通表单保存
	function saveData() {

		$parameter = array();

		if (method_exists($this, 'savePre')) {
			$this->savePre();
		}

		$this->setModule('model');

		$PostData = $this->apiData;

		//检验数据
		$this->VerifyFieldValue($PostData);

		$idField = '';

		//更新所需要的数据
		$editData = array();

		//判断有没有针对的字段
		if ($this->modelInfo['ConditionField'] != '') {
			$idField = $this->modelInfo['ConditionField'];
		} else {
			//没有的话就针对表id
			$idField = $this->modelInfo['ModelTableID'];
		}

		$id = '';
		$idWhere = "";

		//$this->log($this->modelInfo['ConditionType']);

		$IsSite = false;
		switch ($this->modelInfo['ConditionType']) {
		case 0:
			$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
			$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id}";
			break;
		case 1:
			if ($this->modelInfo[$this->_modelType] == 18 || $this->modelInfo[$this->_modelType] == 6) {
				$id = $_SESSION['UserID'];
				$idWhere .= "{$idField} = {$id}";
			} else {
				$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
				$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} and {$idField} = {$_SESSION['UserID']}";
				$editData[$idField] = $_SESSION['UserID'];
			}
			break;
		case 2:
			if ($this->modelInfo[$this->_modelType] == 18 || $this->modelInfo[$this->_modelType] == 6) {
				$id = $_SESSION['SiteID'];
				$idWhere .= "{$idField} = {$id}";
				$IsSite = true;
			} else {
				$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
				$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} and {$idField} = {$_SESSION['SiteID']}";
				$editData[$idField] = $_SESSION['SiteID'];
			}
			break;
		case 3:
			//针对平台
			if ($this->modelInfo[$this->_modelType] == 18 || $this->modelInfo[$this->_modelType] == 6) {
				$id = $_SESSION['SiteID'];
				$idWhere .= "{$idField} = {$id}";
				$IsSite = true;
			} else {
				$id = isset($this->apiData['id']) ? $this->apiData['id'] : '';
				$idWhere .= "{$this->modelInfo['ModelTableID']} = {$id} and {$idField} = {$_SESSION['SiteID']}";
				$editData[$idField] = $_SESSION['SiteID'];
			}
			break;
		};

		// $this->log($IsSite);
		// $this->log($editData);
		// $this->log('条件');
		// $this->log($idWhere);

		// return false;

		//$id = isset($this->apiData['id'])?$this->apiData['id']:0;

		if (isset($this->apiData['id'])) {
			unset($PostData['id']);
		}

		$editFeilds = array();
		//判断主键类型是int 还是 string
		$hasIntTableID = true;
		$existFeild = 0;

		if ($this->tablesFieldsList[$this->modelInfo['ModelTableNames']][$this->modelInfo['ModelTableID']]['DATA_TYPE'] == 'int') {
			$hasIntTableID = true;
			$editFeilds = $id == 0 ? $this->addFieldList : $this->editFieldList;

		} else {

			$hasIntTableID = false;
			//判断该信息是否存在
			$where = "{$this->modelInfo['ModelTableID']} = '{$id}'";
			$existFeild = $this->TModel->getCount($this->modelInfo['ModelTableNames'], $where);

			$editFeilds = $existFeild ? $this->editFieldList : $this->addFieldList;

		}

		$UnableEditFields = array();

		//数据校验 并 组装编辑的值
		foreach ($editFeilds as $k => $v) {

			if ($this->modelFieldList[$v]['FormFieldCheck'] != '') {
				if ($this->modelFieldList[$v]['MultLang'] == 1) {
					foreach ($this->Language as $lang) {
						$this->checkApiForm("(" . $lang['lang'][CLIENT_LANG] . ")" . $this->modelFieldList[$v]['FieldLableName'], $this->modelFieldList[$v]['FieldName'] . '_' . $lang['text'], $this->modelFieldList[$v]['FormFieldCheck']);
					}

				} else {
					$this->checkApiForm($this->modelFieldList[$v]['FieldLableName'], $this->modelFieldList[$v]['FieldName'], $this->modelFieldList[$v]['FormFieldCheck']);
				}

			}

			if ($this->modelFieldList[$v]['FormControlType'] == 'datepicker') {
				$PostData[$this->modelFieldList[$v]['FieldName']] = $PostData[$this->modelFieldList[$v]['FieldName']] / 1000;
			} else if ($this->modelFieldList[$v]['FormControlType'] == 'rwAreaSelect') {

				$AreaCodeArr = $PostData[$this->modelFieldList[$v]['FieldName']];
				if ($AreaCodeArr) {
					$AreaCode = implode(',', $AreaCodeArr);
					$textInfo = $this->TModel->getArray("SELECT areaName,areaID FROM rw_area_code WHERE areaID IN({$AreaCode})");
					if ($textInfo) {
						$AreaCodeText = array();
						foreach ($AreaCodeArr as $kk => $vv) {
							foreach ($textInfo as $kkk => $vvv) {
								if ($vvv['areaID'] == $vv) {
									$AreaCodeText[$vv] = $vvv['areaName'];
								}
							}
						}
						$editData[$this->modelFieldList[$v]['FieldName']] = $AreaCode;
						$editData[$this->modelFieldList[$v]['FieldName'] . 'Text'] = implode(' ', $AreaCodeText);
						continue;
					} else {
						$this->result['status'] = 1;
						$this->result['errorMsg'] = '获取地区数据错误';
						$this->returnResult();
					}
				}
			} else if ($this->modelFieldList[$v]['FormControlType'] == 'multiSelect') {
				$multiSelect = $PostData[$this->modelFieldList[$v]['FieldName']];
				$PostData[$this->modelFieldList[$v]['FieldName']] = implode(',', $multiSelect);
			} else if ($this->modelFieldList[$v]['FormControlType'] == 'tag') {
				//目前的标签字段为中英文
				if ($PostData[$this->modelFieldList[$v]['FieldName']]) {
					if ($this->modelFieldList[$v]['MultLang'] == 1) {
						$PostData[$this->modelFieldList[$v]['FieldName'] . '_CN'] = implode(',', $PostData[$this->modelFieldList[$v]['FieldName'] . '_CN']);
						$PostData[$this->modelFieldList[$v]['FieldName'] . '_EN'] = implode(',', $PostData[$this->modelFieldList[$v]['FieldName'] . '_EN']);
					} else {
						$PostData[$this->modelFieldList[$v]['FieldName']] = implode(',', $PostData[$this->modelFieldList[$v]['FieldName']]);
					}
				}

			} else if ($this->modelFieldList[$v]['FormControlType'] == 'cascaderStrictlyMore') {
				$cascaderStrictlyMore = $PostData[$this->modelFieldList[$v]['FieldName']];
				$PostData[$this->modelFieldList[$v]['FieldName']] = $cascaderStrictlyMore ? implode(',', $cascaderStrictlyMore) : '';
			} else if ($this->modelFieldList[$v]['FormControlType'] == 'checkbox') {
				$multiSelect = $PostData[$this->modelFieldList[$v]['FieldName']];
				$PostData[$this->modelFieldList[$v]['FieldName']] = implode(',', $multiSelect);
			}

			if ($this->modelFieldList[$v]['MultLang'] == 1) {
				foreach ($this->Language as $lang) {
					$editData[$this->modelFieldList[$v]['FieldName'] . '_' . $lang['text']] = $PostData[$this->modelFieldList[$v]['FieldName'] . '_' . $lang['text']];
				}
			} else {
				$editData[$this->modelFieldList[$v]['FieldName']] = $PostData[$this->modelFieldList[$v]['FieldName']];
			}

			if ($this->modelFieldList[$v]['IsEditEnable'] == 0) {
				$UnableEditFields[] = $this->modelFieldList[$v]['FieldName'];
			}

		}

		// $this->log('===============');
		// $this->log($editData);

		$this->TModel->debug = true;

		if (($hasIntTableID && $id == 0 && !$IsSite) || (!$hasIntTableID && $existFeild == 0 && !$IsSite)) {

			// 获取组合参与添加的字段
			foreach ($this->ParticipateAddFeildList as $k => $v) {
				$editData[$this->modelFieldList[$v]['FieldName']] = $this->modelFieldList[$v]['ParticipateParam'] == 'date' ? PAGE_TIME : $this->modelFieldList[$v]['ParticipateParam'];
			}

			// $this->log('save insert 条件');
			// $this->log($editData);
			// return false;

			$addCheck = $this->TModel->insert($this->modelInfo['ModelTableNames'], $editData);
			if ($addCheck) {
				$this->result['status'] = 0;
				$this->result['errorMsg'] = '添加成功';
				$this->result['data']['addID'] = $addCheck;

				$parameter['id'] = $addCheck;

			} else {
				$this->result['status'] = 1;
				$this->result['errorMsg'] = '添加失败';
			}
		} else {

			// 获取组合参与修改的字段

			foreach ($this->ParticipateEditFeildList as $k => $v) {
				$editData[$this->modelFieldList[$v]['FieldName']] = $this->modelFieldList[$v]['ParticipateParam'] == 'date' ? PAGE_TIME : $this->modelFieldList[$v]['ParticipateParam'];
			}
			foreach ($UnableEditFields as $k => $v) {
				if (isset($editData[$v])) {
					unset($editData[$v]);
				}
			}

			$updWhere = $idWhere;
			if ($this->modelInfo['ConditionStatement'] != null && $this->modelInfo['ConditionStatement'] != "" && $this->modelInfo['ConditionStatement'] != "NULL") {
				$updWhere .= " and {$this->modelInfo['ConditionStatement']}";
			}

			$updCheck = $this->TModel->update($this->modelInfo['ModelTableNames'], $editData, $updWhere);
			// if($updCheck){
			$this->result['status'] = 0;
			$this->result['errorMsg'] = '修改成功';
			// }else{
			// 	$this->result['status']=1;
			// 	$this->result['errorMsg']='修改失败';
			// }
		}
		$this->TModel->debug = false;

		if (method_exists($this, 'saveLast')) {
			$postData['PostData'] = $PostData;
			$postData['idWhere'] = $idWhere;
			$this->saveLast($parameter, $postData);
		}

		$this->returnResult();
	}

	//保存一个字段
	function saveSingleField() {

		$ActionID = $this->apiData['ActionID'] ? $this->apiData['ActionID'] : 0;
		$fieldName = $this->apiData['fieldName'] ? $this->apiData['fieldName'] : '';
		$value = $this->apiData['value'] ? $this->apiData['value'] : '';

		if ($ActionID == 0 || $fieldName == '') {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = '数据错误';
			$this->returnResult();
		}

		$this->setModule('model');

		//$this->TModel->debug = true;
		$FieldID = $this->TModel->getFiledIDByModelIDAndFieldName($this->modelInfo['ModelID'], $fieldName);
		//$this->TModel->debug = false;

		if (empty($FieldID)) {
			$this->result['status'] = 2;
			$this->result['errorMsg'] = '没有该字段信息';
			$this->returnResult();
		}

		//检测value是否正确
		if ($this->modelFieldList[$FieldID]['FormFieldCheck'] != '') {
			$this->checkApiForm($this->modelFieldList[$FieldID]['FieldLableName'], $this->modelFieldList[$FieldID]['FieldName'], $this->modelFieldList[$FieldID]['FormFieldCheck'], false, $value);
		}

		$updData = array();
		$updData[$fieldName] = $value;
		$where = "{$this->modelInfo['ModelTableID']} = '{$ActionID}'";
		$updCheck = $this->TModel->update($this->modelInfo['ModelTableNames'], $updData, $where);

		if ($updCheck) {
			$this->result['status'] = 0;
			$this->result['errorMsg'] = '修改成功';
			$this->returnResult();
		} else {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = '修改失败';
			$this->returnResult();
		}

	}

	//删除
	function deleteData() {
		if (method_exists($this, 'deleteDataPre')) {
			$this->deleteDataPre();
		}

		$id = $this->apiData['id'];
		$modelType = $this->apiData['modelType'];

		$this->setModule('model');
		if ($modelType == 14) {
			$result = $this->loopDelete($id);
			$this->result = $result;
		} else {
			$where = $this->modelInfo['ModelTableID'] . " = '{$id}'";

			$delCheck = $this->TModel->delete($this->modelInfo['ModelTableNames'], $where);

			if ($delCheck) {
				$this->result['status'] = 0;
				$this->result['errorMsg'] = "删除成功";
			} else {
				$this->result['status'] = 1;
				$this->result['errorMsg'] = "删除失败";
			}
		}

		if (method_exists($this, 'deleteDataLast')) {
			$this->deleteDataLast();
		}
		$this->returnResult();
	}

	//批量删除
	function batchDelete() {
		if (method_exists($this, 'deleteDataPre')) {
			$this->deleteDataPre();
		}

		$this->setModule('model');
		$batchSelections = $this->apiData['batchSelections'];
		$modelType = $this->apiData['modelType'];

		if ($modelType == 14) {
			foreach ($batchSelections as $key => $value) {
				$result = $this->loopDelete($value);
				if ($result['status'] != 0) {
					break;
				}
			}
			$this->result = $result;
		} else {
			$ids = implode(',', $batchSelections);
			$where = $this->modelInfo['ModelTableID'] . " in ({$ids})";

			$delCheck = $this->TModel->delete($this->modelInfo['ModelTableNames'], $where);

			if ($delCheck) {
				$this->result['status'] = 0;
				$this->result['errorMsg'] = "删除成功";
			} else {
				$this->result['status'] = 1;
				$this->result['errorMsg'] = "删除失败";
			}
		}

		if (method_exists($this, 'deleteDataLast')) {
			$this->deleteDataLast();
		}
		$this->returnResult();
	}

	function loopDelete($id) {
		$this->setModule('model');

		$childList = $this->TModel->select($this->modelInfo['ModelTableNames'], $this->modelInfo['ModelTableID'], $this->modelInfo['PreTableIDName'] . "='{$id}'");

		if ($childList) {
			foreach ($childList as $key => $value) {
				$res = $this->loopDelete($value[$this->modelInfo['ModelTableID']]);
				if ($res['status'] != 0) {
					return $res;
				}
			}
		}

		$where = $this->modelInfo['ModelTableID'] . " = '{$id}'";

		$delCheck = $this->TModel->delete($this->modelInfo['ModelTableNames'], $where);

		if ($delCheck) {
			$result['status'] = 0;
			$result['errorMsg'] = "删除成功";
		} else {
			$result['status'] = 1;
			$result['errorMsg'] = "删除失败";
		}
		return $result;
	}

	function changeStatus() {
		$id = $this->apiData['id'];
		$field = $this->apiData['field'];
		$value = $this->apiData['value'];

		if ($id == '' || $field == '' && $value == '') {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = "数据错误";
		}

		if ($value == 1) {
			$value = 0;
		} else {
			$value = 1;
		}

		//判断字段是否是switch开关  后面再判断

		$this->setModule('model');
		$checkSwitch = $this->TModel->switchCell($this->modelInfo['ModelTableNames'], $this->modelInfo['ModelTableID'], $id, $field, $value);

		if ($checkSwitch) {
			$this->result['status'] = 0;
			$this->result['errorMsg'] = "修改成功";
		} else {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = "修改失败";
		}

		$this->returnResult();
	}

	// 通过数量获取字母
	protected function getLetterByNum($num) {
		$range = array();
		$letters = range('A', 'Z');
		for ($i = 0; $i < $num; $i++) {
			$position = $i * 26;
			foreach ($letters as $ii => $letter) {
				$position++;
				if ($position <= $num) {
					$range[] = ($position > 26 ? $range[$i - 1] : '') . $letter;
				}

			}
		}
		return $range;
	}

	function exportExcel() {
		//和searchData一样的条件，但没有分页;g学要获取index中的字段;
		$exportData = $this->searchData(true, true);

		$dataFieldList = $this->dataFieldList;
		$hearchData = array();
		foreach ($dataFieldList as $k => $v) {
			$hearchData[] = $v['label'];
		}

		include_once FRAME_PATH . 'lib/PHPExcel/PHPExcel/IOFactory.php';
		include_once FRAME_PATH . 'lib/PHPExcel/PHPExcel.php';
		$excel = new PHPExcel();
		$letter = $this->getLetterByNum(count($hearchData));

		array_unshift($exportData, $hearchData);
		foreach ($exportData as $k => $v) {
			$i = $k + 1;
			$j = 0;
			foreach ($v as $kk => $vv) {
				if (strval($kk) == 'id') {
					continue;
				}

				$excel->getActiveSheet()->setCellValue("$letter[$j]$i", "$vv");
				++$j;
			}
		}

		//创建Excel输入对象
		ob_end_clean(); // 设置缓冲区 避免乱码
		$write = new PHPExcel_Writer_Excel5($excel);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		// header('Content-Disposition:attachment;filename="'.$FileName.'.xls"');
		header("Content-Transfer-Encoding:binary");

		$write->save('php://output');
	}

	function importExcel() {

	}

	//字段排序
	function select_sort($arr) {

		for ($i = 0, $len = count($arr); $i < $len - 1; $i++) {
			$p = $i;
			//$j 当前都需要和哪些元素比较，$i 后边的。
			for ($j = $i + 1; $j < $len; $j++) {
				//$arr[$p] 是 当前已知的最小
				if ($this->modelFieldList[$arr[$p]]['FormOrder'] < $this->modelFieldList[$arr[$j]]['FormOrder']) {
					//比较，发现更小的,记录下最小值的位置；并且在下次比较时，
					// 应该采用已知的最小值进行比较。
					$p = $j;
				}
			}

			if ($p != $i) {

				$tmp = $arr[$p];

				$arr[$p] = $arr[$i];

				$arr[$i] = $tmp;

			}

		}

		return $arr;

	}

	/**
	 * [VerifyFieldValue 验证唯一字段值是否已存在]
	 * @param [array] $data      [待验证的数据]
	 */
	protected function VerifyFieldValue($data) {
		$tableName = $this->modelInfo['ModelTableNames'];

		$this->setModule('model');
		// 获取表唯一索引信息
		$UniqueFieldInfo = $this->TModel->getArray("SHOW INDEX FROM rw_{$tableName} WHERE Non_unique=0 AND Key_name!='PRIMARY'");
		//.print_r($UniqueFieldInfo);
		// print_r($data);
		// die();
		$UniqueFieldGroup = array();
		$Tip = array();
		foreach ($UniqueFieldInfo as $k => $v) {
			if (isset($data[$v['Column_name']])) {
				$dataField = $data[$v['Column_name']];
				$UniqueFieldGroup[$v['Key_name']][] = $v['Column_name'] . "='{$dataField}'";
				$Tip[] = $v['Column_name'];
			}

		}

		if (empty($UniqueFieldGroup)) {
			return;
		}
		// array('status' => 0, 'errorMsg' => '没有唯一字段');

		foreach ($UniqueFieldGroup as $k => $v) {
			$UniqueFieldGroup[$k] = implode(' AND ', $v);
		}

		$where = implode(' OR ', $UniqueFieldGroup);
		if ($data['id']) {
			$where .= " AND {$this->modelInfo['ModelTableID']}!={$data['id']}";
		}

		$info = $this->TModel->getInfo($tableName, '*', $where);
		// echo $tableName;
		// print_r($where);
		// exit();

		if ($info) {
			$this->result['status'] = 1;
			$this->result['errorMsg'] = '字段：' . implode('、', $Tip) . '中有数据重复';
			$this->returnResult();
		}

	}

	/**
	 * 将一个字符串部分字符用$re替代隐藏
	 * @param string    $string   待处理的字符串
	 * @param int       $start    规定在字符串的何处开始，
	 *                            正数 - 在字符串的指定位置开始
	 *                            负数 - 在从字符串结尾的指定位置开始
	 *                            0 - 在字符串中的第一个字符处开始
	 * @param int       $length   可选。规定要隐藏的字符串长度。默认是直到字符串的结尾。
	 *                            正数 - 从 start 参数所在的位置隐藏
	 *                            负数 - 从字符串末端隐藏
	 * @param string    $re       替代符
	 * @return string   处理后的字符串
	 */
	function hidestr($string, $start = 0, $length = 0, $re = '*') {
		if (empty($string)) {
			return false;
		}

		$strarr = array();
		$mb_strlen = mb_strlen($string);
		while ($mb_strlen) {
//循环把字符串变为数组
			$strarr[] = mb_substr($string, 0, 1, 'utf8');
			$string = mb_substr($string, 1, $mb_strlen, 'utf8');
			$mb_strlen = mb_strlen($string);
		}
		$strlen = count($strarr);
		$begin = $start >= 0 ? $start : ($strlen - abs($start));
		$end = $last = $strlen - 1;
		if ($length > 0) {
			$end = $begin + $length - 1;
		} elseif ($length < 0) {
			$end -= abs($length);
		}
		for ($i = $begin; $i <= $end; $i++) {
			$strarr[$i] = $re;
		}
		if ($begin >= $end || $begin >= $last || $end > $last) {
			return false;
		}

		return implode('', $strarr);
	}

}
?>