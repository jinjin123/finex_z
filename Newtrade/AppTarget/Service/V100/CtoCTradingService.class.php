<?php
/**
 * CTOC交易模块
 * @author 刘富国
 * 2018-03-12
 */
namespace AppTarget\Service\V100;
use AppTarget\Service\ServiceBase;
use Home\Logics\CtoCTransactionLogicsController;
use Common\Api\RedisCluster;
use Home\Logics\CheckAllCanUseParam;
use Common\Logic\OffTrading;
use Home\Logics\PublicFunctionController;
use Home\Controller\CoinTradeInfoController;
use Home\Tools\SceneCode;
use Common\Api\redisKeyNameLibrary;
use SwooleCommand\Logics\TradePush;

class CToCTradingService extends ServiceBase {
    private $input_data = array();
    private $uid = 0;
    private $cToCTransLogicsObj = null;
    private $redis = null;
    private $omArr = array();
    private $offTradingObj = null;
    private $publicFunctionObj = null;
    private $checkAllCanUseParamObj = null;
    public $omOfCurrencySymbol = array();
    public $msgArr = array(
        'code' => 200,
        'msg'  => '',
        'data' => array()
    );
    //redis锁过期时间 ,锁key前缀
    const  LOCK_EXPIRE =  3;
    const  LOCK_PREFIX = 'lock:order_id';

    public function __construct(){
        $this->uid = $this->getUserId();
        $this->input_data = $this->getData();
        $this->redis  = RedisCluster::getInstance();
        $this->checkAllCanUseParamObj = new CheckAllCanUseParam();
        $this->cToCTransLogicsObj = new CtoCTransactionLogicsController();
        $this->offTradingObj = new OffTrading();
        $this->publicFunctionObj = new PublicFunctionController();
        $this->omArr=[
            '86'=>L('_ZHONGGUO_'),
         //   '886'=>L('_ZGTW_'),/
            '852'=>L('_ZGXG_'),
        ];
        //交易区货币名称
        $this->omOfCurrencySymbol = array( 852 => 'HKD',
         //   886 => 'TWD',
            86 => 'CNY'
        );
    }

    /**
     * 获取交易地区
     * 刘富国
     * @return array
     */
    public function getArea(){
        $area_list = array();
        foreach ($this->omArr as $key => $item){
            $temp['area_name'] = $item;
            $temp['area_om'] = $key;
            $area_list[] = $temp;
        }
        return $area_list;
    }


    /**
     *  校验用户交易区有没银行卡
     */
    public function checkUserBindBank(){
        $this->uid = $this->getUserId();
        $areaCode = $this->input_data['area_code'];
        if($this->uid < 1   ) return 9998;
        if( empty($areaCode) ) return 10000;
        // 未绑定银行卡
        if (!checkUserBindBank($this->uid,$areaCode))  {
            return $this->return_error_num(10033,L('_GJYQWBDYHK_'));//'该交易区未绑定银行卡'
        }
        return array('is_success' => 1);
    }
    /**
     * 挂单数据准备
     * 刘富国
     * @return bool|int
     */
    public function prepareAddMainOrder(){
        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $currencyId = $this->input_data['currency_id'];
        if(empty($currencyId)) return 10000;
        $ret = $this->checkIsLimitOrder(); //校验用户资格
        if(!$ret) return $this->return_error_num($this->errno,$this->errmsg);
        $retCurr = $this->getCurrencyFee($currencyId); //手续费，保证金
        $returnData['currency_fee'] = $retCurr;
        $rateArr = $this->getConfigHUILV(); //汇率
        $omList = array();
        foreach ($this->omArr as $key => $item){
            $temp['area_name'] = $item;
            $temp['area_code'] = $key;
            $temp['area_rate'] = $rateArr[$key]*1;
            $temp['currency_symbol'] = $this->omOfCurrencySymbol[$key];
            $omList[] = $temp;
        }
        $returnData['om_arr'] = $omList;
        //获取货币单价和用户这货币余额
        $returnData['currency_info'] = (object)array() ;
        $ret_user_currency_info = $this->offTradingObj->get_user_currency_info($this->uid,$currencyId);
        if(!$ret_user_currency_info){
            $returnData['currency_info'] = (object)array() ;
        }
        unset($ret_user_currency_info['last_price']);
        $returnData['currency_info'] = $ret_user_currency_info;
        return $returnData;
    }

    /**
     * 获取汇率
     * @author 刘富国 2018-03-13
     * @return array|int
     */
    private function getConfigHUILV(){
        $rateArr = $this->cToCTransLogicsObj->getConfigHUILV();
        return $rateArr;
    }
    /**
     * 根据币种获取保证金/手续费
     * @author 刘富国 2018-03-13
     * @return array|int
     */
    private function getCurrencyFee($currencyId){
        $ret = $this->cToCTransLogicsObj->getCurrencyFee($currencyId);
        return $ret['data'];
    }

    /**
     * 检测用户是否可以挂单/交易
     * @author 刘富国
     * @return bool|int
     */
    private function checkIsLimitOrder(){
        $ret     = $this->checkAllCanUseParamObj->checkUserPower($this->uid);
        // 通用用户校验：实名认证，證件是否過期，资金密码
        if (empty($ret) || $ret['code'] != 200) {
            return $this->return_error(9999,$ret['msg']);
        }
        // 未绑定银行卡
        if (!checkUserBindBank($this->uid)) return $this->return_error(10033);
        // 检测用户是否在c2c交易禁止时间内
        $isDuringTime = $this->cToCTransLogicsObj->checkUserIsDuringTime($this->uid);
        if (empty($isDuringTime) || $isDuringTime['code'] != 200) {
            return $this->return_error(9999,$isDuringTime['msg']);
        }
        return true;
    }

    /**
     * 挂单业务处理
     * @author 刘富国 2018-03-12
     * @return bool|int
     */
    public function subTrade(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(2);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );

        $this->uid = $this->getUserId();
        if($this->uid < 1 ) return 9998;
        $data = $this->input_data;
        $userId            = $this->uid;
        $data['uid']       = $userId;
        $data['leave_num'] = $data['num'];
        $data['money']     = big_digital_mul($data['price'], $data['num'], 4);

        // 验证交易参数
        $ret = $this->checkParams($data,$this->uid);
        if($ret['code'] != 200 ) return $this->return_error_num(9999,$ret['msg']);

        // 检测用户是否存在未完成的订单
         $isExistOrder = $this->cToCTransLogicsObj->checkUserOrderNum($userId, $data['currency_type'], $data['type']);
         if ($isExistOrder['code'] != 200) {
             return $this->return_error_num(9999,$isExistOrder['msg']);
        }

        // 检测用户币种余额是否足够交易
        $currencyIsAd = $this->cToCTransLogicsObj->checkUserCurrencyIsAdequate($userId, $data['currency_type'], $data['num'], $data['type']);
        if (empty($currencyIsAd) || $currencyIsAd['code'] != 200) {
            return 30034;
        }

        // 检测用户交易总金额是否达到最小配置
        $isMinMoney = $this->cToCTransLogicsObj->checkIsMinMoney($data['currency_type'], $data['money']);
        if (empty($isMinMoney) ||$isMinMoney['code'] != 200) {
            $msg = L('_ZJEXYGDJE_')."$".$isMinMoney['data']['min_trade_money'];
            return $this->return_error_num(9999,$msg);//'总金额小于规定金额'
        }

        // 挂单卖出收取手续费
        if ($data['type'] == 2) {
            $data['fee']       = $this->cToCTransLogicsObj->getFee($data['currency_type'], $data['num'], $data['type']);
            $data['leave_fee'] = $data['fee'];
        }
        // 挂单买入保证金
        $bondNum = 0;
        if ($data['type'] == 1) {
            $bondNum           = $isMinMoney['data']['bond_num'];
            $data['bond_num']  = $bondNum;
        }
        // 扣除币种数量及添加财务日志
        $data['order_num'] = $this->cToCTransLogicsObj->genOrderId($userId);
        //开启事务
        M()->startTrans();
        $logRes = $this->cToCTransLogicsObj->processFinanceLogAndCurrencyNum($data, $bondNum,$userId);
        if (empty($logRes) || $logRes['code'] != 200) {
            M()->rollback(); // 事务回退
            return $this->return_error_num(9999,$logRes['msg']);
        }
        $addRes = $this->cToCTransLogicsObj->generateOrder($data,$userId);
        if (empty($addRes) || $addRes['code'] != 200) {
            M()->rollback(); // 事务回退
            return $this->return_error_num(9999,$addRes['msg']);
        }
        M()->commit(); // 事务提交
        return array('order_id' => $addRes['data']['order_id']);
    }

    /**
     * 挂单参数检测
     * @author 刘富国 2018-03-13
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    private function checkParams($data=array(),$userId){
        $res = array(
            'code' => 201,
            'msg'  => '',
            'data' => array()
        );

        if (!in_array($data['type'] ,array(1,2))) {
            $res['msg'] = L('_QXZZQDJYFS_') ;//"请选择正确的交易方式";
            return $res;
        }

        if (empty($data['currency_type'])) {
            $res['msg'] = L('_QXZBZ_')."1";
            return $res;
        }

        $currencyExist = $this->cToCTransLogicsObj->checkCurrencyExist($data['currency_type']);
        if (!$currencyExist) {
            $res['msg'] = L('_GBZZZWH_');
            return $res;
        }

        if (empty($data['om'])) {
            $res['msg'] = L('_QXZDQ_');
            return $res;
        }

        if (empty($data['price'])) {
            $res['msg'] = L('_QSRYMCDDJ_');
            return $res;
        }

        // 价格非数字
        if(!is_numeric($data['price'])){
            $res['msg'] = L('_QSRZQDDJ_');
            return $res;
        }

        // 价格应该大于0
        if($data['price'] <= 0){
            $res['msg'] = L('_QSRZQDDJ_');
            return $res;
        }

        if(!regex($data['price'],'double')){
            $res['msg'] = L('_QSRZQDDJ_');
            return $res;
        }

        // 数量不能为空
        if(empty($data['num'])) {
            $res['msg'] = L('_QTXSL_');
            return $res;
        }
        $numMsg = ($data['type'] == 1) ? L( '_QSRZQDMRSL_') : L('_QSRZQDCSSL_');
        // 数量应是数字
        if(!is_numeric($data['num'])){
            $res['msg'] = $numMsg;
            return $res;
        }

        // 数量应大于0
        if($data['num'] <= 0){
            $res['msg'] = $numMsg;
            return $res;
        }

        // 交易密码不能为空
        if(empty($data['tradepwd'])) {
            $res['msg'] = L('_JYMMBNWK_');
            return $res;
        }
        //验证交易密码的正确性
        $tradePwdRes = $this->publicFunctionObj->checkUserTradePwdMissNum($userId, $data['tradepwd']);
        if($tradePwdRes['code'] != 200){
            $res['msg'] = $tradePwdRes['msg'];
            return $res;
        }
        //通用用户校验：实名认证，證件是否過期，资金密码
        $retReal     = $this->checkAllCanUseParamObj->checkUserPower($userId);
        if (empty($retReal) || $retReal['code'] != 200) {
            $res['msg'] = $retReal['msg'];
            return $res;
        }

        // 卖单，检测该交易区未绑定银行卡
        if (!checkUserBindBank($userId,$data['om']) and $data['type'] == 2 ) {
            $res['msg'] = L('_GJYQWBDYHK_');//'该交易区未绑定银行卡';
            return $res;
        }


        // 检测用户是否在c2c交易禁止时间内
        $isDuringTime = $this->cToCTransLogicsObj->checkUserIsDuringTime($userId);
        if (empty($isDuringTime) || $isDuringTime['code'] != 200) {
            $res['msg'] = $isDuringTime['msg'];
            return $res;
        }
        $res['code'] = 200;
        return $res;
    }

    /*
     * 获取市场订单列表
     * 李江
     * 2018年3月12日14:15:37
     */
    public function getTrade(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(1);
        if ($isMaintain['code'] != 200)  return $this->return_error_num(10047,$isMaintain['msg'] );

        $inputData = $this->input_data;
        $uid = $this->uid;
        $page = $inputData['page'] <= 0 ? 1 : $inputData['page'];
        $limit = intval($inputData['limit']);
        $limit = $limit <= 0 ? 10 : $limit;
        
        if( $uid <= 0 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));//用户名不能空
        }
        if( $inputData['type'] == null || !in_array($inputData['type'],[1,2]) ){
            return $this->return_error_num(10000,L('_QQCSCC_'));//用户名不能空
        }
        if( $inputData['om'] != null && $inputData['om'] != '' ){
            $where['a.om'] = trim($inputData['om']);
        }else{
            $om = M('User')->where(['uid'=>$uid])->getField('om');
            $where['a.om'] = substr($om,1);
        }

        if( $inputData['currency_type'] == null || $inputData['currency_type'] == '' ){
            $inputData['currency_type'] = 1;
        }
        $where['a.currency_type'] = $inputData['currency_type'];
        //获取挂买单或者挂卖单
        $returnData = $this->buyOrSellOrderList($where,$uid,$inputData['type'],$limit,$page);
        //权限验证
        $res = $this->checkPower($uid,$inputData['om']);
        $returnData['msg']  = $res['msg'];
        $returnData['code'] = $res['code'];
        return $returnData;
    }
    /*
     * 李江
     * app设置主订单是否显示
     * 返回值 1显示  0 不显示
     */
    public function setOrderDisplay(){
        $uid = $this->getUserId();
        $data = $this->getData();
        if( $uid < 1 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));//用户名不能空
        }
        $order_display = $data['status'];
        if( $order_display == false || $order_display == null ){
            $order_display = 0;
        }
        $res = M('CcComplete')->where(['uid'=>$uid])->setField('order_display',$order_display);
        if( $res ){
            return ['is_success'=>1,'order_display'=>$order_display];
        }else{
            return $this->return_error_num(9999,L('_CZSB_'));
        }
    }

    /*
     * 李江
     * app获取主订单是否显示状态
     * 返回值 1显示 0不显示
     */
    public function getOrderDisplay(){
        $uid = $this->getUserId();
        if( $uid < 1 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));//用户名不能空
        }
        $status = $this->cToCTransLogicsObj->checkUserOrderDisplay($uid);
        return ['status'=> $status];
    }

    /**@买单列表
     * @param $where
     * @return mixed
     * @author 李江
     * @desc   獲取購買列表
     * 规则
     *      type=1 获取买单 2获取卖单
     *      $limit
     *      $page
     *      状态为挂单中
     *      时间为48小时以内
     *      不展示本人的订单
     */
    private function buyOrSellOrderList($where,$uid,$type,$limit,$page){
        //该订单完成单数
        $rateArr = $this->getConfigHUILV();
        
        $where['a.type'] = $type;
        $where['a.status'] = array('EQ',"1"); // 刪選掉已完成的訂單
      //  $time = time() - 48*3600;
      //  $where['a.add_time'] = array('EGT',$time); // 筛选未超时的订单
        $where['a.uid']  = array('NEQ',$uid); // 刪選掉自己的订单
        $where1['b.order_display'] = 1;
        $order = $type==1 ? 'price DESC' : 'price ASC';
        $count = M('CcOrder')->alias('a')->join('__CC_COMPLETE__ b ON a.uid=b.uid')->where($where)->where($where1)->count();

        $data = M('CcOrder')->alias('a')->join('__CC_COMPLETE__ b ON a.uid=b.uid')
            ->field('a.id,a.uid,a.currency_type,a.start_hide_hour,a.end_hide_hour,a.leave_num,a.success_num,a.price,a.num,a.om,b.small_order_time,b.break_order_time')
            ->where($where)->where($where1)->order($order)
            ->select();
        if(empty($data)) $data = array();
        $riskUserArr =  $this->publicFunctionObj->getRiskUserList();// 获取存在风险的用户
        //添加刷单数据
        $data = $this->cToCTransLogicsObj->scalpingOrder($data, $type,$where['a.om'],$where['a.currency_type']);

        $totals = count($data);
        $countPage = ceil($totals / $limit); #计算总页面数
        $data =  page_array($limit,$page,$data);
        foreach($data as $key=>$value) {

            if (!empty($riskUserArr) && in_array($value['uid'], $riskUserArr)) {
                unset($data[$key]);
                continue;
            }
            unset($data[$key]['uid']);

            $data[$key]['complete_rate'] =  big_digital_div( $value['small_order_time']-$value['break_order_time'],$value['small_order_time'],4) * 100 . '%';
            $tradeWhere['pid'] = $value['id'];
            $data[$key]['order_count'] = $value['small_order_time'];
            if( $this->checkOvertime($value['start_hide_hour'],$value['end_hide_hour']) || $this->checkMinOrderNum($value) ){
                unset($data[$key]);
                continue;
            }
            $data[$key]['reference_price'] = big_digital_mul($rateArr[$value['om']],$value['price'],2);
            $data[$key]['currency_symbol'] = $this->cToCTransLogicsObj->omOfCurrencySymbol[$value['om']];
            $data[$key]['money'] = bcmul($value['leave_num'],$value['price'],2);
            unset($data[$key]['small_order_time']);
            unset($data[$key]['break_order_time']);
        }
        if( empty($data) ){
            $data = [];
        }
        return ['total' => $count, 'list' => array_values($data), 'pager' => $this->_pager($page, $countPage)];
    }

    /**
     * 检测订单是否小于最小金额
     * @param $value 主订单信息
     * @return bool
     */
    private function checkMinOrderNum($value){
        $totalMoney = big_digital_mul( $value['leave_num'],$value['price'],2);
        $minConfig = $this->getMinConfig();
        if( $totalMoney < $minConfig[$value['currency_type']] ){
            return true;
        }
        return false;
    }
    private function getMinConfig(){
        $minConfig = M('CcConfig')->field('min_trade_money,currency_id')->select();
        $returnConfig = [];
        foreach ($minConfig as $item) {
            $returnConfig[$item['currency_id']] = $item['min_trade_money'];
        }
        return $returnConfig;
    }
    /** 检测用户订单是否在某个时间段内
     * @param $value
     * @return bool
     * @author ljiang
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
    public function getUserCheckInfo(){
        $inputData = $this->input_data;
        $uid = $this->uid;

        if( $uid <= 0 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));//用户名不能空
        }
        if( $inputData['om'] == null ){
            return $this->return_error_num(10000,L('_QQCSCC_'));//参数有误
        }
        $res = $this->checkPower($uid,$inputData['om']);
        if( $res['code'] !=  200 ){
            return $this->return_error_num($res['code'],$res['msg']);
        }else{
            return $res;
        }
    }
    
    //获取市场订单前的权限校验
    public function checkPower($uid,$om){
         //通用用户校验
         $res = $this->checkAllCanUseParamObj->checkUserPower($uid);
         if( $res['code'] != 200 )  return $res;
        //检测是否24h违规限制
        $res = $this->cToCTransLogicsObj->checkUserIsDuringTime($uid);
        if( $res['code'] != 200 ) {
            return ['code'=>30032,'msg'=>$res['msg']];
        }
        //是否绑定银行卡
        if( !checkUserBindBank($this->uid,$om) ) {
            return ['code'=>30001,'msg'=>L('_GJYQWBDYHK_')];//未绑定银行卡
        }
        return ['code'=>200,'msg'=>''];
    }

    /*
     * 获取主订单相关信息
     * 李江 2018年3月13日20:18:05
     */
    public function getSubOrderInfo(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );

        $uid = $this->uid;
        $inputData = $this->input_data;
        if( $uid <= 0 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));
        }
        if( $inputData['orderId'] == null || $inputData['type'] == null ){
            return $this->return_error_num(10000,L('_QQCSCC_'));
        }
        $orderId = $inputData['orderId'];
        $tradeType = $inputData['type'];
        if(strpos($orderId,'S')!== false){
            //传入的买卖单类型是对应的是子单的，跟主单的反了，要换一下
            if($tradeType == 1) {
                $scalping_type = 2;
            }else{
                $scalping_type = 1;
            }
            //刷单订单
            $returnData = $this->cToCTransLogicsObj->getScalpingOrderInfoApp($orderId,$uid,$scalping_type);
            if($returnData['code'] <> 200) {
                return $this->return_error_num($returnData['code'],$returnData['msg']);
            }
        }else{
            //正常订单
            $returnData = $this->cToCTransLogicsObj->getOrderInfoApp($orderId,$uid,$tradeType);
            if($returnData['code'] <> 200) {
                return $this->return_error_num($returnData['code'],$returnData['msg']);
            }
        }
        return $returnData['data'];
    }



    /** 获取主订单列表
     *  @author zhangxiwen
     *  @time   2018年3月12日14:34:22
     * */
    public function getUserMainOrderList(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );

        if( $this->uid <= 0 ){
            return $this->return_error_num(10001,L('_YHMBNWK_'));
        }
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $ret_list = $this->cToCTransLogicsObj->getMyUserMainOrderListApp($this->uid,$page,$limit);
        $total = $ret_list['data']['count']*1;
        if(empty($ret_list['data']['list'])){
            return  (object)array();
        }

        $ret = array(
            'total'  => $total,
            'list'   => $ret_list['data']['list'],
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );
        return $ret;
    }

    /**
     * 撤销主订单
     * @return array
     * @author zhangxiwen
     * @time   2018年3月12日14:35:40
     * @return array|bool|int
     */
    public function revokeBigOrder(){
        $uid = $this->uid;
        $orderNum = trim($this->input_data['orderId']);
        if($uid < 1 || $orderNum == '' || $orderNum == null )  return 10000;

        //如果主订单有人买入卖出则不允许撤销 5s内
        $mainOderRevok=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$orderNum;
        $haveSmallTrade=$this->redis->get($mainOderRevok);
        if($haveSmallTrade)
        {
            return $this->return_error_num(30053,L('_CXSBQSHCS_'));
        }
        //改变订单状态为撤销
        $orderRevok=redisKeyNameLibrary::PC_C2C_TRADE_REVOKED_CANNOT_OPERAT.$orderNum;
        $this->redis->setex($orderRevok, 5, 1);

        M()->startTrans();
        $res = $this->cToCTransLogicsObj->revokeBigOrder($orderNum,$uid);
        if( $res['code'] <> 200 ){
            M()->rollback();
            return $this->return_error_num(9999,$res['msg']);
        }
        M()->commit();
        //删除主单的缓存数据
        $this->redis->del(redisKeyNameLibrary::PC_C2C_MIAN_ORDER_ID_INFO.$orderNum);
        return array('is_success' => 1);
    }


    /**
     * 确认打款/收款
     * @return array
     * @author zhangxiwen
     * @time   2018年3月12日14:49:51
     * @return array|bool|int
     */
    public function confirmOrderAcceptOrPaid(){
        $userId  = $this->uid;
        $orderId = $this->input_data['orderId'];
        $type = $this->input_data['type']*1; //订单类型：1买单 2卖单
        if($userId < 1 or $orderId < 1 or !in_array($type,array(1,2)) ) {
            return 10000;
        }
        $where['id']     = $orderId;
        $where['status']     = array('in',array(1,2));
        $where['sell_id|buy_id'] = $userId;
        $orderRes        = M('CcTrade')->where($where)->find();
        if (empty($orderRes)   ) {
            return $this->return_error_num(9999,L('_GDDBKCZ_'));
        }
        M()->startTrans();
        //确认打款
        if($orderRes['status'] == 1 && $userId == $orderRes['buy_id'] ){
            $isConfirm = $this->redis->get(redisKeyNameLibrary::PC_C2C_CONFIRM_ORDER.$orderId);
            if (!empty($isConfirm))  return $this->return_error_num(9999,L('_QWCFCZ_'));
            $res = $this->cToCTransLogicsObj->confirmTradeOrderPaid($userId,$orderId);
            $this->redis->setex(redisKeyNameLibrary::PC_C2C_CONFIRM_ORDER.$orderId, 10, 1);
        }
        //确认收款
        elseif($orderRes['status'] == 2 && $userId == $orderRes['sell_id'] ){
            $isAccept = $this->redis->get(redisKeyNameLibrary::PC_C2C_ACCEPT_ORDER.$orderId);
            if (!empty($isAccept))  return $this->return_error_num(9999,L('_QWCFCZ_'));
            $res = $this->cToCTransLogicsObj->orderAccept($userId, $orderId);
            $this->redis->setex(redisKeyNameLibrary::PC_C2C_ACCEPT_ORDER.$orderId, 10, 1);
        }
        if (empty($res) || $res['code'] !=200 ) {
            M()->rollback();
            return $this->return_error_num(9999,$res['msg']);
        }
        M()->commit();
        //极光推送
        $tempOrderInfo['currencyName'] = getCurrencyName($orderRes['currency_type']) ; 
        $tempOrderInfo['rate_total_money'] = $orderRes['rate_total_money']   ;
        $tempOrderInfo['num']=$orderRes['trade_num'];       //数量
        $extras['send_modle'] = 'C2C';
        $extras['new_order_penging'] = '1';
        //付款成功，通知卖家
        if(($orderRes['status'] == 1)){
            $tempOrderInfo['orderNum'] = $orderRes['order_num'];
            $contentStr = SceneCode::getC2CTradeTemplate(1,'+'.$orderRes['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            $title      = $contentArr[0];
            $content    = $contentArr[1];
            $ret =  push_msg_to_app_person($title, $content, $orderRes['sell_id'],$extras);
        }
        //收款成功，通知买家
        if(($orderRes['status'] == 2)){
            $tempOrderInfo['orderNum'] = $orderRes['order_num_buy'];
            $contentStr = SceneCode::getC2CTradeTemplate(3,'+'.$orderRes['om'],$tempOrderInfo);
            $contentArr = explode('&&&', $contentStr);
            $title      = $contentArr[0];
            $content    = $contentArr[1];
            $ret= push_msg_to_app_person($title, $content, $orderRes['buy_id'],$extras);
        }
        return array('is_success' =>1);
    }

    /***用户确认未收到款项
     * return arr
     * author zhangxiwen
     * time   2018年3月12日14:50:32
     */
    public function unReceiptTradeOrderPaid(){
        $uid = $this->uid;
        $orderId = $this->input_data['orderId'];
        if($uid < 1 or $orderId < 1) {
            $this->msgArr['code'] = 10000;
            $this->msgArr['msg'] = L('_QQCSCC_');
            return $this->return_error_num($this->msgArr['code'],$this->msgArr['msg']);
        }
        M()->startTrans();
        $ret = $this->cToCTransLogicsObj->unReceiptTradeOrderPaid($uid,$orderId);
        if($ret['code']<>200){
            M()->rollback();
            return $this->return_error_num(9999,$ret['msg']);
        }
        M()->commit();
        return array('is_success' => 1);
    }

    /**根据订单id获取订单数据（用于确认收款/确认未收到款项展示页面）
     * return arr
     * author zhangxiwen
     * time   2018年3月12日15:08:46
     */
    public function userConfirmPage(){
        $uid = $this->uid;
        if($uid < 1) return  9998;
        $orderId = $this->input_data['orderId'];
        $ret = $this->cToCTransLogicsObj->getTradeInfo($orderId,$uid);
        if($ret['code']<>200 or empty($ret['data'])){
            return (object)array();
        }
        return $ret['data'];
    }

    /**
     * 获取未完成订单
     * @author zhangxiwen
     * @return array|int|object
     */
    public function getHangIntheAirTradeOrder(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );
        $uid = $this->uid;
        if($uid < 1) return  9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $retList = $this->cToCTransLogicsObj->getHangIntheAirTradeOrder($uid);
        $orderList = $retList['data']['list'];
        if(empty($orderList)){
            return  (object)array();
        }
        $totals = count($orderList);
        $countPage = ceil($totals / $limit); #计算总页面数
        $orderList =  page_array($limit,$page,$orderList);
        if(empty($orderList)){
            return  (object)array();
        }
        $ret = array(
            'total'  => $totals,
            'list'   => $orderList,
            'pager'  => $this->_pager($page, $countPage),
        );
        return $ret;
    }

    /**
     * 已完成的订单
     * @return array|int
     * author zhangxiwen
     */
    public function getCompletedTradeOrder(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );
        $uid = $this->uid;
        if($uid < 1) return  9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $ret_list = $this->cToCTransLogicsObj->getCompletedTradeOrder($uid,$page,$limit);
        $total = $ret_list['data']['count']*1;
        if(empty($ret_list['data']['list'])){
            return (object)array();
        }
        $ret = array(
            'total'  => $total,
            'list'   => $ret_list['data']['list'],
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );
        return $ret;
    }

    /**获取撤销的订单
     * @return array|int
     * @author zhangxiwen
     */
    public function getRevokeTradeOrder(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(3);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );
        $uid = $this->uid;
        if($uid < 1) return  9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $ret_list = $this->cToCTransLogicsObj->getRevokeTradeOrder($uid,$page,$limit);
        $total = $ret_list['data']['count']*1;
        if(empty($ret_list['data']['list'])){
            return (object)array();
        }
        $ret = array(
            'total'  => $total,
            'list'   => $ret_list['data']['list'],
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );
        return $ret;
    }
 
//=====================c2c买卖========================================
    /**
     * @method C2C买卖接口
     * @author 李江 2018年8月1日19:53:19 
    */
    public function buyingOrSellingOrder()
    {
        $data = $this->input_data;
        $id         = $data['id'];
        //检测是否是刷单数据
        if(stripos($data['id'], 'S')!==false) return $this->return_error_num(10000,L('_GDDYJWCHCX_'));
        if(!is_numeric($id) || $id<1)  return $this->return_error_num(10000,L('_CSCW_'));

        $order_key  = redisKeyNameLibrary::PC_C2C_MAIN_ORDER_NUM.$id;
        $incr_key   = redisKeyNameLibrary::PC_C2C_BUY_SELL_NUM.$id;
        $lock_key   = self::LOCK_PREFIX.$id;
        $lock_status= $this->redis->get($lock_key);
        $total_num  = $this->redis->get($order_key);
        if($lock_status || empty($total_num)) return $this->return_error_num(9999,L('_GDDBKCZ_'));
        while(true){
            $lockValue     = time() + self::LOCK_EXPIRE;
            $lock          = $this->redis->setnx($lock_key, $lockValue);

            if(!empty($lock) || ($this->redis->get($lock_key) < time() && $this->redis->getSet($lock_key, $lockValue) < time())){
                $this->redis->expire($lock_key,self::LOCK_EXPIRE);
                return $this->proccessProgram();
                break;
            }else{
                usleep(500000);  //进程暂停多少毫秒 ,500毫秒0.5秒
                $incr_num = $this->redis->get($incr_key);
                if($incr_num >=$total_num)  return $this->return_error_num(9999,L('_GDDYJWCHCX_'));
            }
        }
   }


    /**
     * @method 买卖接口订单
     * @author 建强  2018年2月27日18:04:21
     * @param  int     $id                订单ID
     * @param  int     $num               数量
     * @param  float   $money             金额
     * @param  int     $user_bank_id      用户银行卡id
     * @param  string  $trade_pass        交易密码
     * @param  int     $type              买卖类型
     * @param  int     $currecny_type     币种类型
     */
    public function proccessProgram(){
        //检测网站是否处于维护状态
        $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(2);
        if ($isMaintain['code'] != 200)   return $this->return_error_num(10047,$isMaintain['msg'] );
        $uid  = $this->uid;
        $data = $this->input_data;
        $lock_key = self::LOCK_PREFIX.$data['id'];
        //验证基本参数
        $ret=$this->checkBasicParamIncludeTwdUserRealAndBank($data,$uid);
        if($ret['code']!=200)
        {
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(10000,$ret['msg']);
        }
        //防止二次提交数据
        $Retprev=$this->PreventSubmitSecond($data['id'], $uid);
        if($Retprev['code']!=200)
        {
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(30008,$Retprev['msg']);
        }
        //检验主订单是否完成
        $retMainOrder=$this->cToCTransLogicsObj->checkOrderIsFinshed($data['id'],$data['num'],$data['money'],$uid);
        if($retMainOrder['code']!=200)
        {
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(30001,$retMainOrder['msg']);
        }
        //检测订单业务 绑定银行卡 资金充足 最小配额
        $retBankMoney=$this->checkBankAndMoneyAndMinmoney($data,$retMainOrder['data'],$uid);
        if($retBankMoney['code']!=200)
        {
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(30002,$retBankMoney['msg']);
        }

        //撤销订单  （无法购买 ）  买入时  （无法撤销操作）
        $retRevok=$this->revokeOrPervent($data['id']);
        if($retRevok['code']>200)
        {
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(30003,$retRevok['msg']);
        }
        //防止撤销
        $this->setDispatchKey($data['id'],$uid);
        //进行数据处理
        M()->startTrans();
        $proRet= $this->cToCTransLogicsObj->proccessBuyingOrder($data,$uid,$retMainOrder['data']);

        if($proRet['code']!= 200)
        {
            M()->rollback();
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            return $this->return_error_num(30039,$proRet['msg']);
        }
        if(in_array(false, $proRet['data']['addArr']))
        {
            M()->rollback();
            if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
            $msg=($data['type'] == 2)?L('_MCSBXTFM_'):L('_MRSBXTFM_');
            return $this->return_error_num(30039,$msg);
        }

        M()->commit();
        //累计卖出总数
        $incr_key  = redisKeyNameLibrary::PC_C2C_BUY_SELL_NUM.$data['id'];
        $this->redis->incrbyfloat($incr_key,$data['num']);
        if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);

        //推送消息，通知买家付款
        if($data['type'] == 2)
        {
            $pushObj=new TradePush();
            $pushObj->pushExec($proRet['data']['order_id'],300000,'c2c');  //第二个毫秒时间单位5min后
        }
        $res['msg']  = ($data['type'] == 2) ? L('_MCCG_') : L('_MRCG_');
        $res['data']['order_id']  = $proRet['data']['order_id'];
        $res['data']['leave_num'] = $proRet['data']['leave_num'];
        return  $res;
    }
   
   /**
    * @method 防止用户重复提交数据
    * @param  unknown $id
    * @param  unknown $uid
    * @return array
    */
   protected function PreventSubmitSecond($id,$uid)
   {  
   	    $res=[
   	        'code'=>200,
   	        'msg'=>'',	
   	    ];
	   	$key=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL.$id.$uid;
	   	$secodtime=$this->redis->get($key);
	   	if(!empty($secodtime))
	   	{
	   		$res['msg']=L('_QWCFCZ_');
	   		$res['code']=601;
	   	}
	   	return $res;
   }
   
   /**
    * @method 设置redis key值  防止二次重复提交二次脏数据
    */
   protected function setDispatchKey($id,$uid)
   {
	   	//防止提交
	   	$key=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL.$id.$uid;
	   	//进行买卖  防止主订单撤销
	   	$mainOderRevok=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$id;
	   	$this->redis->setex($key, 5, 1);
	   	$this->redis->setex($mainOderRevok, 5, 1);
   }
   /**
    * @method 买卖接口防止撤销 撤销防止买卖
   */
   protected function revokeOrPervent($id)
   { 
	   	$res=[
	   		'code'=>200,
	   	    'msg'=>'',
	   	];
	   	//正在撤销
	   	$key=redisKeyNameLibrary::PC_C2C_TRADE_REVOKED_CANNOT_OPERAT.$id;
	   	$revokOrdering   =$this->redis->get($key);
	   	if($revokOrdering)
	   	{
	   		$res['code']=621;
	   		$res['msg']= L('_GDDYBGMQNGMQTDD_');
	   		return $res;
	   	}
	   	//正在购买
	   	$orderBuying=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$id;
	   	$this->redis->setex($orderBuying, 5, 1);
	   	//成功返回
	   	return $res;
   }


   
   /**
    * @method  检验基础提交数据 包括用户资金密码 24h内是否改动 用户实名认证
    * @param   array $this->jsonToArr
    * @param   用户uid  $uid
    */
   protected function checkBasicParamIncludeTwdUserRealAndBank($data,$uid)
   {   
	   	$res=[
	   		'code'=>200,
	   		'msg'=>'',
	   	];
       //检测是否是刷单数据
       if(stripos($data['id'], 'S')!==false)
       {
           $res['msg']=L('_GDDYJWCHCX_');
           $res['code']=614;
           return $res;
       }
	   	if(!is_array($data) || !isset($data['id'])  ||
	   	   !isset($data['num']) || !isset($data['money']) ||
	   	   !isset($data['user_bank_id']) || !isset($data['trade_pass']) ||
	   	   !in_array($data['type'], array(1,2)) || !isset($data['currency_type'])||
            intval($uid)<=0
	     )
	   	{
	   		$res['msg']=L('_CSCW_');
	   		$res['code']=602;
	   		return $res;
	   	}

	   	if(empty($data['num']))
	   	{
	   	    $res['msg'] = L('_SLBNWK_');
	   		$res['code']=603;
	   		return $res;
	   	}
	   
	   	if(empty($data['money']))
	   	{
	   		$res['msg'] = L('_JEBNWK_');
	   		$res['code']['code']=604;
	   		return $res;
	   	}
	   	//数量为数字
	   	if(!is_numeric($data['num']))
	   	{
	   		$res['msg'] = L('_SLBNWFS_');
	   		$res['code']=605;
	   		return $res;
	   	}
	   
	   	//金额为数字
	   	if(!is_numeric($data['money']))
	   	{
	   		$res['msg'] = L('_JEBNWFS_');
	   		$res['code']=606;
	   		return $res;
	   	}
	   
	   	//正数数量
	   	if($data['num']<=0)
	   	{
	   		$res['msg'] = L('_SLBNWFS_');
	   		$res['code']=607;
	   		return $res;
	   	}
	   
	   	//正数金额
	   	if($data['money']<=0)
	   	{
	   		$res['msg'] = L('_JEBNWFS_');
	   		$res['code']=608;
	   		return $res;
	   	}
	   
	   	// 交易密码不能为空
	   	if(empty($data['trade_pass']))
	   	{
	   		$res['msg'] = L('_JYMMBNWK_');
	   		$res['code']=609;
	   		return $res;
	   	}
	   	 
	   	if(empty($data['currency_type']) || !is_numeric($data['currency_type']))
	   	{
	   		$res['msg']=L('_BZLXCW_');
	   		$res['code']=610;
	   		return $res;
	   	}

	    //用户通用相关验证
	    $retTradepass=$this->checkAllCanUseParamObj->checkUserPower($uid);
	    if($retTradepass['code']!=200)
	    {
	   	    $res['code']=$retTradepass['code'];
	   	    $res['msg']=$retTradepass['msg'];
	   	    return $res;
	    }

	   	//失信次数内 24小时禁止交易
	   	$duringTime=$this->cToCTransLogicsObj->checkUserIsDuringTime($uid);
	   	if($duringTime['code']!=200)
	   	{
	   		$res['msg']=$duringTime['msg'];
	   		$res['code']=613;
	   		return $res;
	   	}
	   	 
	   	//验证交易密码正确
	   	$publicFunctionObj = new PublicFunctionController();
	   	$tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($uid, $data['trade_pass']);
	   	if($tradePwdRes['code'] != 200)
	   	{
	   		$res['msg']  = $tradePwdRes['msg'];
	   		$res['code'] = 615;
	   		return $res;
	   	}
	   	//通过验证
	   	return $res;
   }
   
   /**
    * @method 检验 最小金额 卖出检验银行卡  最小成交金额
    * @param unknown $data
    */
   protected function checkBankAndMoneyAndMinmoney($data,$order,$uid)
   {  
   	     $res=[
   	     	 'code'=>200,
   	     	 'msg'=>'',	
   	     ];
   	    //币种类型错误
   	    if($data['currency_type']!=$order['currency_type'])
   	    {   
   	    	$res['msg']=L('_CSCW_');
   	    	$res['code']=633;
   	    	return $res;
   	    }
   	    
   	    //买卖业务符合条件   例买匹配卖 1=2
   	    if($data['type']==$order['type'])
   	    {
   	    	$res['code']=634;
   	    	$res['msg']=L('_CSCW_');
   	    	return $res;
   	    }
   	    
	   	//是否绑定银行卡
	   	if(!checkUserBindBank($uid,$order['om']) && $data['type'] == 2 ) 
	   	{
	   	     $res['msg']=L('_GJYQWBDYHK_');
	   	     $res['code']=651;
	   	     return $res;
	   	}
	   	//存在手续费
	   	if($data['type'] == 1 && $order['data']['leave_fee']<= 0 && $data['fee']>0)
	   	{
	   		$res['msg']=L('_SXFYC_');
	   		$res['code']=652;
	   		return $res;
	   	}
	   	//资金是否足够卖出
	   	if($data['type'] == 2)
	   	{
	   		$isEnoughMoney = $this->cToCTransLogicsObj->checkUserMoneyIsEnough($order['currency_type'], $uid, $data['num']);
	   		if($isEnoughMoney['code'] != 200) 
	   		{
	   			$res['msg']=L('_NDZJBZ_');
	   			$res['code']=653;
	   			return $res;
	   		}
	   	}
	   	//最小金额配置
	   	$isMinMoney = $this->cToCTransLogicsObj->checkIsMinMoney($data['currency_type'], $data['money']);
	   	if($isMinMoney['code']!= 200)
	   	{
	   		$msg = L('_ZJEXYGDJE_')."$".$isMinMoney['data']['min_trade_money'];
	   		$res['msg']=$msg;
	   		$res['code']=654;
	   		return $res;
	   	}
	   	 
	   	//买入则获取主订单的uid，卖出则获取当前用户id  银行卡是否存在
	   	$userId = ($data['type'] == 1) ? $order['uid']:$uid ;
	   	$ret=$this->cToCTransLogicsObj->checkUserBankIsHave($userId, $data['user_bank_id']);
	   	
	   	if($ret['code'] != 200) 
	   	{
	   		$res['msg']=L('_FKYHBCZ_');
	   		$res['code']=655;
	   		return $res;
	   	}
	   	//验证通过
	   	return $res;
   }

    /**
     * @method 每个进程处理完毕调用  [锁过期不用处理 ,没过期删除锁]
     * {@inheritDoc}
     * @see \Think\Controller::__destruct()
     */
    public function __destruct(){
        $order_id = $this->input_data['id'];
        $lock_key = self::LOCK_PREFIX.$order_id;
        if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
    }
}