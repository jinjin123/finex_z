<?php
namespace App\Service\V100;
use App\Service\ServiceBase;
use Common\Api\RedisCluster;
use Common\Model\UserTokenModel;
use Common\Model\UserWatchwordModel;
use Common\Logic\UserLog;

/**
 * 生成登入口令
 * Class TokenService
 * @package App\Service\V110
 * 劉富囯
 * 2017-10-19
 */
class TokenService extends ServiceBase{
    public $redis=NULL;
    protected $user_token_model = null;
    protected $user_watchword_model = null;
    protected $APP_LOGIN_PHONE_CODE = "APP_WATCHWORD_LOGIN_PHONE_CODE";
    static $IS_BIND = 1;
    static $IS_NOT_BIND = 0;
    private   $missNum           = 5;
    protected $user_log_obj = null;
    protected $app_platform =  '';
    //构造方法
    public function  __construct()
    {
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $this->user_token_model = new UserTokenModel();
        $this->user_watchword_model= new UserWatchwordModel();
        $this->user_log_obj = new UserLog();
        $this->app_platform = C('APP_PLATFORM');
    }
    /**
     * 生成登入口令所需要的时间戳和密钥
     * @return array|int
     */
    public function createWatchwordToken() {
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $token_info = $this->user_watchword_model
            ->where(array('uid'=>$uid))->Field('secret_key,is_bind')
            ->find();
        if(empty($token_info))  return 9998;
        if(empty($token_info) or empty($token_info['secret_key']) or $token_info['is_bind'] <>1){
            return 10012; //該賬號未綁定
        }
        $where['uid'] = $uid;
        $user_info   = M('User')->where($where)->find();
        if (empty($user_info)) return 10004;  //用户不存在
        if($user_info['status'] == '-2')  return (10007);// 交易風險
        if($user_info['status'] == '-1')return (10008);// 賬戶鎖定
        if($user_info['status'] == '0') return (10009);// 賬戶未激活
        $ret_data = $this->user_watchword_model->setWatchword($uid,$token_info['secret_key']);
        return  $ret_data;
    }

    /**
     *   綁定手機
    1.验证短信驗證碼
    2.查询数据库 改账号是否已经绑定手机
    4.生成序列号 + 还原密码
    5.将序列号和还原密码（密钥）存入数据，手机绑定字段设置为已绑定
     */
    public function bindAccount() {
        $data = $this->getData();
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $sms_code  = trim($data['code']);  //短信驗證碼
        $phone_imei  = trim($data['phone_imei']); //機識別編碼
        if (empty($phone_imei)) return 10010;
        $where['uid'] = $uid;
        $user_info   = M('User')->where($where)->find();
        if (empty($user_info)) return 10004;  // 用戶不存在
        if (empty($user_info['phone'])) return 10006;  //手機號碼為空
        if($user_info['status'] == '-2')  return (10007);// 交易風險
        if($user_info['status'] == '-1')return (10008);// 賬戶鎖定
        if($user_info['status'] == '0') return (10009);// 賬戶未激活
        $ret = $this->_check_sms_code($user_info,$sms_code); //校验登录验证码
        if(!$ret) return  $this->return_error_num($this->errno,$this->errmsg);
        //校驗是否被其他手機或者賬號綁定
        $ret = $this->user_watchword_model->checkOtherBindByUidImei($uid,$phone_imei);
        if(!$ret) return $this->user_watchword_model->errno;
        //綁定手機識別編碼
        $ret = $this->user_watchword_model->bindAccount($uid,$phone_imei);
        if(!$ret) return $this->user_watchword_model->errno;
        //第一次绑定，加积分
        $is_first_bind = M('UserScoreLog'.$uid%4)->where(['uid'=>$uid,'status'=>5])->find();
        if( !$is_first_bind ){
            $publicFun = new \Home\Logics\PublicFunctionController();
            $ret = $publicFun->calUserIntegralAndLeavl($uid,
                10,
                ['operationType'=>'inc','scoreInfo'=>'第一次绑定APP赠送积分','status'=>5]);

        }
        return array('is_bind'=>1);
    }

    /**
     *   查看用户是否已经绑定手机
     **/
    public function checkUserBind(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $where['uid'] = $uid;
        return array('is_bind'=>$this->user_watchword_model->checkUserBind($uid));
    }

    /**
     * 获取序列号和秘鑰
     */
    public  function getUserSerial(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $where['uid'] = $uid;
        $user_token_info   = $this->user_watchword_model->where($where)->find();
        if(empty($user_token_info))  return 9998;
        if(empty($user_token_info['serial_num'] or  $user_token_info['secret_key'] )) return 10021;
        return array('serial_num'=>$user_token_info['serial_num'],'secret_key'=>$user_token_info['secret_key']);
    }


    protected function _check_sms_code($user_info,$sms_code){
        $check_redis_login_sms_key = 'app_watchword_login_sms_check';
        $uid = $user_info['uid'];
        $check_num = $this->redis->get($check_redis_login_sms_key.$uid);
        $check_num = $check_num*1;
        if(!checkSmsCode($user_info['uid'], $user_info['phone'], $this->APP_LOGIN_PHONE_CODE, $sms_code)){
            $check_num = $check_num+1;
            $this->redis->setex($check_redis_login_sms_key.$uid,3600*24,$check_num);
            //验证码失败大于等于5次，设置用户状态为封号
            if($check_num>=5){
                $where['uid'] = $uid;
                M('User')->where($where)->setField('status', '-1');
                $this->user_log_obj->loginOutApp($uid,$this->app_platform);//退出
                $errmsg      = L('_DLYZMCWYBSDQLXKF_');
                $this->redis->del($check_redis_login_sms_key.$uid);
                return $this->return_error(10008,$errmsg);
            }
            $lastNUm          = $this->missNum - $check_num;
            $errmsg      = L('_DLMMCWNHY_').$lastNUm.L('_CJHDL_');
            return $this->return_error(10002,$errmsg);
        }
        $this->redis->del($check_redis_login_sms_key.$uid);
        return true;
    }


}