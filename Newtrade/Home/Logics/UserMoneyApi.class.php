<?php
namespace Home\Logics;

class UserMoneyApi  {

	/**
	 * 添加财务日志
	 * @author lirunqing 2017-10-13T12:20:43+0800
	 * @param  int $uid        用户id
	 * @param  int $currencyId 币种id
	 * @param  array   $dataArr 扩展数组
	 *         string  $dataArr['financeType'];日志类型 必传
	 *         string  $dataArr['content'];内容 必传
	 *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
	 *         float   $dataArr['money'];金钱 必传
	 *         float   $dataArr['afterMoney'];操作之后的余额 必传
	 *         float   $dataArr['remarkInfo'];线下交易的订单号 非必传
	 * @return bool 
	 */
	public function AddFinanceLog($uid, $currencyId, $dataArr=array()) {

		$financeType = $dataArr['financeType'];
		$content     = $dataArr['content'];
		$type        = $dataArr['type'];
		$money       = $dataArr['money'];
		$afterMoney  = $dataArr['afterMoney'];
		$remarkInfo  = !empty($dataArr['remarkInfo']) ? $dataArr['remarkInfo'] : 0;

		if (empty($financeType) || empty($content) || empty($type) 
			|| empty($money) || empty($afterMoney) ) {
			return false;
		}

		$data = array (
			'uid'          => $uid,
			'currency_id'  => $currencyId,
			'finance_type' => $financeType,
			'content'      => $content,
			'type'         => $type,
			'remark_info'  => $remarkInfo,
			'money'        => $money,
			'add_time'     => time(),
		);

		if (!empty($afterMoney)) {
			$data['after_money'] = $afterMoney;
		}

		$table     = 'UserFinance';
		$tableName = getTbl($table,$uid);
		$res       = M($tableName)->add($data);

		return $res;
	}
	/**
	 * 增加修改个人币种资金信息(缓存)
	 * @author lirunqing 2017-10-13 15:32:29
	 * @param int $uid	用户id
	 * @param int $currencyId	币种id
	 * @param string $num		数量
	 * @param string $field		类型 num/forzen_num
	 * @param string $operationType	运算类型	inc/dec
	 * @return boolean
	 */
	public function setUserMoney($uid, $currencyId, $num, $field='num', $operationType='inc'){
		if ($field != 'num' && $field != 'forzen_num'){
			return false;
		}
		if ($operationType != 'inc' && $operationType != 'dec'){
			return false;
		}  

		$userCurrency = M('UserCurrency')->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->find();

		if($operationType == 'inc'){
			$newNum = bcadd($userCurrency['num'], $num, 8);
			return  M('UserCurrency')->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); // 加
		}
        // 不能让余额为负数  改为可以让余额为负数
		$newNum = bcsub($userCurrency['num'], $num, 8);
        return  M('UserCurrency')->where(array('uid'=> $uid, 'currency_id'=> $currencyId))->setField($field, $newNum); //减
	}
}

