<?php

/**
 * JPUSH 逻辑类
 * User: 刘富国
 * Date: 2017/11/7
 * Time: 14:45
 */

namespace Common\Logic;
class Notice{

    public  function getNoticeDataByUid($page,$limit){
        $count = M("Notice")->count();//总记录数
        if(empty($count) or $count<1) return false;
        $log_list = M("Notice")->field("id,`zh-cn-title` as title,add_time")
            ->limit($limit)
            ->page($page)
            ->select();
        return ['list'=>$log_list,'total'=>$count];
    }
	
	public  function getNoticeDetailById($id){
        $data = M("Notice")->field("id,`zh-cn-title` as title,add_time,`zh-cn-content` as content")
			->where(array('id'=>$id))
            ->find();
        return $data;
    }
	
}