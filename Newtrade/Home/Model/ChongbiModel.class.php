<?php
/**
 * 充币记录模型类
 * @author lirunqing 2017-10-9 12:01:29
 */
namespace Home\Model;
use Think\Model;

class ChongbiModel extends Model {
	protected $tableName = 'chongbi';
	const STATUSCB1 = 1;//充值中
	const STATUSCB2 = 2;//充值中成功
	const STATUSCB3 = 3;//充值中失败
	/**
	* 获取网站配置项列表
	* @author lirunqing 2017-10-9 12:03:03
	* @return array
	*/
	public  function getChongbiIcon($uid){
	    //默认显示充值成功的
	    return $this->where(['uid'=>$uid,'status'=>self::STATUSCB2])
            ->field('currency_id,check_time,num,status')
            ->order('add_time desc')
            ->select();
	}
}