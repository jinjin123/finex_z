<?php
/**
 * 公共函数封装类
 * @author lirunqing 2017年10月9日10:29:57
 */
namespace Home\Logics;
use Think\Controller;
use Home\Model\ConfigModel;
use Home\Logics\LoginCheckController;
use Home\Sms\Yunclode;
use Home\Tools\SceneCode;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
use Common\Api\Point;

class PublicFunctionController extends Controller {

    /**
     * 自增登陆密码错误次数和口令错误次数
     * @author lirunqing 2018年6月22日11:12:56
     * @param  int  $userId 用户id
     * @param  integer $type   1表示登陆密码错误次数，2表示口令错误次数
     * @return bool
     */
    public function setIncNum($userId, $type=1){

        // $redisObj = new RedisCluster();
        $redis    = RedisCluster::getInstance();

        if ($type == 1) {
            $passMissRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
            $endTime     = strtotime(date('Ymd', strtotime('+1 days')))-1;
            $nowTime     = time();
            $expreTime   = $endTime - $nowTime;
            if (empty($passMissRes)) {
                $redis->setex(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId, $expreTime, 1);
            }else{
                $redis->incr(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
            }
        }

        if ($type == 2) {
            $tokenMissRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);
            $endTime     = strtotime(date('Ymd', strtotime('+1 days')))-1;
            $nowTime     = time();
            $expreTime   = $endTime - $nowTime;
            if (empty($tokenMissRes)) {
                $redis->setex(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId, $expreTime, 1);
            }else{
                $redis->incr(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);
            }
        }
        
        return true;
    }

    /**
     * 更新用户登陆密码或口令错误次数
     * @author lirunqing 2018-6-22 11:54:33
     * @param  int  $userId 用户id
     * @param  integer $type   1表示登陆密码错误次数，2表示口令错误次数
     * @return bool
     */
    public function updateLoginMiss($userId, $type=1){

        // $redisObj = new RedisCluster();
        $redis    = RedisCluster::getInstance();
        $passMissRes  = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        $tokenMissRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);

        if ($type == 1 && !empty($passMissRes)) {
            $redis->del(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        }

        if ($type == 2 && !empty($tokenMissRes)) {
            $redis->del(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);
        }

        return true;
    }

    /**
     * 获取存在风险的用户
     * @author lirunqing 2018-08-16T16:07:27+0800
     * @return array
     */
    public function getRiskUserList(){
        $riskUserList = M('User')->where(['status' => -2])->field('uid')->select();

        $riskUserArr = [];
        foreach ($riskUserList as $value) {
            $riskUserArr[] = $value['uid'];
        }

        return $riskUserArr;
    }

    /**
     * 检测用户的登陆密码错误次数或者动态口令错误次数
     * @author lirunqing 2018年6月22日11:12:50
     * @param  int  $userId 用户id
     * @param  integer $type   1表示登陆密码错误次数，2表示口令错误次数
     * @return bool|int
     */
    public function checkUserPassMissNumOrTokenMissNum($userId, $type=1){

        // $redisObj = new RedisCluster();
        $redis    = RedisCluster::getInstance();

        if ($type == 1) {
            $missRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        }

        if ($type == 2) {
            $missRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);
        }

        return $missRes;
    }

    /**
     * 检测用户资金密码错误次数及重置资金错误次数
     * @author lirunqing 2017-12-12T15:38:37+0800
     * @param  int $userId   用户id
     * @param  string $trdaePwd 资金密码
     * @return int 200表示成功
     */
    public function checkUserTradePwdMissNum($userId, $trdaePwd){
        $where = array(
            'uid' => $userId
        );
        $userInfo = M('User')->where($where)->find();

        $returnMsg = [
            'code' => 200,
            'msg'  => '',
            'data' => []
        ];

        // 资金密码错了5次
        if (!empty($userInfo['trade_pwd_missnum']) && $userInfo['trade_pwd_missnum'] >= 5) {
            $returnMsg['code'] = 201;
            $returnMsg['msg']  = L('_ZHFXLXPT_');
            return $returnMsg;
        }

        // 资金密码错误设置次数
        if (!passwordVerification($trdaePwd, $userInfo['trade_pwd'])) {
            M('User')->where($where)->setInc('trade_pwd_missnum');
            // 错误达到5次，设置用户状态为封号
            $num = $userInfo['trade_pwd_missnum'] + 1;
            if($num >= 5) {
                // 资金密码错误达到5次以后锁定账号，并推送信息到用户APP上
                $contentStr = SceneCode::getPersonSafeInfoTemplate($userInfo['username'], $userInfo['om'], 5);
                $contentArr = explode('&&&', $contentStr);
                $title      = $contentArr[0];
                $content    = $contentArr[1];
                push_msg_to_app_person($title, $content, $userId);
                M('User')->where($where)->setField('status', '-2');

                // 用户状态列为存在交易风险时，则把p2p和c2c交易模式下订单状态设置为待处理
                $this->setAllOrderRevokeOrPendingByUserId($userId);

                $returnMsg['code'] = 202;
                $returnMsg['msg']  = L('_ZHFXLXPT_');
                return $returnMsg;
            }

            $returnMsg['code'] = 203;
            $surpNum = 5 - $num;
            $returnMsg['msg']  = L('_ZJMMCWNHY_').$surpNum.L('_CJH_');
            return $returnMsg;
        }

        // 错误次数未达到5次，验证成功后重置资金密码错误次数
        if ($userInfo['trade_pwd_missnum'] > 0) {
            M('User')->where($where)->setField('trade_pwd_missnum', '0');
        }
        return $returnMsg;
    }

    /**
     * 判断用户有失信次数是否在禁止交易时间内
     * @author lirunqing 2017-11-07T14:31:37+0800
     * @param  int $overtimeNum  失信次数
     * @param  int $overtimeTime 添加失信次数时的时间
     * @return 
     */
    public function checkOvertime($overtimeNum, $overtimeTime){

        $res = array(
            'code' => 200,
            'msg'  => L('_CHENGGONG_'),
            'data' => array(),
        );

        if ($overtimeNum <= 0 || empty($overtimeTime)) {
            return $res;
        }

        // 如果失信大于3次，则封号
        if ($overtimeNum > 3) {
            $res['code'] = 201;
            $res['msg']  = L('_ZHFXLXPT_');
            return $res;
        }

        $overTimeArr = array(
            1 => 1,  // 失信一次，禁止交易1天
            2 => 7,  // 失信2次，禁止交易7天
            3 => 30  // 失信3次，禁止交易30天
        );

        $days    = $overTimeArr[$overtimeNum];
        $nowTime = time();
        $lasTime = strtotime('+ '.$days.' days', $overtimeTime);
        // 判断是否在禁止交易时间内
        if ($lasTime < $nowTime) {
            return $res;
        }

        $res['code'] = 201;
        $res['msg']  = L('_SHIXIN_').$overtimeNum.L('_CJZJY_').$days.L('_TIAN_');
        return $res;
    }

    /**
     * 计算用户积分及用户等级
     * @author lirunqing 2017-11-02T14:11:03+0800
     * @param  int     $userId      用户id
     * @param  float   $integral    加/减积分值
     * @param  array   $extArr      拓展数组
     *         string  $extArr['operationType']  必传   运算符,inc表示加;dec表示减
     *         string  $extArr['scoreInfo']      必传   积分日志场景；例：首次登录增加积分
     *         string  $extArr['status']         必传   积分日志场类型；
     *                                           1绑定电话号码,2绑定邮箱,3绑定充值地址,4绑定转出地址,5绑定APP令牌,6交易密码,
     *                                           7银行卡账户,8每天首次登陆,9订单交易,10充值钱,11充值币,12vip充值资产额  13 实名认证
     *         string  $extArr['isOverTime']     非必传 失信计算标记；0表示不计算;1表示计算
     *         string  $extArr['remarkInfo']     非必传 线下交易的订单号   
     * @return bool
     */
    public function calUserIntegralAndLeavl($userId, $integral, $extArr=array()){

        $operationType = $extArr['operationType'];
        $isOverTime    = !empty($extArr['isOverTime']) ? $extArr['isOverTime'] : 0;
        $scoreInfo     = $extArr['scoreInfo'];
        $status        = $extArr['status'];
        $remarkInfo    = !empty($extArr['remarkInfo']) ? $extArr['remarkInfo'] : 0;

        if (empty($userId) || empty($integral) || empty($scoreInfo) || empty($status)
            || !in_array($operationType , array('inc', 'dec')) ) {
            return false;
        }

        $whereUser['uid'] = $userId;
        $userLevelInfo    = M('User')->field('level,credit_level,overtime_num')->where($whereUser)->find(); 

        //开启事务
        $flag = false;

        $integral1 = 0;
         // 暂时只开放到vip3，用户积分只能是3000
        if ( ($userLevelInfo['credit_level'] + $integral)  > 3000) {
            $integral1 = 3000 - $userLevelInfo['credit_level'];
        }

        // vip5，用户积分只能是16000
        if (($userLevelInfo['credit_level'] + $integral)  > 16000) {
            $integral1 = 16000 - $userLevelInfo['credit_level'];
        }

        //对应加积分,暂时升级只升到vip3
        if($operationType == 'inc' && $userLevelInfo['level'] < 3){
            $type = 2;
            $flag = true;
            $point = ($integral1 > 0) ? $integral1 : $integral;
            $r[] = M('User')->where($whereUser)->setInc('credit_level', $point);
        }

        //对应减积分
        if($operationType == 'dec'){
            $type = 1;
            $flag = true;
            $r[] =  M('User')->where($whereUser)->setDec('credit_level', $integral);
        }

        //交易超时失信次数增加一次并设置失信时间
        if ($operationType == 'dec' && $isOverTime == 1) {
            $r[] = M('User')->where($whereUser)->setInc('overtime_num', 1);
            $r[] = M('User')->where($whereUser)->setField('overtime_time',time());

            // 失信超过3次则封号
            $overtimeNum = $userLevelInfo['overtime_num']+1;
            if ($overtimeNum > 3) {
                $userWhere = array(
                    'uid' => $userId
                );
                $r[] = M('User')->where($userWhere)->setField('status','-2');
                // 用户状态列为存在交易风险时，则把p2p和c2c交易模式下订单状态设置为待处理
                $this->setAllOrderRevokeOrPendingByUserId($userId);
            }
        }

        $userInfo  = M('User')->where($whereUser)->find();
        $userLevel = $this->getUserLevel($userInfo['credit_level']);// 获取用户积分变化后的用户等级
        // 积分变更后，添加积分日志
        if (!empty($flag)) {
            $logData = array(
                'uid'         => $userId,
                'level'       => $userLevel,
                'integral'    => $integral,
                'total_score' => $userInfo['credit_level'],
                'info'        => $scoreInfo,
                'remark_info' => $remarkInfo,
                'type'        => $type,
                'status'      => $status,
            );
            $r[] = $this->addScoreLog($userId, $logData);
        }

        // 判断用户等级是否发生变更
        if ($userInfo['level'] != $userLevel) {
            $r[] = M('User')->where($whereUser)->setField('level', $userLevel);
        }

        //返回结果
        if(in_array(false, $r)){
            return false;
        }
        return true;      
    }

    /**
     * 用户状态被列为存在交易风险，则把p2p和c2c交易模式下订单状态设置为待处理
     * @author 2018-08-13T12:33:31+0800
     * @param  [type] $userId [description]
     */
    public function setAllOrderRevokeOrPendingByUserId($userId){
        // 设置p2p交易中订单为待处理
        $p2pPendWhere = [
            'status'         => ['in', [1,2]],
            'buy_id|sell_id' => $userId
        ];
        $p2pPendData = [
            'status'      => 8,
            'remark_info' => '用户id:'.$userId.'存在交易风险',
        ];
        M('TradeTheLine')->where($p2pPendWhere)->save($p2pPendData);

        // 设置c2c交易中订单为待处理
        $c2cPendWhere = [
            'status'         => ['in', [1,2]],
            'sell_id|buy_id' => $userId
        ];
        $c2cPendData = [
            'status'      => 5,
            'remark_info' =>  '用户id:'.$userId.'存在交易风险',
        ];
        M('CcTrade')->where($c2cPendWhere)->save($c2cPendData);
    }

    /**
     * 根据用户id获取用户状态
     * @author lirunqing 2018-08-13T14:42:42+0800
     * @param  int $userId 用户id
     * @return bool
     */
    public function getUserStatusByUserId($userId){

        $userInfo = M('User')->field('status')->where('uid='.$userId)->find();

        if (empty($userInfo['status']) || $userInfo['status'] != 1) {
            return false;
        }

        return true;
    }

    /**
     * 根据积分获取用户等级
     * @author 2017-11-02T12:23:05+0800
     * @param  [type] $integral [description]
     * @return [type]           [description]
     */
    public function getUserLevel($integral){
        switch ($integral) {
            case $integral >= 100 && $integral < 1000:
                $level = 1;
                break;
            case $integral >= 1000 && $integral < 3000:
                $level = 2;
                break;
            case $integral >= 3000 && $integral < 6000:
                $level = 3;
                break;
            case $integral >= 6000 && $integral < 16000:
                $level = 4;
                break;
            case $integral >= 16000:
                $level = 5;
                break;
            default:
                $level = 0;
                break;
        }

        return $level;
    }

    /**
     * 添加用户积分加减日志
     * @author lirunqing 2017-11-03T11:34:15+0800
     * @param  int   $userId 用户id
     * @param  array $data   日志信息数组
     * @return bool
     */
    public function addScoreLog($userId, $data){

        $data['uid']         = (int)$userId;
        $data['level']       = (int)$data['level'];
        $data['integral']    = $data['integral'];
        $data['total_score'] = $data['total_score'];
        $data['info']        = $data['info'];
        $data['type']        = $data['type'];
        $data['remark_info'] = $data['remark_info'];
        $data['status']      = $data['status'];
        $data['add_time']    = time();
        $table               = 'UserScoreLog';
        $tableName           = getTbl($table, $userId);

        return M($tableName)->add($data);
    }

    /**
     * 发送邮件
     * @author lirunqing 2017-10-19T12:09:42+0800
     * @param  string $email 用户邮箱
     * @param  string $title 标题
     * @param  string $body  邮件内容
     * @return bool
     */
    public function sendEmail($email, $title, $body){

        $res = M('InterfaceConfig')->select();
        foreach($res as $k => $v) {
            $arr[$v['key']]=$v['value'];
        }

        $data['emailHost']     = $arr['EMAIL_HOST'];
        $data['emailUserName'] = $arr['ENAIL_USERNAME'];
        $data['emailPassWord'] = $arr['EMAIL_PASSWORD'];
        $data['formName']      = $arr['ENAIL_USERNAME'];

        $sendRes = sendEmail($data, $email, $title, $body);

        if (!empty($sendRes)) {
            return $sendRes;
        }

        return true;
    }

    /**
     * 发送手机验证码
     * @author lirunqing 2017-10-11T12:09:32+0800
     * @param  array   $phoneInfo 用户手机相关信息
     *         string  $phoneInfo['om']  必传 手机区号
     *         string  $phoneInfo['phoneNum']  必传 手机号码
     *         string  $phoneInfo['msgType']  必传 手机验证码日志类型
     *         string  $phoneInfo['phoneCodeType']  必传 验证码验证类型  此字段用于redis验证类型         
     * @param  integer $uid       用户id
     * @param  integer $type      验证码类型,此参数用于日志
     * @return [type]             [description]
     */
    public function sendPhoneCode($phoneInfo=array(), $uid=0, $type=1){

        $data = array(
            'status' => 201,
            'msg'    => L('_CSBWZ_'),
            'data'   => array()
        );

        if (empty($phoneInfo)) {
            $this->ajaxReturn($data);
        }

        $om            = !empty($phoneInfo['om']) ? $phoneInfo['om'] : '+86'; // 手机区号
        $phoneNum      = $phoneInfo['phoneNum']; // 手机号码
        $phoneCodeType = $phoneInfo['phoneCodeType']; // 手机验证码类型标记
        $msgType       = $phoneInfo['msgType'];// 手机验证码日志类型

        if (empty($phoneNum)) {
            $data['msg'] = L('_QTXSJH_');
            $this->ajaxReturn($data);
        }

        $userName = 'new';
        // 如果有uid，则检测是否有该用户
        if (!empty($uid)) {
            $userInfo = M('User')->field('username')->where(array('uid'=>$uid))->find();
            if (empty($userInfo)) {
                $data['msg'] = L('_WUCIYHXX_');
                $this->ajaxReturn($data);
            }
            $userName = $userInfo['username'];
        }

        $yunclodeObj = new Yunclode();
        $sendRes     = $yunclodeObj->ApiSendPhoneCode($uid, $om, $phoneNum, $phoneCodeType, $msgType, $userName);// 发送手机验证码
        $smsData     = array(
            'uid'      => $uid,
            'username' => $userName,
            'phone'    => $phoneNum,
            'type'     => $type
        );

        if ($sendRes == 0) {// 发送成功
            $data['status'] = 200;
            $data['msg'] = L('_DXFSCGQCK_');
            // $this->addSmsLog($smsData);// 手机验证码发送日志
        }else if($sendRes == 413){
            $data['msg'] =  L('_CZSBMSSMZNHQYT_');
        } else if($sendRes == 33){
            $data['msg'] =  L('_CZSBMSSMZNHQYT_');
        }else if($sendRes == 22){
            $data['msg'] = L('_CZSBMXSZDZNHQST_');
        }else{
            $data['msg'] = L('_CZSBQSHZS_');
        }

        return $data;
    }

    /**
     * 根据用户填写的银行id获取获取银行信息
     * @author 2017-10-13T15:44:15+0800
     * @param  int $id 用户填写的银行卡信息的id
     * @return string
     */
    public function getBankTypeByBankId($id){
        $id       = (int)$id;
        $bankInfo = M('UserBank')->where(array('id'=>$id))->find();
        return $bankInfo['bank_type'];
    }

    /**
     * 获取用户某币种余额
     * @author lirunqing 2017-10-13T14:49:39+0800
     * @param  int $userId     用户id
     * @param  int $currencyId 币种id
     * @return float
     */
    public function getUserBalance($userId, $currencyId) {

        $currencyWhere['uid']         = $userId;
        $currencyWhere['currency_id'] = $currencyId;
        $curRes                       = M('UserCurrency')->where($currencyWhere)->find();

        return !empty($curRes['num']) ? $curRes['num'] : 0.0000;
    }

    /**
     * 手机验证码发送日志
     * @author lirunqing 2017-10-11T12:25:04+0800
     * @param  array $data
     * @return bool
     */
    public function addSmsLog($data=array()){

        if (empty($data['uid'])) {
            return false;
        }

        $dataLog['uid']      = $data['uid'];
        $dataLog['username'] = $data['username'];
        $dataLog['phonenum'] = $data['phone'];
        $dataLog['type']     = $data['type'];
        $dataLog['add_time'] = time();
        
        return M('SmsLog')->add($dataLog);
    }

    /**
     * 检测网站配置
     * @author lirunqing 2017-10-09T12:19:58+0800
     * @return null
     */
    public function checkWebConfig(){

        $configObj     = new ConfigModel();
        $LoginCheckObj = new LoginCheckController();
        $configList    = $configObj->getConfigList(); // 获取网站配置列表

        // 网站开关
        if($configList['WEB_OPEN'] == 0){
            $LoginCheckObj->loginOut();
            $this->display('Public/maintain2');
        }

        // 网站自动关闭时间
        $webCloseStart = (time() > strtotime($configList['WEB_CLOSE_START'])) ? 1 : 0;
        $webCloseOver  = (time() < strtotime($configList['WEB_CLOSE_OVER'])) ? 1 : 0;

        if($configList['WEB_CLOSE_BUTTON'] == 1 && !empty($webCloseStart) && !empty($webCloseOver)){
            $time    = strtotime($configList['WEB_CLOSE_OVER']) - time();
            $endTime = date("m/d/Y H:i:s", strtotime($configList['WEB_CLOSE_OVER']));
            $LoginCheckObj->loginOut();
            $this->assign('remainingTime',$endTime);
            $this->display('Public/maintain');
        }
    }

	/**
	 *  添加用户操作日志
	 *  @author lirunqing 2017-10-9 10:31:00
     *  @param int $userId   用户id
     *  @param int $type  
     *  @param int $status 
     *  @return int
     */
    public function addUserLog($userId, $type, $status=0){

        $data['uid']      = (int)$userId;
        $data['type']     = (int)$type;
        $data['ip']       = get_client_ip();
        $protocol         = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
        $data['url']      = $protocol.'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];////在线
        $data['add_time'] = time();
        $data['status']   = $status;
        $table            = 'UserLog';
        $tableName        = getTbl($table, $userId);

        return M($tableName)->add($data);
    }


    /**
     * SWOOLE定时器添加用户登录日志
     *  @author 刘富国 2018-7-25
     * @param $logData
     * @return bool|mixed
     */

    public function addUserLogByTick($logData){
        if(empty($logData['uid']) or empty($logData['type'])
            or empty($logData['status']) or empty($logData['url'])
                or empty($logData['ip']) or empty($logData['add_time'])){
            return false;
        }
        $userId = $logData['uid']*1;
        $data['uid']      = (int)$userId;
        $data['type']     = $logData['type']*1;
        $data['ip']       = $logData['ip'];
        $data['url']      = $logData['url'];
        $data['add_time'] = $logData['add_time'];
        $data['status']   = $logData['status'];;
        $table            = 'UserLog';
        $tableName        = getTbl($table, $userId);
        return M($tableName)->add($data);
    }

    /**
     * SWOOLE定时器整理用户登录日志
     *  @author 刘富国 2018-7-25
     * @param $userId
     * @param $type
     * @param int $status
     * @param $addTime
     * @return mixed
     */
    public function getUserLogByTick($userId, $type, $status=0,$addTime){
        $data['uid']      = (int)$userId;
        $data['type']     = (int)$type;
        $data['ip']       = get_client_ip();
        $protocol         = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
        $data['url']      = $protocol.'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];////在线
        if(empty($add_time)) $addTime = time();
        $data['add_time'] = $addTime;
        $data['status']   = $status;
        return $data;
    }

    /**
     * 每日首次登添加积分
     * @author 2017-11-09T19:34:58+0800
     * @return [type] [description]
     */
    public  function firstLoginAddPoint($userId){
        $table             = 'UserLog';
        $tableName         = getTbl($table, $userId);
        $today             = strtotime(date('Ymd'));
        $where['add_time'] = array('egt', $today);
        $where['status']   = 1;
        $where['uid']      = $userId;
        $count             = M($tableName)->where($where)->count();
        if ($count > 1) {
            return true;
        }
        $extArr['operationType'] = 'inc';
        $extArr['scoreInfo']     = '每天首次登陆';
        $extArr['status']        = 8;
        $extArr['isOverTime']    = 0;
        $this->calUserIntegralAndLeavl($userId, Point::ADD_ONE_LOGIN, $extArr);
    }
}