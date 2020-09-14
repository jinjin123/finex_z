<?php
/**
 * 比特币、莱特币等币种交易信息采集及VP币交易信息获取处理
 * @author lirunqing 2017-10-16 10:32:45
 */
namespace Home\Controller;
use Think\Controller;
use Common\Api\RedisCluster;
use Common\Api\RedisIndex;
use Home\Model\ConfigModel;
use Home\Model\CoinConfigModel;
use Common\Api\CurrencyTradingConfig;
use Common\Api\redisKeyNameLibrary;
use Common\Api\Maintain;

class CoinTradeInfoController extends Controller{

	private $thresholdArr = array(// 根据交易区和币种获取数量阈值
		1 => array(
			1 => 500,
			2 => 500,
			3 => 500,
			4 => 500,
		),
		2 => array(
			1 => 500,
			2 => 500,
			3 => 500,
			4 => 500,
		)
	);

	/**
	 * 检测网站是否维护
	 * @author lirunqing 2019-02-26T10:46:01+0800
	 * @param  integer $type 1表示市场订单列表维护开关 2表示下单及购买维护开关，3表示我的订单列表维护开关
	 * @return bool|json
	 */
	public function checkWebMaintain(){
		$ctrMaintain = Maintain::getTradeMaintainVals(Maintain::CTR);

		// 检测p2p模块是否开启维护模式
		$forbidOrder  = $ctrMaintain['forbid_order'];// 下单开关
		$masterSwitch = $ctrMaintain['master_switch'];// p2p模式总开关
		$dealOrder    = $ctrMaintain['deal_order'];// 我的订单  开关
		$listOrder    = $ctrMaintain['list_order'];//  市场订单列表开关

		if ($listOrder == 1 || $masterSwitch == 1) {
			return false;
		}

		return true;
	}

	/**
	 * 获取币币交易订单列表
	 * @author lirunqing 2017-11-30T11:48:18+0800
	 * @return json
	 */
	public function getMarketList(){

		$tradeArea           = (I('post.area_id')) ? I('post.area_id') : 1;// 交易区id,1表示btc交易区；2表示bch交易区
		$entrustType         = (I('post.entrust_type')) ? I('post.entrust_type') : 1;// 委托类别,例如：1btc/bch 2 ltc/bch  3 etc/bch 4 eth/bc
		$tradeAreaArr        = CurrencyTradingConfig::$tradeAreaArr;
		$tradeAreaMachineArr = CurrencyTradingConfig::$tradeAreaMachineArr;
		$tableName           = $tradeAreaArr[$tradeArea];
		$buyWhere            = array();
		$sellWhere           = array();
		$limit               = 12;

		// 检测网站是否维护
		$isMaintain = $this->checkWebMaintain();

		if (empty($isMaintain)) {
			$maintainData = array(
				'code' => 200,
				'msg'  => L('_CHENGGONG_'),
				'data' => array(
					'sell' => [],
					'buy'  => []
				),
			);
			$this->ajaxReturn($maintainData);
		}


		$buyWhere['buy_id']       = array('neq', 0);
		$buyWhere['entrust_type'] = $entrustType;
		$buyWhere['status']       = array('in', array(1));
		$field                    = 'entrust_type,entrust_num,leave_num,entrust_price,entrust_money';
		$buyList                  = M($tableName)->where($buyWhere)->field($field)->order('entrust_price desc')->limit($limit)->select();
		$buyCount                 = count($buyList);
		$machBuyRes               = array();
		// if ($buyCount < 12) {
		// 	$machineTableName = $tradeAreaMachineArr[$tradeArea];
		// 	$machLimit = $limit - $buyCount;
		// 	$field     = 'entrust_type,trade_num as leave_num,trade_price as entrust_price,trade_money as entrust_money';
		// 	$machineBuyWhere = array(
		// 		'sell_id'      => 0,
		// 		'entrust_type' => $entrustType
		// 	);
		// 	$machBuyRes   = M($machineTableName)->where($machineBuyWhere)->field($field)->order('entrust_price desc')->limit($machLimit)->select();
		// }
		
		$buyListArr = array_merge($buyList, $machBuyRes);

		$sellWhere['sell_id']      = array('neq', 0);
		$sellWhere['entrust_type'] = $entrustType;
		$sellWhere['status']       = array('in', array(2));
		$field                     = 'entrust_type,entrust_num,leave_num,entrust_price,entrust_money';
		$sellList                  = M($tableName)->where($sellWhere)->field($field)->order('entrust_price ASC')->limit($limit)->select();
		$sellCount                 = count($sellList);
		$machSellRes               = array();
		// if ($sellCount < 12) {
		// 	$machineTableName = $tradeAreaMachineArr[$tradeArea];
		// 	$machLimit = $limit - $sellCount;
		// 	$field     = 'entrust_type,trade_num as leave_num,trade_price as entrust_price,trade_money as entrust_money';
		// 	$machineBuyWhere = array(
		// 		'sell_id'      => array('neq', 0),
		// 		'entrust_type' => $entrustType
		// 	);
		// 	$machSellRes   = M($machineTableName)->where($machineBuyWhere)->field($field)->order('entrust_price ASC')->limit($machLimit)->select();
		// }

		$sellListArr = array_merge($sellList, $machSellRes);

		$buyArr = array();
		$buyTemp = [];
		foreach ($buyListArr as $key => $value) {
			$buyTemp[] = $value['entrust_price'];
			$temp['price'] = $value['entrust_price'];
			$temp['ctd']   = $value['leave_num'];
			$temp['total_num']   = $value['entrust_num'];
			$temp['scale'] = $this->thresholdArr[$tradeArea][$entrustType];
			$buyArr[]      = $temp;
		}
		// 根据entrust_price排序，倒序
		array_multisort($buyTemp, SORT_DESC, $buyArr);
		unset($temp);

		$sellArr = array();
		$sellTemp = [];
		foreach ($sellListArr as $key => $value) {
			$sellTemp[] = $value['entrust_price'];
			$temp['price'] = $value['entrust_price'];
			$temp['ctd']   = $value['leave_num'];
			$temp['ctd']   = $value['leave_num'];
			$temp['total_num']   = $value['entrust_num'];
			$temp['scale'] = $this->thresholdArr[$tradeArea][$entrustType];
			$sellArr[]     = $temp;
		}
		// 根据entrust_price排序，升序
		array_multisort($sellTemp, SORT_ASC, $sellArr);
		unset($temp);

		$res = array(
			'code' => 200,
			'msg'  => L('_CHENGGONG_'),
			'data' => array(
				'sell' => $sellArr,
				'buy'  => $buyArr
			),
		);
		$this->ajaxReturn($res);
	}

	/**
	 * 获取用户收藏的币种信息
	 * @author lirunqing 2017-10-26T15:19:10+0800
	 * @return json
	 */
	public function getMycollection(){

		$tradeArea = I("get.tradeArea");// 币币交易交易区id
		$type      = I("get.type");// 交易模式类型
		$tradeArea = !empty($tradeArea) ? $tradeArea : 0;
		$type            = !empty($type) ? $type : 1;
		$userId              = getUserId();
		$where['uid']        = $userId;
		$where['status']     = 1;
		$where['type']       = $type;
		$where['trade_area'] = $tradeArea;
		$myCollertion        = M('UserCurrencyCollection')->field('currency_id')->where($where)->select();

		$temp = array();
		foreach ($myCollertion as $key => $value) {
			$temp[] = $value['currency_id'];
		}

		$res = array(
			'msg'  => '成功',
			'code' => 200,
			'data' => array('currency_arr' => $temp, 'coin_arr' => array())
		);

		// 币币交易收藏列表
		if ($tradeArea > 0) {
			$currencyInfo      = $this->getCurrencyInfoListToAll();
			$checkCurrencyInfo = $currencyInfo[$tradeArea];
			$newCheckCurrInfo  = array();
			foreach ($checkCurrencyInfo as $key => $value) {
				if (!in_array($value['currency_id'], $temp)) {
					continue;
				}
				$newCheckCurrInfo[$key] = $value;
			}
			$res['data']['coin_arr'] = $newCheckCurrInfo;
		}

		$this->ajaxReturn($res);
	}

	/**
	 * 获取用户余额
	 * @author lirunqing 2017-10-26T17:28:26+0800
	 * @return json
	 */
	public function getUserCurrency(){

		$userId       = getUserId();
		// $currencyList = M('Currency')->where(array('status'=>1))->select();
		$currencyList = M('Currency')->select();
		$temp         = array();
		foreach ($currencyList as $value) {
			$temp[$value['id']] = $value['currency_name'];
		}

		$where['uid']   = $userId;
		$myCurrencyList = M('UserCurrency')->field('currency_id,num')->where($where)->select();

		$myCurrList = array();
		foreach ($myCurrencyList as $value) {
			// 除比特币外，余额为0的币种不显示
			if (($value['num'] > 0 || $value['currency_id'] == 1) && !empty($temp[$value['currency_id']])) {
				$value['coin_name'] = $temp[$value['currency_id']];
				$myCurrList[] = $value;
			}
		}

		// 获取美金余额
		$userId    = getUserId();
		$assetsRes = M('User')->field('assets')->where(array('uid' => $userId))->find();
		if (!empty($assetsRes) && !empty($myCurrList) && $assetsRes['assetsRes'] > 0) {
			$assetsArr['coin_name'] = 'USD';
			$assetsArr['currency_id'] = '0';
			$assetsArr['num'] = $assetsRes['assets'];
			$myCurrList[] = $assetsArr;
		}

		$res = array(
			'msg'  => '成功',
			'code' => 200,
			'data' => array('myCurrList' => $myCurrList)
		);

		$this->ajaxReturn($res);
	}

	/**
	 * 用户收藏币种
	 * @author lirunqing 2017-10-25T16:16:02+0800
	 * @return json
	 */
	public function collectionMyCoin(){

		$currencyId       = I('post.currency_id');
		$collectionStatus = I('post.collection_status');
		$tradeArea        = I('post.trade_area');
		$type             = I('post.type');
		$userId           = getUserId();
		$res              = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		// 用户未登陆
		if (empty($userId)) {
			$res['msg'] = '未登录';
			$this->ajaxReturn($res);
		}

		// 没有币种id
		if (empty($currencyId)) {
			$res['msg'] = '无此币种';
			$this->ajaxReturn($res);
		}

		$type                 = !empty($type) ? $type : 1;
		$where                = array();
		$where['uid']         = $userId;
		$where['currency_id'] = $currencyId;
		$where['type']        = $type;
		$where['trade_area']  = $tradeArea;
		$myCollertion = M('UserCurrencyCollection')->where($where)->find();

		$msg = ($collectionStatus == 1) ? L('_QXSC_') : L('_SCCG_');
		$collectionStatus = ($collectionStatus == 1) ? 0 : 1;
		// 判断用户是否收藏过
		if (!empty($myCollertion)) {
			$saveData = array(
				'status' => $collectionStatus
			);
			$collRes = M('UserCurrencyCollection')->where($where)->save($saveData);
		}else{
			$addData = array(
				'uid'         => $userId,
				'currency_id' => $currencyId,
				'trade_area'  => $tradeArea,
				'type'        => $type,
				'status'      => 1
			);

			$collRes = M('UserCurrencyCollection')->add($addData);
		}
		
		if (empty($collRes)) {
			$res['msg'] = '222';
			$this->ajaxReturn($res);
		}

		$res['msg']  = $msg;
		$res['code'] = 200;
		$res['data'] = array('collection_status' => $collectionStatus);
		$this->ajaxReturn($res);
	}

	/**
	 * 获取币种信息列表
	 * @author lirunqing 2017-10-25T15:53:35+0800
	 * @return json
	 */
	public function getCoinInfoList(){

		$currencyId       = I('post.currencyId');
		// $currencyId       = I('currencyId');
		$coinArr          = $this->getCoinInfo();
		$coinInfoList     = $coinArr['coinInfoList'];
		$coinInfoLongList = $coinArr['coinInfoLongList'];

		$userColList = $this->getUserCollection(1);
		foreach ($coinInfoList as $key => $value) {
			// 判断某币种的实时价格、最高价格及最低价格是否变化
			$value['rtq_status']   = ($value['last_usa'] >= $coinInfoLongList[$key]['last_usa']) ? 1 : 0;
			$value['heigh_status'] = ($value['high_usa'] >= $coinInfoLongList[$key]['high_usa']) ? 1 : 0;
			$value['low_status']   = ($value['low_usa'] >= $coinInfoLongList[$key]['low_usa']) ? 1 : 0;
			// 判断是否收藏该币种
			$value['col_status'] = !empty($userColList[0][$value['currency_id']]) ? $userColList[0][$value['currency_id']] : 0;
			$coinInfoList[$key]    = $value;
		}

		$currInfo = M('Currency')->where(['id' => $currencyId])->find();

		$checkCoinInfo = array(
			'heigh'       => !empty($coinInfoList[$currencyId]['high_usa']) ? $coinInfoList[$currencyId]['high_usa'] : 0,
			'low'         => !empty($coinInfoList[$currencyId]['low_usa']) ? $coinInfoList[$currencyId]['low_usa'] : 0,
			'rtq'         => !empty($coinInfoList[$currencyId]['last_price']) ? $coinInfoList[$currencyId]['last_price'] : 0,
			'vol'         => !empty($coinInfoList[$currencyId]['num']) ? $coinInfoList[$currencyId]['num'] : 0,
			'coin_name'   => !empty($coinInfoList[$currencyId]['coin_name']) ? $coinInfoList[$currencyId]['coin_name'] : '',
			'currency_id' => $currencyId,
			'status'      => ($currInfo['status'] == 1) ? 1 : 0,
			'last'        => !empty($coinInfoList[$currencyId]['last_usa']) ? $coinInfoList[$currencyId]['last_usa'] : '',
			'money_usa'   => !empty($coinInfoList[$currencyId]['money_usa']) ? $coinInfoList[$currencyId]['money_usa'] : 0,
		);

		$returnData = array(
			'checkCoinInfo' => $checkCoinInfo,
			'coinInfoList'  => $coinInfoList
		);

		$this->ajaxReturn($returnData);
	}

	/**
	 * 获取线下交易币种市场价格
	 * @author lirunqing 2017-12-13T11:07:10+0800
	 * @return array
	 */
	public function getCoinInfo(){

		// 获取redis对象
		// $redisObj             = new RedisCluster();
		$redis                = RedisCluster::getInstance();
		$currencyList         = M('Currency')->field('currency_mark,id,currency_name')->where(['status' => 1])->select();
		$coinInfoKey          = redisKeyNameLibrary::COIN_INFO_LIST_BY_BIF;
		$coinInfoLongCacheKey = redisKeyNameLibrary::COIN_INFO_LIST_LONG_BY_BIF;
		$coinInfoList         = $redis->get($coinInfoKey);

		// 判断币种是否有缓存
		if (empty($coinInfoList)) {

			// 获取币种所属市场
			$currencyConfigList = M('CoinConfig')->field('currency_id,flag')->select();
			$configTemp         = array();
			foreach ($currencyConfigList as $key => $value) {
				$configTemp[$value['currency_id']] = $value['flag'];
			}

			$longPricArr = array();
			$coinArr = [];
			foreach ($currencyList as $key => $value) {
				if ($value['id'] == 6) {
					unset($currencyList[$key]);
					continue;
				}
				$testTemp['currency_name'] = $value['currency_name'];
				$testTemp['currency_id']   = $value['id'];
				$testTemp['flag']          = !empty($configTemp[$value['id']]) ? $configTemp[$value['id']] : 0;
				$coinArr[] = $testTemp;
			}

			$coinReturnArr = $this->getCoinInfoFromBitfixByArr($coinArr);

			$flag = 0;// 外站获取数据是否为空标记，如果为1表示，外站获取数据为空，需要拿缓存
			if (empty($coinReturnArr)|| empty($coinReturnArr[1]['num']) || empty($coinReturnArr[1]['last_usa'])) {
				$flag = 1;
			}

			if (!empty($coinReturnArr)) {
				$coinInfoList = serialize($coinReturnArr);
				$redis->setex($coinInfoKey, 300, $coinInfoList);// 缓存5分钟获取的b站数据
			}

			if (empty($flag)) {
				$longPricArr  = serialize($coinReturnArr);
				$redis->setex($coinInfoLongCacheKey, 24*3600, $longPricArr);// 缓存24小时获取的b站数据
			}
		}

		$coinInfoLongList    = $redis->get($coinInfoLongCacheKey);
		$coinInfoLongListArr = unserialize($coinInfoLongList);
		$coinInfoList        = unserialize($coinInfoList);

		// 外站获取数据为空，需要拿缓存
		if (!empty($flag)) {
			$coinInfoList    = $coinInfoLongListArr;
			$coinInfoListSer = serialize($coinInfoList);
			$redis->setex($coinInfoKey, 300, $coinInfoListSer);
		}

		$returnData = array(
			'coinInfoList'     => $coinInfoList,
			'coinInfoLongList' => $coinInfoLongListArr,
		);

		return $returnData;
	}

	/**
	 * 获取交易区下每个币种的价格
	 * @author lirunqing 2017-12-08T14:36:36+0800
	 * @return json
	 */
	public function getCurrencyInfoByArea(){

		$data = I('get.');

		$currencyInfo = $this->getCurrencyInfoListToAll();
		$areaId       = $data['areaId'];// 交易区id
		$entrustType  = $data['entrust_type'];//委托类型

		$returnData = array(
			'coinInfo'      => array(),
		);

		if (empty($areaId) || empty($entrustType)) {
			$this->ajaxReturn($returnData);
		}

		$coinInfo = array(
			'heigh' => $currencyInfo[$areaId][$entrustType]['high'],
			'low'   => $currencyInfo[$areaId][$entrustType]['low'],
			'rtq'   => $currencyInfo[$areaId][$entrustType]['last'],
		);

		$returnData = array(
			'coinInfo'      => $coinInfo,
		);

		$this->ajaxReturn($returnData);	
	}

	/**
	 * 获取所有交易区相关市场价格
	 * @author lirunqing 2017-12-06T14:38:14+0800
	 * @return json
	 */
	public function getAllCurrencyInfoList(){

		$data = I('get.');
		$currencyInfo = $this->getCurrencyInfoListToAll();
		$areaId       = $data['areaId'];// 交易区id
		$entrustType  = $data['entrust_type'];//委托类型

		$returnData = array(
			'tradeAreaInfo' => array(),
			'coinInfo'      => array(),
		);

		if (empty($areaId) || empty($entrustType)) {
			$this->ajaxReturn($returnData);
		}

		$tradeAreaInfo = $currencyInfo[$areaId];
		$coinInfo = array(
			'heigh' => $currencyInfo[$areaId][$entrustType]['high'],
			'low'   => $currencyInfo[$areaId][$entrustType]['low'],
			'rtq'   => $currencyInfo[$areaId][$entrustType]['last'],
			'sell'  => $currencyInfo[$areaId][$entrustType]['sell'],
			'buy'   => $currencyInfo[$areaId][$entrustType]['buy'],
		);
		$userColList = $this->getUserCollection(2);
		foreach ($tradeAreaInfo as $key => $value) {
			$value['col_status'] = !empty($userColList[$value['area_id']][$value['currency_id']]) ? $userColList[$value['area_id']][$value['currency_id']] : 0;
			$tradeAreaInfo[$key] = $value;
		}

		$returnData = array(
			'tradeAreaInfo' => $tradeAreaInfo,
			'coinInfo'      => $coinInfo,
		);

		$this->ajaxReturn($returnData);	
	}

	/**
	 * 获取用户收藏币币交易币种列表
	 * @author lirunqing 2017-12-08T17:28:46+0800
	 * @return array
	 */
	private function getUserCollection($type){

		$userId = getUserId();
		$where  = array(
			'uid'        => $userId,
			'type'       => $type
		);
		$userColList = M("UserCurrencyCollection")->where($where)->select();

		$colList = array();
		foreach ($userColList as $key => $value) {
			$colList[$value['trade_area']][$value['currency_id']] = $value['status'];
		}

		return $colList;
	}

	/**
	 * 获取每个币种单价
	 * @author 2017-12-13T14:44:04+0800
	 * @return [type] [description]
	 */
	public function getCoinPrice(){
		$coinInfo = $this->getCoinInfo();

		$coinInfoList = array();
		foreach ($coinInfo['coinInfoList'] as $key => $value) {
			$coinInfoList[$value['currency_id']] = $value['last_price'];
		}

		return $coinInfoList;
	}

	/**
	 * 币币交易获取所有币种交易价格
	 * @author 2017-12-07T20:37:53+0800
	 * @return [type] [description]
	 */
	public function getCurrencyInfoListToAll(){

		$primaryMarket = $this->getCurrencyInfoList();// 1级市场
		// $secMarketArr  = $this->getSecMarketInfo();// 二级市场

		$currencyInfo = $primaryMarket;
		if (!empty($secMarketArr)) {
			unset($currencyInfo);
			$currencyInfo = $primaryMarket + $secMarketArr;
		}

		$currencyList = array();
		foreach ($currencyInfo as $key => $currencyVal) {
			$temp = array();
			foreach ($currencyVal['c_info'] as $k => $value) {
				$temp[$value['entrust_type']] = $value;
				$currencyList[$key] = $temp;
			}
		}
		
		return $currencyList;
	}


	/**
	 * 获取二级市场币种信息
	 * @author lirunqing 2019-03-12T15:12:24+0800
	 * @return array
	 */
	private function getSecMarketInfo(){

		$redis            = RedisCluster::getInstance();
		$secMarketInfoKey = redisKeyNameLibrary::CURTENCY_SEC_MARKET_INFO_LIST;
		$secMarketSeri    = $redis->get($secMarketInfoKey);
		if (!empty($secMarketSeri)) {
			return unserialize($secMarketSeri);
		}

		$secondaryMarket = CurrencyTradingConfig::getVPTradeArea();// 二级市场
		$secMarket       = $secondaryMarket['VPConfigArr'];
		$coinInfo        = $this->getCoinPrice();// 获取线下交易币种交易价格
		$secMarketArr    = array();
		$areaArr         = CurrencyTradingConfig::getAllTradeAreaArr();
		foreach ($secMarket as $key => $value) {
			$pInfo['p_str']               = $secondaryMarket['tradeAreaArr'][$key];
			$pInfo['p_currency_id']       = $areaArr[$key];
			$secMarketArr[$key]['p_info'] = $pInfo;

			foreach ($value as $k => $val) {
				$price = big_digital_div($coinInfo[$val['currency_id']],$val['coin_price']);
				$val['last'] = $price;
				$val['high'] = $price;
				$val['low']  = $price;
				$val['buy']  = $price;
				$val['sell'] = $price;
				$value[$k] = $val;
			}

			$secMarketArr[$key]['c_info'] = $value;
		}

		$secMarketSeri = serialize($secMarketArr);
		$redis->setex($secMarketInfoKey, 300, $secMarketSeri);// 缓存5分钟

		return $secMarketArr;
	}

	/**
	 * 获取btc交易区相关各个币种交易价格
	 * @author lirunqing 2017-12-05T17:18:43+0800
	 * @return [type] [description]
	 */
	public function getCurrencyInfoList(){

		// $redisObj                 = new RedisCluster();
		$redis                    = RedisCluster::getInstance();
		$currencyInfoKey          = redisKeyNameLibrary::CURTENCY_INFO_LIST_BY_OKEX;
		$currencyInfoLongCacheKey = redisKeyNameLibrary::CURTENCY_INFO_LIST_LOING_BY_OKEX;
		$currencyInfoList         = $redis->get($currencyInfoKey);

		if (!empty($currencyInfoList)) {
			$currencyInfoList = unserialize($currencyInfoList);
			return $currencyInfoList;
		}

		$currencyArr  = CurrencyTradingConfig::getTradingArea();
		$currencyList = $currencyArr['coinArr'];
		$tradeAreaArr = $currencyArr['tradeAreaArr'];

		$currencyLongInfoList = $redis->get($currencyInfoLongCacheKey);
		$longArr              = unserialize($currencyLongInfoList);
		$currencyNewList      = array();
		$areaArr              = CurrencyTradingConfig::getAllTradeAreaArr();

		$coinNameArr = [];
		foreach ($currencyList as $key => $coinInfo) {
			foreach ($coinInfo as $k => $val) {
				$coinName            = str_replace('/', '_', $val['coin_str']);
				$testTemp['currency_name'] = $coinName;
				$testTemp['currency_id']   = $val['currency_id'];

				$coinNameArr[] = $testTemp;
			}
		}

		$currencyTradeInfo = $this->getCurrencyTradingInfoFromOkex($coinNameArr);
		$flag = 0;// 外站获取数据是否为空标记，如果为1表示，外站获取数据为空，需要拿缓存
		if (empty($currencyTradeInfo)) {
			$flag = 1;
		}

		foreach ($currencyList as $key => $coinInfo) {
			foreach ($coinInfo as $k => $val) {
				$tempInfo = $currencyTradeInfo[$val['currency_id']];

				if ($tempInfo['flag'] == 1) {
					$flag = 1;
					break;
				}
				$newTempInfo                = array_merge($val, $tempInfo);
				$newTempInfo['rate']        = '0%';
				$newTempInfo['perc_status'] = 0;

				if (!empty($longArr[$key][$k]['last'])) {
					$newTempInfo['rate']        = ($newTempInfo['last'] - $longArr[$key][$k]['last'])/$longArr[$key][$k]['last'];
					$newTempInfo['rate']        = (getDecimal($newTempInfo['rate'], 4) > 0) ? getDecimal($newTempInfo['rate'], 4) : 0;
					$newTempInfo['perc_status'] = ($newTempInfo['rate'] >= 0) ? 1 : 0;// 1表示涨，0表示跌
					$newTempInfo['rate']        = abs($newTempInfo['rate']*100).'%';
				}

				$temp[$k] = $newTempInfo;
			}

			$longArr[$key]                   = $temp;
			$pInfo['p_str']                  = $tradeAreaArr[$key];
			$pInfo['p_currency_id']          = $areaArr[$key];
			$currencyNewList[$key]['p_info'] = $pInfo;
			$currencyNewList[$key]['c_info'] = $temp;
		}

		$currencyNewListSer = serialize($currencyNewList);
		$redis->setex($currencyInfoKey, 300, $currencyNewListSer);// 缓存5分钟
		if($flag == 0){
			$redis->setex(redisKeyNameLibrary::CURTENCY_INFO_LIST_LOING_BY_OKEX_TO24, 24*3600, $currencyNewListSer);// 缓存24小时
		}

		$longArr = serialize($longArr);
		$redis->setex($currencyInfoLongCacheKey, 24*3600, $longArr);// 缓存24小时

		// 外站获取数据是否为空标记，如果为1表示，外站获取数据为空，需要拿缓存
		if ($flag == 1) {
			$currencyNewListBy24 = $redis->get(redisKeyNameLibrary::CURTENCY_INFO_LIST_LOING_BY_OKEX_TO24);
			$currencyInfoList = unserialize($currencyNewListBy24);
			$redis->setex($currencyInfoKey, 300, $currencyNewListBy24);
			return $currencyInfoList;
		}

		$currencyInfoList = unserialize($currencyNewListSer);
		return $currencyInfoList;
	}

	/**
	 * 根据名称获取币币交易相关信息
	 * @author lirunqing 2017-12-01T10:53:45+0800
	 * @param  string $coinName [description]
	 * @return [type]           [description]
	 */
	public function getCurrencyTradingInfoFromOther($coinName='LTC_BTC'){

		// $coinName = strtolower($coinName);
		// $currencyTradingInfo = vget('https://www.okex.com/api/v1/ticker.do?symbol='.$coinName);
		// https://www.okex.me/api/v1/ticker.do?symbol=eth_btc  国内备用地址
		$currencyTradingInfo = vget('http://45.123.100.9:17934/getCoinInfo.php?coin_name='.$coinName.'&type=1');
		$currencyInfo        = json_decode($currencyTradingInfo, true);
		$returnData          = array();
		$returnData          = $currencyInfo['ticker'];

		return $returnData;
	}


	/**
	 * 根据名称获取币币交易相关信息
	 * @author lirunqing 2017-12-01T10:53:45+0800
	 * @param  array $coinArr [description]
	 * @return [type]           [description]
	 */
	public function getCurrencyTradingInfoFromOkex($coinArr=[]){

		if (empty($coinArr)) {
			return [];
		}

		$url = [];
		foreach ($coinArr as $value) {
			$value['currency_name'] = strtolower($value['currency_name']);
			// $url[] = 'https://www.okex.com/api/v1/ticker.do?symbol='.$value['currency_name'];
			$url[] = 'https://www.okex.me/api/v1/ticker.do?symbol='.$value['currency_name'];// 国内备用地址
			// https://www.okex.me/api/v1/ticker.do?symbol=eth_btc  国内备用地址
			// $url[] = "http://45.123.100.9:17934/getCoinInfo.php?coin_name=".$value['currency_name'].'&type=1';
		}

		if (empty($url)) {
			return [];
		}

		$mh = curl_multi_init();
		foreach($url as $k => $v) {
			$ch[$k] = curl_init($v);
			curl_setopt($ch[$k], CURLOPT_HEADER, 0); //不输出头
			curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1); //exec返回结果而不是输出,用于赋值
			curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1); //exec返回结果而不是输出,用于赋值
			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYPEER, false);    //禁止 cURL 验证对等证书
  			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYHOST, false);    //是否检测服务器的域名与证书上的是否一致
			curl_multi_add_handle($mh, $ch[$k]); //决定exec输出顺序
		}
		$running = null;
		$json = [];
		do { //执行批处理句柄
			curl_multi_exec($mh, $running); //CURLOPT_RETURNTRANSFER如果为0,这里会直接输出获取到的内容.如果为1,后面可以用curl_multi_getcontent获取内容.
			curl_multi_select($mh); //阻塞直到cURL批处理连接中有活动连接,不加这个会导致CPU负载超过90%.
		} while ($running > 0);
		foreach($ch as $v) {
			$json[] = curl_multi_getcontent($v);
			curl_multi_remove_handle($mh, $v);
		}
		curl_multi_close($mh);

		$returnArr = [];
		foreach ($json as $key => $value) {

			$coinInfoArr      = json_decode($value, true);
			$coinInfo         = $coinInfoArr['ticker'];
			$coinInfo['flag'] = 0;

			if (empty($coinInfo['high']) || $coinInfo['high'] <= 0) {
				$coinInfo['flag'] = 1;
			}
			
			$returnArr[$coinArr[$key]['currency_id']] = $coinInfo;
		}

		return $returnArr;
	}

	/**
	 * 根据币种名称，从bitfinex网站获取币种交易信息
	 * @author lirunqing 2017-10-17T11:50:14+0800
	 * @param  string $coinName 币种名称
	 * @return array
	 */
	public function getCoinInfoFromOther($coinName="BTC"){

		// [BID, BID_SIZE, ASK, ASK_SIZE, DAILY_CHANGE, DAILY_CHANGE_PERC, LAST_PRICE, VOLUME, HIGH, LOW]
        // [买入最高价格, 买入最大数量, 卖出最大价格, 卖出最大数量, 每日涨幅数量, 每日涨幅比例, 最后成交价, 成交量, 最高价, 最低价]
		// $coinInfo = vget('https://api.bitfinex.com/v2/ticker/t'.$coinName.'USD');
		$coinInfo = vget('http://45.123.100.9:17934/getCoinInfo.php?coin_name='.$coinName);
		$coinInfo = json_decode($coinInfo, true);

		// 如果b站获取不到数据，则去okex获取
		if (empty($coinInfo[6])) {
			$coinInfoReturn = $this->getCoinInfoFromOkex($coinName);
			return $coinInfoReturn;
		}

		$returnData['num']         = number_format(round($coinInfo[7])); // 总成交量

		$returnData['money_usa']   = big_digital_mul($coinInfo[6],$coinInfo[7],2);
		$returnData['money_usa']   = number_format($returnData['money_usa'],2); // 总成交值美元
		$returnData['last_usa']    = getDecimal($coinInfo[6], 2); // 最新价美元
		$returnData['high_usa']    = getDecimal($coinInfo[8], 2); // 最高价美元
		$returnData['low_usa']     = getDecimal($coinInfo[9], 2); // 最低价美元
		$returnData['last_price']  = getDecimal($coinInfo[6], 2); // 最后成交价美元
		$returnData['daily_perc']  = $coinInfo[5]; // 每日涨跌比例
		$returnData['perc_per']    = (abs($coinInfo[5]) * 100).'%'; // 每日涨跌比例
		$returnData['perc_status'] = ($coinInfo[5] > 0) ? 1 : -1; // 涨跌状态，1表示涨，-1表示跌
		$returnData['coin_name']   = $coinName; // 币种名称	

	    return $returnData;
	}

	/**
	 * 批量获取b站币种信息
	 * @author lirunqing 2018-05-07T11:04:23+0800
	 * @param  array  $coinArr 币种信息数组，例如：$coinArr = [ ['currency_name' => 'BTC', 'currency_id' => 1], 
	 *                         									['currency_name' => 'LTC', 'currency_id' => 2] ];
	 * @return [type]          [description]
	 */
	public function getCoinInfoFromBitfixByArr($coinArr=[]){

		if (empty($coinArr)) {
			return [];
		}

		$url = [];
		foreach ($coinArr as $value) {
                        //https://www.okex.me/api/spot/v3/instruments/BTC-USDT/ticker
			// $url[] = 'https://api.bitfinex.com/v2/ticker/t'.$value['currency_name'].'USD';
			if ($value['currency_name'] == 'BCH' || $value['currency_name'] == 'BTC') {
				$value['currency_name'] = strtolower($value['currency_name']);
				$url[] = "https://www.okex.com/api/spot/v3/instruments/".$value['currency_name']."-usdt/ticker";
			}else{
				$value['currency_name'] = strtolower($value['currency_name']);
				$url[] = "https://www.okex.com/api/spot/v3/instruments/".$value['currency_name']."-usdt/ticker";
				// $currencyName = strtoupper($value['currency_name']);
				// $currencyName = ($currencyName == 'USDT') ? 'UST' : $currencyName;
				// $url[] = "http://45.123.100.9:17934/getCoinInfo.php?coin_name=".$currencyName;
			}
			
			// $url[] = "http://45.123.100.9:17934/getCoinInfo.php?coin_name=4445555";
		}

		if (empty($url)) {
			return [];
		}

		$mh = curl_multi_init();
                //var_dump($url);die;
//                array(5) {
//                        [0]=>
//                        string(59) "https://www.okex.me/api/spot/v3/instruments/btc-usdt/ticker"
//                        [1]=>
//                        string(59) "https://www.okex.me/api/spot/v3/instruments/ltc-usdt/ticker"
//                        [2]=>
//                        string(59) "https://www.okex.me/api/spot/v3/instruments/eth-usdt/ticker"
//                        [3]=>
//                        string(59) "https://www.okex.me/api/spot/v3/instruments/bch-usdt/ticker"
//                        [4]=>
//                        string(59) "https://www.okex.me/api/spot/v3/instruments/eos-usdt/ticker"
//                      }
                $json = [];
		foreach($url as $k => $v) {
                    $json[$k] = vget($v);
		}
                
		$redis     = RedisCluster::getInstance();
		$returnArr = [];
		$isEmpty   = 0;
                
		foreach ($json as $key => $value) {

			$coinInfo = json_decode($value, true);
                        //var_dump($coinInfo);die;
			if (!empty($coinInfo)) {
				$temp = $this->processOkexInfo($coinInfo, $key, $coinArr);
			}else{
				$temp = $this->processBitfinexInfo($coinInfo, $key, $coinArr);
			}

			if (empty($temp)) {
				$isEmpty = 1;
				break;
			}
                        //var_dump($temp);die;
			// 如果是okex的币种，需要计算币种涨跌幅
			if ($temp['is_type'] == 1) {
				$coinPer          = $redis->get('CONINFO_PER_BY_CURRENCYNAME'.$temp['currency_name']);
				$coinPer          = !empty($coinPer) ? $coinPer : 0;
				$per              = !empty($coinPer) ? abs(($temp['last_price']-$coinPer))/$coinPer : 0;
				$temp['perc_per'] = (round($per, 4) * 100).'%';
				$temp['perc_status'] = ($temp['last_price'] >= $coinPer) ? 1 : 0;
				$redis->set('CONINFO_PER_BY_CURRENCYNAME'.$temp['currency_name'], $temp['last_price']);
			}
			
			$returnArr[$coinArr[$key]['currency_id']] = $temp;
		}

		if (!empty($isEmpty)) {
			return [];
		}

		return $returnArr;
	}


	/**
	 * 处理bitfinex获取的币种信息
	 * @author lirunqing 2019-02-28T10:39:44+0800
	 * @param  array   $coinInfo [description]
	 * @param  integer $key      [description]
	 * @param  array   $coinArr  [description]
	 * @return array
	 */
	private function processBitfinexInfo($coinInfo=[], $key=0 ,$coinArr=[]){

		// 如果有一个币种没有获取到币种信息，则返回空数组；需要获取缓存中的数据
		if (!empty($coinInfo['error']) || empty($coinInfo[7]) || empty($coinInfo[6])) {
			return [];
		}

		$temp['num']           = number_format(round($coinInfo[7])); // 总成交量
		$temp['money_usa']     = big_digital_mul($coinInfo[6], $coinInfo[7], 2);
		$temp['money_usa']     = number_format($temp['money_usa'], 2); // 总成交值美元
		$temp['last_usa']      = getDecimal($coinInfo[6], 2); // 最新价美元
		$temp['high_usa']      = getDecimal($coinInfo[8], 2); // 最高价美元
		$temp['low_usa']       = getDecimal($coinInfo[9], 2); // 最低价美元
		$temp['last_price']    = getDecimal($coinInfo[6], 2); // 最后成交价美元
		$temp['daily_perc']    = $coinInfo[5]; // 每日涨跌比例
		$temp['perc_per']      = (abs($coinInfo[5]) * 100).'%'; // 每日涨跌比例
		$temp['perc_status']   = ($coinInfo[5] > 0) ? 1 : -1; // 涨跌状态，1表示涨，-1表示跌
		$temp['coin_name']     = strtoupper($coinArr[$key]['currency_name']); // 币种名称	
		$temp['currency_name'] = strtoupper($coinArr[$key]['currency_name']); // 币种名称	
		$temp['currency_id']   = $coinArr[$key]['currency_id']; // 币种id
		$temp['flag']          = $coinArr[$key]['flag']; 
		$temp['is_type']       = 0;

		return $temp;
	}

	/**
	 * 处理bitfinex获取的币种信息
	 * @author lirunqing 2019-02-28T10:39:44+0800
	 * @param  array   $coinInfo [description]
	 * @param  integer $key      [description]
	 * @param  array   $coinArr  [description]
	 * @return array
	 */
	private function processOkexInfo($coinInfo=[], $key=0 ,$coinArr=[]){

		// 如果有一个币种没有获取到币种信息，则返回空数组；需要获取缓存中的数据
		if (!empty($coinInfo['error_code'])) {
			return [];
		}

		$tickerInfo = $coinInfo;
		// buy:买一价 contract_id:合约ID high:最高价 last:最新成交价 low:最低价 sell:卖一价 unit_amount:合约面值 vol:成交量(最近的24小时)

		$temp['num']           = number_format(round($tickerInfo['base_volume_24h'])); // 总成交量
		//$temp['money_usa']     = big_digital_mul($tickerInfo['last'],$tickerInfo['base_volume_24h'],2);
		$temp['money_usa']     = number_format(round($tickerInfo['quote_volume_24h'])); // 总成交值美元
		$temp['last_usa']      = getDecimal($tickerInfo['last'], 2); // 最新价美元
		$temp['high_usa']      = getDecimal($tickerInfo['high_24h'], 2); // 最高价美元
		$temp['low_usa']       = getDecimal($tickerInfo['low_24h'], 2); // 最低价美元
		$temp['last_price']    = getDecimal($tickerInfo['best_bid'], 2); // 最后成交价美元
		$temp['coin_name']     = strtoupper($coinArr[$key]['currency_name']); // 币种名称	
		$temp['currency_name'] = strtoupper($coinArr[$key]['currency_name']); // 币种名称	
		$temp['currency_id']   = $coinArr[$key]['currency_id']; // 币种id
		$temp['flag']          = $coinArr[$key]['flag']; 
		$temp['daily_perc']    = 0; // 每日涨跌比例
		$temp['perc_per']      = (0 * 100).'%'; // 每日涨跌比例
		$temp['perc_status']   = (0 > 0) ? 1 : -1; // 涨跌状态，1表示涨，-1表示跌
		$temp['is_type']       = 1;

		return $temp;
	}



	/**
	 * 获取okex币种信息
	 * @author lirunqing 2018-03-09T17:23:03+0800
	 * @param  string $coinName [description]
	 * @return array
	 */
	public function getCoinInfoFromOkex($coinName="BTC"){

		// $coinName = "BTC";
		$coinName = strtolower($coinName);
		$coinRes  = vget('https://www.okcoin.com/api/v1/ticker.do?symbol='.$coinName.'_usdt');
		$coinRes  = json_decode($coinRes, true);

		$coinInfo = $coinRes['ticker'];
		// buy:买一价 contract_id:合约ID high:最高价 last:最新成交价 low:最低价 sell:卖一价 unit_amount:合约面值 vol:成交量(最近的24小时)

		$returnData['num']         = number_format(round($coinInfo['vol'])); // 总成交量
		$returnData['money_usa']   = big_digital_mul($coinInfo['last'],$coinInfo['vol'],2);
		$returnData['money_usa']   = number_format($returnData['money_usa'],2); // 总成交值美元
		$returnData['last_usa']    = getDecimal($coinInfo['last'], 2); // 最新价美元
		$returnData['high_usa']    = getDecimal($coinInfo['high'], 2); // 最高价美元
		$returnData['low_usa']     = getDecimal($coinInfo['low'], 2); // 最低价美元
		$returnData['last_price']  = getDecimal($coinInfo['last'], 2); // 最后成交价美元
		$returnData['daily_perc']  = 0; // 每日涨跌比例
		$returnData['perc_per']    = (0 * 100).'%'; // 每日涨跌比例
		$returnData['perc_status'] = (0 > 0) ? 1 : -1; // 涨跌状态，1表示涨，-1表示跌
		$returnData['coin_name']   = $coinName; // 币种名称

	    return $returnData;
	}
}
