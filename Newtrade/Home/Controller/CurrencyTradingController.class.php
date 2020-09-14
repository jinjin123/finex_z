<?php
/**
 * 币币交易模块
 * @author lirunqing 2017-11-23 11:05:30
 */
namespace Home\Controller;
use Home\Logics\CommonController;
// use Home\Logics\CurrencyTestLogicsController;
use Home\Logics\CurrencyTradingLogicsController;
use Home\Tools\AjaxPage;
use Common\Api\RedisCluster;
use Common\Api\CurrencyTradingConfig;
use Home\Logics\PublicFunctionController;
use Common\Api\redisKeyNameLibrary;
use Home\Logics\CheckAllCanUseParam;
use SwooleCommand\Controller\CurrencyTradeClientController;
use Common\Api\Maintain;

class CurrencyTradingController extends CommonController {

	private $limitArr  = array(1,2); // 交易区id,1表示btc交易区，2表示bch交易区
	private $tradeAreaArr;// 根据交易区获取交易区表名
	private $tradeSuccessArr;// 根据交易区获取交易成功表名
	private $areaBuyCurrencyIdArr;// 根据交易区和委托类别获取买入币种id
	private $areaSellCurrencyIdArr;// 根据交易区和委托类别获取卖出币种id;
	private $coinArr;// 根据交易区和币种获取交易币种类型
	private $coinList;// 根据交易区和币种获取交易币种类型
	private $currencyArr;

	public function _initialize(){
		parent::_initialize();
		$this->getCurrencyTradingConfig();
		$this->getCurrencyLogo();
	}

	/**
	 * 获取币币交易相关配置
	 * @author lirunqing 2017-11-30T19:29:34+0800
	 * @return [type] [description]
	 */
	private function getCurrencyTradingConfig(){
		$currencyArr = CurrencyTradingConfig::getTradingArea();

		// 暂时屏蔽VP交易区 add by lirunqing 2018年6月4日10:16:34
		// $vpConfigArr = CurrencyTradingConfig::getVPTradeArea();
		$this->currencyArr           = CurrencyTradingConfig::getTradeAreaInfoList();
		$this->tradeAreaArr          = CurrencyTradingConfig::$tradeAreaArr;
		$this->tradeSuccessArr       = CurrencyTradingConfig::$tradeAreasuccessArr;
		$this->areaSellCurrencyIdArr = CurrencyTradingConfig::getTradeAreaInfo();
		$this->areaBuyCurrencyIdArr  = CurrencyTradingConfig::getAllTradeAreaArr();
		$this->coinArr               = $currencyArr['coinArr'];
		$this->coinList              = $currencyArr['coinList'];
	}

	public function index(){

		$isMaintain    =  $this->getWebMaiantainInfo(Maintain::CTR);

		$this->assign('isMaintain', $isMaintain);
	    $this->assign('defaultCurrency',str_replace("/","_",$this->currencyArr[1]['child_info'][0]['coin_str'])); //建强补充
		$this->assign('currencyArr', $this->currencyArr);
		//$this->assign('isTour', 1);
		$this->assign('is_currency_tour', $this->userInfo['is_currency_tour']);
		$this->display();
	}

	/**
	 * 获取币种图片
	 * @author 2017-12-29T15:39:04+0800
	 * @return [type] [description]
	 */
	private function getCurrencyLogo(){
		$currencyList = M('Currency')->field('id,currency_logo')->where(['status' =>1])->select();
		$temp = array();
		foreach ($currencyList as $value) {
			$temp[$value['id']] = $value['currency_logo'];
		}

		foreach ($this->currencyArr as $key => $value) {
			$value['parent_info']['currency_logo'] = $temp[$value['parent_info']['currency_id']];
			$this->currencyArr[$key] = $value;
		}
	}

	/**
	 * 根据交易区及交易类型获取表名
	 * @author lirunqing 2017-11-30T15:46:27+0800
	 * @param  int $type      1表示正在交易,3表示历史订单,2表示撤销订单
	 * @param  int $tradeArea // 交易区id,1表示btc交易区；2表示bch交易区
	 * @return string
	 */
	private function getTradeAreaByType($type, $tradeArea){
		if (in_array($type, array(1,2))) {
			$tableName   = $this->tradeAreaArr[$tradeArea];
		}else{
			$tableName   = $this->tradeSuccessArr[$tradeArea];
		}
		return $tableName;
	}

	/**
	 * 撤销订单
	 * @author liruniqng 2017-11-30T20:39:38+0800
	 * @return json
	 */
	public function revokeOrder(){

		$orderId   = I('post.order_id');// 订单id
		$tradeArea = I('post.tradeArea');// 交易区id 1表示btc交易区；2表示bch交易区
		$userId    = getUserId();

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		if (empty($orderId)) {
			$res['msg'] = L('_NCXDWZD_');
			$this->ajaxReturn($res);
		}

		if (!in_array($tradeArea, $this->limitArr)){
			$res['msg'] = L('_ZWGJY_');
			$this->ajaxReturn($res);
		}
		
		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();

		// 防止用户重复撤销订单
		$isRev = $redis->get(redisKeyNameLibrary::CURTENCY_REVOKE_ORDER.$userId.$orderId);
		if (!empty($isRev)) {
			$res['msg'] = L('_QWCFCZ_');
			$this->ajaxReturn($res);
		}

		// 如果该订单正在匹配，则该订单不能进行撤销
		$isMatching = $redis->hget(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $userId."-".$orderId);
		if (!empty($isMatching)) {
			$res['msg'] = L('_GDDBGMCXSB_');
			$this->ajaxReturn($res);
		}

		$currencyTradingLogicsObj = new CurrencyTradingLogicsController();
		$revRes = $currencyTradingLogicsObj->revokerCurrencyOrder($orderId, $tradeArea, $userId);// 撤销订单

		if (empty($revRes) || $revRes['code'] != 200) {
			$res['msg']  = $revRes['msg'];
			$res['code'] = $revRes['code'];
			$this->ajaxReturn($res);
		}

		// 设置防止用户重复撤销订单key
		$redis->setex(redisKeyNameLibrary::CURTENCY_REVOKE_ORDER.$userId.$orderId, 5, true);

		$res['msg']  = L('_CHENGGONG_');
		$res['code'] = 200;
		$this->ajaxReturn($res);
	}

	/**
	 * 获取正在交易的订单/历史订单
	 * @author lirunqing 2017-11-30T16:58:22+0800
	 * @return json
	 */
	public function getOrderList(){

		$res = array(
			'code' => 200,
			'msg'  => L('_CHENGGONG_'),
			'data' => array()
		);

		$type      = I('get.type'); // 1表示正在交易,，2表示撤销/已完成订单
		$tradeArea = I('get.area_id');// 交易区id,1表示btc交易区；2表示bch交易区

		if (!in_array($type, array(1,2)) || !in_array($tradeArea, array(1,2))) {
			$res['data'] = array(
				'order_list' => array(),
				'show'       => '',
			);
			$this->ajaxReturn($res);
		}

		$currencyTradingLogicsObj = new CurrencyTradingLogicsController();
		// 检测网站是否维护
		$isMaintain = $currencyTradingLogicsObj->checkWebMaintain(1);
		if ($isMaintain['code'] != 200) {
			$maintainData['data'] = array(
				'order_list' => [],
				'show'       => ''
			);
			$this->ajaxReturn($maintainData);
		}

		$tableName   = $this->getTradeAreaByType($type, $tradeArea);
		$entrustType = I('get.entrust_type');// 委托类别,例如：1btc/bch 2 ltc/bch  3 etc/bch 4 eth/bc
		$where       = array();
		$userId      = getUserId();

		$where['entrust_type']   = $entrustType;
		$where['sell_id|buy_id'] = $userId;

		// $field = 'id as order_id,entrust_type,sell_id,buy_id,trade_num as entrust_num,trade_num as success_num,trade_price as entrust_price,trade_money as entrust_money,sell_fee,buy_fee,trade_time as add_time';	
		// $orderBy = 'trade_time desc';
		if ($type == 1) {
			$where['status'] = array('in', array(1,2));
			$field           = 'id as order_id,entrust_type,entrust_num,entrust_price,entrust_money,sell_id,buy_id,status,';
			$field          .= 'success_num,leave_num,add_time';
			$orderBy         = 'add_time desc';
		}else {
			$where['status'] = array('in', array(3,4));
			$field           = 'id as order_id,entrust_type,update_time,entrust_num,entrust_price,entrust_money,status,';
			$field          .= 'sell_id,buy_id,success_num,leave_num,add_time';
			$orderBy         = 'add_time desc';
		}

		$total    = M($tableName)->where($where)->count();
		$AjaxPage = new AjaxPage($total, 10, 'insertTable');// 第三个参数需要填写前端js文件的function名称
		$limit    = $AjaxPage->firstRow.','.$AjaxPage->listRows;
		$list     = M($tableName)->where($where)->field($field)->limit($limit)->order($orderBy)->select();
		$show     = $AjaxPage->show();
		$pIdArr	  = array();
		$statusStr = [
			3 => L('_YWC_'),
			4 => L('_YCX_'),
		];
		foreach ($list as $key => $value) {

			$pIdArr[$value['order_id']] = $value['order_id'];

			if ($value['sell_id'] == $userId) {
				$value['type_str'] = L('_MAII_');
			}
			if ($value['buy_id'] == $userId){
				$value['type_str'] = L('_MAI_');
			}

			$value['coin'] = $this->coinList[$tradeArea][$value['entrust_type']];
			$value['add_time'] = date("Y-m-d H:i:s", $value['add_time']);
			if ($type == 2) {
				$value['add_time'] = date("Y-m-d H:i:s", $value['update_time']);
			}

			if (in_array($value['status'], array(3,4))) {
				$value['status_str'] = $statusStr[$value['status']];
			}else{
				$value['status_str'] = L('_JIAOYZ_');
			}
            $value['success_num'] = $value['success_num']; //0.0000000 -> 0

			unset($value['sell_id']);
			unset($value['buy_id']);

			$list[$key] = $value;
		}

		// 获取成交均价及成交总额
		$orderList = $this->getDealChildInfo($list, $pIdArr, $tradeArea);

		$res['data'] = array(
			'order_list' => $orderList,
			'show'       => $show
		);
		$this->ajaxReturn($res);
	}

	/**
	 * 获取成交均价及成交总额
	 * @author lirunqing 2018-06-27T16:58:38+0800
	 * @param  array $list      父单数组信息
	 * @param  array $pIdArr    父订单id数组
	 * @param  int $tradeArea 交易区id,1表示btc交易区；2表示bch交易区
	 * @return array
	 */
	private function getDealChildInfo($list, $pIdArr, $tradeArea){


		$sellList = array();
		$buyList  = array();
		// 计算子单平均成交价格
		if (!empty($pIdArr)) {
			$childSellWhere = array(
				'pid_sell' => array('in', $pIdArr),
			);
			$childTableName = $this->getTradeAreaByType(3, $tradeArea);
			$childSellField = "pid_sell,AVG(trade_price) as avg_price,SUM(trade_money) as sum_money";
			$childSellList  = M($childTableName)->field($childSellField)->where($childSellWhere)->group('pid_sell')->select();

			$sellList = array();
			foreach ($childSellList as $key => $value) {
				$sellList[$value['pid_sell']]['avg_price'] = sprintf("%.8f", $value['avg_price']);
				$sellList[$value['pid_sell']]['sum_money'] = $value['sum_money'];
			}

			$childBuyWhere = array(
				'pid_buy' => array('in', $pIdArr),
			);
			$childTableName = $this->getTradeAreaByType(3, $tradeArea);
			$childBuyField  = "pid_buy,AVG(trade_price) as avg_price,SUM(trade_money) as sum_money";
			$childBuyList   = M($childTableName)->field($childBuyField)->where($childBuyWhere)->group('pid_buy')->select();	

			$buyList = array();
			foreach ($childBuyList as $key => $value) {
				$buyList[$value['pid_buy']]['avg_price'] = sprintf("%.8f", $value['avg_price']);
				$buyList[$value['pid_buy']]['sum_money'] = $value['sum_money'];
			}
		}

		foreach ($list as $key => $value) {
			
			$value['avg_price'] = '0.00000000';
			$value['sum_money'] = '0.00000000';
			if (!empty($buyList[$value['order_id']]['avg_price'])) {
				$value['avg_price'] = $buyList[$value['order_id']]['avg_price'];
			}
			if (!empty($buyList[$value['order_id']]['sum_money'])) {
				$value['sum_money'] = $buyList[$value['order_id']]['sum_money'];
			}

			if (!empty($sellList[$value['order_id']]['avg_price'])) {
				$value['avg_price'] = $sellList[$value['order_id']]['avg_price'];
			}
			if (!empty($sellList[$value['order_id']]['sum_money'])) {
				$value['sum_money'] = $sellList[$value['order_id']]['sum_money'];
			}

			$list[$key] = $value;
		}

		return $list;
	}

	/**
	 * 检测价格及数量是否正确
	 * @author lirunqing 2017-12-20T17:25:58+0800
	 * @return json
	 */
	public function checkUserPrice(){
		$data = I('post.');
		// 检测参数
		$this->checkParams($data);
		// 检测限价单的价格是否超出浮动比例
		$this->checkUserPriceIsTrue($data);
		$userId = getUserId();
		// 检测是否24H修改过资金密码
		$this->checkTradePwdIsChange();
		// 未实名认证
		$userObj = new CheckAllCanUseParam();
		$ret     = $userObj->checkUserRealStatus($userId);
		// 未实名认证
		if (empty($ret) || $ret['code'] != 200) {
			$this->ajaxReturn($ret);
		}

		// 护照过期
		$relt = $userObj->checkUserRealIsExpire($userId);
		if (empty($relt) || $relt['code'] != 200) {
			$this->ajaxReturn($relt);
		}
		// $isReal = checkUserReal($userId);
		// if ($isReal <= 0) {
		// 	$res['msg'] = L('_WJXSMRZ_');
		// 	$this->ajaxReturn($res);
		// }

		// 获取用户交易数量及币种id
		$userNumInfo = $this->getUserNumAndCurrencyId($data);
		$num         = $userNumInfo['num'];
		$currencyId  = $userNumInfo['currencyId'];
		
		// 检测用资金是否足够
		$this->checkUserMoneyIsEnough($userId, $currencyId, $num);

		$res    = array(
			'code' => 200,
			'msg'  => '',
			'data' => array()
		);

		$this->ajaxReturn($res);
	}

	/**
	 * 获取购买数量
	 * @author lirunqing 2018-01-04T17:33:22+0800
	 * @return json
	 */
	public function getBuyNumByPriceAndNum(){
		$data    = I('post.');
		$type    = $data['type'];// 1表示相乘，2表示相除
		$userNum = $data['num'];
		$price   = $data['price'];
		$num     = 0;
		if ($type == 1) {
			$num = big_digital_mul($price, $userNum);
		}
		if ($type == 2) {
			$num = big_digital_div($userNum, $price);
		}

		$res    = array(
			'code' => 200,
			'msg'  => '',
			'data' => array('num' => $num)
		);

		$this->ajaxReturn($res);
	}

	/**
	 * 币币交易业务处理
	 * @author lirunqing 2017-11-27T10:39:43+0800
	 * @return [type] [description]
	 */
	public function processTradeInfo(){

		$data = I('post.');
		// $data['tradeType'] = 1;// 交易区,1表示btc交易区，2表示VP交易区
		// $data['priceType'] = 1;// 价格类型，1表示限价单，2表示市价单
		// $data['transactionType'] = 1;// 交易类型，1表示买入，2表示卖出
		// $data['entrustType'] = 1;// 委托类别;例如：1表示ltc/btc 2表示etc/btc 3表示eth/btc 4表示bcc/btc
		// $data['totalPrice'];//选择市价单时买入/卖出金额
		// $data['entrustPrice'] = '0.11404023';// 单价
		// $data['leaveNum'] = 20;// 数量
		// $data['tradePwd'] = '123456';//资金密码

		$userId = getUserId();
		$res    = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);
		// 检测参数
		$this->checkParams($data);

		$currencyTradingLogicsObj = new CurrencyTradingLogicsController();

		// 检测网站是否维护
		$isMaintain = $currencyTradingLogicsObj->checkWebMaintain(2);
		// var_dump($isMaintain);die;
		if ($isMaintain['code'] != 200) {
			$res['msg']  = $isMaintain['msg'];
			$res['code'] = $isMaintain['code'];
			$this->ajaxReturn($res);
		}

		// 检测用户资金的正确
		$this->checkUserTradePwd($userId, $data['tradePwd']);
		// 获取用户交易数量及币种id
		$userNumInfo = $this->getUserNumAndCurrencyId($data);
		$data        = $userNumInfo['data'];
		$num         = $userNumInfo['num'];
		$currencyId  = $userNumInfo['currencyId'];

		
		// 检测限价单的价格是否超出浮动比例
		$this->checkUserPriceIsTrue($data);

		// 检测是否24H修改过资金密码
		$this->checkTradePwdIsChange();
		// 未实名认证
		$userObj = new CheckAllCanUseParam();
		$ret     = $userObj->checkUserRealStatus($userId);
		// 未实名认证
		if (empty($ret) || $ret['code'] != 200) {
			$this->ajaxReturn($ret);
		}

		// 护照过期
		$relt = $userObj->checkUserRealIsExpire($userId);
		if (empty($relt) || $relt['code'] != 200) {
			$this->ajaxReturn($relt);
		}

		// $redisObj = new RedisCluster();
		$redis    = RedisCluster::getInstance();
		$isOrder  = $redis->get(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE.$userId);
		// 防止用户重复提交订单
		if (!empty($isOrder)) {
			$res['msg'] = L('_QWCFCZ_');
			$res['code'] = 302;
			$this->ajaxReturn($res);
		}

		// 检测用资金是否足够
		$this->checkUserMoneyIsEnough($userId, $currencyId, $num);

		// 检测用户是否存在未完成的订单
		$isComplete = $currencyTradingLogicsObj->checkIsCompleteOrderExist($userId, $data['tradeType'], $data['entrustType'], $data['transactionType']);
		if ($isComplete != 200) {
			$res['msg']  = L('_CZWWCDD_');
			$res['code'] = 303;
			$this->ajaxReturn($res);
		}

		$addRes = $currencyTradingLogicsObj->addOrderByUserSubData($data, $userId);

		if (empty($addRes) || $addRes['code'] != 200) {
			$res['msg']  = ($data['transactionType'] == 1) ? L('_MRSBQSHZS_') : L('_MCSBSHS_');
			$res['code'] = 304;
			$this->ajaxReturn($res);
		}

		$orderId   = $addRes['data']['id'];
		$orderInfo = $this->getOrderInfo($data['tradeType'], $orderId);// 获取当前插入最后一条未完成的订单信息

		$redis->setex(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE.$userId, 5, true);// 防止重复提交订单

		$res['code'] = 200;
		$res['msg']  = L('_CZCG_');
		$res['data'] = array('orderInfo' => $orderInfo);
		$this->ajaxReturn($res);
	}

	/**
	 * 获取币种id及交易数量
	 * @author lirunqing 2017-12-21T11:09:48+0800
	 * @param  array $data 
	 * @return array
	 */
	private function getUserNumAndCurrencyId($data){
		$num = big_digital_mul($data['entrustPrice'], $data['leaveNum']);
		$currencyId = $this->areaBuyCurrencyIdArr[$data['tradeType']];

		// 买入，用户选择市价单
		if ($data['priceType'] == 2 && $data['transactionType'] == 1) {
			$data['entrustPrice'] = $this->getPriceByTradeArea($data['tradeType'], $data['transactionType'], $data['entrustType']);
			$data['leaveNum']     = big_digital_div($data['totalPrice'], $data['entrustPrice'], 8);// 数量
			$num                  = $data['totalPrice'];
		}
		// 卖出，用户选择市价单
		if ($data['priceType'] == 2 && $data['transactionType'] == 2) {
			$data['entrustPrice'] = $this->getPriceByTradeArea($data['tradeType'], $data['transactionType'], $data['entrustType']);
			$data['leaveNum']     = $data['totalPrice'];// 数量
		}

		if ($data['transactionType'] == 2) {// 卖出获取交易币的数量及币种id
			$num        = $data['leaveNum'];
			$currencyId = $this->areaSellCurrencyIdArr[$data['tradeType']][$data['entrustType']];
		}

		$num = getDecimal($num);

		$returnData = array(
			'num'        => $num,
			'currencyId' => $currencyId,
			'data'       => $data
		);
		return $returnData;
	}

	/**
	 * 检测价格是否超限
	 * @author lirunqing 2017-12-20T17:18:23+0800
	 * @param  array $data 
	 * @return json
	 */
	private function checkUserPriceIsTrue($data){
		$currencyTradingLogicsObj = new CurrencyTradingLogicsController();
		// 检测限价单的价格是否超出浮动比例
		$marketPrice  = $this->getPriceByTradeArea($data['tradeType'], $data['transactionType'], $data['entrustType']);
		if ($data['tradeType'] == 1) {
			$priceRateRes = $currencyTradingLogicsObj->priceFloatingRatio($data['tradeType'], $data['entrustPrice'], $marketPrice);
		}else{
			$areaCurrencyId = $this->areaBuyCurrencyIdArr[$data['tradeType']];
			$priceRateRes = $currencyTradingLogicsObj->priceFloatingRatioToVp($areaCurrencyId, $data['entrustType'], $data['entrustPrice'], $marketPrice);
		}

		$floatPriceRate = $currencyTradingLogicsObj->getUserPriceFloatingRatio($data);
		
		if ($priceRateRes != 200 && $data['priceType'] == 1) {
			$floatPriceRate = $floatPriceRate * 100;
			$msg = str_replace('10', $floatPriceRate, L('_NSRDJGCCXZ_'));
			$res['msg'] = $msg;
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 获取当前插入最后一条未完成的订单信息
	 * @author lirunqing 2017-12-15T12:06:18+0800
	 * @param  int $tradeType 交易区,1表示btc交易区，2表示VP交易区
	 * @param  int $id    订单id
	 * @return array
	 */
	private function getOrderInfoByUserIdAndAddTime($tradeType, $userId, $addTime, $transactionType){
		$tableName = $this->tradeAreaArr[$tradeType];

		$tradeTypeArr = [
			'1' => 'buy_id',
			'2' => 'sell_id',
		];

		$getLastWhere = array(
			'add_time' => $addTime,
			'status' => $transactionType,
		);
		$getLastWhere[$tradeTypeArr[$transactionType]] = $userId;
		$field     = 'id as order_id,entrust_type,entrust_num,entrust_price,sell_id,buy_id,entrust_money,success_num,leave_num,add_time';
		$orderInfo = M($tableName)->where($getLastWhere)->field($field)->order('add_time desc')->find();

		if (empty($orderInfo)) {
			return array();
		}

		if ($orderInfo['leave_num'] <= 0) {
			return array();
		}

		$orderInfo['type_str'] = !empty($orderInfo['sell_id']) ? L('_MAII_') : L('_MAI_');
		$orderInfo['add_time'] = date('Y-m-d H:i:s', $orderInfo['add_time']);
		$orderInfo['coin']     = $this->coinList[$tradeType][$orderInfo['entrust_type']];

		unset($orderInfo['sell_id']);
		unset($orderInfo['buy_id']);

		return $orderInfo;
	}

	/**
	 * 获取当前插入最后一条未完成的订单信息
	 * @author lirunqing 2017-12-15T12:06:18+0800
	 * @param  int $tradeType 交易区,1表示btc交易区，2表示VP交易区
	 * @param  int $id    订单id
	 * @return array
	 */
	private function getOrderInfo($tradeType, $id){
		$tableName = $this->tradeAreaArr[$tradeType];
		$getLastWhere = array(
			'id' => $id,
		);
		$field     = 'id as order_id,entrust_type,entrust_num,entrust_price,sell_id,buy_id,entrust_money,success_num,leave_num,add_time';
		$orderInfo = M($tableName)->where($getLastWhere)->field($field)->order('add_time desc')->find();

		if (empty($orderInfo)) {
			return array();
		}

		if ($orderInfo['leave_num'] <= 0) {
			return array();
		}

		$orderInfo['type_str'] = !empty($orderInfo['sell_id']) ? L('_MAII_') : L('_MAI_');
		$orderInfo['add_time'] = date('Y-m-d H:i:s', $orderInfo['add_time']);
		$orderInfo['coin']     = $this->coinList[$tradeType][$orderInfo['entrust_type']];

		unset($orderInfo['sell_id']);
		unset($orderInfo['buy_id']);

		return $orderInfo;
	}

	/**
	 * 检测用户是否更改过交易密码，如果更改过，24H不能交易
	 * @author liruniqng 2017-12-04T10:37:36+0800
	 * @return [type] [description]
	 */
	private function checkTradePwdIsChange(){
		$checkUserInfoobj = new CheckUserInfoController();
		$checkUserInfoobj->checkTradePwdIsChange();
	}

	/**
	 * 市价单，买入卖出获取交易价格
	 * @author lirunqing 2017-12-07T16:53:45+0800
	 * @param  int $tradeType       // 交易区,1表示btc交易区，2表示VP交易区
	 * @param  int $transactionType // 交易类型，1表示买入，2表示卖出
	 * @param  int $entrustType     // 委托类别;例如：1表示ltc/btc 2表示etc/btc 3表示eth/btc 4表示bcc/btc
	 * @return float
	 */
	private function getPriceByTradeArea($tradeType, $transactionType, $entrustType){

		$coinObj      = new CoinTradeInfoController();
		$currencyArr  = $coinObj->getCurrencyInfoListToAll();

		$price = 0.0;
		$price = $currencyArr[$tradeType][$entrustType]['buy'];
		if ($transactionType == 2) {
			$price = $currencyArr[$tradeType][$entrustType]['sell'];
		}

		return $price;
	}

	/**
	 * 检测用户资金是否足够交易
	 * @author lirunqing 2017-12-04T11:13:41+0800
	 * @param  int $userId     用户id
	 * @param  int $currencyId 币种id
	 * @param  float $num      交易数量
	 * @return json
	 */
	private function checkUserMoneyIsEnough($userId, $currencyId, $num){

		$where = array(
			'uid' => $userId,
			'currency_id' => $currencyId
		);
		$userMoneyInfo = M('UserCurrency')->where($where)->find();

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);
		
		$isTrue = bccomp($userMoneyInfo['num'], $num, 8);
		$num    = (string)$num;

		if (empty($userMoneyInfo['num']) || $isTrue < 0) {
			$res['msg'] = L('_NDZJBZ_');
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 检测挂单参数
	 * @author lirunqing 2017-12-04T09:59:45+0800
	 * @param  array $data 
	 * @return json
	 */
	private function checkParams($data){

		$data['tradeType'];// 交易区,1表示btc交易区，2表示bch交易区
		$data['priceType'];// 价格类型，1表示限价单，2表示市价单
		$data['transactionType'];// 交易类型，1表示买入，2表示卖出
		$data['entrustType'];// 委托类别;例如：1表示ltc/btc 2表示etc/btc 3表示eth/btc 4表示bcc/btc
		$data['totalPrice'];//选择市价单时买入/卖出金额
		$data['entrustPrice'];// 单价
		$data['leaveNum'];// 数量
		$data['tradePwd'];//资金密码

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		if (empty($data['tradeType']) || !in_array($data['tradeType'], $this->limitArr)) {
			$res['msg'] = L('_QXZDQ_');
			$this->ajaxReturn($res);
		}

		if (empty($data['transactionType']) || !in_array($data['transactionType'], array(1, 2))) {
			$res['msg'] = L( '_QXZJYLX_');
			$this->ajaxReturn($res);
		}

		$entrustTypeArr = array_flip($this->areaSellCurrencyIdArr[$data['tradeType']]);
		if (empty($data['entrustType']) || !in_array($data['entrustType'], $entrustTypeArr)) {
			$res['msg'] = L('_QXZBZ_');
			$this->ajaxReturn($res);
		}

		// 限价单需要填写价格,市价单直接获取最新交易价
		if (empty($data['entrustPrice']) && $data['priceType'] == 1) {
			$res['msg'] = L('_QTXDJ_');
			$this->ajaxReturn($res);
		}

		if ($data['entrustPrice'] <= 0 && $data['priceType'] == 1) {
			$res['msg'] = L('_QTXDJ_');
			$this->ajaxReturn($res);
		}

		if (!regex($data['entrustPrice'], 'double') && $data['priceType'] == 1) {
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}

		// 限价单需要填写数量,市价单直接获取最新交易价
		if (empty($data['leaveNum']) && $data['priceType'] == 1) {
			$res['msg'] = L( '_QTXSL_');
			$this->ajaxReturn($res);
		}

		if ($data['leaveNum'] <= 0 && $data['priceType'] == 1) {
			$res['msg'] = L( '_QTXSL_');
			$this->ajaxReturn($res);
		}

		if (!regex($data['leaveNum'], 'double') && $data['priceType'] == 1) {
			$res['msg'] = L( '_QTXZQSL_');
			$this->ajaxReturn($res);
		}

		// 市价单需要填写数量,市价单直接获取最新交易价
		if (empty($data['totalPrice']) && $data['priceType'] == 2) {
			$res['msg'] = ($data['transactionType'] == 1) ? L('_QTXMRJE_') : L( '_QTXMCJE_');
			$this->ajaxReturn($res);
		}

		if ($data['totalPrice'] <= 0 && $data['priceType'] == 2) {
			$res['msg'] = ($data['transactionType'] == 1) ? L('_QTXMRJE_') : L( '_QTXMCJE_');
			$this->ajaxReturn($res);
		}

		if (!regex($data['totalPrice'], 'double') && $data['priceType'] == 2) {
			$res['msg'] = ($data['transactionType'] == 1) ? L('_QTXZQDMRJE_') : L( '_QTXZQDMCJE_');
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 检测用户的资金密码是否正确
	 * @author lirunqing 2017-12-20T17:10:11+0800
	 * @param  int $userId   用户id
	 * @param  string $tradePwd 资金密码
	 * @return json
	 */
	private function checkUserTradePwd($userId, $tradePwd){

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		if (empty($tradePwd)) {
			$res['msg'] = L('_JYMMBNWK_');
			$this->ajaxReturn($res);
		}

		$publicFunctionObj = new PublicFunctionController();
		//验证交易密码的正确性
		$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $tradePwd);
		if($tradePwdRes['code'] != 200){
			$res['msg'] = $tradePwdRes['msg'];
			// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
			$this->ajaxReturn($res);
		}
	}
}