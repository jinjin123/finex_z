<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * JUSH用戶于設備ID對應表
 * 劉富國
 * 2017-11-07
 * Class PushUserModel
 * @package Common\Model
 */

class PushUserModel extends BaseModel{

        //添加用戶和設備ID
    public function add_push_user($uid,$regId,$app_platform){

        if (empty($regId) or empty($app_platform)) return true;
        $where['reg_id'] = $regId;
        $where['app_platform'] = $app_platform;
        $ret_reg = M('PushUser')->where($where)->find();
        M('PushUser')->where(array('uid' => $uid,'app_platform'=>$app_platform))
                    ->save(array('uid' => 0, 'update_time' => time()));
        if ($ret_reg) {
            M('PushUser')->where(array('id' => $ret_reg['id']))
                        ->save(array('uid' => $uid, 'update_time' => time()));
        } else {
            M('PushUser')->add(array(
                'uid' => $uid,
                'reg_id' => $regId,
                'app_platform' => $app_platform,
                'add_time' => time(),
                'update_time' => time()
            ));
        }
        return true;
    }


}