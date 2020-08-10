<?php
/**
 * 银行信息模型类
 * @author lirunqing 2017-11-2 15:44:42
 */
namespace Home\Model;
use Think\Model;

class BankListModel extends Model {
	protected $tableName = 'bank_list'; 

	/**
	 * 获取银行名称列表,id作为key
	 * @author lirunqing 2017-11-02T15:44:08+0800
	 * @return array
	 */
	public function getBankListName(){
		$list = $this->select();

		$bankNameList = array();
		foreach ($list as $value) {
			$bankNameList[$value['id']] = formatBankType($value['id']);
		}

		return $bankNameList;
	}
}