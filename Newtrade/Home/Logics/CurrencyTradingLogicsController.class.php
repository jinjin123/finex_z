<?php
/**
 * 币币交易业务逻辑模块
 */
namespace Home\Logics;
use Think\Controller;
use Common\Api\CurrencyTradingConfig;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
use Common\Api\Maintain;

class CurrencyTradingLogicsController extends Controller {

	private $tradeAreaArr;// 根据交易区获取交易区表名

	private $tradeAreasuccessArr;// 根据交易区获取交易成功表名

	private $areaSellCurrencyIdArr;// 根据交易区和委托类别获取卖出币种id;

	private $areaBuyCurrencyIdArr;// 根据交易区和委托类别获取买入币种id;
	private $returnMsg = [
		'code' => 200,
		'msg'  => '',
		'data' => [],
	];
	
	public function _initialize(){
		$this->getCurrencyTradingConfig();
	}

	/**
	 * 获取币币交易相关配置
	 * @author lirunqing 2017-11-30T19:29:34+0800
	 * @return [type] [description]
	 */
	private function getCurrencyTradingConfig(){
		$this->tradeAreaArr          = CurrencyTradingConfig::$tradeAreaArr;
		$this->tradeAreasuccessArr   = CurrencyTradingConfig::$tradeAreasuccessArr;
		$this->areaSellCurrencyIdArr = CurrencyTradingConfig::getTradeAreaInfo();
		$this->areaBuyCurrencyIdArr  = CurrencyTradingConfig::getAllTradeAreaArr();
	}

	/**
	 * 检测网站是否维护
	 * @author lirunqing 2019-02-26T10:46:01+0800
	 * @param  integer $type 1表示我的订单列表维护开关 2表示下单维护开关
	 * @return array
	 */
	public function checkWebMaintain($type=1){
		$ctrMaintain = Maintain::getTradeMaintainVals(Maintain::CTR);

		// 检测币币模块是否开启维护模式
		$forbidOrder  = $ctrMaintain['forbid_order'];// 下单开关
		$masterSwitch = $ctrMaintain['master_switch'];// 币币模式总开关
		$dealOrder    = $ctrMaintain['deal_order'];// 我的订单  开关
		$listOrder    = $ctrMaintain['list_order'];//  市场订单列表开关
		$msgArr = [];
		$msgArr['code'] = 200;

		// 1表示我的订单列表维护开关
		if ($type == 1 && ($dealOrder == 1 || $masterSwitch == 1)) {
			$msgArr['code'] = 701;
			$msgArr['msg']  = L('_BBGRDDWHZ_');
			return $msgArr;
		}

		if ($type == 1){
			return $msgArr;
		}

		// $type==2 下单及购买维护开关
		if ($masterSwitch == 1) {
			$msgArr['code'] = 703;
			$msgArr['msg']  = L('_BBMSWHZ_');
			return $msgArr;
		}

		if ($dealOrder == 1) {
			$msgArr['code'] = 704;
			$msgArr['msg']  = L('_BBGRDDWHZ_');
			return $msgArr;
		}

		if ($forbidOrder == 1) {
			$msgArr['code'] = 705;
			$msgArr['msg']  = L('_BBXDWHZ_');
			return $msgArr;
		}

		return $msgArr;
	}

    /**
     * 获取价格浮动限制比例
     * @author 刘富国
     * @param $data
     * @return bool
     */
    public function getUserPriceFloatingRatio($data){
        if ($data['tradeType'] == 1) {
            $where = array(
                'trade_area_id' => $data['tradeType']
            );
            $configRes = M('BiBiConfig')->field('float_price_rate')->where($where)->find();
        }else{
            $areaCurrencyId = $this->areaBuyCurrencyIdArr[$data['tradeType']];
            $where = array(
                'main_currency_id'     => $areaCurrencyId,
                'exchange_currency_id' => $data['entrustType']
            );
            $configRes = M('SecondClassCoinConfig')->where($where)->find();
        }
        if(empty($configRes) or $configRes['float_price_rate'] == 0) return 0;
        return $configRes['float_price_rate'];
    }

	/**
	 * 检测用户买卖订单是否存在未完成的订单
	 * @author lirunqing 2017-12-05T11:51:08+0800
	 * @param  int  $userId 用户id
	 * @param  int  $areaId 交易区id
	 * @param  int  $entrustType 委托类型
	 * @param  integer $type   1表示买入，2表示卖出
	 * @return int 200表示成功；其他表示失败
	 */
	public function checkIsCompleteOrderExist($userId, $areaId, $entrustType, $type=1){

		$tableName = $this->tradeAreaArr[$areaId];
		$where     = array();

		if ($type == 1) {
			$where['buy_id']       = $userId;
			$where['status']       = 1;
			$where['entrust_type'] = $entrustType;
		}else{
			$where['sell_id']      = $userId;
			$where['status']       = 2;
			$where['entrust_type'] = $entrustType;
		}
		
		$count = M($tableName)->where($where)->count();
		
		if ($count >= 3) {
			return 201;
		}

		return 200;
	}

	/**
	 * 检测限价单的价格是否超出当前价格的浮动比例
	 * @author lirunqing 2017-12-05T10:43:24+0800
	 * @param  int $areaId      交易区id
	 * @param  float $userPrice   用户填写的价格
	 * @param  float $marketPrice 当前市场的价格
	 * @return int 200表示成功，其他表示失败
	 */
	public function priceFloatingRatio($areaId, $userPrice, $marketPrice){

		$where = array(
			'trade_area_id' => $areaId
		);

		$configRes = M('BiBiConfig')->field('float_price_rate')->where($where)->find();

		$priceRate = big_digital_mul($marketPrice, $configRes['float_price_rate']);

		if (bcsub($marketPrice,$priceRate,8) > $userPrice
            || bcadd($marketPrice , $priceRate,8) < $userPrice ) {
			return 201;
		}

		return 200;
	}

	/**
	 * 二级交易区检测限价单的价格是否超出当前价格的浮动比例
	 * @author lirunqing 2017-12-08T16:22:19+0800
	 * @param  int $areaCurrencyId 交易区主币种id
	 * @param  int $entrustType    委托类型
	 * @param  float $userPrice    用户填写的价格
	 * @param  float $marketPrice  当前市场的价格
	 * @return int 200表示成功，其他表示失败
	 */
	public function priceFloatingRatioToVp($areaCurrencyId, $entrustType, $userPrice, $marketPrice){
		$where = array(
			'main_currency_id'     => $areaCurrencyId,
			'exchange_currency_id' => $entrustType
		);
		$configRes = M('SecondClassCoinConfig')->where($where)->find();
		$priceRate = big_digital_mul($marketPrice, $configRes['float_price_rate']);

        if (bcsub($marketPrice,$priceRate,8) > $userPrice
            || bcadd($marketPrice , $priceRate,8) < $userPrice ) {
			return 201;
		}

		return 200;
	}

	/**
	 * 撤销订单
	 * @author lirunqing 2018-07-18T16:04:06+0800
	 * @param  int $orderId      订单id
	 * @param  int $tradeArea    交易区id
	 * @param  int $userId       用户id
	 * @return array
	 */
	public function revokerCurrencyOrder($orderId, $tradeArea, $userId){

		$tableName               = $this->tradeAreaArr[$tradeArea];
		$where['id']             = $orderId;
		$where['sell_id|buy_id'] = $userId;
		$orderInfo = M($tableName)->where($where)->find();

		if (empty($orderInfo)) {
			$this->returnMsg['msg'] = L('_NCXDWZD_');
			return $this->returnMsg;
		}

		if (!in_array($orderInfo['status'], array(1,2)) && $orderInfo['leave_num'] <= 0) {
			$this->returnMsg['msg'] = L('_NDDDBKCX_');
			return $this->returnMsg;
		}

		//开启事务
		M()->startTrans();

		$upWhere['id'] = $orderId;
		$saveData = array(
			'status' => 4,
			'update_time' => time()
		);
		$upRes = M($tableName)->where($upWhere)->save($saveData);

		if (empty($upRes)) {
			M()->rollback(); // 事务回退
			$this->returnMsg['msg'] = L('_NDDDBKCX_');
			return $this->returnMsg;
		}

		// 买入撤销
		$logRes = 0;
		if ($orderInfo['status'] == 1) {
			$currencyId = $this->areaBuyCurrencyIdArr[$tradeArea];

			if ($orderInfo['success_num'] == 0) {// 如果没有成交，则直接原有数量
				$fee = $orderInfo['entrust_money'];
			}else{
				$fee = big_digital_mul($orderInfo['leave_num'], $orderInfo['entrust_price']);
			}
			
			$extArr     = array(
				'fee'         => $fee,
				'operation'   => 'inc',
				'content'     => '币币交易买入撤销返还',
				'financeType' => 'trade',
				'remarkInfo'  => $orderInfo['order_num'],
				'is_revoke'   => 1,
			);

			$logRes = $this->calUserMoneyAndAddFinanceLog($userId, $currencyId, $extArr);
		}elseif ($orderInfo['status'] == 2) {// 卖出撤销
			$currencyId = $this->areaSellCurrencyIdArr[$tradeArea][$orderInfo['entrust_type']];
			$fee        = $orderInfo['leave_num'];
			$extArr     = array(
				'fee'         => $fee,
				'operation'   => 'inc',
				'content'     => '币币交易卖出撤销返还',
				'financeType' => 'trade',
				'remarkInfo'  => $orderInfo['order_num'],
				'is_revoke'   => 1,
			);

			$logRes = $this->calUserMoneyAndAddFinanceLog($userId, $currencyId, $extArr);
		}else{
			$this->returnMsg['msg'] = L('_NDDDBKCX_');
			return $this->returnMsg;
		}

		if ($logRes != 200) {
			M()->rollback(); // 事务回退
			$this->returnMsg['msg'] = L('_NDDDBKCX_');
			return $this->returnMsg;
		}

		M()->commit(); // 事务提交

		return $this->returnMsg;
	}

    /**
     * 匹配订单
     * @author lirunqing 2018-07-16T15:44:23+0800
     * @param  array $data       用户提交的订单信息
     * @param  int $userId       用户id
     * @param  int $pendingId    新订单id
     * @return array
     */
    public function matchingOrder($data, $userId, $pendingId){

    	$tradeType       = $data['tradeType'];// 交易区,1表示btc交易区，2表示bch交易区
		$transactionType = $data['transactionType'];// 交易类型，1表示买入，2表示卖出
		$entrustType     = $data['entrustType'];// 委托类别
		$entrustPrice    = $data['entrustPrice'];// 单价0.1089533
		$leaveNum        = $data['leaveNum'];// 数量    0.10868421 

    	$statusArr = array(
			1 => 2,// 买入匹配卖出
			2 => 1,// 卖出匹配买入
		);

		$tableName              = $this->tradeAreaArr[$tradeType];
		$status                 = $statusArr[$transactionType];
		$where['entrust_price'] = ($transactionType == 1) ? array('elt', $entrustPrice) : array('egt', $entrustPrice);
		$where['leave_num']     = array('gt', 0);
		$where['entrust_type']  = $entrustType;
		$where['status']        = $status;
		$where['sell_id']       = array('neq', $userId);
		$where['buy_id']        = array('neq', $userId);
		$orderBy                = ($transactionType == 1) ? 'entrust_price ASC,add_time ASC' : 'entrust_price DESC,add_time ASC';// 买入匹配卖出最低价格，卖出匹配买入最高价格
		$matchRes = M($tableName)->where($where)->order($orderBy)->find();

		if (empty($matchRes) || in_array($matchRes['status'], [3,4])) {
			$this->returnMsg['code'] = 999;
			return $this->returnMsg;
		}

		$toStatusUserId = !empty($matchRes['sell_id']) ? $matchRes['sell_id'] : $matchRes['buy_id'];
		$publicObj      = new PublicFunctionController();
		$isUserStatus   = $publicObj->getUserStatusByUserId($toStatusUserId);
		// 如果挂单人存在交易风险，则不能购买订单
		if (empty($isUserStatus)) {
			$this->returnMsg['msg'] = L('_GDDYJWCHCX_');
			$this->returnMsg['code'] = 299;
			return $this->returnMsg;
		}

		$pendingData = M($tableName)->where(['id' => $pendingId])->find();

		$pendingData['transaction_type'] = $transactionType;
		$pendingData['priceType']        = $data['priceType'];
		$pendingData['totalPrice']       = $data['totalPrice'];
		$pendingData['trade_type']       = $data['tradeType'];  
		$pendingData['entrust_type']     = $data['entrustType'];

		$uid = $matchRes['buy_id'];
		if ($transactionType == 1) {
			$uid = $matchRes['sell_id'];
		}

		// $redisObj = new RedisCluster();
		$redis    = RedisCluster::getInstance();

		$redis->hset(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $userId."-".$pendingId, $pendingId);
		$redis->hset(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $uid."-".$matchRes['id'], $matchRes['id']);

		//开启事务
		M()->startTrans();
		$isSucc = $this->matchingOrderSuccess($pendingData, $matchRes, $userId);

		$redis->hdel(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $userId."-".$pendingId);
		$redis->hdel(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $uid."-".$matchRes['id']);

		if (empty($isSucc) || $isSucc['code'] != 200) {
			M()->rollback(); // 事务回退
			$this->returnMsg['code'] = 702;
			return $this->returnMsg;
		}
		M()->commit();// 事务提交

		$this->returnMsg['data'] = ['id' => $matchRes['id']];
		return $this->returnMsg;
    }

	/**
	 * 生成交易订单
	 * @author lirunqing 2018年7月16日14:36:14
	 * @param  array $data
	 * @param  int $userId
	 * @return array
	 */
	public function addOrderByUserSubData($data, $userId){

		$tradeType       = $data['tradeType'];// 交易区,1表示btc交易区，2表示bch交易区
		$transactionType = $data['transactionType'];// 交易类型，1表示买入，2表示卖出
		$entrustType     = $data['entrustType'];// 委托类别
		$entrustPrice    = $data['entrustPrice'];// 单价0.1089533
		$leaveNum        = $data['leaveNum'];// 数量    0.10868421 

		$pendingData['price']            = $entrustPrice;
		$pendingData['leave_num']        = $leaveNum;
		$pendingData['entrust_type']     = $entrustType;
		$pendingData['trade_type']       = $tradeType;
		$pendingData['transaction_type'] = $transactionType;
		$pendingData['order_num']        = $this->genOrderId($data['tradeType'], $userId);
		$pendingData['priceType']        = $data['priceType'];
		$pendingData['totalPrice']       = $data['totalPrice'];
		$pendingData['add_time']         = $data['add_time'];

		//开启事务
		M()->startTrans();

		$addRes = $this->addOrder($pendingData, $userId);
		if(empty($addRes) || $addRes['code'] != 200){
			M()->rollback(); // 事务回退
			$this->returnMsg['code'] = $addRes['code'];
			return $this->returnMsg;
		}

		// $redisObj        = new RedisCluster();
		$redis           = RedisCluster::getInstance();
		
		$data['userId']  = $userId;
		$data['orderId'] = $addRes['data']['id'];
		$serOrderInfo    = serialize($data);
		$lpushRes        = $redis->lpush(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_FOR_ORDER_INFO_BY_ORDER, $serOrderInfo);

		if(empty($lpushRes)){
			M()->rollback(); // 事务回退
			$this->returnMsg['code'] = 999;
			return $this->returnMsg;
		}

		M()->commit();// 事务提交

		$this->returnMsg['data'] = ['id' => $addRes['data']['id']];
		return $this->returnMsg;
	}

	/**
	 * 数据入库等待匹配并扣除买入/卖出币
	 * @author lirunqing 2017-11-29T21:16:36+0800
	 * @param  array $data 待处理订单数据
	 * @return int 200表示成功，其他表示失败
	 */
	public function addOrder($pendingData, $userId){

		// 买入且是市价单时，不用计算总数
		if($pendingData['priceType'] == 2 && $pendingData['transaction_type'] == 1){
			$entrustMoney = $pendingData['totalPrice'];
		}else{
			$entrustMoney = big_digital_mul($pendingData['price'], $pendingData['leave_num']);
		}

		$pendingInfo['entrust_type']  = $pendingData['entrust_type'];
		$pendingInfo['entrust_num']   = $pendingData['leave_num'];
		$pendingInfo['entrust_price'] = $pendingData['price'];
		$pendingInfo['entrust_money'] = $entrustMoney;
		$pendingInfo['leave_num']     = $pendingData['leave_num'];
		$pendingInfo['status']        = $pendingData['transaction_type'];
		$pendingInfo['order_num']     = $pendingData['order_num'];
		$pendingInfo['add_time']      = !empty($pendingData['add_time']) ? $pendingData['add_time'] :time();

		$roleArr = array(
			1 => 'buy_id',
			2 => 'sell_id',
		);
		$transactionType                         = $pendingData['transaction_type'];
		$pendingInfo[$roleArr[$transactionType]] = $userId;
		$tableName = $this->tradeAreaArr[$pendingData['trade_type']];

		$pendRes   = M($tableName)->add($pendingInfo);

		if(empty($pendRes)){
			$this->returnMsg['code'] = 291;
			return $this->returnMsg;
		}

		$logRes = 0;
		if($transactionType == 1){// 买入扣除币种，例如：用btc买入eth，先扣除btc
			$buyCurrencyId = $this->areaBuyCurrencyIdArr[$pendingData['trade_type']];
			$logRes        = $this->buyMoneyAndFinanceLog($userId, $buyCurrencyId, $entrustMoney, $pendingData['order_num']);
		}else{// 卖出扣除币种，例如：卖出eth，先扣除eth
			$sellCurrencyId = $this->areaSellCurrencyIdArr[$pendingData['trade_type']][$pendingData['entrust_type']];
			$logRes         = $this->sellMoneyAndFinanceLog($userId, $sellCurrencyId, $pendingData['leave_num'], $pendingData['order_num']);
		}

		if (empty($logRes) || $logRes != 200) {
			$this->returnMsg['code'] = 292;
			return $this->returnMsg;
		}

		$this->returnMsg['data'] = ['id' => $pendRes];
		return $this->returnMsg;
	}

	/**
	 * 生成订单号
	 * @author 2017-12-21T12:16:25+0800
	 * @param  int $tradeType // 交易区,1表示btc交易区，2表示VP交易区
	 * @param  int $userId 用户id
	 * @return string
	 */
	public function genOrderId($tradeType, $userId){
		$orderNum = date('YmdHis').$userId.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 10);
		return $orderNum;
	}

	/**
	 * 订单匹配成功
	 * @author lirunqing 2017-11-27T15:39:57+0800
	 * @param  array $pendingData 待处理订单信息
	 * @param  array $matchData   匹配成功订单信息
	 * @return [type]              [description]
	 */
	private function matchingOrderSuccess($pendingData, $matchData, $userId){

		$pendingPrice    = $pendingData['entrust_price'];
		$matchPrice      = $matchData['entrust_price'];
		$transactionType = $pendingData['transaction_type'];// 交易类型，1表示买入，2表示卖出

		// 获取剩余数量
		$pendingLeaveNum = ($pendingData['leave_num'] >= $matchData['leave_num']) ? (bcsub($pendingData['leave_num'], $matchData['leave_num'], 8)) : 0;
		$mactchLeaveNum  = ($matchData['leave_num'] >= $pendingData['leave_num']) ? (bcsub($matchData['leave_num'], $pendingData['leave_num'], 8)) : 0;
		// 获取成交数量
		$successNum        = ($matchData['leave_num'] >= $pendingData['leave_num']) ? $pendingData['leave_num'] : $matchData['leave_num'];

		// 成交price以卖出的为准;交易类型，1表示买入，2表示卖出
		if ($transactionType == 1) {
			$price          = $matchPrice;
			$sellId         = $matchData['sell_id'];
			$pidSell        = $matchData['id'];
			$pidBuy         = 0;
			$buyId          = $userId;
			$pendingStatus  = ($pendingLeaveNum > 0 ) ? 1 : 3; // 待处理订单状态
			$matchingStatus = ($mactchLeaveNum > 0 ) ? 2 : 3; // 匹配成功订单状态
		}else{
			$price          = $pendingPrice;
			$buyId          = $matchData['buy_id'];
			$pidBuy         = $matchData['id'];
			$pidSell        = 0;
			$sellId         = $userId;
			$pendingStatus  = ($pendingLeaveNum > 0 ) ? 2 : 3;  // 待处理订单状态
			$matchingStatus = ($mactchLeaveNum > 0 ) ? 1 : 3; // 匹配成功订单状态
		}

		// 买入且是市价单时，不用计算总数
		if($pendingData['priceType'] == 2 && $pendingData['transaction_type'] == 1){
			$entrustMoney = $pendingData['totalPrice'];
		}else{
			$entrustMoney = big_digital_mul($pendingData['entrust_price'], $pendingData['leave_num']);
		}

		// 待处理订单信息
		$pendingUpInfo['success_num']   = $successNum;
		$pendingUpInfo['leave_num']     = $pendingLeaveNum;
		$pendingUpInfo['status']        = $pendingStatus;
		$pendingUpInfo['update_time']   = time();
		$pendingUpInfo['add_time']      = time();

		$tableName = $this->tradeAreaArr[$pendingData['trade_type']];
		$pendUpRes = M($tableName)->where(['id' => $pendingData['id']])->save($pendingUpInfo);

		if(empty($pendUpRes)){
			$this->returnMsg['code'] = 301;
			return $this->returnMsg;
		}

		// 交易类型，1表示买入，2表示卖出
		if ($transactionType == 1) {
			$pidBuy       = $pendingData['id'];
			$tradeMoney   = big_digital_mul($successNum, $price);
			$buyOrderNum  = $pendingData['order_num'];
			$sellOrderNum = $matchData['order_num'];
		}else{
			$buyOrderNum  = $matchData['order_num'];
			$sellOrderNum = $pendingData['order_num'];
			$pidSell      = $pendingData['id'];
			$tradeMoney   = big_digital_mul($successNum, $price);
		}

		// 匹配成功订单信息
		$matchingInfo['success_num'] = bcadd($matchData['success_num'],$successNum,8) ;
		$matchingInfo['leave_num']   = $mactchLeaveNum;
		$matchingInfo['status']      = $matchingStatus;
		$matchingInfo['update_time'] = time();
		$matchingWhere['id']         = $matchData['id'];
		$matchingRes = M($tableName)->where($matchingWhere)->save($matchingInfo);

		if (empty($matchingRes)) {
			$this->returnMsg['code'] = 302;
			return $this->returnMsg;
		}

		$buyFee  = $this->getFeeByTradeType($pendingData['trade_type'], $pendingData['entrust_type'], $successNum, 1);// 每次成交计算买入手续费;例如：用btc买入出eth，收取手续费eth
		$sellFee = $this->getFeeByTradeType($pendingData['trade_type'], $pendingData['entrust_type'], $tradeMoney, 2);// 每次成交计算买入手续费;例如：用btc买入出eth，收取手续费eth

		// 成交订单信息		
		$successInfo['pid_buy']      = $pidBuy;
		$successInfo['pid_sell']     = $pidSell; 
		$successInfo['entrust_type'] = $pendingData['entrust_type'];
		$successInfo['sell_id']      = $sellId;
		$successInfo['buy_id']       = $buyId;
		$successInfo['trade_num']    = $successNum;
		$successInfo['trade_price']  = $price;
		$successInfo['trade_money']  = $tradeMoney;
		$successInfo['sell_fee']     = $sellFee;
		$successInfo['buy_fee']      = $buyFee;
		$successInfo['trade_time']   = time();

		$successTableName = $this->tradeAreasuccessArr[$pendingData['trade_type']];
		$successRes       = M($successTableName)->add($successInfo);

		if (empty($successRes)) {
			$this->returnMsg['code'] = 304;
			return $this->returnMsg;
		}

		$buyCurrencyId = $this->areaBuyCurrencyIdArr[$pendingData['trade_type']];// 买入扣除币种，例如：用btc买入eth，先扣除btc
		$sellCurrencyId = $this->areaSellCurrencyIdArr[$pendingData['trade_type']][$pendingData['entrust_type']];// 卖出扣除币种，例如：卖出eth，先扣除eth

		$buyArr  = array();
		$sellArr = array();
		$buyArr['buyId']          = $buyId;
		$buyArr['sellCurrencyId'] = $sellCurrencyId;// 每次成交成功买入入账卖出币种;例如：用btc买入出eth，入账eth
		$buyArr['successNum']     = $successNum;
		$buyArr['buy_fee']        = $buyFee;
		$buyArr['buyOrderNum']    = $buyOrderNum;
		
		$sellArr['sellId']        = $sellId;
		$sellArr['buyCurrencyId'] = $buyCurrencyId;// 每次成交成功卖出入账买入币种;例如：卖出eth，获取btc，入账btc
		$sellArr['tradeMoney']    = $tradeMoney;
		$sellArr['sell_fee']      = $sellFee;
		$sellArr['sellOrderNum']  = $sellOrderNum;

		$add = $this->successToAddUserMoney($buyArr, $sellArr);

		if ($add['code'] != 200) {
			$this->returnMsg['code'] = $add['code'];
			return $this->returnMsg;
		}

		return $this->returnMsg;
	}

	/**
	 * 匹配成功，买卖双方币入账及收取买卖双方手续费
	 * @author lirunqing 2017-11-29T16:24:34+0800
	 * @param  array $buyArr 买家信息
	 * @param  array $sellArr 卖家信息
	 * @return [type] [description]
	 */
	private function successToAddUserMoney($buyArr , $sellArr){

		$buyId          = $buyArr['buyId'];
		$sellCurrencyId = $buyArr['sellCurrencyId'];
        $successNum     = $buyArr['successNum'];
		$buyFee         = $buyArr['buy_fee'];

		// 买入交易成功，进行入账
		$successBuyExtArr = array(
			'fee'         => $successNum,
			'operation'   => 'inc',
			'content'     => '币币交易买入获取',
			'financeType' => 'trade',
			'remarkInfo'  => $buyArr['buyOrderNum']
		);
		$getSellRes = $this->calUserMoneyAndAddFinanceLog($buyId, $sellCurrencyId, $successBuyExtArr);

		if (empty($getSellRes) || $getSellRes != 200) {
			$this->returnMsg['code'] = 401;
			return $this->returnMsg;
			// return 201;
		}

		// 收取买入手续费
		if (!empty($buyFee) && $buyFee > 0) {
			$buyExtArr     = array(
				'fee'         => $buyFee,
				'operation'   => 'dec',
				'content'     => '币币交易买入扣除手续费',
				'financeType' => 'fee',
				'remarkInfo'  => $buyArr['buyOrderNum']
			);
			$buyLogRes = $this->calUserMoneyAndAddFinanceLog($buyId, $sellCurrencyId, $buyExtArr);
			if (empty($buyLogRes) || $buyLogRes != 200) {
				$this->returnMsg['code'] = 402;
				return $this->returnMsg;
				// return 202;
			}
		}

		$sellId        = $sellArr['sellId'];
		$buyCurrencyId = $sellArr['buyCurrencyId'];
		$tradeMoney    = $sellArr['tradeMoney'];
		$sellFee       = $sellArr['sell_fee'];

		// 卖出成功，进行入账
		$successSellExtArr = array(
			'fee'         => $tradeMoney,
			'operation'   => 'inc',
			'content'     => '币币交易卖出获取',
			'financeType' => 'trade',
			'remarkInfo'  => $sellArr['sellOrderNum']
		);
		$getBuyRes = $this->calUserMoneyAndAddFinanceLog($sellId, $buyCurrencyId, $successSellExtArr);

		if (empty($getBuyRes) || $getBuyRes != 200) {
			$this->returnMsg['code'] = 403;
			return $this->returnMsg;
			// return 203;
		}

		// 收取卖出手续费
		if (!empty($sellFee) && $sellFee > 0) {
			$sellExtArr     = array(
				'fee'         => $sellFee,
				'operation'   => 'dec',
				'content'     => '币币交易卖出扣除手续费',
				'financeType' => 'fee',
				'remarkInfo'  => $sellArr['sellOrderNum']
			);
			$logRes = $this->calUserMoneyAndAddFinanceLog($sellId, $buyCurrencyId, $sellExtArr);

			if (empty($logRes) || $logRes != 200) {
				$this->returnMsg['code'] = 404;
				return $this->returnMsg;
				// return 204;
			}
		}

		return $this->returnMsg;
		// return 200;
	}

	/**
	 * 扣除买入的手续费及交易数量并记录财务日志
	 * @author @author lirunqing 2017-11-29T14:18:28+0800
	 * @param  int $buyId         购买人用户id
	 * @param  int $buyCurrencyId 币种id
	 * @param  float $tradeMoney    交易额
	 * @param  float $buyOrderNum   订单号
	 * @return int 200表示成功，其他表示失败
	 */
	private function buyMoneyAndFinanceLog($buyId, $buyCurrencyId, $tradeMoney, $buyOrderNum){

		$buyExtArr     = array(
			'fee'         => $tradeMoney,
			'operation'   => 'dec',
			'content'     => '币币交易买入扣除',
			'financeType' => 'trade',
			'remarkInfo'  => $buyOrderNum
		);

		$buyRes = $this->calUserMoneyAndAddFinanceLog($buyId, $buyCurrencyId, $buyExtArr);

		if (empty($buyRes) || $buyRes != 200) {
			return 201;
		}

		return 200;
	}

	/**
	 * 扣除卖出的手续费及交易数量并记录财务日志
	 * @author @author lirunqing 2017-11-29T14:18:28+0800
	 * @param  int $sellId         出售人用户id
	 * @param  int $sellCurrencyId 币种id
	 * @param  float $totalNum    交易额
	 * @param  float $sellOrderNum   订单号
	 * @return int 200表示成功，其他表示失败
	 */
	private function sellMoneyAndFinanceLog($sellId, $sellCurrencyId, $totalNum, $sellOrderNum){

		$sellExtArr = array(
			'fee'         => $totalNum,
			'operation'   => 'dec',
			'content'     => '币币交易卖出扣除',
			'financeType' => 'trade',
			'remarkInfo'  => $sellOrderNum
		);
		$sellRes = $this->calUserMoneyAndAddFinanceLog($sellId, $sellCurrencyId, $sellExtArr);

		if (empty($sellRes) || $sellRes != 200) {
			return 201;
		}

		return 200;
	}

	/**
	 * 计算用户余额及添加财务日志
	 * @author lirunqing 2017-11-27T15:58:01+0800
	 * @param  int    $userId     用户id
	 * @param  int    $currencyId 币种id
	 * @param  array  $extArr  拓展数组;
	 *                $extArr['fee'] 必传  币数量
	 *                $extArr['operation'] 必传  运算符,dec减；inc加
	 *                $extArr['content'] 必传  场景说明
	 *                $extArr['financeType'] 必传 财务类型;fee表示手续费，trade表示交易
	 *                $extArr['remarkInfo'] 非必传 交易订单号
	 * @return int  200表示成功，其他表示失败
	 */
	public function calUserMoneyAndAddFinanceLog($userId, $currencyId, $extArr=array()){

		$fee         = $extArr['fee'];
		$operation   = $extArr['operation'];
		$content     = $extArr['content'];
		$financeType = $extArr['financeType'];
		$remarkInfo  = !empty($extArr['remarkInfo']) ? $extArr['remarkInfo'] : 0;

		if (empty($fee) || empty($operation) || empty($content) || empty($financeType) || !in_array($operation, array('inc', 'dec')) ) {
			return 201;
		}
		$UserMoneyApi     = new UserMoneyApi();
		$publicFuntionObj = new PublicFunctionController();

		// 扣除/增加币数量
		$moneyRes = $UserMoneyApi->setUserMoney($userId, $currencyId, $fee, 'num', $operation);

		if (empty($moneyRes)) {
			return 202;
		}

		$typeArr = array(
			'dec' => 2,// 支出
			'inc' => 1,// 收入
		);
		$financeTypeArr = array(
			'fee' => array(
				'dec' => 10,// 扣除币币交易手续费
				'inc' => 11,// 返还币币交易手续费
			),
			'trade' => array(
				'dec' => 12,// 扣除币币交易数量
				'inc' => 13,// 返还币币交易数量
			)
		);

		$type         = $typeArr[$operation];
		$financeTypes = $financeTypeArr[$financeType][$operation];

		if (!empty($extArr['is_revoke']) && $extArr['is_revoke'] == 1) {
			$financeTypes = 37;
		}

		// 获取扣除/增加币余额
		$balance     = $publicFuntionObj->getUserBalance($userId, $currencyId);
		$sellDataArr = array(
			'financeType' => $financeTypes,
			'content'     => $content,
			'type'        => $type,
			'money'       => $fee,
			'afterMoney'  => $balance,
			'remarkInfo'  => $remarkInfo
		);

		// 添加财务日志
		$logRes = $UserMoneyApi->AddFinanceLog($userId, $currencyId, $sellDataArr);

		if (empty($logRes)) {
			return 203;
		}
		return 200;
	}


	/**
	 * 获取买入/卖出手续费
	 * @author lirunqing 2017-11-27T12:14:27+0800
	 * @param  integer  $areaId 交易区id
	 * @param  integer  $entrustType 委托类型
	 * @param  float  $num    交易数量
	 * @param  integer $type   1表示买入；2表示卖出
	 * @return float
	 */
	private function getFeeByTradeType($areaId, $entrustType, $num, $type=1){
		$fee = 0.0;
		if ($areaId == 1) {
			$fee = $this->getFee($areaId, $num, $type);
		}else{
			$areaCurrencyId = $this->areaBuyCurrencyIdArr[$areaId];
			$fee = $this->getSecFee($areaCurrencyId, $entrustType, $num, $type);
		}

		return $fee;
	}

	/**
	 * 二次市场获取买入/卖出手续费
	 * @author lirunqing 2017-11-27T12:14:27+0800
	 * @param  integer  $areaId 交易区id
	 * @param  float  $num    交易数量
	 * @param  integer $type   1表示买入；2表示卖出
	 * @return float
	 */
	private function getSecFee($mainCurrencyId, $entrustType, $num, $type=1){
		$where = array(
			'main_currency_id'     => $mainCurrencyId,
			'exchange_currency_id' => $entrustType,
		);
		$config = M("SecondClassCoinConfig")->where($where)->find();
		$fee    = 0.00000000;

		if ($type == 1) {
			$fee = big_digital_mul($num, $config['buy_fee']);
		}

		if ($type == 2) {
			$fee = big_digital_mul($num, $config['sell_fee']);
		}

		// 如果手续费小于8位小数，则不收取手续费
		if ($fee < 0.00000001) {
			$fee = 0.00000000;
		}

		return $fee;
	}

	/**
	 * 一次市场获取买入/卖出手续费
	 * @author lirunqing 2017-11-27T12:14:27+0800
	 * @param  integer  $areaId 交易区id
	 * @param  float  $num    交易数量
	 * @param  integer $type   1表示买入；2表示卖出
	 * @return float
	 */
	private function getFee($areaId, $num, $type=1){

		$config = $this->getCoinConfigByAreaId($areaId);
		$fee    = 0.00000000;

		if ($type == 1) {
			$fee = big_digital_mul($num, $config['buy_fee']);
		}

		if ($type == 2) {
			$fee = big_digital_mul($num, $config['sell_fee']);
		}

		// 如果手续费小于8位小数，则不收取手续费
		if ($fee < 0.00000001) {
			$fee = 0.00000000;
		}

		return $fee;
	}

	/**
	 * 根据areaid获取交易区币种配置
	 * @author lirunqing 2017-11-27T11:07:24+0800
	 * @param  int $areaId 交易区id
	 * @return array
	 */
	private function getCoinConfigByAreaId($areaId){

		$where['trade_area_id'] = $areaId;
		$coinConfig = M('BiBiConfig')->where($where)->find();

		return $coinConfig;
	}


}