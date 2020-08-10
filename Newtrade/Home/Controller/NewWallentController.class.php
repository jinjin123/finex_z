<?php
/**
 * @author 建强   2019年3月4日15:01:43
 * @desc   充提币页(钱包)
 */
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Logics\CheckAllCanUseParam;
use Home\Model\WallentModel;
use Common\Api\BchAddressApi;
use Home\Logics\PublicFunctionController;
use Home\Logics\UserMoneyApi;
use Common\Api\RedisCluster;
use Home\Sms\Yunclode;
use Common\Api\Tibi;
use Common\Api\Wallent;
class NewWallentController extends CommonController
{
    protected $uid;  //用户id 
    
    public function _initialize(){
        parent::_initialize();
        $this->uid = getUserId();  
    }
    /**
     * @author 建强 2019年3月4日 下午3:06:04
     * @method 获取币种信息
     * @param  int $currency_id
     * @return string json
    */
    public function getChargeInfo($currency_id=''){
        $uid =   $this->uid;
        $model = new WallentModel();
        $ret   = $model->getChargeTabPage($uid,$currency_id);
        $this->ajaxReturn($ret);
    }
    /**
     * @author 建强 2019年3月4日 下午6:16:59
     * @method 绑定充币地址 (首次绑定或者更新)
     * @return string json
    */
    public function bindChargeAddr(){
        $curr_id = I('currency_id');
        $type    = I('type');
        $pack_url= I('pack_url');
        $uid     = $this->uid;
        
        //检验是否实名认证
        $userObj   = new CheckAllCanUseParam();
        $user_real = $userObj->checkUserRealStatus($uid);
        if($user_real['code']!=200){
            $this->ajaxReturn($user_real);
        }
        $model   = new WallentModel();
        $data    = $model->bindMyChargeUrl($curr_id,$uid,$type,$pack_url);
        $this->ajaxReturn($data);
    }
    /**
     * @author 建强 2019年3月5日 上午10:05:58
     * @method 获取充币的记录列表 分页
     * @param  page页码    int
     * @param  curr_id int
     * @return string json
     */
    public function getChargeList(){
        $ret  =[
            'code'=>'60001',
            'msg' =>L('_CSCW_'),
            'data'=>''
        ];
        $uid = $this->uid;
        $currency_id = I('currency_id');
        if(empty($uid) || empty($currency_id)){
            $this->ajaxReturn($ret);
        }
        $model    = new WallentModel();
        $currencys=  $model ->getCurrency();
        $data     = WallentModel::getChargelistByCurrencyId($uid,$currency_id,$currencys);
        if(empty($data['list'])){
            $ret['msg']=L('_CZSB_');
            $this->ajaxReturn($ret);
        }
        //成功
        $ret['msg']='success';
        $ret['code']=200;
        $ret['data']=$data;
        $this->ajaxreturn($ret);
    }
    /**
     * @method 提币记录ajax分页
     * @author 杨鹏 2019年3月5日16:53:27
     * @param  int page
     * @return string json
     */
    public function getTiBiList(){
        $ret  = [
            'code'=>'60001',
            'msg' =>L('_CSCW_'),
            'data'=>''
        ];
        $uid = $this->uid;
        $currency_id = I('currency_id');
        if(empty($uid) || empty($currency_id)){
            $this->ajaxReturn($ret);
        }
        $model    = new WallentModel();
        $data     = $model->getTiBIRecord($uid,$currency_id);
        if(empty($data['list'])){
            $ret['msg']=L('_CZSB_');
            $this->ajaxReturn($ret);
        }
        //成功
        $ret['msg']='success';
        $ret['code']=200;
        $ret['data']=$data;
        $this->ajaxreturn($ret);
    }
    /**
     * @method 获取提币页面数据信息
     * @author 杨鹏 2019年3月4日15:32:03
     * @param  string $currency_id 币种id
     * @return string json
     */
    public function getTiBiPageData($currency_id=''){
        $ret = [
            'code'=>30001,'msg'=>L('_CZSB_') ,
            'data'=>'',
        ];
        $uid =$this->uid;
        $wallenlt = new WallentModel();
        
        //检验币种是否维护
        $status = $wallenlt->checkCurrConfig($currency_id,2);
        if($status){
            $ret['data']['refresh']=1;
            $ret['msg'] = L('_BZWHZBZBYXCZ_');
            $this->ajaxReturn($ret);
        }
        if(empty($currency_id)){
            //注意提币为2字段筛选条件
            $currency_id = WallentModel::searchCurrId($wallenlt->getCurrency(2));
        }
        //1、提币币种列表（注意币种状态，标注出币种下架、币种维护，暂停充提币等具体信息）
        $currencyList = $wallenlt->getWalletCurrencyList($currency_id);
        //2、用户提币地址（已绑定后面有两个按钮：提币和删除;未绑定有一个按钮：绑定提币地址）
        $tibiUrl      = $wallenlt->getUserTiBiUrl($uid,$currency_id);
        //4、提币记录（异步分页）
        $userTibiRecord = $wallenlt->getTiBIRecord($uid,$currency_id);
        
        $ret['code'] =200;
        $ret['msg']  ='success';
        $ret['data']=[
            'tab_currency'=>$currencyList,
            'tibiUrl'=>$tibiUrl,
            'res'=>$userTibiRecord,
            'refresh'=>0
        ];
        $this->ajaxReturn($ret);
    }
    /**
     * @method 提币次数校验
     * @author 杨鹏 2019年3月5日11:34:11
     * @param $uid
     * @return string json 直接返回客户端
     */
    private function CheckTiBiTime($uid,$tibi_many_time_key,$redisClient){
        $tibi_many_time = $redisClient->get($tibi_many_time_key);
        if($tibi_many_time){
            $this->ajaxReturn(['status'=>404,'msg'=>L('_FWQFM_')]);  //服务器繁忙
        }
    }
    /** 
     * @method 提币申请 (AJAX POST)
     * @author 杨鹏 2019年3月4日15:38:26 
     * @param  币种 currency_id，
     * @param  转出数量 tibi_num，
     * @param  提币字段 address_index，
     * @param  旷工费  collier_fee，
     * @param  资金密码 trade_pwd，
     * @param  验证码，code 
     * @param  手机验证码 phone_code
     * @return string json
     */
    public function subTransferNum(){
        if(!IS_AJAX || !IS_POST) $this->ajaxReturn(['status'=>400,'msg'=>L('_FWQFM_')]);
        $uid = $this->uid ;
        $input = I('post.');
        
        //检验是否实名认证
        $userObj   = new CheckAllCanUseParam();
        $user_real = $userObj->checkUserRealStatus($uid);
        if($user_real['code']!=200){
            $this->ajaxReturn($user_real);
        }
        //效验基础参数
        $wallent = new WallentModel();
        $checkRes = $wallent->checkTiBiParam($input,$uid);
        
        if($checkRes['status']!=200){
           $this->ajaxReturn(['status'=>$checkRes['status'],'msg'=>$checkRes['msg']]);
        }
        $user_curr_num = $checkRes['num'];
        $user_phone    = $checkRes['phone'];
        //提币次数校验
        $redisClient   = RedisCluster::getInstance();
        $redis_key     = 'TIBI_MANY_TIME_'.$uid;
        $this->CheckTiBiTime($uid,$redis_key,$redisClient);

        //处理充币记录 
        $data = [
            'uid'        => $uid,
            'url'        => $checkRes['tibi_url'],
            'currency_id'=> $input['currency_id'],
            'actual'     => $input['num']-$input['collier_fee'],
            'collier_fee'=> $input['collier_fee'],
            'num'        => floatval(trim($input['num'])),
            'status'     => 0,
            'add_time'   => time(),
        ];
        M()->startTrans();
        $db_trans = [];
        $redisClient->setex($redis_key,10,1);
        //添加提币记录
        $db_trans[] = M('Tibi')->add($data);
        //更新用户余额
        $UserMoneyApi     = new UserMoneyApi();
        $db_trans[]       = $UserMoneyApi->setUserMoney($uid, $input['currency_id'],$input['num'],'num','dec');
        $after_money      = bcsub($user_curr_num,$input['num'],8);
        
        //增加财务记录 
        $user_finance_data= [
            'uid'=> $uid,
            'currency_id'=> $input['currency_id'],
            'finance_type'=> 1,
            'money'=> $input['num'],
            'add_time'=> time(),
            'after_money'=> $after_money,
            'type'=> 2,
            'content'=> '提币',
        ];
        $db_trans[] = M(getTbl('UserFinance',$uid))->add($user_finance_data);
        //提币失败
        if(in_array(false, $db_trans)){
            M()->rollback();
            $this->ajaxReturn(['code'=>'50001','msg'=>L('_TIBISHIBAI_')]); 
        }
        //提币成功
        M()->commit();
        $redisClient->del('TIBI_'.$uid.'_'.$user_phone);
        //提币成功返回 分页数据
        $model    = new WallentModel();
        $list     = $model->getTiBIRecord($uid, $input['currency_id']);
        $ajaxRes  = ['code'=>'200','msg'=>L('_TIBICHENGGONG_'),'data'=>''];
        if(empty($list['list'])){
            $ajaxRes['data']['page_code']=30003;
            $this->ajaxReturn($ajaxRes); 
        }
        $ajaxRes['data']['res']=$list;
        $ajaxRes['data']['page_code']=200;
        $this->ajaxReturn($ajaxRes); 
    }
    /**
     * @author 建强 2019年3月6日 上午11:30:38
     * @method 获取当前币种当前最大可提币数量 
     * @return string json
     */
    public function getMaxTransferNum($currency_id=''){
        $ret = [
            'code'=>200,'msg'=>'success',
            'data'=>[
                 'num'=>0,
                 'min_num'=>0,
            ]
        ];
        if(empty($currency_id)) $this->ajaxReturn($ret);
        $uid    = $this->uid;
        $num_val= Tibi::getTibiNumConfigVal($uid,$currency_id);
        $ret['data']['num']    = $num_val['num'];
        $ret['data']['min_num']= $num_val['min_num'];
        $this->ajaxReturn($ret);
    }
    /**
     * @method 删除提币地址    --tips：只是清除字段信息，不删除记录
     * @author 杨鹏 2019年3月4日15:43:32
     * @param string currency_id 币种id
     * @param string address_index 提币地址字段
     * @return string json
     */
    public function delTiBiUrl(){
        $ret = [
            'code'=>30001,'msg'=>L('_SCDZSB_'),
            'data'=>'',
        ];
        $uid = $this->uid;
        $currency_id   = I('currency_id');
        $address_index = I('address_index');
        if(!in_array($address_index, [1,2,3]) || empty($currency_id)){
            $ret['code'] = 40001;
            $ret['msg'] = L('_FEIFAQQ_');
            $this->ajaxReturn($ret);
        }
        //检验是否实名认证
        $userObj   = new CheckAllCanUseParam();
        $user_real = $userObj->checkUserRealStatus($uid);
        if($user_real['code']!=200) {
            $this->ajaxReturn($user_real);
        }
        $where=[
            'uid'=>$this->uid,
            'currency_id'=>$currency_id,
        ];
        $data= ['my_mention_pack_url'.$address_index=> ''];
        $res = M('UserCurrency')->where($where)->save($data);
        if($res){
            $ret['code'] = 200;
            $ret['msg']  = L('_SCDZCG_');
            $this->ajaxReturn($ret);  //成功
        }
        $this->ajaxReturn($ret);    //失败
    }
    /**
     * @author 建强 2019年3月6日 上午9:53:28
     * @method 验证绑定提币地址的参数 
     * @return string json 
    */
    protected function checkBindTiBiUrlParam($address,$address_index,$currency_id,$uid,$memo_url = ''){
        $ret= [
            'code'=>30002,
            'msg' =>L('_DZCDBHG_'),
        ];        
        $address_ret = Tibi::checkAddress($currency_id, $address,$memo_url);
        if($address_ret['code']!=200){
            $this->ajaxReturn($address_ret);
        }
        //地址索引
        if(!in_array($address_index, [1,2,3]) || empty($currency_id)){
            $ret['code'] = 30004;
            $ret['msg'] = L('_FEIFAQQ_');
            $this->ajaxReturn($ret);
        }  
        //币种是否正常上线(币种维护，关闭提币)
        $walletModel  = new WallentModel();
        $currencyInfo = $walletModel->getCurrencyInfoById($currency_id);       
        if($currencyInfo['close_carry']==1 || $currencyInfo['maintain_currency']==1 ){
            $ret['code'] = 30005;
            $ret['msg']  = L('_BZWHZBZBYXCZ_'); //地址维护
            $this->ajaxReturn($ret);
        }       
        //检验是否实名认证
        $userObj   = new CheckAllCanUseParam();
        $user_real = $userObj->checkUserRealStatus($uid);
        if($user_real['code']!=200) {
            $this->ajaxReturn($user_real);
        }
        //地址是否相同
        $field   = 'my_mention_pack_url1,my_mention_pack_url2,my_mention_pack_url3';
        $addInfo = M('UserCurrency')
           ->where(['uid'=>$uid,'currency_id'=>$currency_id])
           ->field($field)
           ->find();
        $addInfo = array_unique(array_values($addInfo));
        $dbAddr  = $addInfo['my_mention_pack_url'.$address_index];
        if(in_array($address,$addInfo) || !empty($dbAddr)){
            $ret['code'] = 40005;
            $ret['msg'] = L('_CDZYBBD_');
            $this->ajaxReturn($ret);
        }
    }
    /**
     * @method 绑定提币地址
     * @author 杨鹏 2019年3月4日15:43:32 
     * @param string currency_id 币种id
     * @param string address_index 地址字段
     * @param string address 地址
     * @return string json
     */
    public function bindTiBiUrl(){
        $ret = [
            'code'=>30001,'msg'=>L('_BDDZSB_'),
        ];
        $currency_id   = I('currency_id');
        $address       = trim(I('address'));
        $address_index = intval(I('address_index'));
        $uid           = $this->uid;
        //eos附选参数 
        $memo_url      = trim(I('memo'));
        //基本参数验证
        $this->checkBindTiBiUrlParam($address,$address_index,$currency_id,$uid,$memo_url);
        //bch,bsv地址验证 
        $result = BchAddressApi::checkBCHaddrByApi($currency_id,$address);
        
        if($result['code']!=200){
            $ret['code'] = 40006;
            $ret['msg'] = $result['msg'];
            $this->ajaxReturn($ret);
        }
        $tibi_url = $result['addr'];
        //eos地址返回
        if($currency_id == C('EOS_ID')){
            $tibi_url = $result['addr'].':'.$memo_url;
        }
        $data= [
            'my_mention_pack_url'.$address_index =>$tibi_url,
            'url_date'.$address_index=>time(),
        ];
        $where= [
            'uid'=>$uid,'currency_id'=>$currency_id,
        ];
        $db_trans = [];
        $model = M();
        $model->startTrans();
        $db_trans[]= M('UserCurrency')->where($where)->save($data);
        //首次绑定加积分
        $userScoreLogModel=M(getTbl('UserScoreLog',$uid));
        $count = $userScoreLogModel->where(['uid'=>$uid,'status'=>4])->count();
        if($count<=0){
            $PublicFun  = new PublicFunctionController();
            $scoreArr   = ['operationType'=>'inc','scoreInfo'=>'第一次绑定地址赠送积分','status'=>4];
            $db_trans[] = $PublicFun->calUserIntegralAndLeavl($uid,10,$scoreArr);
        }
        if(in_array(false, $db_trans)){
            $model->rollback();
            $ret['code'] = 40007;
            $ret['msg'] = L('_BDDZSB_');
            $this->ajaxReturn($ret);  //绑定地址失败
        }
        //成功
        $model->commit();
        $ret['code'] = 200;
        $ret['msg'] = L('_BDDZCG_');
        $ret['data']['addr']=$result['addr'];
        $ret['data']['memo']=$memo_url;
        $this->ajaxReturn($ret);
    }
    /**
     * @author 李江 2017年11月1日20:39:59
     * @method 发送短信接口
     * @param  需要验证图片验证码   
     * @return string json  
     */
    public function sendPhoneCode(){
        /* $code = I('img_code');
        if(empty($code)) $this->ajaxReturn(['status'=>411,'msg'=>L('_TXYZMBNWK_')]);
        $res = $this->checkVerify($code);
        if(empty($res))  $this->ajaxReturn(['status'=>410,'msg'=>L('_TXYZMCW_')]); */
        $sence    = 'TIBI';
        $uid      = $this->uid;
        $userinfo = M('User')->where(['uid'=>$uid])->find();
        $om       = $userinfo['om'];
        $phone    = $userinfo['phone'];
        $sms      = new Yunclode();
        $res      = $sms->ApiSendPhoneCode($uid,$om,$phone,$sence,2,$userinfo['username']);
        if($res==0){
            $this->ajaxReturn(['status'=>200,'msg'=>L('_DXFSCGQCK_')]);//短信发送成功
        }
        if($res == 403 || $res == 413){
            $this->ajaxReturn(['status'=>403,'msg'=>L('_DXFSPLGK_')]); // 短信发送频率过快
        }
        $this->ajaxReturn(['status'=>404,'msg'=>L('_DXFSSB_')]);   //短信发送失败
    }
    /**
     * @author 建强 2019年3月5日 下午5:28:14
     * @method 检验图片验证码
     * @return bool
    */
    private function checkVerify($code, $id = ''){
        $verify = new \Common\Api\VerifyApi();
        return $verify->check($code,$id);
    }
}