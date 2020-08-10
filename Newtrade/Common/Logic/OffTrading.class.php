<?php
/**
 * 线下交易订单处理
 * User: 刘富国
 * Date: 2017/11/30
 * Time: 14:27
 */

namespace Common\Logic;

use Home\Model\BankListModel;
use Common\Api\RedisCluster;
use Home\Logics\PublicFunctionController;
use Home\Logics\UserMoneyApi;
use Home\Model\UserCurrencyModel;
use Home\Logics\OffTradingLogicsController;
use Common\Api\Point;
use Home\Model\CurrencyModel;
use Common\Api\redisKeyNameLibrary;
use Home\Controller\CoinTradeInfoController;

class OffTrading extends BaseLogic{
    const   ST_ORDER_STATUS_BUYING_SUCCESS = 1; //订单状态：买入成功
    const   ST_ORDER_STATUS_FOR_SALE = 0; //订单状态：待售
    const   ST_ORDER_STATUS_REVOKE = 5; //订单状态：用户撤销
    const   ST_FINANCE_INCOME   = 1; //财务状态：收入
    const   ST_FINANCE_EXPENSE  = 2; //财务状态：支出
    const BUY_ORDER = 1; //买入单
    const SELL_ORDER = 2; //卖出单
    public  $redis=NULL;
    protected   $userMoneyApiObj    = null;
    protected   $publicFunctionObj  = null;
    protected   $offTradingLogicsObj = null;
    protected   $userCurrencyModel  = null;
    public $primary_market_currrency_type = array(1,2,3,4,5);//一级市场的货币ID
    public function __construct() {
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->userMoneyApiObj  = new UserMoneyApi();
        $this->publicFunctionObj = new PublicFunctionController();
        $this->userCurrencyModel = new UserCurrencyModel();
        $this->offTradingLogicsObj = new OffTradingLogicsController();
    }
    private   $decPointArr     = array( // 用户等级对应减积分
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
    
    /**
     * 获取货币的最新价格，用户这货币的剩余数量
     * @param $uid
     * @param $currency_id
     * @return bool
     */
    public function get_user_currency_info($uid,$currency_id){
        $where['uc.uid'] = $uid;
        $where['uc.currency_id'] = $currency_id;
        $user_currency_info  = M('UserCurrency')->alias('uc')
        ->join('__CURRENCY__ as c on c.id=uc.currency_id', 'left')
        ->where($where)
        ->field('uc.currency_id,uc.num,c.currency_mark,currency_name')->find();
        if(empty($user_currency_info)) return false;
        $currency_info = $this->getCoinInfoFromOther($currency_id);
        $ret_user_currency_info['currency_id'] = $user_currency_info['currency_id'];
        $ret_user_currency_info['currency_name'] = $user_currency_info['currency_name'];
        $ret_user_currency_info['num'] = $user_currency_info['num'];
        $ret_user_currency_info['last_price'] = $currency_info['last_price'];
        return $ret_user_currency_info;
    }
    
    /**
     * 根据币种名称，从bitfinex网站获取币种交易信息
     * @author 李江 2017年12月15日14:53:52
     * @param  string $coinName 币种名称
     * @return array
     */
    public function getCoinInfoFromOther($currency_id){
        $coinTradeInfoObj =  new CoinTradeInfoController();
        $returnData = $coinTradeInfoObj->getCoinInfo();
        $currency_info = [];
        if(empty($returnData['coinInfoList'][$currency_id])) {
            $currency_info['last_price'] = 0;
        }else{
            $currency_info = $returnData['coinInfoList'][$currency_id];
        }
        return $currency_info ;
    }
    
    /**
     * 处理订单列表
     * @author 刘富国
     * @param array $res
     * @return array
     */
    public function processOrderAppList($res,$user_id,$rateArr){
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
            '1' => '',
            '2' => L('_QRSK_'),
            '8' => '',
        );
        $buyStr = array(
            '1' => L('_DAKUAN_'),
            '2' => '',
            '8' => '',
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
        $symbolMoney=[
            "86"=>"CNY",
            "886"=>"TWD",
            "852"=>"HKD",
        ];
        $currencyModel = new CurrencyModel();
        $currencyList  = $currencyModel->getCurrencyList('*', true);
        //获取卖家用银行信息
        $seller_bank_list = $this->getSellerBankListByOrder($res);
        //获取买家护照名
        $user_real_list = $this->getbuyerBankNameByOrder($res);
        $remitRestTime =  $this->offTradingLogicsObj->confirmPaidOvertime;//确认打款超时配置，30分钟
        $receiptRestTime = $this->offTradingLogicsObj->acceptPaidOvertime;// 确认收款超时配置，12小时
        foreach ($res as $key => $value) {
            $orderNumArr = explode('-', $value['order_num']);
            $value['order_id']= $value['id'];
            $value['user_bank_id']= $value['bank_id'];
            $value['opt_unreceipt_type'] = 0;//收款异常,0,不显示，1显示
            $value['opt_str_unreceipt'] = ''; //收款异常按钮文字
            $value['time_limit'] = 0; //确认已汇款 或者 確認已收款 按钮剩余时间
            $orderNum = 0;
            $buyer_real_name = '';
            //卖家
            if ($value['sell_id'] == $user_id) {
                $orderNum             = $orderNumArr[1];
                $value['status_str'] = $sellStatusArr[$value['status']];
                $value['type_str']   = $sellStr[$value['status']]; //操作按钮文字,例如：打款，撤销
                $value['transaction_type']   = self::SELL_ORDER; //卖单
                
                if($value['status'] == 2){
                    //买家付款后5分钟，卖家订单显示“收款异常”按钮
                    $abnormalSecond = time() - ($value['shoukuan_time']+ $this->offTradingLogicsObj->abnormalSecond) ;
                    if($abnormalSecond > 0 ) {
                        $value['opt_str_unreceipt'] = L( '_SKYC_');
                        $value['opt_unreceipt_type'] = 1;
                    }
                }
            }
            //买家
            if ($value['buy_id'] == $user_id) {
                $orderNum             = $orderNumArr[0];
                $value['status_str'] = $buyStatusArr[$value['status']];
                $value['type_str']   = $buyStr[$value['status']];
                $value['transaction_type']   = self::BUY_ORDER; //买单
            }
            //付款状态显示
            if($value['status'] == 1){
                $timeLimit = $remitRestTime-(time()-$value['trade_time']);
                if($timeLimit > 0) {
                    $value['time_limit'] = $timeLimit; //付款剩余时间
                } else{
                    $value['status_str'] = L('_FUKCS_');//付款超时
                }
            }
            //收款状态显示
            if($value['status'] == 2){
                $timeLimit = $receiptRestTime-(time()-$value['shoukuan_time']);
                if($timeLimit > 0) {
                    $value['time_limit'] = $timeLimit; //收款剩余时间
                } else{
                    $value['status_str'] = L('_SHOUKCS_');//收款超时
                }
            }
            $value['details']       = $strArr[2]; //详情按钮文字
            $value['order_num']     = $orderNum;
            $value['total_price']   = big_digital_mul($value['num'],$value['price'],2);
            $value['coin_name']     = $currencyList[$value['currency_id']]['currency_name'];
            //卖家收款卡号;收款银行;开户行地址;收款人姓名
            $value['bank_name'] = formatBankType($seller_bank_list[$value['user_bank_id']]['bank_list_id']);
            $value['bank_real_name'] = $seller_bank_list[$value['user_bank_id']]['bank_real_name'];
            $value['bank_num'] = $seller_bank_list[$value['user_bank_id']]['bank_num'];
            $value['bank_address'] = $seller_bank_list[$value['user_bank_id']]['bank_address'];



            if ($value['status'] != 0) {
                $value['reference_price'] = big_digital_div($value['rate_total_money'],$value['num'],2);
                $value['reference_total_price'] = $value['rate_total_money'];
            }else{
                $value['reference_price'] = big_digital_mul($rateArr[$value['om']],$value['price'],2);
                $value['reference_total_price'] = ($value['om'] == '886') ? big_digital_mul($value['total_price'], $rateArr[$value['om']], 0).'.00' : big_digital_mul($value['total_price'], $rateArr[$value['om']], 2) ;
            }

            if(!empty($user_real_list) and !empty($value['buy_id'])) {
                $buyer_real_name =  $user_real_list[$value['buy_id']]['card_name'];
            }
            $value['buyer_real_name'] = $buyer_real_name;
            $value['unit'] = $symbolMoney[$value['om']];
            unset($value['id']);
            unset($value['bank_id']);
            unset($value['buy_fee']);
            unset($value['sell_fee']);
            unset($value['sell_id']);
            unset($value['buy_id']);
            $res[$key] = $value;
        }
        
        return $res;
    }
    
    //获取卖家用银行信息
    private function getSellerBankListByOrder($order_list){
        $seller_arr = array_column($order_list,'sell_id');
        $user_bank_where['uid'] = array('in',$seller_arr);
        $field = 'a.id as user_bank_id,a.bank_real_name,a.bank_num,a.bank_address
        ,b.bank_name,b.country_code,a.bank_list_id';
        $seller_bank_list = M('userBank')->alias('a')->field($field)
        ->join('__BANK_LIST__ b ON b.id= a.bank_list_id')
        ->where($user_bank_where)
        ->group('a.id')
        ->select();
        foreach ($seller_bank_list as $key => $item){
            $seller_bank_list[$item['user_bank_id']] = $item;
        }
        return $seller_bank_list;
    }
    
    //获取买家用银行开户名
    private function getbuyerBankNameByOrder($order_list){
        $buyer_arr = array_column($order_list,'buy_id');
        $buyer_arr = array_keys(array_flip($buyer_arr));
        $nullKey = array_search('0',$buyer_arr);
        if(!empty($nullKey)) unset($buyer_arr[$nullKey]);
        if(empty($buyer_arr)) return array();
        $user_real_where['uid'] = array('in',$buyer_arr);
        $user_real_list = M('userReal')->field('uid,card_name')
        ->where($user_real_where)
        ->select();
        foreach ($user_real_list as $key => $item){
            $user_real_list[$item['uid']] = $item;
        }
        return $user_real_list;
    }
    
    /**
     * 处理用户确认打款业务
     * @author lirunqing 2017-11-02T14:50:35+0800
     * @param  array $orderRes 交易订单信息数组
     * @return bool|json
     */
    public function confirmOrderPaid($orderRes){
        if ($orderRes['status'] != 1)  return $this->return_error(10040,L('_QWCFCZ_'));
        $nowTime          = time();
        $whereUser['uid'] = $orderRes['buy_id'];
        $userLevelInfo    = M('User')->field('level')->where($whereUser)->find();
        
        // 判断用户打款是否超时,如果超时则减积分且添加一次失信次数;如果不超时，则添加积分
        if (($nowTime - $orderRes['trade_time']) >= $this->offTradingLogicsObj->confirmPaidOvertime) {
            $decIntegral = $this->decPointArr[$userLevelInfo['level']];
            $extArr['operationType'] = 'dec';
            $extArr['isOverTime']    = 1;
            $extArr['status']        = 9;
            $extArr['scoreInfo']     = L('_XXJYQRDKCSJJF_');//线下交易确认打款超时减积分
            $extArr['remarkInfo']    = $orderRes['order_num'];
            $addPointRes = $this->publicFunctionObj
            ->calUserIntegralAndLeavl($orderRes['buy_id'], $decIntegral, $extArr);
        }else{
            $incIntegral = $this->incPointArr[$userLevelInfo['level']];
            $extArr['operationType'] = 'inc';
            $extArr['isOverTime']    = 0;
            $extArr['status']        = 9;
            $extArr['scoreInfo']     = L('_XXJYQRDKWCSJJF_');
            $extArr['remarkInfo']    = $orderRes['order_num'];
            $addPointRes = $this->publicFunctionObj
            ->calUserIntegralAndLeavl($orderRes['buy_id'], $incIntegral, $extArr);
        }
        if (empty($addPointRes))  return $this->return_error(10040,L('_XTFMSHCS_'));
        $where['id'] = $orderRes['id'];
        //存储 接单到打款时间
        M('TradeTheLine')->where($where)->setField('shoukuan_time',time());
        $res = M('TradeTheLine')->where($where)->setField('status','2');
        if (empty($res))  return $this->return_error(10040,L('_CZSBQCS_'));
        return true;
    }
    
    /**
     * 处理用户确认收款业务
     * @author 2017-11-03T15:45:43+0800
     * @param  [type] $orderRes [description]
     * @return [type]           [description]
     */
    public function orderAccept($orderRes){
        if ($orderRes['status'] != 2) return $this->return_error(10040,L('_QWCFCZ_'));
        $nowTime          = time();
        $whereUser['uid'] = $orderRes['sell_id'];
        $userLevelInfo    = M('User')->field('level')->where($whereUser)->find();
        // 判断用户确认收款是否超时,如果超时则减积分且添加一次失信次数;如果不超时，则添加积分
        if (($nowTime - $orderRes['shoukuan_time']) >= $this->offTradingLogicsObj->acceptPaidOvertime) {
            $decIntegral = $this->decPointArr[$userLevelInfo['level']];
            $extArr['operationType'] = 'dec';
            $extArr['isOverTime']    = 1;
            $extArr['status']        = 9;
            $extArr['scoreInfo']     = L('_XXJYQRSKCSJJF_');//线下交易确认收款超时减积分
            $extArr['remarkInfo']    = $orderRes['order_num'];
            $addPointRes = $this->publicFunctionObj->calUserIntegralAndLeavl($orderRes['sell_id'], $decIntegral, $extArr);
        }else{
            $incIntegral = $this->incPointArr[$userLevelInfo['level']];
            $extArr['operationType'] = 'inc';
            $extArr['isOverTime']    = 0;
            $extArr['status']        = 9;
            $extArr['scoreInfo']     = L('_XXJYSKWCSJJF_');//线下交易确认收款未超时加积分
            $extArr['remarkInfo']    = $orderRes['order_num'];
            $addPointRes = $this->publicFunctionObj->calUserIntegralAndLeavl($orderRes['sell_id'], $incIntegral, $extArr);
        }
        
        if (empty($addPointRes)) return $this->return_error(10040,L('_XTFMSHCS_'));
        // 确认收款后，增加购买人币数量
        $this->processOrderAccept($orderRes);
        return true;
    }
    
    
    /**
     * 确认收款后，增加购买人币数量
     * @author lirunqing 2017-11-03T15:46:21+0800
     * @param  array $orderRes 订单信息
     * @return bool|json
     */
    private function processOrderAccept($orderRes){
        // 设置打款时间
        $where['id'] = $orderRes['id'];
        $r[]         = M('TradeTheLine')->where($where)->setField('end_time',time());
        $r[]         = M('TradeTheLine')->where($where)->setField('status','3');
        //加买家币
        $r[] = $this->_setUserCurrency($orderRes['buy_id'],$orderRes['num'],
            $orderRes['currency_id'],6,
            L('_XXJYGMRHQ_'),self::ST_FINANCE_INCOME,$orderRes['order_num']);
        
        // 是否要收取买家手续费
        if (!empty($orderRes['buy_fee']) && $orderRes['buy_fee'] > 0) {
            $r[] = $this->_setUserCurrency($orderRes['buy_id'],$orderRes['buy_fee'],
                $orderRes['currency_id'],8,
                L('_XXJYMRKCSXF_'),self::ST_FINANCE_EXPENSE,$orderRes['order_num']);
        }
        //返回结果
        if(in_array(false, $r)){
            return $this->return_error(10040,L('_XTFMSHCS_'));
        }
        return true;
    }
    
    /**
     *  处理提交卖出挂单时的数据库操作
     * @author 刘富国
     * @param  array $dataPost          出售订单信息
     * @param  array $userCurrencyInfo 用户个人资金信息
     * @return bool
     */
    public function processSellOrderInfo($dataPost,$user_id){
        $num          = $dataPost['num'];
        $currency_id   = $dataPost['currency_id'];
        $dataPost['order_num'] = $this->offTradingLogicsObj->genOrderId($user_id);
        // 扣卖家手续费
        if (!empty($dataPost['sell_fee']) && $dataPost['sell_fee'] > 0) {
            $ret = $this->_setUserCurrency($user_id,$dataPost['sell_fee'],$currency_id,8,
                L('_XXJYKCSXF_'),self::ST_FINANCE_EXPENSE,$dataPost['order_num']);
            if (!$ret)  return $this->return_error(10040,L('_SXFKCSBSHS_'));
        }
        //扣卖家金额
        $ret = $this->_setUserCurrency($user_id,$num, $currency_id,7,
            L('_XXJYGSRKC_'),self::ST_FINANCE_EXPENSE,$dataPost['order_num']);
        if (!$ret)  return $this->return_error(10040,L('_MCSB_'));
        $orderRes = $this->offTradingLogicsObj->generateOrder($dataPost,$user_id);
        // 生成订单失败，回滚数据库
        if (empty($orderRes))   return $this->return_error(10040,L('_MCSB_'));
        return $orderRes;
    }
    
    /**
     * 获取汇款银行卡信息
     *   @author  刘富国  20171130
     * @param $order_id
     * @param $user_id
     * @return bool|mixed
     */
    public function getUserBankInfo($order_id,$user_id){
        $where['id']     = $order_id;
        $where['status'] = array('in', array(0,1,2));
        $orderRes        = M('TradeTheLine')->where($where)->find();
        if (empty($orderRes)) return $this->return_error(10040,L('_GDDBKCZ_'));
        // 获取用户汇款银行信息
        $bankWhere['id'] = $orderRes['bank_id'];
        $bankWhere['status'] = 1;
        $fields          = 'bank_real_name,bank_list_id,bank_num,bank_address';
        $bankInfo        = M('UserBank')->field($fields)->where($bankWhere)->find();
        if (empty($bankInfo)) return $this->return_error(10040,L('_GJYQWBDYHK_'));
        $bankInfo['bank_name'] = formatBankType($bankInfo['bank_list_id']);
        $bankInfo['order_id']  = $order_id;
        $bankInfo['btn_str']   = ($orderRes['buy_id'] == $user_id) ? L('_QRYDK_') : L('_QRYSK_');
        return $bankInfo;
    }
    
    /**
     *  检测用户买入/卖出是否存在未完成的订单
     * @author 刘富国
     * @param  int    $user_id 	   用户id
     * @param  int    $level       用户等级
     * @param  int    $currency_id  币种id
     * @param  int    $type  1表示买入，2卖出
     * @return bool
     */
    public function checkOrderIsComplete($user_id, $level, $currency_id, $type=1){
        $configWhere['vip_level']   = $level;
        $configWhere['currency_id'] = $currency_id;
        $levelConfig                = M('LevelConfig')->where($configWhere)->find();
        $limitSellCount             = $levelConfig['sell_order'];
        $buyWhere['buy_id'] = $user_id;
        $buyWhere['status'] = array('in',array('0','1','2','8'));
        $buyCount           = M('TradeTheLine')->where($buyWhere)->count();
        $limitBuyCount      = $levelConfig['buy_order'];
        // 根据用户等级判断用户是否是否存在未完成的购买订单
        if ($buyCount > 0 and $buyCount >= $limitBuyCount  and $type == 1) {
            return $this->return_error(10031,L('_CZWWCDD_'));
        }
        $where['sell_id'] = $user_id;
        $where['status']  = array('in',array('0','1','2','8'));
        $sellCount        = M('TradeTheLine')->where($where)->count();
        // 根据用户等级判断用户是否是否存在未完成的销售订单
        if ($sellCount > 0 and $sellCount >= $limitSellCount and $type == 2) {
            return $this->return_error(10031,L('_CZWWCDD_'));
        }
        return true;
    }
    
    /**
     *  检测用户是否被锁定
     *  刘富国
     * @param $user_id
     * @return bool
     */
    public function checkUserIsOverTime($user_id){
        $userInfo            = M('User')->where(array('uid'=>$user_id))->find();
        $overRes = $this->publicFunctionObj->checkOvertime($userInfo['overtime_num'], $userInfo['overtime_time']);
        if ($overRes['code'] != 200) {
            return $this->return_error(10031,$overRes['msg']);
        }
        return true;
    }
    
    /**
     * 用戶收入或者支出金額處理
     * @param $user_id
     * @param $pawn_num     金額數量
     * @param $currency_id  貨幣類型
     * @param $finance_type
     * @param $desc
     * @param $expense_or_income_type 1為收入，2為支出
     * @return bool
     */
    protected function _setUserCurrency($user_id,$currency_num,$currency_id,
        $finance_type,$desc,$expense_or_income_type, $order_num='' ){
            
            //设置金额是增加还是减少
            $operationType = 'inc';
            if($expense_or_income_type == self::ST_FINANCE_EXPENSE)  $operationType = 'dec';
            //设置用户金额
            $ret[]  = $this->userMoneyApiObj->setUserMoney($user_id, $currency_id, $currency_num,
                'num',$operationType);
            // 获取用户余额
            $balance = $this->publicFunctionObj->getUserBalance($user_id, $currency_id);
            $dataArr = array(
                'financeType' => $finance_type,
                'content'     => $desc,
                'type'        => $expense_or_income_type,
                'money'       => $currency_num,
                'afterMoney'  => $balance,
                'remarkInfo'  => $order_num
            );
            $ret[]  = $this->userMoneyApiObj->AddFinanceLog($user_id, $currency_id, $dataArr);
            if(in_array(false,$ret)) {
                return $this->return_error(9999,$desc.L('_CZSB_'));
            }
            return true;
    }
    
    /**
     * 检测某币今日挂单货币数量总量是否超出限制
     * @author 刘富国
     * @param  int $currencyId 币种id
     * @param  int $userId     用户id
     * @param  float $num      出售数量
     * @param  int $level      用户等级
     * @return json|bool
     */
    public function checkCoinSellSum($currency_id, $user_id, $num, $level){
        $configWhere['vip_level']   = $level;
        $configWhere['currency_id'] = $currency_id;
        $levelConfig                = M('LevelConfig')->where($configWhere)->find();
        // 如果最大挂单总量没有设置，则不需要计算
        if ($levelConfig['day_max_sell_amount'] <= 0)  return true;
        //统计挂单总量
        $where['currency_id'] = $currency_id;
        $where['sell_id']     = $user_id;
        $where['status']      = array('in', array(0,1,2,3,4,8));
        $where['add_time']    = array('egt',strtotime(date('Y-m-d'),time()));
        $count                = M('TradeTheLine')->where($where)->sum('num'); //统计当日挂单快捷币数量
        // 今日的数量加上现在的对比
        if(  bcadd($count,$num,8) > $levelConfig['day_max_sell_amount']){
            return $this->return_error(10040,L('_JRGDZLCCXZ_'));
        }
        return true;
    }
    
    /**
     * 检测自身资金是否足够及计算卖出费用
     * @author 刘富国
     * @param $userId
     * @param  int $currencyId 币种id
     * @param  float $num       卖出数量
     * @param  int   $type     获取手续费，1表示卖出手续费，2表示买入手续费
     * @return array|bool
     */
    public function checkUserCurrencyIsAdequate($user_id,$currency_id, $num, $type=1){
        // 个人资金信息
        $userCurrencyInfo  = $this->userCurrencyModel->getUserCurrencyByUid($user_id, $currency_id);
        if(empty($userCurrencyInfo)){
            return $this->return_error(10040,L('_NXZBZXXYWLXGLY_'));
        }
        //自身账号有负数的情况
        if($userCurrencyInfo['num'] < 0 || $userCurrencyInfo['forzen_num'] < 0){
            return $this->return_error(10040,L('_NDZHYWMQBNJYLXGLY_'));
        }
        if ($userCurrencyInfo['num'] <= 0) {
            return $this->return_error(10040,L('_NDZJBZ_'));
        }
        // 获取卖出手续费
        $fee = $this->offTradingLogicsObj->getFee($currency_id, $num, $type);
        // 计算卖出数量加上手续费是否足够
        if ( bcadd($num,$fee,8) > $userCurrencyInfo['num']) {
            return $this->return_error(10040,L('_NDZJBZ_'));
        }
        return true;
    }
    
    /**
     * 检测用户的个人钱包币种是否和网站币种数量匹配，如果不匹配则增加
     * @author lirunqing 2017-10-16T17:35:25+0800
     * @param  int $userId 用户id
     * @return bool
     */
    public function checkCurrencyCount($userId){
        $currency     = M('Currency')->select();
        $where['uid'] = $userId;
        $userCurrency = M('UserCurrency')->where($where)->select();
        
        
        // 获取用户钱包的币种id
        $currencyTemp = array();
        foreach ($userCurrency as $key => $value) {
            $currencyTemp[$value['currency_id']] = $value['currency_id'];
        }
        
        // 获取已经开通的币并且用户钱包没有改币钱包的币种id
        $addCurrencyArr = array();
        foreach ($currency as $value) {
            if ($value['id'] == $currencyTemp[$value['id']] || $value['status'] == 0) {
                continue;
            }
            $addCurrencyArr[] = $value['id'];
        }
        
        if (empty($addCurrencyArr)) {
            return false;
        }
        
        $trans = M();
        $trans->startTrans();   // 开启事务
        
        foreach ($addCurrencyArr as $value) {
            $userCurrencyData['uid']         = $userId;
            $userCurrencyData['currency_id'] = $value;
            $allData[]                       = $userCurrencyData;
        }
        // 添加用户钱包数据 user_currency
        $userCurrencyId = M('UserCurrency')->addAll($allData);
        
        if (empty($userCurrencyId)) {
            $trans->rollback();// 事务回滚
            return false;
        }
        
        // 提交事务
        $trans->commit();
        
        return true;
    }
    
    
    
    
    
}