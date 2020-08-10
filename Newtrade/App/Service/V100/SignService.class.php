<?php
namespace App\Service\V100;

use App\Service\ServiceBase;
use Common\Model\UserWatchwordModel;
use Common\Model\UserTokenModel;
use Home\Sms\Yunclode;
use Think\Exception;
use Common\Logic\UserLog;
use Common\Model\PushUserModel;
use Home\Model\UserMissLogModel;
use Home\Logics\PublicFunctionController;

/**
 * 登入，綁定，註冊類
 * 為APP提供登入、註冊的接口
 * @author 劉富國
 * 2017-10-16
 *
 */
class SignService extends ServiceBase{

    protected $APP_LOGIN_PHONE_CODE = "APP_WATCHWORD_LOGIN_PHONE_CODE";
    const  SMS_BIND_TYPE = 4;
    static $IS_BIND = 1;
    static $IS_NOT_BIND = 0;
    const  MISS_NUM = 5;
    protected $user_token_model = null;
    protected $push_user_model = null;
    protected $user_watchword_model = null;
    protected $user_log_obj = null;
    protected $app_platform =  '';
    protected $public_obj = null;
    protected $user_miss_log_model = null;
    public function __construct()  {
        parent::__construct();
        $this->user_watchword_model= new UserWatchwordModel();
        $this->user_token_model= new UserTokenModel();
        $this->push_user_model = new PushUserModel();
        $this->user_log_obj = new UserLog();
        $this->user_miss_log_model = new UserMissLogModel();
        $this->public_obj = new PublicFunctionController();
        $this->app_platform = C('APP_PLATFORM');
    }

    /**
     * 發送驗證碼
     * @author 劉富國
     */
    public function sendCode() {
        $data = $this->getData();
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $username = $data['username'];
        $username = trim($username);
        if (empty($username))  return 10001;
        $user = M('User')->where(array('username'=>$username))->find();
        if (empty($user)) return 10004;
        $om             = $user['om'];
        $phone          = $user['phone'];
        $phoneCodeType  = $this->APP_LOGIN_PHONE_CODE;
        $uid            = $user['uid'];
        $smsModle=new Yunclode();
        $res_send=$smsModle->ApiSendPhoneCode($uid,$om,$phone,$phoneCodeType,self::SMS_BIND_TYPE,$username);
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
        if(!$ret_user_info ) return $this->return_error_num($this->errno,$this->errmsg) ;
        M()->startTrans();
        try {
            ///登入，並獲取到用戶信息 
            $user_info['uid'] = $ret_user_info['uid'];
            $user_info['phone_imei'] = $data['phone_imei'];
            $user_info['regId'] = $data['regId'];
            $user_data =  $this->_signin_success($user_info);
            if(!$user_data) return $this->errno;
            $user_data['username'] = $ret_user_info['username'];
            $user_data['phone'] = $ret_user_info['phone'];
            $user_data['is_bind'] = $ret_user_info['is_bind']*1;
            M()->commit();
        } catch (Exception $e) {
            M()->rollback();
            return (9999);
        }
        return ($user_data);
    }

    /**
     * 校驗是否被其他手機或者賬號綁定
     * 返回用戶基本信息
     * @author 刘富国
     * @param  array $data
     * @return bool
     */
    protected function checkUser($data){
        $username   = trim($data['username']); //賬號
        $password  = trim($data['password']);  //密碼
        $phone_imei  = trim($data['phone_imei']); //手机唯一标识码
        if (empty($username))   return $this->return_error(10001);
        if (empty($password))   return $this->return_error(10014);
        if (empty($phone_imei))   return $this->return_error(10010);
        $username =  decrypt_rsa_private_key($username,C('APP_PRIVATE_KEY'));
        $password =  decrypt_rsa_private_key($password,C('APP_PRIVATE_KEY'));
        if(!$password or !$username) return $this->return_error(10020);
        $where['username'] = $username;
        $user_info   = M('User')->where($where)->find();
        if ($user_info['username'] !== $username) return $this->return_error(10020);  //用戶名密碼有誤
        $uid    = $user_info['uid'];
        //密码错误次数设置
        if (passwordEncryption($password) != $user_info['pwd']){
            $this->public_obj->setIncNum($uid, 1);
        }
        $miss_num = $this->public_obj->checkUserPassMissNumOrTokenMissNum($uid,1);
        // 密码错误达到5次，则不能登陆
        if ($miss_num >= self::MISS_NUM) return $this->return_error(9999,L('_CIPDLPFMTZS_'));
        // 密码错误次数返回
        if (passwordEncryption($password) != $user_info['pwd']) {
            $lastNUm          = self::MISS_NUM - $miss_num;
            $errmsg      = L('_YHMHMMCUNHY_').$lastNUm.L('_CJHDL_');
            return $this->return_error(9999,$errmsg);
        }
        if($user_info['status'] == '-2')  return $this->return_error(10007);// 交易風險
        if($user_info['status'] == '-1')return $this->return_error(10008);// 賬戶鎖定
        if($user_info['status'] == '0') return $this->return_error(10009);// 賬戶未激活
        //除了測試賬號，校驗是否被其他手機或者賬號綁定,
        if(!in_array($username,['lfg','Eason005','lbs'])){
            $ret = $this->user_watchword_model->checkOtherBindByUidImei($uid,$phone_imei);
            if(!$ret) return  $this->return_error($this->user_watchword_model->errno);
        }
        //清掉TOKEN，让其他手机的用户退出
        $this->user_token_model->delAppTokenByUserId($uid,$this->app_platform);
        $user_info['is_bind'] = $this->user_watchword_model->checkUserBind($uid);
        $user_info['phone'] =  substr_replace($user_info['phone'],'****',3,4);
        return $user_info;
    }


    /**
     * 登入成功後的操作
     * 註冊和登入後都需要進行生成token等操作，
     * 所以提出來一個獨立的方法。
     * @param int 用戶ID
     * @return array 返回帶token的用戶信息數組
     * @author 劉富國
     */
    public function _signin_success($user_info){
        //保存JPUSH設備ID
        $regId = trim($user_info['regId']);
      //  口令APP，不需要加极光设备ID
      //  if(!empty($regId)) $this->push_user_model->add_push_user($user_info['uid'],$regId,$this->app_platform);
        //保存TOKEN
        $set_time = time();
        $token_info = $this->_create_token($user_info,$set_time);
        if(!$token_info) return false;
        $user_data['token'] = $token_info;
        return $user_data;
    }

    /**
     *
     * 1， 創建和存儲token和手機imei碼
     * @param array 數據數組
     * @param int 用戶ID
     * @return string TOKEN
     */
    protected function _create_token($user_info,$set_time = 0){
        // 記錄登入信息
        if ($set_time<=0) $set_time = time();
        $data = array(
            'last_login_ip' => get_client_ip(), // 登入人ID
            'last_login_time' => $set_time, // 登入時間
        );
        $data['sign'] = build_rand_str(6, 'char');    // 創建用戶token
        $sign = sys_md5(data_auth_sign($data));
        $uid = $user_info['uid'];
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
        $input_data['uid'] = $uid;
        $input_data['token'] = $token;
        $input_data['app_platform'] = C('APP_PLATFORM');
        $token = $this->user_token_model->saveUserLoginToken($input_data);
        if(!$token) {
            $this->errno = $this->user_token_model->errno;
            return false;
        }
        return $ret_token_data['token'] = $token;
    }


    /**
     * 退出登录
     * @author 刘富国
     */
    public function loginOut() {
        $uid = $this->getUserId();
        if ($uid) {
            $this->user_token_model->delAppTokenByUserId($uid,$this->app_platform);
        }
        return array('is_logout' => '1');
    }

}
