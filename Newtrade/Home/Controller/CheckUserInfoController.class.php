<?php
/**
 * 检测用户个人信息
 * @author lirunqing 2017-12-4 10:05:57
 */
namespace Home\Controller;
use Think\Controller;
use Home\Logics\CommonController;
use Home\Logics\OffTradingLogicsController;
use Home\Logics\PublicFunctionController;
use Common\Api\RedisIndex;
use Common\Api\RedisCluster;
use Home\Logics\CheckAllCanUseParam;

class CheckUserInfoController extends CommonController {

	/**
	 * 检测用户是否设置交易密码
	 * @author lirunqing 2017-11-07T17:47:51+0800
	 * @return json
	 */
	public function checkUserIsTradePwd(){

		$userId       = getUserId();
		$where['uid'] = $userId;
		$isTradePwd   = M("User")->field('trade_pwd')->where($where)->find();

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);
		if (empty($isTradePwd['trade_pwd'])) {
			
			$res['msg'] = L('_ZHUYI_').'<a href="/PersonalCenter/index?costpwd" style="color:#00dcda;">'.L('_SZZJMM_').'</a>'.L('_HCNJMMMCZ_' );
			$this->ajaxReturn($res);
		}

		// 检测用户是否更改过交易密码，如果更改过，24H不能交易
		$this->checkTradePwdIsChange();

		$res['code'] = 200;
		$res['msg']  = L('_YSZJYMM_');
		$this->ajaxReturn($res);
	}

	/**
	 * 用户已读新手教程
	 * @author lirunqing 2017-12-19T15:36:44+0800
	 * @return json
	 */
	public function checkUserIsTour(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		$tourType    = I('post.tourType');
		$tourType    = !empty($tourType) ? $tourType : 1;
		$tourTypeArr = array(
		    1 => 'is_p2p_tour',
		    2 => 'is_currency_tour',
		    3 => 'is_c2c_tour'
		);// 1是线下交易，2是币币交易
		$userId      = getUserId();
		$where       = array(
			'uid' => $userId
		);
		M('User')->where($where)->setField($tourTypeArr[$tourType], 1);

		$res['code'] = 200;
		$res['msg']  = L('_CHENGGONG_');
		$this->ajaxReturn($res);
	}

	/**
	 * 检测用户是否超时
	 * @author lirunqing 2017-11-14T16:41:28+0800
	 * @return json
	 */
	public function checkUserIsOverTime(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		$userId = getUserId();
		$publicFunctionObj = new PublicFunctionController();
		$userInfo            = M('User')->where(array('uid'=>$userId))->find(); // 获取用户个人信息
		$offTradingLogicsObj = new OffTradingLogicsController();

		$overRes = $publicFunctionObj->checkOvertime($userInfo['overtime_num'], $userInfo['overtime_time']); // 检测用户是否被锁定
		if ($overRes['code'] != 200) {
			$res['msg'] = $overRes['msg'];
			$this->ajaxReturn($res);
		}

		$res['code'] = 200;
		$res['msg']  = L('_CHENGGONG_');
		$this->ajaxReturn($res);
	}

	/**
	 * 检测用户是否更改过交易密码，如果更改过，24H不能交易
	 * @author 2017-11-09T18:59:12+0800
	 * @return [type] [description]
	 */
	public function checkTradePwdIsChange(){
		
		// $redisObj = new RedisCluster();
		$redis  = RedisCluster::getInstance();
		$userId = getUserId();
		$isChangeTradePwd = $redis->get('setNewTradePassword'.$userId);

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array()
		);

		//  检测用户是否更改过交易密码，如果更改过，24H不能交易
		if (!empty($isChangeTradePwd)) {
			$res['msg'] = L('_ZJYHXGJYMMYTBNJY_');
			$this->ajaxReturn($res);
		}
	}

	/**
	 * 检测用户是否实名认证
	 * @author lirunqing 2017-10-31T15:13:59+0800
	 * @return boolean [description]
	 */
	public function isUserRealName(){

		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array(),
		);

		$userId = getUserId();
		// 检测用户是否实名认证
		$realRes = checkUserReal($userId);
	    if ($realRes < 0) {
	    	$personRealUrl=U('PersonalCenter/index');
	    	$res['msg'] = L('_ZYSMQBNJXJYCZQW_')."<a href='{$personRealUrl}' style='color:#00dcda;'>".L('_SMRZ_')."</a>";
	    	$this->ajaxReturn($res);
	    }
	    if($realRes==0 )
	    {   
	    	//等待审核
	    	//$res['msg'] =L('_SMRZSHZQDD_'); //待审核;
	    	$personRealUrl=U('PersonalCenter/index');
	    	$res['msg'] = L('_ZYSMQBNJXJYCZQW_')."<a href='{$personRealUrl}' style='color:#00dcda;'>".L('_SMRZ_')."</a>"; //待审核;
	    	$this->ajaxReturn($res);
	    }

	    // 检测护照是否过期
		$checkAllCanUseParam = new CheckAllCanUseParam();
		$checkRes = $checkAllCanUseParam->checkUserRealIsExpire($userId);
		if ($checkRes['code'] != 200) {
			$res['msg'] = $checkRes['msg'];
			$res['code'] = $checkRes['code'];
			$this->ajaxReturn($res);
		}

	    $res['code'] = 200;
	    $res['msg']  = L('_YSMRZ_');
	    $this->ajaxReturn($res);
	}

	/**
	 * 检测用户是否绑定银行卡
	 * @author lirunqing 2018-03-02T12:17:49+0800
	 * @return boolean [description]
	 */
	public function isUserBindBank(){
		$res = array(
			'msg'  => '',
			'code' => 201,
			'data' => array(),
		);

		$userId = getUserId();
		// 检测用户是否绑定银行卡
		$realRes = checkUserBindBank($userId);

		if (empty($realRes)) {
	    	$res['msg'] = L('_ZWBDYHK_');
	    	$this->ajaxReturn($res);
	    }

	    $res['code'] = 200;
	    $res['msg']  = L('_BDYHKCG_');
	    $this->ajaxReturn($res);
	}
}

