<?php
/**
 * 线下交易模块
 * @author lirunqing 2017-10-12 10:25:50
 */
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Model\CurrencyModel;
use Common\Api\RedisIndex;
use Common\Api\RedisCluster;
use Home\Logics\OffTradingLogicsController;
use Home\Logics\PublicFunctionController;
use Home\Tools\AjaxPage;
use Home\Model\BankListModel;
use Common\Api\redisKeyNameLibrary;
use Common\Api\Maintain;
use Common\Logic\CheckUser;
use Home\Logics\CheckAllCanUseParam;

class OffTradingController extends CommonController {

	private $currencyModel = null;
	private $p2pMaintain   = [];
    private $checkUserLogicObj = null;
	private $notesArr      = array(
				'86'  => 'CNY',
				// '886' => 'TWD',
				'852' => 'HKD',
			);

	/**
	 * 自动加载
	 * @author lirunqing 2017-10-12T11:11:23+0800
	 */
	public function _initialize(){
		parent::_initialize();
		$this->setObj();
	}

	/**
	 * 检测用户买入/卖出是否存在未完成的订单
	 * @author lirunqing 2017-11-21T14:38:03+0800
	 * @return json
	 */
	public function checkIsCompleteOrder(){

		$userId     = getUserId();
		$currencyId = trim(I('post.currencyId'));
		$type       = trim(I('post.type'));
		$userInfo   = M('User')->where(array('uid'=>$userId))->find(); // 获取用户个人信息

		$offTradingLogicsObj = new OffTradingLogicsController();
		$offTradingLogicsObj->checkOrderIsComplete($userId, $userInfo['level'], $currencyId, $type);

		$res = array(
			'code' => 200,
			'msg'  => L('_CHENGGONG_'),
			'data' => array()
		);

		$this->ajaxReturn($res);
	}

	/**
	 * 获取汇款银行卡信息
	 * @author lirunqing 2017-11-02T16:13:48+0800
	 * @return json
	 */
	public function getUserBankInfo(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		$id = I('post.order_id');
		if (empty($id)) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		$userId          = getUserId();
		$where['id']     = $id;
		$where['status'] = array('in', array(0,1,2));
		$orderRes        = M('TradeTheLine')->where($where)->find();

		if (empty($orderRes)) {
			$res['msg'] = L('_GDDBKCZ_');
			$this->ajaxReturn($res);
		}

		// if ($orderRes['buy_id'] != $userId && $orderRes['sell_id'] != $userId) {
		// 	$res['msg'] = '该订单不可操作2';
		// 	$this->ajaxReturn($res);
		// }

		// 获取购买人用户名
		$buyUserWhere = [
			'uid' => $orderRes['buy_id'],
			'status' => 1
		];
		$buyUserInfo = M('UserReal')->where($buyUserWhere)->find();

		// 获取用户汇款银行信息
		$bankWhere['id'] = $orderRes['bank_id'];
        $bankWhere['status'] = 1;
		$fields          = 'bank_real_name,bank_list_id,bank_num,bank_address';
		$bankInfo        = M('UserBank')->field($fields)->where($bankWhere)->find();
		
		if (empty($bankInfo)) {
			$res['msg'] = L('_GDDBKCZ_');
			$this->ajaxReturn($res);
		}

		$bankListModel         = new BankListModel();
		$bankList              = $bankListModel->getBankListName();
		$bankInfo['bank_name'] = $bankList[$bankInfo['bank_list_id']];
		$bankInfo['buy_name']  = $buyUserInfo['card_name'];
		$bankInfo['order_id']  = $id;
		$bankInfo['pay_time']  = !empty($orderRes['shoukuan_time']) ? date("Y-m-d H:i:s", $orderRes['shoukuan_time']) : '';
		$bankInfo['btn_str']   = ($orderRes['buy_id'] == $userId) ? L('_QRYDK_') : L('_QRYSK_');

		$res['msg']  = L('_CHENGGONG_');
		$res['code'] = 200;
		$res['data'] = array('bank_info' => $bankInfo);
		$this->ajaxReturn($res);
	}

	/**
	 * 未收到款项
	 * @author lirunqing 2018-04-09T15:34:16+0800
	 * @return json
	 */
	public function unGetMoney(){

		$orderId = I('post.order_id');

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		if (empty($orderId)) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		$tradepwd = I('post.tradePwd');
		$userId   = getUserId();
		
		// 交易密码不能为空
		if(empty($tradepwd)) {
			$res['msg'] = L('_JYMMBNWK_');
			$this->ajaxReturn($res);
		}
		$publicFunctionObj = new PublicFunctionController();
		//验证交易密码的正确性
		$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $tradepwd);

		if($tradePwdRes['code'] != 200){
			$res['msg'] = $tradePwdRes['msg'];
			$res['code'] =  233;
			// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
			$this->ajaxReturn($res);
		}

		
		$where['id'] = $orderId;
		$orderRes    = M('TradeTheLine')->where($where)->find();

		if (empty($orderRes)) {
			$res['msg'] = L('_GDDBKCZ_');
			$this->ajaxReturn($res);
		}

		if ($userId != $orderRes['sell_id']) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		if ($orderRes['status'] != 2) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		$offTradingLogicsObj = new OffTradingLogicsController();
		$abnormalSecond = ($orderRes['shoukuan_time']+$offTradingLogicsObj->abnormalSecond) - time();
		
		// 未到收款异常，发送请求
		if ($abnormalSecond > 0) {
			$res['msg'] = L('_FEIFAQQ_');
			$this->ajaxReturn($res);
		}

		$saveData = [
			'status'      => 8,
			'remark_info' => '收款异常',
		];

		$upRes = M('TradeTheLine')->where($where)->save($saveData);

		if (empty($upRes)) {
			$res['msg'] = L('_CZSB_');
			$this->ajaxReturn($res);
		}

		$res['msg']  = L('_CHENGGONG_');
		$res['code'] = 200;
		$this->ajaxReturn($res);
	}

	/**
	 * 处理用户打款/确认收款
	 * @author 2017-11-01T21:30:54+0800
	 * @return [type] [description]
	 */
	public function confirmOrderPaidOrOrderAccept(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);
		$id = I('post.order_id');
		if (empty($id)) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		$offTradingLogicsObj = new OffTradingLogicsController();
		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();

		$userId          = getUserId();
		$where['id']     = $id;
		$where['status'] = array('in', array(1,2));
		$orderRes        = M('TradeTheLine')->where($where)->find();

		if (empty($orderRes)) {
			$res['msg'] = L('_GDDBKCZ_');
			$this->ajaxReturn($res);
		}

		if ($userId == $orderRes['sell_id'] && $orderRes['status'] == 2) {
			$tradepwd = I('post.tradePwd');
			// 交易密码不能为空
			if(empty($tradepwd)) {
				$res['msg'] = L('_JYMMBNWK_');
				$this->ajaxReturn($res);
			}
			$publicFunctionObj = new PublicFunctionController();
			//验证交易密码的正确性
			$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $tradepwd);

			if($tradePwdRes['code'] != 200){
				$res['msg'] = $tradePwdRes['msg'];
				$res['code'] =  233;
				// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
				$this->ajaxReturn($res);
			}
		}

		if ($userId == $orderRes['buy_id'] && $orderRes['status'] == 1) {// 向卖家打款

			$isTrue = $redis->get(redisKeyNameLibrary::OFF_LINE_DAKUANG_ORDER.$id);
			if (!empty($isTrue)) {
				$res['msg'] = L('_QWCFCZ_');
				$this->ajaxReturn($res);
			}

			$offTradingLogicsObj->confirmOrderPaid($orderRes);
			$res['msg']  = L('_HKCG_');
			$redis->setex(redisKeyNameLibrary::OFF_LINE_DAKUANG_ORDER.$id, 10, true);

		}else if ($userId == $orderRes['sell_id'] && $orderRes['status'] == 2) {// 确认收款

			$isTrue = $redis->get(redisKeyNameLibrary::OFF_LINE_ACCEPT_ORDER.$id);
			if (!empty($isTrue)) {
				$res['msg'] = L('_QWCFCZ_');
				$this->ajaxReturn($res);
			}

			$offTradingLogicsObj->orderAccept($orderRes);
			$res['msg']  = L('_CZCG_');
			$redis->setex(redisKeyNameLibrary::OFF_LINE_ACCEPT_ORDER.$id, 10, true);

		}else{
			$res['msg'] = L('_GDDBKCZ_');
			$this->ajaxReturn($res);
		}

		$res['code'] = 200;
		$res['data'] = array('status_str' => L('_ZWCZ_'));
		$this->ajaxReturn($res);
	}

	/**
	 * 撤销订单
	 * @author lirunqing 2017-11-01T20:02:01+0800
	 * @return json
	 */
	public function revokeOrder(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);
		$id = I('post.order_id');
		if (empty($id)) {
			$res['msg'] = L('_SJYCQSHCS_');
			$this->ajaxReturn($res);
		}

		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();
		$isTrue = $redis->get(redisKeyNameLibrary::OFF_LINE_IS_REVOKE_ORDER.$id);
		if (!empty($isTrue)) {
			$res['msg'] = L('_QWCFCZ_');
			$this->ajaxReturn($res);
		}

        $userId           = getUserId();
        // 撤销订单和购买订单并发时，利用队列处理
        $checkBuykey = redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$id;
        $isBuy = $this->checkUserLogicObj->checkConcurrencyControl($checkBuykey,$userId);
        if (!$isBuy) {
            $res['msg'] = L( '_DQDDBZCCCZ_');
            $this->ajaxReturn($res);
        }
        $where['id']      = $id;
        $where['status']  = 0;
        $where['sell_id'] = $userId;
        $orderRes = M('TradeTheLine')->where($where)->find();
        if (empty($orderRes)) {
            $res['msg'] = L( '_GDDBGMCXSB_');
            $this->ajaxReturn($res);
        }
		$redis->setex(redisKeyNameLibrary::OFF_LINE_IS_REVOKE_ORDER.$id, 10, true);

		$offTradingLogicsObj = new OffTradingLogicsController();
		$recRes = $offTradingLogicsObj->recallOrder($id);// 撤销订单

		// 撤销失败返还撤销资格
		if (empty($recRes) || $recRes['code'] != 200) {
			$res['code'] = $recRes['code'];
			$res['msg']  = $recRes['msg'];
			$this->ajaxReturn($res);
		}
		$res['code'] = 200;
		$res['msg']  = L('_CZCG_');
		$this->ajaxReturn($res);
	}

	/**
	 * 获取待买入的订单列表
	 * @author lirunqing 2017-11-08T10:32:22+0800
	 * @return json
	 */
	public function getPendingPurchaseOrderList(){

		$data       = I('get.');
		$areaId     = $data['areaId'];
		$currencyId = $data['currencyId'];
		$bankId     = $data['bankId'];
		$num1       = $data['num1'];
		$num2       = $data['num2'];
		$price1     = $data['price1'];
		$price2     = $data['price2'];
		$omArr  = array(
			3 => '86',// 大陆
			// 2 => '886',// 台湾
			1 => '852',// 香港
		);

		$offTradingLogicsObj = new OffTradingLogicsController();
		// 检测网站是否处于维护状态
		$isMaintain = $offTradingLogicsObj->checkWebMaintain(1);
		if ($isMaintain['code'] != 200) {
			$maintainData = array(
				'code' => 200,
				'data' => [],
				'msg'  => ''
			);
			
			$this->ajaxReturn($maintainData);
		}

		$userId              = getUserId();
		$userOm              = $this->userInfo['om'];
		$userOm              = str_replace('+', '', $userOm);
		$userOm              = (in_array($userOm, $omArr)) ? $userOm : '852';// 如果不是大陆，台湾，香港注册用户，则默认置顶香港订单
		
		$publicObj           = new PublicFunctionController();
		$riskUserArr         = $publicObj->getRiskUserList();// 获取存在风险的用户
		$repeaetIdArr        = $offTradingLogicsObj->getOfftradingRepeatBuyersByUserid();// 根据用户id获取购买2次及以上的订单
		$repeaetIdArr[]      = $userId; // 剔除当前用户
		$where['a.om']       = $userOm;

		

		// 币种筛选
		if (!empty($currencyId)) {
			$where['a.currency_id'] = $currencyId;
		}

		// 地区筛选
		if (!empty($areaId)) {
			$userOm        = $omArr[$areaId];
			$where['a.om'] = $userOm;
		}

		// 银行筛选
		if (!empty($bankId)) {
			$where['c.bank_list_id'] = $bankId;
		}

		// 数量筛选
		if (!empty($num1) && empty($num2)) {
			$where['a.num'] = array('egt', $num1);
		}

		// 数量筛选
		if (!empty($num2) && empty($num1)) {
			$where['a.num'] = array('elt', $num2);
		}

		// 数量筛选
		if (!empty($num2) && !empty($num1)) {
			$where['a.num'] = array(array('egt', $num1),array('elt', $num2), 'AND');
		}

		// 价格筛选
		if (!empty($price1) && empty($price2)) {
			$where['a.price'] = array('egt', $price1);
		}

		// 价格筛选
		if (empty($price1) && !empty($price2)) {
			$where['a.price'] = array('elt', $price2);
		}

		// 价格筛选
		if (!empty($price1) && !empty($price2)) {
			$where['a.price'] = array(array('egt', $price1),array('elt', $price2), 'AND');
		}

		$where['a.status']  = 0;
		// $where['a.add_time'] = array('EGT', strtotime('-1 days'));
		$where['a.sell_id'] = array('not in', $repeaetIdArr);
		$join1              = 'LEFT JOIN __USER__ as b on a.sell_id = b.uid';
		$join2              = 'LEFT JOIN __USER_BANK__ AS c ON a.bank_id = c.id';

		$count     = M('TradeTheLine')->alias('a')->join($join1)->join($join2)->where($where)->count();
		
		$AjaxPage  = new AjaxPage($count, 8, 'getPendingPurchaseOrderList');// 第三个参数需要填写前端js文件的function名称
		$limit     = $AjaxPage->firstRow.','.$AjaxPage->listRows;

		// 用户注册所在地区订单置顶，然后再显示其他用户
		// $orderBy   = 'IF(a.om="'.$userOm.'", 1, 0) desc,a.price asc,b.credit_level desc,a.add_time desc';
		$orderBy   = 'a.price asc,b.credit_level desc,a.add_time desc';
		$field     = 'a.id,a.sell_id,a.price,a.bank_id,a.num,a.om,a.currency_id,c.bank_list_id,b.level,b.credit_level';
		$orderList = M('TradeTheLine')->alias('a')->field($field)->join($join1)->join($join2)->where($where)->order($orderBy)->limit($limit)->select();
		$show      = $AjaxPage->show();
		
		$areaArr  = array_flip($omArr);
		if (!empty($orderList)) {
			$bankListModel = new BankListModel();
			$bankList      = $bankListModel->getBankListName();
			$currencyList  = $this->currencyModel->getCurrencyList('*', true);
			$rateArr = $offTradingLogicsObj->getConfigHUILV();

			foreach ($orderList as $key => $value) {

				if (!empty($riskUserArr) && in_array($value['sell_id'], $riskUserArr)) {
					unset($orderList[$key]);
					continue;
				}

				$value['bank_name']  = $bankList[$value['bank_list_id']];
				$value['coin_name']  = $currencyList[$value['currency_id']]['currency_name'];
				$value['area_id']    = !empty($areaArr[$value['om']]) ? $areaArr[$value['om']] : 1;
				$totalPrice          = big_digital_mul($value['num'], $value['price'], 2);

				// 台湾交易区暂时不开放
				// if ($value['om'] == '886') {
				// 	$value['total_rate'] = big_digital_mul($totalPrice, $rateArr[$value['om']], 0)  .'('.$this->notesArr[$value['om']].')';
				// }else{
				$om = empty($this->notesArr[$value['om']]) ? '852' : $value['om'];
					$value['total_rate'] = big_digital_mul($totalPrice, $rateArr[$om], 2)  .'('.$this->notesArr[$om].')';
				// }
				unset($value['sell_id']);
				$orderList[$key]     = $value;
			}
		}

		
		$data = array(
			'order_list' => $orderList,
			'page_show'  => $show
		);

		$returnData = array(
			'code' => 200,
			'data' => $data,
			'msg'  => ''
		);
		
		$this->ajaxReturn($returnData);
	}

	/**
	 * 获取用户订单列表
	 * @author 2017-11-01T12:35:33+0800
	 * @return [type] [description]
	 */
	public function getOrderList(){

		$type   = I('get.type');
		$type   = !empty($type) ? $type : 1;
		$userId = getUserId();

		$typeArr = array(
			'1' => 'buy_id|sell_id',
			'2' => 'buy_id',
			'3' => 'sell_id',
			'4' => 'buy_id',
			'5' => 'sell_id',
			'6' => 'buy_id|sell_id',
		);

		$statusArr = array(
			'1' => array('in', array(0, 1, 2, 8)),
			'2' => array('in', array(1, 2, 8)),
			'3' => array('in', array(0, 1, 2, 8)),
			'4' => array('in', array(3, 4)),
			'5' => array('in', array(3, 4)),
			'6' => array('in', array(5,6,7)),
		);

		if (empty($userId)) {
			$returnData = array(
				'data'      => [],
				'page_show' => ''
			);
			$this->ajaxReturn($returnData);
		}

		$offTradingLogicsObj = new OffTradingLogicsController();
		// 检测网站是否维护
		$isMaintain = $offTradingLogicsObj->checkWebMaintain(3);
		if ($isMaintain['code'] != 200) {
			$maintainData = array(
				'data'      => [],
				'page_show' => ''
			);
			$this->ajaxReturn($maintainData);
		}

		$filedStr         = $typeArr[$type];
		$valStr           = $statusArr[$type];
		$where[$filedStr] = $userId;
		$where['status']  = $valStr;
		
		$total     = M('TradeTheLine')->where($where)->count();
		$AjaxPage  = new AjaxPage($total, 5, 'getOrderListForPage');// 第三个参数需要填写前端js文件的function名称
		$limit     = $AjaxPage->firstRow.','.$AjaxPage->listRows;
		$orderBy   = ($type == 1) ? 'status desc,id desc,trade_time desc' : 'trade_time desc';
		$res       = M('TradeTheLine')->where($where)->limit($limit)->order($orderBy)->select();
		
		$show      = $AjaxPage->show();

		
		$listRes             = $offTradingLogicsObj->processOrderList($res);

		$returnData = array(
			'data'      => $listRes,
			'page_show' => $show
		);
		$this->ajaxReturn($returnData);
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
	 * 处理购买数据
	 * @author lirunqing 2017-11-09T16:00:04+0800
	 * @return json
	 */
	public function buying(){
		$data = I('post.');
		$currencyId    = $data['currencyId'];		     //卖出币种id
		$bankId        = (int)$data['bankId'];		 //银行信息
		$tradepwd      = $data['tradePwd'];			     //交易密码
		$orderId       = $data['orderId']; 		     // 图片验证码
		$picCode       = $data['verifyCode']; 		     // 图片验证码
		$userId        = getUserId();

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		// 验证购买参数
		$this->checkBuyParams($data);

		$offTradingLogicsObj = new OffTradingLogicsController();
		// 检测网站是否处于维护状态
		$isMaintain = $offTradingLogicsObj->checkWebMaintain(2);
		if ($isMaintain['code'] != 200) {
			$maintainData = array(
				'code' => $isMaintain['code'],
				'data' => [],
				'msg'  => $isMaintain['msg']
			);
			
			$this->ajaxReturn($maintainData);
		}

		// 检测交易密码24h内是否修改过
		$this->checkTradePwdIsChange();

		// 未实名认证
		$isReal = checkUserReal($userId);
		if ($isReal <= 0) {
			$res['msg'] = L('_WJXSMRZ_');
			$this->ajaxReturn($res);
		}

		// 检测护照是否过期
		$checkAllCanUseParam = new CheckAllCanUseParam();
		$checkRes = $checkAllCanUseParam->checkUserRealIsExpire($userId);
		if ($checkRes['code'] != 200) {
			$res['msg']  = $checkRes['msg'];
			$res['code'] = $checkRes['code'];
			$this->ajaxReturn($res);
		}

        // 拿到购买资格
        $checkBuykey = redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$orderId;
        $ret = $this->checkUserLogicObj->checkConcurrencyControl($checkBuykey,$userId);
        if(!$ret){
            $res['msg'] = L('_DQDDBZCCCZ_');
            $res['code'] = 209;
            $this->ajaxReturn($res);
        }
		$publicFunctionObj   = new PublicFunctionController();
		$userInfo            = M('User')->where(array('uid'=>$userId))->find(); // 获取用户个人信息

		// 检测用户是否失信被锁定
		$overRes = $publicFunctionObj->checkOvertime($userInfo['overtime_num'], $userInfo['overtime_time']); 
		if ($overRes['code'] != 200) {
			$res['msg'] = $overRes['msg'];
			$this->ajaxReturn($res);
		}

		$offTradingLogicsObj->checkOrderIsComplete($userId, $userInfo['level'], $currencyId, 1); // 检测是否存在未完成的订单
		// $orderRes = M('TradeTheLine')->field('num')->where(array('id' => $orderId))->find();
		// $offTradingLogicsObj->checkUserCurrencyIsAdequate($currencyId, 0, 2); // 检测自身资金是否足够
		// $fee = $offTradingLogicsObj->getFee($currencyId, $orderRes['num'], 2);// 获取买入手续费

		// $buyData['buy_fee']     = $fee;
		$buyData['currency_id'] = $currencyId;
		$buyData['bank_id']     = $bankId;
		$buyData['id']          = $orderId;

		$buyRes = $offTradingLogicsObj->processBuyOrderInfo($buyData);

		// 购买失败，返还购买资格
		if (empty($buyRes) || $buyRes['code'] != 200) {
			$res['msg'] = $buyRes['msg'];
			$res['code'] = $buyRes['code'];
			$this->ajaxReturn($res);
		}
		$res['code'] = 200;
		$res['msg'] = L('_MRCG_');
		$this->ajaxReturn($res);
	}

	/**
	 * 检测购买订单验证参数
	 * @author lirunqing 2017-11-09T15:59:04+0800
	 * @param  array $data 
	 * @return json
	 */
	private function checkBuyParams($data){
		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		$currencyId    = $data['currencyId'];		     //卖出币种id
		$bankId        = (int)$data['bankId'];		 //银行信息
		$tradepwd      = $data['tradePwd'];			     //交易密码
		$orderId       = $data['orderId']; 		     // 图片验证码
		$picCode       = $data['verifyCode']; 		     // 图片验证码

		if (empty($orderId)) {
			$res['msg'] = L('_NXZDDDBCZ_');
			$this->ajaxReturn($res);
		}

		$isExits = M('TradeTheLine')->field('id')->where(array('id' => $orderId))->find();
		if (empty($isExits)) {
			$res['msg'] = L('_NXZDDDBCZ_');
			$this->ajaxReturn($res);
		}

		// 币种信息检测
		$currencyRes = $this->currencyModel->getCurrencyByCurrencyid($currencyId);
		if(empty($currencyRes) || $currencyRes['status'] == 0){
			$res['msg'] = L('_NXZDBZBCZQQR_');
			$this->ajaxReturn($res);
		}

		// 银行信息不能为空
		if(empty($bankId)) {
			$res['msg'] = L('_HKYHWK_');
			$this->ajaxReturn($res);
		}

		$bankWhere['id'] = $bankId;
		$bankWhere['status'] = 1;
		$bankInfo        = M('UserBank')->where($bankWhere)->find();
		if(empty($bankInfo)) {
			$res['msg'] = L('_HKYHXXCW_');
			$this->ajaxReturn($res);
		}

		// 交易密码不能为空
		if(empty($tradepwd)) {
			$res['msg'] = L('_JYMMBNWK_');
			$this->ajaxReturn($res);
		}
		$publicFunctionObj = new PublicFunctionController();
		//验证交易密码的正确性
		$userId = getUserId();
		$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $tradepwd);

		if($tradePwdRes['code'] != 200){
			$res['msg'] = $tradePwdRes['msg'];
			$res['code'] =  233;
			// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 处理卖出订单数据
	 * @author lirunqing 2017-10-12T10:31:16+0800
	 * @return json
	 */
	public function selling(){

		$data          = I("POST.");

		$areaArr = array(
			3 => '86',
			// 2 => '886',
			1 => '852',
		);// 获取默认地区

		$om            = $areaArr[$data['userArea']];    //地区id
		$currencyId    = $data['currencyId'];		     //卖出币种id
		$price         = $data['coinPrice'];             //卖出单价
		$num           = $data['coinNum'];			     //卖出数量
		$bankId        = (int)$data['userBankId'];		 //银行信息
		$tradepwd      = $data['transpwd'];			     //交易密码
		$piccode       = $data['verifyCode']; 		     // 图片验证码
		$price         = getDecimal($price, 2);	             // 取小数2位
		$num           = getDecimal($num, 4);		         // 取小数4位
		$price         = abs($price);                    // 去正数
		$num           = abs($num);						 // 去正数
		$data['om']    = $om;
		$data['price'] = $price;
		$data['num']   = $num;
		$userId        = getUserId();

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array(),
		);

		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();
		$isSell = $redis->get(redisKeyNameLibrary::PC_OFF_LINE_TRADE.$userId);

		if (!empty($isSell)) {
			$res['msg']=L('_QWCFCZ_');
	 		$this->ajaxReturn($res);
		}

		// 验证订单参数信息
		$this->checkSellParams($data);

		$offTradingLogicsObj = new OffTradingLogicsController();
		// 检测网站是否维护
		$isMaintain = $offTradingLogicsObj->checkWebMaintain(2);
		if ($isMaintain['code'] != 200) {
			$maintainData = array(
				'data' => [],
				'code' => $isMaintain['code'],
				'msg'  => $isMaintain['msg'],
			);
			$this->ajaxReturn($maintainData);
		}

		// 检测交易密码24h内是否修改过
		$this->checkTradePwdIsChange();

		// 未实名认证
		if (!checkUserReal($userId)) {
			$res['msg'] = L('_WJXSMRZ_');
			$this->ajaxReturn($res);
		}

		// 检测护照是否过期
		$checkAllCanUseParam = new CheckAllCanUseParam();
		$checkRes = $checkAllCanUseParam->checkUserRealIsExpire($userId);
		if ($checkRes['code'] != 200) {
			$res['msg'] = $checkRes['msg'];
			$res['code'] = $checkRes['code'];
			$this->ajaxReturn($res);
		}

		$publicFunctionObj = new PublicFunctionController();
		$userInfo            = M('User')->where(array('uid'=>$userId))->find(); // 获取用户个人信息

		// 检测用户是否失信被锁定
		$overRes = $publicFunctionObj->checkOvertime($userInfo['overtime_num'], $userInfo['overtime_time']); 
		if ($overRes['code'] != 200) {
			$res['msg'] = $overRes['msg'];
			$this->ajaxReturn($res);
		}

		$totalPrice = big_digital_mul($price, $num, 2);
		$isTotalPrice = $offTradingLogicsObj->checkTotalPrice($currencyId, $totalPrice);
		if ($isTotalPrice['code'] != 200) {
			$res['msg'] = $isTotalPrice['msg'];  //总金额小于规定金额
			$this->ajaxReturn($res);
		}

		$offTradingLogicsObj->checkOrderIsComplete($userId, $userInfo['level'], $currencyId, 2); // 检测是否存在未完成的订单
		$offTradingLogicsObj->checkCoinSellSum($currencyId, $userId, $num, $userInfo['level']); // 检测某币当日挂单总量是否超出限制
		$userCurrencyInfo = $offTradingLogicsObj->checkUserCurrencyIsAdequate($currencyId, $num, 1); // 检测自身资金是否足够
                
		//写入数据库
		$dataPost['om']          = $om;
		$dataPost['currency_id'] = $currencyId;
		$dataPost['price']       = $price;
		$dataPost['num']         = $num;
		$dataPost['bank_id']     = $bankId;
		$dataPost['tradepwd']    = $tradepwd;
		$dataPost['sell_fee']    = $userCurrencyInfo['fee']; // 卖出手续费
		$dataPost['order_num'] 	 = $offTradingLogicsObj->genOrderId($userId);
		$id = $offTradingLogicsObj->processSellOrderInfo($dataPost, $userCurrencyInfo); // 处理提交卖出挂单时的数据库操作

		$redis->setex(redisKeyNameLibrary::PC_OFF_LINE_TRADE.$userId, 3, 1);

		$res['code'] = 200;
		$res['msg']  = L('_MCCG_');
		$res['data'] = array('orderId' => $id);
		$this->ajaxReturn($res);
	}

	/**
	 * 卖出订单信息检测
	 * @author lirunqing 2017-10-12T16:09:19+0800
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	private function checkSellParams($data=array()){

		$om            = $data['om'];                    //地区id
		$currencyId    = $data['currencyId'];		     //卖出币种id
		$price         = $data['coinPrice'];             //卖出单价
		$num           = $data['coinNum'];			     //卖出数量
		$bankId        = $data['userBankId'];		     //银行信息
		$tradepwd      = $data['transpwd'];			     //交易密码
		$picCode       = $data['verifyCode']; 		     // 图片验证码

		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		if (empty($om)) {
			$res['msg'] = L('_QXZDQ_');
			$this->ajaxReturn($res);
		}
		// 币种信息检测
		if(empty($currencyId)){
			$res['msg'] = L('_QXZBZ_');
			$this->ajaxReturn($res);
		}
		// 币种信息检测
		$currencyRes = $this->currencyModel->getCurrencyByCurrencyid($currencyId);
		if(empty($currencyRes) || $currencyRes['status'] == 0){
			$res['msg'] = L('_NXZDBZBCZQQR_');
			$this->ajaxReturn($res);
		}
		// 价格为空
		if (empty($price)) {
			$res['msg'] = L('_QSRYMCDDJ_');
			$this->ajaxReturn($res);
		}
		// 价格非数字
		if(!is_numeric($price)){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}
		// 价格应该大于0
		if($price <= 0){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}
		if(!regex($price,'double')){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}
		// 数量不能为空
		if(empty($num)) {
			$res['msg'] = L('_QSRYMCDSL_');
			$this->ajaxReturn($res);
		}
		// 数量应是数字
		if(!is_numeric($num)){
			$res['msg'] = L('_QSRZQDCSSL_');
			$this->ajaxReturn($res);
		}
		// 数量应大于0
		if($num <= 0){
			$res['msg'] = L('_QSRZQDCSSL_');
			$this->ajaxReturn($res);
		}

		$configWhere['currency_id'] = $currencyId;
		$coinConfig = M('CoinConfig')->where($configWhere)->find();

		// 每单最大数量限制
		if ($num > $coinConfig['maximum_num']) {
			$res['msg'] = L('_MDZD_').$coinConfig['maximum_num'];
			$this->ajaxReturn($res);
		}

		// 每单最小数量限制
		if ($num < $coinConfig['minimum_num']) {
			$res['msg'] = L('_MDZX_').$coinConfig['minimum_num'];
			$this->ajaxReturn($res);
		}

		// 银行信息不能为空
		if(empty($bankId)) {
			$res['msg'] = L('_HKYHWK_');
			$this->ajaxReturn($res);
		}

		$userId           = getUserId();
		$bankWhere['id']  = $bankId;
		$bankWhere['status']  = 1;
		$bankWhere['uid'] = $userId;
		$bankInfo = M('UserBank')->where($bankWhere)->find();
		if(empty($bankInfo)) {
			$res['msg'] = L('_HKYHXXCW_');
			$this->ajaxReturn($res);
		}

		// 交易密码不能为空
		if(empty($tradepwd)) {
			$res['msg'] = L('_JYMMBNWK_');
			$this->ajaxReturn($res);
		}

		$publicFunctionObj = new PublicFunctionController();

		//验证交易密码的正确性
		$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $tradepwd);
		if($tradePwdRes['code'] != 200){
			$res['msg'] = $tradePwdRes['msg'];
			// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 获取业务所需对象
	 * @author lirunqing 2017-10-12T11:10:48+0800
	 * @return null
	 */
	protected function setObj(){
		$this->currencyModel = new CurrencyModel();
        $this->checkUserLogicObj = new CheckUser();
	}
}