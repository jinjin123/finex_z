<?php
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Logics\PublicFunctionController;
use Common\Api\RedisCluster;
use Home\Logics\CtoCTransactionLogicsController;
use Home\Logics\CheckAllCanUseParam;
use Common\Api\redisKeyNameLibrary;
use SwooleCommand\Logics\TradePush;
/**
 * @author 建强  2019年3月21日 
 * @desc   c2c买卖接口  
 */
class OrderBusinessController extends CommonController{
    /**
     * @var $jsonToArr          array  接口参数
     * @var $cToCTransLogicsObj object c2c验证逻辑 
     * @var $redis              object redis  
     * @var $msgArr             array  接口返回数据包                  
    */
	public    $jsonToArr;
	private   $cToCTransLogicsObj;
	private   $redis;
    public  $msgArr = [
        'code' => 200, 'msg'  => '','data' => []
    ];
    
    //redis锁过期时间 ,锁key前缀
    const  LOCK_EXPIRE =  3; 
    const  LOCK_PREFIX = 'lock:order_id';
    
	public function __construct(){
		parent::__construct();
        $this->cToCTransLogicsObj= new CtoCTransactionLogicsController();
        $this->redis=RedisCluster::getInstance();
		if(IS_AJAX){
		   $this->jsonToArr=$_REQUEST;
		   foreach($this->jsonToArr as $k=>$v) {
		       $this->jsonToArr[$k]=strip_tags(trim($v));
		   }		      
		}
	}
   /**
	 * @method 防止用户二次提交
	 * @param  int $uid
	*/
	protected function PreventSubmitSecond($uid){
	       $key      = redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL.$this->jsonToArr['id'].$uid;
	       $secodtime= $this->redis->get($key);
	       if(!empty($secodtime)){
	           $this->msgArr['msg']=L('_QWCFCZ_');
	           $this->msgArr['code']=601;
	       }
	       return $this->msgArr;
	 }
	 /**
	  * @method  检验基础提交数据 包括用户资金密码 24h内是否改动 用户实名认证
	  * @param   int  用户uid  $uid  
	  */
	 protected function checkBasicParamIncludeTwdUserRealAndBank($uid){
	     if($uid<=0 || !is_array($this->jsonToArr) || !isset($this->jsonToArr['id'])  ||
	        !isset($this->jsonToArr['num']) || !isset($this->jsonToArr['money'])      ||
	        !isset($this->jsonToArr['user_bank_id']) || !isset($this->jsonToArr['trade_pass']) ||
	        !in_array($this->jsonToArr['type'], array(1,2)) || !isset($this->jsonToArr['currency_type'])
	      ){
	         $this->msgArr['msg']=L('_CSCW_');
	         $this->msgArr['code']=602;
	         return $this->msgArr;
	      }   
	      
	      if(empty($this->jsonToArr['num'])){
	           $this->msgArr['msg'] = L('_SLBNWK_');
	           $this->msgArr['code']=603;
	           return $this->msgArr;
	      }
	       
	      if(empty($this->jsonToArr['money'])){
	           $this->msgArr['msg'] = L('_JEBNWK_');
	           $this->msgArr['code']=604;
	           return $this->msgArr;
	      }
	      //数量为数字
	      if(!is_numeric($this->jsonToArr['num'])){
	          $this->msgArr['msg'] = L('_SLBNWFS_');
	          $this->msgArr['code']=605;
	          return $this->msgArr;
	      }
	       
	      //金额为数字
	      if(!is_numeric($this->jsonToArr['money'])){
	           $this->msgArr['msg'] = L('_JEBNWFS_');
	           $this->msgArr['code']=606;
	           return $this->msgArr;
	      }
	       
	      //正数数量
	      if($this->jsonToArr['num']<=0){
	          $this->msgArr['msg'] = L('_SLBNWFS_');
	          $this->msgArr['code']=607;
	          return $this->msgArr;
	      }
	       
	      //正数金额
	      if($this->jsonToArr['money']<=0){
	          $this->msgArr['msg'] = L('_JEBNWFS_');
	          $this->msgArr['code']=608;
	          return $this->msgArr;
	      }
	       
	      // 交易密码不能为空
	      if(empty($this->jsonToArr['trade_pass'])){
	          $this->msgArr['msg'] = L('_JYMMBNWK_');
	          $this->msgArr['code']=609;
	          return $this->msgArr;
	      }
	      
	      if(empty($this->jsonToArr['currency_type']) || !is_numeric($this->jsonToArr['currency_type'])){
	          $this->msgArr['msg']=L('_BZLXCW_');
	          $this->msgArr['code']=610;
	          return $this->msgArr;
	      }
	      
	      //交易密码24h内
	      $userObj=new CheckAllCanUseParam();
	      $ret=$userObj->checkUserIsTradePwd($uid);
	      if($ret['code']!=200){
	          $this->msgArr['msg']=$ret['msg'];
	          $this->msgArr['code']=611;
	          return $this->msgArr;
	      }
	      //实名认证
	      $ret=$userObj->checkUserRealStatus($uid);
	      if($ret['code']!=200){
	          $this->msgArr['msg']=$ret['msg'];
	          $this->msgArr['code']=612;
	          return $this->msgArr;
	      }

	      // 护照过期
		$relt = $userObj->checkUserRealIsExpire($uid);
		if (empty($relt) || $relt['code'] != 200) {
			$this->msgArr['msg']=$relt['msg'];
	        $this->msgArr['code']=$relt['code'];
	        return $this->msgArr;
		}


	      //失信次数内 24小时禁止交易
	      $duringTime=$this->cToCTransLogicsObj->checkUserIsDuringTime($uid);
	      if($duringTime['code']!=200){
	          $this->msgArr['msg']=$ret['msg'];
	          $this->msgArr['code']=613;
	          return $this->msgArr;
	      }
	      //验证交易密码正确
	      $publicFunctionObj = new PublicFunctionController();
	      $tradePwdRes = $publicFunctionObj->checkUserTradePwdMissNum($uid, $this->jsonToArr['trade_pass']);
		  if($tradePwdRes['code'] != 200){
			  $this->msgArr['msg']  = $tradePwdRes['msg'];
			  $this->msgArr['code'] = 615;
			  return $this->msgArr;
		  }
	      //通过验证返回
	      return $this->msgArr;
	 }
	 /**
	  * @method 效验订单业务条件参数
	  * @param  int  $uid
	 */
	 protected function checkBuinessConditions($uid){
	     //检测（主订单）是完成 ，剩余量是否足够
	     $orderRes= $this->cToCTransLogicsObj->checkOrderIsFinshed($this->jsonToArr['id'],$this->jsonToArr['num'],$this->jsonToArr['money'],$uid);
	     if($orderRes['code']!=200){
	          $this->msgArr['code']=616;
	          $this->msgArr['msg']=$orderRes['msg'];
	         return $this->msgArr;
	     }
	     //买卖业务符合条件   例买匹配卖 1=2
         if($orderRes['data']['type']==$this->jsonToArr['type']){
             $this->msgArr['code']=617;
             $this->msgArr['msg']=L('_CSCW_');  
             return $this->msgArr;
         }
         //币种类型判断 
         if($orderRes['data']['currency_type']!=$this->jsonToArr['currency_type']){
         	$this->msgArr['code']=620;
         	$this->msgArr['msg']=L('_CSCW_');
         	return $this->msgArr;
         }     
	     //如果存在手续费   用户买入 （检测主订单卖出手续费字段）
	     if($orderRes['data']['fee']>0  && $this->jsonToArr['type']==1 && $orderRes['data']['leave_fee']<=0){
	         $this->msgArr['code']=618;
	         $this->msgArr['msg']=L('_SXFYC_');
	         return $this->msgArr;
	     }
	     //效验银行卡  买入主订单的uid， 卖出登录uid  (针对的卖单人的银行卡)
	     $userId = ($this->jsonToArr['type'] == 1)?$orderRes['data']['uid']:$uid;
	     if(!checkUserBindBank($userId,$orderRes['data']['om']) && $orderRes['data']['type'] == 2 ){   
	         $this->msgArr['code']=619;
	         $this->msgArr['msg']=L('_GJYQWBDYHK_');
	         return $this->msgArr;
	     }
	     //检验银行卡是否存在
	     $ret= $this->cToCTransLogicsObj->checkUserBankIsHave($userId, $this->jsonToArr['user_bank_id']);
	     if($ret['code']!=200){ 
	         $this->msgArr['code']=$ret['code'];
	         $this->msgArr['msg']=$ret['msg'];
	         return $this->msgArr;
	     }
	     // 检测用户资金是否足够卖出    当前用户的uid
	     if ($this->jsonToArr['type'] == 2){
	         $isEnoughMoney = $this->cToCTransLogicsObj->checkUserMoneyIsEnough($orderRes['data']['currency_type'], $uid, $this->jsonToArr['num']);
	         if(empty($isEnoughMoney) || $isEnoughMoney['code'] != 200){
	             $this->msgArr['code'] = $isEnoughMoney['code'];
	             $this->msgArr['msg']  = L('_NDZJBZ_');
	             return $this->msgArr;
	         }
	     }
	     // 检测用户交易总金额是否达到最小配置
	     $isMinMoney = $this->cToCTransLogicsObj->checkIsMinMoney($this->jsonToArr['currency_type'], $this->jsonToArr['money']);
	     if(empty($isMinMoney) ||$isMinMoney['code'] != 200){
	         $this->msgArr['code'] = $isMinMoney['code'];
	         $this->msgArr['msg'] = L('_ZJEXYGDJE_')."$".$isMinMoney['data']['min_trade_money'];
	         return $this->msgArr;
	     }
	     //通过返回数据
	     $this->msgArr['data']=$orderRes['data'];  //订单数据
	     return $this->msgArr;
	 }
	 /**
	  * @method 买卖接口防止撤销 撤销防止买卖
	 */
	 protected function revokeOrPervent(){
	     //正在撤销
	     $key=redisKeyNameLibrary::PC_C2C_TRADE_REVOKED_CANNOT_OPERAT.$this->jsonToArr['id'];
	     $revokOrdering   =$this->redis->get($key);
	     if($revokOrdering){
	         $this->msgArr['code']=621;
	         $this->msgArr['msg']= L('_GDDYBGMQNGMQTDD_');
	         return $this->msgArr;
	     }
	     //正在购买
	     $orderBuying=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$this->jsonToArr['id'];
	     $this->redis->setex($orderBuying, 5, 1);
	     //成功返回
	     return $this->msgArr;
	 }
 	 /**
	   * @method 设置redis key值  防止二次重复提交二次脏数据
	  */
	 protected function setDispatchKey($uid){  
	     //防止提交
	     $key=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL.$this->jsonToArr['id'].$uid;
	     //进行买卖  防止主订单撤销
	     $mainOderRevok=redisKeyNameLibrary::PC_C2C_TRADE_BUY_SELL_REVOKED.$this->jsonToArr['id'];
	     $this->redis->setex($key, 5, 1);
	     $this->redis->setex($mainOderRevok, 5, 1);
	 }
	 /**
	  * @method 买卖接口订单
	  * @author 建强  2018年2月27日18:04:21
	  * @param  int     $id                订单ID 
	  * @param  int     $num               数量
	  * @param  float   $money             金额
	  * @param  int     $user_bank_id      用户银行卡id
	  * @param  string  $trade_pass        交易密码
	  * @param  int     $type              买卖类型 
	  * @param  int     $currecny_type     币种类型
	  */
	 public function proccessProgram(){
	 	 $uid = getUserId();  
	 	 $lock_key = self::LOCK_PREFIX.$this->jsonToArr['id'];
	     //基础参数效验
	     $ret = $this->checkBasicParamIncludeTwdUserRealAndBank($uid);	     
	     if($ret['code']!=200) {
	         if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
	         $this->ajaxReturn($ret);
	     }
	       
	     // 检测网站维护状态
         $isMaintain = $this->cToCTransLogicsObj->checkWebMaintain(2);
         if($isMaintain['code'] != 200){
        	$maintainData = [
        		'code' => $isMaintain['code'],
        		'msg'  => $isMaintain['msg']
        	];
        	
        	if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
        	$this->ajaxReturn($maintainData);
         } 
         
	     //二次提交数据
	     $ret = $this->PreventSubmitSecond($uid);
	     if($ret['code']!=200){
	         if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
	         $this->ajaxReturn($ret);
	     }
	     
	     //检测买卖单订单业务条件
	     $orderRes = $this->checkBuinessConditions($uid);    
	     if($orderRes['code']!=200){
	         if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
	         unset($orderRes['data']);
	         $this->ajaxReturn($orderRes);
	     }
	     
	     //撤销订单  （无法购买 ）  买入时  （无法撤销操作）
	     $ret = $this->revokeOrPervent();
	     if($ret['code']!=200){
	         if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
	         $this->ajaxReturn($ret);
	     }
	     //防止撤销
	     $this->setDispatchKey($uid);
         M()->startTrans();
         $proRet  = $this->cToCTransLogicsObj->proccessBuyingOrder($this->jsonToArr,$uid,$orderRes['data']);
         if($proRet ['code']!=200){
             if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
             $res = ['code'=>$proRet['code'],'msg'=>$proRet['msg']];
             $this->ajaxReturn($res);
         }
         if(in_array(false, $proRet['data']['addArr'])){
             if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
             M()->rollback();
             $res['code']='676';
             $res['msg']=($this->jsonToArr['type'] == 2)?L('_MCSBXTFM_'):L('_MRSBXTFM_');
             $this->ajaxReturn($res);
         }
         
         M()->commit();
         //累计卖出总数
         $incr_key  = redisKeyNameLibrary::PC_C2C_BUY_SELL_NUM.$this->jsonToArr['id'];
         $this->redis->incrbyfloat($incr_key,$this->jsonToArr['num']);
         if($this->redis->ttl($lock_key)) $this->redis->del($lock_key);
         
         //通知买家进行付款 异步通知
         if($this->jsonToArr['type'] == 2){
             $pushObj=new TradePush();
             $pushObj->pushExec($proRet['data']['order_id'],300000,'c2c');  //第二个毫秒时间单位5min后
         }
         //返回值
         $res['code'] = 200;
         $res['msg']  =($this->jsonToArr['type'] == 2)?L('_MCCG_'):L('_MRCG_');
         $res['data']['order_id']  = $proRet['data']['order_id'];
         $res['data']['leave_num'] = $proRet['data']['leave_num'];
	     $this->ajaxReturn($res);
	 }
	 /**
	  * @author 建强 2019年3月27日 下午2:09:55
	  * @method redis 锁机制 ，业务代码看 proccessProgram(该接口会直接die并输出)
	  * @return string  json 
	  */
	 public function BuyingOrderApi(){
	     $id         = $this->jsonToArr['id'];
	     //检测是否是刷单数据
	     if(stripos($id, 'S')!==false){
	         $this->msgArr['msg']=L('_GDDYJWCHCX_');
	         $this->msgArr['code']=614;
	         $this->ajaxReturn($this->msgArr);
	     }
	     
	     if(!is_numeric($id) || $id<1) $this->ajaxReturn(['code'=>10001,'msg'=>L('_CSCW_')]);
	     
	     $order_key  = redisKeyNameLibrary::PC_C2C_MAIN_ORDER_NUM.$id;
	     $incr_key   = redisKeyNameLibrary::PC_C2C_BUY_SELL_NUM.$id;
	     $lock_key   = self::LOCK_PREFIX.$id;       
	     $lock_status= $this->redis->get($lock_key);
	     $total_num  = $this->redis->get($order_key); 
	     if($lock_status || empty($total_num)) $this->ajaxReturn(['code'=>10002,'msg'=>L('_GDDBKCZ_')]);
	     while(true){
	         $lockValue     = time() + self::LOCK_EXPIRE;
	         $lock          = $this->redis->setnx($lock_key, $lockValue);
	         
	         if(!empty($lock) || ($this->redis->get($lock_key) < time() && $this->redis->getSet($lock_key, $lockValue) < time())){
	             $this->redis->expire($lock_key,self::LOCK_EXPIRE);
	             $this->proccessProgram();
	         }else{
	             usleep(500000);  
	             $incr_num = $this->redis->get($incr_key);
	             if($incr_num >=$total_num) $this->ajaxReturn(['code'=>10005,'msg'=>L('_GDDYJWCHCX_')]);
	         }
	     }
	 }
}