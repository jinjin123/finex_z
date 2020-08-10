<?php
namespace Common\Logic;

use Home\Logics\PublicFunctionController;
/**
 * 用户日志逻辑类
 * Class UserLog
 * @package Common\Logic
 * 刘富国
 * 2017-10-26
 */
class UserLog {

    /**
     * 查询根据用户id,查询操作日志
     * @param $uid
     * @param $where
     * @param $page
     * @param $limit
     * @return array|bool
     */
    public  function getUserLoginInfoByUid($uid,$where,$page,$limit){
        $mod = $uid%4;//确定分表尾号
        $time = strtotime('-1 month');
        $where['add_time'] = array('EGT',$time); //一个月前的时间戳
        $where['uid'] = $uid;
        $count = M("UserLog$mod")->where($where)->count();//总记录数
        if(empty($count) or $count<1) return false;
        $log_list = M("UserLog$mod")
            ->where($where)
            ->limit($limit)
            ->page($page)
            ->order('id desc')
            ->select();

        return ['list'=>$log_list,'total'=>$count];
    }

    /**
     * 记录用户登录日志
     * @param $uid
     * @return bool
     */
     public function setUserLoginSameClient($uid,$set_time = 0,$phone_imei){
         if (empty($set_time)) $set_time = time();
         // 查找用戶登陸信息
         $logWhere['uid'] = $uid;
         $tableName       = 'UserLoginSameClient';
         $userLoginLog    = M($tableName)->where($logWhere)->find();
         // 登陸日誌信息
         $dengluDataNew['client_token']       = $uid.$phone_imei; // 登录信息
         $dengluDataNew['add_time'] = $set_time; // 当前时间
         $dengluDataNew['status']   = 1; // 在线

         // 判斷用戶是否是首次登陸
         if ($userLoginLog) {
             $res =  M($tableName)->where($logWhere)->save($dengluDataNew);
         }else{
             $dengluDataNew['uid'] = $uid;// 用戶ID
             $res =  M($tableName)->add($dengluDataNew);
         }
         // 設置最後登陸時間
         $lastLoginTimeWhere['uid']       = $uid;
         $lastLoginTimeData['updated_at'] = $set_time;
         M('User')->where($lastLoginTimeWhere)->save($lastLoginTimeData);
         return true;
     }


    /**
     * 退出登录
     * @author 刘富国
     * @param  int $uid 用户id
     * @return bool
     */
    public function loginOutApp($uid=0,$app_platform=''){
        $where['uid'] = $uid;
        $tableName    = 'UserLoginSameClient';
        $loginRes     = M($tableName)->where($where)->find();
        if(empty($loginRes)) return true;
        $loginDataNew['client_token'] = get_client_ip(); // 获得客户端IP地址
        $loginDataNew['add_time']     = time(); // 当前时间
        $loginDataNew['status']       = 0; // 下线
        $loginLogRes                  = M($tableName)->where($where)->save($loginDataNew);
        if (empty($loginLogRes))  return true;
        $token_data['phone_token']  = '';
        $token_data['expire']  = 0;
        $where_app['uid'] = $uid;
        if(!empty($app_platform)){
            $where_app['app_platform'] = $app_platform;
        }
        M('user_token')->where($where_app)->save($token_data);
        return true;
    }
  
}












