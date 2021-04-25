<?php
//独家首创，直接扩展PDO类，作为应用程序的一部分
class  DATABASE extends C_DB{
	public $oldData;
	public $newData;
	public $oldFields;
	public $newFields;
	public $updateWhere;
	public $IDField;
	public $IDValue;
	
	/*
		//如果是使用系统模型，就需要使用这一句
		在$this->setModule('模型名称之后，或者是在这个模型需要调用的地方');
		//	$this->initModule('lang_pack');
	*/
	function __construct($dbConf){
		parent::__construct($dbConf);   
	}
	function initModule($tableName){
		if ($this->setTableName($tableName)){
			return true;
		}else{
			return false;
		}
		
	}

	//设置字段数据，用于单条数据更新
	function set($field,$value){
		$this->fields[$field]=$value;
	}
	function get($field){
		return $this->fields[$field];
	}
	//保存单条数据
	function save(){
		
	}
	//获取该表对就的ID的数据
	function getInfoByID($tableID=0){
		
	}
	//删除该表指定ID的数据，不传参数时，看之前是否有设置，如果没有设置，则不删除
	function deleteByID($tableID=0){
		
	}
}
?>