<?php
/**
 * 忘记密码相关操作类
 * @author zhanghanwen
 * @time 2017年10月9日11:55:44
 */

namespace Home\Controller;
use Think\Controller;
use Home\Tools\SceneCode;
use Home\Logics\PublicFunctionController;
use  Common\Api\RedisCluster;
use Home\Controller\CheckVerifyController;
use Common\Model\UserTokenModel;
use Home\Model\UserModel;
use Common\Logic\UserLog;

class ForgetPassWordController extends Controller{
    /*
     * 忘记密码显示页面
     * author zhanghanwen
     * @time   2017年10月9日12:16:22
     */
    public function index(){
        $this->display();
    }

    public $redis=NULL;

    /*
     * 提交忘记密码页面
     * author zhaghanwen
     * @time   2017年10月9日12:16:30
     * @return json
     */
    public function subForgetPassWord(){
        if(!IS_POST){
            // 非法请求
            $this->ajaxreturn(array('msg'=>L('_FEIFAQQ_'),'status'=>false));
        }
        $postData = I('post.'); // 接收post 的值
        // 检测各项参数
        $this->checkForgetPassWordSubParam( $postData );
        //检测用户信息
        $uid = getUserIdForUserName( $postData['username'], array( 'phone'=> $postData['phone'] ) );
        if(!$uid) {
            $this->ajaxreturn( array( 'msg'=>L('_YHMSJBPP_'),'status'=>false ) );
        }

        $user = getUserForId( $uid , 'username' );
        if( $user['username'] !== $postData['username'] ) {
            $this->ajaxreturn( array( 'msg'=>L('_YHMSJBPP_'),'status'=>false ) );
        }

        // 检测图片验证码
        // $checkVerifyObj = new CheckVerifyController();
        // if( !$checkVerifyObj->checkVerify( $postData['vercode'] ) ){
        //     $this->ajaxreturn(array('msg'=>L('_TPYZMCW_'),'status'=>false));
        // }
        
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $this->redis ->setex('setForgetPass_'.$uid, 1800, true);
        $this->ajaxreturn( array( 'msg'=>L('_CZCG_'),'status'=>true,'uid'=>$uid ) );
    }

    /*
     * 检查subForgetPassWord方法提交的各项参数
     * @author zhanghanwen
     * @time   2017年10月9日12:15:33
     * @return json
     */
    private function checkForgetPassWordSubParam( $data ){
        if( empty( $data['username']) ){
            $this->ajaxreturn(array('msg'=>L('_QTXYHM_'),'status'=>false));
        }
        if( strpos( $data['username'], ' ') ){
            $this->ajaxreturn(array('msg'=>L('_YHMSJBPP_'),'status'=>false));
        }
        if( empty( $data['phone']) ){
            $this->ajaxreturn(array('msg'=>L('_QTXSJHM_'),'status'=>false));
        }
        if( !is_numeric( $data['phone']) ){
            $this->ajaxreturn(array('msg'=>L('_SJGSBZQ_'),'status'=>false));
        }
        // if( empty( $data['vercode'] ) ){
        //     $this->ajaxreturn(array('msg'=>L('_QTXTPYZM_'),'status'=>false));
        // }
    }

    /*
     * 修改登陆密码提交
     * author zhanghanwen
     * @time  2017年10月9日12:16:47
     */
    public function checkToken(){
        if(!IS_POST){
            // 非法请求
            $this->ajaxreturn(array('msg'=>L('_FEIPQQIU_'),'status'=>false));
        }
        $postData = I('post.'); // 接收post 的值
        $this->checkSubTokenParam( $postData );

        $uid = I('post.uid');
        // redis 集群获取上一步的key
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $validity = $this->redis->get('setForgetPass_'.$uid);
        if( !$validity ){ // 有效期内可以修改密码
            $this->ajaxreturn(array('msg'=>L('_FEIFAQQ_'),'status'=>false));
        }

        // 验证动态密令是否正确
        if( ! check_watchword( $uid, $postData['token'] ) ) {
            $this->ajaxreturn(array('msg'=>L('_DTLPCW_'),'status'=>false));
        }

        // 正确验证
        $this->redis->setex('setForgetPassToken_'.$uid, 1800, true);
        $this->ajaxreturn( array( 'msg'=>L('_CZCG_'),'status'=>true ) );
    }

    /*
     * 检查modifyPassWord方法输入是否符合规范
     * author zhanghanwen
     * @time  2017年10月10日11:26:31
     * @return json
     */
    private function checkSubTokenParam( $data ){
        if( empty( $data['token']) ){
            $this->ajaxreturn(array('msg'=>L('_DTMLBNWK_'),'status'=>false));
        }
        if( empty( $data['uid']) ){
            $this->ajaxreturn(array('msg'=>L('_YHIDBNK_'),'status'=>false));
        }
    }

    /**
     * 设置密码步骤
     * @param $data['password'], $data['reppassword']
     * @author zhanghanwen
     * @time   2017年10月23日17:20:55、
     * @return json 格式
     */
    public function subSetPassWord(){
        $postData = I('post.'); // 接收post 的值

        // redis 集群获取上一步的key
        // $redisObj = new RedisCluster();
        $this->redis = RedisCluster::getInstance();
        $validity = $this->redis->get('setForgetPassToken_'.$postData['uid']);
        if( !$validity ){ // 有效期内可以修改密码
            $this->ajaxreturn(array('msg'=>L('_FEIFAQQ_'),'status'=>false));
        }

        // $redisObj = new RedisCluster();
        // $this->redis = $redisObj->getInstance();

        $hasClick =  $this->redis->get('forget_password_btn_'.$postData['uid']);
        if( $hasClick ){ // 有效期内可以修改密码
            $this->ajaxreturn(array('msg'=>L('_QWCFDJCAN_'),'status'=>false));
        }
        $this->redis->setex('forget_password_btn_'.$postData['uid'],10,1);

        // 检测输入参数
        $this->checkSetPasswordParam( $postData );

        // 修改密码，增加修改日志
        $newPassword = passwordEncryption( $postData['newpwd'] );
        M('user')->where( array( 'uid'=>$postData['uid'] ) )->save( array('pwd'=> $newPassword ) );
        //app退出
        $userLogObj= new UserLog();
        $userLogObj->loginOutApp($postData['uid'],'app_target');
        // 新增登陆日志
        $publicObj = new PublicFunctionController;
        $publicObj->addUserLog($postData['uid'],4,0 );

        // 极光推送
        $userModel = new UserModel();
        $userData = $userModel->getUserInfoForId($postData['uid'], 'phone,om,username');
        $message = SceneCode::getPersonSafeInfoTemplate($userData['username'],$userData['om'],3);
        $data = explode('&&&',$message);
		push_msg_to_app_person($data[0],$data[1],$postData['uid']);
        $this->ajaxreturn( array( 'msg'=>L('_CZCG_'),'status'=>true ) );
    }

    /**
     * 检测设置密码是否符合规范
     * @author zhanghanwen
     * @time   2017年10月23日17:22:30
     * @data   $data['password'],$data['reppassword']
     */
    private function checkSetPasswordParam( $data ){
        if( empty( $data['uid']) ){
            $this->ajaxreturn(array('msg'=>L('_YHIDBNK_'),'status'=>false));
        }
        if( empty( $data['newpwd']) ){
            $this->ajaxreturn(array('msg'=>L('_QSRMM_'),'status'=>false));
        }
        if( empty( $data['repeatpwd'] ) ){
            $this->ajaxreturn(array('msg'=>L('_QCFSRMM_'),'status'=>false));
        }
        if( strlen( $data['newpwd']) < 6 || strlen( $data['repeatpwd']) < 6 ){
            $this->ajaxreturn(array('msg'=>L('_MMCDLDSB_'),'status'=>false));
        }
        if( $data['newpwd'] != $data['repeatpwd'] ) {
            $this->ajaxreturn(array('msg'=>L('_LCMMSRBYZ_'),'status'=>false));
        }
        if( !regex( $data['repeatpwd'], 'password5' ) || !regex( $data['newpwd'], 'password4' ) ){
            $this->ajaxreturn(array('msg'=>L('_ERRZSXCD_'),'status'=>false));
        }
    }

}