<?php
/**
 * 充币记录模型类
 * @author lirunqing 2017-10-9 12:01:29
 */
namespace Home\Model;
use Think\Model;

class TibiModel extends Model {
	protected $tableName = 'tibi';
	const STATUSTB = 0;//提币中
	const STATUSTB1 = 1;//成功
	const STATUSTB2 = 2;//等待提出
	const STATUSTB3= -1;//失败

	/**
	* 获取网站配置项列表 ,'status'=>self::STATUSTB1]
	* @author lirunqing 2017-10-9 12:03:03
	* @return array
	*/
	public  function getTibiIcon($uid){
	    //默认显示提币成功的
	    return $this->where(['uid'=>$uid])
            ->field('currency_id,check_time,num,status')
            ->order('add_time desc')
            ->select();
	}
}