<?php
/**
 * 网站维护模块
 * @author lirunqing  2019年2月25日15:47:54
 */
namespace Common\Api;
class Maintain {

	CONST P2P = 1;// p2p交易模式的值
	CONST C2C = 2;// c2c交易模式的值
	CONST CTR = 3;// 币币交易模式的值
	CONST EXPIRE = 500;// 交易模式数据缓存到期时间

	/**
	 * 获取交易模式维护模块
	 * @author lirunqing 2019-02-25T15:58:15+0800
	 * @param  integer $type 交易模式的值，0表示获取所有交易模块
	 * @return array  $type为0，返回二维数组，其他值，返回一维数组
	 */
	public static function getTradeMaintainVals($type=0){

		$where = [];
		if (!empty($type)) {
			$where['type'] = $type;
		}

		$redis = RedisCluster::getInstance();
		// $res   = $redis->get(redisKeyNameLibrary::TRADEMAINTAINTYPEVALS.$type);

		// if (!empty($res)) {
		// 	return json_decode($res, true);
		// }

		$field = 'forbid_order,list_order,deal_order,master_switch,type';
		$res   = M('Maintain')->where($where)->field($field)->select();

		// 返回二维数组
		if (empty($type)) {
			$redis->setex(redisKeyNameLibrary::TRADEMAINTAINTYPEVALS.$type, self::EXPIRE, json_encode($res));
			return $res;
		}

		list($res) = $res;
		$redis->setex(redisKeyNameLibrary::TRADEMAINTAINTYPEVALS.$type, self::EXPIRE, json_encode($res));

		// 返回一维数组
		return $res;
	}
	
	/**
	 * @var string 正在上线币种
	 */
	const ON_LINE_CURRENCYS ='ON_LINE_CURRENCYS';
	/**
	 * @author 建强 2019年3月11日 下午4:33:45
	 * @method 获取在线币种信息   
	 * @return array  
	 */
	public static function getOnlineCurrencyList(){
	    $key   = self::ON_LINE_CURRENCYS;
	    $redis = RedisCluster::getInstance();
	    $res   = $redis->get($key);
	    if(!empty($res)) return json_decode($res,true);
	    $currs = M('Currency')->field('id,currency_name')->select();
	    if(empty($currs)) return [];
	    $redis->setex($key, self::EXPIRE, json_encode($currs));
	    return $currs;
	}
}