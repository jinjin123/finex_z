<?php
/**
 * 币种配置信息模型类
 * @author lirunqing 2017-10-16 14:34:04
 */
namespace Home\Model;
use Think\Model;

class UserMissLogModel extends Model {
	protected $tableName = 'user_miss_log'; 

	 /**
     * 检测用户登陆密码或口令错误次数
     * @author lirunqing 2017-10-25T22:07:18+0800
     * @param  integer $userId 用户id
     * @param  integer $type 1表示密码错误次数，2表示口令错误次数
     * @return bool
     */
    public function checkLoginPassMiss($userId, $type=1){
        
        $startTime         = strtotime(date('Ymd'));
        $endTime           = strtotime(date('Ymd', strtotime('+1 days')));
        $where['uid']      = $userId;
        $where['add_time'] = array(array('EGT', $startTime),array('LT', $endTime), 'AND');
        $missRes           = $this->where($where)->find();

        if ($missRes['pass_miss_num'] >= 5 && $type == 1) {
            return false;
        }

        if ($missRes['token_miss_num'] >= 5 && $type == 2) {
            return false;
        }

        return true;
    }

    /**
     * 获取登陆错误次数信息
     * @author 2017-10-26T11:03:03+0800
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getMissInfo($userId){
    	$startTime         = strtotime(date('Ymd'));
		$endTime           = strtotime(date('Ymd', strtotime('+1 days')));
		$where['uid']      = $userId;
		$where['add_time'] = array(array('EGT', $startTime),array('LT', $endTime), 'AND');
		$rs                = $this->where($where)->find();

		return $rs;
    }

    /**
     * 自增错误次数
     * @author lirunqing 2017-10-26T10:36:15+0800
     * @param  int  $userId 用户id
     * @param  integer $type   1表示密码错误次数，2表示口令错误次数
     * @return bool
     */
    public function incNum($userId, $type=1){

		$startTime         = strtotime(date('Ymd'));
		$endTime           = strtotime(date('Ymd', strtotime('+1 days')));
		$where['uid']      = $userId;
		$where['add_time'] = array(array('EGT', $startTime),array('LT', $endTime), 'AND');
		$rs                = $this->where($where)->find();

		if (empty($rs)) {
			$addData = array(
				'uid'            => $userId,
				'pass_miss_num'  => 0,
				'token_miss_num' => 0,
				'add_time'       => strtotime(date('Ymd')),
			);
			$this->add($addData);
		}

        $saveData = array();
		$res      = false;

        if ($type == 1) {
            $res = $this->where($where)->setInc('pass_miss_num');
        }
        if ($type == 2) {
            $res = $this->where($where)->setInc('token_miss_num');
        }
        
        return $res;
    }

    /**
     * 更新用户登陆密码或口令错误次数
     * @author lirunqing 2017-10-25T22:11:13+0800
     * @param  int  $userId 用户id
     * @param  integer $type   1表示密码错误次数，2表示口令错误次数
     * @return bool
     */
    public function updateLoginMiss($userId, $type=1){

        $startTime         = strtotime(date('Ymd'));
        $endTime           = strtotime(date('Ymd', strtotime('+1 days')));
        $where['uid']      = $userId;
        $where['add_time'] = array(array('EGT', $startTime),array('LT', $endTime), 'AND');

		$saveData = array();
		$res      = false;

        if ($type == 1) {
            $saveData['pass_miss_num'] = 0;
            $res = $this->where($where)->save($saveData);
        }
        if ($type == 2) {
            $saveData['token_miss_num'] = 0;
            $res = $this->where($where)->save($saveData);
        }
        
        return $res;
    }
}