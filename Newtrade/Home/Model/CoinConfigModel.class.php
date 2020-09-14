<?php
/**
 * 币种配置信息模型类
 * @author lirunqing 2017-10-16 14:34:04
 */
namespace Home\Model;
use Think\Model;

class CoinConfigModel extends Model {
	protected $tableName = 'coin_config'; 

	/**
	 * 根据币种id获取币种配置信息
	 * @author lirunqing 2017-10-16T14:37:22+0800
	 * @param  int $currencyId 币种id
	 * @return array
	 */
	public function getConfigByCurrencyId($currencyId){

		$where = array(
			'currency_id' => $currencyId
		);
		$data = $this->where($where)->find();

		return $data;
	}
}