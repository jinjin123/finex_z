<?php
/**
 * 用户币种资金模型
 * @author lirunqing 2017-10-13 10:41:09
 */
namespace Home\Model;
use Think\Model;
class UserCurrencyModel extends Model {
	protected $tableName = 'user_currency';

	/**
	* 根据uid获取用户资金信息
	* @author lirunqing 2017-10-13 10:43:35
	* @param int $uid 用户id
	* @param int $currencyId 币种id
	* @param string $field
	* @return array
	*/
	public function getUserCurrencyByUid($uid, $currencyId, $field = '*') {
	  	return $this->field($field)->where(array('uid' => $uid, 'currency_id' => $currencyId))->find();
	}

	/*
	 * 李江
	 * 2017年11月1日18:45:38
	 * 功能 修改用户资金
	 * 参数 $uid $currency_id $num $field：修改字段 $operationType='inc'加/减
	 */
    public function setUserMoney($uid,$currency_id,$num,$field='num',$operationType='inc'){
        if ( $field!='num' && $field!='forzen_num' ){
            return false;
        }
        if ( $operationType != 'inc' && $operationType != 'dec' ){
            return false;
        }
        if( $operationType == 'inc' ){
            return  M('UserCurrency')->lock(true)->where( array('uid'=>$uid,'currency_id'=>$currency_id) )->setInc($field,$num); // 加
        }else{
            /////不能让余额为负数  改为可以让余额为负数
            return  M('UserCurrency')->lock(true)->where(array('uid'=>$uid,'currency_id'=>$currency_id))->setDec($field,$num); //减
        }
    }


}