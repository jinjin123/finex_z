<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Library\Tool\Token;
use Common\Api\RedisCluster;
use Common\Logic\UserLog;
use Home\Logics\PublicFunctionController;
use Common\Model\UserWatchwordModel;

/**
 * 用戶驗證碼模型
 * 劉富國
 * 2017-10-24
 * Class UserTokenModel
 * @package Common\Model
 */

class UserTokenModel extends BaseModel{
    public $redis=NULL;
    static $IS_BIND = 1;
    static $IS_NOT_BIND = 0;
    protected $user_log_obj = null;
    protected $public_obj = null;
    protected $user_watch_model = null;
    public function  __construct(){
        parent::__construct();
        $this->public_obj = new PublicFunctionController();
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $this->user_log_obj = new UserLog();
        $this->user_watch_model = new UserWatchwordModel();
    }

    /**
     * 校驗登錄token
     * @param $uid
     * @param $sign
     * @return bool
     */
    public  function  checkUserLoginToken($data){
        $uid = $data['uid'];
        $phone_token  = $data['phone_token'];
        $app_platform = $data['app_platform'];
        $where_token['uid'] = $uid;
        $where_token['app_platform'] = $app_platform;
        $db_token = $this->where($where_token)->field('phone_token,expire')->find();
        //token有误
       if(empty($db_token)
           or !$db_token
           or crc32($db_token['phone_token']) !== crc32($phone_token) ){
           return  $this->return_error(9998) ;
       }
        //登录会话已超时，请重新登录
        if(time() >= $db_token['expire']){
            return  $this->return_error(10029) ;
        }
       // 系统APP登录信息发生改变时，说明有其他平台登录，返回错
       if ($app_platform == 'app_target'){
           //校验用户状态
           if(!$this->checkUserStatus($uid)){
               $this->user_log_obj->loginOutApp($uid,$app_platform);
               return  $this->return_error($this->errno);
           }
           //密码或者动态口令错误5次，冻结24小时
           if(!$this->checkUserMiss($uid)){
               $this->user_log_obj->loginOutApp($uid,$app_platform);
               return  $this->return_error($this->errno);
           }
           //口令APP是否绑定
           if(!$this->user_watch_model->checkUserBind($uid)){
               $this->user_log_obj->loginOutApp($uid,$app_platform);
               return  10044;
           }

           // 是否有其他設備登錄： 設備ID+$uid
           $where['uid'] = $uid;
           $tableName    = 'UserLoginSameClient';
           $loginData    = M($tableName)->where($where)->find();
           if (!empty($loginData) and $loginData['client_token'] != $uid.$data['phone_imei']) {
               return  $this->return_error(10035);
           }
       }
        $data = array('expire' => time() + 7*24*60*60); //7天后失效
        $this->where($where_token)->save($data);
        return true;
    }



    /**
     * 創建和存儲token和手機imei碼
     * @param $data
     * @return mixed
     */
    public function saveUserLoginToken($data){
        $uid = $data['uid'];
        $app_platform = $data['app_platform'];
        $token  = $data['token'];
        $token_data['phone_token']  = $data['sign'];
        $token_data['app_platform'] = $app_platform;
        $token_data['expire']   = $data['time'] + 7*24*60*60; // 7天后失效
        $where_token['uid'] = $uid;
        $where_token['app_platform'] = $app_platform;
        $token_info = $this->where($where_token)->find();
        if (!empty($token_info)) {
            $token_data['uid'] = $uid;
            $token_data['change_time'] = $data['time'];
            $ret = M('UserToken')->where(array('id' => $token_info['id']))->save($token_data);
        } else {
            //新用戶，綁定手機識別編碼
            $token_data['uid'] = $uid;
            $token_data['add_time'] = $data['time'];
            $ret =  $this->add($token_data);
        }
        if(!$ret) return $this->return_error(9999,'操作失敗') ;
        $ret_token = Token::buildToken($token);
        return $ret_token;
    }

    /**
     * 所有app退出
     * @param $uid
     * @param string $app_platform
     * @return bool
     */
    public function delAppTokenByUserId($uid,$app_platform=''){
        $uid = $uid*1;
        if($uid < 1) return false;
        $token_data['phone_token']  = '';
        $token_data['expire']  = 0;
        $token_data['change_time'] = time();
        $where_app['uid'] = $uid;
        if(!empty($app_platform)){
            $where_app['app_platform'] = $app_platform;
        }
        return M('user_token')->where($where_app)->save($token_data);
    }

    public function checkUserStatus($uid)
    {
        //验证用户是否被禁用或者存在交易风险
        $status = M('User')->where(['uid' => $uid])->getField('status');
        if ($status == -1) {
            return $this->return_error(30016);
        }
        if ($status == -2) {
            return $this->return_error(10007);
        }
        return true;
    }

    //24小时冻结
    public function checkUserMiss($uid)
    {
        // 如果账号已经登陆，但是在其他地方继续登陆，如果密码或者动态口令错误达到5次以上，则已经登陆的设备需要退出登陆
        $token_miss_num = $this->public_obj->checkUserPassMissNumOrTokenMissNum($uid,1);
        $pass_miss_num = $this->public_obj->checkUserPassMissNumOrTokenMissNum($uid,2);
        if ($token_miss_num >= 5 || $pass_miss_num >= 5) {
             return $this->return_error(30032);
        }
        return true;
    }

}