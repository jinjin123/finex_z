<?php
namespace Home\Logics;
use Common\Api\RedisCluster;  
/**
 * @author 效验通用的数据参数
 * @author 建强   2018年2月27日16:24:11
 */
class CheckAllCanUseParam 
{  
	public $result=[
		  'msg'  => '',
		  'code' => 200,
		  'data' => array()
	];
   /**
	 * 检测用户是否设置交易密码  或者是否修改过
	 * @author 建强  2018年2月27日16:32:13
	 * @return array
	 */
	public function checkUserIsTradePwd($uid){
	    $this->checkUserIsTradePwdForExt($uid);
        if($this->result['code'] == 611){
            $this->result['msg'] = L('_ZHUYI_').'<a href="/PersonalCenter/index?costpwd" >'.L('_SZZJMM_').'</a>'.L('_HCNJMMMCZ_' );
            return $this->result;
        }
        return $this->result;
	}

    /**
     * 交易密码校验公共方法，方便其他地方调用
     * 刘富国
     * 20190823
     * @param $uid
     * @return array
     */
    public function checkUserIsTradePwdForExt($uid){
        $isTradePwd   = M("User")->field('trade_pwd')->where(['uid'=>$uid])->find();
        if(empty($isTradePwd['trade_pwd']))
        {
            $this->result['code']=611;
            $this->result['msg'] = L('_SZZJMM_').L('_HCNJMMMCZ_' );
            return $this->result;
        }

        $redis  = RedisCluster::getInstance();
        $isChangeTradePwd = $redis->get('setNewTradePassword'.$uid);
        //检测用户是否更改过交易密码，如果更改过，24H不能交易
        if (!empty($isChangeTradePwd))
        {
            $this->result['code']=612;
            $this->result['msg'] = L('_ZJYHXGJYMMYTBNJY_');
            return $this->result;
        }
        return $this->result;
    }

	/**
	 * 检测护照是否过期
	 * @author lirunqing 2019-06-10T12:23:16+0800
	 * @param  int $userId 用户id
	 * @return array
	 */
	public function checkUserRealIsExpire($userId){
	    $this->checkUserRealIsExpireForExt($userId);
		$personRealUrl = U('PersonalCenter/index');
		if ($this->result['code']== 613) {
			$this->result['msg'] = L('_ZYSMQBNJXJYCZQW_')."<a href='{$personRealUrl}' >".L('_SMRZ_')."</a>";
			return $this->result;
		}
		// 护照过期
		if ($this->result['code'] == 614) {
			$this->result['msg'] = L('_ZJGQPC_')."<a href='{$personRealUrl}' >".L('_SMRZ_')."</a>";
			return $this->result;
		}
		return $this->result;
	}

    /**
     * 检测护照是否过期公共方法，方便其他地方调用
     * @author 刘富国
     * @param  int $userId 用户id
     * @return array
     * 20190823
     */
    public function checkUserRealIsExpireForExt($userId){
        $realInfo      = M('UserReal')->where(['uid' => $userId])->find();
        if (empty($realInfo)) {
            $this->result['code']= 613;
            $this->result['msg'] = L('_ZYSMQBNJXJYCZQW_');
            return $this->result;
        }
        // 护照过期
        if ($realInfo['expire_status'] == 3) {
            $this->result['code'] = 614;
            $this->result['msg'] = L('_ZJGQPC_');
            return $this->result;
        }
        return $this->result;
    }
	
	/**
	 * 判断否实名认证   '0等待认证 1通过实名认证  -1没通过'
	 * @建强  2018年2月27日14:36:40
	 */
	function checkUserRealStatus($uid)
	{
	    $this->checkUserRealStatusExt($uid);
		$personRealUrl=U('PersonalCenter/index');
		if($this->result['code']==601)
		{
			$this->result['msg'] = L('_ZYSMQBNJXJYCZQW_')."<a href='{$personRealUrl}' >".L('_SMRZ_')."</a>";
		}
		if($this->result['code']==602)
		{
			$this->result['msg']=L('_SMRZSHZQDD_'); //待审核
		}
		if($this->result['code']==603)
		{
			$this->result['msg'] = L('_ZYSMQBNJXJYCZQW_')."<a href='{$personRealUrl}' >".L('_SMRZ_')."</a>";
		}
		return $this->result;
	}


	 /**
	  * 验证实名认证公共方法，，方便其他地方调用
	  * @param int  $uid
      * 刘富国
      * 20190823
	 */
	 public function checkUserRealStatusExt($uid)
	 {
	 	 $status = M('UserReal')->where(['uid'=>$uid])->getField('status');
	 	 if(!isset($status))
	 	 {
	 		$this->result['code']=601;
	 		$this->result['msg'] = L('_WSMRZ_'); //未进行实名认证
	 	 }
	 	 if($status=='0')
	 	 {
	 		$this->result['code']=602;
	 		$this->result['msg']=L('_SMRZSHZQDD_'); //待审核
	 	 }
	 	 if($status=='-1')
	 	 {
	 		$this->result['code']=603;
	 		$this->result['msg'] = L('_SMRZWTGSH_');
	 	 }
	 	 return $this->result;
	 }

    /**
     * 通用用户校验：实名认证，證件是否過期，资金密码
     * 刘富国
     * @param $uid
     * @return array
     * 20190823
     */
	 function checkUserPower($uid){
         //检测实名认证
         $this->checkUserRealStatusExt($uid);
         if( $this->result['code'] <> 200 )  return $this->result;
         //證件是否過期
         $this->checkUserRealIsExpireForExt($uid);
         if( $this->result['code'] <> 200 )   return $this->result;
         //检测资金密码
         $this->checkUserIsTradePwdForExt($uid);
         if( $this->result['code'] <> 200 ) return $this->result;
         return $this->result;
     }
}

