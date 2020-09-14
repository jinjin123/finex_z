<?php
namespace Home\Controller;

use Home\Model\UserModel;
use Home\Logics\PublicFunctionController;
use Common\Model\UserWatchwordModel;
use Common\Api\RedisCluster;
use Home\Logics\CommonController;
use Home\Tools\SceneCode;
use Common\Logic\UserLog;

class PersonalCenterController extends CommonController
{
    public $redis =  null;
    /**
     * {@inheritDoc}
     * @see \Home\Logics\CommonController::_initialize()
     */
    public function _initialize(){
        parent::_initialize(); 
        $this->redis = RedisCluster::getInstance();
    }

    /**
     * 用户个人中心首页
     * @authotr zhanghanwen
     * @time    2017年10月24日17:04:49
     */
    public function index()
    {
        // 获取用户信息
        $uid = getUserId();
        //建强补充实名认证
        $realInfo  = $this->getRealInfo($uid);
        $userModel = new UserModel();
        $userData  = $userModel->getUserInfoForId($uid, 'credit_level,trade_pwd,email,username,phone,reg_time,status,jiaoyi_num,credit_level');
        $isTradeBind = 0;
        if($userData['trade_pwd']) {
            $userData['trade_pwd'] = "**********";
            $isTradeBind = 1;
        }
        $isEmail = 0;
        if($userData['email']) {
            $userData['email'] = substr_replace($userData['email'], '*****', 3, -8);
            $isEmail = 1;
        }
        // 获取token
        $userWatchwordModel = new UserWatchwordModel();
        $token = $userWatchwordModel->getUserSecretNum( $uid );
        if(!$token){
            $token = '';
        }

        $this->assign('realInfo',$realInfo);
        $this->assign('time', time());
        $this->assign('isEmail',$isEmail);
        $this->assign('isTradeBind',$isTradeBind);
        $integralImg = getIntegralAsImg( $userData['credit_level'] );
        $this->assign('integralImg', $integralImg );
        $this->assign('userToken', $token);
        $this->assign('user', $userData);
        $this->display();
    }

   /**
     *实名认证的提示信息 
     *审核通过 | 等待审核    补充多语言 
     *@author 宋建强 2017年11月17号
     *@param  $word  system_reply
    */
    public function  arrRealWord($word)
    {

        $arr= [
    		'0'=>L('_NYTJQNXDDKFSH_'),
    		'1'=>L('_SMRZTG_'),
       ];
        return  $arr[$word];
    }


    /**
     *@method 实名认证的提示信息  *审核通过 | 等待审核    补充多语言
     *@author 宋建强 2017年11月17号
     *@param  $status        int  status字段
     *@param  $system_reply  int  system_reply字段
     *@return string  word
     */
    public function getRealWordByStatus($status,$system_reply = 0){
        $arr= [
            '0'=>L('_NYTJQNXDDKFSH_'),
            '1'=>L('_SMRZTG_'),
        ];
        if(array_key_exists($status, $arr)) return $arr[$status];

        return trim(formatUserRealReply($system_reply),'"');
    }


    /**
    * 获取实名认证信息
    * @author 宋建强 2017年11月17日 
    * @param  [type] $uid [description]
    * @return array
    */
    public  function getRealInfo($uid){
        $ret = M('UserReal')->alias('m')->join('left join __USER__ as n on m.uid=n.uid')
            ->field('m.*,n.tips')->where(['m.uid' => $uid])->find();
        // 请进行实名认证
        if(empty($ret)){
            $ret =[
                'status'  => 200,
                'is_real' => 0,
                'word'    =>L('_WJXSMRZ_')
            ];
            return $ret;
        }
        //tip  存在实名认证   补充另外业务字段
        $ret['is_change'] = 1;     // 可以更新所有信息
        $ret['is_real']   = $ret['status'];
        $ret['word']      = $this->getRealWordByStatus($ret['status'],$ret['system_reply']);

        $nameArr          = explode(',', $ret['card_name']);
        $ret['first_name']= $nameArr[0];
        $ret['last_name'] = $nameArr[1];

        if($ret['expire_status']==3 && $ret['status']==0)return $ret;

        //
        if(in_array($ret['expire_status'],[2,3]) && $ret['status']==-1){
            $ret['is_real']    = -1;
            $ret['is_change']  =  -1;    // 只允许修改指定字段
            $ret['status']     =200;
            return $ret;
        }

        //  护照过期或即将过期
        if ($ret['expire_status'] == 3  || ($ret['expire_time'] - (time()+86400*30)) < 0 && $ret['status'] == 1) {
            $ret['is_real']    = -1;
            $ret['is_change']  =  -1;    // 只允许修改指定字段
            $ret['status']     =200;
            $ret['word']       = ($ret['expire_status'] == 3) ? L('__SMRZHZGQ__') : L('__SMRZHZJJGQ__');
        }
        return $ret;
    }
    
    /**
     *@method 更改实名认证弹框提示状态
     *@author  yangpeng   2017-11-27
     */
    public function changeStatus(){
        $uid = getUserId();
        $user = M('User')->where(['uid'=>$uid])->find();
        if($user['tips']!=2){
            $info = M('User')->where(['uid'=>$uid])->save(['tips'=>2]);
        }
    }
    /**
     * @method 提交实名认证表单
     * @param idname1 姓；idname2 名；bank_name银行开户名；date 过期时间；idnum 证件号
     * @param up_img 正面照；all_img 手持合照
     * @author yangpeng 2017-10-25
     * @return string json
     */
    public function subIdentify(){
      
        if (!IS_POST) $this->ajaxReturn(['info' => L('_XTFMSHCS_'), 'status' => 408]);
        
        $subData    = I('post.');
        $card_type  = 1;//目前只限护照
        $uid        = getUserId();
        
        $this->checkIdentifyData($subData);
        $token_key = 'real_card_submit_'.$uid;            
        $token     =  $this->redis->get($token_key);
       
        if(empty($_FILES['up_img'])){
            $this->ajaxReturn(['info' => L('_QSCZJZZM_'), 'status' => 207]);
        }
        if(empty($_FILES['all_img'])){
            $this->ajaxReturn(['info' => L('_QSCZJZHYZ_'), 'status' => 208]);
        }
        if(!empty($token)){
            $this->ajaxReturn(['info' => L('_QWCFCZ_'), 'status' => 409]);
        }   
        $this -> checkCardNum($subData['idnum']);
       
        $data = []; 
        $res  = $this->upFile(1, 'up_img', $data);
        if ($res['status'] == false)  $this->ajaxReturn(['info' => L('_ZMZSCSB_'),'msg'=>$res['info'], 'status' => 211]);
       
        $data['up_img'] = $res['info'];
        $res_2          = $this->upFile(2, 'all_img', $data);
        
        if ($res_2['status'] == false) $this->ajaxReturn(['info' => L('_FMZSCSB_'), 'status' => 212]);
        $data['all_img'] = $res_2['info'];

        $exp_time  =  newStrToTime($subData['date']);
        if($exp_time < time()) $this->ajaxReturn(['info' => L('_ZJGQ_'), 'status' => 213]);
        
        $pending  =[
             'card_type'     => $card_type,
             'card_name'     => $subData['idname1'].','.$subData['idname2'],
             'card_num'      => trim($subData['idnum']),
             'bank_name'     => trim($subData['bank_name']),
             'add_time'      => time(),
             'expire_time'   => $exp_time,
             'uid'           => $uid,
             'status'        => 0,
             'submit_lock'   => 1,
        ];
        $data = array_merge($pending, $data);
        
        $ret  = $this->getRealList($uid);
        $this->redis->setex($token_key, 3, 1);
        
        if ($ret) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $ret['up_img']);  
            unlink($_SERVER['DOCUMENT_ROOT'] . $ret['all_img']);
            
            $data['id'] = $ret['id'];
            $res = M('UserReal')->save($data);
        } else {
            $res = M('UserReal')->add($data);
        }
        
        if (empty($res)) $this->ajaxReturn(['info' => L('_XTFMSHCS_'), 'status' => 500]);
        $this->ajaxReturn(['info' => L('__TJCGQDSSH__'), 'status' => 200]);
    }
    /**
     * @method 实名认证添加工单记录
     * @author yangpeng 2018年10月12日14:36:30
     * @param $uid
     * @return array
     */
    public function feedbackAdd($uid){
        $res =  ['info'=>'','status'=>200];
        $userInfo = M('User')->where(['uid'=>$uid])->find();
        $subData=[
            'uid'=>$uid,
            'f_pid'=> 4,        //身份认证问题
            'f_cid'=>24,        //其他身份认证问题
            'describe'=>'用戶上傳實名認證資料',
            'add_time'=>time(),
            'status'=>1,        //待分配
            'ex_phone'=>$userInfo['om'].'-'.$userInfo['phone'],
            'source'=>3,        //特殊业务
            'level_id'=>4,
        ];
        $feedbackModel = M('Feedback',C("DB_CONFIG2_PREFIX"),C("DB_CONFIG2"));//跨库连接
        $addRes = $feedbackModel->add($subData);//添加工单
        if(!$addRes) $res['status']=201;
        return $res;
    }
    /**
     *@method 验证是否是否上传已有的护照号
     *@param card_num 护照号
     *@author yangpeng 2017-11-27
     *@return | json
     */
    private function checkCardNum($card_num) {       
        $map=[
            'uid'=>['neq',getUserId()],
            'card_num'=>$card_num,
            ] ;
        $userinfo = M('UserReal')->where($map)->find();
        if($userinfo) $this->ajaxReturn(['info' => L('_GHZHYBRZ_'), 'status' => 209]);
    }
    
    /**
     * @author yangeng  2019年8月23日 下午4:41:47
     * @method 效验实名认证提交数据  
     * @param  $data  array
     * @return | mix 
     */
    private function checkIdentifyData($data) {

        $card_name1 =  trim($data['idname1']);
        $card_name2 =  trim($data['idname2']);
        $bank_name  =  trim($data['bank_name']);
        $date       =  trim($data['date']);
        $card_num   =  trim($data['idnum']);

        if(empty($card_name1))  $this->ajaxReturn(['info'=>L('_QTXZJXM_'),   'status'=>201]);
        if(empty($card_name2))  $this->ajaxReturn(['info'=>L('_QTXZJXM_'),   'status'=>202]);
        if(empty($bank_name))   $this->ajaxReturn(['info'=>L('_YHKKHMBNWK_'),'status'=>203]);
        if(empty($card_num))    $this->ajaxReturn(['info'=>L('_QTXZJHM_'),   'status'=>204]);
        if(empty($date))        $this->ajaxReturn(['info'=>L('_QTXZJGQSJ_'), 'status'=>205]);
        
        //护照姓名也要支出 空格
        if(!regex($card_name1,'psptNameHasBlank') || strlen($card_name1)>18 ){
            $this->ajaxReturn(['info'=>L('_HZXMGSBZQ_'),'status'=>202]);
        }
        
        //护照名称  中间可以为空格
        if(!regex($card_name2,'psptNameHasBlank') || strlen($card_name2)>18 ){
            $this->ajaxReturn(['info'=>L('_HZXMGSBZQ_'),'status'=>203]);
        }
        
        if(!regex($card_num,'passport')){//护照正则验证
            $this->ajaxReturn(['info'=>L('_ZJHGSBZQ_'),'status'=>201]);//证件号格式不正确
        }
        
        if(!regex($bank_name,'cardname1') && !regex($bank_name,'cardname2')){
            $this->ajaxReturn(['info'=>L('_YHKKHMGSBZQ_'),'status'=>201]);
        }
        
    }     

    /**
    * @method 获取用户实名认证信息
    * @param  uid
    * @author yangpeng  2017-10-17
    * @return  array
    */
    private function getRealList($uid)
    {
        $real_list = M('UserReal')->where(['uid' => $uid])->find();
        return $real_list;
    }

    /**
     *  图片上传
     *  @param1 num int 第几张
     *  @param2 string 表单域
     *  @param3 arr  删除的图片路径
     *  return array  res 
     *   
    */
    private function upFile($num, $name, $arr)
    {
        $res = $this->uploadOne($name);
        if ($num == 1) {
            return $res;
        } else if ($num == 2 && $res['status'] == false) {
            //证明第二次上传失败
            unlink($_SERVER['DOCUMENT_ROOT'] . $arr['up_img']);
        }
        return $res;
    }

    /**
     * 文件上传类
     * @author yangpeng 2017-10-11 
     * @param string $name 传入图片的name
     * @return array
     */
    protected function uploadOne($name)
    {
        /*1、实例化上传类并初始化相关值*/
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3*1024*1024;// 设置附件上传大小3M
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Upload/Home/realname/'; // 设置附件上传根目录
        $upload->saveName = $name . '_' . time() . '_' . rand(100000, 999999);
        /*2、上传单个文件，正确返回图片路径，错误返回错误信息*/
//                $info   =   $upload->uploadOne($_FILES[$name]);
        $info = $upload->uploadOne($_FILES[$name]);
        if (!$info) {// 上传错误提示错误信息
            $data['info'] = $upload->getError();
            $data['status'] = false;
            return $data;
        } else {// 上传成功 获取上传文件信息
            $data['info'] = '/Upload/Home/realname/' . $info['savepath'] . $info['savename'];
            $data['status'] = true;
            return $data;
        }
    }

        
    
    /**
     * @method 删除银行卡
     * @param $id 银行卡id ，$om区号（不带'+'）
     * @author  yangpeng 2018-5-15
     * @return json
     */
    public function BankDel(){
        $id = I('id');
        $om = I('om');
        if(!$id){
            $this->ajaxReturn(['status'=>203,'msg'=>L('_FWQFM_')]);
        }
        $info=M('UserBank')->where(['id'=>$id])->find();
        if(empty($info) || $info['status'] != 1) $this->ajaxReturn(['status'=>202,'msg'=>L('_FWQFM_')]);
        if($info['default_status']==1)//删除默认卡
        {
            $setDefaultRes = $this->setBankDefault($om);
            if(!$setDefaultRes) $this->ajaxReturn(['status'=>303,'msg'=>L('_FWQFM_')]);
        }

        $this->checkIsTrading($id,$om);
        //删除银行卡
        $res = M('UserBank')->where(['id'=>$id])->setField('status',0);
        if(!$res) $this->ajaxReturn(['status'=>204,'msg'=>L('_FWQFM_')]);
        $this->ajaxReturn(['status'=>200,'msg'=>L('_SCCCG_'),'data'=>'+'.$om]);
    }

    /**
     * @method 删除默认卡后，立即设置一张默认卡
     * @param $om 地区不带“+”
     * @author yangpeng 2019年3月27日15:37:49
     * @return bool
     */
    protected function setBankDefault($om){
        $where = [
            'country_code'=>"+".$om,
        ];
        // 用户当前地区绑定的银行卡
        $bankIds = M('BankList')->where($where)->field('id')->select();
        $map['bank_list_id'] = ['in',implode(',',array_column($bankIds,'id'))];
        $map['uid'] = getUserId();
        $map['status'] = 1;
        $map['default_status'] = ['neq',1];
        $areaBanks = M('UserBank')->where($map)->field('id')->select();
        if(empty($areaBanks)) return true;
        $ids = array_column($areaBanks,'id');
        $res = M('UserBank')->where(['id'=>current($ids)])->setField('default_status',1);
        if(!$res) return false;
        return true;
    }
    /**
     * @method 检测银行卡是否正在参与交易
     * @param $id 银行卡id ，$om区号（不带'+'）
     * @author yangpeng  2018-5-17
     * @return json
     */
    protected function checkIsTrading($id,$om) {
        if(empty($id) || empty($om)){
            $this->ajaxReturn(['status'=>201,'msg'=>L('_FWQFM_')]);
        }
        //p2p交易是否存在交易中的银行卡
        $where_p['status'] = array('in',array(0,1,2,8));//0挂单1买入成功2买家确认打款 不能删除银行卡
        $where_p['bank_id'] = $id;
        $info_p = M('TradeTheLine')->where($where_p)->select();
        if($info_p)   $this->ajaxReturn(['status'=>206,'msg'=>L('_YHKBKCZZS_')]);

        //c2c交易是否存在交易中的银行卡
        $where_c['status'] = array('in',array(1,2,5));//1买入成功 2买家确认打款 5.待处理
        $where_c['user_bank_id'] = $id;
        $info_c = M('CcTrade')->where($where_c)->select();        
        if($info_c)   $this->ajaxReturn(['status'=>206,'msg'=>L('_YHKBKCZZS_')]);

        //c2c挂卖单，此交易区只有一张卡，则这个卡也不能删除
        $order_map['status'] = 1;//c2c挂单
        $order_map['type'] = 2;//卖
        $order_map['uid'] = getUserId();
        $order_map['om'] = $om;
        $isBindPhone = M('CcOrder')->where($order_map)->find();
       if (!empty($isBindPhone)) {
            $bankwhere['n.country_code'] = '+'.$om;
            $bankwhere['m.uid'] = getUserId();
            $bankwhere['m.status'] = 1;
            $bankCount = M('UserBank')
                    ->alias('m')
                    ->field('m.id')
                    ->join('inner join __BANK_LIST__ as n on n.id=m.bank_list_id  ')
                    ->where($bankwhere)
                    ->count();
            if($bankCount == 1){//c2c挂卖时，此交易区只有一张卡，则这个卡也不能删除
                $this->ajaxReturn(['status'=>207,'msg'=>L('_YHKBKCZZS_')]);
            }
        }
        
    }  
    /*
     * 设置新登陆密码
     * @author zhanghanwen
     * @addtime 2017年10月24日17:04:03
     * @return json  
     * @param revisepwd
     * @param repeatpwd
     * @param token
     */
    public function setNewPassword()
    {
        $uid = getUserId();
        if (empty($uid)) {
            $this->ajaxreturn(array('msg' => L('_FEIFAQQ_'), 'status' => false));
        }

        $postData = I('post.');
        $this->setNewPasswordCheckParam($postData);
        // 检测是否和交易密码相同
        $userData = getUserForId( $uid, 'trade_pwd,om,username'  );
        $newPassword = passwordEncryption($postData['revisepwd']);
        if( $userData['trade_pwd'] == $newPassword ){
            $this->ajaxreturn(array('msg' => L('_DLMMBNHZJMMXT_'), 'status' => false));
        }
        // 验证动态密令是否正确
        if (!check_watchword($uid, $postData['token'])) {
            $this->ajaxreturn(array('msg' => L('_DTLPCW_'), 'status' => false));
        }

        // 修改密码，增加修改日志

        $r = M('user')->where(array('uid' => $uid))->save(array('pwd' => $newPassword));
        // 新增登陆日志
        $publicObj = new PublicFunctionController;
        $publicObj->addUserLog($uid, 2, 0);
        //记录口令日志
        $userWatchwordModel = new UserWatchwordModel();
        $userWatchwordModel->setTokenUsingLog($uid,$postData['token'],3);
        //app退出
        $userLogObj= new UserLog();
        $userLogObj->loginOutApp($postData['uid'],'app_target');
        if(!$r){
            $this->ajaxreturn(array('msg' => L('_XDLMMYZQYZQCS_'), 'status' => false));
        }
        // 操作成功
        $message = SceneCode::getPersonSafeInfoTemplate($userData['username'],$userData['om'],1);
        $data = explode('&&&',$message);
        push_msg_to_app_person($data[0],$data[1],$uid);
        $this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true));

    }

    /**
     * 检测输入的值
     * @author zhanghanwen
     * @param revisepwd
     * @param repeatpwd
     * @param token
     */
    private function setNewPasswordCheckParam($data)
    {   
        if (empty($data['revisepwd'])) {
            $this->ajaxreturn(array('msg' => L('_QTXXDLMM_'), 'status' => false));
        }
        if (empty($data['repeatpwd'])) {
            $this->ajaxreturn(array('msg' => L('_QCFTXXDLMM_'), 'status' => false));
        }
        if ($data['revisepwd'] != $data['repeatpwd']) {
            $this->ajaxreturn(array('msg' => L('_LCMMSRBYZ_'), 'status' => false));
        }
        if( strlen( $data['revisepwd']) < 6 || strlen( $data['repeatpwd']) < 6 ){
            $this->ajaxreturn(array('msg'=>L('_MMCDLDSB_'),'status'=>false));
        }
        if( (!regex( $data['revisepwd'], 'password5' ) || !regex( $data['revisepwd'], 'password4' )) || (!regex( $data['repeatpwd'], 'password5' ) || !regex( $data['repeatpwd'], 'password4' )) ){
            $this->ajaxreturn(array('msg'=>L('_MMGSBZQ_'),'status'=>false));
        }
        if (empty($data['token'])) {
            $this->ajaxreturn(array('msg' => L('_QTXDTKL_'), 'status' => false));
        }
    }

    /**
     * 设置交易密码
     * @author zhanghanwen
     * @time   2017年10月24日17:25:53
     * @param Trapass1
     * @param Trapass2
     * @param Trapass3
     */
    public function setNewTradePassword()
    {
        $uid = getUserId();
        if (empty($uid)) {
            $this->ajaxreturn(array('msg' => L('_FEIFAQQ_'), 'status' => false,'code' => 201));
        }
        $user_real = $this->getRealList($uid);
        if($user_real['status']!=1){
            $this->ajaxreturn(array('msg' => L('_ZYSMQBNJXJYCZQW_'), 'status' => false,'code' => 202));
        }
        $postData = I('post.');
        $this->setNewTradePasswordCheckParam($postData);
        // 验证登陆密码是否和交易密码一致
        $userData = getUserForId( $uid, 'pwd,om,username'  );
        $newPassword = passwordEncryption($postData['Trapass1']);
        if( $newPassword == $userData['pwd'] ){
            $this->ajaxreturn(array('msg' => L('_JYMMHDLMMBNXT_'), 'status' => false,'code' => 203));
        }
        // 验证动态密令是否正确
        if (!check_watchword($uid, $postData['Trapass3'])) {
            $this->ajaxreturn(array('msg' => L('_DTLPCW_'), 'status' => false,'code' => 204));
        }

        // 修改密码，增加修改日志
        $oldTraPwd = M('user')->where(array('uid' => $uid))->field('trade_pwd')->find();
        if( !$oldTraPwd['trade_pwd'] ){
            // 增加积分
            $publicObj = new PublicFunctionController;
            $publicObj->calUserIntegralAndLeavl( $uid, 10,array( 'operationType'=>'inc', 'scoreInfo'=>'綁定交易密碼贈送積分','status'=>6  ) );
            // 新增登陆日志
            $publicObj = new PublicFunctionController;
            $publicObj->addUserLog($uid, 7, 0);
        } else{
            // $redisObj = new RedisCluster();
            $this->redis = RedisCluster::getInstance();
            $this->redis->setex('setNewTradePassword'.$uid, 3600*24, true);
            // 新增登陆日志
            $publicObj = new PublicFunctionController;
            $publicObj->addUserLog($uid, 3, 0);
        }

        $r = M('user')->where(array('uid' => $uid))->save(array('trade_pwd' => $newPassword));

        //记录口令日志
        $userWatchwordModel = new UserWatchwordModel();
        $userWatchwordModel->setTokenUsingLog($uid,$postData['Trapass3'],2);
        if(!$r){
            $this->ajaxreturn(array('msg' => L('_XZJMMYYQYZQCS_'), 'status' => false,'code' => 205));
        }
        // 极光推送
		if( $oldTraPwd['trade_pwd'] ){
			$message = SceneCode::getPersonSafeInfoTemplate($userData['username'],$userData['om'],2);
			$data = explode('&&&',$message);
			push_msg_to_app_person($data[0],$data[1],$uid);
		}
		$this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true,'code' => 200));
    }

    /**
     * 检测参数
     * author zhanghanwen
     * @param Trapass1
     * @param Trapass2
     * @param Trapass3
     */
    private function setNewTradePasswordCheckParam($data)
    {
        if (empty($data['Trapass1'])) {
            $this->ajaxreturn(array('msg' => L('_QTXXZJMM_'), 'status' => false,'code' => 206));
        }
        if (empty($data['Trapass2'])) {
            $this->ajaxreturn(array('msg' => L('_QCFTXXZJMM_'), 'status' => false,'code' => 206));
        }
        if ($data['Trapass2'] != $data['Trapass1']) {
            $this->ajaxreturn(array('msg' => L('_LCSRBYZ_'), 'status' => false,'code' => 206));
        }
        if ( (!regex($data['Trapass1'], 'password5') || !regex($data['Trapass1'],'password4'))) {
            $this->ajaxreturn(array('msg' => L('_MMGSYW_'), 'status' => false,'code' => 206));
        }
        if( strlen( $data['Trapass1']) < 6 || strlen( $data['Trapass2']) < 6 ){
            $this->ajaxreturn(array('msg'=>L('_MMCDLDSB_'),'status'=>false,'code' => 206));
        }
        if (empty($data['Trapass3'])) {
            $this->ajaxreturn(array('msg' => L('_QTXDTKL_'), 'status' => false,'code' => 206));
        }
    }

    /*
     * 绑定电子邮件地址
     * author zhanghanwen
     * @param email
     */
    public function bindEmailAdress()
    {
        $uid = getUserId();
        if (empty($uid)) {
            $this->ajaxreturn(array('msg' => L('_FEIFAQQ_'), 'status' => false));
        }
        $postData = I('post.');
        if (empty($postData['email'])) {
            $this->ajaxreturn(array('msg' => L('_QTXYX_'), 'status' => false));
        }
        if (!regex($postData['email'], 'email')) {
            $this->ajaxreturn(array('msg' => L('_YXGSCW_'), 'status' => false));
        }
        $userData = getUserForId( $uid, 'email' );
        if( $userData['email'] ){
            $this->ajaxreturn(array('msg' => L('_NYBDYX_'), 'status' => false));
        }
        // 邮箱地址
        $r = M('user')->where(array('uid' => $uid))->save(array('email' => $postData['email']));

        $publicObj = new PublicFunctionController;
        $publicObj->calUserIntegralAndLeavl( $uid, 10 ,array('operationType'=>'inc', 'scoreInfo'=>'綁定郵箱贈送積分','status'=>2 ) );

        if(!$r){
            $this->ajaxreturn(array('msg' => L('_CZSBQCS_'), 'status' => false));
        }
        $this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true));
    }

    /**
     * 修改邮箱绑定
     * author zhanghanwen
     */
    public function modifyEmailAdress()
    {
        $postData = I('post.');
        $uid = getUserId();
        $this->modifyEmailAdressCheckParam($postData);
        $info = M('User')->where(['uid'=>$uid,'email'=>$postData['email']])->find();        
        if($info){
            $this->ajaxreturn(array('msg' => L('_XDZYXYYYXXTQCS_'), 'status' => false));
        }
        $r = M('user')->where(array('uid' => $uid))->save(array('email' => $postData['email']));
        if(!$r){
            $this->ajaxreturn(array('msg' => L('_CZSBQCS_'), 'status' => false));
        }
        $this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true));
    }

    /**
     * 修改邮箱绑定检测参数
     * author zhanghanwen
     * @param email
     */
    private function modifyEmailAdressCheckParam($data)
    {
        if (empty($data['email'])) {
            $this->ajaxreturn(array('msg' => L('_QTXYX_'), 'status' => false));
        }
        if (!regex($data['email'], 'email')) {
            $this->ajaxreturn(array('msg' => L('_YXGSCW_'), 'status' => false));
        }
        // 检测图片验证码
        /*
        $checkVerifyObj = new CheckVerifyController();
        if (!$checkVerifyObj->checkVerify($data['vercode'])) {
            $this->ajaxreturn(array('msg' => L('_TPYZMCW_'), 'status' => false));
        }
        */
    }
    /*
     * 历史登录记录
     * author 黎玲
     * 2017年10月26日11:47:24
     */
    public function getData(){
        $uid = getUserId();
        $mood = $uid%4;//确定分表尾号
        $time = strtotime('-1 month');//一个月前的时间戳  
        $save_time['add_time'] = array('EGT',$time);         
        $count = M("UserLog$mood")->where(array('uid'=>$uid))->where($save_time)->count();//总记录数
        $page = new \Home\Tools\AjaxPage($count, 15,'getData','1');
        $show = $page->show();
        $login_list = M("UserLog$mood")
                ->where(array('uid'=>$uid))
                ->where($save_time)
                ->order('id desc')
                ->limit($page->firstRow.','.$page->listRows)
                ->select();
        foreach( $login_list as $k=>$value ){
            $login_list[$k]['add_time'] = date('Y-m-d H:i:s', $value['add_time'] );
            $login_list[$k]['type'] = formatLogType($value['type']);
        }
        $this->ajaxReturn(array('data'=>$login_list ,'page'=>$show ));
        
    } 
    /*
     * test liling
     */
    public function historyLoad(){
        $uid = getUserId();
        $mood = $uid%4;//确定分表尾号
        $time = strtotime('-1 month');//一个月前的时间戳  
        $save_time['add_time'] = array('EGT',$time);         
        $count = M("UserLog$mood")->where(array('uid'=>$uid))->where($save_time)->count();//总记录数
        $page = New \Home\Tools\AjaxPage($count, 15,'getData','');
        $show = $page->show();
        $list =  M("UserLog$mood")->where(array('uid'=>$uid))
                ->where($save_time)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('id desc')
                ->select();
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display();
    }
    /**
     * 解绑令牌haha 
     * @author zhanghanwen
     */
    public function unBundingToken()
    {
        $uid = getUserId();
        $postData = I('post.');
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $this->unBundingTokenCheckParam( $postData );
        $isOk = $this->redis->get('unBundingToken_'.$uid);
        if( $isOk ){
            $this->ajaxreturn(array('msg' => L('_YTNZNCZYC_'), 'status' => false));
        }
        $userModel = new UserModel();
        $userData = $userModel->getUserInfoForId($uid, 'phone,om,username');
        if(!checkSmsCode( $uid, $userData['phone'], 'unBundingToken', $postData['sms'] ) ){
            $this->ajaxreturn(array('msg' => L('_DXYZCW_'), 'status' => false));
        }
        $userWatchwordModel = new UserWatchwordModel();
        $result = $userWatchwordModel->unbundlingUserToken( $uid, $postData['serial_num'], $postData['secret_key'] );      
        if( $result ) {
            $this->redis ->setex('unBundingToken_'.$uid, 3600*24, true);
            // 极光推送
            $message = SceneCode::getPersonSafeInfoTemplate($userData['username'],$userData['om'],6);
            $data = explode('&&&',$message);
            push_msg_to_app_person($data[0],$data[1],$uid);
            $this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true));
        } else {
            if($userWatchwordModel->errno == 10004){
                $this->ajaxreturn(array('msg' => L('_LPXLHHMYCW_'), 'status' => false));
            }
            if($userWatchwordModel->errno == 9999){
                $this->ajaxreturn(array('msg' => L('_CZSBQSHZS_'), 'status' => false));
            }
            if($userWatchwordModel->errno == 10026){
                $this->ajaxreturn(array('msg' => L('_LPXLHHMYCW_'), 'status' => false));
            }
        }
    }
    /*
     * @param serial_num
     * @param secret_key
     */
    private function unBundingTokenCheckParam( $data ){
        if (empty($data['serial_num'])) {
            $this->ajaxreturn(array('msg' => L('_QTXLPXL_'), 'status' => false));
        }
        if (empty($data['secret_key'])) {
            $this->ajaxreturn(array('msg' => L('_QTXLPMY_'), 'status' => false));
        }
         if(!regex($data['secret_key'],'bindtoken') || !regex($data['serial_num'],'bindtoken')){
             $this->ajaxreturn(array('msg' => L('_QSRZMHSZDZH_'), 'status' => false));
         }
        //绑定的时候进行验证码图片验证码   2019年5月8日11:51:32
        if(empty($data['vercode'])){
            $this->ajaxreturn(array('msg' => L('_TPYZMBNWK_'), 'status' => false));
        }
        $checkVerifyObj = new CheckVerifyController();
        if (!$checkVerifyObj->checkVerify($data['vercode'])) {
            $this->ajaxreturn(array('msg' => L('_TPYZMCW_'), 'status' => false));
        }
      
        if (empty($data['sms'])){
            $this->ajaxreturn(array('msg' => L('_QSRSJYZM_'), 'status' => false));
        }
    }

    /**
     * 发送短信验证码
     * author zhanghanwen
     */
    public function unBundingTokenSmsSend(){
        $uid = getUserId();
        $userModel = new UserModel();
        $userData = $userModel->getUserInfoForId($uid, 'om,phone');
        $publicLogic = new PublicFunctionController();
        $result = $publicLogic->sendPhoneCode( array( 'om'=>$userData['om'], 'phoneNum'=>$userData['phone'], 'phoneCodeType'=>'unBundingToken','msgType'=>3 ), $uid, 5  );
        $this->ajaxreturn( $result );
    }
    /**
     * 银行卡绑定  
     * 2017-10-31   yangpeng
     */
    public function showBankCardBind(){
        //1、检测实名认证
        $uid = getUserId();
        $om = I('om');
        $type = I('type');
        $real_info = M('UserReal')->where(['uid'=>$uid])->find();       
         //2、获取地区列表
        $bank_area = M('CountryCode')
                ->alias('a')
                ->join(' RIGHT JOIN __BANK_LIST__ as m on a.code = m.country_code')
                ->field('a.code,a.country')
                ->select();
        $bank_country = $this->array_unique_fb($bank_area);//删除多余的键值对

        //3、获取用户银行卡绑定信息
        $user_bank_list =  M('UserBank')->where(['uid'=>$uid,'status'=>1])->order('id desc')->getField("bank_list_id",true);
        //4、默认下拉框要展示的银行卡类型.已绑定的银行卡不再出现
        if($user_bank_list){
            $map['id'] = array('not in',$user_bank_list);
            $bank_xg =M('BankList')->where(['country_code'=>'+852'])->where($map)->select(); //默认下拉框要展示的银行卡类型
        }else{
            $bank_xg =M('BankList')->where(['country_code'=>'+852'])->select(); //默认下拉框要展示的银行卡类型
        }
        //5、获取用户默认展示在页面下方的银行卡信息
        foreach ($bank_country as $value) {
            $user_bank_lists[] = $this->getUserBank($uid,$value['code']);
        }
        //6、获取用户绑定的默认银行卡
        $userbanks  = M('UserBank')->where(['uid'=>$uid,'default_status'=>1,'status'=>1])->field('bank_list_id')->select();
        if($type){
            $code= M('BankList')->where(['id'=>current($user_bank_list)])->field('country_code')->find();
            $this->assign('code',$code?current($code):"+852"); 
        }elseif($om){
            $this->assign('code',$om);   
        }else{
            $this->assign('code','+852');
        }
        $this->assign('bank_xg',$bank_xg);
        $this->assign('bank_country',$bank_country);       
        $this->assign('real_info',$real_info);
        $this->assign('userbanks', json_encode($userbanks));
        $this->assign("user_bank_lists",$user_bank_lists);
        $this->display();
    }

    /*
     * 根据地区获取银行列表
     * 2017-11-1  yangpeng
     */
    public function ajaxDataGet(){

        if(!IS_AJAX) $this->ajaxReturn([]);
        $uid = getUserId();
        $country = I('country');
        if(empty($country) ){
            $country = '+852';
        }elseif(!strstr($country,'+')){
            $country = '+'.$country;
        }
        $bank_list =  M('UserBank')->where(array('uid'=>$uid,'status'=>1))->getField("bank_list_id",true);//获取用户银行卡绑定信息
        if($bank_list){
            $map['id'] = array('not in',$bank_list);
            $data_banks = M('BankList')->where(['country_code'=>$country])->where($map)->select(); //总银行类型
            foreach($data_banks as $key=> $value){
                $data_banks[$key]['bank_name'] =formatBankType($value['id']);//转换成多语言
            }
        }else{
            $data_banks = M('BankList')->where(['country_code'=>$country])->select(); //总银行类型
            foreach($data_banks as $key=> $value){
                $data_banks[$key]['bank_name'] =formatBankType($value['id']);
            }
        }
        if(empty($data_banks))  $this->ajaxReturn(['msg'=>L('_ZANWUYINHANG_'),'code'=>201,'data'=>[]]);
        $this->ajaxReturn(['data'=>$data_banks,'code'=>200,'msg'=>'ok']);

    }
     /**
     * 用户已绑定的银行卡信息
     * 2017-11-1  yangpeng
      * return array()
     */
    protected function getUserBank($uid,$country_code="+852"){
            $user_bank_lists =  M('UserBank')
                ->alias('m')
                ->field('m.*,n.id as bank_id,n.country_code')    
                ->join('LEFT JOIN __BANK_LIST__ as n on m.bank_list_id = n.id')
                ->order('id')
                ->where(['uid'=>$uid,'country_code'=>$country_code,'status'=>1])
                ->select();
           return $user_bank_lists;
    }
    /*
     * 设置银行卡为默认银行卡
     * 2017-11-1  yangpeng
     */
    public function ajaxBankDefault(){
        if(!IS_AJAX) $this->ajaxReturn(['info'=>L('_FWQFM_'),'status'=>500]);
        //1、获取传递过来的地区编号和银行卡id
        $uid = getUserId();
        $bankid = I('bankid');
        $country = I('country');
        //2、判断是否已为默认值
        $bank_info = M('UserBank')->where(['uid'=>$uid,'bank_list_id'=>$bankid,'status'=>1])->find();
        if($bank_info['default_status']==1)  $this->ajaxReturn(['info'=>L('_YWMRZ_'),'status'=>201]);

         //1、根据用户id和地区编号得到该地区的默认绑定银行卡
        $diaodiao= M('UserBank')
        ->alias('m')
        ->join('LEFT JOIN __BANK_LIST__ as n on m.bank_list_id = n.id')
        ->where(['uid'=>$uid,'country_code'=>$country])
        ->field("m.id,m.uid,m.bank_list_id,m.default_status")
        ->select();
        foreach ($diaodiao as $value) {//将该地区的默认卡都清为0
            $default_status = M('UserBank')->where(['id'=>$value['id']])->find();
            if($default_status['default_status']==1)  M("UserBank")->where(['id'=>$value['id']])->setField('default_status',0);
        }
        $res = M('UserBank')->where(['uid'=>$uid,'bank_list_id'=>$bankid])->setField('default_status',1);
        if(!$res)  $this->ajaxReturn(['info'=>L('_FWQFM_'),'status'=>201]);
        $this->ajaxReturn(['info'=>L('_SZCG_'),'status'=>200]);
    }
    /*
     * 用户已绑定的银行卡信息
     * 2017-11-1  yangpeng
     */
    public function ajaxBankShow(){
        if(IS_AJAX){
            $uid = getUserId();
            $country = I('country')?I('country'):'+852'; 
            $user_bank_lists =  M('UserBank')
                ->alias('m')
                ->join('LEFT JOIN __BANK_LIST__ as n on m.bank_list_id = n.id')
                ->where(['uid'=>$uid,'country_code'=>$country,'status'=>1])
                ->select();
            $this->ajaxReturn($user_bank_lists);
        }
    }
   
    /*
     * 二维数组将重复的一维数组去掉
     * 2017-11-2 yangpeng
     */
    protected function array_unique_fb($array2D)
    {
        foreach ($array2D as $k=>$v)
        {
            $v = join(",",$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k] = $v;
        }
        $temp = array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组
        $sb = 0;
        foreach ($temp as $k => $v)
        {
            $array=explode(",",$v);        //再将拆开的数组重新组装
            //暂时取消跟台湾相关的银行卡绑定
            if($array[0]=='+886') continue;
            $temp2[$sb]["code"] =$array[0];   
            $temp2[$sb]["country"] =$array[1];
            $sb++;
        }
        return $temp2;
    }
    /*
     * checkBankData  
     * 2018-1-2  yangpeng 
     */
    protected function checkBankData($area,$bank_type,$name,$bankNum,$bankAddress) {
        if(!$area){
                $this->ajaxReturn(['info'=>L('_KHDQBNWK_'),'status'=>201]);
            }
            if(!$bank_type || $bank_type< 0){
                $this->ajaxReturn(['info'=>L('_KHYHMBNWK_'),'status'=>201]);
            }
            if(!$name){//表单数据存在性验证
                $this->ajaxReturn(['info'=>L('_KHXMBNWK_'),'status'=>201]);
            }
            if(!$bankNum){
                $this->ajaxReturn(['info'=>L('_YHKZHBNWK_'),'status'=>201]);
            }
            if($area !='+886'){//台湾用户可以不用填写地址
                if(!$bankAddress){
                    $this->ajaxReturn(['info'=>L('_KHDZBNWK_'),'status'=>201]);
                }
            }
            if(!formatBankType($bank_type)){
                $this->ajaxReturn(['info'=>L('_ZBZCCLXYH_'),'status'=>201]);
            }
            if(!regex($name,'cardname1') && !regex($name,'cardname2')){
                $this->ajaxReturn(['info'=>L('_KHXMGSBZQ_'),'status'=>201,'name'=>$name]);
            }
            $regex = $this->getRegexByArea($area);//获取地该区银行卡的正则表达式
            if(!regex($bankNum,$regex)){//银行卡号的正则验正
                $this->ajaxReturn(['info'=>L('_YHKZHGSBZC_'),'status'=>201]);
            }
            if($area!='+886'){
                if(!regex($bankAddress,'addressname') ){//银行卡地区的正则验正
                $this->ajaxReturn(['info'=>L('_DZBNBHFH_'),'status'=>201]);
            }
            }
    }
    /*
    * 处理提交绑定银行卡信息
    * @author yangpeng 2017-8-9
    */
        public function subBankCardBind(){
            //1、屏蔽不支持的访问类型
            if(!IS_POST)   $this->ajaxReturn(['info'=>L('_BZCDFWFS_'),'status'=>201]);
            //2、实名检测
            $uid = getUserId();
            if(!$uid) $this->ajaxReturn(['info'=>L('_YHBCZ_'),'status'=>201]);

            $real = M('UserReal')->where(array('uid'=>$uid))->find();
            if(!$real)     $this->ajaxReturn(['info'=>L('_WJXSMRZ_'),'status'=>201]);//未进行实名认证
            if($real['status']!=1)     $this->ajaxReturn(['info'=>L('_WTGSMRZ_'),'status'=>201]);//未通过实名认证

            //3、数据获取
            $area =I('area')?I('area'):'';//地区代号
            $bank_type = I('bank_type')?I('bank_type'):NULL;//开户银行
            $name = I('name')?trim(I('name')):NULL;//开户姓名
            $bankNum = I('bank_num')?trim(I('bank_num')):NULL;//银行卡号
            $bankAddress = I('bank_address')?trim(I('bank_address')):NULL;   //开户银行地址 
            //4、数据非空验证和正则验证
            $this->checkBankData($area,$bank_type,$name,$bankNum,$bankAddress);
            //5、同类型银行卡是否绑定
            $sameBank = M('UserBank')->where(array('uid'=>$uid,'bank_list_id'=>$bank_type,'status'=>1))->find();//查看用户是否已绑定此类银行卡
            if($sameBank)   $this->ajaxReturn(['info'=>L('_NYBDCLXYHK_'),'status'=>201]);

            $user_banks = M('UserBank')->where(['uid'=>$uid])->select();
            $table = getTbl('UserScoreLog', $uid, 4);
            $user_log = M($table)->where(['uid'=>$uid,'status'=>7])->find();           
            //6、组装数据入库
            $data = array(
            		'uid' => $uid,
            		'bank_real_name'=>$name,
            		'bank_list_id' => $bank_type,
            		'bank_address' => $bankAddress,
            		'bank_num'	=> $bankNum,
            		'status'	=> 1,
            		'add_time'	=> time(),
            );
            M()->startTrans();
            if(empty($user_banks) && empty($user_log)){//第一次绑卡
                $res = M('UserBank')->add($data);
                if(!$res)  {
                    M()->rollback();
                    $this->ajaxReturn(['info'=>L('_FWQFM_' ),'status'=>201]);
                }
                //8、首次绑定加积分
                $publicObj = new PublicFunctionController;
                $publicObj->calUserIntegralAndLeavl($uid, 10 ,array('operationType'=>'inc', 'scoreInfo'=>'綁定銀行卡贈送積分','status'=>7 ) );
                //9、首次绑定，当前卡设置为默认卡
                $default_bank = M('UserBank')->where(['id'=>$res])->setfield('default_status',1);
                if(empty($default_bank)) {
                    M()->rollback();
                    $this->ajaxReturn(['info'=>L('_FWQFM_'),'status'=>201]);
                }
                M()->commit();
                $this->ajaxReturn(['info'=>L('_YHKBDCG_'),'status'=>200,'flage'=>1,'default_status'=>1]);

            }else{//非第一次绑卡
                //7、该地区首次绑定，当前卡设置为默认卡

                $area_bank = $this->getUserAreaBank($uid,$area);
                $res = M('UserBank')->add($data);
                if(!$res) {
                    M()->rollback();
                    $this->ajaxReturn(['info'=>L('_FWQFM_' ),'status'=>201]);
                }
                if(empty($area_bank)){
                    $default_bank = M('UserBank')->where(['id'=>$res])->setfield('default_status',1);
                    if(empty($default_bank)){
                        M()->rollback();
                        $this->ajaxReturn(['info'=>L('_FWQFM_' ),'status'=>201]);
                    }
                    M()->commit();
                    $this->ajaxReturn(['info'=>L('_YHKBDCG_'),'status'=>200,'default_status'=>1]);
                }
                M()->commit();
                $this->ajaxReturn(['info'=>L('_YHKBDCG_'),'status'=>200]);
            }
       }
       
    /*
     * 获取不同地区银行卡正则
     * 2017-12-15 yangpeng
     */   
    protected function getRegexByArea($area){
           switch ($area) {
               case '+886':
                   return 'bankcard_tw';
               case '+856':
                    return 'bankcard_xg';
               case '+86':
                    return 'bankcard_cn';
               default:
                   return 'bankcard_xg';
           }
       }
    /*
    * 获取用户地区的绑定卡
    * @author yangpeng 2017-11-13
    */
        public function getUserAreaBank($uid,$country){
        //1、根据用户id和地区编号得到该地区的默认绑定银行卡
            $diaodiao= M('UserBank')
                    ->alias('m')
                    ->join('LEFT JOIN __BANK_LIST__ as n on m.bank_list_id = n.id')
                    ->where(['uid'=>$uid,'country_code'=>$country,'status'=>1])
                    ->select();   
            return $diaodiao;
        }
        
   /**
    * 诚信积分
    * author zhanghanwen
    * addtime 2017年11月3日15:10:34
    */
    public function integrityScore(){
        $uid = getUserId();
        $userInfo = getUserForId( $uid ,'phone,email,trade_pwd,credit_level,level' );
        $needIntegral = nextLevelIntegralRequired( $userInfo['level'], $userInfo['credit_level'] );
        $this->assign( 'needIntegral', $needIntegral );
        $this->assign( 'needIntegralProportion', ( $userInfo['credit_level'] / $needIntegral['total'] ) * 100 );
        $this->assign( 'userInfo', $userInfo );

        /*分页*/
        $tbl = getTbl('UserScoreLog', $uid );
        $where['uid']=$uid;
        $count      = M($tbl)->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Home\Tools\AjaxPage($count, 10,'getIntegrityScoreData','',1);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $limit     = $Page->firstRow.','.$Page->listRows;
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = M($tbl)->where($where)->order("id desc")->limit($limit)->select();
        foreach($list as $key=>$v ){
            $symbol = $v['type'] ==2?'+':'-';
            if($v['type']==1){
                $list[$key]['integral'] = '<span style="color:#e8653b;">'.$symbol.$v['integral'].'</span>';
            } else{
                $list[$key]['integral'] = '<span style="color:#4bd2b7;">'.$symbol.$v['integral'].'</span>';
            }
        }
        // 查询各种状态
        $realStatus = M('UserReal')->where( $where )->field('status')->find();
        if(!$realStatus['status']){
            $realStatus['status'] = 0;
        }
        // 查询是否绑定token
        $userWatchModel = new UserWatchwordModel();
        $isBind = $userWatchModel->checkUserBind($uid);
        if($isBind){
            $tokenStatus = 1;
        }else{
            $tokenStatus = 0;
        }
        // 查询是否绑定银行卡
        $bankStatus = M('UserBank')->where( $where )->field('id')->find();
        if(empty($bankStatus['id'])){
            $bankStatus = 0;
        } else{
            $bankStatus = 1;
        }
        // 查询是否绑定充值地址
        $rechargeStatusArr = M('UserCurrency')->where( $where )->field('my_mention_pack_url3,my_mention_pack_url2,my_mention_pack_url1,my_charge_pack_url')->select();
        foreach( $rechargeStatusArr as $key=>$value ){
            if(!empty($value['my_charge_pack_url'])){
                $rechargeStatus = 1;
                break;
            } else{
                $rechargeStatus = 0;
            }
        }
        // 查询提币地址是否绑定
        foreach( $rechargeStatusArr as $key=>$value ){
            if(!empty($value['my_mention_pack_url3']) || !empty($value['my_mention_pack_url2']) || !empty($value['my_mention_pack_url1']) ){
                $extractStatus = 1;
                break;
            }else{
                $extractStatus = 0;
            }
        }
        // 删除超过一个月的记录
        $firstMonth = time()-2592000;
        $newWhere['uid']=$uid;
        $newWhere['add_time']=array('ELT',$firstMonth);
        M($tbl)->where($newWhere)->delete();
        $currencyData = M('Currency')->field('currency_name,id,buy_off_line_fee,sell_off_line_fee')->where(['status' => 1])->select();
        $this->assign( 'currencyData', $currencyData );   // 币种信息
        $this->assign( 'extractStatus', $extractStatus ); // 是否绑定提币地址
        $this->assign( 'rechargeStatus', $rechargeStatus ); // 是否绑定充值地址
        $this->assign( 'bankStatus', $bankStatus ); // 是否绑定银行卡
        $this->assign( 'realStatus', $realStatus['status'] ); // 是否绑定令牌
        $this->assign( 'tokenStatus', $tokenStatus ); // 是否实名认证
        $this->assign( 'page', $show );
        $this->assign( 'data', $list );
        $this->display();
    }

    /**
     * 获取数据
     * author zhanghanwen
     * time 2017年11月3日18:02:10
     */
    public function getIntegrityScoreData(){
        /*分页*/
        $type = I('dataType',1);
        $uid = getUserId();
        $time = time();
        $tbl = getTbl('user_score_log', $uid );
        $where['uid'] = $uid;
        $BeginDate = strtotime('-30 day');//默认搜最近一个月的记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        if( $type ==2 ){
            $BeginDate = strtotime('-2 week');

        }elseif( $type ==3 ){
            $BeginDate = strtotime('-2week');

        }elseif( $type ==4 ){
            $BeginDate = strtotime("-3week");
        }elseif( $type ==5 ){
            $BeginDate = strtotime('-30 day');
        }
        $EndDate =  $time;
        $where['add_time'] = array('between',array($BeginDate,$EndDate));
        $count = M($tbl)->where($where)->count();
        $Page       = new \Home\Tools\AjaxPage($count, 10,"getIntegrityScoreData",$type,$type);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $limit     = $Page->firstRow.','.$Page->listRows;
        $show       = $Page->show();// 分页显示输出

        $list = M($tbl)->where($where)->limit($limit)->order("id desc")->select();

        foreach( $list as $k=>$value ){
            $list[$k]['add_time'] = date('Y-m-d', $value['add_time'] );
            $symbol = $value['type'] ==2?'+':'-';

//            if( $value['level'] == 0 ) {
//                $text = L('_PTYH_');
//            } else{
//                $text = '<img src="/Public/Home/img/VIP'.$value['level'].'.png" alt="">';
//            }
            if($value['type']==1){
                $list[$k]['pointadd'] = '<span style="color:#e8653b;">'.$symbol.$value['integral'].'</span>';
            } else{
                $list[$k]['pointadd'] = '<span style="color:#4bd2b7;">'.$symbol.$value['integral'].'</span>';
            }
            $list[$k]['info'] = formatJifenLog($value['status']);
        }

        $this->ajaxReturn( array( 'data'=>$list ,'page'=>$show ) );
    }
	
	public function checkVerCode(){
        $data = I();
        $checkVerifyObj = new CheckVerifyController();
        if (!$checkVerifyObj->checkVerify($data['vercode'])) {
            $this->ajaxreturn(array('msg' => L('_TPYZMCW_'), 'status' => false));
        }
        $this->ajaxreturn(array('msg' => L('_CZCG_'), 'status' => true));
	}

}