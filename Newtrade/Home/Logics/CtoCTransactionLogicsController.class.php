<?php
/**
 * C2C交易逻辑业务
 * @author lirunqing 2017年10月9日10:20:20
 */
namespace Home\Logics;
use Think\Controller;
use Common\Api\RedisIndex;
use Home\Model\UserCurrencyModel;
use Common\Model\UserBankModel;
use Common\Api\redisKeyNameLibrary;
use Common\Api\RedisCluster;
use Home\Model\CurrencyModel;
use Home\Model\UserModel;
use Home\Tools\SceneCode;
use Common\Api\Maintain;
use Home\Model\ConfigModel;

class CtoCTransactionLogicsController extends Controller {

    const BUY_TYPE = 1; //买单
    const SELL_TYPE = 2; //卖单
    private $redis=NULL;
    public $orderTradeTypeNameArr = array();
    public $selfOrderNameArr = array();
    private $userBankObj;
    private $areaOmName = array();
    private $userModel = array();
    private $orderTradeStatusNameArr = array();
    public $omOfCurrencySymbol = array();
	public $msgArr = array(
		'code' => 200,
		'msg'  => '',
		'data' => array()
	);
    public $optStatusNameArr = array(); //订单显示状态
    //刷单状态 1关闭刷单，2升高，3降低
    const  CLOST_PRICE = 1;
    const  RAISE_PRICE = 2;
    const REDUCE_PRICE = 3;
    
    /**
     * @var float c2c 买卖累计量
    */
    protected $c2c_trade_num;
    
    public function  __construct()
    {
        $this->userBankObj = new UserBankModel;
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->userModel = new UserModel();
        //交易区名称
        $this->areaOmName = array( 852 => L('_ZGXG_'),
                                    886 => L('_ZGTW_'),
                                    86 => L('_ZHONGGUO_')
                                );
        //交易区货币名称
        $this->omOfCurrencySymbol = array( '852' => 'HKD',
            '886' => 'TWD',
            '86' => 'CNY'
        );

        $this->orderTradeTypeNameArr = array(1 => L('_MAIRU_'),2 => L('_MAICHU_')); // 1买入 2卖出
        $this->selfOrderNameArr = array(0 => L('_JIAOYIDAN_'),1 => ''); // 0 交易单
        
            $this->orderTradeStatusNameArr = array(1 => L('_DENGDFK_'),
        		2 => L( '_DDSK_'),
        		3 => L( '_DDSK_'),
        		4 => L( '_CSZDCX_'),//,'超时自动撤销',
        		5 => L('_DCL_'),
        		6 => L('_GLYCXDD_'),
        		7 => L( '_GLYWCDD_')
        );

        $this->optStatusNameArr = array(1 => L(  '_JINGXINGZHONG_'), 2 => L( '_DAICEX_'),3=>L(  '_ZT_'));
        
    }

    /**
     * 检测币种是否存在
     * @author lirunqing 2018-02-27T10:30:36+0800
     * @param  integer $currencyId 币种id
     * @return bool
     */
    public function checkCurrencyExist($currencyId=0){
        $currencyModel = new CurrencyModel();
        $currencyList = $currencyModel->getCurrencyList();
        $currencyIdList = array();
        foreach ($currencyList as $key => $value) {
        	if ($value['status'] == 0) {
        		continue;
        	}
            $currencyIdList[] = $value['id'];
        }
        if (!in_array($currencyId, $currencyIdList)) {
            return false;
        }
        return true;
    }


    /**
     * 根据币种获取保证金/手续费
     * @author 刘富国 2018-03-13
     * @return array|int
     */
    public function getCurrencyFee($currencyId){
        $where = array('currency_id' => $currencyId);
        $currencyConfig = M('CcConfig')->where($where)->find();
        $returnData = array(
            'bond_num' => 0,
            'sell_fee' => 0,
            'buy_fee'  => 0
        );
        if (!empty($currencyConfig)) {
            $returnData['bond_num'] = $currencyConfig['bond_num'];
            $returnData['sell_fee'] = $currencyConfig['sell_fee'];
            $returnData['buy_fee']  = $currencyConfig['buy_fee'];
        }
        $this->msgArr['data'] = $returnData;
        return $this->msgArr;
    }

    /**
	 * 检测网站是否维护
	 * @author lirunqing 2019-02-26T10:46:01+0800
	 * @param  integer $type 1表示市场订单列表维护开关 2表示下单及购买维护开关，3表示我的订单列表维护开关
	 * @return array
	 */
	public function checkWebMaintain($type=1){
		$C2CMaintain = Maintain::getTradeMaintainVals(Maintain::C2C);

		// 检测C2C模块是否开启维护模式
		$forbidOrder  = $C2CMaintain['forbid_order'];// 下单开关
		$masterSwitch = $C2CMaintain['master_switch'];// p2p模式总开关
		$dealOrder    = $C2CMaintain['deal_order'];// 我的订单  开关
		$listOrder    = $C2CMaintain['list_order'];//  市场订单列表开关

		// 1表示市场订单列表维护开关 
		if ($type == 1 && ($listOrder == 1 || $masterSwitch == 1)) {
			$this->msgArr['code'] = 701;
			$this->msgArr['msg']  = L('_CCSCDDWHZ_');
			return $this->msgArr;
		}

		if ($type == 1){
			return $this->msgArr;
		}

		// 表示我的订单列表维护开关
		if ($type == 3 && ($dealOrder == 1 or $masterSwitch == 1)) {
			$this->msgArr['code'] = 702;
			$this->msgArr['msg']  = L('_CCDDGLWHZ_');
			return $this->msgArr;
		}

		if ($type == 3){
			return $this->msgArr;
		}

		// $type==2 下单及购买维护开关
		if ($masterSwitch == 1) {
			$this->msgArr['code'] = 703;
			$this->msgArr['msg']  = L('_CCMSWHZ_');
			return $this->msgArr;
		}

		if ($dealOrder == 1) {
			$this->msgArr['code'] = 704;
			$this->msgArr['msg']  = L('_CCDDGLWHZ_');
			return $this->msgArr;
		}

		if ($forbidOrder == 1) {
			$this->msgArr['code'] = 705;
			$this->msgArr['msg']  = L('_CCXDMKWHZ_');
			return $this->msgArr;
		}

		return $this->msgArr;
	}


    /**
	 * 检测用户资金是否足够挂单
	 * @author lirunqing 2018-02-27T10:57:04+0800
	 * @param  integer $userId     用户id
	 * @param  integer $currencyId 币种id
	 * @param  integer $num        交易总数量
	 * @param  integer $tradeType  交易类型 1 买 2 卖
	 * @return array
	 */
	public function checkUserCurrencyIsAdequate($userId=0, $currencyId=0, $num=0, $tradeType=1){
		if (empty($userId) || empty($currencyId)) {
			$this->msgArr['code'] = 201;
			return $this->msgArr;
		}

		$where = array(
			'currency_id' => $currencyId
		);
		$ccConfig      = M('CcConfig')->where($where)->find();
		$bondNum = !empty($ccConfig['bond_num']) ? $ccConfig['bond_num'] : 0;

		$userCurrencyModel = new UserCurrencyModel();
		$userCurrencyInfo  = $userCurrencyModel->getUserCurrencyByUid($userId, $currencyId); // 个人资金信息

		if(empty($userCurrencyInfo)){
			$this->msgArr['code'] = 202;
			return $this->msgArr;
		}

		if ($userCurrencyInfo['num'] <= 0) {
			$this->msgArr['code'] = 205;
			return $this->msgArr;
		}

		// 挂买入单收取保证金
		if ( $tradeType == 1 && ($bondNum > $userCurrencyInfo['num'])) {
			$this->msgArr['code'] = 206;
			return $this->msgArr;
		}

		// 挂卖出单收取手续费
		$sellFee = $this->getFee($currencyId, $num, 2);
		if ($tradeType == 2 && (bcadd($num, $sellFee, 4) > $userCurrencyInfo['num'])) {
			$this->msgArr['code'] = 207;
			return $this->msgArr;
		}

		return $this->msgArr;
	}
        
	/**
	 * 获取手续费
	 * @author lirunqing 2018-02-27T16:53:10+0800
	 * @param  int $currencyId  币种id
	 * @param  float $num       币数量
	 * @param  float $tradeType 交易类型 1 买 2 卖
	 * @return float
	 */
	public function getFee($currencyId, $num, $tradeType=1){

		$where = array(
			'currency_id' => $currencyId
		);
		$ccConfig = M('CcConfig')->where($where)->find();

		$fee = 0;

		if ($tradeType == 1) {
			$buyFee = big_digital_mul($num, $ccConfig['buy_fee'], 4);
			$fee = ($buyFee < 0.0001) ? 0 : $buyFee;
		}

		if ($tradeType == 2) {
			$sellFee = big_digital_mul($num, $ccConfig['sell_fee'], 4);
			$fee = ($sellFee < 0.0001) ? 0 : $sellFee;
		}
		
		return $fee;
	}


	/**
	 * 检测用户交易卖出是否足够资金
	 * @author lirunqing 2018-02-28T18:13:39+0800
	 * @param  int $currencyId 币种id
	 * @param  int $userId     用户id
	 * @param  float $num      交易数量
	 * @return array
	 */
	public function checkUserMoneyIsEnough($currencyId, $userId, $num){

		if (empty($currencyId) || empty($userId) || empty($num)) {
			$this->msgArr['code'] = 214;
			return $this->msgArr;
		}

		$userCurrencyModel = new UserCurrencyModel();
		$userCurrencyInfo  = $userCurrencyModel->getUserCurrencyByUid($userId, $currencyId); // 个人资金信息

		if(empty($userCurrencyInfo)){
			$this->msgArr['code'] = 215;
			return $this->msgArr;
		}

		if ($userCurrencyInfo['num'] <= 0) {
			$this->msgArr['code'] = 216;
			return $this->msgArr;
		}

		if ($num > $userCurrencyInfo['num']) {
			$this->msgArr['code'] = 206;
			return $this->msgArr;
		}

		return $this->msgArr;
	}

	/**
	 * 判断总金额是否达到配置最小值 统一美元 无需转换
	 * @author lirunqing 2018-02-27T14:08:43+0800
	 * @param  integer $currencyId 币种id
	 * @param  integer $money     总金额
	 * @return array
	 */
	public function checkIsMinMoney($currencyId=0, $money=0){

		if (empty($currencyId) || empty($money)) {
			$this->msgArr['code'] = 203;
			return $this->msgArr;
		}

		$where = array(
			'currency_id' => $currencyId
		);
		$ccConfig = M('CcConfig')->where($where)->find();
		$minMradeMoney =$ccConfig['min_trade_money'];
		if ($money < $minMradeMoney){
			$this->msgArr['code'] = 204;
            $this->msgArr['data']['min_trade_money'] = $minMradeMoney;
			return $this->msgArr;
		}

		$this->msgArr['data'] = array(
			'bond_num' => !empty($ccConfig['bond_num']) ? $ccConfig['bond_num'] : 0,
		);
		$this->msgArr['data']['min_trade_money']=$minMradeMoney;
		return $this->msgArr;
	}

	/**
	 * 挂单检测用是否存在未完成的订单，买入/卖出各自只能挂一单
	 * @author lirunqing 2018-05-17T11:34:42+0800
	 * @param  int  $userId     用户id
	 * @param  int  $currencyId 币种id
	 * @param  integer $type    交易类型
	 * @return json
	 */
	public function checkUserOrderNum($userId, $currencyId, $type=1){

		if (empty($userId) || empty($currencyId)) {
			$this->msgArr['code'] = 801;
            $this->msgArr['msg'] = L('_SJYCQSHCS_');
			return $this->msgArr;
		}

		$where = [
			'uid'           => $userId,
			'status'        => 1,
			'currency_type' => $currencyId,
			'type'          => $type
		];
		$orderNum = M('CcOrder')->where($where)->count();

		if ($orderNum >= 1) {
			$this->msgArr['code'] = 802;
            $this->msgArr['msg'] = L('_CZWWCDD_');
			return $this->msgArr;
		}

		return $this->msgArr;
	}

	/**
	 * 挂单扣除币种数量及添加财务日志
	 * @author lirunqing 2018-02-27T16:45:43+0800
	 * @param  array $data    挂单参数数据
	 * @param  float $bondNum 保证金
	 * @return bool
	 */
	public function processFinanceLogAndCurrencyNum($data, $bondNum,$userId = 0){

		// 挂买入单，收取保证金
        $userId = $userId*1;
		if ($data['type'] == 1 && $bondNum > 0) {
			$bondExtArr = array();
			$bondFinanceType          = 21;
			$bondExtArr['content']    = 'C2C交易保证金扣除'; // 内容 线下交易挂售人扣除

			$bondExtArr['type']       = 2;  // 类型(收入=1/支出=2)
			$bondExtArr['money']      = $bondNum; // 金额
			$bondExtArr['remarkInfo'] = $data['order_num'];
			$bondExtArr['opera']      = 'dec';
			if( $userId>0) $bondExtArr['userId']    = $userId;
			$bondRes = $this->calCurrencyNumAndAddLog($data['currency_type'], $bondFinanceType, $bondExtArr);

			if (empty($bondRes) || $bondRes['code'] != 200) {
				$this->msgArr['code'] = 210;
				$this->msgArr['msg']  = L('_GDSB_');
				return $this->msgArr;
			}
		}

		// 挂卖出单扣除卖出手续费
		if ($data['type'] == 2 && $data['fee'] > 0) {
			$tradeFeeExtArr = array();
			$tradeFeeFinanceType          = 23;
			$tradeFeeExtArr['content']    = "C2C交易单手续费扣除"; // 内容 C2C交易挂售人扣除
			$tradeFeeExtArr['type']       = 2;  // 类型(收入=1/支出=2)
			$tradeFeeExtArr['money']      = $data['fee']; // 金额
			$tradeFeeExtArr['remarkInfo'] = $data['order_num']; // 金额
			$tradeFeeExtArr['opera']      = 'dec';
            if( $userId>0) $tradeFeeExtArr['userId']    = $userId;
			$tradeFeeRes = $this->calCurrencyNumAndAddLog($data['currency_type'], $tradeFeeFinanceType, $tradeFeeExtArr);

			if (empty($tradeFeeRes) || $tradeFeeRes['code'] != 200) {
				$this->msgArr['code'] = 211;
				$this->msgArr['msg']  = L('_GDSB_');
				return $this->msgArr;
			}
		}

		// 挂卖出单扣除卖出币种数量
		if ($data['type'] == 2) {
			$tradeExtArr = array();
			$tradeFinanceType          = 19;
			$tradeExtArr['content']    = 'C2C交易单(卖)扣除币'; // 内容 C2C交易挂售人扣除
			$tradeExtArr['type']       = 2;  // 类型(收入=1/支出=2)
			$tradeExtArr['money']      = $data['num']; // 金额
			$tradeExtArr['remarkInfo'] = $data['order_num'];
			$tradeExtArr['opera']      = 'dec';
            if( $userId>0) $tradeExtArr['userId']    = $userId;
			$tradeRes = $this->calCurrencyNumAndAddLog($data['currency_type'], $tradeFinanceType, $tradeExtArr);

			if (empty($tradeRes) || $tradeRes['code'] != 200) {
				$this->msgArr['code'] = 212;
				$this->msgArr['msg']  = L('_GDSB_');
				return $this->msgArr;
			}
		}

		return $this->msgArr;
	}

	/**
	 * 扣除币种数量及添加财务日志
	 * @author lijiang 2018-02-27T16:13:46+0800
	 * @param  int $currencyType    币种id
	 * @param  int $financeType 日志类型
	 * @param  array $extArr      扩展数组
	 *                $extArr['content']    内容说明 例如:线下交易挂售人扣除 必传
	 *			      $extArr['type']       类型(收入=1/支出=2) 必传
	 *				  $extArr['money']      金额 必传
	 *				  $extArr['remarkInfo'] 订单号    必传
	 *				  $extArr['opera']      运算符号,inc加，dec扣除   必传         	
	 *				  $extArr['userId']     用户id,非必传 
	 * @return array
	 */
	public function calCurrencyNumAndAddLog($currencyType, $financeType, $extArr){

		$userId       = !empty($extArr['userId']) ? $extArr['userId'] : getUserId(); // 用户id
		$userMoneyApi = new UserMoneyApi();
		$currencyRes  = $userMoneyApi->setUserMoney($userId, $currencyType, $extArr['money'], 'num', $extArr['opera']);

		if (empty($currencyRes)) {
			$this->msgArr['code'] = 208;
			return $this->msgArr;
		}
		$finLogRes = $this->operationUserCoin($currencyType, $financeType, $extArr);// 添加财务日志

		if (empty($finLogRes) || $finLogRes['code'] != 200) {
			$this->msgArr['code'] = 209;
			return $this->msgArr;
		}

		return $this->msgArr;
	}

	/**
	 * 计算用户币种金额
	 * @author lirunqing 2018年2月27日14:32:26
	 * @param  int     $currencyId  币种id
	 * @param  string  $financeType 日志类型
	 * @param  array   $dataArr     扩展数组
	 *         string  $dataArr['content'];内容 必传
	 *         float   $dataArr['money'];金额 必传
	 *         int     $dataArr['type'];类型(收入=1/支出=2) 必传
	 *         int     $dataArr['userId']     用户id,非必传
	 * @return array
	 */
	public function operationUserCoin($currencyId, $financeType, $dataArr=array()){

		$returnArr              = array();
		$userId                 = !empty($dataArr['userId']) ? $dataArr['userId'] : getUserId(); // 用户id
		$dataArr['financeType'] = $financeType;
		
		$publicFuntionObj      = new PublicFunctionController();
		$userMoneyApi          = new UserMoneyApi();
		$afterMoney            = $publicFuntionObj->getUserBalance($userId, $currencyId);// 获取用户余额
		$dataArr['afterMoney'] = $afterMoney;
		$moneyRes              = $userMoneyApi->AddFinanceLog($userId, $currencyId, $dataArr);// 记录用户财务日志
		if (empty($moneyRes)) {
			$this->msgArr['code'] = 207;
			return $this->msgArr;
		}

		return $this->msgArr;
	}

	/**
	 * 生成一条订单信息
	 * @author lirunqing 2018-02-27T11:54:17+0800
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	public function generateOrder($data=array(),$userId = 0){

		$data['add_time']  = time();
		$data['status']    = 1;

		$addData = $this->checkAddParams($data);

		// 生成挂单表的一条记录
		$addRes = M('CcOrder')->add($addData);

		if (empty($addRes)) {
			$this->msgArr['code'] = 213;
			$this->msgArr['msg']  = L('_GDSB_');
			return $this->msgArr;
		}
		if(empty($userId) or $userId<1) $userId = getUserId();
		// 检测用户是否存在完成率表，如果不存在则新增
		$this->checkUserIsExistComplete($userId);

		$this->msgArr['data'] = array('order_id' => $addRes);

		return $this->msgArr;
	}

	/**
	 * 过滤字段
	 * @author lirunqing 2018-08-01T10:57:49+0800
	 * @param  array $data 
	 * @return array
	 */
	public function checkAddParams($data){
		$fieldArr = [
			'id',
			'order_num',
			'currency_type',
			'price',
			'money',
			'num',
			'success_num',
			'leave_num',
			'uid',
			'om',
			'status',
			'update_time',
			'add_time',
			'bond_num',
			'type',
			'start_hide_hour',
			'end_hide_hour',
			'is_break',
			'fee',
			'leave_fee',
  			'last_uid'
		];

		foreach ($data as $key => $value) {
			if (!in_array($key, $fieldArr)) {
				unset($data[$key]);
				continue;
			}
		}

		return $data;
	}

	/**
	 * 生成唯一订单号订单号
	 * @author lirunqing 2018年2月27日11:49:29
	 * @param  int $userId 1表示cc_order表，2表示cc_trade表
	 * @return string
	 */
	public function genOrderId($userId=1){
		$orderId = 'C'.date('YmdHis').$userId.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 10);		
        return $orderId;
	}

	/**
	 * 生成唯一订单号订单号
	 * @author lirunqing 2018年2月27日11:49:29
	 * @param  int $userId 1表示cc_order表，2表示cc_trade表
	 * @return string
	 */
	public function genOrderIdBuy($userId=1){
		$orderId = 'C'.date('YmdHis').$userId.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 8, 14), 1))), 0, 10);
        return $orderId;
	}



	/**
	 * 检验订单是否完成
	 * @author 建强  2018年2月27日16:45:28
	 * @param  $id 订单id
	 * @param  $num>0  量级  验证量级
	 * @param  $moeny>0 验证金额
	 */
    public function checkOrderIsFinshed($id,$num=0,$money=0,$uid=0)
    {
  	    $ret=M('CcOrder')->find($id);
        if($ret['status']>=2 or empty($ret))
        {
            $this->msgArr['code']=652;
            $this->msgArr['msg']=L('_GDDYJWCHCX_');
            return $this->msgArr;
        }
  	    //自己不能买自己的
  	    if($uid==$ret['uid'])
  	    {
  	    	$this->msgArr['code']=626;
  	    	$this->msgArr['msg']=L('_BNGMZJDDD_');
  	    	return $this->msgArr;
  	    }

  	    $displayWhere=[
  	        'order_display'=>0,      
  	        'uid'=>$ret['uid'],      
  	    ];
  	    
  	    $disInfo=M('CcComplete')->where($displayWhere)->find();
  	    if($disInfo)
  	    {
  	        $this->msgArr['code']=693;
  	        $this->msgArr['msg']=L('_CDDGSJBKJ_');
  	        return $this->msgArr;
  	    }
  	    
  	    //订单是否可见 
  	    if($this->checkOvertime($ret['start_hide_hour'],$ret['end_hide_hour']))
  	    {
  	    	$this->msgArr['code']=645;
  	    	$this->msgArr['msg']=L('_CDDGSJBKJ_');
  	    	return $this->msgArr;
  	    }
         
        if($num>0 && $ret['leave_num']<$num)
        {
        	//余量不足 
            $str = ($ret['type'] == 1) ? L('_MAICHU_') : L('_MAIRU_');
            $this->msgArr['code']=622;
            $this->msgArr['msg']=L('_DDWLBZWFGM_').$str;
            return $this->msgArr;
        }
      
        //检验money金额是否正确
        if($money>0 && $num>0)
        {     
        	$totalMoney = big_digital_mul($num, $ret['price'], 2);
        	$diff = bcsub($totalMoney, $money, 2);
            if(abs($diff) >= 1)   //看整数金额是否正确
            {    
            	$this->msgArr['code']=677;
        	    $this->msgArr['msg']=L('_DDJEWCTD_');
        	    return $this->msgArr;
            }
        }

        $publicObj    = new PublicFunctionController();
		$isUserStatus = $publicObj->getUserStatusByUserId($ret['uid']);
		// 如果挂单人存在交易风险，则不能购买订单
		if (empty($isUserStatus)) {
			$this->msgArr['code']=699;
    	    $this->msgArr['msg']=L('_GDDYJWCHCX_');
			return $this->msgArr;
		}
        
        $this->msgArr['data']=$ret;
        return $this->msgArr;
    }
    /**
     * @method 获取美金兑人民币汇率
     * @return array
    */
    public function getConfigHUILV(){
    	$res=M('Config')->select();
        $arr=[];   
    	foreach($res as $v){   
    		$arr[$v['key']]=$v['value'];
    	}
        return [ 
    		 "86"=>$arr['RMB_HUILV'],
    		 "886"=>$arr['TW_HUILV'],
    		 "852"=>$arr['HK_HUILV'],
        ];
    }
    /**
     * @method 获取订单详细数据
     * @author 建强  2018年2月27日16:45:28
	 * @param  int  $id 订单id
	 * @param  int  $tradeType  购买类别 1表示买入 2表示卖出
     */
	 public function getOrderInfo($id, $tradeType=1){   
	 	 $getField= 'c.id,c.price,c.num,c.leave_num,c.om,c.uid,c.currency_type,u.username,u.level,cu.currency_name';
	 	 $join    = 'LEFT JOIN __USER__ AS u ON u.uid = c.uid';
	 	 $join2   = 'LEFT JOIN __CURRENCY__ AS cu ON cu.id = c.currency_type';
	     $ret     = M('CcOrder')->alias('c')->join($join)->join($join2)
	         ->where(['c.status=1','c.id'=>$id])
	         ->field($getField)->find();
	     if(empty($ret)){  
	    	$this->msgArr['code']=622;
	    	$this->msgArr['msg']=L('_GDDBCZ_');
	    	return  $this->msgArr;
	     }
	     $ccWhere = [
			'uid'           => $ret['uid'],
			'order_display' => 1];
	 	 $ccRes = M('CcComplete')->where($ccWhere)->find();
	 	// 检测买入/卖出订单的用户是否开启接单
	 	if(empty($ccRes)){
	 	    $this->msgArr['code']=623;
	 	    $this->msgArr['msg']=L('_DQDDBZCCCZ_');
	 	    return  $this->msgArr;
	 	}
	     // 买入获取订单中的uid
	     if ($tradeType == 1){
	     	$userId = $ret['uid'];
	     }else{
	     	// 卖出获取当前用户id
	     	$userId = getUserId();
	     }
	     //主订单redis缓存
	     $this->setMainOrderCache($ret);
	     //返回值
	     $ret['areaName']= getAreaName($ret['om']);
	     $ret['tips']    = $this->getOrderomDiffUserom($ret['om'],getUserId());
	     $ret['username']= $this->getUserRealBankName($ret['uid'],'card_name'); //获取用户实名认证姓名
	     
	     $this->msgArr['data']['orederInfo']= $ret;
	     $this->msgArr['data']['bankInfo']  = $this->getUserBank($userId,$ret['om']);
	     $this->msgArr['data']['huilv']     = $this->getConfigHUILV();
	     $this->msgArr['data']['msg']       = L('_CZCG_');
	     return $this->msgArr;
	 }

    /**
     * @author 建强 2019年3月25日 下午4:31:16
     * @method 主订单设置redis缓存   订单信息:订单总量和订单剩余量
     * @param $order
     * @return array
     */
	 protected function setMainOrderCache($order){
	     $id         = $order['id'];
	     $order_key  = redisKeyNameLibrary::PC_C2C_MAIN_ORDER_NUM.$id;
	     //如果订单总量缓存,设置订单缓存
	     $order_num  = $this->redis->get($order_key);
	     if(empty($order_num)){
	        return $this->redis->set($order_key,$order['num']);   
	     } 
	     return true;
	 }
    /**
     *
     * 获取APP订单详细数据
     * @author 刘富国 20180612
     * @param $orderId  订单id
     * @param $uid   用户ID
     * @param $tradeType 购买类别，1表示买入；2表示卖出
     * @return array
     */
	 function  getOrderInfoApp($orderId,$uid,$tradeType){
         $orderInfo = M('CcOrder')->where(['id'=>$orderId])->find();
         if($orderInfo['status']>=2 or empty($orderInfo))
         {
             $this->msgArr['code']=30037;
             $this->msgArr['msg']=L('_GDDYJWCHCX_');
             return $this->msgArr;
         }
         $pendingOrderUsername = $this->getUserRealBankName($orderInfo['uid'],'card_name');
         $currencyId = $orderInfo['currency_type'];
         //计算卖家/买家的完成率
         $completeInfo = M('CcComplete')->where(['uid'=>$orderInfo['uid']])->find();
         $allTime = $completeInfo['small_order_time'];
         $subTime = $completeInfo['break_order_time'];
         $completeRate = $this->calcCompleteRate($allTime,$subTime,4);

         //获取交易区
         $om = $orderInfo['om'];
         $omName = getAreaName($om);
         $omKey = '+'.$om;

         $operOm = M('User')->where(['uid'=>$uid])->getField('om');
         $isFlag = $omKey == $operOm ? 1 : 0;
         //获取银行卡信息
         if( $tradeType == 1 ){
             //买入获取挂单人银行卡id
             $bankList = M('UserBank')->alias('ub')
                 ->join('left join trade_bank_list as bl on ub.bank_list_id=bl.id')
                 ->where(['uid'=>$orderInfo['uid'],'status' => 1,'country_code'=>$omKey])
                 ->field('ub.id,ub.bank_list_id,bl.bank_name')
                 ->order('ub.default_status desc,ub.id asc')
                 ->select();
         }else{
             //卖出获取
             $bankList = M('UserBank')->alias('ub')
                 ->join('left join trade_bank_list as bl on ub.bank_list_id=bl.id')
                 ->where(['uid'=>$uid,'status' => 1,'country_code'=>$omKey])
                 ->field('ub.id,ub.bank_list_id,bl.bank_name')
                 ->order('ub.default_status desc,ub.id asc')
                 ->select();
             if( empty($bankList) || !isset($bankList) ){
                 $this->msgArr['msg']=L('_WBDJYYHK_'); //'未绑定银行卡'
                 $this->msgArr['code'] = 30001;
                 return  $this->msgArr;
             }
         }

         if(!empty($bankList)){
             foreach ($bankList as $key => $item){
                 $bankList[$key]['bank_name'] = formatBankType($item['bank_list_id']);
             }
         }

        //主订单redis缓存
         $this->setMainOrderCache($orderInfo);

         //订单剩余量
         $leaveNum  = $orderInfo['leave_num'];

         //获取汇率信息
         $retCurr = $this->getCurrencyFee($currencyId); //手续费，保证金
         $returnData['currency_fee'] = $retCurr;
         $rateArr = $this->getConfigHUILV(); //汇率
         $returnData = [];
         $returnData['price'] = $orderInfo['price'];
         $returnData['bankInfo'] = $bankList;
         $returnData['pendingOrderUsername'] = $pendingOrderUsername;
         $returnData['completeRate'] = $completeRate;
         $returnData['leaveNum'] = $leaveNum;
         $returnData['omName'] = $omName;
         $returnData['orderCount'] = $allTime;
         $returnData['area_rate'] = $rateArr[$om]*1;
         $returnData['currency_symbol'] = $this->omOfCurrencySymbol[$om];
         $returnData['isFlag'] = $isFlag;//判断操作人默认交易区和大单的交易区是否一样

         $currencyInfo = M('UserCurrency')->alias('uc')->join('__CURRENCY__ as c on uc.currency_id=c.id')
             ->field('uc.num,c.currency_name')
             ->where(['uc.currency_id'=>$currencyId,'uc.uid'=>$uid])
             ->find();
         $returnData['currencyName'] = $currencyInfo['currency_name'];
         //如果是卖出 获取当前用户可用余额
         $returnData['balance'] = $currencyInfo['num'];
         $this->msgArr['code'] = 200;
         $this->msgArr['data'] = $returnData;
         return $this->msgArr;
     }

    /**
     *
     * 获取app刷单订单详细数据
     * @author 刘富国 20180612
     * @param $orderId  订单id
     * @param $uid   用户ID
     * @param $tradeType 购买类别，1表示买入；2表示卖出
     * @return array
     */
    function  getScalpingOrderInfoApp($orderId,$uid,$tradeType){
        $jsonScalpingOrderInfo = $this->redis
            ->get(redisKeyNameLibrary::CC_SCALPING_ORDER_INFO.$tradeType.$orderId);
        if(empty($jsonScalpingOrderInfo))
        {
            $this->msgArr['code']=30037;
            $this->msgArr['msg']=L('_GDDBCZ_');
            return  $this->msgArr;
        }
        $scalpingOrderInfo = json_decode($jsonScalpingOrderInfo,1);
        $om = $scalpingOrderInfo['om'];
        $currencyId = $scalpingOrderInfo['currency_type'];
        $rateArr = $this->getConfigHUILV(); //汇率
        //获取交易区
        $omKey = '+'.$om;
        $operOm = M('User')->where(['uid'=>$uid])->getField('om');
        $isFlag = $omKey == $operOm ? 1 : 0;
        //获取银行卡信息
        if( $tradeType == 2 ){
            //卖单，获取挂单人银行信息
            $bankInfo['bank_name']= formatBankType($scalpingOrderInfo['bank_id']);
            $bankInfo['id']=1;
            $bankInfo['bank_list_id'] = $scalpingOrderInfo['bank_id'];
            $bankList[] = $bankInfo;
        }else{
            //买单，获取买家银行
            $bankList = M('UserBank')->alias('ub')
                ->join('left join trade_bank_list as bl on ub.bank_list_id=bl.id')
                ->where(['uid'=>$uid,'status' => 1,'country_code'=>$omKey])
                ->field('ub.id,ub.bank_list_id,bl.bank_name')
                ->order('ub.default_status desc,ub.id asc')
                ->select();
            if( empty($bankList) || !isset($bankList) ){
                $this->msgArr['msg']=L('_WBDJYYHK_'); //'未绑定银行卡'
                $this->msgArr['code'] = 30001;
                return  $this->msgArr;
            }
        }

        if(!empty($bankList)){
            foreach ($bankList as $key => $item){
                $bankList[$key]['bank_name'] = formatBankType($item['bank_list_id']);
            }
        }
        $returnData = [];
        $returnData['completeRate'] = $scalpingOrderInfo['complete_rate'];
        $returnData['price'] = $scalpingOrderInfo['price'];
        $returnData['pendingOrderUsername'] =  $scalpingOrderInfo['username'];
        $returnData['leaveNum'] = $scalpingOrderInfo['leave_num'];
        $returnData['orderCount'] = $scalpingOrderInfo['total_order'];
        $returnData['omName'] = getAreaName($om);
        $returnData['area_rate'] = $rateArr[$om]*1;
        $returnData['currency_symbol'] = $this->omOfCurrencySymbol[$om];
        $returnData['bankInfo'] = $bankList;
        $returnData['isFlag'] = $isFlag;//判断操作人默认交易区和大单的交易区是否一样
        $currencyInfo = M('UserCurrency')->alias('uc')->join('__CURRENCY__ as c on uc.currency_id=c.id')
            ->field('uc.num,c.currency_name')
            ->where(['uc.currency_id'=>$currencyId,'uc.uid'=>$uid])
            ->find();
        $returnData['currencyName'] = $currencyInfo['currency_name'];
        //如果是卖出 获取当前用户可用余额
        $returnData['balance'] = $currencyInfo['num'];
        $this->msgArr['code'] = 200;
        $this->msgArr['data'] = $returnData;
        return $this->msgArr;
    }

    /*
  * 计算某用户的完成率
  * 李江
  * $num 保留几位小数
  */
    public function calcCompleteRate($allData,$SubData,$num){
        return big_digital_div( $allData-$SubData,$allData,$num) * 100 . '%';
    }

    /**
     * 获取刷单订单详细数据
     * @author 富国 20180611
     * @param  $id 订单id
     * @param  $tradeType  购买类别，1表示买入；2表示卖出
     */
    public function getScalpingOrderInfo($id, $tradeType=1)
    {
        if(empty($id) or empty($tradeType) ){
            $this->msgArr['code']=621;
            $this->msgArr['msg']=L('_GDDBCZ_');
            return  $this->msgArr;
        }
        $jsonScalpingOrderInfo = $this->redis
            ->get(redisKeyNameLibrary::CC_SCALPING_ORDER_INFO.$tradeType.$id);
        if(empty($jsonScalpingOrderInfo))
        {
            $this->msgArr['code']=623;
            $this->msgArr['msg']=L('_GDDBCZ_');
            return  $this->msgArr;
        }
        $scalpingOrderInfo = json_decode($jsonScalpingOrderInfo,1);
        $om = $scalpingOrderInfo['om'];
        $currency_type = $scalpingOrderInfo['currency_type'];
        $retOrederInfo['id'] = $scalpingOrderInfo['id'];
        $retOrederInfo['price'] = $scalpingOrderInfo['price'];
        $retOrederInfo['om'] = $om;
        $retOrederInfo['uid'] = $scalpingOrderInfo['uid'];
        $retOrederInfo['currency_type'] = $currency_type;
        $retOrederInfo['username'] =  $scalpingOrderInfo['username'];
        $retOrederInfo['level'] = 1;
        $retOrederInfo['currency_name'] = getCurrencyName($currency_type);

        // 卖单获取卖家银行信息
        if ($tradeType == 2){
            $bankInfo['default_status']=1;
            $bankInfo['bank_num']= $scalpingOrderInfo['bank_num'];
            $bankInfo['bank_address']=formatBankType($scalpingOrderInfo['bank_id']);
            $bankInfo['user_bank_id'] = 1;
            $bankList[] = $bankInfo;
        }else{
            // 买单，当前用户银行信息
            $bankList = $this->getUserBank(getUserId(),$om);
        }

        //组装数据
        $retOrederInfo['areaName']  =getAreaName($om);
        $retOrederInfo['tips']      = 0;
        $this->msgArr['data']['orederInfo'] =$retOrederInfo;
        $this->msgArr['data']['bankInfo'] = $bankList;
        $this->msgArr['data']['huilv']=$this->getConfigHUILV();
        $this->msgArr['data']['msg']=L('_CZCG_');
        return $this->msgArr;
    }
	 
	 /**
	  * @param int 获取用户银行姓名 $uid
	  * @return string
	 */
	 public function getUserRealBankName($uid,$field='bank_name'){
	     $bankName= M('UserReal')->where(['uid'=>$uid])->getField($field);
	     if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $bankName)>0){
	         return  mb_substr($bankName,0,1,'utf-8').'**';
	     }
	     return  substr($bankName,0,3).'**';
	 }
	 /**
	  * @method 根据当前登录用户进行对比
	  * @param  string 发布订单order $orderOm
	  * @param  string 注册用户表            $userOm
	  * @return int $flag
	  */ 
	 private function getOrderomDiffUserom($orderOm,$loginUid){
	 	   $loginOm=M('User')->where(['uid'=>$loginUid])->getField('om');
	 	   $flag=1;
           $userOm=substr($loginOm, 1); 	 	
           if($orderOm==$userOm)  $flag=0; //不显示           
           return $flag;	
	 }
	 /**
	  *@method 获取用户银行信息
	  *@param  $uid 
	  *@return array
	 */
	 public  function getUserBank($uid,$om){  
	 	//筛选用户默认的指定地区的银行卡
	 	if(stripos($om,'+')===false){
	 	    $om='+'.$om;	
	 	}
	 	$join='LEFT JOIN __BANK_LIST__  AS l ON l.id = u.bank_list_id';
	 	$bankInfo=M('UserBank')
	 	    ->alias('u')->join($join)
	 	    ->field('u.default_status,u.id as user_bank_id,u.bank_num,l.id')
	 	    ->where(['u.uid'=>$uid,'u.status'=>1,'l.country_code'=>$om])
	 	    ->order('u.default_status desc')
	 	    ->select();
	 	foreach ($bankInfo as $k=>$v){   
	 		$bankInfo[$k]['default_status']=$v['default_status'];
	 	    $bankInfo[$k]['bank_num']= substr_replace($v['bank_num'],'***',0,-4);
	 	    $bankInfo[$k]['bank_address']=formatBankType($v['id']);
	 	    unset($bankInfo[$k]['id']);
	 	}
	 	return $bankInfo;
	 }
	 
	 /**
	  * @method 检验银行卡是否存在
	  * @author 建强  2018年2月27日20:16:02
	  * @param  int  uid  $order_uid
	  * @param  int  $user_bank_id
	  */
	 public  function checkUserBankIsHave($order_uid,$user_bank_id){
	     $where          = [];
	 	 $where['uid']   = $order_uid;
	 	 $where['id']    = $user_bank_id;
	     $where['status']= 1;
	 	 $ret            = M('UserBank')->where($where)->find();
	 	 if(empty($ret)){
	 	   	 $this->msgArr['code']=632;
	 	   	 $this->msgArr['msg']=L('_FKYHBCZ_');
	 	 }
	 	 unset($this->msgArr['data']);
         return $this->msgArr;	 	   
	 }

	 /**
	 * 根据国家地区返回汇率
	 * @author lirunqing 2019-05-23T16:25:16+0800
	 * @param  [type] $om [description]
	 * @return [type]     [description]
	 */
	private function getRateByOm($om){
		$configModel = new ConfigModel();
		$configList = $configModel->getConfigList();

		if ($om == '86') {
			return $configList['RMB_HUILV'];
		}elseif ($om == '886') {
			return $configList['TW_HUILV'];
		}else{
			return $configList['HK_HUILV'];
		}
	}
	 
	 /**
	  * @method 处理买入卖出
	  * @author 建强  2018年2月27日20:16:48 
	  * @param  array $jsonToarr  买卖post参数
	  * @param  int   $uid        用户id
	  * @param  array $orederInfo 主订单数据
	  * @return array 
	  */
	 public function proccessBuyingOrder($jsonToarr,$uid,$orederInfo){   
	     
	       $data = $mainOrder = $r = [];
	 	   //子订单数据
	 	   $data['status']       = 1;                   //买入成功
	 	   $data['trade_time']   = NOW_TIME;
	 	   $data['type']         = $jsonToarr['type'];    //1买入 2卖出
	       $data['pid']          = $orederInfo['id'];
	       $data['p_order_num']  = $orederInfo['order_num'];
	       $data['currency_type']= $orederInfo['currency_type'];
	       $data['trade_num']    = $jsonToarr['num'];
	       $data['trade_price']  = $orederInfo['price'];
	       $data['trade_money']  = bcmul($orederInfo['price'], $jsonToarr['num'],2);
	       $data['user_bank_id'] = $jsonToarr['user_bank_id'];
	       $data['om']           = $orederInfo['om'];

	       $data['order_num']=$this->genOrderId($orederInfo['uid']);
	       $data['order_num_buy']=$this->genOrderIdBuy($uid);


	       // 计算当前地区汇率的总额
			$rate                     = $this->getRateByOm($orederInfo['om']);
			$rateTotalMoney           = big_digital_mul($data['trade_money'], $rate, 2);
			$rateTotalMoney           = ($orederInfo['om'] == '886') ? round($rateTotalMoney, 0).'.00' : $rateTotalMoney;
			$data['rate_total_money'] = $rateTotalMoney;
	       
	       // 主订单是卖出，则子订单buy_id为当前用户id，sell_id则是主订单的uid
	       // 主订单是买入，则子订单buy_id主订单的uid，sell_id则是为当前用户id
	       if ($orederInfo['type'] == 1) {
	       		$data['buy_id'] =$orederInfo['uid'];
	       		$data['sell_id']=$uid;	
	       }else{
	       		$data['buy_id'] =$uid;
	       		$data['sell_id']=$orederInfo['uid'];
	       }
	       
	       //主订单表
	       $mainOrder['success_num']= bcadd($orederInfo['success_num'], $jsonToarr['num'],4);
	       $mainOrder['leave_num']  = bcsub($orederInfo['leave_num'], $jsonToarr['num'],4);
	       $mainOrder['update_time']= microtime(true);    //秒数后四位保留  
	       $mainOrder['last_uid']   = $uid;               //最后一次买卖人的用户id
	       
	        //收取挂单人的手续费     子订单买入  买家不收取手续费
	      if($jsonToarr['type'] == 1){
               //子订单		   	    
		   	   $data['sell_fee']=$this->getFee($orederInfo['currency_type'], $jsonToarr['num'],2);
		   	   //主订单剩余手续费不够 
		   	   if($orederInfo['leave_fee']<$data['sell_fee']){
		   	       $this->msgArr['code']= 688;
		   	       $this->msgArr['msg'] = L('_SXFKCYC_');
		   	       return $this->msgArr;
		   	   }
		   	   //主订单更新剩余手续费
		   	   $mainOrder['leave_fee']=bcsub($orederInfo['leave_fee'], $data['sell_fee'],4);
		   }else{
		   		// 子订单卖出     买家收取手续费
		   		$data['buy_fee']=$this->getFee($orederInfo['currency_type'], $jsonToarr['num'],1);
		   } 
		   
		   //如果剩余量为0 订单修改成完成
		   if($mainOrder['leave_num']==0) $mainOrder['status']=2;
		   
	       $whereMainOrder=[
	         'id'=>$orederInfo['id'],
	       	 'leave_num'=>['EGT',0],  	
	       ];
	       
	       $id  = M('CcTrade')->add($data);	     
	       $r[] = M('CcOrder')->where($whereMainOrder)->save($mainOrder);
	       $r[] = $id;

	       if(in_array(false, $r)){
	           $this->msgArr['code']=639;
	           $this->msgArr['msg']=($jsonToarr['type'] == 2)?L('_MCSBXTFM_'):L('_MRSBXTFM_');
	           return $this->msgArr;
	       }
	       
	       // 卖出扣除币及添加财务日志
	       if ($jsonToarr['type'] == 2){
		       	$financeType = 25;
		       	$extArr      = array(
		       			'content'    => 'C2C卖出订单扣除',
		       			'type'       => 2,
		       			'money'      => $jsonToarr['num'],
		       			'remarkInfo' =>	$data['order_num'],   //卖出  记录订单卖出的orderNum
		       			'opera'      => 'dec',
                        'userId'     => $uid
		       	);
		       	$finRes = $this->calCurrencyNumAndAddLog($orederInfo['currency_type'], $financeType, $extArr);
		       	if(empty($finRes) || $finRes['code'] != 200) $r[]=false;
	       }

	       // 添加用户完成率表
	       $this->checkUserIsExistComplete($data['sell_id']);
	       $this->checkUserIsExistComplete($data['buy_id']);

	       $this->msgArr['data']['addArr']    = $r;
	       $this->msgArr['data']['order_id']  = $id;
	       $this->msgArr['data']['leave_num'] = $mainOrder['leave_num'];
	       return $this->msgArr;
	 }
    /** 检测用户订单是否在某个时间段内
     * @param $value
     * @return bool
     * @author zhangxiwen
     */
    private function checkOvertime($start_hide_hour,$end_hide_hour){
        $currentH = date('H');
        if(abs($end_hide_hour-$start_hide_hour)>0){
            if($end_hide_hour-$start_hide_hour<0){
                if( ($start_hide_hour<= $currentH &&
                        strtotime(date('Ymd').$currentH.":0:0") <=strtotime(date('Ymd')."23:59:59")) ||
                    (strtotime(date('Ymd')."23:59:59") <=strtotime(date('Ymd').$currentH.":0:0") &&
                        $currentH <= $end_hide_hour )){
                    return true;
                }
            } else{
                if( $start_hide_hour<= $currentH && $currentH <=$end_hide_hour){
                    return true;
                }
            }
        }
       return false;
    }

    /**@买单列表
     * @param $where
     * @return mixed
     * @author zhangxiwen
     * @desc   獲取購買列表
     * 规则
     *      type =1 买单
     *      状态为挂单中
     *      时间为48小时以内
     *      不展示本人的订单
     */
    public function buyOrderList($where){
        $tradeModel = M('Cc_order')->alias('a');
        $where['a.type'] = 1;
        $where['b.order_display'] = 1;
        $where['a.status'] = array('EQ',"1"); // 刪選掉已完成的訂單
        //$time = time() - 48*3600;
        //$where['a.add_time'] = array('EGT',$time); // 筛选未超时的订单
        $ccTrade = M('CcConfig')->select();
        $newCcTrade = null;
        foreach ($ccTrade as $item){
            $newCcTrade[$item['currency_id']] = $item['min_trade_money'];
        }
        $where['a.uid']  = array('NEQ',getUserId()); // 刪選掉自己的订单
        $data = $tradeModel->join('__CC_COMPLETE__ b ON a.uid=b.uid')
                           ->field('a.currency_type,a.id,a.uid,a.start_hide_hour,a.end_hide_hour,a.leave_num,a.success_num,a.price,a.num,a.money,b.small_order_time,b.break_order_time')
                           ->where($where)->order('price DESC')
                           ->limit(20)
                           ->select();
        if(empty($data)) $data = array();
        $publicObj           = new PublicFunctionController();
		$riskUserArr         = $publicObj->getRiskUserList();// 获取存在风险的用户

        foreach($data as $key=>$value)
        {
            if($newCcTrade[$value['currency_type']] > ($value['leave_num'] * $value['price'])){
                unset($data[$key]);
                continue;
            }
            if (!empty($riskUserArr) && in_array($value['uid'], $riskUserArr)) {
				unset($data[$key]);
				continue;
			}
			unset($data[$key]['uid']);
            $data[$key]['money'] = big_digital_mul($value['leave_num'],$value['price'],2);
            $data[$key]['completion'] = $value['leave_num']/$value['num'];
            if($value['small_order_time']-$value['break_order_time'] > 0){
                $data[$key]['complete_rate'] = ($value['small_order_time']-$value['break_order_time'])/$value['small_order_time'];
                $data[$key]['complete_rate'] = !empty($value['small_order_time']) ? $data[$key]['complete_rate'] : 0;
            } else{
                $data[$key]['complete_rate'] = 0;
            }
            $data[$key]['complete_rate'] = number_format($data[$key]['complete_rate']*100,2).'%';
            $data[$key]['total_order'] = $value['small_order_time'];
            $data[$key]['leave_num'] = $value['leave_num'];
            if($this->checkOvertime($value['start_hide_hour'],$value['end_hide_hour'])){
                unset( $data[$key]);
            }
        }
        $this->msgArr['data'] = array_values($data);
        return $this->msgArr;
    }
    
    /**@卖单列表
     * @param $where
     * @return mixed
     * @author zhangxiwen
     * @desc   獲取銷售列表
     * 规则
     *      type =2 卖单
     *      状态为挂单中
     *      时间为48小时以内
     *      不展示本人的订单
     */
    public function sellOrderList($where){
        $tradeModel = M('Cc_order')->alias('a');
        $where['a.type'] = 2;
        $where['b.order_display'] = 1;
        $where['a.status'] = array('EQ',"1"); // 刪選掉已完成的訂單
        //$time = time() - 48*3600;
        //$where['a.add_time'] = array('EGT',$time); // 筛选未超时的订单
        $ccTrade = M('CcConfig')->select();
        $newCcTrade = null;
        foreach ($ccTrade as $item){
            $newCcTrade[$item['currency_id']] = $item['min_trade_money'];
        }

        $where['a.uid']  = array('NEQ',getUserId()); // 刪選掉自己的订单
        $data = $tradeModel->join('__CC_COMPLETE__ b ON a.uid=b.uid')
                           ->field('a.currency_type,a.id,a.uid,a.start_hide_hour,a.end_hide_hour,a.leave_num,a.success_num,a.price,a.num,a.money,b.small_order_time,b.break_order_time')
                           ->where($where)
                           ->order('price ASC')
                           ->limit(20)
                           ->select();
        $currentH = date('H');
        if(empty($data)) $data = array();
        $publicObj           = new PublicFunctionController();
		$riskUserArr         = $publicObj->getRiskUserList();// 获取存在风险的用户
        foreach($data as$key=>$value)
        {
            if($newCcTrade[$value['currency_type']] > ($value['leave_num'] * $value['price'])){
                unset( $data[$key]);
                continue;
            }

            if (!empty($riskUserArr) && in_array($value['uid'], $riskUserArr)) {
				unset($data[$key]);
				continue;
			}
			unset($data[$key]['uid']);
            $data[$key]['money'] = big_digital_mul($value['leave_num'],$value['price'],2);
            $data[$key]['completion'] = $value['leave_num']/$value['num'];
            if($value['small_order_time']-$value['break_order_time'] > 0){
                $data[$key]['complete_rate'] = ($value['small_order_time']-$value['break_order_time'])/$value['small_order_time'];
                $data[$key]['complete_rate'] = !empty($value['small_order_time']) ? $data[$key]['complete_rate'] : 0;
            } else{
                $data[$key]['complete_rate'] = 0;
            }
            $data[$key]['total_order'] = $value['small_order_time'];
            $data[$key]['leave_num'] = $value['leave_num'];
            $data[$key]['complete_rate'] = number_format($data[$key]['complete_rate']*100,2).'%';
            if($this->checkOvertime($value['start_hide_hour'],$value['end_hide_hour'])){
                unset( $data[$key]);
            }
        }
        $this->msgArr['data'] = array_values($data);
        return $this->msgArr;
    }

    /**
     * 刷单程序
     * @param $orderList
     * @param string $opera
     * @param int $orderType  订单类型：1 买单 2 卖单
     * @return array
     */
    function scalpingOrder($orderList,$orderType=1,$om,$currency_type){
        $orderNum = 5;//生成订单的条数
        // 判断刷单是否开启，如果开启，获取买卖单浮动比例
        $orderType = $orderType*1;
        if(empty($orderList) or $orderType<1) return $orderList;
        $priceFloatingRatioStart = 0;
        $priceFloatingRatioEnd   = 0;
        $whereConfig['key'] = 'CC_PRICE_FIX';
        $ccPriceFixConfigInfo = M('InterfaceConfig')->where($whereConfig)->field('value')->find();
        if(empty($ccPriceFixConfigInfo) or $ccPriceFixConfigInfo['value']<2) return $orderList;
        $ccPriceFix = $ccPriceFixConfigInfo['value'];
        //1 关闭，不处理
        if($ccPriceFix == self::CLOST_PRICE ) return $orderList;
        $jsonScalpingOrderList = $this->redis->get(redisKeyNameLibrary::CC_SCALPING_ORDER_LIST.$orderType.$om.$currency_type);
        if (empty($jsonScalpingOrderList)){
            $wherePricefix['type']   = $orderType;
            $ccPriceFixInfo = M('CcPricefix')->where($wherePricefix)->find();
            if(empty($ccPriceFixInfo))  return $orderList;
            //2升价
            if($ccPriceFix == self::RAISE_PRICE ) {
                $priceFloatingRatioStart    = 1 + $ccPriceFixInfo['high_rate_start'];
                $priceFloatingRatioEnd      = 1 + $ccPriceFixInfo['high_rate_end'];
            }
            //3降价
            if($ccPriceFix == self::REDUCE_PRICE ) {
                $priceFloatingRatioStart    = 1 - $ccPriceFixInfo['low_rate_start'];
                $priceFloatingRatioEnd      = 1 - $ccPriceFixInfo['low_rate_end'];
            }
            //获取平均价
            $priceArr =  array_column($orderList,'price');
            sort($priceArr);
            if(count($orderList) > 3) {
                unset($priceArr[0]);
                array_pop($priceArr);
            }
            $averagePrice = big_digital_div(array_sum($priceArr),count($priceArr));
            // 生成5条刷单数据
            $arrMode = $orderList[0];
            $priceGradient = round(($priceFloatingRatioEnd - $priceFloatingRatioStart) / ($orderNum-1),4);//价格变化梯度
            $scalpingOrderList = array();
            $userNameArr = array('杨','刘','王','陈',
                '张','钱','欧阳','李',
                'Emma','Joyce','Loren',
                'Larissa','Solomon','Wilson','Albert'
            ,'Kenny','Bill','Albert','Kevin','Johnny','Alex');
            $bankList =   M('BankList')->where(array('country_code'=>'+'.$om))->order('rand()')->select();
            $bankIdArr = array_column($bankList,'id');
            for($i=0;$i<$orderNum;$i++){
                $tempPrice = round( $averagePrice*($priceFloatingRatioStart+($priceGradient*$i)),2);
                $tempArr = $arrMode;
                $tempArr['id'] = 'S00'.$i.$orderType.rand(1,9).time();
                $tempArr['start_hide_hour'] = 0;
                $tempArr['end_hide_hour'] = 0;
                $tempArr['num'] = round(rand(1,4)*1000/$averagePrice,2);
                $tempArr['leave_num'] = abs($tempArr['num'] - rand(1,6));
                $tempArr['success_num'] = abs($tempArr['num']- $tempArr['leave_num']);
                $tempArr['price'] = $tempPrice;
                $tempArr['money'] = round($tempArr['leave_num']*$tempArr['price'],2);
                $tempArr['small_order_time'] =  rand(80,100);
                $tempArr['break_order_time'] = rand(1,10);
                $tempArr['complete_rate'] = round((($tempArr['small_order_time']-$tempArr['break_order_time'])/$tempArr['small_order_time'])*100,2).'%';
                $tempArr['break_order_time'] = rand(1,5);
                $tempArr['completion'] = '0.'.rand(1,9);
                $tempArr['total_order'] = $tempArr['small_order_time'] ;
                $tempArr['uid'] = 'U00'.$i.rand(1,9);;
                $tempArr['username'] = $userNameArr[array_rand($userNameArr,1)]."**";
                $tempArr['bank_id'] = $bankIdArr[array_rand($bankIdArr,1)];
                $tempArr['bank_num'] = '*****'.rand(2000,5000);
                $tempArr['om'] = $om;
                $this->redis->setex(redisKeyNameLibrary::CC_SCALPING_ORDER_INFO.$orderType.$tempArr['id'],
                    60*5,json_encode($tempArr));
                $scalpingOrderList[] = $tempArr;
            }
            $jsonScalpingOrderList = json_encode($scalpingOrderList);
            $this->redis
                 ->setex(redisKeyNameLibrary::CC_SCALPING_ORDER_LIST.$orderType.$om.$currency_type,
                15,$jsonScalpingOrderList);
        }else{
            $scalpingOrderList = json_decode($jsonScalpingOrderList,1);
        }
               
        if(!empty($scalpingOrderList)){
            foreach ($scalpingOrderList as $key => $item){
                unset( $scalpingOrderList[$key]['uid']);
                unset( $scalpingOrderList[$key]['username']);
                unset( $scalpingOrderList[$key]['bank_id']);
                unset( $scalpingOrderList[$key]['bank_num']);
            }
        }
        //重新排序
        $orderList = array_merge($orderList,$scalpingOrderList);
        //订单类型：1 买单，降序， 2 卖单，升序
        if($orderType == 1) {
            $orderList = array_orderby($orderList,'price',SORT_DESC);
        }else{
            $orderList = array_orderby($orderList,'price',SORT_ASC);
        }
        
        return $orderList;
    }
    /*
     * 检测子订单
     */
    private function checkSubOrders($subOrders){
        if( count($subOrders) == 0 ){
            return 1;//没有子订单 退还保证金
        }
        foreach ($subOrders as $order){
            if( in_array($order['status'],[1,2,5]) ){
                return false;//有未完成的订单 不退
            }else{
                continue;
            }
        }
        return true;
    }
    /*
     *  李江 2018年2月27日18:09:58
     *  撤销订单业务逻辑层 orderNum  主订单id
     */
    public function revokeBigOrder($orderId,$userId=1){
        $resArr = [];
        $saveData = [
            'status' => 3,
            'update_time' =>time(),
        ];
        $bigOrder = M('CcOrder')->where(['id'=>$orderId])->find();
        if( $userId != $bigOrder['uid'] ){
            $this->msgArr['code'] = 404;
            $this->msgArr['msg'] = L('_BNCZZJDDD_');
            return $this->msgArr;
        }
        if( $bigOrder['status'] != 1 ){
            $this->msgArr['code'] = 403;
            $this->msgArr['msg']  = L('_CXSBQSHCS_');//您的订单不可撤销
            return $this->msgArr;
        }
        //防止并发执行  加redis锁机制
        // $redisObj = new RedisCluster();
        $redisInstance = RedisCluster::getInstance();
        $revokeRedisKey = 'RevokeRedisKey'.$orderId.$userId;
        if($redisInstance->get($revokeRedisKey)){
            $this->msgArr['code'] = 404;
            $this->msgArr['msg'] = L('_CXSBQSHCS_');//您的订单不可撤销
            return $this->msgArr;
        }

        $orderNum = $bigOrder['order_num'];
        if( !$bigOrder ){
            $this->msgArr['code'] = 402;
            $this->msgArr['msg']  = L('_ZDDBCZ_');
            return $this->msgArr;
        }
   
        $currency_id = $bigOrder['currency_type'];
        $extArr = [
            'type'   => 1,
            'remarkInfo'=>$orderNum,
            'opera'  => 'inc'
        ];

        //分挂买单和挂卖单退还
        //挂买单
        if($bigOrder['type'] == 1){
            //如果没有子订单
            $subOrders= M('CcTrade')->where(['pid'=>$orderId])->select();
            //有子订单 判断子订单是否违规
            $returnFlag = $this->checkSubOrders($subOrders);
            
            if($returnFlag && $bigOrder['is_break'] == 0 && $bigOrder['bond_num'] > 0){
                //退还保证金
                $bondNum = $bigOrder['bond_num'];
                $extArr['content'] = "C2C挂买单保证金返还";//C2C挂买单保证金返还
                $extArr['money'] = $bondNum;
                $extArr['userId'] = $userId;
                $res = $this->calCurrencyNumAndAddLog($currency_id,22,$extArr);//返还保证金
                
                if($res['code'] == 200 ){
                    $saveData['is_break']=1;  //已退还用户保证金
                    $resArr[] = 1;
                }else{
                	$resArr[] = 0;
                }
            }
        }else{
            //挂卖单
            $leaveNum = $bigOrder['leave_num'];
            $leaveFee = $bigOrder['leave_fee'];
            if( $leaveNum <= 0){
                $this->msgArr['code'] = 401;
                $this->msgArr['msg']  = L('_NDDDBKCX_');
                return $this->msgArr;
            }
            //1、退手续费
            $extArr['content'] = "C2C交易单手续费返还";//C2C交易手续费返还
            $extArr['money'] = $leaveFee;
            $extArr['userId'] = $userId;
            if( $leaveFee > 0 ){
                $res = $this->calCurrencyNumAndAddLog($currency_id,24,$extArr);//退手续费
                if( $res['code'] != 200 ){
                    $resArr[] = 0;
                }else{
                	$resArr[] = 1;
                }
            }
            //2、退剩余的数量
            $extArr['content'] = 'C2C交易单(卖)撤销返还币';//C2C挂单撤销返还币
            $extArr['money'] = $leaveNum;
            $extArr['userId'] = $userId;
            $res = $this->calCurrencyNumAndAddLog($currency_id,20,$extArr);//退剩余数量
            if( $res['code'] != 200 ){
                $resArr[] = 0;
            }else{
            	$resArr[] = 1;
            }
        }
        
        $resArr[] = M('CcOrder')->where(['id'=>$orderId])->save($saveData);  //更新主订单数据
        
        if(!in_array(false,$resArr) ){
            $this->msgArr['code'] = 200;
            $this->msgArr['msg'] = L('_CXDDCG_');
            //设置redis 阻止下次点击
            $redisInstance->setex($revokeRedisKey,8,1);
            return $this->msgArr;
        }

        $this->msgArr['code'] = 409;
        $this->msgArr['msg'] = L('_CXDDFSYC_');
        return $this->msgArr;
    }

    /**
     * 获取用户交易中的挂单列表
     * 挂单状态：
     *          1挂单中 2 完成 3用户撤销 4系统自动撤销
     * 交易订单状态：
     *          1买入成功 2买家确认打款 3卖家确认收款 4.超时自动撤销
     *          5.待处理 6.管理员撤销订单 7.管理员完成订单
     *  @author 刘富国
     * 20180227
     * @param $userId
     * @param $type 1表示正在交易的挂单,历史订单：2已经完成交易订单，3，已撤销的交易订单
     * @return array|bool
     */
    public function getUserMainOrderList($userId) {
        //需求订单
        $ret = $this->getCcMainOrderList($userId);
        $ccMainOrderList = $ret['ccMainOrderList'];
        $tradePidArr    = $ret['tradePidArr'];

        // 网站维护
        $isMaintain = $this->checkWebMaintain(3);

        if ($isMaintain['code'] != 200) {
        	$this->msgArr['code'] = 200;
        	return $this->msgArr;
        }

        $ccMainOrderPidArr = array_column($ccMainOrderList,'pid');
        //个人交易订单
        $selfTradeOrderArr = array_diff($tradePidArr,$ccMainOrderPidArr);

        if(!empty($selfTradeOrderArr)){
            $selfTradeOrderList = $this->getSelfTradeOrderList($userId,$selfTradeOrderArr);
            $ccMainOrderList = array_merge($ccMainOrderList,$selfTradeOrderList);//合并到主单
        }
        if (empty($ccMainOrderList)) return $this->msgArr;
        //需处理订单
        $proceMainOrderIdArr = array();
        if(!empty($tradePidArr)){
            $proceMainOrderIdArr = $this->getProceMainOrderIdArr($userId);
        }
        //整理输出格式
        $rateArr = $this->getConfigHUILV();
        //订单是否暂停交易
        $checkDisplay = $this->checkUserOrderDisplay($userId);
        foreach ($ccMainOrderList as $key => $item) {
            //需处理订单：1要处理
            $penging = 0;
            if(!empty($proceMainOrderIdArr)   ){
                if(!empty($item['order_id'])
                    and  in_array($item['order_id'], $proceMainOrderIdArr['order_id'])){
                    $penging = 1;
                }elseif(empty($item['order_id'])
                    and  in_array($item['pid'], $proceMainOrderIdArr['pid'])){
                    $penging = 1;
                }
            }
            $ccMainOrderList[$key]['penging'] = $penging;
            //是否是用户自己的挂单，如果不是，不要显示撤销按钮和完成数量
            $selfTradeorder = 0;
            if($item['order_id'] > 0) $selfTradeorder = 1;
            $ccMainOrderList[$key]['self_trade_order'] = $selfTradeorder;
            $ccMainOrderList[$key]['currency_name'] = getCurrencyName($item['currency_type']);
            //显示撤销按钮,订单状态
            $ccMainOrderList[$key]['opt_str'] = '';
            $ccMainOrderList[$key]['cancel_enable'] = 0;
            $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[1];
            if ($selfTradeorder == 0   ) {
                if($item['status'] == 1){ //进行中的订单--> 进行中
                    $ccMainOrderList[$key]['opt_str'] = L('_CHEXIAO_');
                    $ccMainOrderList[$key]['cancel_enable'] = 1;
                }elseif(in_array($item['status'],array(3,4))){ //撤销订单--> 待撤销
                    $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[2];
                    $ccMainOrderList[$key]['cancel_enable'] = 0;
                }elseif($item['status'] == 2){ //完成订单--> 进行中
                    $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[1];
                    $ccMainOrderList[$key]['cancel_enable'] = 0;
                }
                if(!$checkDisplay)  $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[3];
            }
            //订单类型
            $type = $item['type'];
            if(empty($item['order_id'])){
                $type_name = $this->selfOrderNameArr[$selfTradeorder] . "(".$this->orderTradeTypeNameArr[$type].")";
            }else{
                $type_name =  $this->orderTradeTypeNameArr[$type];
            }
            $ccMainOrderList[$key]['type_name'] = $type_name;
            $ccMainOrderList[$key]['reference_price'] = big_digital_mul($rateArr[$item['om']], $item['money'], 2);//参考价格
            $ccMainOrderList[$key]['currency_symbol'] = $this->omOfCurrencySymbol[$item['om']];

            unset($ccMainOrderList[$key]['uid']);
            unset($ccMainOrderList[$key]['status']);
            unset($ccMainOrderList[$key]['om']);
        }
        //交易中的订单排前面
        $pengingArr = array_column($ccMainOrderList, 'penging');
        $updateTimeArr = array_column($ccMainOrderList, 'add_time');
        array_multisort($pengingArr, SORT_DESC, $updateTimeArr, SORT_DESC, $ccMainOrderList);
        $this->msgArr['code'] = 200;
        $this->msgArr['data'] = $ccMainOrderList;
        return $this->msgArr;
    }

    /**
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     * app获取用户主订单列表
     * @author zhangxiwen
     */
    public function getMyUserMainOrderListApp($userId,$page,$limit){
        //需求订单
        $tradingOrderStatusArr = array(1, 2, 5);//交易中的交易订单状态
        $tradeOrderWhere['sell_id|buy_id'] = $userId;
        //查出交易表里面交易中订单，对应主订单也要在“交易中”区域显示
        $tradeOrderWhere['status'] = array('in', $tradingOrderStatusArr);
        $ccTradeOrderList = M('CcTrade')
            ->field('pid')
            ->where($tradeOrderWhere)->group('pid')
            ->select();
        $tradePidArr = array();
        if (!empty($ccTradeOrderList)) {
            $tradePidArr = array_column($ccTradeOrderList, 'pid');
        }

        //$mainOrderWhereOrConditionGt['leave_num'] = array('gt',0);
        $mainOrderWhere['uid'] = $userId;
        $mainOrderWhereAndCondition['status'] = array('in', array(1));
        if (!empty($tradePidArr)) {
            $mainOrderWhereOrCondition['id'] = array('in', $tradePidArr);
            $mainOrderWhere['_complex'] = array(
                $mainOrderWhereAndCondition,
                $mainOrderWhereOrCondition,
                //$mainOrderWhereOrConditionGt,
                '_logic' => 'or'
            );
        }else{
            $mainOrderWhere[]  = $mainOrderWhereAndCondition;
        }
        $mainOrderBy = 'add_time desc';
        $fieldStr = 'id,0 order_id,currency_type,type,status,price,num,leave_num,money,add_time,uid,type,om';
        $count = M('CcOrder')
            ->where($mainOrderWhere)
            ->order($mainOrderBy)
            ->count();

        $ccMainOrderList = M('CcOrder')->field($fieldStr)
            ->where($mainOrderWhere)
            ->order($mainOrderBy)
            ->limit($limit)
            ->page($page)
            ->select();

        $ret['ccMainOrderList'] = $ccMainOrderList;
        $ret['tradePidArr'] = $tradePidArr;

        $ccMainOrderList = $ret['ccMainOrderList'];
        $tradePidArr    = $ret['tradePidArr'];

        $ccMainOrderPidArr = array_column($ccMainOrderList,'pid');

        //需处理订单
        $proceMainOrderIdArr = array();
        if(!empty($tradePidArr)){
            $proceMainOrderIdArr = $this->getProceMainOrderIdArr($userId);
        }
        //整理输出格式
        $rateArr = $this->getConfigHUILV();
        //订单是否暂停交易
        $checkDisplay = $this->checkUserOrderDisplay($userId);
        foreach ($ccMainOrderList as $key => $item) {
            //需处理订单：1要处理
            $penging = 0;
            if(!empty($proceMainOrderIdArr)){
                if (  in_array($item['pid'], $proceMainOrderIdArr)
                    or in_array($item['order_id'], $proceMainOrderIdArr)) {
                    $penging = 1;
                }
            }
            $ccMainOrderList[$key]['penging'] = $penging;
            //是否是用户自己的挂单，如果不是，不要显示撤销按钮和完成数量
            $selfTradeorder = 0;
            if($item['order_id'] > 0) $selfTradeorder = 1;
            $ccMainOrderList[$key]['self_trade_order'] = $selfTradeorder;
            $ccMainOrderList[$key]['currency_name'] = getCurrencyName($item['currency_type']);
            //显示撤销按钮,订单状态
            $ccMainOrderList[$key]['opt_str'] = '';
            $ccMainOrderList[$key]['cancel_enable'] = 0;
            $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[1];

            if($item['status'] == 1){ //进行中的订单--> 进行中
                $ccMainOrderList[$key]['opt_str'] = L('_CHEXIAO_');
                $ccMainOrderList[$key]['cancel_enable'] = 1;
            }elseif(in_array($item['status'],array(3,4))){ //撤销订单--> 待撤销
                $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[2];
                $ccMainOrderList[$key]['cancel_enable'] = 0;
            }elseif($item['status'] == 2){ //完成订单--> 进行中
                $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[1];
                $ccMainOrderList[$key]['cancel_enable'] = 0;
            }
            if(!$checkDisplay)  $ccMainOrderList[$key]['opt_status_str'] = $this->optStatusNameArr[3];


            //订单类型
            $type = $item['type'];
            $type_name = $this->selfOrderNameArr[$selfTradeorder] . $this->orderTradeTypeNameArr[$type];
            $ccMainOrderList[$key]['type_name'] = $type_name;
            $ccMainOrderList[$key]['reference_price'] = big_digital_mul($rateArr[$item['om']], $item['money'], 2);//参考价格
            $ccMainOrderList[$key]['currency_symbol'] = $this->omOfCurrencySymbol[$item['om']];

            unset($ccMainOrderList[$key]['uid']);
            unset($ccMainOrderList[$key]['status']);
            unset($ccMainOrderList[$key]['om']);

            unset($ccMainOrderList[$key]['order_id']);
            unset($ccMainOrderList[$key]['currency_type']);
            unset($ccMainOrderList[$key]['add_time']);
            unset($ccMainOrderList[$key]['penging']);
            unset($ccMainOrderList[$key]['cancel_enable']);
            unset($ccMainOrderList[$key]['type_name']);

        }
        //交易中的订单排前面
        $pengingArr = array_column($ccMainOrderList, 'penging');
        $updateTimeArr = array_column($ccMainOrderList, 'add_time');
        array_multisort($pengingArr, SORT_DESC, $updateTimeArr, SORT_DESC, $ccMainOrderList);
        $this->msgArr['code'] = 200;
        $this->msgArr['data']['count'] = $count;
        $this->msgArr['data']['list'] = $ccMainOrderList;
        return $this->msgArr;
    }

    /**
     * 获取需求订单
     * @param $userId
     * @return mixed
     * @author 刘富国
     * 20180227
     */
    private function getCcMainOrderList($userId){
        $tradingOrderStatusArr = array(1, 2, 5);//交易中的交易订单状态
        $tradeOrderWhere['sell_id|buy_id'] = $userId;
        //查出交易表里面交易中订单，对应主订单也要在“交易中”区域显示
        $tradeOrderWhere['status'] = array('in', $tradingOrderStatusArr);
        $ccTradeOrderList = M('CcTrade')
            ->field('pid')
            ->where($tradeOrderWhere)->group('pid')
            ->select();
        $tradePidArr = array();
        if (!empty($ccTradeOrderList)) {
            $tradePidArr = array_column($ccTradeOrderList, 'pid');
        }

        $mainOrderWhere['uid'] = $userId;
        $mainOrderWhereAndCondition['status'] = array('in', array(1));
        if (!empty($tradePidArr)) {
            $mainOrderWhereOrCondition['id'] = array('in', $tradePidArr);
            $mainOrderWhere['_complex'] = array(
                $mainOrderWhereAndCondition,
                $mainOrderWhereOrCondition,
                '_logic' => 'or'
            );
        }else{
            $mainOrderWhere[]  = $mainOrderWhereAndCondition;
        }
        $mainOrderBy = 'add_time desc';
        $fieldStr = 'id as pid,0 order_id,currency_type,type,status,price,num,leave_num,money,add_time,uid,type,om';
        $ccMainOrderList = M('CcOrder')->field($fieldStr)
            ->where($mainOrderWhere)
            ->order($mainOrderBy)
            ->select();
        $ret['ccMainOrderList'] = $ccMainOrderList;
        $ret['tradePidArr'] = $tradePidArr;
        return $ret;
    }

    /**
     * 获取个人交易订单
     * @param $userId
     * @param $selfTradeOrderArr
     * @return mixed
     * @author 刘富国
     * 20180227
     */
    private  function getSelfTradeOrderList ($userId, $selfTradeOrderArr) {
        $whereSelf['status'] = array('in', array(1,2,5));
        $whereSelf['pid'] = array('in', $selfTradeOrderArr);
        $whereSelf['sell_id|buy_id'] = $userId;
        $selfTradeOrderList = M('CcTrade')
            ->field("pid,id as  order_id,currency_type,type,status,trade_price as price,om,
                     trade_num as num, 0 leave_num,
                    trade_money as money,
                     trade_time as add_time,0 as uid")
            ->where($whereSelf)
            ->select();
        return $selfTradeOrderList;
    }

    /**
     * 需处理订单
     * @param $userId
     * @return array|bool
     * @author 刘富国
     * 20180227
     */
    private function getProceMainOrderIdArr($userId){
        $tradingOrderStatusArr = array(1, 2);//交易中的交易订单状态
        $tradeOrderWhere['sell_id|buy_id'] = $userId;
        $tradeOrderWhere['status'] = array('in', $tradingOrderStatusArr);
        $proceMainOrderIdList = M('CcTrade')
            ->field('pid,id as order_id,sell_id,buy_id,status,trade_time,shoukuan_time,type,om')
            ->where($tradeOrderWhere)
            ->select();
        if(empty($proceMainOrderIdList)) return false;
        $proceMainOrderIdArr = array();
        //获取超时配置
        $restTimeArr = $this->getCcTimeLimit();
        $remitRestTime = $restTimeArr['CC_REMIT_TIME']; //打款超时时间（小时）
        $receiptRestTime = $restTimeArr['CC_RECEIPT_TIME']; //收款超时时间（小时）
        foreach ($proceMainOrderIdList as $key => $item){
            //剩余打款时间
            if ($item['status'] == 1 ) {
                $timeLimit = $remitRestTime * 60 * 60 - (time() - $item['trade_time']);
                if ($timeLimit > 0 and $item['buy_id'] == $userId ){
                    $proceMainOrderIdArr['order_id'][]= $item['order_id'];
                    $proceMainOrderIdArr['pid'][] = $item['pid'];
                }
            }
            //剩余收款时间
            if ($item['status'] == 2) {
                $timeLimit = $receiptRestTime * 60 * 60 - (time() - $item['shoukuan_time']);
                if ($timeLimit > 0 and $item['sell_id'] == $userId )   {
                    $proceMainOrderIdArr['order_id'][]= $item['order_id'];
                    $proceMainOrderIdArr['pid'][] = $item['pid'];
                }
            }
        }
        return $proceMainOrderIdArr;
    }


    /**
     * 获取用户子订单列表
     *  @author 刘富国
     * 20180227
     * @param $pid
     * @param $userId
     * @return array
     */
     public function  getUserTradeOrderList($userId,$pid,$orderId){
         //查出交易表里面交易中订单
         $tradeOrderWhere['pid'] = $pid;
         $tradeOrderWhere['status'] =  array('in',array(1, 2, 5));//交易中的交易订单状态
         $tradeOrderWhere['sell_id|buy_id'] = $userId;
         if(!empty($orderId)) $tradeOrderWhere['id'] = $orderId;
         $ccTradeOrderList = M('CcTrade')
             ->where($tradeOrderWhere)
             ->order('update_time desc,id desc')
             ->select();
         if(empty($ccTradeOrderList)) return $this->msgArr;
         $userBankIdArr = array_column($ccTradeOrderList,'user_bank_id');
         //用户银行账户信息
         $retUserBankList = $this->userBankObj->getUserBankByUserBankId($userBankIdArr);
         $userBankList = array();
         foreach ($retUserBankList as $key => $item){
             $userBankList[$item['user_bank_id']] = $item;
         }
         $retCcTradeOrderList = array();
         //获取超时配置
         $restTimeArr = $this->getCcTimeLimit();
         $remitRestTime = $restTimeArr['CC_REMIT_TIME']; //打款超时时间（小时）
         $receiptRestTime = $restTimeArr['CC_RECEIPT_TIME']; //收款超时时间（小时）
         $unreceiptRestTime = $restTimeArr['CC_UNRECEIPT_TIME']; //收款异常时间（小时）
         // 获取汇率
         $rateArr = $this->getConfigHUILV();
         //获取用户银行卡开户名
         $sellIdArr = array_column($ccTradeOrderList,'sell_id');
         $buyIdArr = array_column($ccTradeOrderList,'buy_id');
         $userIdArr = array_unique(array_merge($sellIdArr,$buyIdArr));
         $userBankRealNameList = $this->userModel->getUserRealList($userIdArr,'u.uid,bank_name as bank_real_name,card_name');
         $userBankRealNameArr = array_column($userBankRealNameList,'bank_real_name','uid');
         $userCardNameArr = array_column($userBankRealNameList,'card_name','uid');
         foreach ($ccTradeOrderList as $key => $item){
            $ret_value = array();
            //用户卖家，银行账号不需要全显示
            $userBankInfo = $userBankList[$item['user_bank_id']];
            $bankNum =  $userBankInfo['bank_num'];
            if($item['sell_id'] == $userId)  $bankNum ='***************'.substr($bankNum,-4);
            if($item['buy_id'] == $userId and $item['status'] > 1) $bankNum = '';
            $ret_value['order_id']      = $item['id']; //订单ID
            $ret_value['order_num']      = $item['order_num']; //订单ID
            $ret_value['bank_num']      = $bankNum; //银行账号
            $ret_value['bank_name']     =  formatBankType($userBankInfo['bank_list_id']);  //银行名称（汇款方式）
            $ret_value['bank_address']     = $userBankInfo['bank_address'];  //银行地址
            $ret_value['om_name']       = $this->areaOmName[$item['om']];  //交易区
            $ret_value['trade_num']     = $item['trade_num'];   //成交数量
            $ret_value['trade_price']   = $item['trade_price'];   //单价
            $ret_value['trade_money']   = $item['trade_money'];   //金额
            // $ret_value['reference_price']    = big_digital_mul($rateArr[$item['om']], $item['trade_money'], 2);//参考价格
            $ret_value['reference_price']    = $item['rate_total_money'];//参考总额
            $ret_value['om_of_currency_symbol']    = $this->omOfCurrencySymbol[$item['om']]; //交易区的货币符号
            $ret_value['currency_name'] = getCurrencyName($item['currency_type']); //货币名称
            $ret_value['status']        = $item['status'];    //状态值
            $ret_value['status_name']   = $this->orderTradeStatusNameArr[$item['status']];
            $ret_value['opt_str_unreceipt'] = '';//未收到款项按钮
            $ret_value['time_limit']    = 0; //剩余操作时间
             $ret_value['sell_username']   = $userCardNameArr[$item['sell_id']];
             $ret_value['buy_username']   = $userCardNameArr[$item['buy_id']];

             //订单类型，1 买单，2 卖单
             $order_type = 2; //卖
             $ret_value['bank_real_name'] = $userBankRealNameArr[$item['buy_id']]; // 买家银行卡开户名
             if($item['buy_id'] == $userId){
                 $order_type = 1;
                 $ret_value['bank_real_name'] = $userBankRealNameArr[$item['sell_id']]; // 卖家银行卡开户名
                 $ret_value['order_num'] = $item['order_num_buy']; // 买家订单编码
             }
            $ret_value['type']    = $order_type; //1买单 2卖单
            $ret_value['confirm_pay'] = 0;
            $ret_value['confirm_receivables'] = 0;
            $ret_value['penging'] = 0;  //需处理的订单
            $ret_value['remark'] = '';
            $ret_value['status_logo']   = '';//图标1 等待付款，2，等待收款，3，收款超时，4，收款异常
            $ret_value['opt_str_confirm'] = '';
            $ret_value['pay_type_name']   = L('_YINHANGHK_');//todo 支付方式，以后还会加，先写死
            $status_name = $this->orderTradeStatusNameArr[$item['status']]; //状态名称
            $temp_str = '';
            $temp_remark = '';
            $status_logo   = 3;
            //剩余打款时间
            if($item['status'] == 1 ){
                $timeLimit = $remitRestTime*60*60-(time()-$item['trade_time']);
                $ret_value['time_limit'] = $timeLimit;
                $status_logo   = 1;
                $temp_remark = L('_FKCSDDJZDQX_');//付款超时订单将自动取消
                if($timeLimit > 0 ) {
                    if($item['buy_id'] == $userId){
                        $ret_value['penging'] = 1;
                        $ret_value['opt_str_confirm'] = L('_QRYDK_');
                        $ret_value['confirm_pay'] = 1;
                        $status_name   = L('_DENGDFK_');//'等待付款';
                    }else{
                    	//$status_name   = L('_DDMJFK_');//等待买家付款
                        //改成等待付款  不要买家字样  2018年4月4日17:11:48
                        $status_name   = L('_DENGDFK_');//'等待付款';
                    }
                }
                if($timeLimit <= 0){
                    $temp_remark = L('_JJQXDD_');//即将取消订单
                     $status_logo  =3;
                     if($item['buy_id'] <> $userId){
                         $temp_str = L('_MAIJIA_');//买家
                     }
                     //  $status_name   = $temp_str.L('_FUKCS_');//付款超时
                     $status_name   = L('_FUKCS_');//付款超时
                }
            }
             //剩余收款时间
             if($item['status'] == 2){
                 $timeLimit = $receiptRestTime*60*60-(time()-$item['shoukuan_time']);
                 $ret_value['time_limit'] = $timeLimit;
                 $status_logo   = 1;
                 $temp_remark = L('_SKCSDDJZDFB_'); //收款超时订单将自动放币
                 if($timeLimit > 0 ) {
                     if( $item['sell_id'] == $userId){
                         $ret_value['penging'] = 1;
                         $ret_value['opt_str_confirm'] = L('_QRYSK_');
                         $ret_value['confirm_receivables'] = 1;
                         $status_name = L('_DDSK_');//等待收款
                         //超两小时，显示“未收到款项”按钮
                         if((time()-$item['shoukuan_time']) > 3600*$unreceiptRestTime) {
                             $ret_value['opt_str_unreceipt'] = L('_SKYC_');
                         }
                     }else{
                     	
                         //$status_name = L('_DDMJSK_');//等待卖家收款
                         //不要卖家两个字
                         $status_name = L('_DDSK_');//等待收款
                     }

                 }
                 if($timeLimit <= 0){
                     $status_logo   = 2;
                     $temp_remark =  L('_JJZDFB_'); //即将自动放币
                     if($item['sell_id'] <> $userId){
                         $temp_str = L('_MAIIJIA_');//卖家'
                     }
                     //$status_name   = $temp_str.L('_SHOUKCS_');//收款超时
                     $status_name   = L('_SHOUKCS_');//收款超时
                 }
             }

            if($item['status'] == 5){
                $status_logo  = 3;
                $temp_remark = L('_CDDYKFJRQQBDHCT_');//此订单由客服介入,请确保您的电话畅通
                if($item['sell_id']==$userId){
                    $status_name = L('_SKYC_');//收款异常
                } else{
                    //$status_name = L('_MJSKYC_');//卖家收款异常 
                    $status_name = L('_SKYC_');//收款异常
                }
            }
            $ret_value['status_logo']   = $status_logo;
            $ret_value['status_name']   = $status_name;
            $ret_value['remark']   = $temp_remark;
            $retCcTradeOrderList[] = $ret_value;
         }
         $pengingArr = array_column($retCcTradeOrderList, 'penging');
         array_multisort($pengingArr, SORT_DESC, $retCcTradeOrderList);
         $this->msgArr['data'] = $retCcTradeOrderList;
         return $this->msgArr;
     }


    /**
     *  用户打款
     * @author 刘富国
     * 20180228
     */
    public function confirmTradeOrderPaid($userId,$orderId){
        $where['buy_id'] = $userId;
        $where['id']     = $orderId;
        $where['status'] = 1;
        $orderRes        = M('CcTrade')->where($where)->find();
        if (!$orderRes){
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_GDDBKCZ_');
            return $this->msgArr;
        }
        //校验是否超时
        $restTimeArr = $this->getCcTimeLimit();
        $remitRestTime = $restTimeArr['CC_REMIT_TIME'];
        $timeLimit = $remitRestTime*60*60-(time()-$orderRes['trade_time']);
        if($timeLimit <= 0) {
            $this->msgArr['code'] = 302;
            $this->msgArr['msg'] = L('_CSWQRHK_');
            return $this->msgArr;
        }
        //校验是否重复打款
        $isTrue = $this->redis->get(redisKeyNameLibrary::CC_LINE_DAKUANG_ORDER.$orderId);
        if ($isTrue){
            $this->msgArr['code'] = 303;
            $this->msgArr['msg'] = L('_QWCFCZ_');
            return $this->msgArr;
        }
        $this->redis->setex(redisKeyNameLibrary::CC_LINE_DAKUANG_ORDER.$orderId, 10, true);
        //确认打款
        $whereComfirm['id'] = $orderRes['id'];
        $saveArr['shoukuan_time'] = time();
        $saveArr['status'] = 2;
        $res = M('CcTrade')->where($whereComfirm)->save($saveArr);
        if(!$res){
            $this->msgArr['code'] = 304;
            $this->msgArr['msg'] = L('_GDDBKCZ_');
            return $this->msgArr;
        }else{
            $this->msgArr['code'] = 200;
            return $this->msgArr;
        }
    }

    /**
     * 页码信息
     * @param unknown $curr_page
     * @param unknown $total_page
     */
    protected function _pager($curr_page, $total_page){
        $pager = array(
            'current_page' => $curr_page <= 0 ? 1 : $curr_page,
            'last_page'    => $curr_page - 1 <=0 ? '' : $curr_page - 1,
            'next_page'    => ($curr_page + 1 > $total_page) ? '' : $curr_page + 1,
            'total_pages'  => $total_page,
        );
        return $pager;
    }

    /**
     * 用户收款
     * @author lirunqing 2018-03-01T14:50:40+0800
     * @param  int $userId  用户id
     * @param  int $orderId 订单自增id
     * @return array
     */
    public function orderAccept($userId, $orderId){
		$tradeInfo = M('CcTrade')->find($orderId);

		if (empty($tradeInfo)) {
			$this->msgArr['code'] = 217;
  	    	$this->msgArr['msg']  = L('_NXZDDDBCZ_');
  	    	return $this->msgArr;
		}

		if ($tradeInfo['status'] != 2) {
			$this->msgArr['code'] = 218;
  	    	$this->msgArr['msg']  = L('_QWCFCZ_');

  	    	return $this->msgArr;
		}

		// 获取确认打款超时时间配置
		$ccLimit = $this->getCcTimeLimit();
		$sec     = $ccLimit['CC_RECEIPT_TIME'] * 3600;
		if ((time() - $tradeInfo['shoukuan_time']) >= $sec)  {
			$this->msgArr['code'] = 219;
  	    	$this->msgArr['msg']  = L('_CSWQRSK_');
  	    	return $this->msgArr;
		}

		$financeType = 27;
 		$extArr      = array(
			'content'    => 'C2C子订单入账',
			'type'       => 1,
			'money'      => $tradeInfo['trade_num'],
			'remarkInfo' => $tradeInfo['order_num'],
			'opera'      => 'inc',
			'userId'     => $tradeInfo['buy_id']
 		);

 		// 币入买家账及添加财务日志
 		$finRes = $this->calCurrencyNumAndAddLog($tradeInfo['currency_type'], $financeType, $extArr);

 		if (empty($finRes) || $finRes['code'] != 200) {
 			$this->msgArr['code'] = 220;
  	    	$this->msgArr['msg']  = L( '_QRSKSB_');
  	    	return $this->msgArr;
 		}

 		// 子订单卖出，主订单则是买入，判断是否需要退换保证金
 		if ($tradeInfo['type'] == 2) {
 			$bondRes = $this->checkIsReturnBondNum($tradeInfo['pid'], $tradeInfo['id'], $tradeInfo);

 			if (empty($bondRes)) {
 				$this->msgArr['code'] = 222;
	  	    	$this->msgArr['msg']  = L( '_QRSKSB_');
	  	    	return $this->msgArr;
 			}
 		}

 		// 收取买家手续费
 		if ($tradeInfo['buy_fee'] > 0) {
 			$financeType = 23;
 			$fee         = $tradeInfo['buy_fee'];
	 		$extArr      = array(
				'content'    => 'C2C交易手续费扣除',
				'type'       => 2,
				'money'      => $fee,
				'remarkInfo' => $tradeInfo['order_num'],
				'opera'      => 'dec',
				'userId'     => $tradeInfo['buy_id']
	 		);

	 		// 收取买家手续费及添加财务日志
	 		$feeLogRes = $this->calCurrencyNumAndAddLog($tradeInfo['currency_type'], $financeType, $extArr);
	 		if (empty($feeLogRes) || $feeLogRes['code'] != 200) {
	 			$this->msgArr['code'] = 221;
	  	    	$this->msgArr['msg']  = L( '_QRSKSB_');
	  	    	return $this->msgArr;
	 		}
 		}

		$where = array(
			'id' => $orderId
		);
		$saveArr = array(
			'end_time' => time(),
			'update_time'=>time(),
			'status'   => 3
		);
		$tradeRes = M('CcTrade')->where($where)->save($saveArr);

		if (empty($tradeRes)) {
 			$this->msgArr['code'] = 223;
  	    	$this->msgArr['msg']  = L( '_QRSKSB_');
  	    	return $this->msgArr;
 		}

 		// 子订单完成，总子订单数加1
		$this->checkUserIsExistComplete($tradeInfo['sell_id'], 1);
		$this->checkUserIsExistComplete($tradeInfo['buy_id'], 1);

		return $this->msgArr;
	}

	/**
	 * 检测是否退还保证金
	 * @author lirunqing 2018-03-06T18:07:31+0800
	 * @param  int $pid     主订单id
	 * @param  int $childId 子订单id
	 * @param  array $orderInfo 子订单信息
	 * @return bool
	 */
	private function checkIsReturnBondNum($pid, $childId, $orderInfo){

		$pWhere = array(
			'id' => $pid
		);
		$pRes = M('CcOrder')->where($pWhere)->find();
		// 主订单已扣除保证金或者已退还保证金，则不进行退还扣除操作
		if (empty($pRes) || $pRes['status'] == 1 || $pRes['bond_num'] <=0 || in_array($pRes['is_break'], array(1,2))) {
			return true;
		}

		$where = array(
			'pid'    => $pid,
			'id'     => array('neq', $childId),
			'status' => array('in', array(1,2,5)),
		);
		$childRes = M('CcTrade')->where($where)->count();

		// 子订单有未完成的,剔除本订单,则不进行退还扣除操作
		if (!empty($childRes)) {
			return true;
		}

		$financeType = 22;
		$financeContent = 'C2C挂单保证金返还';
		$extArr         = array(
			'content'    => $financeContent,
			'type'       => 1,
			'money'      => $pRes['bond_num'],
			'remarkInfo' => $pRes['order_num'],
			'opera'      => 'inc',
			'userId'     => $orderInfo['buy_id']
 		);
        $logRes = $this->calCurrencyNumAndAddLog($orderInfo['currency_type'], $financeType, $extArr);

        if (empty($logRes) || $logRes['code'] != 200) {
        	return false;
        }

        $pUpWhere = array(
			'id' => $pid
		);
		$pUpArr = array(
			'is_break' => 1
		);
		$pUpRes = M('CcOrder')->where($pUpWhere)->save($pUpArr);

		if (empty($pUpRes)) {
        	return false;
        }

        return true;
	}

    /**
     * 获取超时配置
     * * @author 刘富国
     * 20180228
     * @return array
     */
    public  function getCcTimeLimit(){
        $config_where['key'] = array('in',array('CC_REMIT_TIME','CC_RECEIPT_TIME','CC_UNRECEIPT_TIME'));
        $restTimeList =  M('Config')->where($config_where)->select();
        $restTimeArr = array_column($restTimeList,'value','key');
        return  $restTimeArr;
    }

    /**
     * 检测用户是否存在完成率表，如果不存在则新增
     * @author 2018-03-01T20:57:49+0800
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function checkUserIsExistComplete($userId, $zAdd=0){

    	$where = array(
    		'uid' => $userId
    	);
    	$complRes = M('CcComplete')->where($where)->find();

    	if (!empty($complRes) && !empty($zAdd)) {
    		M('CcComplete')->where($where)->setInc('small_order_time', 1);
    		return true;
    	}

    	if(!empty($complRes)) {
    		return true;
    	}

    	$num = 0;
    	if (!empty($zAdd)) {
    		$num = 1;
    	}

    	$addArr = array(
			'uid'              => $userId,
			'break_order_time' => 0,
			'small_order_time' => $num,
			'add_time'         => time(),
			'update_time'      => 0
		);
		$ret = M('CcComplete')->add($addArr);
		return true;
    }
    /**
     * 卖家未收到款项，子订单转为待处理
     * @author 刘富国
     * 20180301
     * @param $userId
     * @param $orderId
     * @return array|int
     */
    public function unReceiptTradeOrderPaid($userId,$orderId){
        $where['sell_id'] = $userId;
        $where['id']     = $orderId;
        $where['status'] = 2;
        $orderRes        = M('CcTrade')->where($where)->find();
        if (!$orderRes){
            $this->msgArr['code'] = 301;
            $this->msgArr['msg'] = L('_GDDBKCZ_');
            return $this->msgArr;
        }
        //校验是否超时
        $restTimeArr = $this->getCcTimeLimit();
        $receiptRestTime = $restTimeArr['CC_RECEIPT_TIME'];
        $timeLimit = $receiptRestTime*60*60-(time()-$orderRes['shoukuan_time']);
        if($timeLimit <= 0){
            $this->msgArr['code'] = 302;
            $this->msgArr['msg'] = L('_CSWQRHK_');
            return $this->msgArr;
        }
        //未收到款项
        $whereUnRece['id'] = $orderRes['id'];
        $saveArr['update_time'] = time();
        $saveArr['status'] = 5;  //待处理
        $saveArr['remark_info'] = '收款异常';
        $res = M('CcTrade')->where($whereUnRece)->save($saveArr);
        if(!$res){
            $this->msgArr['code'] = 303;
            $this->msgArr['msg'] = L('_GDDBKCZ_');
            return $this->msgArr;
        }
        $this->msgArr['msg'] = L('_CZCG_');
        $this->msgArr['code'] = 200;
        return $this->msgArr;
    }
    
    /**
     * @author 建强 
     * @method 检测用户是否在c2c交易禁止时间内
     * @param  $uid
     * @return Array
    */
    public function checkUserIsDuringTime($uid)
    {
    	 $ret=M('CcComplete')->field('cc_break_time,cc_break_num')->where(['uid'=>$uid])->find();
    	 if($ret['cc_break_num']>=3 && $ret['cc_break_time']+24*3600>time())
    	 {  
    	 	 $this->msgArr['code']=672;
    	 	 $this->msgArr['msg']=L('_YTJZJY_');
    	 	 return $this->msgArr;
    	 }
    	 $this->msgArr['code']=200;
         return $this->msgArr;    	  
    }
    /**
     * 根据用户id获取用户名
     * author zhangxiwen
    */
    private function getUserNameForUserId($uid){
        $user = M('User')->where(['uid'=>$uid])->field('username')->find();
        return $user['username'];
    }


    /**
     *  @author zhangxiwen
     * @methor 根据订单id获取订单数据（用于确认收款/确认未收到款项展示页面）
     * @param  $orderId
     * @return array
     */
    public function getTradeInfo($orderId,$uid){
    	
        $where['t.sell_id|t.buy_id'] = $uid;
        $where['t.id'] = $orderId;


        $orderRes = M('CcTrade')->alias('t')
            ->join('__CURRENCY__ as c on c.id=t.currency_type')
            ->join('__USER_BANK__ as userbank on userbank.id=t.user_bank_id')
            ->join('__BANK_LIST__ as bank on bank.id=userbank.bank_list_id')
            ->field('t.trade_time,t.om,t.end_time,t.order_num_buy,t.order_num,userbank.bank_address,t.trade_price,t.trade_time,
            t.buy_id,t.trade_num,t.update_time,t.trade_time,t.sell_id,t.type,t.status,trade_money,
            bank.bank_name,userbank.bank_num,userbank.bank_list_id,t.rate_total_money,
            userbank.bank_real_name,userbank.bank_address,t.shoukuan_time,c.currency_name')
            ->where($where)->find();
        if(empty($orderRes)){
            $this->msgArr['code']= 200;
            $this->msgArr['data']= array();
            return $this->msgArr;
        }

        $userData = $this->userModel->getUserReaDetail($orderRes['sell_id'],'r.card_name');
        $orderRes['status_name'] = $this->orderTradeStatusNameArr[$orderRes['status']];
        $orderRes['sell_username'] = $userData['card_name'];
        
        $userData = null;
        $userData = $this->userModel->getUserReaDetail($orderRes['buy_id'],'r.card_name');
        $orderRes['buy_username'] = $userData['card_name'];

        $order_type = 2; // 卖单
        if($orderRes['buy_id'] == $uid){
            $order_type = 1; // 买单
            $orderRes['order_num'] = $orderRes['order_num_buy'];
        }
        $orderRes['bank_name'] = formatBankType($orderRes['bank_list_id']);
        $orderRes['order_type'] = $order_type;

        $huilv = $this->getConfigHUILV();
        $orderRes['rate_money'] = $orderRes['rate_total_money'];//参考总额
        $orderRes['currency_symbol'] = $this->omOfCurrencySymbol[$orderRes['om']];

        $orderRes['area'] = $this->areaOmName[$orderRes['om']];
        $orderRes['trade_time'] = date('Y-m-d H:i:s',$orderRes['trade_time']);
//        $orderRes['end_time'] = date('Y-m-d H:i:s',$orderRes['end_time']);

        // 撤销时间和完成时间同一用update_time
//        if($orderRes['status']==4 || $orderRes['status']==6){
        $orderRes['update_time'] = date('Y-m-d H:i:s',$orderRes['update_time']);
//        }
        $order_type = 2;
        if($orderRes['buy_id'] == $uid){
            $order_type = 1;
        }
        $orderRes['type'] = $order_type;
        //获取超时配置
        $orderRes['opt_unreceipt_type'] = 0;
        $restTimeArr = $this->getCcTimeLimit();
        $remitRestTime = $restTimeArr['CC_REMIT_TIME']; //打款超时时间（小时）
        $receiptRestTime = $restTimeArr['CC_RECEIPT_TIME']; //收款超时时间（小时）
        $unreceiptRestTime = $restTimeArr['CC_UNRECEIPT_TIME']; //收款异常时间（小时）
        //剩余打款时间
        if($orderRes['status'] == 1
            and $orderRes['buy_id'] == $uid){
            $timeLimit = $remitRestTime*60*60-(time()-$orderRes['trade_time']);
            if($timeLimit > 0) {
                $orderRes['opt_str_confirm'] = L('_QRYDK_');
                $orderRes['time_limit'] = $timeLimit;
                $orderRes['confirm_pay'] = 1;
            } else{
                $orderRes['status_name'] = L('_MJFKCS_');//买家支付超时
            }
        }

        //剩余收款时间
        if($orderRes['status'] == 2
            and $orderRes['sell_id'] == $uid){
            $timeLimit = $receiptRestTime*60*60-(time()-$orderRes['shoukuan_time']);
            if($timeLimit > 0) {
                $orderRes['opt_str_confirm'] = L('_QRYSK_');
                $orderRes['time_limit'] = $timeLimit;
                $orderRes['confirm_receivables'] = 1;
            } else{
                if($orderRes['shoukuan_time']==0){
                    $orderRes['status_name'] = L( '_MJSKYC_');
                }
            }
            //超两小时，显示“未收到款项”按钮
            if((time()-$orderRes['shoukuan_time']) > 3600*$unreceiptRestTime) {
                $orderRes['opt_str_unreceipt'] = L( '_SKYC_');
                $orderRes['opt_unreceipt_type'] = 1;
            }
            if($orderRes['status']==5){
                $orderRes['status_name']  = L( '_MJSKYC_');
            }
        }
        unset($orderRes['buy_id']);
        unset($orderRes['status']);
        unset($orderRes['rate_total_money']);
        unset($orderRes['order_num_buy']);
        unset($orderRes['om']);
        $this->msgArr['code']= 200;
        $this->msgArr['data']= $orderRes;
        return $this->msgArr;
    }

    /**@desc  获取未完成的订单
     * @author zhangxiwen
     * @param $userId
     * @param $page
     * @param $limit
     * @return array
     */
    public function getHangIntheAirTradeOrder($userId){
        //查出交易表里面交易中订单
        $tradeOrderWhere['status'] =  array('in', [1,2,5]);;
        $tradeOrderWhere['sell_id|buy_id'] = $userId;

        $count = M('CcTrade')
            ->where($tradeOrderWhere)
            ->count();
        if(empty($count) or $count<1) return $this->msgArr;
        $ccTradeOrderList = M('CcTrade')
            ->where($tradeOrderWhere)
            ->select();
        $userBankIdArr = array_column($ccTradeOrderList,'user_bank_id');
        //用户银行账户信息
        $retUserBankList = $this->userBankObj->getUserBankByUserBankId($userBankIdArr);
        $userBankList = array();
        foreach ($retUserBankList as $key => $item){
            $userBankList[$item['user_bank_id']] = $item;
        }
        $retCcTradeOrderList = array();
        //获取超时配置
        $restTimeArr = $this->getCcTimeLimit();
        $remitRestTime = $restTimeArr['CC_REMIT_TIME']; //打款超时时间（小时）
        $receiptRestTime = $restTimeArr['CC_RECEIPT_TIME']; //收款超时时间（小时）
        $unreceiptRestTime = $restTimeArr['CC_UNRECEIPT_TIME']; //收款异常时间（小时）
        // 获取汇率
        $rateArr = $this->getConfigHUILV();
        foreach ($ccTradeOrderList as $key => $item){
            $ret_value = array();
            //用户卖家，银行账号不需要全显示
            $userBankInfo = $userBankList[$item['user_bank_id']];
            $bankNum =  $userBankInfo['bank_num'];
            if($item['sell_id'] == $userId)  $bankNum ='***************'.substr($bankNum,-4);
            if($item['buy_id'] == $userId and $item['status'] > 1) $bankNum = '';
            $ret_value['order_num']      = $item['order_num']; //订单ID
            $ret_value['order_id']      = $item['id']; //订单ID
            $ret_value['bank_num']      = $bankNum; //银行账号
            $ret_value['bank_real_name'] = $userBankInfo['bank_real_name']; // 汇款人
            $ret_value['bank_name']     = $userBankInfo['bank_name'].$userBankInfo['bank_address'];  //银行名称（汇款方式）
            $ret_value['om_name']       = $this->areaOmName[$item['om']];  //交易区
            $ret_value['trade_num']     = $item['trade_num'];   //成交数量
            $ret_value['trade_price']   = $item['trade_price'];   //单价
            $ret_value['trade_money']   = $item['trade_money'];   //金额
            $ret_value['reference_price']    = $item['rate_total_money'];//参考总额
            $ret_value['om_of_currency_symbol']    = $this->omOfCurrencySymbol[$item['om']]; //交易区的货币符号
            $ret_value['currency_name'] = getCurrencyName($item['currency_type']); //货币名称
            $ret_value['status']        = $item['status'];    //状态值
            $ret_value['status_name']   = $this->orderTradeStatusNameArr[$item['status']]; //状态名称
            $ret_value['opt_str_confirm']      = ''; //买家确认打款/收款按钮，如果为空则不要显示按钮
            $ret_value['opt_str_unreceipt'] = '';//未收到款项按钮
            $ret_value['time_limit']    = 0; //剩余操作时间
            $ret_value['type']    = $item['type']; //1买单 2卖单
            $ret_value['confirm_pay'] = 0;
            $ret_value['confirm_receivables'] = 0;
            $ret_value['shoukuan_time'] = $item['shoukuan_time'];
            $ret_value['penging'] = 0;
            $temp_str = '';
            $order_type = 2;
            $temp_remark = '';
            if($item['buy_id'] == $userId){
                $ret_value['order_num'] = $item['order_num_buy'];
                $order_type = 1;
            }
            //剩余打款时间
            if($item['status'] == 1 ){
                $timeLimit = $remitRestTime*60*60-(time()-$item['trade_time']);
                $ret_value['time_limit'] = $timeLimit;
                $ret_value['status_logo']   = 1;
                $temp_remark = L('_FKCSDDJZDQX_');  //付款超时订单将自动取消
                if($timeLimit > 0 ) {
                    if($item['buy_id'] == $userId){
                        $ret_value['penging'] = 1;
                        $ret_value['opt_str_confirm'] = L('_QRYDK_');
                        $ret_value['confirm_pay'] = 1;
                        $status_name   = L('_DENGDFK_');//'等待付款';
                    }else{
                        //$status_name   = L('_DDMJFK_');//等待买家付款
                        $status_name   = L('_DENGDFK_');//'等待付款';
                    }
                }
                if($timeLimit <= 0){
                    $temp_remark = L('_JJQXDD_');//即将取消订单
                    if($item['buy_id'] <> $userId){
                        $temp_str = L('_MAIJIA_');//买家
                    }
                    $ret_value['status_logo']   = 5;
                    //$status_name   = $temp_str.L('_FUKCS_');//付款超时
                    $status_name   = L('_FUKCS_');//付款超时
                }
            }
            //剩余收款时间
            if($item['status'] == 2){
                $timeLimit = $receiptRestTime*60*60-(time()-$item['shoukuan_time']);
                $ret_value['time_limit'] = $timeLimit;
                $ret_value['status_logo']   = 2;
                if($timeLimit > 0 ) {
                    $temp_remark = L('_SKCSDDJZDFB_'); //收款超时订单将自动放币
                    if( $item['sell_id'] == $userId){
                        $ret_value['penging'] = 1;
                        $ret_value['opt_str_confirm'] = L('_QRYSK_');
                        $ret_value['confirm_receivables'] = 1;
                        $status_name = L('_DDSK_');//等待收款
                        //超两小时，显示“未收到款项”按钮
                        if((time()-$item['shoukuan_time']) > $unreceiptRestTime*3600) {
                            $ret_value['opt_str_unreceipt'] = L('_SKYC_');
                        }
                    }else{
                        //$status_name = L('_DDMJSK_');//等待卖家收款
                        $status_name = L('_DDSK_');//等待收款
                    }

                }
                if($timeLimit <= 0){
                    $ret_value['status_logo']   = 3;
                    $temp_remark = L('_JJZDFB_'); //即将自动放币
                    if($item['sell_id'] <> $userId){
                        $temp_str = L('_MAIIJIA_');//卖家
                    }
                    $status_name   = $temp_str.L('_SHOUKCS_');//收款超时
                    $status_name   = L('_SHOUKCS_');//收款超时
                }
            }
            if($item['status']==5){
                $ret_value['status_logo']   = 4;
                $ret_value['status_type'] = 5;
                $temp_remark =  L('_CDDYKFJRQQBDHCT_');//此订单由客服介入，请确保您的电话畅通
                if($item['sell_id']==$userId){
                    $status_name = $ret_value['opt_str_confirm'] = L('_SKYC_');//收款异常
                } else{
                    $status_name = $ret_value['opt_str_confirm'] = L('_MJSKYC_');//卖家收款异常
                }

            }
            $ret_value['opt_str'] =  $temp_remark;
            $ret_value['status_name'] = $status_name;
            $ret_value['type'] = $order_type;

            unset($ret_value['bank_num']);
            unset($ret_value['bank_real_name']);
            unset($ret_value['bank_name']);
            unset($ret_value['om_name']); 
            unset($ret_value['confirm_receivables']);

            $retCcTradeOrderList[] = $ret_value;
        }
        $pengingArr = array_column($retCcTradeOrderList, 'penging');
        array_multisort($pengingArr, SORT_DESC, $retCcTradeOrderList);
        $this->msgArr['data']['list'] = $retCcTradeOrderList;
        return $this->msgArr;
    }

    /** 已完成的订单
     * @param $userId
     * @param $page
     * @param $limit
     * @return mixed
     * @author zhangxiwen
     */
    public function getCompletedTradeOrder($userId,$page,$limit){
        $tradeOrderWhere['c.shoukuan_time'] = array('gt',0);
        $tradeOrderWhere['c.status'] = array('in', array(3,7,4,8));//交易中的子订单状态
        $tradeOrderWhere['c.sell_id|c.buy_id'] = $userId;
        $count = M('CcTrade')->alias('c')
            ->where($tradeOrderWhere)
            ->count();

        $data = M('CcTrade')->alias('c')
            ->where($tradeOrderWhere)
            ->join('__CURRENCY__ as y ON y.id=c.currency_type')
            ->limit($limit)
            ->field('c.id as order_id,c.om,c.trade_money,c.buy_id,c.sell_id,c.trade_num,c.trade_price,c.update_time,y.currency_name,rate_total_money')
            ->order('c.update_time desc,c.id desc')
            ->page($page)
            ->select();

        $huilv = $this->getConfigHUILV();
        foreach($data as $key=>$item ){
            // 初始化值
            $data[$key]['rate_money'] = 0;
            $data[$key]['currency_symbol'] = null;
            $data[$key]['type_name'] = null;
            $data[$key]['order_type'] = null;

            $data[$key]['rate_money'] = $item['rate_total_money'];//参考总额
            $data[$key]['currency_symbol'] = $this->omOfCurrencySymbol[$item['om']];
            //订单类型
            $order_type = 2;
            if($item['buy_id'] == $userId) $order_type = 1;
            //完成时间
            if(empty($data[$key]['end_time']) or $data[$key]['end_time'] < 1){
                $data[$key]['end_time']  = $data[$key]['update_time'] ;
            }
            $data[$key]['type_name']     = $this->orderTradeTypeNameArr[$order_type];    //订单类型
            $data[$key]['order_type'] = $order_type;
            unset( $data[$key]['om']);
            unset( $data[$key]['buy_id']);
            unset( $data[$key]['rate_total_money']);
        }
        if(empty($data)){
            $data = array();
        }
        $result['data']['list'] = $data;
        $result['data']['count'] = $count;
        return $result;
    }

    /**获取撤销的订单
     * @param $userId
     * @param $page
     * @param $limit
     * @return mixed
     * @author zhangxiwen
     */
    public function getRevokeTradeOrder($userId,$page,$limit) {
        $tradeOrderWhere['c.sell_id|c.buy_id'] = $userId;
        $whereBuy['c.status'] = 4;
        $whereBuy['c.shoukuan_time'] = array('eq',0);
        $whereBuy['_logic'] = "AND";
        $whereBack['c.status'] = 6;
        $whereBack['_logic'] = "OR";
        $whereBack['_complex']=$whereBuy;
        $tradeOrderWhere['_complex']=$whereBack;
        $count = M('CcTrade')->alias('c')
            ->where($tradeOrderWhere)
            ->count();
        $data = M('CcTrade')->alias('c')
            ->where($tradeOrderWhere)
            ->join('__CURRENCY__ as y ON y.id=c.currency_type')
            ->order('c.update_time desc,c.id desc')
            ->limit($limit)
            ->field('c.id as order_id,c.om,c.trade_money,c.buy_id,
                c.trade_num,c.trade_price,c.status,y.currency_name,c.rate_total_money')
            ->page($page)
            ->select();

        $huilv = $this->getConfigHUILV();
        foreach($data as$key=>$item ){
            $data[$key]['rate_money'] = 0;
            $data[$key]['currency_symbol'] = null;
            $data[$key]['type_name'] = null;
            $data[$key]['order_type'] = null;

            $data[$key]['rate_money'] = $item['rate_total_money'];//参考总额
            $data[$key]['currency_symbol'] = $this->omOfCurrencySymbol[$item['om']];
            $order_type = 2;
            if($item['buy_id'] == $userId){
                $order_type = 1;
            }
            $data[$key]['type_name']     = $this->orderTradeTypeNameArr[$order_type];    //订单类型
            $data[$key]['order_type'] = $order_type;
            unset($data[$key]['buy_id']);
            unset($data[$key]['om']);
            unset($data[$key]['rate_total_money']);
        }
        if(empty($data)){
            $data = array();
        }
        $result['data']['list'] = $data;
        $result['data']['count'] = $count;
        return $result;
    }

    /**
     * 返回用户订单是否显示
     * 0：不显示
     * 1：显示
     * @param $uid
     * 刘富国
     *  20180504
     */
    public function  checkUserOrderDisplay($uid){
        $completeInfo = M('CcComplete')->where(['uid'=>$uid])->find();
        if( empty($completeInfo) ){
            $status = 1;
        }else{
            $status = $completeInfo['order_display'];
        }
        return $status;
    }
  
    //获取最小成交金额 C2C配置
    protected function getCcTradeMinMoney(){
        $ccConfig= M('CcConfig')->select();
        $minMoneyArr=array_column($ccConfig,'min_trade_money','id');
        return $minMoneyArr;
    }
}