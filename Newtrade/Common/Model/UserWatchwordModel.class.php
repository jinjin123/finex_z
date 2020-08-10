<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Library\Tool\Token;
use Common\Api\RedisCluster;

/**
 * 用戶口令模型
 * 劉富國
 * 2017-10-24
 * Class UserTokenModel
 * @package Common\Model
 */

class UserWatchwordModel extends BaseModel{
    public $redis=NULL;
    static $IS_BIND = 1;
    static $IS_NOT_BIND = 0;
    const BIND = 1; //绑定
    const UNBUNDING = 2; //解绑
    const USE_LOGIN = 1;//口令使用类型:登录
    const USE_CHANGE_TRADE_PWD = 2;//修改资金密码
    const USE_CHANGE_LOGIN_PWD = 3;//修改登录密码
    public function  __construct(){
        parent::__construct();
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
    }

    /**
     * 綁定手機識別編碼
     * @param $uid
     * @param $phone_imei
     * @return bool
     */
    public  function bindAccount($uid,$phone_imei){
        $where_token = array();
        $where_token['uid'] = $uid;
        $token_info = $this->where($where_token)->find();
        if($token_info['is_bind'] == $this::$IS_BIND ) {
            return $this->return_error(10013,'該賬號已被綁定') ;
        }
        $isUnbundlingUserToken = $this->redis->get('setUnbundlingUserToken'.$uid);
        if($isUnbundlingUserToken) return $this->return_error(10027,'解绑口令后，24小时候才可重新绑定') ;
        $secret_info = $this->_create_serial($check_create_serial_times = 0);
        if(!$secret_info)  return $this->return_error(10022,'綁定的序列號生成失敗，請重試') ;
        $token_data['is_bind'] = $this::$IS_BIND;
        $token_data['phone_imei'] = $phone_imei;
        $token_data['serial_num'] = $secret_info['serial_num'];
        $token_data['secret_key'] = $secret_info['secret_key'];
        if(empty($token_info)) {
            $token_data['uid'] = $uid;
            $token_data['add_time'] = time();
            $ret = $this->add($token_data);
        }else{
            $token_data['change_time'] = time();
            $where['id'] = $token_info['id'];
            $ret = $this->where($where)->save($token_data);
        }
        if(!$ret) return $this->return_error(9999,'操作失敗') ;
        //记录日志
        $token_log_data['uid'] = $uid;
        $token_log_data['type'] = self::BIND; //绑定
        $token_log_data['add_time'] = time();
        $ret = M('token_change_log')->add($token_log_data);
        if(!$ret) return $this->return_error(9999,'操作失敗') ;
        return true;
    }

    /**
     * 生成登入口令所需要的时间戳和密钥
     * @param $uid
     * @return array|int
     */
    public function setWatchword($uid,$secret_key){
        $current_time_key='WATCHWORD_CURRENT_TIME_'.$uid;
        $current_time = $this->redis->get($current_time_key);
        if (!empty($current_time)){
            $this->redis->del($current_time_key);
            $this->redis->del('USER_SECRET_KEY_'.$uid.$current_time);
        }
        $current_time = time();
        $current_time_key='WATCHWORD_CURRENT_TIME_'.$uid;
        $this->redis->setex($current_time_key,3600*12, $current_time);
        $this->redis->setex('USER_SECRET_KEY_'.$uid.$current_time,3600*12, $secret_key);
        return array('current_time'=>$current_time,'secret_key'=>$secret_key);
    }

    /**
     * 生成序列號和還原密碼
     * 如果有重複還原密碼，需要重新生成，如果超過10次重複，返回錯誤
     */
    protected function _create_serial($check_create_serial_times = 0){
        //生成序列號
        $serial_num = build_rand_str(4, 'num').build_rand_str(4, 'char')
            .build_rand_str(4, 'num').build_rand_str(4, 'char');
        //生成還原密碼
        $secret_key = build_rand_str(4, 'num').build_rand_str(10, 'char');
        $serial_num = strtoupper($serial_num);
        $secret_key = strtoupper($secret_key);
        $where['secret_key'] = $secret_key;
        $ret = $this->where($where)->find();
        if($ret){
            $check_create_serial_times += 1;
            if($check_create_serial_times > 10 ) return false;
            $ret_secret = $this->_create_serial($check_create_serial_times);
            if(!$ret_secret) return false;
            $secret_info['serial_num'] = $ret_secret['serial_num'];
            $secret_info['secret_key'] = $ret_secret['secret_key'];
            return $secret_info;
        }
        $secret_info['serial_num'] = $serial_num;
        $secret_info['secret_key'] = $secret_key;
        return $secret_info;
    }

    /**
     * 校验口令是否正确
     * 在密钥时间戳的基础上，每30秒累加，生成新的加密时间戳
     * @param $uid 用户名
     * @param $check_watchword 口令
     * @return bool
     */
    public function  checkWatchword($uid,$check_watchword){
        $current_time_key='WATCHWORD_CURRENT_TIME_'.$uid;
        $current_time = $this->redis->get($current_time_key); //解密初始时间戳
        if(empty($current_time))  return false;
        $this_wathchword = $this->_createWatchword($uid);
        //如果正确，对应的码只能用一次
        if(strtoupper($check_watchword) == strtoupper($this_wathchword)) {
            $old_current_wathchword_key = 'USER_WATCHWORD_USED_'.$uid.'_'.$current_time.'_'.$this_wathchword;
            $old_current_wathchword = $this->redis->get($old_current_wathchword_key);
            if($old_current_wathchword) return false;
            $this->redis->setex($old_current_wathchword_key,300, $this_wathchword);
            return true;
        }
        return false;
    }

    /**
     * 生成口令
     * @param $current_time  时间戳
     * @param $secret_key        密钥
     * @return bool|string
     */
    protected function _createWatchword($uid){
        $current_time_key='WATCHWORD_CURRENT_TIME_'.$uid;
        $current_time = $this->redis->get($current_time_key); //解密初始时间戳
        if(empty($current_time))  return false;
        $secret_key = $this->redis->get('USER_SECRET_KEY_'.$uid.$current_time); //解密密钥
        $now_time = time();
        $now_current_time = floor(($now_time-$current_time)/30)*30 + $current_time; //每30秒累加
        $p_token = md5(C('TOKENSUFFIX').$now_current_time.$secret_key);
        $wathchword = substr($p_token,5,6);
        return $wathchword;
    }

    /**
     * 解綁手机
     * @param $uid
      * @param $serial_num  序列號
     * @param $secret_key  解碼秘鑰
     * @return bool
     */
    public function unbundlingUserToken($uid,$serial_num,$secret_key){
        $where['uid'] = $uid;
        $where['secret_key'] = $secret_key;
        $where['serial_num'] = $serial_num;
        $user_token   = $this->where($where)->find();
        if(empty($user_token) ){
            return $this->return_error(10026,'令牌序列号或令牌秘钥错误') ;
        }
        $token_data['secret_key'] = '';
        $token_data['serial_num'] = '';
        $token_data['is_bind'] = $this::$IS_NOT_BIND;
        $ret = $this->where($where)->save($token_data);
        if(!$ret) return $this->return_error(9999,'操作失敗') ;
        //记录日志
        $token_log_data['uid'] = $uid;
        $token_log_data['type'] = self::UNBUNDING; //解绑
        $token_log_data['add_time'] = time();
        $ret = M('token_change_log')->add($token_log_data);
        $this->redis->setex('setUnbundlingUserToken'.$uid, 3600*24, true);
        if(!$ret) return $this->return_error(9999,'操作失敗') ;
        return $ret;
    }

    /**
     *  查看用户是否已经绑定手机
     * @param $uid
     * @return array|bool
     * 劉富國
     * 2017-10-24
     */
    public function checkUserBind($uid){
        $where['uid'] = $uid;
        $where['is_bind'] = $this::$IS_BIND;
        $user_token   = $this->where($where)->find();
        if(empty($user_token)) return $this::$IS_NOT_BIND;
        return $this::$IS_BIND;
    }

    /**
     * 校驗是否被其他手機或者賬號綁定
     * @param $uid
     * @param $phone_imei
     * @return int
     */
    public function checkOtherBindByUidImei($uid,$phone_imei){
        $where_token['uid'] = $uid;
        $where_token['is_bind'] = $this::$IS_BIND;
        $where_token['phone_imei'] = array('neq',$phone_imei);
        $token_info = $this->where($where_token)->find();
        if(!empty($token_info)){
            return $this->return_error(10011,'該賬號已經被其他手機綁定') ;
        }
        $where_imei['phone_imei'] = $phone_imei;
        $where_imei['is_bind'] = $this::$IS_BIND;
        $where_imei['uid'] = array('neq',$uid);
        $check_token_info = $this->where($where_imei)->select();
        if(!empty($check_token_info)){
            return $this->return_error(10023,'該手機綁定了其他賬號') ;
        }
        return true;
    }

    /** 查看用户綁定手機的序列號，中間有替換*號
     * 劉富國
     * 2017-10-24
     * @param $uid
     * @return int|mixed
     */
    public function getUserSecretNum($uid){
        $where['uid'] = $uid;
        $where['is_bind'] = $this::$IS_BIND;
        $user_token   = $this->where($where)->find();
        if(empty($user_token) or empty($user_token['serial_num'])) return 0;
        $serial_num = $user_token['serial_num'];
        $serial_num = substr_replace($serial_num,'*********',5,9);
        return $serial_num;
    }

    /**
     *  记录用户使用口令日志
     * @param $uid
     * @param $sys_token 系统生成口令
     * @param $user_token  用户输入的口令
     * @param $use_type 1.登录  2.修改资金密码  3.修改登录密码
     * @return mixed
     */
    public function setTokenUsingLog($uid,$user_token,$use_type){
        $token_log_data['uid'] = $uid;
        $token_log_data['sys_token']    = strtolower($this->_createWatchword($uid));
        $token_log_data['user_token']   = strtolower($user_token);
        $token_log_data['type']     = $use_type;
        $token_log_data['add_time'] = time();
        $ret = M('token_using_log')->add($token_log_data);
        return $ret;
    }


}