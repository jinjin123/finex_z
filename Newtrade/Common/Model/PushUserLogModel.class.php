<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * JPUSH消息推送歷史記錄
 * 劉富國
 * 2017-11-07
 * Class PushUserModel
 * @package Common\Model
 */

class PushUserLogModel extends BaseModel{
    static  $NOT_SEND = 0;
    static  $SEND_SUCCESS = 1;
    static  $SEND_ERROR = 2;
    static  $VIEW_ALREADY = 3;
    public function  __construct(){
        parent::__construct();
    }
      //todo 記錄日志



    //  设置消息已阅
    public function setMsgViewAlready($id,$uid){
        $where_arr['id'] = $id;
        $where_arr['uid'] = $uid;
        $update['status'] = $this::$VIEW_ALREADY; //已查阅
        $update['update_time'] = time(); //已查阅
        return  M('PushUserLog')->where($where_arr)->save($update);
    }

    //  添加消息
    public function addMsg($title,$content,$uid=0,$regId=0){
        $pushLog['uid'] = $uid;
        $pushLog['title'] = $title;
        $pushLog['content'] = $content;
        $pushLog['add_time'] = time();
        $pushLog['reg_id'] = $regId;
        $pushLog['status'] = $this::$NOT_SEND;
        $push_id =  M('push_user_log')->add($pushLog);
        return  $push_id;
    }

    //  消息已发送
    public function setSendMsgSuccess($pushId,$msgId = 0,$errorMsg=''){
        $pushLogWhere['id'] = $pushId;
        $pushLogUpdate['status'] = $this::$SEND_SUCCESS;
        $pushLogUpdate['msg_id'] = $msgId;
        $pushLogUpdate['update_time'] = time();
        $pushLogUpdate['error_msg'] = $errorMsg;
        return  M('push_user_log')->where($pushLogWhere)->save($pushLogUpdate);;
    }

    //查看已发送的消息列表
    public function getNotViewPushMsgList ($uid,$where,$page,$limit){
        $where['uid'] = $uid;
        $where['status'] = $this::$SEND_SUCCESS;
        $count = $this->where($where)->count();//总记录数

        if(empty($count) or $count<1) return  false;
        $log_list = $this->field('id,title,content,add_time')
            ->where($where)
            ->limit($limit)
            ->page($page)
            ->order('id desc')
            ->select();
        return ['list'=>$log_list,'total'=>$count];
    }




}