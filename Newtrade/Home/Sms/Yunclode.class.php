<?php
namespace Home\Sms;
use  Common\Api\RedisCluster;
/**
 * 前台短信发送类
 * @author 宋建强 2017年9月25日 17:29
 */
class Yunclode
{      
	   //短信发送秘钥
	   const  APIKEY = "f666e3d9de44523a87fde4a593e64f32";
	   //模板语言类型
	   public  $smsTypeArr=[1,2,3,4,5]; 
	   public $redis=NULL;
	   
	   public function  __construct(){
	   	  $this->redis = RedisCluster::getInstance();
	   }
	   private function set_redis($key,$value,$expire=120){
	   	    if($key && $value){   
	   	    	 return  $this->redis->setex($key, $expire, $value);
	   	    }
	   	    return false;
	   }
	   private function get_redis($key=''){
		   	if(!empty($key)){  
		   		return $this->redis->get($key);
		   	}
		   	return NULL;
	   }
	/**
    * 短信接口发送
    * @param1 $uid   用户id
    * @param2 $om    国家码 例"+86"
    * @param3 $phone 电话号码
    * @param4 $scene 场景
    * @param5 $msgType  短信发送的类型   
    * @param6 $username uid对应的用户名 
    * @return int      
    */
	 public function ApiSendPhoneCode($uid, $OM, $phone, $scene='LOGIN', $msgType, $username='new'){    
	 	 //类型错误
		 if(!in_array($msgType,$this->smsTypeArr)){ 
		 	 return  400;	
		 }
	 	 //首先判断是不是2min分请求
	 	 $key=$scene.'_'.$uid.'_'.$phone;
	 	 if($this->get_redis($key)){  
	 	      return 413;  
	 	 }
	 	 $OM=!empty($OM)?$OM:'+86';
	 	 $code=$this->getRandomCheckCode();
	 	 $msg=$this->getSmsMsg($OM,$code,$msgType);
	 	 $mobile = $OM.$phone;
	 	 //发送处理
	 	 $sendStatus=$this->curlApiSms($msg, $mobile);
	 	 if(empty($sendStatus)){ 
	 	   	 return 402; 
	 	 }
	 	 $sendStatus=json_decode($sendStatus,true);
	 	 if(0 == $sendStatus['code']){   
	 	   	 //添加如果该手机号发送成功之后  同一场景下的短信验证码两分钟不能请求   同时存储发发送$code
	 	   	 $this->set_redis($key, $code);
	 	   	 $this->setSmsLog($uid,$username,$phone,$msgType);
	 	 }
	 	 return $sendStatus['code'];
	 }
	 /**
	  * @method 短信发送记录日志
	  * @param int $uid  
	  * @param string $username 
	  * @return bool 
	 */ 
	 public function setSmsLog($uid,$username,$phone,$type)
	 {
	      $data=[
	      	 	'uid'=>$uid,
	      	 	'username'=>$username,
	      		'phonenum'=>$phone,
	      		'type'=>$type,
	      		'add_time'=>time(),
	      ];
	      M('SmsLog')->add($data); 
	 }	 
	 /**
	  * @method curl 发送接口
	  * @param1 string msg    消息
	  * @param2 string mobile 手机号
	  * @return bool |json
	 */
     private  function curlApiSms($text,$mobile)
     {    
     	  $data=[
     			'text'       =>$text,
     			'apikey'     => self::APIKEY,
     			'mobile'     =>$mobile
     	  ];
     	  $ch = curl_init();
     	  //form表单模式
     	  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8')); // 设置验证方式
     	  curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间
     	  curl_setopt($ch, CURLOPT_POST, 1);     // 设置通信方式
     	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
     	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
     	  curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
     	  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
     	  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
     	  $send_result = curl_exec($ch);
     	  curl_close($ch);
     	  
     	  return $send_result;
     }	
     /**
      * @param string $OM
      * @param string $code
      * @param int  $smsType
      * @return string 相应的模板文本
      */
     public  function  getSmsMsg($OM,$code,$smsType)
     {
	     $arr=[
	     		//注册
		     	1=>[
		     	   
			     	't_+86' => "【BTCS】验证码为：$code"."，您正在注册账号，请在2分钟内完成验证",
			     	't_+852'=> "【BTCS】驗證碼為：$code"."，您正在注册帳號，請在2分鐘內完成驗證",
			     	't_+886'=> "【BTCS】驗證碼為：$code"."，您正在注册帳號，請在2分鐘內完成驗證",
			     	't_'    => "【BTCS】Register Verification code: Your verification code is $code".", and remains valid for 2 minutes enter valid.",
		     	  ],
	     		//转出
		     	2=>[
		     		't_+86' => "【BTCS】验证码为：$code"."，您正在申请转出服务，请在2分钟内完成验证",
		     		't_+852'=> "【BTCS】驗證碼為：$code"."，您正在申請轉出服務，請在2分鐘內完成驗證",
		     		't_+886'=> "【BTCS】驗證碼為：$code"."，您正在申請轉出服務，請在2分鐘內完成驗證",
		     		't_'    => "【BTCS】Withdrawal Verification code: Your verification code is $code".", and remains valid for 2 minutes enter valid.",
		     	],
	     		//解绑
		        3=>[
		        	't_+86' => "【BTCS】验证码为：$code"."，您正在申请解绑服务，请在2分钟内完成验证",
		        	't_+852'=> "【BTCS】驗證碼為：$code"."，您正在申請解綁服務，請在2分鐘內完成驗證",
		        	't_+886'=> "【BTCS】驗證碼為：$code"."，您正在申請解綁服務，請在2分鐘內完成驗證",
		        	't_'    => "【BTCS】Unbind Verification code: Your verification code is $code".", and remains valid for 2 minutes enter valid.",
		        ],
	     		//绑定
	     		4=>[
	     			't_+86' => "【BTCS】验证码为：$code"."，您正在申请绑定服务，请在2分钟内完成验证",
	     			't_+852'=> "【BTCS】驗證碼為：$code"."，您正在申請綁定服務，請在2分鐘內完成驗證",
	     			't_+886'=> "【BTCS】驗證碼為：$code"."，您正在申請綁定服務，請在2分鐘內完成驗證",
	     			't_'    => "【BTCS】Bind Verification code: Your verification code is $code".", and remains valid for 2 minutes enter valid.",
	     		],
	     		//App登录
	     		5=>[
	     			't_+86' => "【BTCS】验证码为：$code"."，您正在申请App登录，请在2分钟内完成验证",
	     			't_+852'=> "【BTCS】驗證碼為：$code"."，您正在申請App登陸，請在2分鐘內完成驗證",
	     			't_+886'=> "【BTCS】驗證碼為：$code"."，您正在申請App登陸，請在2分鐘內完成驗證",
	     			't_'    => "【BTCS】App login code: Your login code is $code".", and remains valid for 2 minutes enter valid",
	     		],
		  ];
	      return  $arr[$smsType]['t_'.$OM]?$arr[$smsType]['t_'.$OM]:$arr[$smsType]['t_'];  
     }
   
     /**
      * @method 生成随机验证码数验证码 6位数字 
      * @return string
      */
     protected function getRandomCheckCode(){   
	     $str="abcdefghijkmnpqrstuvwxyz";
		 $chars = '23456789';
		 mt_srand((double)microtime()*1000000*getmypid());
		 for($i=1;$i<=4;$i++){
		     $CheckCode.=substr($str,(mt_rand()%strlen($str)),1);
		     $tmp.=substr($chars,(mt_rand()%strlen($chars)),1);
		 }
		 $rate=rand(1,10);
		 if($rate>5){
			 $string=substr($CheckCode, 0,2).$tmp;
		 }
		 else{
			$string=substr($CheckCode, 0,3).substr($tmp, 0,3);
		 }
         return str_shuffle($string);
     }
}
