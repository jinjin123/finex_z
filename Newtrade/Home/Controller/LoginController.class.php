<?php
/**
 * 登陆相关操作类
 * @author lirunqing 2017-9-27 16:58:30
 */

namespace Home\Controller;

use Think\Controller;
use Home\Logics\LoginCheckController;
use Home\Tools\SceneCode;
use Home\Logics\PublicFunctionController;
use Home\Controller\CheckVerifyController;
use Common\Model\UserWatchwordModel;
use Home\Model\UserMissLogModel;
use Common\Api\Point;
use Common\Api\RedisCluster;
use Common\Api\RedisIndex;
use Home\Model\UserModel;

class LoginController extends Controller
{
    public $tmpl = '../../../Public/Home/fe';

    protected $loginCheckObj = null;
    protected $publicFunctionObj = null;
    private $missNum = 5;

    /**
     * 自动加载方法
     * @return
     * @author lirunqing 2017-10-09T14:49:50+0800
     */
    public function _initialize()
    {
        $this->getObj(); // 自动加载业务所需对象
        // $this->publicFunctionObj->checkWebConfig(); // 检测网站配置
    }

    /**
     * 判断是否登录
     * @return boolean [description]
     * @author liruqing 2018-10-17T10:29:10+0800
     */
    private function isLogin()
    {
        $sessionObj = RedisIndex::getInstance(); // 获取session对象
        $loginInfo = $sessionObj->getSessionValue('LOGIN_INFO');
        if (!empty($loginInfo['USER_KEY_ID'])) {
            $this->redirect("/UserCenter/index");
        }
    }

    /*
     * 登陆方法
     * yangpeng  2018-5-15  添加
     */
    public function showLogin()
    {
        $this->isLogin();// 判断是否登录
        $this->display($this->tmpl . '/userLogin');
    }

    public function checkBox(){
        $model = new UserModel();
        $list  = $model->getCheck();
        echo $list;
    }
    /**
     * AJAX轮训，校验用户是否已经登录
     * 刘富国
     * 20180116
     */
    function QRCheckUserIsLogin()
    {
        // $redisObj = new RedisCluster();
        $redis_client_obj = RedisCluster::getInstance();
        $loginCheckObj = new LoginCheckController();
        $ret = $loginCheckObj->checkUserIsLogin();
        //已经登录
        if ($ret == 200) {
            //检查APP设置的语言版本，跟APP一致
            $userId = getUserId();
            $langSet = $redis_client_obj->get('APP_VAR_LANGUAGE' . $userId);
            if (!empty($langSet)) {
                $expireTime = C('LANG_EXPIRE');
                $expireTime = !empty($expireTime) ? $expireTime : 3600 * 24 * 30;
                cookie('think_language', $langSet, $expireTime);
            }
            $res['code'] = 200;
            $res['msg'] = L('_CHENGGONG_');
            $res['data'] = array(
                'url' => '/UserCenter/index'
            );
            $this->ajaxReturn($res);
        }
        $returnRes = array(
            'msg' => '',
            'code' => $ret,
            'data' => array()
        );
        $this->ajaxReturn($returnRes);
    }

    /**
     * 登陆错误
     */
    public function loginError()
    {
        $this->display();
    }

    /**
     * 登出操作
     * @author 2017-10-25T21:46:15+0800
     */
    public function LoginOut()
    {
        if(IS_AJAX){
            $status = $this->loginCheckObj->loginOut();
            $this->ajaxReturn(['status' => $status]);
        }
        $status = $this->loginCheckObj->loginOut();
        $userId = getUserId();
        if (empty($userId)) {
            $this->redirect('/');
            exit;
        }
    }

    /**
     * 设置用户登录
     * @return json
     * @author lirunqing 2017-10-24T17:28:19+0800
     */
    public function logingIn()
    {
        $data = I('post.');
        // 检测密令是否正确
        // $this->checkAppCode($data);

        $account = trim($data['email']);
        $password = $data['password'];
        $where['username'] = $account;
        $where['pwd'] = passwordEncryption($password);
        $userInfo = M('User')->where($where)->find();

        if (empty($userInfo)) {
            $res['msg'] = 'Username or password is invalid';
            $res['code'] = 201;
            return $this->ajaxReturn($res);
        }

        $userId = $userInfo['uid'];

        // 检查用户是否首次登陆
//        $this->checkUserIsFirstLogin($userInfo);
        // 设置登陆信息
        $this->loginCheckObj->setLogin($userId);
        // 重置错误密码次数
//        $publicFunctionObj = new PublicFunctionController();
//        $publicFunctionObj->updateLoginMiss($userId, 2);

        // 每日首次登添加积分
//        $this->firstLoginAddPoint();
//        $this->setTipsForAlert();//设置tips  yangpeng添加

        $res['code'] = 200;
        $res['msg'] = 'Sign In Succeed';
        $res['data'] = array(
            'url' => 'UserCenter/index'
        );

        $this->ajaxReturn($res);
    }

    /*
     *   设置tips
     * yangpeng 2017-12-21
     */
    public function setTipsForAlert()
    {
        $uid = getUserId();
        $userRealResault = M('UserReal')->where(array('uid' => $uid))->field('status')->find();
        $tips = M('User')->where(['uid' => $uid])->getField('tips');
        if (!$userRealResault || $userRealResault['status'] == -1) {
            if ($tips == 2) {
                M('User')->where(['uid' => $uid])->setField('tips', 1);
            }
        }
    }


    /**
     * 登陆数据处理
     * @return json
     * @author liruqning 2017-10-09T10:03:35+0800
     */
    public function subLogin()
    {
        $data = I('POST.');
        $data['account'] = trim($data['email']);

        // 验证登录参数
        $this->checkLoginParams($data);

        // 检测用户信息
        $this->checkUserInfo($data);

        $this->logingIn();

//        $res = array(
//            'msg' => L('_CHENGGONG_'),
//            'code' => 200,
//            'data' => array()
//        );
//        $this->ajaxReturn($res);
    }

    /**
     * 忘记密码
     */
    public function forgetPassWord()
    {
        $this->display($this->tmpl . '/userLost');
    }

    //修改登录密码
    public function submitForget()
    {
        if (IS_AJAX) {
            $postData = I('post.');
            $username = trim($postData['email']);
            $pass = trim($postData['password']);
            $emailCode = $postData['email_code'];
            $row = M('User')->where([
                'username' => $username,
                'email' => $username,
                '_logic' => 'OR'
            ])->find();
            if (!$row) $this->ajaxReturn(['status' => false, 'msg' => 'Email Not Exist']);

            $key = $username . '_forget_password_email_code';
            $redis = RedisIndex::getInstance();
            $rcode = $redis->getSessionValue($key);
            $msg = $this->checkPass($username, $pass, $emailCode, $rcode);
            if ($msg) $this->ajaxReturn(['code' => 201, 'msg' => $msg]);
            //插入数据表
            $ret = M('User')->where(['uid' => $row['uid']])->save([
                'pwd' => passwordEncryption($pass)
            ]);
            if ($ret) {
                $redis->delSessionRedis($key);
                $this->ajaxReturn(['status' => true, 'msg' => 'Commit Success']);
            }
            $this->ajaxReturn(['status' => false, 'msg' => 'Commit Fail']);
        }
        $this->ajaxReturn(['status' => false, 'msg' => 'Request Method Error']);
    }

    /**检测参数
     */
    protected function checkPass($username, $pass, $emailCode, $rcode)
    {
        $msg = '';
        if (!$username || !$pass || !$emailCode) {
            $msg = 'Incorrect input information';
        }
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Incorrect email address';
        }

        if ($emailCode != $rcode) {
            $msg = 'Email verification code error';
        }
        return $msg;
    }

    //发送邮件
    public function sendEmail()
    {
        $email = I('post.email');
        $code = rand(100000, 999999);
        //判断邮箱是否存在
        $row = M('User')->where(['username' => $email])->find();
        if (empty($row)) return $this->ajaxReturn(['msg' => 'Without this user']);

        $redis = RedisIndex::getInstance();
        $redis->setSessionRedis($email . '_forget_password_email_code', $code);

        $email_data  = M("EmailConf")->find();
        $smtp =[
            'emailHost' => $email_data["emailhost"],
            'formName' => $email_data["formname"],
            'emailPassWord' => $email_data['emailpassword'],
            'emailUserName' => $email_data['emailusername'],
        ];
//        C('smtp')
        $status = sendEmail($smtp, $email, 'Find Password', '[SpaceFinEX]Your verification code is '.$code.'. If it is not your operation, please ignore it.');
        $this->ajaxReturn(['msg' => 'Send ' . ($status ? 'Success' : 'Fail')]);
    }

    /**
     * 检测密令是否正确
     * @param array $data
     * @return int|json
     * @author lirunqing 2017-10-24T17:24:22+0800
     */
    private function checkAppCode($data)
    {

        $account = trim($data['account']);
        $password = $data['password'];
        $oneTimePwd = $data['oneTimePwd'];
        $res = array(
            'code' => 201,
            'msg' => '',
            'data' => array()
        );

        if (empty($oneTimePwd)) {
            $res['msg'] = L('_DTMLBNWK_');
            $this->ajaxReturn($res);
        }


        $where['username'] = $account;
        $where['pwd'] = passwordEncryption($password);
        $userInfo = M('User')->where($where)->find();

        // 验证用户是否存在
        if (empty($userInfo) || empty($userInfo['uid'])) {
            $res['msg'] = L('_YHMHMMBZQ_');
            $this->ajaxReturn($res);
        }

        $userId = $userInfo['uid'];
        $publicFunctionObj = new PublicFunctionController();
        $checkWatchwordStatus = check_watchword($userId, $oneTimePwd);

        // 检测动态密令是否正确
        if (!$checkWatchwordStatus) {
            $publicFunctionObj->setIncNum($userId, 2);
        }

        // 获取动态密令错误次数
        $missRes = $publicFunctionObj->checkUserPassMissNumOrTokenMissNum($userId, 2);

        // 密令错误达到5次，则不能登陆
        if (!empty($missRes) && $missRes >= $this->missNum && !$checkWatchwordStatus) {

            if ($missRes == $this->missNum) {
                // 动态口令错误达到5次以后不能登陆，并推送信息到用户APP上
                $contentStr = SceneCode::getPersonSafeInfoTemplate($userInfo['username'], $userInfo['om'], 7);
                $contentArr = explode('&&&', $contentStr);
                $title = $contentArr[0];
                $content = $contentArr[1];
                push_msg_to_app_person($title, $content, $userId);
            }

            $res['msg'] = L('_CIPDLPFMTZS_');
            $this->ajaxReturn($res);
        }

        // 验证动态密令的正确
        if (!$checkWatchwordStatus) {
            $lastNUm = $this->missNum - $missRes;
            $res['msg'] = L('_DTLPCWA_') . $lastNUm . L('_CJHDL_');
            $this->ajaxReturn($res);
        }

        //记录口令日志
        $userWatchwordModel = new UserWatchwordModel();
        $userWatchwordModel->setTokenUsingLog($userId, $oneTimePwd, 1);
        return $userId;
    }

    /**
     * 获取业务所需对象
     * @return null
     * @author lirunqing 2017-10-09T15:01:01+0800
     */
    protected function getObj()
    {
        $this->loginCheckObj = new LoginCheckController();
        $this->publicFunctionObj = new PublicFunctionController();
    }

    /**
     * 验证登录参数
     * @param array $data
     * @return json
     * @author lirunqing 2017-09-30T14:26:59+0800
     */
    private function checkLoginParams($data = array())
    {

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        // 验证账号
        if (empty($data['account'])) {
            $res['msg'] = 'Username is empty';
            $this->ajaxReturn($res);
        }

        // 验证登录密码
        if (empty($data['password'])) {
            $res['msg'] = 'Password is empty';
            $this->ajaxReturn($res);
        }

        $where['username'] = $data['account'];                    //用户名
        //$where['pwd']      = passwordEncryption($data['password']);//密码加密  passwordEncryption
        $userInfo = M('User')->where($where)->find();

        if ($userInfo['username'] !== $data['account']) {
            $res['msg'] = 'Username or password is invalid';
            $this->ajaxReturn($res);
        }
        // 用户名/密码不正确
        if (empty($userInfo)) {
            $res['msg'] = 'Username is not exist';
            $this->ajaxReturn($res);
        }

//        // 密码错误次数设置
//        $publicFunctionObj = new PublicFunctionController();
//        if (passwordEncryption($data['password']) != $userInfo['pwd']) {
//            $publicFunctionObj->setIncNum($userInfo['uid'], 1);
//        }
//
//        $missRes = $publicFunctionObj->checkUserPassMissNumOrTokenMissNum($userInfo['uid'], 1);
//
//        // 密码错误达到5次，则不能登陆
//        if (!empty($missRes) && $missRes >= $this->missNum) {
//            $res['msg'] = 'Too many logins, please try again later';
//            $this->ajaxReturn($res);
//        }
//
//        // 密码错误次数返回
//        if (passwordEncryption($data['password']) != $userInfo['pwd']) {
//            $lastNUm = $this->missNum - $missRes;
//            $res['msg'] = 'Password error, please try again later.(sign in times: '.$lastNUm.')';
//            $this->ajaxReturn($res);
//        }
    }

    /**
     * 检测用户信息
     * @param array $data
     * @return bool
     * @author lirunqing 2017-09-30T14:55:06+0800
     */
    private function checkUserInfo($data = array())
    {

        $userName = trim($data['email']);
        $password = $data['password'];
        $where['username'] = $userName;                    //用户名
        $where['pwd'] = passwordEncryption($password);//密码加密  passwordEncryption
        $userInfo = M('User')->where($where)->find();

        // 用户不存在或者密码错误
        if (empty($userInfo) || empty($userInfo['uid'])) {
            $res = array(
                'msg' => L('Username or password is invalid'),
                'code' => 201,
                'data' => array()
            );
            $this->ajaxReturn($res);
        }

        $userId = $userInfo['uid'];

        // 检查用户的账号状态和是否超过45天未登陆
//        $this->checkUserStatusAndLastLogin($userInfo);
        // 检测用户是否绑定app
        // $this->checkBindApp($userId);
        // 重置错误密码次数
//        $publicFunctionObj = new PublicFunctionController();
//        $publicFunctionObj->updateLoginMiss($userId, 1);

        return true;
    }

    /**
     * 检测用户是否绑定app
     * @param int $userId [description]
     * @return bool|json
     * @author lirunqing 2017-10-24T16:20:11+0800
     */
    private function checkBindApp($userId)
    {
        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        $userWatchwordModel = new UserWatchwordModel();
        $isBind = $userWatchwordModel->checkUserBind($userId);
        if (empty($isBind)) {
            $res['code'] = 202;
            $this->ajaxReturn($res);
        }

        return true;
    }

    /**
     * 检查用户是否首次登陆
     * @param array $userInfo 用户信息
     * @return json
     * @author lirunqing 2017-09-30T14:58:56+0800
     */
    private function checkUserIsFirstLogin($userInfo = array())
    {
        $returnRes = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        // 查找用户登陆信息
        $userId = $userInfo['uid'];
        $logWhere['uid'] = $userId;
        $tableName = 'UserLoginSameClient';
        $userLoginLog = M($tableName)->where($logWhere)->find();

        // 登陆之前判断是否登录，如果登录先把当前用户登出
        if (getUserId()) {
            $this->loginCheckObj->loginOut($userId);
        }

        $publicFunctionObj = new PublicFunctionController();
        $missRes = $publicFunctionObj->checkUserPassMissNumOrTokenMissNum($userInfo['uid'], 1);

        // 密码错误达到5次，则不能登陆
        if (!empty($missRes) && $missRes >= 5) {

            // 登陆密码错误达到5次以后不能登陆，并推送信息到用户APP上
            if ($missRes == 5) {
                $contentStr = SceneCode::getPersonSafeInfoTemplate($userInfo['username'], $userInfo['om'], 4);
                $contentArr = explode('&&&', $contentStr);
                $title = $contentArr[0];
                $content = $contentArr[1];
                $rs = push_msg_to_app_person($title, $content, $userId);
            }

            $returnRes['msg'] = L('_CIPDLPFMTZS_');
            $this->ajaxReturn($returnRes);
        }

        $missTokenRes = $publicFunctionObj->checkUserPassMissNumOrTokenMissNum($userInfo['uid'], 2);
        // 动态口令错误达到5次，则不能登陆
        if (!empty($missTokenRes) && $missTokenRes >= 5) {
            // 动态口令错误达到5次以不能登陆，并推送信息到用户APP上
            if ($missTokenRes == 5) {
                $contentStr = SceneCode::getPersonSafeInfoTemplate($userInfo['username'], $userInfo['om'], 7);
                $contentArr = explode('&&&', $contentStr);
                $title = $contentArr[0];
                $content = $contentArr[1];
                push_msg_to_app_person($title, $content, $userId);
            }
            $returnRes['msg'] = L('_CIPDLPFMTZS_');
            $this->ajaxReturn($returnRes);
        }

        // 登陆日志信息
        $dengluDataNew['client_token'] = $userId . time(); // 登录信息
        $dengluDataNew['add_time'] = time(); // 当前时间
        $dengluDataNew['status'] = 1; // 在线

        // 判断用户是否是首次登陆
        if (!empty($userLoginLog)) {
            $res = M($tableName)->where($logWhere)->save($dengluDataNew);
        } else {
            // 首次登陆
            $dengluDataNew['uid'] = $userId;// 用户ID
            $res = M($tableName)->add($dengluDataNew);
        }

        // 登录日志入库失败
        if (empty($res)) {
            $this->ajaxReturn($returnRes);
        }
    }

    /**
     * 检查用户的账号状态和是否超过45天未登陆
     * @param array $userInfo 用户信息
     * @return json
     * @author lirunqing 2017-09-30T14:39:55+0800
     */
    private function checkUserStatusAndLastLogin($userInfo = array())
    {
        $userId = $userInfo['uid'];
        $table = 'UserLog';
        $userLogTableName = getTbl($table, $userId);

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        // 保存最后登录时间
        $userLogWhere['uid'] = $userId;
        $userLogData = M($userLogTableName)->where($userLogWhere)->order('id desc')->find();

        $days = 45 * 24 * 3600;
        if ((time() - $userLogData['add_time']) > $days) {
            $res['msg'] = L('_ZHWDLXGCS_');
            $this->ajaxReturn($res);// 超过45天未登陆  3888000秒
        }

        if ($userInfo['status'] == '-2') {
            $res['msg'] = L('_ZHFXLXPT_');
            $this->ajaxReturn($res);// 交易风险
        }

        if ($userInfo['status'] == '-1') {
            $res['msg'] = L('_NDZHBSD_');
            $this->ajaxReturn($res);// 账户锁定
        }

    }
}