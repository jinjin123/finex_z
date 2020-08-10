<?php
/**
 * @desc 检测用户个人信息
 * @author lirunqing 2017-12-4 10:05:57
 */
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Logics\PublicFunctionController;
use Common\Api\RedisCluster;
use Home\Logics\CtoCTransactionLogicsController;
use Home\Logics\CheckAllCanUseParam;
use Home\Tools\SceneCode;
use Common\Api\redisKeyNameLibrary;
use Common\Api\Maintain;

class CtoCTransactionController extends CommonController {

	public  $jsonToArr;
	private $cToCTransLogicsObj = null;
	private $redis = null;
    public $msgArr = array(
        'code' => 200,
        'msg'  => '',
        'data' => array()
    );

    private $notesArr = array(
		'86'  => 'CNY',
		'886' => 'TWD',
		'852' => 'HKD',
	);
    
	public function __construct()
	{
		parent::__construct();
        $this->cToCTransLogicsObj= new CtoCTransactionLogicsController();
        $this->redis=RedisCluster::getInstance();
        
        //赋值
        if(IS_AJAX){
            $this->jsonToArr=$_REQUEST;
            foreach ($this->jsonToArr as  $k=> $v) {
                $this->jsonToArr[$k]=strip_tags(trim($v));
            }
        }
        
        
	}
	public function index(){

        $omArr = ['+86'=>1,'+852'=>2,'+886'=>3];
        $userId = getUserId();
        $userData = M('User')->where(['uid'=>$userId])->field('om')->find();
        $om = $omArr[$userData['om']];
        if(!$om){
            $om = 2;
        }

        $isMaintain = $this->getWebMaiantainInfo(Maintain::C2C);
		$this->assign('isMaintain', $isMaintain);

        // 获取汇率
        $rateArr = $this->cToCTransLogicsObj->getConfigHUILV();
        $userReal      = M('UserReal')->where(['uid'=> getUserId()])->find();
        $this->assign('userReal',$userReal);
        $this->assign('rateArr', $rateArr);
        $this->assign('om',$om);
        $this->assign('is_c2c', 1);
        $this->assign('is_c2c_tour', $this->userInfo['is_c2c_tour']);
		$this->display();
	}

	/**
	 * 根据币种获取保证金/手续费
	 * @author lirunqing 2018-03-06T11:17:39+0800
	 * @return string json
	 */
	public function getCurrencyFee(){
        $currencyId = I('currency_id');
        $returnData = $this->cToCTransLogicsObj->getCurrencyFee($currencyId);
		if(!empty($returnData['data']['sell_fee'])){
            $returnData['data']['sell_fee'] = $returnData['data']['sell_fee']*100;
            $returnData['data']['buy_fee'] = $returnData['data']['buy_fee']*100;
        }
		$this->ajaxReturn($returnData);
	}

	/**
	 * @method 统一前后台计算总金额
	 * @author liruniqng 2018-02-27T20:48:28+0800
	 * @return string json
	 */
	public function calTotalPrice(){
		
		$data = I('POST.');
		$totalPrice = big_digital_mul($data['num'], $data['price'], 2);
		$this->ajaxReturn($totalPrice);
	}

	/**
	 * @method 挂单业务处理
	 * @author lirunqing 2018-02-27T20:46:23+0800
	 * @return string json
	 */
	public function subTrade(){

		$data = I('POST.');
		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array(),
		);

		$userId            = getUserId();
		$data['uid']       = $userId;
		$data['leave_num'] = $data['num'];
		$data['money']     = big_digital_mul($data['price'], $data['num'], 2);

		// 防止重复提交订单
		$isRepeat = $this->redis->get(redisKeyNameLibrary::PC_C2C_TRADE_BUYANDSELL.$userId);
		if (!empty($isRepeat)) {
			$res['msg']  = L('_QWCFCZ_');
			$res['code'] = 501;
			$this->ajaxReturn($res);
		}

		// 验证交易参数
		$this->checkParams($data);
		// 检测网站是否维护中
		$isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(2);
		if ($isMaintain['code'] != 200) {
			$res['msg']  = $isMaintain['msg'];
			$res['code'] = $isMaintain['code'];
			$this->ajaxReturn($res);
		}

		// 检测用户是否存在未完成的订单
		$isExistOrder = $this->cToCTransLogicsObj->checkUserOrderNum($userId, $data['currency_type'], $data['type']);
		if ($isExistOrder['code'] != 200) {
			$res['msg']  = $isExistOrder['msg'];
			$res['code'] = $isExistOrder['code'];
			$this->ajaxReturn($res);
		}

		// 检测用户币种余额是否足够交易
		$currencyIsAd = $this->cToCTransLogicsObj->checkUserCurrencyIsAdequate($userId, $data['currency_type'], $data['num'], $data['type']);
		if (empty($currencyIsAd) || $currencyIsAd['code'] != 200) {
			$res['msg']  = L('_NDZJBZ_');
			$res['code'] = $currencyIsAd['code'];
			$this->ajaxReturn($res);
		}

		// 检测用户交易总金额是否达到最小配置
		$isMinMoney = $this->cToCTransLogicsObj->checkIsMinMoney($data['currency_type'], $data['money']);
		if (empty($isMinMoney) ||$isMinMoney['code'] != 200) {
			$res['code'] = $isMinMoney['code'];
			$res['msg'] = L('_ZJEXYGDJE_')."$".$isMinMoney['data']['min_trade_money'];
			$this->ajaxReturn($res);
		}

		//开启事务
		M()->startTrans();
		
		// 挂单卖出收取手续费
		if ($data['type'] == 2) {
			$data['fee']       = $this->cToCTransLogicsObj->getFee($data['currency_type'], $data['num'], $data['type']);
			$data['leave_fee'] = $data['fee'];
			$msg = L('_MCJYDFBCG_');
		}

		// 挂单买入保证金
		$bondNum = 0;
		if ($data['type'] == 1) {
			$bondNum           = $isMinMoney['data']['bond_num'];
			$data['bond_num']  = $bondNum;
			$msg = L('_MRJYDFBCG_');
		}
		
		$data['order_num'] = $this->cToCTransLogicsObj->genOrderId($userId);
		// 扣除币种数量及添加财务日志
		$logRes = $this->cToCTransLogicsObj->processFinanceLogAndCurrencyNum($data, $bondNum);
		if (empty($logRes) || $logRes['code'] != 200) {
			M()->rollback(); // 事务回退
			$res['code'] = $logRes['code'];
			$res['msg']  = $logRes['msg'];
			$this->ajaxReturn($res);
		}
		
		$addRes = $this->cToCTransLogicsObj->generateOrder($data);
		if (empty($addRes) || $addRes['code'] != 200) {
			M()->rollback(); // 事务回退
			$res['code'] = $addRes['code'];
			$res['msg']  = $addRes['msg'];
			$this->ajaxReturn($res);
		}

		M()->commit(); // 事务提交
        $this->redis->setex(redisKeyNameLibrary::PC_C2C_TRADE_BUYANDSELL.$userId, 3, 1);
		$res['code'] = 200;
		$res['msg']  = $msg;
		$res['data'] = array('order_id' => $addRes['data']['order_id']);
		$this->ajaxReturn($res);
	}

	/**
	 * @method 检测用户是否可以挂单
	 * @author lirunqing  2018-03-07T10:29:12
	 * @return string json
	 */
	public function checkIsLimitOrder(){
		// 是否检测绑定银行卡标记 1表示不检测，null或者其他非1值表示需要检测银行卡
		$type = I('post.type');
		$om   = I('post.om');
		$res  = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		$userId  = getUserId();
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

		// 检测未绑定银行卡 
		if (!checkUserBindBank($userId) && $type == 1) {
			$res['msg'] = L('_ZHUYI_').L('_QXJX_').'<a href="/PersonalCenter/showBankCardBind" style="color:#00dcda;">'.L('_BDYHK_').'</a>'.L('_HCNJMMMCZ_');
			$res['code'] = 202;
			$this->ajaxReturn($res);
		}

		// 检测是否绑定该交易区的银行卡 
		if (!checkUserBindBank($userId,$om) && $type == 3) {
			$res['msg'] = L('_ZHUYI_').L('_QXJX_').'<a href="/PersonalCenter/showBankCardBind" style="color:#00dcda;">'.L('_BDYHK_').'</a>'.L('_HCNJMMMCZ_');
			$res['code'] = 203;
			$this->ajaxReturn($res);
		}

		//检查24h是否修改交易密码    
		$r  = $userObj->checkUserIsTradePwd($userId);
		if($r['code'] != 200) {
			$this->ajaxReturn($r);
		}

		// 检测用户是否在c2c交易禁止时间内
		$isDuringTime = $this->cToCTransLogicsObj->checkUserIsDuringTime($userId);
		if (empty($isDuringTime) || $isDuringTime['code'] != 200) {
			$this->ajaxReturn($isDuringTime);
		}
		$res['code'] = 200;
		$res['msg']  = L('_CHENGGONG_');
		$this->ajaxReturn($res);
	}
	/**
	 * @method 挂单参数检测
	 * @author lirunqing 2018-02-27T10:08:58+0800
	 * @param  array  $data [description]
	 * @return  int   [type]       [description]
	 */
	private function checkParams($data=array()){
		$res = array(
			'code' => 201,
			'msg'  => '',
			'data' => array()
		);

		if (!in_array($data['type'] ,array(1,2))) {
			$res['msg'] = L( '_QXZZQDJYFS_');
			$this->ajaxReturn($res);
		}

		if (empty($data['currency_type'])) {
			$res['msg'] = L('_QXZBZ_');
			$this->ajaxReturn($res);
		}

		$currencyExist = $this->cToCTransLogicsObj->checkCurrencyExist($data['currency_type']);
		if (!$currencyExist) {
			$res['msg'] = L('_QXZBZ_');
			$this->ajaxReturn($res);
		}

		if (empty($data['om'])) {
			$res['msg'] = L('_QXZDQ_');
			$this->ajaxReturn($res);
		}

		if (empty($data['price'])) {
			$res['msg'] = L('_QTXDJ_');
			$this->ajaxReturn($res);
		}

		// 价格非数字
		if(!is_numeric($data['price'])){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}

		// 价格应该大于0
		if($data['price'] <= 0){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}

		if(!regex($data['price'],'double')){
			$res['msg'] = L('_QSRZQDDJ_');
			$this->ajaxReturn($res);
		}

		// 数量不能为空
		if(empty($data['num'])) {
			$res['msg'] = L('_QTXSL_');
			$this->ajaxReturn($res);
		}

		$numMsg = ($data['type'] == 1) ? L( '_QSRZQDMRSL_') : L('_QSRZQDCSSL_');
		// 数量应是数字
		if(!is_numeric($data['num'])){
			$res['msg'] = $numMsg;
			$this->ajaxReturn($res);
		}

		// 数量应大于0
		if($data['num'] <= 0){
			$res['msg'] = $numMsg;
			$this->ajaxReturn($res);
		}

		// 交易密码不能为空
		if(empty($data['tradepwd'])) {
			$res['msg'] = L('_JYMMBNWK_');
			$this->ajaxReturn($res);
		}

		$userId            = getUserId();
		$publicFunctionObj = new PublicFunctionController();
		//验证交易密码的正确性
		$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($userId, $data['tradepwd']);
		if($tradePwdRes['code'] != 200){
			$res['msg'] = $tradePwdRes['msg'];
			// $res['msg'] = ($tradePwdRes != 202 ) ? L('_ZHFXLXPT_') : L('_JYMMCW_');
			$this->ajaxReturn($res);
		}

		$userObj = new CheckAllCanUseParam();
        $retReal     = $userObj->checkUserRealStatus($userId);
		// 未实名认证
		if (empty($retReal) || $retReal['code'] != 200) {
			$this->ajaxReturn($retReal);
		}
		// 护照过期
		$relt = $userObj->checkUserRealIsExpire($userId);
		if (empty($relt) || $relt['code'] != 200) {
			$this->ajaxReturn($relt);
		}
		// 卖单，检测该交易区未绑定银行卡
		if (!checkUserBindBank($userId,$data['om'])) {
			$res['msg'] = L('_GJYQWBDYHK_');
			$this->ajaxReturn($res);
		}

		//检查24h是否修改交易密码 
		$r       = $userObj->checkUserIsTradePwd($userId);
		if($r['code'] != 200) {
			$this->ajaxReturn($r);
		}

		// 检测用户是否在c2c交易禁止时间内
		$isDuringTime = $this->cToCTransLogicsObj->checkUserIsDuringTime($userId);
		if (empty($isDuringTime) || $isDuringTime['code'] != 200) {
			$this->ajaxReturn($isDuringTime);
		}
	}

	/**
	 * @method 买卖订单详细信息
	 * @author 建强  2018年2月27日12:04:57 
	 * @param  int    $id  订单id
	 * @return string json  
	 */
	 public function getOrderInfoByIdApi(){ 
	 	 $res = [
	 	     'code' => 601,'msg'  => L('_CZCG_'),
	 	     'data' =>[]
	 	 ];
	 	 $uid =getUserId();
	 	 if(!isset($this->jsonToArr['id']) ||  !isset($this->jsonToArr['type']) 
	 	     || !isset($this->jsonToArr['om']) || 
	 	     !in_array($this->jsonToArr['type'], array(1, 2)) || !is_array($this->jsonToArr)){
	 	 	$res['msg']=L('_CSCW_');
	 	 	$this->ajaxReturn($res);
	 	 }
         $id           = $this->jsonToArr['id'];
         $om           = $this->jsonToArr['om'];
         $type         = $this->jsonToArr['type'];
	 	 //检查24h是否修改交易密码    
	 	 $userObj=new CheckAllCanUseParam();
	 	 $ret=$userObj->checkUserIsTradePwd($uid);
	 	 if($ret['code']!=200)  $this->ajaxReturn($ret);
	 	 
	 	 //实名认证
	     $ret=$userObj->checkUserRealStatus($uid);
	     if($ret['code']!=200) $this->ajaxReturn($ret);

	     // 护照过期
		$relt = $userObj->checkUserRealIsExpire($uid);
		if (empty($relt) || $relt['code'] != 200) {
			$this->ajaxReturn($relt);
		}
		
	     //卖出绑定银行卡
	     if(!checkUserBindBank($uid,$om) &&  $type==2)
	     {   
	     	 $res['code']=610;
	     	 $res['msg']=L('_ZHUYI_').L('_QXJX_').'<a href="/PersonalCenter/showBankCardBind" style="color:#00dcda;">'.L('_BDYHK_').'</a>'.L('_ZJXMCCZ_');;
	     	 $this->ajaxReturn($res);
 	     }
 	     
 	     //检测是否在24h禁止交易
 	     $duringTime=$this->cToCTransLogicsObj->checkUserIsDuringTime($uid);
 	     if($duringTime['code']!=200) $this->ajaxReturn($duringTime);
 	     
         //刷单
         if(strpos($id,'S')!== false){
 	         //传入的买卖单类型是对应的是子单的，跟主单的反了，要换一下
             if($type == 1) {
                 $scalping_type = 2;
             }else{
                 $scalping_type = 1;
             }
             $result=$this->cToCTransLogicsObj->getScalpingOrderInfo($id,$scalping_type);
             $this->ajaxReturn($result);
         }
         
         //检查订单是否完成
         $result = $this->cToCTransLogicsObj->checkOrderIsFinshed($id);
         if($result['code']!=200) $this->ajaxReturn($result);
         $result=$this->cToCTransLogicsObj->getOrderInfo($id, $type);
         $this->ajaxReturn($result);
	 }
	 
    /**
     * @return json
     * @author zhangxiwen
     * @desc  獲取買入和賣出交易的數據
     */
    public function getTrade(){
        $postData = I('');
        $data = $this->checkTradeParam($postData);
        $om = $data['om'];
        $currencyType = $data['currency_type'];
        $where['a.om'] = $data['om'];
        unset($data['om']);
        $where['a.currency_type'] = $data['currency_type'];
        unset($data['currency_type']);

        // 检测网站维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(1);
        if ($isMaintain['code'] != 200) {
        	$maintainData = [
        		'code' => 200,
        		'data' => ['buy' => [], 'sell' => []],
        		'msg'  => ''
        	];
        	$this->ajaxReturn($maintainData);
        }

        $buyOrderList = $this->cToCTransLogicsObj->buyOrderList($where);
        $sellerOrderList = $this->cToCTransLogicsObj->sellOrderList($where);
        $buyOrderList = $buyOrderList['data'];
        $sellerOrderList = $sellerOrderList['data'];    
        
        $newdata = [];
        //添加刷单数据
        $buyOrderList = $this->cToCTransLogicsObj->scalpingOrder($buyOrderList,
             1,$om,$currencyType);
         $sellerOrderList = $this->cToCTransLogicsObj->scalpingOrder($sellerOrderList,
             2,$om,$currencyType);

        $newdata['buy'] = $buyOrderList;
        $newdata['sell'] = $sellerOrderList;
        $returndata['data'] = $newdata;
        $returndata['code'] = 200;
        $this->ajaxReturn($returndata);
    }
	
	/**
     * @param $data
     * @return array
     * @author zhangxiwen
     */
    private function checkTradeParam($data){
        if (empty($data['om'])) {
            $userId = getUserId();
            $userData = M('User')->where(['uid'=>$userId])->field('om')->find();
            $data['om'] = substr($userData['om'],1);
        }
        if (empty($data['currency_type'])) {
            $data['currency_type'] = 1;
        }
        return $data;
    }
	
    /*
     * 李江 2018年2月27日12:03:47
     * 撤销当前订单
     */
    public function revokeBigOrder(){
        $uid = getUserId();
        $orderNum = trim(I('post.orderNum'));
        if($orderNum == '' || $orderNum == null)
        {
            $this->ajaxReturn(['code' => 403,'msg'=> L('_CSCW_')]);
        }
        //如果主订单有人买入卖出则不允许撤销 5s内
        $mainOderRevok=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$orderNum;
        $haveSmallTrade=$this->redis->get($mainOderRevok);
        if($haveSmallTrade){
            $this->ajaxReturn(['code' => 413,'msg'=> L('_CXSBQSHCS_')]);
        }
        //改变订单状态为撤销
        $orderRevok=redisKeyNameLibrary::PC_C2C_TRADE_REVOKED_CANNOT_OPERAT.$orderNum;
        $this->redis->setex($orderRevok, 5, 1);
        M()->startTrans();   
        $res = $this->cToCTransLogicsObj->revokeBigOrder($orderNum,$uid);
        if($res['code'] == 200 ){
            //撤销成功进行添加
            M()->commit();
            //删除主单的缓存数据
            $this->redis->del(redisKeyNameLibrary::PC_C2C_MIAN_ORDER_ID_INFO.$orderNum);
            
        }else{
            M()->rollback();
        }
        $this->ajaxReturn($res);
    }

    /**
     * 获取用户交易中的挂单列表
     * @author 刘富国
     * 20180227
     * @return array|bool
     */
    public function getUserMainOrderList(){
        $uid = getUserId();
        if($uid < 1 ) {
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_QQCSCC_');
            $this->ajaxReturn($this->msgArr);
        }
        $ret = $this->cToCTransLogicsObj->getUserMainOrderList($uid);
        $this->ajaxReturn($ret);
    }

    /**
     * 用户历史交易历史订单
     * @author 刘富国
     * 20180227
     * @param $type    1：完成订单，2：已撤销订单
     * @param $page   页数
     * * * 交易订单状态：
     *          1买入成功 2买家确认打款 3卖家确认收款 4.超时自动撤销
     *          5.待处理 6.管理员撤销订单 7.管理员完成订单
     * @return array|bool
     */
    public function getUserHistoryTradeOrderList(){
        $userId = getUserId();
        if($userId < 1 ) {
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_QQCSCC_');
            $this->ajaxReturn($this->msgArr);
        }
        $type = trim(I('type'));
        $ajaxFunc= I('ajax_func');
        $parameter['type'] = $type;
        $parameter['ajax_func'] = $ajaxFunc;
        if ( empty($type) or !in_array($type,array(1,2))) $type = 1;
        $orderTradeStatusNameArr = array(   3 => L('_WCHENG_'),//'完成',
            4 => L( '_CSZDCX_'),//'超时自动撤销',
            6 => L('_GLYCXDD_'),
            7 => L( '_GLYWCDD_'),
            8 => L('_XITZDFB_'),   //系统自动放币（属于完成状态）
            
        );
        $orderTradeTypeNameArr = array(1 => L('_MAIRU_'),2 => L('_MAICHU_'));
        $tradeOrderWhere['sell_id|buy_id'] = $userId;
        //已经放币的系统撤销单，算已经完成单
        if($type == 1 ){
            $tradeOrderWhere['shoukuan_time'] = array('gt',0);
            $tradeOrderWhere['status'] =  array('in', array(3,4,7,8));
        }elseif($type == 2){
            $tradeOrderWhere['sell_id|buy_id'] = $userId;
            $whereBuy['status'] = 4;
            $whereBuy['shoukuan_time'] = array('eq',0);
            $whereBuy['_logic'] = "AND";
            $whereBack['status'] = 6;
            $whereBack['_logic'] = "OR";
            $whereBack['_complex']=$whereBuy;
            $tradeOrderWhere['_complex']=$whereBack;
        }
        $ccTradeOrderCount = M('CcTrade')
            ->where($tradeOrderWhere)
            ->count();
        if(empty($ccTradeOrderCount)) return $this->msgArr;
        $AjaxPage =  new \Home\Tools\AjaxPage($ccTradeOrderCount, 8, $ajaxFunc);
        $show      = $AjaxPage->show();// 分页显示输出
        $limit     = $AjaxPage->firstRow.','.$AjaxPage->listRows;
        $ccTradeOrderList = M('CcTrade')
            ->field('*')
            ->where($tradeOrderWhere)
            ->limit($limit)
            ->order('update_time desc,id desc')
            ->select();
        $retCcTradeOrderList = array();
        foreach ($ccTradeOrderList as $key => $item){
            $ret_value = array();
            $ret_value['order_num']     = $item['order_num']; //订单账号
            $ret_value['trade_num']     = $item['trade_num'];   //出售额数量
            $ret_value['trade_price']   = $item['trade_price'];   //单价
            $ret_value['trade_money']   = $item['trade_money'];   //金额
            $ret_value['currency_name'] = getCurrencyName($item['currency_type']); //货币名称
            $ret_value['trade_time']    = $item['trade_time'];    //成交时间
            $ret_value['shoukuan_time'] = $item['shoukuan_time'];    //买家确认打款的时间
            $ret_value['end_time']      = $item['end_time'];    //卖家确认收款时间
            $ret_value['update_time']   = $item['update_time'];    //数据更新时间
            $ret_value['reference_price']   = $item['rate_total_money'].'('.$this->notesArr[$item['om']].')';    //参考总额
            //完成时间
            if(empty($ret_value['end_time']) or $ret_value['end_time'] < 1){
                $ret_value['end_time']  = $ret_value['update_time'] ;
            }
            //订单类型
            $order_type = 2;
            if($item['buy_id'] == $userId){
                $order_type = 1;
                $ret_value['order_num']     = $item['order_num_buy'];
            }
            $ret_value['type_name']     = $orderTradeTypeNameArr[$order_type];    //订单类型
            $ret_value['status_name']   = $orderTradeStatusNameArr[$item['status']]; //状态名称
            $retCcTradeOrderList[]      = $ret_value;
        }
        $ret = array(
            'list'   => $retCcTradeOrderList,
            'show'  => $show,
        );
        $this->msgArr['data'] = $ret;
        $this->ajaxReturn($this->msgArr);
    }

    /**
     * 获取用户子订单列表
     * @author 刘富国
     * 20180227
     * @return array|bool
     */
    public function getUserTradeOrderList(){
        $uid = getUserId();
        $pid = I('pid',0);
        $orderId = I('orderId',0);
        if($uid < 1 or $pid < 1) {
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_QQCSCC_');
            $this->ajaxReturn($this->msgArr);
        }
        $ret = $this->cToCTransLogicsObj->getUserTradeOrderList($uid,$pid,$orderId);
        $this->ajaxReturn($ret);
    }

    /**
     * 用户确认打款/收款
     * @author 刘富国 2018-03-01T14:33:32+0800
     * @return json
     */
    public function confirmOrderAcceptOrPaid(){
        $userId  = getUserId();
        $orderId = trim(I('orderId'));
        $type = I('type')*1; //订单类型：1买单 2卖单
        if($userId < 1 or $orderId < 1 or !in_array($type,array(1,2)) ) {
            $this->msgArr['code'] = 201;
            $this->msgArr['msg'] = L('_QQCSCC_');
            $this->ajaxReturn($this->msgArr);
        }
        $where['id']     = $orderId;
        $where['status']     = array('in',array(1,2));
        $where['sell_id|buy_id'] = $userId;
        $orderRes        = M('CcTrade')->where($where)->find();
        if (empty($orderRes) ) {
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_GDDBKCZ_');
            $this->ajaxReturn($this->msgArr);
        }
   
        //判断主单撤销（先屏蔽，主单撤了，子单还可以正常操作）
//        $mainOrder=M('CcOrder')->find($orderRes['pid']);
//        if($mainOrder['status']==3 && $orderRes['trade_num']>=$mainOrder['num'])
//        {
//            $this->msgArr['code'] = 303;
//            $this->msgArr['msg'] = L('_GDDBKCZ_');
//            $this->ajaxReturn($this->msgArr);
//        }
        M()->startTrans();
        //确认打款
        if($orderRes['status'] == 1 && $userId == $orderRes['buy_id']){

        	$isConfirm = $this->redis->get(redisKeyNameLibrary::PC_C2C_CONFIRM_ORDER.$orderId);
        	if (!empty($isConfirm)) {
        		$this->msgArr['code'] = 700;
		        $this->msgArr['msg']  = L('_QWCFCZ_');
		        $this->ajaxReturn($this->msgArr);
        	}

            $res = $this->cToCTransLogicsObj->confirmTradeOrderPaid($userId,$orderId);
            $msg = L('_MJYQRHK_');
            $this->redis->setex(redisKeyNameLibrary::PC_C2C_CONFIRM_ORDER.$orderId, 10, 1);
        }
        //确认收款
        elseif($orderRes['status'] == 2 && $userId == $orderRes['sell_id']){
        	$isAccept = $this->redis->get(redisKeyNameLibrary::PC_C2C_ACCEPT_ORDER.$orderId);
        	if (!empty($isAccept)) {
        		$this->msgArr['code'] = 701;
		        $this->msgArr['msg']  = L('_QWCFCZ_');
		        $this->ajaxReturn($this->msgArr);
        	}
            $res = $this->cToCTransLogicsObj->orderAccept($userId, $orderId);
            $msg = L('_MJYQRSK_');
            $this->redis->setex(redisKeyNameLibrary::PC_C2C_ACCEPT_ORDER.$orderId, 10, 1);
        }
        if (empty($res) || $res['code'] !=200 ) {
            M()->rollback();
            $this->msgArr['msg'] = $res['msg'];
            $this->msgArr['code'] = $res['code'];
            $this->ajaxReturn($this->msgArr);
        }
        M()->commit();

        //极光推送
        $tempOrderInfo['currencyName'] = getCurrencyName($orderRes['currency_type']) ;
        $tempOrderInfo['rate_total_money'] = $orderRes['rate_total_money']  ;
        $tempOrderInfo['num']=$orderRes['trade_num'];       //数量
        $extras['send_modle'] = 'C2C';
        $extras['new_order_penging'] = '1';
        //付款成功，通知卖家
        if(($orderRes['status'] == 1 )){
            $tempOrderInfo['orderNum'] = $orderRes['order_num'];
            $contentStr = SceneCode::getC2CTradeTemplate(1,'+'.$orderRes['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            $title      = $contentArr[0];
            $content    = $contentArr[1];
            $ret =  push_msg_to_app_person($title, $content, $orderRes['sell_id'],$extras);
        }
        //收款成功，通知买家
        if(($orderRes['status'] == 2 )){
            $tempOrderInfo['orderNum'] = $orderRes['order_num_buy'];
            $contentStr = SceneCode::getC2CTradeTemplate(3,'+'.$orderRes['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            $title      = $contentArr[0];
            $content    = $contentArr[1];
            push_msg_to_app_person($title, $content, $orderRes['buy_id'],$extras);
        }


        $this->msgArr['code'] = 200;
        $this->msgArr['msg']  = $msg;
        $this->ajaxReturn($this->msgArr);
    }

    /**
     * 卖家未收到款项，子订单转为待处理
     * @author 刘富国
     * 20180301
     * @param $userId
     * @param $orderId
     * @return array|int
     */
    public function unReceiptTradeOrderPaid(){
        $uid = getUserId();
        $orderId = trim(I('orderId'));
        if($uid < 1 or $orderId < 1) {
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_QQCSCC_');
            $this->ajaxReturn($this->msgArr);
        }
        M()->startTrans();
        $ret = $this->cToCTransLogicsObj->unReceiptTradeOrderPaid($uid,$orderId);
        if (empty($ret) || $ret['code'] !=200 ) {
            M()->rollback();
        }else{
            M()->commit();
        }
        $this->ajaxReturn($ret);
    }

    /*
     * 设置主订单显示状态
     * 李江
     */
    public function setOrderDisplay(){
        $uid = getUserId();
        $this->msgArr['code'] = 200;
        $this->msgArr['msg'] = L('_KAISHIJIEDAN_');
        if( $uid < 1 ){
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_YHMBNWK_');
            $this->ajaxReturn($this->msgArr);
        }
        $order_display = I('post.status');
        if( !in_array(intval($order_display),[0,1]) ){
            $this->msgArr['code'] = 303;
            $this->msgArr['msg'] = L('_CSCW_');
            $this->ajaxReturn($this->msgArr);
        }
        $res = M('CcComplete')->where(['uid'=>$uid])->setField('order_display',$order_display);
        if( !$res ){
            $this->msgArr['code'] = 302;
            $this->msgArr['msg'] = L('_CZSB_');
        }else{
            if( $order_display == 1 ){
                $this->msgArr['msg'] = L('_KAISHIJIEDAN_');
            }else{
                $this->msgArr['msg'] = L('_GUANBIJIEDAN_');
            }
        }
        $this->ajaxReturn($this->msgArr);
    }
    /*
     * 李江
     * 获取主订单是否显示状态
     * 返回值 1显示 0不显示
     */
    public function getOrderDisplay(){
        $uid = getUserId();
        $this->msgArr['code'] = 200;
        $this->msgArr['msg'] = L('_CZCG_');
        if( $uid < 1 ){
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_YHMBNWK_');
            $this->ajaxReturn($this->msgArr);
        }
        $status = $this->cToCTransLogicsObj->checkUserOrderDisplay($uid);
        if( $status == 1 ){
            $msg = L('_KAISHIJIEDAN_');
        }else{
            $msg = L('_GUANBIJIEDAN_');
        }
        $this->ajaxReturn(['status'=>$status,'msg'=>$msg]);
    }
}