<?php
/**
 * config模型类
 * @author lirunqing 2017-10-9 12:01:29
 */
namespace Home\Model;
use Think\Model;

class ConfigModel extends Model {
	protected $tableName = 'config'; 

	/**
	* 获取网站配置项列表
	* @author lirunqing 2017-10-9 12:03:03
	* @return array
	*/
	public function getConfigList(){

		$configList = array();
		$configArr = $this->select();
		foreach($configArr as $k => $v){
		  $configList[$v['key']] = $v['value'];
		}

		return $configList;
	}
}