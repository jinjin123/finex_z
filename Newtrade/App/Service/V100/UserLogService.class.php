<?php
namespace App\Service\V100;
use App\Service\ServiceBase;
use Common\Logic\UserLog;

/**
 * 用户登陆日志
 * Class UserLogService
 * @package App\Service\V110
 * 劉富囯
 * 2017-10-29
 */
class UserLogService extends ServiceBase{
    protected $user_log_obj = null;
    public function  __construct() {
        $this->user_log_obj = new UserLog();
    }

    /**
     * 根据用户ID，查询用户登录日志
     * @return array|int|object
     */
    public function getUserLogByUid(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $user_list = $this->user_log_obj->getUserLoginInfoByUid($uid,'',$page,$limit);
        if(!$user_list) return (object)array() ;
        $total = $user_list['total']*1;
        $log_list = $user_list['list'] ;
        $ret_log_list = array();
        if(!empty($log_list)){
            foreach ($log_list as $key => $item){
                $ret_log_list[$key]['id'] = $item['id'];
                $ret_log_list[$key]['type_name'] = formatLogType($item['type']);
                $ret_log_list[$key]['ip_area'] = getIpArea($item['ip']);
                $ret_log_list[$key]['add_time'] = $item['add_time'];
            }
        }else{
            $ret_log_list = (object)array() ;
        }
        $ret = array(
            'total'  => $total,
            'list'   => $ret_log_list,
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );
        return $ret;
    }



}