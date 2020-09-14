<?php

/**
 * JPUSH 逻辑类
 * User: 刘富国
 * Date: 2017/11/7
 * Time: 14:45
 */

namespace Common\Logic;
use Common\Library\JPush\Client ;
use Common\Model\PushUserLogModel;
class Jpush{
    protected $client_obj = null;
    protected $app_key = '';
    protected $master_secret = '';
    public $errno = 0;
    public $errmsg = '';
    static  $NOT_SEND = 0;
    static  $SEND_SUCCESS = 1;
    static  $SEND_ERROR = 2;
    static  $VIEW_ALREADY = 3;
    protected $push_user_log_model = null;

    //jpush返回状态字典
    public static $jpush_status_arr = array(
        '200' => '成功',
        '400' => '該請求是無效的。相應的描述信息會說明原因。',
        '401' => '沒有驗證信息或者驗證失敗',
        '403' => '理解該請求，但不被接受。相應的描述信息會說明原因。',
        '404' => '資源不存在，請求的用戶的不存在，請求的格式不被支持',
        '405' => '該接口不支持該方法的請求',
        '410' => '請求的資源已下線。請參考相關公告',
        '429' => '請求超出了頻率限制。相應的描述信息會解釋具體的原因',
        '500' => '服務器內部出錯了。請聯系我們盡快解決問題。',
        '502' => '無效代理,業務服務器下線了或者正在升級。請稍後重試。',
        '503' => '服務暫時失效,服務器無法響應請求。請稍後重試',
        '504' => '代理超時,服務器在運行，但是無法響應請求。請稍後重試。',
    );

    function __construct() {
        $this->app_key    =  C('PUSH.APP_KEY');
        $this->master_secret = C('PUSH.MASTER_SECRET');
        $this->client_obj = new Client($this->app_key,$this->master_secret);
        $this->push_user_log_model = new PushUserLogModel();
    }

    public function pushToPerson($title, $content, $uid,$extras='',$app_platform = 'app_target',$push_id=0){
        if (empty($uid)) return  $this->return_error(__LINE__,'無用戶ID');
        if(empty($app_platform)) $app_platform = 'app_target';
        $reg_data = M('PushUser')
            ->field('reg_id')
            ->where(array('uid' => $uid,'app_platform'=>$app_platform))
            ->find();
        $reg_id = $reg_data['reg_id'] ;
        if (empty($reg_id)) return  $this->return_error(__LINE__,'無設備ID');
        if(empty($push_id)){
            $push_id = $this->push_user_log_model->addMsg($title,$content,$uid,$reg_id);
        }
        $push_log_update = array();
        $push_log_where['id'] = $push_id;
        $extras['push_id'] = $push_id;
        $push_info = array(
            'regIds' => $reg_id,
            'title' => $title,
            'content' => $content,
            'extras'=>$extras
        );
        //jpush
        $ret = $this->push_msg($push_info,$push_id);
        $push_log_update['update_time'] = time();
        $push_log_update['reg_id'] = $reg_id;
        //發送成功
        if( $ret['http_code']*1 == 200){
            $push_log_update['status'] = $this::$SEND_SUCCESS;
            $push_log_update['msg_id'] = $ret['body']['msg_id'];
        }
        //异常返回
        if(!is_array($ret) or $ret['http_code']*1 <> 200 ){
            $push_info['status'] = $this::$SEND_ERROR;
            if($ret['http_code']*1>0){
                $push_log_update['error_msg'] = self::$jpush_status_arr[$ret['http_code']*1].';'.json_encode($ret);
            }else{
                $push_log_update['error_msg'] = '異常返回:'.$ret;
            }
        }
        M('push_user_log')->where($push_log_where)->save($push_log_update);
        if($ret['http_code']*1 <> 200){
            return  $this->return_error(__LINE__,'發送失敗');
        }
        return  $push_log_update['msg_id'];
    }

    public function pushToAllUser($title, $content,  $extras='',$push_id=0){
        if(empty($push_id)){
            $push_id = $this->push_user_log_model->addMsg($title,$content);
        }
        $push_log_update = array();
        $push_log_where['id'] = $push_id;
        $extras['push_id'] = $push_id;
        $push_info = array(
            'title' => $title,
            'content' => $content,
            'extras'=>$extras
        );
        //jpush
        $ret = $this->push_msg($push_info,$push_id);
        $push_log_update['update_time'] = time();
        //發送成功
        if( $ret['http_code']*1 == 200){
            $push_log_update['status'] = $this::$SEND_SUCCESS;
            $push_log_update['msg_id'] = $ret['body']['msg_id'];
        }
        //异常返回
        if(!is_array($ret) or $ret['http_code']*1 <> 200 ){
            $push_info['status'] = $this::$SEND_ERROR;
            if($ret['http_code']*1>0){
                $push_log_update['error_msg'] = self::$jpush_status_arr[$ret['http_code']*1].';'.json_encode($ret);
            }else{
                $push_log_update['error_msg'] = '異常返回:'.$ret;
            }
        }
        M('push_user_log')->where($push_log_where)->save($push_log_update);
        if($ret['http_code']*1 <> 200){
            return  $this->return_error(__LINE__,'發送失敗');
        }
        return  $push_log_update['msg_id'];
    }

    //發送信息
    public function push_msg($push_info,$push_id){
        $obj = $this->client_obj->push();
        $push_payload = $this->getPushPayload($obj, $push_info) ;
        $push_payload_info['push_payload'] = json_encode($push_payload);
        M('push_user_log')->where('id = '.$push_id)->save($push_payload_info);
        try {
            $response = $obj->sendByPayload($push_payload);
            return $response;
        } catch (\Common\Library\JPush\Exceptions\JPushException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 组装发送平台内容
     * @param $push_info
     * @return array|bool
     */
    public function  getPushPayload($push_obj,$push_info,$send_time=0){
        $title = trim($push_info['title']);
        $content = trim($push_info['content']);
        $url= trim($push_info['url']);
        $regId_arr = $push_info['regIds'];
        $send_time= $send_time*1;
        $extras = array();
        $push_payload = array();
        if(!is_object($push_obj) or empty($title) or empty($content) ){
            return false;
        }
        //apns环境,false测试环境，true生产环境
        if(C('IS_DEBUG_VER')){
            $options = array('apns_production'=> false);
        }else{
            $options = array('apns_production'=> true);
        }
        $extras['title'] = $title;
        $extras['url'] =  $url;
        $extras['content'] = $content;
        $extras['type'] = '';
        $extras['date_mills'] = $send_time;
        $extras['is_read'] = '0';
        if($push_info['extras']){
            $extras = array_merge($extras,$push_info['extras']);
        }

        $push_obj->setPlatform('all'); //推送平台
        if(!empty($regId_arr)){
            $push_obj->addRegistrationId($regId_arr);//根据设备ID发送
        }else{
            $push_obj->setAudience('all');//全平台广播
        }
        $push_obj->options($options);
        $android_notification = array(
            'title' => $title,
            'builder_id' => 2,
            'extras' => $extras
        );
        $ios_notification = array(
            'badge' => '+1',
            'extras' => $extras,
            'content-available' => true ,
        );

        $push_obj->androidNotification($content,$android_notification);
        $push_obj->iosNotification($content,$ios_notification);

        $push_payload = $push_obj->build();
        return $push_payload;
    }


    /**
     * 设置错误信息
     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     */
    public function return_error($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return $errno == 0 ;
    }

    /**
     * 获取最后一次错误代码
     */
    public function last_error(){
        if($this->errno > 0 ){
            return   $this->errmsg ? $this->errmsg : 'UNKNOW_ERROR';
        }
        else{
            return '';
        }
    }

}