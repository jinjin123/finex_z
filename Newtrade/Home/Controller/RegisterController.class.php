<?php
/**
 * 注册相关操作类
 * @author lirunqing 2017-9-27 17:04:10
 */

namespace Home\Controller;

use Think\Controller;
use Home\Tools\SceneCode;
use Home\Model\UserModel;
use Home\Controller\CheckVerifyController;
use Home\Logics\PublicFunctionController;
use Common\Api\RedisIndex;
use Common\Api\Point;
use Common\Api\MobileReg;
use Org\Net\IpLocation;

class RegisterController extends Controller
{
    public $tmpl = '../../../Public/Home/fe';

    public function _initialize()
    {
        $sessionObj = RedisIndex::getInstance(); // 获取session对象
        $loginInfo = $sessionObj->getSessionValue('LOGIN_INFO');

        if (!empty($loginInfo['USER_KEY_ID'])) {
            $this->redirect("UserCenter/index");
        }
    }

    public function index()
    {
        $this->display($this->tmpl . '/userRegister');
    }

    public function terms()
    {
        $this->display($this->tmpl . '/terms');
    }

    public function userLogin()
    {
        $this->display($this->tmpl . '/userLogin');
    }

    /**
     * 检测某注册参数的正确性
     * @return [type] [description]
     * @author 2017-10-23T15:56:47+0800
     */
    public function checkRegisterParam()
    {

        $param = I('post.param');
        $val = I('post.val');
        $val = trim($val);
        $type = I('post.type');
        $checkArr = array(
            'username' => 'username',
            'mobile-number' => 'phone',
            'password' => 'pwd'
        );
        $msgArr = array(
            'username' => L('_YHMBZY_'),
            'mobile-number' => L('_SJHMBZY_')
        );

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array(),
        );

        // 判断是否是固定的参数
        if (!array_key_exists($param, $checkArr)) {
            $res['msg'] = L('_SJYCQSHCS_');
            $this->ajaxReturn($res);
        }

        // 检测用户名的合法性
        if ($type == 1) {
            $this->checkUsername($val);
        } else if ($type == 2) {// 检测手机号码的合法性
            $om = I('post.phoneOm');
            $this->checkUserPhone($val, $om);
        } else if ($type == 3) {// 检测密码的合法性
            $this->checkUserPwd($val);
        }

        $res['msg'] = L('_CHENGGONG_');
        $res['code'] = 200;

        $this->ajaxReturn($res);
    }

    /**
     * 处理注册表单相关数据
     * @return [type] [description]
     * @author lirunqing 2017-09-27T17:06:46+0800
     */
    public function subRegister()
    {
        $postData = I('post.');
        $username = trim($postData['email']);
        $pass = trim($postData['password']);
//        $code = trim($postData['code']);
        $emailCode = $postData['email_code'];

        $key = $username . '_register_email_code';

        $redis = RedisIndex::getInstance();
        $rcode = $redis->getSessionValue($key);

        if ($rcode != $emailCode) return $this->ajaxReturn(['msg' => 'The email verification code is incorrect']);

//        if ($code == '') return $this->ajaxReturn(['msg' => 'Invite code cannot be empty']);
        if ($username == '') return $this->ajaxReturn(['msg' => 'The mailbox cannot be empty']);
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) return $this->ajaxReturn(['msg' => 'Incorrect email address']);
        if ($pass == '') return $this->ajaxReturn(['msg' => 'The password cannot be empty']);

        //判断邮箱是否存在
        $row = M('User')->where([
            'username' => $username,
            'email' => $username,
            '_logic' => 'OR'
        ])->find();
        if (!empty($row)) return $this->ajaxReturn(['msg' => 'The mailbox has been registered']);

        $addData['username'] = $addData['email'] = $username;
        $addData['pwd'] = passwordEncryption($pass);
        $addData['status'] = 1;        //状态为1可登录
//        $addData['invite_code'] = '';
        $addData['reg_time'] = time();

        //判断邀请码是否存在
//        if ($code != '') {
//            $row = M('AdminUser')->where(['invite_code' => $code])->find();
//            if (empty($row)) return $this->ajaxReturn(['msg' => 'The invitation code does not exist']);
//            $addData['invite_code'] = $code;
//        }

        M()->startTrans();

        // 新增用户
        $userModel = new UserModel();
        $userId = $userModel->add($addData);

        if (empty($userId)) {
            M()->rollback();
            $result['msg'] = L('Sign Up Fail');
            $this->ajaxReturn($result);
        }

//        //更新邀请码
//        M('User')->where(['uid' => $userId])->save([
//            'invite_code' => $userId.rand(1000, 9999)
//        ]);

        $r[] = $this->addUserLog($userId);
        $r[] = $this->addCurrencyInfo($userId);
        $r[] = $this->addCcComplete($userId);

        if (in_array(false, $r)) {
            M()->rollback();
            $result['msg'] = L('_ZCSBSHCS_');
            $this->ajaxReturn($result);
        }

        $result = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

//        $publicObj = new PublicFunctionController;

//        $res = $publicObj->calUserIntegralAndLeavl($userId, Point::ADD_PHONE, array('operationType' => 'inc', 'scoreInfo' => '綁定手机赠送积分', 'status' => 1));
//
//        if (empty($res)) {
//            M()->rollback();
//            $result['msg'] = L('_ZCSBSHCS_');
//            $this->ajaxReturn($result);
//        }
        M()->commit();

        // 删除邮箱验证码
        $redis->delSessionRedis($key);

        $result['code'] = 200;
        $result['msg'] = 'Registered successfully';
        $this->ajaxReturn($result);
    }

    public function sendEmail()
    {
        $email = I('post.email');
        $code = rand(100000, 999999);

        //判断邮箱是否存在
        $row = M('User')->where(['username' => $email])->find();
        if (!empty($row)) return $this->ajaxReturn(['msg' => 'The mailbox has been registered']);

        $redis = RedisIndex::getInstance();
        $redis->setSessionRedis($email . '_register_email_code', $code);
		$email_data  = M("EmailConf")->find();
		$smtp =[
			'emailHost' => $email_data["emailhost"],
			'formName' => $email_data["formname"],
			'emailPassWord' => $email_data['emailpassword'],
			'emailUserName' => $email_data['emailusername'],
		];
        $status = sendEmail($smtp, $email, 'Email Verify Code', '[SpaceFinEX]Your verification code is '.$code.'. If it is not your operation, please ignore it.');
        $this->ajaxReturn(['msg' => 'Send ' . ($status ? 'Success' : 'Error')]);
    }

    /**
     * c2c模式完成率表添加一条记录
     * @param int $userId 用户id
     * @return bool
     * @author liruqing 2018-05-04T16:23:20+0800
     */
    private function addCcComplete($userId)
    {

        $addArr = [
            'uid' => $userId,
            'add_time' => time()
        ];

        $ccRes = M('CcComplete')->add($addArr);

        if (empty($ccRes)) {
            return false;
        }

        return true;
    }

    /**
     * 添加日志
     * @param int $userId 用户id
     * @return bool
     * @author lirunqing 2017-10-24T16:50:34+0800
     */
    private function addUserLog($userId)
    {

        if (empty($userId)) {
            return false;
        }

        $userLog = array();

        // user_log
        $userLog['uid'] = '';
        $userLog['ip'] = get_client_ip();
        $userLog['add_time'] = time();
        $userLog['status'] = 0;
        $userLog['type'] = 6;     //加入记录 类型 注册
        $userLog['url'] = '';

        //入user_log 分表
        $userLog['uid'] = $userId;
        $tableName = getTbl('UserLog', $userId);
        $resLog = M($tableName)->add($userLog);

        if (empty($resLog)) {
            return false;
        }

        return true;
    }

    /**
     * 添加用户日志及初始用户钱包信息
     * @param int $userId 用户id
     * @return bool
     * @author liruqning 2017-09-29T14:50:54+0800
     */
    private function addCurrencyInfo($userId)
    {

        if (empty($userId)) {
            return false;
        }

        //初始化数据
        $userCurrencyData = $allData = array();

        //user_currency
        $userCurrencyData['uid'] = '';
        $userCurrencyData['currency_id'] = '';
        $userCurrencyData['num'] = 0;
        $userCurrencyData['forzen_num'] = 0;
        $userCurrencyData['my_charge_pack_url'] = '';
        $userCurrencyData['my_mention_pack_url'] = '';

        $currency = M('Currency')->where(['status' => 1])->select();

        // 添加用户钱包数据 user_currency
        foreach ($currency as $key => $value) {
            $userCurrencyData['uid'] = $userId;
            $userCurrencyData['currency_id'] = $value['id'];
            $allData[] = $userCurrencyData;
        }

        $userCurrencyId = M('UserCurrency')->addAll($allData);

        if (empty($userCurrencyId)) {
            return false;
        }

        return true;
    }

    /**
     * 检测手机验证码和图片验证码
     * @param string $verfiycode 图片验证码
     * @param string $phoneCode 手机验证码
     * @param string $phoneNum 手机号码
     * @return
     * @author lirunqing 2017-09-28T10:48:37+0800
     */
    private function checkPhoneCodeAndImgCode($verfiycode, $phoneCode, $phoneNum)
    {

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        if (empty($verfiycode)) {
            // 图片验证码为空
            $res['msg'] = L('_TPYZMBNWK_');
            $this->ajaxReturn($res);
        }

        // 图片验证码错误
        $actionCode = new CheckVerifyController();
        if (!$actionCode->checkVerify($verfiycode)) {
            $res['msg'] = L('_TPYZMCW_');
            $this->ajaxReturn($res);
        }

        if (empty($phoneCode)) {
            // 手机验证码为空
            $res['msg'] = L('_SJYZMBNWK_');
            $this->ajaxReturn($res);
        }

        // 手机验证码错误
        if (!checkSmsCode(0, $phoneNum, SceneCode::HOME_SMS_CODE_REGISTER, $phoneCode)) {
            $res['msg'] = L('_SJYZMCW_');
            $this->ajaxReturn($res);
        }
    }

    /**
     * 检测用户名的合法性
     * @param string $username 用户名
     * @return json
     * @author lirunqing 2017-10-25T20:00:59+0800
     */
    private function checkUsername($username)
    {

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        if (empty($username)) {
            // 用户名为空
            $res['msg'] = L('_YHMBNWK_');
            $this->ajaxReturn($res);
        }

        if (strlen($username) < 6 || strlen($username) > 18) {
            // 用户名为空
            $res['msg'] = L('_YHMGSCWCD_');
            $this->ajaxReturn($res);
        }

        $firstStr = substr($username, 0, 1);
        $firstStr = ord($firstStr);
        // 首字符必须是字母，不能是其他字符
        if ($firstStr < 65 || ($firstStr > 90 && $firstStr < 97) || $firstStr > 122) {
            $res['msg'] = L('_YHMSZMSZM_');
            $this->ajaxReturn($res);
        }

        if (!regex($username, 'username')) {
            // 用户名格式错误
            $res['msg'] = L('_YHMSYZSZZH_');
            $this->ajaxReturn($res);
        }

        //排除特殊字符
        if (stripos($username, "admin") !== false || stripos($username, "vip") !== false || stripos($username, 'test') !== false ||
            stripos($username, "administrator") !== false || stripos($username, "administrators") !== false ||
            stripos($username, "root") !== false) {
            // 用户名不能含有敏感字符
            $res['msg'] = L('_YHMBHMGZF_');
            $this->ajaxReturn($res);
        }

        $userModel = new UserModel();

        //验证用户名是否存在
        if ($userModel->checkUsername($username)) {
            $res['msg'] = L('_YHMBZY_');
            $this->ajaxReturn($res);
        }
    }

    /**
     * 检测用户手机号码的合法性
     * @param string $phoneNum 用户手机号码
     * @return json
     * @author lirunqing 2017-10-25T20:02:49+0800
     */
    private function checkUserPhone($phoneNum, $om)
    {

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        if (empty($phoneNum)) {
            // 手机号码为空
            $res['msg'] = L('_SJHMBNWK_');
            $this->ajaxReturn($res);
        }

        $rulesArr = MobileReg::$validator_rules;
        $om = str_replace('+', '', $om);
        $rule = $rulesArr[$om]['RE'];

        // 台湾手机格式错误
        if ($om == "886" && strlen($phoneNum) != 9) {
            // 手机号码格式错误
            $res['msg'] = L('_SJHMGSCW_');
            $this->ajaxReturn($res);
        }

        // 香港手机格式错误
        if ($om == "852" && strlen($phoneNum) != 8) {
            // 手机号码格式错误
            $res['msg'] = L('_SJHMGSCW_');
            $this->ajaxReturn($res);
        }

        if (!preg_match($rule, $om . $phoneNum)) {
            // 手机号码格式错误
            $res['msg'] = L('_SJHMGSCW_');
            $this->ajaxReturn($res);
        }

        // 手机号码被占用
        $userModel = new UserModel();
        if ($userModel->checkUserPhone($phoneNum)) {
            $res['msg'] = L('_SJHMBZY_');
            $this->ajaxReturn($res);
        }
    }

    /**
     * 检测用户的密码格式正确性
     * @param string $pass
     * @return json
     * @author lirunqing 2017-10-30T17:08:21+0800
     */
    private function checkUserPwd($pass)
    {

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        if (empty($pass)) {
            // 登录密码为空
            $res['msg'] = L('_DLMMBNK_');
            $this->ajaxReturn($res);
        }

        $firstStr = substr($pass, 0, 1);
        $firstStr = ord($firstStr);
        // 密码首字母必须大写
        if ($firstStr < 65 || $firstStr > 90) {
            // 登录密码格式错误
            $res['msg'] = L('_MMSZMBXDX_');
            $this->ajaxReturn($res);
        }

        if (strlen($pass) < 6 || strlen($pass) > 18) {
            // 用户名为空
            $res['msg'] = L('_MMGSCW_');
            $this->ajaxReturn($res);
        }

        if (!regex($pass, 'password5') || !regex($pass, 'password4')) {
            // 登录密码格式错误
            $res['msg'] = L('_SZMDXSZFHZC_');
            $this->ajaxReturn($res);
        }
    }

    /**
     * 检测注册提交表单数据
     * @param array $data [description]
     * @return [type]       [description]
     * @author lirunqing 2017-09-27T17:20:27+0800
     */
    private function checkPostRegData($data = array())
    {

        $username = trim($data['username']);
        $pass = trim($data['password']);
        $repass = trim($data['repassword']);
        $om = trim($data['om']);
        $phoneNum = trim($data['phoneNum']);
        $verfiycode = trim($data['verfiycode']);
        $phoneCode = trim($data['phoneCode']);

        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array()
        );

        // 检测用户名的合法性
        $this->checkUsername($username);

        // 检测用户密码的合法性
        $this->checkUserPwd($pass);

        // if(empty($repass)){
        // 	// 确认密码为空
        // 	$res['msg'] = L('_QRDLMMBNK_');
        //  	     	$this->ajaxReturn($res);
        // }

        // if(!regex($repass, 'password5')||!regex($repass, 'password4')){
        // 	// 确认密码格式错误
        // 	$res['msg'] = L('_QRMMGSCW_');
        //  	     	$this->ajaxReturn($res);
        // }

        if ($pass != $repass) {
            // 两次密码不相同
            $res['msg'] = L('_QRDLYDLMMBYZ_');
            $this->ajaxReturn($res);
        }

        if (empty($om)) {
            // 区号为空
            $res['msg'] = L('_QHBNWK_');
            $this->ajaxReturn($res);
        }

        // 检测用户手机号码的合法性
        $this->checkUserPhone($phoneNum, $om);

        // 检测用户手机验证码及图片验证码的合法性
        $this->checkPhoneCodeAndImgCode($verfiycode, $phoneCode, $phoneNum);
    }
}
