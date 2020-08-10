<?php
/**
 * 线下交易模块业务逻辑
 * @author lirunqing 2017-10-12 16:56:39
 */
namespace Home\Logics;
use Think\Controller;
use Home\Model\ConfigModel;
use Home\Model\UserCurrencyModel;
use Home\Model\CurrencyModel;
use Home\Logics\UserMoneyApi;
use Home\Model\CoinConfigModel;
use Common\Api\Point;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
use Home\Tools\SceneCode;
use SwooleCommand\Logics\TradePush;
use Common\Api\Maintain;

class OffTradingLogicsController extends Controller {

	protected $configList          = array(); // 网站配置列表初始化
	protected $coinConfigModel     = null; // 币种配置模型
	protected $configModel         = null; // 网站配置模型
    public   $acceptPaidOvertime  = 43200; // 确认收款超时配置，12小时
    public   $confirmPaidOvertime = 1800; // 确认打款超时配置，30分钟
	public    $abnormalSecond      = 7200; // 2个小时收款异常按钮显示时间
	private   $decPointArr         = array( // 用户等级对应减积分
				'0' => Point::DECR_TRADE_LEVEL_ZERO,
				'1' => Point::DECR_TRADE_LEVEL_ONE,
				'2' => Point::DECR_TRADE_LEVEL_TWO,
				'3' => Point::DECR_TRADE_LEVEL_THREE,
				'4' => Point::DECR_TRADE_LEVEL_FOUR,
				'5' => Point::DECR_TRADE_LEVEL_FIVE,
			);
	private   $incPointArr = array(// 用户等级对应加积分
				'0' => Point::ADD_TRADE_LEVEL_ZERO,
				'1' => Point::ADD_TRADE_LEVEL_ONE,
				'2' => Point::ADD_TRADE_LEVEL_TWO,
				'3' => Point::ADD_TRADE_LEVEL_THREE,
				'4' => Point::ADD_TRADE_LEVEL_FOUR,
				'5' => Point::ADD_TRADE_LEVEL_FIVE,
			);
	private  $errorArr = array(
				'code' => 201,
				'msg'  => '',
				'data' => array()
			);

	private $notesArr      = array(
				'86'  => 'CNY',
				// '886' => 'TWD',
				'852' => 'HKD',
			);

	/**
	 * 自动加载
	 * @author lirunqing 2017-10-12T17:41:16+0800
	 */
	public function _initialize(){
		$this->setObj(); // 获取业务相关对象
		$this->configList = $this->configModel->getConfigList();// 获取网站配置列表	
	}

	/**
	 * 检测网站是否维护
	 * @author lirunqing 2019-02-26T10:46:01+0800
	 * @param  integer $type 1表示市场订单列表维护开关 2表示下单及购买维护开关，3表示我的订单列表维护开关
	 * @return array
	 */
	public function checkWebMaintain($type=1){
		$p2pMaintain = Maintain::getTradeMaintainVals(Maintain::P2P);

		// 检测C2C模块是否开启维护模式
		$forbidOrder    = $p2pMaintain['forbid_order'];// 下单开关
		$masterSwitch   = $p2pMaintain['master_switch'];// p2p模式总开关
		$dealOrder      = $p2pMaintain['deal_order'];// 我的订单  开关
		$listOrder      = $p2pMaintain['list_order'];//  市场订单列表开关
		$msgArr         = [];
		$msgArr['code'] = 200;

		// 1表示市场订单列表维护开关 
		if ($type == 1 && ($listOrder == 1 || $masterSwitch == 1)) {
			$msgArr['code'] = 701;
			$msgArr['msg']  = L('_PPSCDDWHZ_');
			return $msgArr;
		}

		if ($type == 1){
			return $msgArr;
		}

		// 表示我的订单列表维护开关
		if ($type == 3 && ($dealOrder == 1 or $masterSwitch == 1)) {
			$msgArr['code'] = 702;
			$msgArr['msg']  = L('_PPWDDDWHZ_');
			return $msgArr;
		}

		if ($type == 3){
			return $msgArr;
		}

		// $type==2 下单及购买维护开关
		if ($masterSwitch == 1) {
			$msgArr['code'] = 703;
			$msgArr['msg']  = L('_PPMSWHZ_');
			return $msgArr;
		}

		if ($dealOrder == 1) {
			$msgArr['code'] = 704;
			$msgArr['msg']  = L('_PPWDDDWHZ_');
			return $msgArr;
		}

		if ($forbidOrder == 1) {
			$msgArr['code'] = 705;
			$msgArr['msg']  = L('_PPXDMKWHZ_');
			return $msgArr;
		}

		return $msgArr;
	}

	/**
	 * 根据用户id获取购买2次及以上的订单
	 * @author lirunqing 2017-11-8 14:42:06
	 * @param  string $buyId 用户id
	 * @return array
	 */
	public function getOfftradingRepeatBuyersByUserid($buyId=''){
		$whereOrder['status']   = array('in', array(1,2,3,8));
        $today                  = strtotime(date('Y-m-d'));
        if(empty($buyId)){
            $buyId = getUserId();
        }
        $whereOrder['buy_id']   = $buyId;
        $whereOrder['add_time'] = array('egt',$today);
        $tableName              = 'TradeTheLine';
        $dataList               = M($tableName)->field('sell_id')->where($whereOrder)->select();

        $list         = array();
        $repeaetIdArr = array();

        foreach( $dataList as $data){
            $list[] = $data['sell_id'];
        }

        $repeatArr = array_count_values($list); // 统计每个sell_id出现的次数

        // 获取出现2次及以上的sell_id
        foreach( $repeatArr as $k => $data){
            if($data > 1){
                $repeaetIdArr[] = $k;
            }
        }

        return $repeaetIdArr;
	}

	 /**
     * 获取美金兑人民币汇率
    */
    public function getConfigHUILV(){

    	$res=M('Config')->select();
        $arr=[];   
    	foreach($res as $v)
    	{   
    		 $arr[$v['key']]=$v['value'];
    	}
        return [ 
        		 "86"=>$arr['RMB_HUILV'],
        		 "886"=>$arr['TW_HUILV'],
        		 "852"=>$arr['HK_HUILV'],
        ];
    }

	/**
	 * 检测币种总值是否达到最小限制
	 * @author liruniqng 2018-03-13T21:04:51+0800
	 * @param  [type] $currencyId [description]
	 * @param  [type] $totalPrice [description]
	 * @return [type]             [description]
	 */
	public function checkTotalPrice($currencyId, $totalPrice){

		$where = array(
			'currency_id' => $currencyId
		);
		$ccConfigInfo = M('CcConfig')->where($where)->find();

		if (empty($ccConfigInfo)) {
			return ['code' => 201, 'msg'=>L('_SJYCQSHCS_'), 'data' => array()];
		}

		if ($totalPrice < $ccConfigInfo['min_trade_money']) {
			return ['code' => 201, 'msg'=>L('_ZJEXYGDJE_')."$".$ccConfigInfo['min_trade_money'], 'data' => array()];
		}

		return ['code' => 200, 'msg'=>L('_SJYCQSHCS_'), 'data' => array()];
	}

	/**
	 * 计算收取的手续费
	 * @author lirunqing 2017-11-03T14:44:27+0800
	 * @param  int $currencyId 币种id
	 * @param  float $num      出售/购买币数量
	 * @param  int $type       买家/卖家类型;1表示卖家,2表示买家
	 * @return float
	 */
	public function getFee($currencyId, $num, $type=1){

		$where['id']  = $currencyId;
		$currencyInfo = M('Currency')->where($where)->find();
		$fee          = 0.0000;

		if ($type == 1) {
			$fee = big_digital_mul($num, $currencyInfo['sell_off_line_fee'], 4);//线下交易卖家手续费百分比
		}

		if ($type == 2) {
			$fee = big_digital_mul($num, $currencyInfo['buy_off_line_fee'], 4);//线下交易买家手续费百分比
		}

		// 如果手续费小于8位小数，则不收取手续费
		if ($fee < 0.0001) {
			$fee = 0.0000;
		}

		return $fee;
	}

	/**
	 * 处理用户确认收款业务
	 * @author 2017-11-03T15:45:43+0800
	 * @param  [type] $orderRes [description]
	 * @return [type]           [description]
	 */
	public function orderAccept($orderRes){

		if ($orderRes['status'] != 2) {
			$this->errorArr['msg'] = L('_QWCFCZ_');
			$this->ajaxReturn($this->errorArr);
		}

		$nowTime          = time();
		$publicFuntionObj = new PublicFunctionController();
		$whereUser['uid'] = $orderRes['sell_id'];
        $userLevelInfo    = M('User')->field('level')->where($whereUser)->find(); 

        //开启事务
		M()->startTrans();

        // 判断用户确认收款是否超时,如果超时则减积分且添加一次失信次数;如果不超时，则添加积分
		if (($nowTime - $orderRes['shoukuan_time']) >= $this->acceptPaidOvertime) {
			$decIntegral = $this->decPointArr[$userLevelInfo['level']];
			$extArr['operationType'] = 'dec';
			$extArr['isOverTime']    = 1;
			$extArr['status']        = 9;
			$extArr['scoreInfo']     = L('_XXJYQRSKCSJJF_');//线下交易确认收款超时减积分
			$extArr['remarkInfo']    = $orderRes['order_num'];
			$addPointRes = $publicFuntionObj->calUserIntegralAndLeavl($orderRes['sell_id'], $decIntegral, $extArr);
		}else{
			$incIntegral = $this->incPointArr[$userLevelInfo['level']];
			$extArr['operationType'] = 'inc';
			$extArr['isOverTime']    = 0;
			$extArr['status']        = 9;
			$extArr['scoreInfo']     = L('_XXJYSKWCSJJF_');//线下交易确认收款未超时加积分
			$extArr['remarkInfo']    = $orderRes['order_num'];
			$addPointRes = $publicFuntionObj->calUserIntegralAndLeavl($orderRes['sell_id'], $incIntegral, $extArr);
		}

		if (empty($addPointRes)) {
			M()->rollback(); // 事务回退
			$this->errorArr['msg'] = L('_XTFMSHCS_');
			$this->ajaxReturn($this->errorArr);
		}

		// 确认收款后，增加购买人币数量
		$this->processOrderAccept($orderRes);

		M()->commit();// 事务提交

		// 极光推送APP信息
		$this->pushAppInfo(3, $orderRes);
		
		return true;
	}

	/**
	 * 确认收款后，增加购买人币数量
	 * @author lirunqing 2017-11-03T15:46:21+0800
	 * @param  array $orderRes 订单信息
	 * @return bool|json
	 */
	private function processOrderAccept($orderRes){

		// 接单到打款时间
		$where['id'] = $orderRes['id'];
		$r[]         = M('TradeTheLine')->where($where)->setField('end_time',time());
		$r[]         = M('TradeTheLine')->where($where)->setField('status','3');

		$UserMoneyApi     = new UserMoneyApi();
		$publicFuntionObj = new PublicFunctionController();
		//加购买人的币
		$r[] = $UserMoneyApi->setUserMoney($orderRes['buy_id'], $orderRes['currency_id'], $orderRes['num']);
		// 获取交易后的余额
		$balance = $publicFuntionObj->getUserBalance($orderRes['buy_id'], $orderRes['currency_id']);
		$sellDataArr = array(
			'financeType' => 6,
			'content'     => L('_XXJYGMRHQ_'),//线下交易购买人获取
			'type'        => 1,
			'money'       => $orderRes['num'],
			'afterMoney'  => $balance,
			'remarkInfo'  => $orderRes['order_num']
		);
		$r[] = $UserMoneyApi->AddFinanceLog($orderRes['buy_id'], $orderRes['currency_id'], $sellDataArr);

		// 是否要收取手续费
		if (!empty($orderRes['buy_fee']) && $orderRes['buy_fee'] > 0) {

			$r[] = $UserMoneyApi->setUserMoney($orderRes['buy_id'], $orderRes['currency_id'], $orderRes['buy_fee'], 'num', 'dec');
			// 获取扣除手续费后的余额
			$balance = $publicFuntionObj->getUserBalance($orderRes['buy_id'], $orderRes['currency_id']);
			$sellDataArr = array(
				'financeType' => 8,
				'content'     => L('_XXJYMRKCSXF_'),//线下交易买入扣除手续费
				'type'        => 2,
				'money'       => $orderRes['buy_fee'],
				'afterMoney'  => $balance,
				'remarkInfo'  => $orderRes['order_num']
			);
			$r[] = $UserMoneyApi->AddFinanceLog($orderRes['buy_id'], $orderRes['currency_id'], $sellDataArr);
		}

		//返回结果
		if(in_array(false, $r)){
			M()->rollback(); // 事务回退
			$this->errorArr['msg'] = L('_CZSBQCS_');
			$this->ajaxReturn($this->errorArr);
		}

		return true;
	}

	/**
	 * 处理用户确认打款业务
	 * @author lirunqing 2017-11-02T14:50:35+0800
	 * @param  array $orderRes 交易订单信息数组
	 * @return bool|json
	 */
	public function confirmOrderPaid($orderRes){

		if ($orderRes['status'] != 1) {
			$this->errorArr['msg'] = L('_QWCFCZ_');
			$this->ajaxReturn($this->errorArr);
		}

		$nowTime          = time();
		$publicFuntionObj = new PublicFunctionController();
		$whereUser['uid'] = $orderRes['buy_id'];
        $userLevelInfo    = M('User')->field('level')->where($whereUser)->find(); 

        //开启事务
		M()->startTrans();

		// 判断用户打款是否超时,如果超时则减积分且添加一次失信次数;如果不超时，则添加积分
		if (($nowTime - $orderRes['trade_time']) >= $this->confirmPaidOvertime) {
			$decIntegral = $this->decPointArr[$userLevelInfo['level']];
			$extArr['operationType'] = 'dec';
			$extArr['isOverTime']    = 1;
			$extArr['status']        = 9;
			$extArr['scoreInfo']     = L('_XXJYQRDKCSJJF_');//线下交易确认打款超时减积分
			$extArr['remarkInfo']    = $orderRes['order_num'];
			$addPointRes = $publicFuntionObj->calUserIntegralAndLeavl($orderRes['buy_id'], $decIntegral, $extArr);
		}else{
			$incIntegral = $this->incPointArr[$userLevelInfo['level']];
			$extArr['operationType'] = 'inc';
			$extArr['isOverTime']    = 0;
			$extArr['status']        = 9;
			$extArr['scoreInfo']     = L('_XXJYQRDKWCSJJF_');
			$extArr['remarkInfo']    = $orderRes['order_num'];
			$addPointRes = $publicFuntionObj->calUserIntegralAndLeavl($orderRes['buy_id'], $incIntegral, $extArr);
		}

		if (empty($addPointRes)) {
			M()->rollback(); // 事务回退
			$this->errorArr['msg'] = L('_XTFMSHCS_');
			$this->ajaxReturn($this->errorArr);
		}

		$where['id'] = $orderRes['id'];
		//存储 接单到打款时间
		M('TradeTheLine')->where($where)->setField('shoukuan_time',time());
		$res = M('TradeTheLine')->where($where)->setField('status','2');

		if (empty($res)) {
			M()->rollback(); // 事务回退
			$this->errorArr['msg'] = L('_CZSBQCS_');
			$this->ajaxReturn($this->errorArr);
		}

		M()->commit();// 事务提交

		// 极光推送APP信息
		$this->pushAppInfo(1, $orderRes);

		return true;
	}

	/**
	 * 极光推送APP信息
	 * @author 2018-03-29T20:37:14+0800
	 * @param  [type] $type     [description]
	 * @param  [type] $orderRes [description]
	 * @return [type]           [description]
	 */
	public function pushAppInfo($type, $orderRes){

		if ($type == 1) {
			$where = ['uid' => $orderRes['sell_id']];
			$userId      = $orderRes['sell_id'];
		}else{
			$where = ['uid' => $orderRes['buy_id']];
			$userId      = $orderRes['buy_id'];
		}

		$userInfo    = M('User')->where($where)->find();
		$currWhere   = ['id' => $orderRes['currency_id']];
		$currInfo    = M('Currency')->where($currWhere)->find();
		
		$total       = big_digital_mul($orderRes['price'], $orderRes['num']);
		$orderNumStr = explode('-', $orderRes['order_num']);
		$orderNum    = ($type == 1) ? $orderNumStr[1] : $orderNumStr[0];
		$orderInfo   = [
			'orderNum'     => $orderNum,
			'currencyName' => $currInfo['currency_name'],
			'rate_total_money'        => $orderRes['rate_total_money'],
			'num'          => $orderRes['num'],
		];

		$extras['send_modle']        = 'P2P';
        $extras['new_order_penging'] = '1';

		$contentStr = SceneCode::getP2PTradeTemplate($type, '+'.$orderRes['om'], $orderInfo);
		$contentArr = explode('&&&', $contentStr);
		$title      = $contentArr[0];
		$content    = $contentArr[1];
        $res = push_msg_to_app_person($title, $content, $userId, $extras);
	}

	/**
	 * 撤销订单
	 * @author lirunqing 2017-11-01T19:59:38+0800
	 * @param  int $id 
	 * @return json
	 */
	public function recallOrder($id,$userId = 0){
        if($userId == 0) $userId = getUserId();
		$where['id']      = $id;
		$where['status']  = 0;
		$where['sell_id'] = $userId;

		$orderRes = M('TradeTheLine')->where($where)->find();

		if (empty($orderRes)) {
			$this->errorArr['msg'] = L('_NCXDWZD_');
			return $this->errorArr;
			// $this->ajaxReturn($this->errorArr);
		}

		if ($orderRes['status'] != 0) {
			$this->errorArr['msg'] = L('_NDDDBKCX_' );
			return $this->errorArr;
			// $this->ajaxReturn($this->errorArr);
		}

		// 撤销订单具体业务逻辑
		$recRes = $this->recallOrderProcess($where, $orderRes,$userId);

		if (empty($recRes) || $recRes['code'] != 200) {
			$this->errorArr['msg'] = $recRes['msg'];
			return $this->errorArr;
		}

		$this->errorArr['code'] = 200;
		return $this->errorArr;
	}

	/**
	 * 处理撤销订单业务
	 * @author lirunqing 2017-11-01T20:25:14+0800
	 * @param  array $where    
	 * @param  array $orderRes 
	 * @return bool|json
	 */
	private function recallOrderProcess($where, $orderRes,$userId=0){

		//开启事务
		M()->startTrans();
		if($userId == 0) $userId = getUserId();
		$UserMoneyApi     = new UserMoneyApi();
		$publicFuntionObj = new PublicFunctionController();

		$rate           = $this->getRateByOm($orderRes['om']);
		$totalMoney     = big_digital_mul($orderRes['num'], $orderRes['price'], 2);
		$rateTotalMoney = big_digital_mul($totalMoney, $rate, 2);
		// 台湾的不保留小数
		$rateTotalMoney = ($orderRes['om'] == '886') ? round($rateTotalMoney, 0).'.00' : round($rateTotalMoney, 2);

		//修改订单状态
		$saveData = array(
			'status'           => 5,
			'end_time'         => time(),
			'rate_total_money' => $rateTotalMoney,
		);
		$r[]     = M('TradeTheLine')->where($where)->save($saveData);
		//对应订单返钱
		$r[]     = $UserMoneyApi->setUserMoney($userId, $orderRes['currency_id'], $orderRes['num']);
		// 获取订单返钱之后余额
		$balance = $publicFuntionObj->getUserBalance($userId, $orderRes['currency_id']);
		$dataArr = array(
			'financeType' => 5,
			'content'     => L('_XXJYCXFK_'),//线下交易撤销返款
			'type'        => 1,
			'money'       => $orderRes['num'],
			'afterMoney'  => $balance,
			'remarkInfo'  => $orderRes['order_num']
		);
		$r[]     = $UserMoneyApi->AddFinanceLog($userId, $orderRes['currency_id'], $dataArr);

		// 返还卖出手续费
		if ($orderRes['sell_fee'] > 0) {
			$r[] = $UserMoneyApi->setUserMoney($userId, $orderRes['currency_id'], $orderRes['sell_fee']);
			// 获取返还卖出手续费之后余额
			$sellFeeBalance = $publicFuntionObj->getUserBalance($userId, $orderRes['currency_id']);
			$sellDataArr = array(
				'financeType' => 9,
				'content'     => L('_XXJYCXFHSXF_'),//线下交易撤销返还手续费
				'type'        => 1,
				'money'       => $orderRes['sell_fee'],
				'afterMoney'  => $sellFeeBalance,
				'remarkInfo'  => $orderRes['order_num']
			);
			$r[] = $UserMoneyApi->AddFinanceLog($userId, $orderRes['currency_id'], $sellDataArr);
		}

		//返回结果
		if(in_array(false, $r)){
			M()->rollback(); // 事务回退
			$this->errorArr['msg'] = L('_CXSBQSHCS_');
			return $this->errorArr;
			// $this->ajaxReturn($this->errorArr);
		}
		
		M()->commit();// 事务提交
		$this->errorArr['code'] = 200;
		return $this->errorArr;
		// return true;
	}

	/**
	 * 处理订单列表
	 * @author lirunqing 2017-11-01T14:43:24+0800
	 * @param array $data
	 * @return array
	 */
	public function processOrderList($data){

		if (empty($data)) {
			return array();
		}

		$strArr = array(
			'0'  => L('_MAII_'),
			'1'  => L('_MAI_'),
			'2'  => L('_XIANGQING_'),
		);
		$sellStatusArr = array(
			'0'  => L('_DDMR_'),
			'1'  => L('_DDHK_'),
			'2'  => L('_DDQR_'),
			'3'  => L('_DDWC_'),
			'4'  => L('_HTQR_'),
			'5'  => L('_YHCX_'),
			'6'  => L('_HTCX_'),
			'7'  => L('_XTZDCD_'),
			'8'  => L('_DCL_'),
		);
		$sellStr = array(
			'0' => L('_CHEXIAO_'),
			'1' => L('_ZWCZ_'),
			'2' => L('_SHOUKXX_'),
			'8' => L('_ZWCZ_'),
		);
		$buyStr = array(
			'1' => L('_DAKUAN_'),
			'2' => L('_HKXX_'),
			'8' => L('_ZWCZ_'),
		);
		$buyStatusArr = array(
			'1'  => L('_DDHK_'),
			'2'  => L('_DDMJQR_'),
			'3'  => L('_DDWC_'),
			'4'  => L('_HTQR_'),
			'5'  => L('_YHCX_'),
			'6'  => L('_HTCX_'),
			'7'  => L('_XTZDCD_'),
			'8'  => L('_DCL_'),
		);
		$userId = getUserId();
		$currencyModel = new CurrencyModel();
		$currencyList  = $currencyModel->getCurrencyList('*', true);
		$rateArr = $this->getConfigHUILV();

		foreach ($data as $key => $value) {
			$orderNumArr = explode('-', $value['order_num']);
            $orderNum = 0;
			if ($value['sell_id'] == $userId) {
				$orderNum             = $orderNumArr[1];
				$value['buy_str']    = $strArr[0];
				$value['status_str'] = $sellStatusArr[$value['status']];
				$value['type_str']   = $sellStr[$value['status']];
				$value['is_sell']     = ($value['status'] == 2) ? 2 : 0;
				$value['type_status'] = (in_array($value['status'], array(1, 8))) ? 1 : 0;
			}
			if ($value['buy_id'] == $userId) {
				$orderNum             = $orderNumArr[0];
				$value['buy_str']    = $strArr[1];
				$value['status_str'] = $buyStatusArr[$value['status']];
				$value['type_str']   = $buyStr[$value['status']];
				$value['is_sell']     = ($value['status'] == 1) ? 1 : 0;
				$value['type_status'] = (in_array($value['status'], array(2, 8))) ? 1 : 0;
			}

			$value['complete_flag'] = 0;
			if ($value['status'] == 3 || $value['status'] == 4) {
				$value['complete_flag'] = 1;
				$value['time']    = date("Y-m-d", $value['end_time']);
			}else{
				$value['time']    = date("Y-m-d", $value['add_time']);
			}

			$value['remaining_time']     = 0;
			$value['remaining_time_str'] = '';

			if ($value['status'] == 1) {
				$remainingTime = $this->confirmPaidOvertime-(time()-$value['trade_time']);
				$remainingTime = ($remainingTime > 0) ? $remainingTime : 0;
				$value['remaining_time_str'] = ($remainingTime <= 0) ? L('_CSWQRHK_') : '';
				$value['remaining_time'] = $remainingTime;
				$value['remaining_time_status'] = 1;
			}

			$value['abnormal_second'] = 0;
			if ($value['status'] == 2) {
				$remainingTime = $this->acceptPaidOvertime-(time()-$value['shoukuan_time']);
				$remainingTime = ($remainingTime > 0) ? $remainingTime : 0;
				$value['remaining_time_str'] = ($remainingTime <= 0) ? L('_CSWQRSK_') : '';
				$value['remaining_time'] = $remainingTime;
				$value['remaining_time_status'] = 2;
				// 收款异常按钮显示剩余时间
				$value['abnormal_second'] =  ($value['shoukuan_time']+$this->abnormalSecond) - time();
			}

			$value['status_flag']   = (in_array($value['status'], array(5,6,7))) ? 1 : 0;// 1表示订单撤销，0表示未撤销
			$value['details']       = $strArr[2];
			$value['order_num']     = $orderNum;
			$value['total_price']   = big_digital_mul($value['num'],$value['price'], 2);
			$value['coin_name']     = $currencyList[$value['currency_id']]['currency_name'];
			$value['add_time']      = !empty($value['add_time']) ? date("Y-m-d H:i:s", $value['add_time']) : '-';
			$value['trade_time']    = !empty($value['trade_time']) ? date("Y-m-d H:i:s", $value['trade_time']) : '-';
			$value['shoukuan_time'] = !empty($value['shoukuan_time']) ? date("Y-m-d H:i:s", $value['shoukuan_time']) : '-';

			// 如果未购买，则需要计算实时汇率的参考价格
			if ($value['status'] != 0) {
				$defaultOm = empty($this->notesArr[$value['om']]) ? '852' : $value['om'];// 非香港地区默认为香港
				$value['total_rate'] = $value['rate_total_money'].'('.$this->notesArr[$defaultOm].')';
			}else{

				// $value['total_rate'] = ($value['om'] == '886') ? big_digital_mul($value['total_price'], $rateArr[$value['om']], 0).'.00'.'('.$this->notesArr[$value['om']].')' : big_digital_mul($value['total_price'], $rateArr[$value['om']], 2) .'('.$this->notesArr[$value['om']].')';
				$defaultOm = empty($this->notesArr[$value['om']]) ? '852' : $value['om'];// 非香港地区默认为香港
				$value['total_rate'] =  big_digital_mul($value['total_price'], $rateArr[$defaultOm], 2) .'('.$this->notesArr[$defaultOm].')';
			}

			// if ($value['om'] == '886') {
			// 	$value['total_rate'] = big_digital_mul($value['total_price'], $rateArr[$value['om']], 0).'.00'.'('.$this->notesArr[$value['om']].')';
			// }else{
			// 	$value['total_rate']    = big_digital_mul($value['total_price'], $rateArr[$value['om']], 2) .'('.$this->notesArr[$value['om']].')';
			// }

			if (in_array($value['status'],array(5,6,7))) {
				$value['end_time'] = '-';
			}else{
				$value['end_time'] = !empty($value['end_time']) ? date("Y-m-d H:i:s", $value['end_time']) : '-';
			}

			unset($value['buy_pawn']);
			unset($value['sell_id']);
			unset($value['buy_id']);
			$data[$key] = $value;
		}

		return $data;
	}

	/**
	 * 获取业务相关对象
	 * @author lirunqing 2017-10-17T14:24:31+0800
	 */
	private function setObj(){
		$this->configModel     = new ConfigModel();
		$this->coinConfigModel = new CoinConfigModel();
	}

	/**
	 * 根据国家地区返回汇率
	 * @author lirunqing 2019-05-23T16:25:16+0800
	 * @param  [type] $om [description]
	 * @return [type]     [description]
	 */
	private function getRateByOm($om){
		$configList = $this->configModel->getConfigList();

		if ($om == '86') {
			return $configList['RMB_HUILV'];
		}elseif ($om == '886') {
			return $configList['TW_HUILV'];
		}else{
			return $configList['HK_HUILV'];
		}
	}

	/**
	 * 处理买入订单相关信息
	 * @author lirunqing 2017-11-09T16:25:35+0800
	 * @param  arrar $data 
	 * @return json|bool
	 */
	public function processBuyOrderInfo($data,$userId=0){
        if($userId == 0) $userId = getUserId();
		$orderId    = $data['id'];
		$currencyId = $data['currency_id'];
		$tableName  = 'TradeTheLine';
		$orderInfo  = M($tableName)->where(array('id' => $orderId))->find();

		// 判断如果已经有买入者，则不能买入
        if(!empty($orderInfo['buy_id']) or empty($orderInfo)){
			$this->errorArr['msg'] = L('_DQDDBZCCCZ_');
			return $this->errorArr;//不是新挂单
			// $this->ajaxReturn($this->errorArr);//不是新挂单
		}

        //判断订单状态
		if($orderInfo['status'] != 0){
			$this->errorArr['msg'] = L('_DQDDBZCCCZ_');
			$this->errorArr['code'] = 202;
			return $this->errorArr;//不是新挂单
			// $this->ajaxReturn($this->errorArr);//不是新挂单
		}

		// 如果当前订单超过24H则不能交易
		// $lastTime = $orderInfo['add_time'] + 86400;
		// if ($lastTime < time()) {
		// 	$this->errorArr['msg'] = L('_DQDDBZCCCZ_');
		// 	$this->errorArr['code'] = 203;
		// 	return $this->errorArr;//不是新挂单
		// 	// $this->ajaxReturn($this->errorArr);//不是新挂单
		// }

		$publicObj    = new PublicFunctionController();
		$isUserStatus = $publicObj->getUserStatusByUserId($orderInfo['sell_id']);
		// 如果挂单人存在交易风险，则不能购买订单
		if (empty($isUserStatus)) {
			$this->errorArr['msg'] = L('_GDDYJWCHCX_');
			$this->errorArr['code'] = 299;
			return $this->errorArr;
		}

		$fee = $this->getFee($currencyId, $orderInfo['num'], 2);// 获取买入手续费

        //开启事务
		M()->startTrans();
		
		//匹配成功 修改订单状态
		$where['id']            = $orderId;
		$saveData['status']     = 1;
		$saveData['buy_id']     = $userId;
		$saveData['trade_time'] = time();
		$saveData['buy_fee']    = $fee;

		// 计算当前地区汇率的总额
		$rate                         = $this->getRateByOm($orderInfo['om']);
		$totalMoney                   = big_digital_mul($orderInfo['num'],$orderInfo['price'], 2);
		$rateTotalMoney               = big_digital_mul($totalMoney, $rate, 2);
		$saveData['rate_total_money'] = $rateTotalMoney;
		// 台湾的不保留小数
		$saveData['rate_total_money'] = ($orderInfo['om'] == '886') ? round($saveData['rate_total_money'], 0).'.00' : round($saveData['rate_total_money'], 2);

		$upRes = M('TradeTheLine')->where($where)->save($saveData);

		//返回结果
		if (empty($upRes)){
			M()->rollback();
			$this->errorArr['msg'] = L('_GMSB_');
			return $this->errorArr;//不是新挂单
			// $this->ajaxReturn($this->errorArr);
		}

		M()->commit();

		$TradePushObj = new TradePush();
		// 推送到swoole task，购买后15分钟内未付款，则推送信息告知及时付款
		$TradePushObj->pushExec($orderId, 900000, "P2P");

		$this->errorArr['code'] = 200;
		return $this->errorArr;//不是新挂单
		// return true;
	}

	/**
	 * 处理提交卖出挂单时的数据库操作
	 * @author lirunqing 2017-10-13T11:47:46+0800
	 * @param  array $dataPost          出售订单信息
	 * @param  array $userCurrencyInfo 用户个人资金信息
	 * @return bool
	 */
	public function processSellOrderInfo($dataPost, $userCurrencyInfo){

		$num          = $dataPost['num'];
		$userId       = getUserId();
		$currencyId   = $dataPost['currency_id'];
		$userMoneyApi = new UserMoneyApi();

		//开启事务
		M()->startTrans();

		// 是否要收取手续费
		if (!empty($dataPost['sell_fee']) && $dataPost['sell_fee'] > 0) {
			$r = $this->feeAddFanLog($userId, $currencyId, $dataPost['sell_fee'], L('_XXJYKCSXF_'), $dataPost['order_num']);

			if (in_array(false,  $r)) {
				M()->rollback();
				$this->errorArr['msg'] = L('_SXFKCSBSHS_');
				$this->ajaxReturn($this->errorArr);
			}
		}
		
		// 添加交易金额财务日志
		$financeType           = 7;
		$dataArr['content']    = L('_XXJYGSRKC_'); // 内容 线下交易挂售人扣除
		$dataArr['type']       = 2;  // 类型(收入=1/支出=2)
		$dataArr['money']      = $num; // 金额
		$dataArr['remarkInfo'] = $dataPost['order_num']; // 金额
		$tradeRes              = $userMoneyApi->setUserMoney($userId, $currencyId, $num, 'num', 'dec'); // 扣除交易金额
		$finRes                = $this->operationUserCoin($currencyId, $financeType, $dataArr); // 扣除交易金额财务日志

		// 只要有一个添加失败，则回滚数据库
		if (in_array(false, array($tradeRes, $finRes))) {
			M()->rollback();
			$this->errorArr['msg'] = L('_MCSB_');
			$this->ajaxReturn($this->errorArr);
		}

		$orderRes = $this->generateOrder($dataPost);
		// 生成订单失败，回滚数据库
		if (empty($orderRes)) {
			M()->rollback();
			$this->errorArr['msg'] = L('_MCSB_');
			$this->ajaxReturn($this->errorArr);
		}

		// 成功则提交事务
		M()->commit();

		return $orderRes;
	}

	/**
	 * 卖出订单入队列，做并发处理。撤销和买入并发处理
	 * @author lirunqing 2018-01-15T11:45:04+0800
	 * @param  int $orderId 卖出订单表id
	 * @return null
	 */
	public function setSellRedsiList($orderId){
		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();
     	$redis->lpush(redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$orderId, $orderId);
	}

	/**
	 * 卖出订单入队列，做并发处理。出队列处理
	 * @author lirunqing 2018年1月22日14:59:46
	 * @param  int $orderId 卖出订单表id
	 * @return null
	 */
	public function popSellRedsiList($orderId){
		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();
		$redis->rpop(redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$orderId);
	}

	/**
	 * 生成一条订单信息
	 * @author lirunqing 2017-10-13T15:56:29+0800
	 * @param  array $dataPost 订单信息
	 * @return bool
	 */
    public function generateOrder($dataPost,$userId=0){
        if($userId < 1 ) $userId  = getUserId();
		$dataPost['sell_id']   = $userId;
		$dataPost['add_time']  = time();
		$dataPost['status']    = 0;

		// 生成挂单表的一条记录
		$addRes = M('TradeTheLine')->add($dataPost);

		return $addRes;
	}

	/**
	 * 线下交易扣除手续费
	 * @author lirunqing 2017-11-07T16:01:55+0800
	 * @param  int    $userId     用户id
	 * @param  int    $currencyId 币种id
	 * @param  float  $fee        手续费
	 * @param  string $content    场景描述,例如：线下交易卖出扣除手续费
	 * @param  string $orderNum   订单号
	 * @return array
	 */
	public function feeAddFanLog($userId, $currencyId, $fee, $content, $orderNum=''){
		$UserMoneyApi     = new UserMoneyApi();
		$publicFuntionObj = new PublicFunctionController();


		$r[] = $UserMoneyApi->setUserMoney($userId, $currencyId, $fee, 'num', 'dec');

		// 获取返还卖出手续费之后余额
		$sellFeeBalance = $publicFuntionObj->getUserBalance($userId, $currencyId);
		$sellDataArr = array(
			'financeType' => 8,
			'content'     => $content,
			'type'        => 2,
			'money'       => $fee,
			'afterMoney'  => $sellFeeBalance,
			'remarkInfo'  => $orderNum
		);
		$r[] = $UserMoneyApi->AddFinanceLog($userId, $currencyId, $sellDataArr);

		return $r;
	}

	/**
	 * 生成唯一订单号订单号
	 * @author lirunqing 2017-10-13T16:12:03+0800
	 * @return string
	 */
	public function genOrderId($userId=0){

		$orderId1 = date('YmdHis').$userId.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 10);//买家显示订单号
		$orderId2 = date('YmdHis').$userId.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 8, 14), 1))), 0, 10);//卖家显示订单号
		$orderNum = $orderId1.'-'.$orderId2;
		// $where['order_num'] = $orderNum;
		// $orderInfo          = M('TradeTheLine')->field('id')->where($where)->find();

		// if(!empty($orderInfo)){
		// 	$this->genOrderId($userId);
		// }

		return $orderNum;
	}

	/**
	 * 计算用户币种金额
	 * @author lirunqing 2017-10-13T12:08:03+0800
	 * @param  int     $currencyId  币种id
	 * @param  string  $financeType 日志类型
	 * @param  array   $dataArr     扩展数组
	 *         string  $dataArr['content'];内容 必传
	 *         float   $dataArr['money'];金额 必传
	 *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
	 * @return bool
	 */
	private function operationUserCoin($currencyId, $financeType, $dataArr=array()){

		$returnArr              = array();
		$userId                 = getUserId(); // 用户id
		$dataArr['financeType'] = $financeType;
		
		$publicFuntionObj      = new PublicFunctionController();
		$userMoneyApi          = new UserMoneyApi();
		$afterMoney            = $publicFuntionObj->getUserBalance($userId, $currencyId);// 获取用户余额
		$dataArr['afterMoney'] = $afterMoney;
		$moneyRes              = $userMoneyApi->AddFinanceLog($userId, $currencyId, $dataArr);// 记录用户财务日志

		if (empty($moneyRes)) {
			return false;
		}

		return true;
	}

	/**
	 * 检测自身资金是否足够及计算卖出费用
	 * @author lirunqing 2017-10-13T10:49:20+0800
	 * @param  int $currencyId 币种id
	 * @param  float $num       卖出数量
	 * @param  int   $type     获取手续费，1表示卖出手续费，2表示买入手续费
	 * @return array
	 */
	public function checkUserCurrencyIsAdequate($currencyId, $num, $type=1){

		$userId            = getUserId();
		$userCurrencyModel = new UserCurrencyModel();
		$userCurrencyInfo  = $userCurrencyModel->getUserCurrencyByUid($userId, $currencyId); // 个人资金信息

		if(empty($userCurrencyInfo)){
			$this->errorArr['msg'] = L('_NXZBZXXYWLXGLY_');
			$this->ajaxReturn($this->errorArr);
		}

		//自身账号有负数的情况
		if($userCurrencyInfo['num'] < 0 || $userCurrencyInfo['forzen_num'] < 0){
			$this->errorArr['msg'] = L('_NDZHYWMQBNJYLXGLY_');
			$this->ajaxReturn($this->errorArr);
		}

		if ($userCurrencyInfo['num'] <= 0) {
			$this->errorArr['msg'] = L('_NDZJBZ_');
			$this->ajaxReturn($this->errorArr);
		}

		// 获取卖出手续费
		$fee = $this->getFee($currencyId, $num, $type);

		// 计算卖出数量加上手续费是否足够
		if ( bcadd($num,$fee,4)  > $userCurrencyInfo['num']) {
			$this->errorArr['msg'] = L('_NDZJBZ_');
			$this->ajaxReturn($this->errorArr);
		}

		$userCurrencyInfo['fee'] = $fee;

        return $userCurrencyInfo;
	}

	/**
	 * 检测某币今日挂单总量是否超出限制
	 * @author lirunqing 2017-10-12T18:03:19+0800
	 * @param  int $currencyId 币种id
	 * @param  int $userId     用户id
	 * @param  float $num      出售数量
	 * @param  int $level      用户等级
	 * @return json|bool
	 */
	public function checkCoinSellSum($currencyId, $userId, $num, $level){

		$configWhere['vip_level']   = $level;
		$configWhere['currency_id'] = $currencyId;
		$levelConfig                = M('LevelConfig')->where($configWhere)->find();

		// 如果最大挂单总量没有设置，则不需要计算
		if ($levelConfig['day_max_sell_amount'] <= 0) {
			return true;
		}

		//统计挂单总量
		$where['currency_id'] = $currencyId; 
		$where['sell_id']     = $userId;
        $where['status']      = array('in', array(0,1,2,3,4,8));
		$where['add_time']    = array('egt',strtotime(date('Y-m-d'),time()));
		$count                = M('TradeTheLine')->where($where)->sum('num'); //统计当日挂单快捷币数量

		// 今日的数量加上现在的对比
        if( bcadd($count,$num,4) > $levelConfig['day_max_sell_amount']){
        	$this->errorArr['msg'] = L('_JRGDZLCCXZ_');
            $this->ajaxReturn($this->errorArr);//今日挂单总量超出限制
        } 
            
        return true;
	}

	/**
	 * 检测是否存在未完成的订单
	 * @author lirunqing 2017-10-12T17:29:37+0800
	 * @param  int    $userId 	   用户id
	 * @param  int    $level       用户等级
	 * @param  int    $currencyId  币种id
	 * @param  int    $type  1表示买入，2卖出
	 * @return json|bool
	 */
	public function checkOrderIsComplete($userId, $level, $currencyId, $type=1){

		$configWhere['vip_level']   = $level;
		$configWhere['currency_id'] = $currencyId;
		$levelConfig                = M('LevelConfig')->where($configWhere)->find();
		$limitSellCount             = $levelConfig['sell_order'];


		$buyWhere['buy_id'] = $userId;
		$buyWhere['status'] = array('in',array('0','1','2','8'));
		$buyCount           = M('TradeTheLine')->where($buyWhere)->count();
		$limitBuyCount      = $levelConfig['buy_order'];

		// 根据用户等级判断用户是否是否存在未完成的订单
		if ($buyCount >= $limitBuyCount  && $type == 1) {
			$this->errorArr['msg'] = L('_CZWWCDD_');
			$this->ajaxReturn($this->errorArr);
		}

		$where['sell_id'] = $userId;
		$where['status']  = array('in',array('0','1','2','8'));
		$sellCount        = M('TradeTheLine')->where($where)->count();

		// 根据用户等级判断用户是否是否存在未完成的订单
		if ($sellCount >= $limitSellCount && $type == 2) {

			$this->errorArr['msg'] = L('_CZWWCDD_');
			$this->ajaxReturn($this->errorArr);
		}

		return true;
	}
}