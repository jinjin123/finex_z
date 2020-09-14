<?php
/**
 * 币币交易模块
 * @author 刘富国
 * 2013-12-14
 */
namespace AppTarget\Service\V100;

use AppTarget\Service\ServiceBase;
use Home\Logics\CurrencyTradingLogicsController;
use Common\Api\CurrencyTradingConfig;
use Home\Logics\PublicFunctionController;
use Home\Controller\CoinTradeInfoController;
use Common\Api\RedisCluster;
use Common\Logic\OffTrading;
use Common\Logic\CheckUser;
use Common\Api\redisKeyNameLibrary;
use Home\Logics\CheckAllCanUseParam;

class CurrencyTradingService extends ServiceBase {
    
    private $limitArr  = array(1,2); // 交易区id,1表示btc交易区，2表示bch交易区
    private $tradeAreaArr;// 根据交易区获取交易区表名
    private $tradeSuccessArr;// 根据交易区获取交易成功表名
    private $areaBuyCurrencyIdArr;// 根据交易区和委托类别获取买入币种id
    private $areaSellCurrencyIdArr;// 根据交易区和委托类别获取卖出币种id;
    private $coinArr;// 根据交易区和币种获取交易币种类型
    private $coinList;// 根据交易区和币种获取交易币种类型
    private $currencyArr;
    private $coinTradeInfoController = null ;
    private $input_data = array();
    private $uid = 0;
    private $currencyTradingLogicsObj = null;
    private $checkAllCanUseParamObj = null;
    private $publicFunctionObj = null;
    private $offTradingObj = null;
    const LIMIT_ORDER = 1; //限价单
    const MARKET_ORDER = 2; //市价单
    const BUY_ORDER = 1; //买入单
    const SELL_ORDER = 2; //卖出单
    const   ST_ORDER_STATUS_REVOKE = 4; //订单状态：用户撤销
    private $redis=NULL;
    private $checkUserObj = null;
    
    public function __construct(){
        $this->uid = $this->getUserId();
        $this->input_data = $this->getData();
        $this->coinTradeInfoController = new CoinTradeInfoController();
        $this->getCurrencyTradingConfig();
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->currencyTradingLogicsObj = new CurrencyTradingLogicsController();
        $this->publicFunctionObj = new PublicFunctionController();
        $this->offTradingObj = new OffTrading();
        $this->checkUserObj = new CheckUser();
        $this->checkAllCanUseParamObj = new CheckAllCanUseParam();
    }
    
    public function getUserCurrency(){
        if($this->uid < 1 ) return 9998;
        $entrust_type  = $this->input_data['entrust_type'];
        $area_id  = $this->input_data['area_id'];
        $tradeArea           = $area_id ? $area_id : 1;// 交易区id,1表示btc交易区；2表示bch交易区
        $entrustType         = $entrust_type? $entrust_type : 1;// 委托类别,例如：1btc/bch 2 ltc/bch  3 etc/bch 4 eth/bc
        
        $myCurrencyList = $this->currencyArr[$tradeArea][$entrustType];
        $currencyList = M('Currency')->where(array('status'=>1))->select();
        $temp         = array();
        foreach ($currencyList as $value) {
            $temp[$value['id']] = $value['currency_mark'];
        }
        $where['uid']   = $this->uid;
        $myAllCurrencyList = M('UserCurrency')->field('currency_id,num')->where($where)->select();
        foreach ($myAllCurrencyList as $value) {
            if($value['currency_id'] == $myCurrencyList['currency_id']){
                $myCurrList['coin_name'] = $temp[$myCurrencyList['currency_id']];
                $myCurrList['num'] = $value['num'];
                continue;
            }
            if($value['currency_id'] == $myCurrencyList['p_currency_id']){
                $myCurrList['p_coin_name'] = $temp[$myCurrencyList['p_currency_id']];
                $myCurrList['p_num'] = $value['num'];
                continue;
            }
        }
        $this->checkD2DPower();
        $myCurrList['code'] = $this->errno;
        $myCurrList['msg'] = $this->errmsg;
        return $myCurrList;
    }
    
    /**
     * 获取币币交易订单列表
     */
    public function getMarketList(){
        $area_id  = $this->input_data['area_id'];
        $entrust_type  = $this->input_data['entrust_type'];
        $limit  = $this->input_data['limit'];
        $tradeArea           = $area_id ? $area_id : 1;// 交易区id,1表示btc交易区；2表示bch交易区
        $entrustType         = $entrust_type? $entrust_type : 1;// 委托类别,例如：1btc/bch 2 ltc/bch  3 etc/bch 4 eth/bc
        $limit               = $limit? $limit : 5;
        $tradeAreaArr        = CurrencyTradingConfig::$tradeAreaArr;
        $tradeAreaMachineArr = CurrencyTradingConfig::$tradeAreaMachineArr;
        $tableName           = $tradeAreaArr[$tradeArea];
        if (empty($tableName))  return array('sell' => [],'buy'  => []);
        $buyWhere            = array();
        $sellWhere           = array();
        // 检测网站是否维护
        $isMaintain = $this->coinTradeInfoController->checkWebMaintain();
        if (empty($isMaintain))  return array('sell' => [],'buy'  => []);
        $buyWhere['entrust_type'] = $entrustType;
        $buyWhere['buy_id']       = array('neq', 0);
        $buyWhere['status']       = array('in', array(1));
        $field                    = 'entrust_type,leave_num,entrust_price,entrust_money';
        $buyList                  = M($tableName)->where($buyWhere)->field($field)->order('entrust_price desc')->limit($limit)->select();
        $buyCount                 = count($buyList);
        $machBuyRes               = array();
        /*
         if ($buyCount < $limit) {
         $machineTableName = $tradeAreaMachineArr[$tradeArea];
         $machLimit = $limit - $buyCount;
         $field     = 'entrust_type,trade_num as leave_num,trade_price as entrust_price,trade_money as entrust_money';
         $machineBuyWhere = array(
         'sell_id'      => 0,
         'entrust_type' => $entrustType
         );
         $machBuyRes   = M($machineTableName)->where($machineBuyWhere)->field($field)->order('entrust_price desc')->limit($machLimit)->select();
         }
         */
        
        $buyListArr = array_merge($buyList, $machBuyRes);
        
        $sellWhere['entrust_type'] = $entrustType;
        $sellWhere['sell_id']      = array('neq', 0);
        $sellWhere['status']       = array('in', array(2));;
        $field                     = 'entrust_type,leave_num,entrust_price,entrust_money';
        $sellList                  = M($tableName)->where($sellWhere)->field($field)->order('entrust_price ASC')->limit($limit)->select();
        $sellCount                 = count($sellList);
        $machSellRes               = array();
        /*
         if ($sellCount < 12) {
         $machineTableName = $tradeAreaMachineArr[$tradeArea];
         $machLimit = $limit - $sellCount;
         $field     = 'entrust_type,trade_num as leave_num,trade_price as entrust_price,trade_money as entrust_money';
         $machineBuyWhere = array(
         'sell_id'      => array('neq', 0),
         'entrust_type' => $entrustType
         );
         $machSellRes   = M($machineTableName)->where($machineBuyWhere)->field($field)->order('entrust_price ASC')->limit($machLimit)->select();
         }
         */
        
        $sellListArr = array_merge($sellList, $machSellRes);
        
        $buyArr = array();
        foreach ($buyListArr as $key => $value) {
            $temp['price'] = $value['entrust_price'];
            $temp['ctd']   = $value['leave_num'];
            $buyArr[]      = $temp;
        }
        $sellArr = array();
        foreach ($sellListArr as $key => $value) {
            $temp['price'] = $value['entrust_price'];
            $temp['ctd']   = $value['leave_num'];
            $sellArr[]     = $temp;
        }
        return array('sell' => $sellArr,'buy'  => $buyArr);
    }
    
    /**
     * 获取币币交易相关配置
     * @author lirunqing 2017-11-30T19:29:34+0800
     * @return [type] [description]
     */
    private function getCurrencyTradingConfig(){
        $currencyArr = CurrencyTradingConfig::getTradingArea();
        $vpConfigArr = CurrencyTradingConfig::getVPTradeArea();
        $this->currencyArr           = $this->coinTradeInfoController->getCurrencyInfoListToAll();
        $this->tradeAreaArr          = CurrencyTradingConfig::$tradeAreaArr;
        $this->tradeSuccessArr       = CurrencyTradingConfig::$tradeAreasuccessArr;
        $this->areaSellCurrencyIdArr = CurrencyTradingConfig::getTradeAreaInfo();
        $this->areaBuyCurrencyIdArr  = CurrencyTradingConfig::getAllTradeAreaArr();
        $this->coinList              = $currencyArr['coinList']+$vpConfigArr['VPConlist'];
    }
    
    /**
     * 获取购买数量
     */
    public function getBuyNumByPriceAndNum(){
        $type    = $this->input_data['type'];// 1表示相乘，2表示相除
        $userNum = $this->input_data['num'];
        $price   = $this->input_data['price'];
        $num     = 0;
        if ($type == 1) {
            $num = big_digital_mul($price, $userNum);
        }
        if ($type == 2) {
            $num = big_digital_div($userNum, $price);
        }
        return array('num' => $num);
    }
    
    /**
     * 币币交易业务处理
     * @return array|bool|object
     */
    public function processTradeInfo(){
        //检测网站是否处于维护状态
        $isMaintain = $this->currencyTradingLogicsObj->checkWebMaintain(2);
        if($isMaintain['code']!=200) return $this->return_error_num(10046,$isMaintain['msg'] );
        
        $userId = $this->uid;
        if($this->uid < 1 ) return 9998;
        $data['tradeType']      = $this->input_data['area_id'];// 交易区,1表示btc交易区，2表示VP交易区
        $data['priceType']      = $this->input_data['price_type'];// 价格类型，1表示限价单，2表示市价单
        $data['transactionType'] = $this->input_data['transaction_type'];// 交易类型，1表示买入，2表示卖出
        $data['entrustType']    = $this->input_data['entrust_type'];// 委托类别;例如：1表示ltc/btc 2表示etc/btc 3表示eth/btc 4表示bcc/btc
        $data['totalPrice']     = $this->input_data['total_price'];//选择市价单时买入/卖出金额
        $data['entrustPrice']   = $this->input_data['entrust_price'];// 单价
        $data['leaveNum']   = $this->input_data['leave_num'];// 数量
        $data['tradePwd']   = $this->input_data['trade_pwd'];//资金密码
        // 检测参数
        if(!$this->checkParams($data))  return $this->return_error_num($this->errno,$this->errmsg);
        // 校验用户资格和资金状况
        $data = $this->checkTradeBefore($data);
        if(!$data) return $this->return_error_num($this->errno,$this->errmsg);
        $isOrder  = $this->redis->get(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE.$userId);
        // 防止用户重复提交订单
        if (!empty($isOrder)) return $this->return_error_num(9999,L('_QWCFCZ_'));
        //添加订单
        $addRes = $this->currencyTradingLogicsObj->addOrderByUserSubData($data, $userId);
        if (empty($addRes) || $addRes['code'] != 200 || empty($addRes['data']['id'])) {
            $res['msg']  = ($data['transactionType'] == 1) ? L('_MRSBQSHZS_') : L('_MCSBSHS_');
            return $this->return_error_num(9999,$res['msg']);
        }
        // 获取当前插入最后一条未完成的订单信息
        $orderId   = $addRes['data']['id'];
        $orderInfo = $this->getOrderInfo($data['tradeType'], $orderId);// 获取当前插入最后一条未完成的订单信息
        $this->redis->setex(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE.$userId, 5, true);// 防止重复提交订单
        if(empty($orderInfo)) return array('is_success' => 1);
        return $orderInfo;
    }

    /**
     * 校验用户权限
     * @return bool
     */
    protected function checkD2DPower(){
        $ret = $this->checkAllCanUseParamObj->checkUserPower( $this->uid);
        if($ret['code']<> 200)    return $this->return_error($ret['code'],$ret['msg']);
        return true;
    }

    /**
     * 对者资格做校验
     * @return bool
     */
    private function checkTradeBefore($data){
        $userId = $this->uid;
        // 检测限价单的价格是否超出浮动比例
        if(!$this->checkUserPriceIsTrue($data))  return $this->return_error($this->errno,$this->errmsg);
        //用户通用权限校验
        $ret = $this->checkAllCanUseParamObj->checkUserPower($userId);
        if ($ret['code'] <> 200)    return $this->return_error($ret['code'],$ret['msg']);
        // 检测用户资金密码的正确
        if(!$this->checkUserTradePwd($this->uid, $data['tradePwd'])) {
            return $this->return_error($this->errno,$this->errmsg);
        }
        // 获取用户交易数量及币种id
        $userNumInfo = $this->getUserNumAndCurrencyId($data);
        $data        = $userNumInfo['data'];
        $num         = $userNumInfo['num'];
        $currencyId  = $userNumInfo['currencyId'];
        // 检测用资金是否足够
        $ret =  $this->checkUserMoneyIsEnough($userId, $currencyId, $num);
        if(!$ret)  return $this->return_error($this->errno,$this->errmsg);;
        // 检测用户是否存在未完成的订单
        $isComplete = $this->currencyTradingLogicsObj->checkIsCompleteOrderExist($userId, $data['tradeType'], $data['entrustType'], $data['transactionType']);
        if ($isComplete != 200) {
            return $this->return_error(10031,L('_CZWWCDD_'));
        }
        return $data;
    }
    
    /**
     * 获取币种id及交易数量
     * @author lirunqing 2017-12-21T11:09:48+0800
     * @param  array $data
     * @return array
     */
    private function getUserNumAndCurrencyId($data){
        
        $num        = big_digital_mul($data['entrustPrice'], $data['leaveNum']);
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
    
    /**获取交易区
     * @return array
     */
    public function getTradeAreaList(){
        $tradeAreaArr = CurrencyTradingConfig::getTradingArea();
        $VPTradeAreaArr = CurrencyTradingConfig::getVPTradeArea();
        $trade_area_arr = array();
        //组合1级和2级交易区的货币类型
        // $trade_area_list = array_replace_recursive($tradeAreaArr['tradeAreaArr'],$VPTradeAreaArr['tradeAreaArr']);
        $trade_area_list = array_replace_recursive($tradeAreaArr['tradeAreaArr']);
        foreach ($trade_area_list as $trade_area_key => $trade_area_value) {
            $area_id = $trade_area_key;
            if(empty($this->tradeAreaArr[$area_id])) continue;
            $temp['area_name']  = $trade_area_value;
            $temp['area_id']    = $area_id;  //交易区ID
            $trade_area_arr[] = $temp;
        }
        return $trade_area_arr;
    }
    
    /**获取交易区的委托类型
     * @return array
     */
    public function getTradeAreaForEntrustTypeList(){
        $area_id = $this->input_data['area_id'];// 交易区id
        if($area_id < 1 ) return 10000;
        $tradeAreaInfo = $this->currencyArr[$area_id];
        $currencyList   = M('Currency')
        ->field('currency_mark,id,currency_name,currency_logo')
        ->select();
        $currency_arr = array_column($currencyList,'currency_name','id');
        if (empty($tradeAreaInfo)) return (object)array();
        $trade_area_arr = array();
        foreach ($tradeAreaInfo as $key => $value) {
            $child_temp['entrust_type'] = $value['entrust_type']; //委托类型ID
            $child_temp['coin_str'] = $value['coin_str'];//委托类型名称
            $child_temp['high'] = $value['high'];
            $child_temp['low'] = $value['low'];
            $child_temp['vol'] = $value['vol']; //成交量(最近的24小时)
            $child_temp['rtq'] = $value['last']; //最后成交价
            $child_temp['p_currency_id'] = $value['p_currency_id'];
            $child_temp['p_currency_name'] = $currency_arr[$value['p_currency_id']];
            $child_temp['currency_id'] = $value['currency_id'];
            $child_temp['currency_name'] = $currency_arr[$value['currency_id']];
            $child_temp['rate'] =  $value['rate']; //每日涨跌比例
            $child_temp['perc_status'] = $value['perc_status']; //涨跌标识  1 涨 -1跌
            $child_temp['sell'] = $value['sell']; //卖出价
            $child_temp['sell_fee'] = $value['sell_fee']; //卖出手续费
            $child_temp['buy'] = $value['buy']; //买入价
            $child_temp['buy_fee'] = $value['buy_fee'];  //买入手续费
            $child_temp['entrust_type'] =  $value['entrust_type']; //委托类型ID
            //价格浮动限制
            $floatingRatioData['tradeType'] = $area_id;
            $floatingRatioData['entrustType'] = $value['entrust_type'];
            $child_temp['float_price_rate'] = $this->currencyTradingLogicsObj
            ->getUserPriceFloatingRatio($floatingRatioData);
            $currencyPic = strtolower($child_temp['currency_name']).'.png';
            $child_temp['app_index_log'] = C('app_index_log').$currencyPic;
            $child_temp['app_pull_checked_log'] = C('app_pull_checked_log').$currencyPic;
            $child_temp['app_pull_nochecked_log'] = C('app_pull_nochecked_log').$currencyPic;
            $trade_area_arr[] = $child_temp;
        }
        return $trade_area_arr;
    }
    
    /**获取交易区和委托类型
     * @return array
     */
    public function getTradeArea(){
        $tradeAreaArr = CurrencyTradingConfig::getTradingArea();
        $VPTradeAreaArr = CurrencyTradingConfig::getVPTradeArea();
        $currencyInfo = $this->currencyArr;
        $trade_area_arr = array();
        $first_entrust_info = $this->input_data['first_entrust_info'];// 交易区id
        //组合1级和2级交易区的货币类型
        //  $trade_area_list = array_replace_recursive($tradeAreaArr['tradeAreaArr'],$VPTradeAreaArr['tradeAreaArr']);
        $trade_area_list = array_replace_recursive($tradeAreaArr['tradeAreaArr']);

        foreach ($trade_area_list as $trade_area_key => $trade_area_value) {
            $area_id = $trade_area_key;
            if(empty($this->tradeAreaArr[$area_id])) continue;
            $temp['area_name']  = $trade_area_value;
            $temp['area_id']    = $area_id;  //交易区ID
            $child_info = array();
            $tradeAreaInfo = $currencyInfo[$area_id];
            if (empty($tradeAreaInfo)) continue;
            foreach ($tradeAreaInfo as $key => $value) {
                $child_temp['entrust_type'] = $value['entrust_type']; //委托类型ID
                $child_temp['coin_str'] = $value['coin_str'];//委托类型名称
                $child_temp['high'] = $value['high'];
                $child_temp['low'] = $value['low'];
                $child_temp['vol'] = $value['vol']; //成交量(最近的24小时)
                $child_temp['rtq'] = $value['last']; //最后成交价
                $child_temp['p_currency_id'] = $value['p_currency_id'];
                $child_temp['p_currency_name'] = getCurrencyName($value['p_currency_id']);
                $child_temp['currency_id'] = $value['currency_id'];
                $child_temp['currency_name'] = getCurrencyName($value['currency_id']);
                $child_temp['rate'] =  $value['rate']; //每日涨跌比例
                $child_temp['perc_status'] = $value['perc_status']; //涨跌标识  1 涨 -1跌
                $child_temp['sell'] = $value['sell']; //卖出价
                $child_temp['sell_fee'] = $value['sell_fee']; //卖出手续费
                $child_temp['buy'] = $value['buy']; //买入价
                $child_temp['buy_fee'] = $value['buy_fee'];  //买入手续费
                $child_temp['entrust_type'] =  $value['entrust_type']; //委托类型ID
                //价格浮动限制
                $floatingRatioData['tradeType'] = $area_id;
                $floatingRatioData['entrustType'] = $value['entrust_type'];
                $child_temp['float_price_rate'] = $this->currencyTradingLogicsObj
                ->getUserPriceFloatingRatio($floatingRatioData);
                $child_info[] = $child_temp;
                if ($first_entrust_info == 1 ) break;
            }
            $temp['child_info'] = $child_info;
            $trade_area_arr[] = $temp;
        }
        return $trade_area_arr;
    }
    
    
    
    //系统基本类别：价格类型，交易类型
    public function getCurrencyTradeConfigType(){
        // 价格类型，1表示限价单，2表示市价单
        $config_type['price_type'] = array('limit_order'=>self::LIMIT_ORDER,'market_order'=>self::MARKET_ORDER);
        // 交易类型，1表示买入，2表示卖出
        $config_type['transaction_type'] =array('buy_order'=>self::BUY_ORDER,'sell_order'=>self::SELL_ORDER);
        return $config_type;
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
     * 获取正在交易的订单/历史订单
     * @author 刘富国
     */
    public function getOrderList(){
        //检测网站是否处于维护状态
        $isMaintain = $this->currencyTradingLogicsObj->checkWebMaintain(1);
        if($isMaintain['code']!=200) return $this->return_error_num(10046,$isMaintain['msg'] );
        
        if($this->uid < 1 ) return 9998;
        $type = $this->input_data['type'];//  1表示正在交易,2表示撤销/已完成订单
        $tradeArea = $this->input_data['area_id'];// 交易区id
        $entrustType = $this->input_data['entrust_type'];// 委托类别,例如：1btc/bch 2 ltc/bch  3 etc/bch 4 eth/bc
        $page = intval($this->input_data['page']);
        $limit = intval($this->input_data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        
        if (!in_array($type, array(1, 2)) || !in_array($tradeArea, array(1, 2))) {
            return (object)array();
        }
        $tableName = $this->getTradeAreaByType($type, $tradeArea);
        if(empty($tableName)) return (object)array();
        $where = array();
        $userId = $this->uid;
        if(!empty($entrustType)) $where['entrust_type'] = $entrustType;
        $where['sell_id|buy_id'] = $userId;
        
        if ($type == 1) {
            $where['status'] = array('in', array(1, 2));
            $field  = 'id as order_id,entrust_type,entrust_num,entrust_price,entrust_money,sell_id,buy_id,status,';
            $field  .= 'success_num,add_time';
            $orderBy    = 'add_time desc';
        }else {
            $where['status'] = array('in', array(3,4));
            $field  = 'id as order_id,entrust_type,update_time,entrust_num,entrust_price,entrust_money,status,';
            $field  .= 'sell_id,buy_id,success_num,add_time';
            $orderBy    = 'add_time desc';
        }
        
        $count = M($tableName)->where($where)->count();
        if($count < 1 ) return (object)array();
        $order_list = M($tableName)->where($where)->field($field)->limit($limit)->order($orderBy)
        ->page($page)->select();
        $pIdArr = array();
        $statusStr = [
            3 => L('_YWC_'),
            4 => L('_YCX_'),
        ];
        foreach ($order_list as $key => $value) {
            $pIdArr[$value['order_id']] = $value['order_id'];
            //卖单
            if ($value['sell_id'] == $userId) {
                $value['transaction_type'] = 2;
            }
            //买单
            if ($value['buy_id'] == $userId){
                $value['transaction_type'] = 1;
            }
            
            $value['coin'] = $this->coinList[$tradeArea][$value['entrust_type']];
            if ($type == 2) {
                $value['add_time'] = $value['update_time'];
            }
            
            if (in_array($value['status'], array(3,4))) {
                $value['status_str'] = $statusStr[$value['status']];
            }else{
                $value['status_str'] = L('_JIAOYZ_');
            }
            
            $value['success_num'] = $value['success_num'];
            
            unset($value['sell_id']);
            unset($value['buy_id']);
            unset($value['status']);
            $order_list[$key] = $value;
        }
        // 获取成交均价及成交总额
        $order_list = $this->getDealChildInfo($order_list, $pIdArr, $tradeArea);
        
        $ret = array(
            'total'  => $count,
            'list'   => $order_list,
            'pager'  => $this->_pager($page, ceil($count/$limit)),
        );
        return $ret;
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
                $sellList[$value['pid_sell']]['avg_price'] = round($value['avg_price'], 8);
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
                $buyList[$value['pid_buy']]['avg_price'] = round($value['avg_price'], 8);
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
     * 撤销订单
     */
    public function revokeOrder(){
        $orderId = $this->input_data['order_id']; // 订单id
        $tradeArea = $this->input_data['area_id']; // 交易区id
        if($this->uid < 1 ) return 9998;
        $userId = $this->uid;
        if (empty($orderId)) return $this->return_error_num(10000,L('_NCXDWZD_'));
        if (!in_array($tradeArea, $this->limitArr)){
            return $this->return_error_num(10000,L('_ZWGJY_'));
        }
        // 防止用户重复撤销订单
        $isRev = $this->redis->get(redisKeyNameLibrary::CURTENCY_REVOKE_ORDER.$userId.$orderId);
        if (!empty($isRev)) {
            return $this->return_error_num(9999,L('_QWCFCZ_'));
        }
        
        // 如果该订单正在匹配，则该订单不能进行撤销
        $isMatching = $this->redis->hget(redisKeyNameLibrary::CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH, $userId."-".$orderId);
        if (!empty($isMatching)) {
            return $this->return_error_num(9999,L('_GDDBGMCXSB_'));
        }
        
        $currencyTradingLogicsObj = new CurrencyTradingLogicsController();
        $revRes = $currencyTradingLogicsObj->revokerCurrencyOrder($orderId, $tradeArea, $userId);// 撤销订单
        
        if (empty($revRes) || $revRes['code'] != 200) {
            return $this->return_error_num(9999,$revRes['msg']);
        }
        
        // 设置防止用户重复撤销订单key
        $this->redis->setex(redisKeyNameLibrary::CURTENCY_REVOKE_ORDER.$userId.$orderId, 5, true);
        
        return array('is_success' => 1);
    }
    
    /**
     * 检测挂单参数
     * @param $data
     * @return bool
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
        
        if (empty($data['tradeType']) || !in_array($data['tradeType'], $this->limitArr)) {
            return $this->return_error(10000,L('_QXZDQ_'));
        }
        
        if (empty($data['transactionType']) || !in_array($data['transactionType'], array(1, 2))) {
            return $this->return_error(10000,L('_QXZJYLX_'));
        }
        
        $entrustTypeArr = array_flip($this->areaSellCurrencyIdArr[$data['tradeType']]);
        if (empty($data['entrustType']) || !in_array($data['entrustType'], $entrustTypeArr)) {
            return $this->return_error(10000,L('_QXZBZ_'));
        }
        
        // 限价单需要填写价格,市价单直接获取最新交易价
        if (empty($data['entrustPrice']) && $data['priceType'] == 1) {
            return $this->return_error(10000,L('_QTXDJ_'));
        }
        
        if (!regex($data['entrustPrice'], 'double') && $data['priceType'] == 1) {
            return $this->return_error(10000,L('_QSRZQDDJ_'));
        }
        
        // 限价单需要填写数量,市价单直接获取最新交易价
        if (empty($data['leaveNum']) && $data['priceType'] == 1) {
            return $this->return_error(10000,L('_QTXSL_'));
        }
        
        if (!regex($data['leaveNum'], 'double') && $data['priceType'] == 1) {
            return $this->return_error(10000,L('_QTXZQSL_'));
        }
        
        // 市价单需要填写数量,市价单直接获取最新交易价
        if (empty($data['totalPrice']) && $data['priceType'] == 2) {
            $res['msg'] = ($data['transactionType'] == 1) ? L('_QTXMRJE_') : L( '_QTXMCJE_');
            return $this->return_error(10000,$res['msg']);
        }
        
        if (!regex($data['totalPrice'], 'double') && $data['priceType'] == 2) {
            $res['msg'] = ($data['transactionType'] == 1) ? L('_QTXZQDMRJE_') : L( '_QTXZQDMCJE_');
            return $this->return_error(10000,$res['msg']);
        }
        
        return true;
    }
    
    /**
     * 检测用户的资金密码是否正确
     * @param  int $userId   用户id
     * @param  string $tradePwd 资金密码
     * @return bool
     */
    private function checkUserTradePwd($userId, $tradePwd){
        if (empty($tradePwd) or empty($userId))  return $this->return_error('10000',L('_JYMMCW_'));;
        //验证交易密码的正确性
        $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($userId, $tradePwd);
        if($tradePwdRes['code'] != 200){
            return $this->return_error(10000,$tradePwdRes['msg']);
        }
        return true;
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
        $price = $this->currencyArr[$tradeType][$entrustType]['buy'];
        if ($transactionType == 2) {
            $price = $this->currencyArr[$tradeType][$entrustType]['sell'];
        }
        return $price;
    }
    
    /**
     * 检测价格是否超限
     * @param  array $data
     * @return bool
     */
    private function checkUserPriceIsTrue($data){
        // 检测限价单的价格是否超出浮动比例
        $marketPrice  = $this->getPriceByTradeArea($data['tradeType'], $data['transactionType'],
            $data['entrustType']);
        if ($data['tradeType'] == 1) {
            $priceRateRes = $this->currencyTradingLogicsObj
            ->priceFloatingRatio($data['tradeType'], $data['entrustPrice'], $marketPrice);
        }else{
            $areaCurrencyId = $this->areaBuyCurrencyIdArr[$data['tradeType']];
            $priceRateRes = $this->currencyTradingLogicsObj
            ->priceFloatingRatioToVp($areaCurrencyId, $data['entrustType'],
                $data['entrustPrice'], $marketPrice);
        }
        $floatPriceRate = $this->currencyTradingLogicsObj->getUserPriceFloatingRatio($data);
        if ($priceRateRes != 200 && $data['priceType'] == 1) {
            $floatPriceRate = $floatPriceRate * 100;
            $msg = str_replace('10', $floatPriceRate, L('_NSRDJGCCXZ_'));
            return $this->return_error(10000,$msg);
        }
        return true;
    }
    
    /**
     * 检测用户资金是否足够交易
     * @param  int $userId     用户id
     * @param  int $currencyId 币种id
     * @param  float $num      交易数量
     * @return bool
     */
    private function checkUserMoneyIsEnough($userId, $currencyId, $num){
        $where = array(
            'uid' => $userId,
            'currency_id' => $currencyId
        );
        $userMoneyInfo = M('UserCurrency')->where($where)->find();
        if (empty($userMoneyInfo['num']) || $userMoneyInfo['num'] < $num) {
            return $this->return_error(10000,L('_NDZJBZ_'));
        }
        return true;
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
        if (empty($tableName)) return array();
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
    
    
    
}