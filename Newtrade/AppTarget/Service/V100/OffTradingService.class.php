<?php
namespace AppTarget\Service\V100;

use AppTarget\Service\ServiceBase;
use Home\Logics\CtoCTransactionLogicsController;
use Home\Model\CurrencyModel;
use Common\Api\RedisCluster;
use Common\Logic\OffTrading;
use Common\Logic\CheckUser;
use Home\Logics\OffTradingLogicsController;
use Home\Logics\PublicFunctionController;
use Common\Api\redisKeyNameLibrary;
use SwooleCommand\Logics\TradePush;
use Home\Logics\CheckAllCanUseParam;
/**
 *
 * 线下交易
 * @author 劉富國
 * 2017-11-28
 *
 */
class OffTradingService extends ServiceBase{
    private $currencyModel = null;
    private $offTradingObj = null;
    private $offTradingLogicsObj = null;
    private $checkUserObj = null;
    private $publicFunctionObj = null;
    private $tradePushObj = null;
    private $checkAllCanUseParamObj = null;
    private $uid = 0;
    private $input_data = array();
    private $user_info = array();
    private $redis=NULL;
    const SELL_FEE_TYPE = 1; //卖家手续费类型
    const BUY_FEE_TYPE = 2;  //买家手续费类型
    const BUY_TYPE = 1; //买入
    const SELL_TYPE = 2; //卖出
    // 获取默认地区
    private $area_om_arr  = array(
            3 => '86',// 大陆
            2 => '886',// 台湾
            1 => '852',// 香港
            );
    private $area_name_arr = array();

    public function __construct()  {
        parent::__construct();
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->currencyModel = new CurrencyModel();
        $this->offTradingObj = new OffTrading();
        $this->offTradingLogicsObj = new OffTradingLogicsController();
        $this->checkUserObj = new CheckUser();
        $this->publicFunctionObj = new PublicFunctionController();
        $this->tradePushObj = new TradePush();
        $this->checkAllCanUseParamObj = new CheckAllCanUseParam();
        $this->area_name_arr = array( 1 => L('_ZGXG_'),
                                //      2 => L('_ZGTW_'),
                                      3 => L('_ZHONGGUO_')
                                    );
    }

    /**
     * 买家付款后两小时，卖家订单显示“收款异常”按钮，卖家可以点击“收款异常”
     * 未收到款项
     * @author 刘富国 2018-07-11
     * @return array|bool|int
     */
    public function unGetMoney(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $orderId = $this->input_data['order_id'];
        if (empty($orderId)) {
            return $this->return_error_num(10040,L('_SJYCQSHCS_'));
        }
        $userId      = $this->uid ;
        $where['id'] = $orderId;
        $orderRes    = M('TradeTheLine')->where($where)->find();
        if (empty($orderRes)) {
            return $this->return_error_num(10040,L('_GDDBKCZ_'));
        }

        if ($userId != $orderRes['sell_id']) {
            return $this->return_error_num(10040,L('_SJYCQSHCS_'));
        }

        if ($orderRes['status'] != 2) {
            return $this->return_error_num(10040,L('_SJYCQSHCS_'));
        }
        $trade_pwd =  $this->input_data['trade_pwd'];   // 校验资金密码
        if(empty($trade_pwd)) return $this->return_error_num(10000,L('_JYMMBNWK_'));
        $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($this->uid, $trade_pwd);
        if($tradePwdRes['code'] != 200) return $this->return_error_num(10000,$tradePwdRes['msg']);
        $offTradingLogicsObj = new OffTradingLogicsController();
        $abnormalSecond = ($orderRes['shoukuan_time']+$offTradingLogicsObj->abnormalSecond) - time();

        // 未到收款异常，发送请求
        if ($abnormalSecond > 0) {
            return $this->return_error_num(10040,L('_FEIFAQQ_'));
        }
        $saveArr['status'] = 8;  //待处理
        $saveArr['remark_info'] = '收款异常';
        $upRes = M('TradeTheLine')->where($where)->save($saveArr);
        if (empty($upRes)) {
            return $this->return_error_num(9999,L('_CZSB_'));
        }
        return array('is_success' => 1);
    }

    /**
     * 获取用户订单列表
     * @author 2017-11-01T12:35:33+0800
     * @return [type] [description]
     */
    public function getOrderList(){
        //检测网站是否处于维护状态
        $isMaintain = $this->offTradingLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10045,$isMaintain['msg'] );

        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $type   = $this->input_data['type'] ;
        $type   = !empty($type) ? $type : 1;
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
            '2' => array('in', array(1, 2)),
            '3' => array('in', array(0, 1, 2)),
            '4' => array('in', array(3, 4)),
            '5' => array('in', array(3, 4)),
            '6' => array('in', array(5,6,7)),
        );

        $filedStr         = $typeArr[$type];
        $valStr           = $statusArr[$type];
        $where[$filedStr] = $this->uid;
        $where['status']  = $valStr;
        $page = intval($this->input_data['page']);
        $limit = intval($this->input_data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $count     = M('TradeTheLine')->where($where)->count();
        if($count < 1) return (object)array() ;
        $orderBy   = ($type == 1) ? 'status desc,id desc,trade_time desc' : 'trade_time desc';
        $order_list = M('TradeTheLine')->where($where)->limit($limit)->order($orderBy)
                                     ->page($page)->select();
        if (empty($order_list)) return (object)array() ;
        $rateArr = $this->getConfigHUILV();
        $listRes = $this->offTradingObj->processOrderAppList($order_list,$this->uid,$rateArr);
        $ret = array(
            'total'  => $count,
            'list'   => $listRes,
            'pager'  => $this->_pager($page, ceil($count/$limit)),
        );
        return $ret;
    }

    /**
     * 处理用户打款/确认收款
     * @author 刘富国
     * @return [type] [description]
     */
    public function confirmOrderPaidOrOrderAccept(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $order_id = $this->input_data['order_id'];
        $where['id']     = $order_id;
        $where['status'] = array('in', array(1,2));
        $orderRes        = M('TradeTheLine')->where($where)->find();
        if (!$orderRes)  return $this->return_error_num(10040,L('_GDDBKCZ_'));
        // 卖家确认收款,校验资金密码
        if ($this->uid == $orderRes['sell_id'] && $orderRes['status'] == 2) {
            $trade_pwd = $this->input_data['trade_pwd'];
            if (empty($trade_pwd)) return $this->return_error_num(10000, L('_JYMMBNWK_'));
            $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($this->uid, $trade_pwd);
            if ($tradePwdRes['code'] != 200) return $this->return_error_num(10000, $tradePwdRes['msg']);
        }
        M()->startTrans();
        // 买家向卖家打款
        if ($this->uid == $orderRes['buy_id'] && $orderRes['status'] == 1) {
            $isTrue = $this->redis->get(redisKeyNameLibrary::OFF_LINE_DAKUANG_ORDER.$order_id);
            if (!empty($isTrue))  return $this->return_error_num(10040,L('_QWCFCZ_'));
            $this->redis->setex(redisKeyNameLibrary::OFF_LINE_DAKUANG_ORDER.$order_id, 10, true);
            $ret = $this->offTradingObj->confirmOrderPaid($orderRes);
        }
        // 卖家确认收款
        else if ($this->uid == $orderRes['sell_id'] && $orderRes['status'] == 2) {
            $isTrue = $this->redis->get(redisKeyNameLibrary::OFF_LINE_ACCEPT_ORDER.$order_id);
            if (!empty($isTrue)) return $this->return_error_num(10040,L('_QWCFCZ_'));
            $this->redis->setex(redisKeyNameLibrary::OFF_LINE_ACCEPT_ORDER.$order_id, 10, true);
            $ret = $this->offTradingObj->orderAccept($orderRes);
        }else{
            M()->rollback();
            return $this->return_error_num(10040,L('_GDDBKCZ_'));
        }
        if(!$ret){
            M()->rollback();
            return  $this->return_error_num($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        }
        M()->commit();
        // 极光消息
        // 买家向卖家打款
        if ($this->uid == $orderRes['buy_id'] && $orderRes['status'] == 1) {
            $this->offTradingLogicsObj->pushAppInfo(1,$orderRes);
        }
        // 卖家确认收款
        else if ($this->uid == $orderRes['sell_id'] && $orderRes['status'] == 2) {
            $this->offTradingLogicsObj->pushAppInfo(3,$orderRes);
        }
        return array('is_success' => 1);
    }

    /**
     * 获取待买入的订单列表
     * @author 刘富国 2017-11-29
     */
    public function getPendingPurchaseOrderList(){
        //检测网站是否处于维护状态
        $isMaintain = $this->offTradingLogicsObj->checkWebMaintain(1);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10045,$isMaintain['msg'] );
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $this->user_info = getUserForId( $this->uid);
        $om = str_replace('+','',$this->user_info['om']);
        $page = intval($this->input_data['page']);
        $limit = intval($this->input_data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $search_arr = $this->pending_purchase_order_search_param();
        $where = $search_arr['where'];
        $order_by = $search_arr['order_by'];
        $join1  = 'LEFT JOIN __USER__ as b on a.sell_id = b.uid';
        $join2  = 'LEFT JOIN __USER_BANK__ AS c ON a.bank_id = c.id';
        $count  = M('TradeTheLine')->alias('a')
            ->join($join1)
            ->join($join2)
            ->where($where)
            ->count();
        if($count < 1) {
            $count = 0 ;
            $order_list = [] ;
        }else{
            $field     = 'a.id,a.price,a.sell_id,a.bank_id as user_bank_id,a.num,a.om,a.currency_id,c.bank_list_id,
                        b.level,b.credit_level';
            $order_list = M('TradeTheLine')->alias('a')->field($field)
                ->join($join1)
                ->join($join2)
                ->where($where)
                ->order($order_by)
                ->limit($limit)
                ->page($page)
                ->select();
            if (!empty($order_list)) {
                $order_list = $this->set_pending_purchase_order_value($order_list,$om);
            }else{
                $order_list = [] ;
            }
        }
        //校验用户资格
        $this->checkP2PPower(self::BUY_TYPE,$om);
        $ret = array(
            'total'  => $count,
            'list'   => $order_list,
            'pager'  => $this->_pager($page, ceil($count/$limit)),
            'msg'  => $this->errmsg,
            'code'  => $this->errno,
        );
        return $ret;
    }

    /*
    * 獲取搜索列表搜索條件
   */
    private  function  pending_purchase_order_search_param() {
        $area_id     = $this->input_data['area_id'];
        $currency_id = $this->input_data['currency_id'];
        $bank_id     = $this->input_data['bank_id'];
        $num_start  = $this->input_data['num_start'];
        $num_end    = $this->input_data['num_end'];
        $price_start     = $this->input_data['price_start'];
        $price_end     = $this->input_data['price_end'];
        $userOm              = $this->get_user_om();
        $where['a.om'] = $userOm;
        // 根据用户id获取购买2次及以上的订单,
        $repeaetIdArr        = $this->offTradingLogicsObj->getOfftradingRepeatBuyersByUserid($this->uid);
        $repeaetIdArr[]      = $this->uid ; // 剔除当前用户

        // 币种筛选
        if (!empty($currency_id)) {
            $where['a.currency_id'] = $currency_id;
        }

        // 地区筛选
        if (!empty($area_id)) {
            $userOm        = $this->area_om_arr[$area_id];
            $where['a.om'] = $userOm;
        }

        // 银行筛选
        if (!empty($bank_id)) {
            $where['c.bank_list_id'] = $bank_id;
        }

        // 数量筛选
        if (!empty($num_start) && empty($num_end)) {
            $where['a.num'] = array('egt', $num_start);
        }

        // 数量筛选
        if (!empty($num_end) && empty($num_start)) {
            $where['a.num'] = array('elt', $num_end);
        }

        // 数量筛选
        if (!empty($num_end) && !empty($num_start)) {
            $where['a.num'] = array(array('egt', $num_start),array('elt', $num_end), 'AND');
        }

        // 价格筛选
        if (!empty($price_start) && empty($price_end)) {
            $where['a.price'] = array('egt', $price_start);
        }

        // 价格筛选
        if (empty($price_start) && !empty($price_end)) {
            $where['a.price'] = array('elt', $price_end);
        }

        // 价格筛选
        if (!empty($price_start) && !empty($price_end)) {
            $where['a.price'] = array(array('egt', $price_start),array('elt', $price_end), 'AND');
        }

        $where['a.status']  = 0;
        $where['a.sell_id'] = array('not in', $repeaetIdArr);
    //    $where['a.add_time'] = array('EGT', strtotime('-1 days'));  
        // 用户注册所在地区订单置顶，然后再显示其他用户
        $order_by   = ' a.price asc,b.credit_level desc,a.add_time desc';
        return array('where'=>$where,'order_by'=>$order_by);
    }


    //设置查询结果
    private function set_pending_purchase_order_value($order_list,$om){
        $areaArr  = array_flip($this->area_om_arr);
        $bank_list = M('BankList')->field('id,bank_logo,bank_name')->select();
        $bank_logo_arr = array_column($bank_list,'bank_logo','id');
        $ret_order_list = array();
        $symbolMoney=[
            "86"=>"CNY",
            "886"=>"TWD",
            "852"=>"HKD",
        ];
        $rateArr = $this->getConfigHUILV();

        $riskUserArr = $this->publicFunctionObj->getRiskUserList();// 获取存在风险的用户
        foreach ($order_list as $key => $item) {

            // 剔除存在风险的用户订单
            if (!empty($riskUserArr) && in_array($item['sell_id'], $riskUserArr)) {
                unset($order_list[$key]);
                continue;
            }

            $value['id'] = $item['id'];
            $value['level'] = $item['level'];
            $value['num'] = $item['num'];
            $value['price'] = $item['price'];
            $value['user_bank_id'] = $item['user_bank_id'];
            $value['bank_name'] =  formatBankType($item['bank_list_id']);
            $value['currency_id'] = $item['currency_id'];
            $value['refer_price'] = big_digital_mul($item['price'],$item['num'],2);
            $value['bank_log_pic'] = pic_path($bank_logo_arr[$item['bank_list_id']]);
            $value['area_name']   = !empty($this->area_name_arr[$areaArr[$item['om']]])
                                    ? $this->area_name_arr[$areaArr[$item['om']]]
                                    : '';
            //获取单位
            $value['unit'] = $symbolMoney[$item['om']];
            //获取参考价
            $value['reference_price'] = big_digital_mul($rateArr[$item['om']],$item['price'],2);
            //参考总价
            $value['reference_total_price'] = big_digital_mul($rateArr[$item['om']],$value['refer_price'],2);
            $value['rate'] = $rateArr[$item['om']];
            $value['trade_flag'] = 1;
            if( $om != $item['om'] ){
                $value['trade_flag'] = 0;
            }
            $ret_order_list[$key]    = $value;
        }
        return $ret_order_list;
    }
    /**
     * 获取汇率
     * @author 李江
     * @return array|int
     */
    private function getConfigHUILV(){
        $cToCTransLogicsObj = new CtoCTransactionLogicsController();
        $rateArr = $cToCTransLogicsObj->getConfigHUILV();
        return $rateArr;
    }
    /**
     * 购买订单前对购买者资格做校验
     * @return bool
     */
    private function checkPurchaserBeforeBuying(){
        $uid = $this->uid;
        $order_id = $this->input_data['order_id'];
        $ret = $this->checkP2PPower(self::BUY_TYPE);
        if (!$ret) return false;
        $ret = $this->offTradingObj->getUserBankInfo($order_id,$uid);  //订单汇款银行
        if(!$ret) return $this->return_error($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        return true;
    }
    /**
     * 处理购买数据
     * @return array|bool|int
     */
    public function buying(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $this->user_info = getUserForId( $this->uid);
        $order_id = $this->input_data['order_id'];
        $currency_id = $this->input_data['currency_id'];
        $bank_id = (int)$this->input_data['user_bank_id'];
         //检测网站是否处于维护状态
        $isMaintain = $this->offTradingLogicsObj->checkWebMaintain(2);
        if ($isMaintain['code'] != 200)  return $this->return_error_num(10045,$isMaintain['msg'] );
        //校验输入参数
        $ret = $this->checkBuyParams();
        if (!$ret) return $this->return_error_num($this->errno,$this->errmsg);
        //校验用户资格
        $ret = $this->checkPurchaserBeforeBuying();
        if (!$ret) return $this->return_error_num($this->errno,$this->errmsg);
        // 撤销订单和购买订单并发时，利用队列处理
        $checkBuykey = redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$order_id;
        $isBuy = $this->checkUserObj->checkConcurrencyControl($checkBuykey,$this->uid);
        if (!$isBuy) return $this->return_error_num(9999,L('_DQDDBZCCCZ_'));
        $buyData['currency_id'] = $currency_id;
        $buyData['bank_id']     = $bank_id;
        $buyData['id']    = $order_id;
        $buyData['uid']    = $this->uid;
        //处理买入订单相关信息
        $buyRes = $this->offTradingLogicsObj->processBuyOrderInfo($buyData,$this->uid);
        if (empty($buyRes) || $buyRes['code'] != 200) {
            return  $this->return_error_num(10040,$buyRes['msg']);
        }
        return array('is_success' => 1);
    }

    /**
     * 检测购买订单验证参数
     * @return bool
     */
    private function checkBuyParams(){
        $currency_id    = $this->input_data['currency_id'];		     //卖出币种id
        $user_bank_id       = (int)$this->input_data['user_bank_id'];		 //用户银行信息
        $trade_pwd      = $this->input_data['trade_pwd'];			 //资金密码
        $order_id       = $this->input_data['order_id']; 		     // 订单号
        if (empty($order_id))   return $this->return_error(10000,L('_NXZDDDBCZ_'));
        if(empty($user_bank_id)) return $this->return_error(10000,L('_HKYHWK_'));      // 银行信息不能为空
        if(empty($trade_pwd)) return $this->return_error(10000,L('_JYMMBNWK_'));  // 资金密码不能为空
        $isExits = M('TradeTheLine')->field('id,currency_id')->where(array('id' => $order_id))->find();
        if (empty($isExits))  return $this->return_error(10040,L('_NXZDDDBCZ_'));
        if($isExits['currency_id'] <> $currency_id)   return $this->return_error(10040,L('_NXZDBZBCZQQR_'));
        // 币种信息检测
        $currencyRes = $this->currencyModel->getCurrencyByCurrencyid($currency_id);
        if(empty($currencyRes) ) return $this->return_error(10000,L('_NXZDBZBCZQQR_'));
        if( $currencyRes['status'] == 0) return $this->return_error(10000,L('_GBZZZWH_'));
        $bankWhere['id'] = $user_bank_id;
        $bankInfo        = M('UserBank')->where($bankWhere)->find();
        if(empty($bankInfo))  return $this->return_error(10040,L('_HKYHXXCW_'));
        //验证资金密码的正确性
        $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($this->uid, $trade_pwd);
        if($tradePwdRes['code'] != 200){
            return $this->return_error(10000,$tradePwdRes['msg']);
        }
        return true;
    }

    /**
     * 卖家撤销订单
     * 刘富国
     * @return array|bool|int
     */
    public function revokeOrder(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $order_id = $this->input_data['order_id'];
        if(empty($order_id)) return 10000;
        //是否已经被购买
        $checkBuykey = redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$order_id;
        $isBuy = $this->checkUserObj->checkConcurrencyControl($checkBuykey,$this->uid);
        if (!$isBuy) return $this->return_error_num(9999,L('_DQDDBZCCCZ_'));
        $where['id']      = $order_id;
        $where['status']  = 0;
        $where['sell_id'] = $this->uid;
        $orderRes = M('TradeTheLine')->where($where)->find();
         if (empty($orderRes)) return $this->return_error_num(9999,L('_GDDBGMCXSB_'));
        //避免重复撤销操作
        $isTrue =  $this->redis->get(redisKeyNameLibrary::OFF_LINE_IS_REVOKE_ORDER.$order_id);
        if (!empty($isTrue)) return $this->return_error_num(10032,L('_QWCFCZ_'));
        $this->redis->setex(redisKeyNameLibrary::OFF_LINE_IS_REVOKE_ORDER.$order_id, 10, true);
        // 撤销订单
        $recRes = $this->offTradingLogicsObj->recallOrder($order_id,$this->uid);// 撤销订单
        if (empty($recRes) || $recRes['code'] != 200) {
            return  $this->return_error_num(9999,$recRes['msg']);
        }
        return array('is_success' => 1);
    }

    /**
     * 卖单的数据准备
     * 1，获取用户绑定的银卡卡信息
     * 2，获取货币单价和用户这货币余额
     * @author 刘富国
     * @return [type] [description]
     */
    public function getUserBindBankAndCurrencyInfo(){
       $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->user_info = getUserForId( $this->uid);
        $this->input_data = $this->getData();
        $currency_id = $this->input_data['currency_id'];
        $om = '';
        if(!empty($this->input_data['area_id'])){
            $om = $this->area_om_arr[$this->input_data['area_id']];
        }
        if($currency_id < 1 ) return 10000;
        //获取用户银行信息
        $ret_user_bank_list = $this->_get_user_bank($this->uid,
                                            $this->input_data['area_id'],
                                               $this->input_data['user_bank_id']);
        if(!$ret_user_bank_list){
            $ret_user_bank_list['area_info'] = [] ;
        }
        //获取货币单价和用户这货币余额
        $ret_user_currency_info = $this->offTradingObj->get_user_currency_info($this->uid,$currency_id);

        if(!$ret_user_currency_info){
            $ret_user_bank_list['currency_info'] = (object)array() ;
        }
        $ret_user_bank_list['currency_info'] = $ret_user_currency_info;
        //验证卖家资格
        $this->checkP2PPower(self::SELL_TYPE,$om);
        $ret_user_bank_list['msg']  = $this->errmsg;
        $ret_user_bank_list['code'] = $this->errno;
        return $ret_user_bank_list;
    }

    /**
     * 查询订单卖家的银行信息
     */

    public function getSellerBankInfo(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $order_id = $this->input_data['order_id'];
        if(empty($order_id)) return 10000;
        $where_order['id'] = $order_id;
        $where_order['buy_id'] = $this->uid;
        $order_info =  M('trade_the_line')->where($where_order)->find();
        if(empty($order_info) or empty($order_info['bank_id'])) return 9999;
        $where_bank['a.id'] = $order_info['bank_id'];
        $field = 'a.id as user_bank_id, a.bank_list_id,a.bank_real_name,a.bank_num,a.bank_address,b.bank_name';
        $user_bank_info = M('userBank')->alias('a')->field($field)
            ->join('__BANK_LIST__ b ON b.id= a.bank_list_id')
            ->where($where_bank)
            ->find();
        if(empty($user_bank_info)) return 10033;
        $user_bank_info['bank_name'] = formatBankType($user_bank_info['bank_list_id']);
        unset($user_bank_info['bank_list_id']);
        return $user_bank_info;
    }


    /**
     * 处理卖出订单数据
     * @author 刘富国
     * @return array|bool|int
     */
    public function selling(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $this->input_data = $this->getData();
        $this->user_info = getUserForId( $this->uid);
        $this->input_data['om']            = $this->area_om_arr[$this->input_data['area_id']];    //地区id
        $this->input_data['user_bank_id']  = (int)$this->input_data['user_bank_id'];		 //用户银行ID
        $this->input_data['price']  = getDecimal($this->input_data['coin_price'], 2); // 取小数4位,取正数
        $this->input_data['num']    = getDecimal($this->input_data['coin_num'], 4);	 // 卖出数量，取小数8位,取正数
        // 检测网站是否处于维护状态
        $isMaintain = $this->offTradingLogicsObj->checkWebMaintain(2);
        if ($isMaintain['code'] != 200)  return $this->return_error_num(10045,$isMaintain['msg'] );
        // 验证订单参数信息
        if (!$this->checkSellParams()) return $this->return_error_num($this->errno,$this->errmsg);
        //验证卖家资格
        if (!$this->checkSellBefore()) return $this->return_error_num($this->errno,$this->errmsg);
        //  卖家手续费
        $fee = $this->offTradingLogicsObj
                    ->getFee($this->input_data['currency_id'],$this->input_data['num'] , self::SELL_FEE_TYPE);
        //写入数据库
        $dataPost['om']          = $this->input_data['om'];
        $dataPost['currency_id'] = $this->input_data['currency_id'];
        $dataPost['price']       = $this->input_data['price'];
        $dataPost['num']         = $this->input_data['num'] ;
        $dataPost['bank_id']     = $this->input_data['user_bank_id'];
        $dataPost['sell_fee']    = $fee;
        $totalPrice = big_digital_mul($this->input_data['price'], $this->input_data['num'], 2);
        $isTotalPrice = $this->offTradingLogicsObj->checkTotalPrice($this->input_data['currency_id'], $totalPrice);
        if ($isTotalPrice['code'] != 200) {
            return $this->return_error_num(9999,$isTotalPrice['msg']);
        }

        //处理提交卖出挂单时的数据库操作
        M()->startTrans();
        $orderRes = $this->offTradingObj->processSellOrderInfo($dataPost,$this->uid);
        if(!$orderRes){
            M()->rollback();
            return  $this->return_error_num($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        }
        M()->commit();
        return array('is_success' => 1,'order_id' => $orderRes);
    }

    /**
     * 挂单卖家者资格做校验
     * @return bool
     */
    private function checkSellBefore(){
        $uid = $this->uid;
        $num = $this->input_data['num'];
        $om = $this->input_data['om'];
        $currency_id = $this->input_data['currency_id'];
        $this->user_info = getUserForId( $this->uid);
        //通用用户校验：实名认证，證件是否過期，资金密码
        $ret = $this->checkP2PPower(self::SELL_TYPE,$om);
        if (!$ret) return false;
        //todo  检测某币当日挂单总量是否超出限制,
      //  if(!$this->offTradingObj->checkCoinSellSum($currency_id, $uid, $num, $this->user_info['level']))
      //     return $this->return_error($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        // 检测自身资金是否足够
        if(!$this->offTradingObj->checkUserCurrencyIsAdequate($uid,$currency_id, $num, 1))
            return $this->return_error($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        return true;
    }

    /**
     * 买卖订单列表提示相关的校验
     * @param int $type  1表示买入，2卖出
     * @return bool
     */
    protected function checkP2PPower($type=self::BUY_TYPE,$om=''){
        $uid = $this->uid;
        $currency_id = $this->input_data['currency_id'];
        //检测用户是否被锁定
        if(!$this->offTradingObj->checkUserIsOverTime($uid))
            return $this->return_error($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        //通用用户校验：实名认证，證件是否過期，资金密码
        $ret = $this->checkAllCanUseParamObj->checkUserPower($uid);
        if ($ret['code'] <> 200)    return $this->return_error($ret['code'],$ret['msg']);
         //卖出校验是否绑定银行卡
        if(!empty($om) and $type == self::SELL_TYPE ){
            if( !checkUserBindBank($this->uid,$om) ) {
                return $this->return_error(30001,L('_GJYQWBDYHK_')); //未绑定银行卡
            }
        }
        //用户是否存在未完成的订单
        if(!empty($currency_id)){
            if(!$this->offTradingObj->checkOrderIsComplete($uid,$this->user_info['level'],$currency_id,$type))
                return $this->return_error($this->offTradingObj->errno,$this->offTradingObj->errmsg);
        }
        return true;
    }

    /**
     * 卖出订单信息检测
     * @author lirunqing 2017-10-12T16:09:19+0800
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    private function checkSellParams(){
        $om            = $this->input_data['om'];                //地区id
        $currency_id    = $this->input_data['currency_id'];		 //卖出币种id
        $price         = $this->input_data['price'];             //卖出单价
        $num           = $this->input_data['num'];			     //卖出数量
        $user_bank_id       = $this->input_data['user_bank_id']; //银行信息
        $trade_pwd      = $this->input_data['trade_pwd'];		 //资金密码

        if (empty($om)) return $this->return_error(10000,L('_QXZDQ_'));
        if(empty($currency_id)) return $this->return_error(10000,L('_QXZBZ_'));   // 币种信息检测
        // 币种信息检测
        $currencyRes = $this->currencyModel->getCurrencyByCurrencyid($currency_id);
        if(empty($currencyRes) ) return $this->return_error(10000,L('_NXZDBZBCZQQR_'));
        if( $currencyRes['status'] == 0) return $this->return_error(10000,L('_GBZZZWH_'));
        // 价格为空 或者 价格非数字 或者 价格小于0
        if (empty($price)
            or !is_numeric($price)
            or $price <= 0
            or !regex($price,'double')
        ) return $this->return_error(10000,L('_QSRYMCDDJ_'));
        // 数量校验
        if(empty($num)
            or !is_numeric($num)
            or $num <= 0
        )  return $this->return_error(10000,L('_QSRYMCDSL_'));
        $configWhere['currency_id'] = $currency_id;
        $coinConfig = M('CoinConfig')->where($configWhere)->find();
        // 每单最大数量限制
        if ($num > $coinConfig['maximum_num'])
            return $this->return_error(10000,L('_MDZD_').$coinConfig['maximum_num']);
        // 每单最小数量限制
        if ($num < $coinConfig['minimum_num'])
            return $this->return_error(10000,L('_MDZX_').$coinConfig['minimum_num']);
        // 银行信息不能为空
        if(empty($user_bank_id)) return $this->return_error(10000,L('_HKYHWK_'));
        $bankWhere['id']  = $user_bank_id;
        $bankWhere['uid'] = $this->uid;
        $bankInfo = M('UserBank')->where($bankWhere)->find();
        if(empty($bankInfo))  return $this->return_error(10000,L('_HKYHXXCW_'));
        // 资金密码不能为空
        if(empty($trade_pwd)) return $this->return_error(10000,L('_JYMMBNWK_'));
        //验证资金密码的正确性
        $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($this->uid, $trade_pwd);
        if($tradePwdRes['code'] != 200){
            return $this->return_error(10000,$tradePwdRes['msg']);
        }
        return true;
    }

    /**
     * 获取交易地区
     * @author 2017-11-29
     */
    public function getArea(){
        $area_list = array();
        foreach ($this->area_name_arr as $key => $item){
            $item_value['area_id'] = $key;
            $item_value['area_om'] = $this->area_om_arr[$key];
            $item_value['area_name'] = $item;
            $area_list[]= $item_value;
        }
        return $area_list;
    }

    /**
     * 根据地区获取银行相关信息
     * @author 2017-11-29
     */
    public function getBankInfoByArea(){
        $this->input_data = $this->getData();
        $area_id      = $this->input_data['area_id']*1;
        if($area_id < 1 ) return 10000;
        $countryCode = $this->area_om_arr[$area_id];
        $where['country_code'] = '+'.$countryCode;
        $bank_logo_str = "CONCAT('".C('PIC_DOMAIN')."',bank_logo) as bank_logo_pic";
        $bank_list   = M('BankList')
            ->field('id as bank_id ,bank_name,'.$bank_logo_str.'')
            ->where($where)
            ->select();
        if (empty($bank_list)) return (object)array() ;
        foreach ($bank_list as $key => $item){
            $bank_list[$key]['bank_name'] = formatBankType($item['bank_id']);
        }
        return $bank_list;
    }

    //根据用户手机获取用户所属地区
    private  function  get_user_om(){
        $userOm              = $this->user_info['om'];
        $userOm              = str_replace('+', '', $userOm);
        // 如果不是大陆，台湾，香港注册用户，则默认置顶香港订单
        $userOm              = (in_array($userOm, $this->area_om_arr)) ? $userOm : '852';
        return $userOm;
    }

    /**
     * 获取用户银行的信息（国家+银行信息）
     * @param $uid
     * @param $area_id
     * @param $user_bank_id
     * @return int
     */
    private function _get_user_bank($uid,$area_id,$user_bank_id){
        $rateArr  = $this->getConfigHUILV();
        $symbolMoney=[
            "86"=>"CNY",
            "886"=>"TWD",
            "852"=>"HKD",
        ];
        $areaArr  = array_flip($this->area_om_arr);
        $where['a.uid'] = $uid;
        $where['a.status'] = 1;
        if(!empty($area_id)){
            $om   = $this->area_om_arr[$area_id];    //地区id
            $country_code             = '+'.$om;
            $where['b.country_code'] = $country_code;
        }
        if(!empty($user_bank_id)){
            $where['a.id'] = $user_bank_id;
        }
        $field = 'a.id as user_bank_id,a.bank_real_name,a.bank_num,a.bank_address
        ,b.bank_name,b.country_code,a.bank_list_id';
        $user_bank_list = M('userBank')->alias('a')->field($field)
            ->join('__BANK_LIST__ b ON b.id= a.bank_list_id')
            ->where($where)
            ->order('a.default_status desc ')
            ->select();
        if(empty($user_bank_list)) return $this->return_error(10033,L('_QBDYHK_'));
        $country_code_arr = array_unique(array_column($user_bank_list,'country_code'));
        foreach ($country_code_arr as $item_country_code){
            $bank_list = array();
            $ret_user_bank = array();
            $country_code = str_replace('+','',$item_country_code);
            $area_id = $areaArr[$country_code];
            $ret_user_bank['area_id'] = $area_id;
            $ret_user_bank['area_om'] = $country_code;
            $ret_user_bank['area_name'] = $this->area_name_arr[$area_id];
            $ret_user_bank['area_rate'] = $rateArr[$country_code];
            $ret_user_bank['unit'] = $symbolMoney[$country_code];
            foreach ($user_bank_list as $key => $item_user_bank){
                if($item_user_bank['country_code'] <> $item_country_code) continue;
                $temp_value['user_bank_id'] = $item_user_bank['user_bank_id'];
                $temp_value['bank_name'] = formatBankType($item_user_bank['bank_list_id']);
                $temp_value['bank_real_name'] = $item_user_bank['bank_real_name'];
                $temp_value['bank_num'] = $item_user_bank['bank_num'];
                $temp_value['bank_address'] = $item_user_bank['bank_address'];
                $bank_list[] = $temp_value;
            }
            $ret_user_bank['bank_list']= $bank_list;
            $ret_user_bank_list['area_info'][] = $ret_user_bank;
        }
        return $ret_user_bank_list;
    }
}
