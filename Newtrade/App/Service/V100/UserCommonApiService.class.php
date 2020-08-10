<?php
namespace App\Service\V100;

use App\Service\ServiceBase;
use Common\Api\RedisCluster;

/**
 * 用户常用方法
 * @author 劉富國
 * 2017-11-07
 *
 */
class UserCommonApiService extends ServiceBase{

    private $redis=NULL;
    protected  $push_obj = null;
    public function __construct(){
        // $redisObj = new RedisCluster();
        $this->redis  = RedisCluster::getInstance();
        $this->push_obj =  new \Common\Logic\Jpush();
    }

    //修改语言版本
    public function setLang(){
        $uid = $this->getUserId();
        $data = $this->getData();
        $langSet= $data['var_language']; //语言版本
        $phone_imei  = trim($data['phone_imei']); //機識別編碼
        $langList = C('LANG_LIST',null,'zh-cn');
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

    //用户发送消息
    public  function  sendMsgToPerson(){
        $data = $this->getData();
        $uid = $data['uid']*1;
        $title = $data['title'];
        $content= $data['content'];
        $extras= $data['extras'];
        if($uid < 1
            or empty($title)
            or empty($content)
        ) return 10000;
        $ret =  push_msg_to_app_person($title,$content,$uid,$extras);
        if(!$ret) return 10025;
        return array('is_success' => 1);
    }

    //批量用户发送消息
    public  function  sendMsgToPersonList(){
        $data = $this->getData();
        $send_msg_list = $data['send_msg_list'];

        if(empty($send_msg_list)) return 10000;
        $ret_error = array();
        foreach ($send_msg_list as $key => $item ){
            $uid = 0;
            $title = '';
            $content = '';
            $extras = '';
            $uid = $item['uid'];
            $title = $item['title'];
            $content= $item['content'];
            $extras= $item['extras'];
            if($uid < 1
                or empty($title)
                or empty($content)
            ) continue;
            $ret =  push_msg_to_app_person($title,$content,$uid,$extras);
            if(!$ret) {
                $itme_error = array();
                $itme_error['uid'] = $uid;
                $itme_error['errmsg'] = $this->push_obj->errmsg;
                $ret_error[] = $itme_error;
            }
        }
        if(!empty($ret_error)) array('error_list' => $ret_error);
        return array('error_list' => 0);
    }

    //批量websocket msg to user
    public  function  sendWebsocketToUser(){
        $data= $this->getData();
        if(empty($data)) return 10000;
        $ret =  push_websocket_msg_to_person($data);
        if(!$ret) return 10025;
        return array('is_success' => 1);
    }
}
