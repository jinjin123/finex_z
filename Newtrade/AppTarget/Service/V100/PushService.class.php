<?php
namespace AppTarget\Service\V100;

use App\Service\ServiceBase;
use Common\Model\PushUserLogModel;

/**
 *  消息推送
 * @author 劉富國
 * 2017-10-16
 *
 */
class PushService extends ServiceBase{
    protected $push_user_log_model = null;

    public function __construct()  {
        parent::__construct();
        $this->push_user_log_model = new PushUserLogModel();
    }

    //查询未读消息列表
    public function getPushMsgList(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $push_list =  $this->push_user_log_model
                            ->getNotViewPushMsgList($uid,'',$page,$limit);
        if(!$push_list) return (object)array() ;
        $total = $push_list['total']*1;
        $ret = array(
            'total'  => $total,
            'list'   => $push_list['list'],
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );
        return $ret;
    }

    //查看消息详细内容，并设置消息为已阅
    public function getPushMsgView(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $id = $data['id']*1;
        if ($id < 0) return 10000;
        $where_arr['id'] = $id;
        $where_arr['uid'] = $uid;
        $push_info =  M('PushUserLog')->where($where_arr)->find();
        if(empty($push_info)) return 10024;
        $this->push_user_log_model->setMsgViewAlready($id,$uid); //设置消息为已阅
        return array('title' => $push_info['title'],
            'content' => $push_info['content'],
            'add_time' => $push_info['add_time']);
    }

    //删除
    public function delPushMsg(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $id = $data['id'];
        if (empty($id)) return 10000;
        $id_arr = explode(',',$id);
        if (empty($id_arr)) return 10000;
        $where_arr['id'] = array('in',$id_arr);
        $where_arr['uid'] = $uid;
        $where_arr['status'] = PushUserLogModel::$SEND_SUCCESS;
        $push_list =  M('PushUserLog')->where($where_arr)->select();
        if(empty($push_list)) return 10024;
        $where_update_arr['id'] = array('in',$id_arr);
        $where_update_arr['uid'] = $uid;
        $update['status'] = PushUserLogModel::$VIEW_ALREADY; //已查阅
        $update['update_time'] = time();
         M('PushUserLog')->where($where_update_arr)->save($update);
        return array('is_del' => 1);
    }

    //全删除
    public function delAllPushMsg(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $where_update_arr['uid'] = $uid;
        $where_update_arr['status'] = PushUserLogModel::$SEND_SUCCESS;
        $update['status'] = PushUserLogModel::$VIEW_ALREADY; //已查阅
        $update['update_time'] = time();
        M('PushUserLog')->where($where_update_arr)->save($update);
        return array('is_del' => 1);
    }
}
