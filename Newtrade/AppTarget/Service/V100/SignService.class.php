<?php
namespace AppTarget\Service\V100;

use AppTarget\Service\ServiceBase;
use Common\Model\UserTokenModel;
use Home\Sms\Yunclode;
use Think\Exception;
use Common\Logic\UserLog;
use Common\Model\PushUserModel;
use Home\Logics\LoginCheckController;
use Home\Model\UserMissLogModel;
use Home\Logics\PublicFunctionController;
use Common\Api\RedisCluster;
use Common\Model\UserWatchwordModel;
use Common\Api\Point;
use Common\Api\MobileReg;
use SwooleCommand\Controller\WebSocketPushClientController;
use Home\Tools\SceneCode;
use Home\Logics\CheckAllCanUseParam;

/**
 * 登入
 * 為APP提供登入、註冊的接口
 * @author 劉富國
 * 2017-10-16
 *
 */
class SignService extends ServiceBase{

    protected $APP_LOGIN_PHONE_CODE = "APP_TARGET_LOGIN_PHONE_CODE";
    static $IS_BIND = 1;
    static $IS_NOT_BIND = 0;
    const  SMS_LOGIN_TYPE = 5;
    const  MISS_NUM = 5;
    protected $user_token_model = null;
    protected $push_user_model = null;
    protected $login_check_obj = null;
    protected $user_miss_log_model = null;
    protected $user_watch_model = null;
    protected $user_log_obj = null;
    protected $public_obj = null;
    protected $webSocketPushClientObj = null;
    private $checkAllCanUseParamObj = null;
    protected $app_platform =  '';
    private $redis=NULL;
    private $omArr = array();
    // 获取默认地区
    private $area_om_arr  = array(
        '86' => '3',// 大陆
        '886' => '2',// 台湾
        '852' => '1',// 香港
    );
    public function __construct()  {
        parent::__construct();
        $this->user_token_model= new UserTokenModel();
        $this->push_user_model = new PushUserModel();
        $this->user_miss_log_model = new UserMissLogModel();
        $this->login_check_obj = new LoginCheckController();
        $this->user_log_obj = new UserLog();
        $this->app_platform = C('APP_PLATFORM');
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->user_watch_model = new UserWatchwordModel();
        $this->public_obj = new PublicFunctionController();
        $this->webSocketPushClientObj = new WebSocketPushClientController();
        $this->checkAllCanUseParamObj = new CheckAllCanUseParam();
        $this->omArr=[
            '86'=>L('_ZHONGGUO_'),
            '886'=>L('_ZGTW_'),
            '852'=>L('_ZGXG_'),
        ];

    }


    //PC端二维码登录
    public function QRLogin(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $php_session_id = $data['php_session_id'];
        $login_time= $data['login_time'];
        if(empty($php_session_id) or empty($login_time)) return 10000;
        if((time()-$login_time) > 60)  return 10043;
        //校验动态密令绑定
        $isBind = $this->user_watch_model->checkUserBind($uid);
        if($isBind !=1) return 10044;
        session_id($php_session_id);
        $this->loginOut();
        $this->user_log_obj->loginOutApp($uid,$this->app_platform);//将其他端退出
        $set_time = time();
        $this->user_log_obj->setUserLoginSameClient($uid,$set_time,$set_time); //设置登录信息
        $this->login_check_obj->setLogin($uid,$set_time);//登录
        $pushData =  [
            'method' => 'push',
            'service_name' => 'QRLogin',
            'data'   => [
                'time'      => time(),
                'message'   => 'QR登陆推送信息到服务端',
                'push_data' => ['QRLoginSuccess' => 1,
                    'php_session_id' => $php_session_id,
                    'login_time' => $login_time ]
            ],
        ];

        $message    = json_encode($pushData);
        $data       = $this->webSocketPushClientObj->sendTcpMessage($message);
        $serverData = json_decode($data, true);
        if($serverData['status'] <> 1)  return 9999;
        return array('is_success' => 1);
    }


    //修改语言版本
    public function setLang(){
        $uid = $this->getUserId();
        $data = $this->getData();
        $langSet= $data['var_language']; //语言版本
        $phone_imei  = trim($data['phone_imei']); //機識別編碼
        $langList = C('LANG_LIST',null,'zh-tw');
        $expireTime = C('LANG_EXPIRE');
        $expireTime = !empty($expireTime) ? $expireTime : 3600*24*30;
        if(false === stripos($langList,$langSet)) {
            $langSet = C('DEFAULT_LANG');
        }
        if(!empty($uid)){
            $this->redis->setex('APP_VAR_LANGUAGE'.$uid,$expireTime,$langSet);
        }
        if(!empty($phone_imei)){
            $this->redis->setex('APP_VAR_LANGUAGE'.$phone_imei,$expireTime,$langSet);
        }
        return array('is_success' => 1);
    }

    /**
     * 發送驗證碼
     * @author 劉富國
     */
    public function loginSendCode() {
        $data = $this->getData();
        $check_username = trim($data['username']);
        if (empty($check_username))  return 10001;
        $user_info = $this->_check_user_name($check_username);
        if(!$user_info) return $this->errno;
        $uid            = $user_info['uid'];
        $om             = $user_info['om'];
        $phone          = $user_info['phone'];
        $sms_codeType   = $this->APP_LOGIN_PHONE_CODE;
        $username       = $user_info['username'];
        $rulesArr = MobileReg::$validator_rules;
        $checkOm       = str_replace( '+', '', $om);
        $rule     = $rulesArr[$checkOm]['RE'];
        // 手机号码格式错误
        if(!preg_match($rule, $checkOm.$phone)){
            return $this->return_error_num(10019,L('_SJHMGSCW_'));
        }

        $smsModle = new Yunclode();
        $res_send=$smsModle->ApiSendPhoneCode($uid,$om,$phone,$sms_codeType,
            self::SMS_LOGIN_TYPE,$username);
        if($res_send > 0){
            if($res_send==413)
            {
                return 10018; //曾已發送，請兩分鐘后再試
            }elseif($res_send == 17){
                return 10028; //该用户手机号短信量已经超过限额
            }else{
                return 10019; //发送失败
            }
        }
        return array('is_send' => '1');
    }

    /**
     * 登入
     * @author 劉富國
     */
    public function signIn(){
        $data =  $this->getData();
        $ret_user_info = $this->checkUser($data);
        if(!$ret_user_info) {
            return $this->return_error_num($this->errno,$this->errmsg);
        }
        M()->startTrans();
        try {
            $user_info['uid'] = $ret_user_info['uid'];
            $user_info['regId'] = $data['regId'];
            $user_info['phone_imei'] = $data['phone_imei'];
            $token = $this->_signin_success($user_info);
            if(!$token) return 9999;
        } catch (Exception $e) {
            M()->rollback();
            return (9999);
        }
        M()->commit();
        $ret_data['token']  =  $token;
        $ret_data['uid']  =  $ret_user_info['uid'];
        $ret_data['username']  =  $ret_user_info['username'];
        $ret_data['level']  =  $ret_user_info['level'];
        $ret_data['phone'] =  substr_replace($ret_user_info['phone'],'****',3,4);
        $ret_data['checkUserReal'] = 1;
        $ret = $this->checkAllCanUseParamObj->checkUserRealIsExpireForExt($ret_user_info['uid']);//證件是否過期
        if ($ret['code'] == 613)   $ret_data['checkUserReal'] = 0;
        if ($ret['code'] == 614)   $ret_data['checkUserReal'] = -1;
        $om = str_replace('+','',$ret_user_info['om']);
        $ret_data['area_om']  = $om;
        $ret_data['area_id']  = $this->area_om_arr[$om];
        $ret_data['area_name']  =  $this->omArr[$om];
        return $ret_data;

    }

    /**
     * 校驗是否被其他手機或者賬號綁定
     * 返回用戶基本信息
     * @author 刘富国
     * @param  array $data
     * @return bool
     */
    protected function checkUser($data){
        $check_username   = trim($data['username']); //賬號
        $check_password  = trim($data['password']);  //密碼
        $sms_code = $data['sms_code']; //短信验证码
        $login_continue = $data['login_continue']; //不提示其他平台是否登录，直接登录
        if (empty($check_username))   return $this->return_error(10001,L('_YHMBNWK_'));
        if (empty($check_password))   return $this->return_error(10014);
        if (empty($sms_code))   return $this->return_error(10003);
        $user_info = $this->_check_user_name($check_username);
        if(!$user_info) return $this->return_error($this->errno);
        $uid = $user_info['uid'];
        $password =  decrypt_rsa_private_key($check_password,C('APP_PRIVATE_KEY'));
        if(!$password)   return $this->return_error(10014);
        $miss_num = $this->public_obj->checkUserPassMissNumOrTokenMissNum($uid,1);
        // 密码错误达大于等于5次，则不能登陆
        if ($miss_num >= self::MISS_NUM ){
            return $this->return_error(10026);
        }
        //密码错误次数设置
        if (passwordEncryption($password) != $user_info['pwd']){
            $this->public_obj->setIncNum($uid, 1);
            $lastNUm          = self::MISS_NUM - $miss_num - 1;
            // 登陆密码错误等于5次推送信息到用户APP上
            if( $lastNUm == 0){
                $contentStr = SceneCode::getPersonSafeInfoTemplate($user_info['username'], $user_info['om'], 4);
                $contentArr = explode('&&&', $contentStr);
                $title      = $contentArr[0];
                $content    = $contentArr[1];
                push_msg_to_app_person($title, $content, $user_info['uid']);
                return $this->return_error(10026);
            }
            $errmsg      = L('_YHMHMMCUNHY_').$lastNUm.L('_CJHDL_');
            return $this->return_error(10020,$errmsg);
        }

        // 超过45天未登陆,要修改密码

        $table            = 'UserLog';
        $userLogTableName = getTbl($table, $uid);
        $userLogWhere['uid'] = $uid;
        $userLogData         = M($userLogTableName)->where($userLogWhere)->order('id desc')->find();
        $days = 45*24*3600;
        if((time() - $userLogData['add_time']) > $days ){
            return $this->return_error(9999,L('_ZHWDLXGCS_'));
        }
        //其他平台已登录
        $logWhere['uid'] = $uid;
        $LoginSameInfo   = M('UserLoginSameClient')->where($logWhere)->find();
        if(!$login_continue
            and $LoginSameInfo['client_token'] <> $uid.$data['phone_imei']
            and $LoginSameInfo['status'] == 1
        )  {
            return $this->return_error(10042);
        }
        $ret = $this->_check_sms_code($user_info,$sms_code); //校验短信验证码
        if(!$ret) return  $this->return_error($this->errno,$this->errmsg);
        $this->public_obj->updateLoginMiss($uid,1);// 重置错误密码次数
        $isBind = $this->user_watch_model->checkUserBind($uid);
        if($isBind !=1) return $this->return_error(10044);;
        return $user_info;
    }


    /**
     * 登入成功後的操作
     * 註冊和登入後都需要進行生成token等操作，
     * 所以提出來一個獨立的方法。
     * @author 劉富國
     * @param $user_info
     * @return bool
     */
    protected function _signin_success($user_info){
        $uid = $user_info['uid'];
        //保存JPUSH設備ID
        $regId = trim($user_info['regId']);
        $this->user_log_obj->loginOutApp($uid,$this->app_platform);//将其他端退出
        $set_time = time();
        $this->user_log_obj->setUserLoginSameClient($uid,$set_time,$user_info['phone_imei']); //设置登录信息
        $token = $this->_create_token($user_info,$set_time);// 创建token
        //用戶登陸日誌
        $ret = $this->public_obj->addUserLog($uid, 1, 1);
        // 每日首次登添加积分
        $this->public_obj->firstLoginAddPoint($uid);
        if(!empty($regId)) $this->push_user_model->add_push_user($uid,$regId,$this->app_platform);
        return $token;
    }

    /**
     *
     * 1， 創建和存儲token和手機imei碼
     * @param array 數據數組
     * @param int 用戶ID
     * @return string TOKEN
     */
    public function _create_token($user_info,$set_time = 0){
        if ($set_time<=0) $set_time = time();
        $data = array(
            'last_login_ip' => get_client_ip(), // 登入人ID
            'last_login_time' => $set_time, // 登入時間
        );
        $data['sign'] = build_rand_str(6, 'char');    // 創建用戶token
        $sign = sys_md5(data_auth_sign($data));
        $token = array(
            'access' => $this->_access,
            'time'   => $set_time,
            'uid'    => $user_info['uid'],
            'sign'   => $sign,
            'ver'    => strval($this->_ver),
            'os'     => $this->_os,
            'phone_imei'     => $user_info['phone_imei']
        );
        // 存儲token
        $input_data['time'] = $set_time;
        $input_data['sign'] = $sign;
        $input_data['uid'] = $user_info['uid'];
        $input_data['token'] = $token;
        $input_data['app_platform'] = $this->app_platform ;
        $token = $this->user_token_model->saveUserLoginToken($input_data);
        if(!$token) {
            $this->errno = $this->user_token_model->errno;
            return false;
        }
        return $token;
    }


    /**
     * 退出登录
     * @author 刘富国
     */
    public function loginOut() {
        $uid = $this->getUserId();
        if ($uid) {
            $this->user_log_obj->loginOutApp($uid,$this->app_platform);//退出
        }
        return array('is_logout' => '1');
    }

    /**
     * 忘记密码
     * @author 刘富国
     */
    public function forgetPwd(){
        $data =  $this->getData();
        $check_username   = trim($data['username']);
        $sms_code = $data['sms_code'];
        $password = $data['password'];
        $repassword = $data['repassword'];
        // 验证请求参数
        if (empty($check_username) || empty($sms_code) || empty($password) || empty($repassword)) {
            return 10000;
        }

        $user_info = $this->_check_user_name($check_username);
        if(!$user_info) return $this->errno;
        $uid = $user_info['uid'];
        $ret = $this->_check_sms_code($user_info,$sms_code); //校验短信验证码
        if(!$ret) return  $this->return_error_num($this->errno,$this->errmsg);
        if ($password !== $repassword) return(10027); // 验证两次密码输入一致
        //校验格式
        if( strlen( $password) < 6 || strlen( $repassword) < 6 ){
            return $this->return_error_num(10041,L('_MMCDLDSB_'));
        }
        if( !regex( $password, 'password2' ) || !regex( $repassword, 'password2' ) ){
            return $this->return_error_num(10041,L('_ZSXCD_'));
        }
        // 修改密码，增加修改日志
        $newPassword = passwordEncryption( $password);
        $ret =  M('user')
            ->where( array( 'uid'=>$uid ) )
            ->save( array('pwd'=> $newPassword ) );
        $this->user_log_obj->loginOutApp($uid);// 全部退出
        $this->public_obj->addUserLog($uid,4,0 );
        return($ret ? array('is_modify' => 1) : 9999);
    }

    /**
     * 校验加密用户名，返回用户信息
     * @param $username
     * @return bool|mixed
     */
    protected function _check_user_name($check_username){
        if (empty($check_username))   return $this->return_error(10001);
        $username =  decrypt_rsa_private_key($check_username,C('APP_PRIVATE_KEY'));

          if(!$username) return $this->return_error(10020);
      //  if(!$username) $username = $check_username;
        $where['username'] = $username;
        $user_info   = M('User')->where($where)->find();
        if($user_info['username'] !== $username )  return $this->return_error(10020);
        if (empty($user_info))  return $this->return_error(10020);

       if($user_info['status'] == '-2')    return $this->return_error(10007);// 交易風險
        if($user_info['status'] == '-1')return $this->return_error(10008);// 賬戶鎖定
        if($user_info['status'] == '0') return $this->return_error(10009);// 賬戶未激活
        return $user_info;
    }

    /**
     * 短信校验
     * @param $user_info
     * @param $sms_code
     * @return bool
     */
    protected function _check_sms_code($user_info,$sms_code){
        //todo 测试
        return true;
        $uid = $user_info['uid'];
        $miss_num = $this->public_obj->checkUserPassMissNumOrTokenMissNum($uid,2);
        //验证码失败大于等于5次，不能登陆
        if($miss_num>= self::MISS_NUM){
            $errmsg      = L('_DLYZMCWYBSDQLXKF_');
            return $this->return_error(10002,$errmsg);
        }
        if(!checkSmsCode($user_info['uid'], $user_info['phone'], $this->APP_LOGIN_PHONE_CODE, $sms_code)){
            $this->public_obj->setIncNum($uid, 2);
            $lastNUm    = self::MISS_NUM - $miss_num - 1;
            if($lastNUm == 0){
                // 动态口令错误达到5次以后不能登陆，并推送信息到用户APP上
                $contentStr = SceneCode::getPersonSafeInfoTemplate($user_info['username'], $user_info['om'], 7);
                $contentArr = explode('&&&', $contentStr);
                $title      = $contentArr[0];
                $content    = $contentArr[1];
                push_msg_to_app_person($title, $content, $uid);
                $this->user_log_obj->loginOutApp($uid,$this->app_platform);//退出
                $errmsg      = L('_DLYZMCWYBSDQLXKF_');
                return $this->return_error(10002,$errmsg);
            }
            $errmsg      = L('_DLMMCWNHY_').$lastNUm.L('_CJHDL_');
            return $this->return_error(10002,$errmsg);
        }
        return true;
    }


}
