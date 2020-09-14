<?php
/**
 * 登录公共方法
 * @author lirunqing 2017年10月9日10:20:20
 */

namespace Home\Logics;

use Think\Controller;
use Common\Api\RedisIndex;
use Home\Model\UserMissLogModel;

class LoginCheckController extends Controller
{

    protected $sessionObj = null;

    public function _initialize()
    {
        $this->sessionObj = RedisIndex::getInstance(); // 获取session对象
    }

    /**
     * 检测用户是否登录及是否多台设备登录账户
     * @return int 200表示成功，201表示登陆失效，202表示多设备登陆
     * @author lirunqing 2017-10-09T10:51:10+0800
     */
    public function checkUserIsLogin()
    {

        $loginInfo = $this->sessionObj->getSessionValue('LOGIN_INFO');
        $loginOk = $loginInfo['DENGLU_OK'];
        $userId = $loginInfo['USER_KEY_ID'];

        if ((empty($loginOk) || empty($userId))) {
            // $this->email($loginInfo, 1);
            return 201;
        }

        // 查找登陆信息  登录时间  ip 地址
        $where['uid'] = $userId;
        $tableName = 'UserLoginSameClient';
        $loginData = M($tableName)->where($where)->find();

        // 登录信息发送改变时，跳转首页
        if (!empty($loginData) && $loginData['client_token'] != $loginOk) {
            // $this->email($loginInfo, 2);
            $this->abnormalLoginOut($userId);
            return 202;
        }

        $userMissLogModel = new UserMissLogModel();
        $userMissInfo = $userMissLogModel->getMissInfo($userId);
        // 如果账号已经登陆，但是在其他地方继续登陆，如果密码或者动态口令错误达到5次以上，则已经登陆的设备需要退出登陆
        if ($userMissInfo['token_miss_num'] >= 5 || $userMissInfo['pass_miss_num'] >= 5) {
            $this->abnormalLoginOut($userId);
            return 203;
        }

        return 200;
    }

    // test
    public function email($data, $type)
    {
        $arr = [
            'emailHost' => 'smtp.exmail.qq.com',            //发送邮件选择的主机域名
            'emailPassWord' => 'Qiang1990525',              //邮件账号的密码
            'emailUserName' => 'jianqiang.song@winads.cn',  //邮件发的的账号
            //'formName'=>'jianqiang.song@winads.cn',       //邮件发送用户名
            'formName' => '夺标php组',       //邮件发送用户名

        ];

        $testArr = serialize($data);
        $email = '511782353@qq.com';
        $title = "邮件发送的标题测试";
        $body = "打印内容:" . $testArr . ' type：' . $type;

        //common 下的function 邮件发送公共方法
        $res = sendEmail($arr, $email, $title, $body);
        // var_dump($res);  //bool true为发送成功
    }

    /**
     * 退出登录
     * @param int $userId 用户id
     * @return bool
     * @author lirunqing 2017-10-09T10:57:20+0800
     */
    public function loginOut($userId = 0)
    {

        $loginInfo = $this->sessionObj->getSessionValue('LOGIN_INFO');

        if (empty($loginInfo['USER_KEY_ID'])) {
            return false;
        }

//        // 查找用户登陆信息
//        $userId = !empty($userId) ? $userId : $loginInfo['USER_KEY_ID'];
//        $where['uid'] = $userId;
//        $tableName = 'UserLoginSameClient';
//        $loginRes = M($tableName)->where($where)->find();
//
//        if (empty($loginRes)) {
//            return false;
//        }
//
//        $loginDataNew['client_token'] = get_client_ip(); // 获得客户端IP地址
//        $loginDataNew['add_time'] = time(); // 当前时间
//        $loginDataNew['status'] = 0; // 下线
//        $loginLogRes = M($tableName)->where($where)->save($loginDataNew);
//
//        if (empty($loginLogRes)) {
//            return false;
//        }

        $this->sessionObj->delSessionRedis('LOGIN_INFO');

        return true;
    }

    /**
     * 修改交易密码后需要重新验证交易密码
     * @return [type] [description]
     * @author lirunqing 2017-08-23T16:26:46+0800
     */
    public function loginOutTradePwd()
    {
        $this->sessionObj->delSessionRedis('checkTPwdCode');
    }

    /**
     * 设置登陆信息到session
     * @param int $userId 用户id
     * @return bool
     * @author liruqning 2017-10-09T10:24:46+0800
     */
    public function setLogin($userId)
    {

        if (empty($userId)) {
            return false;
        }

        $loginDataIp = $userId . time();
        $loginSessionArr = array(
            'DENGLU_OK' => $loginDataIp,
            'USER_KEY_ID' => $userId,
            'LOGIN_EXPIRE' => time() //设置登陆有效日期
        );

        $this->sessionObj->setSessionRedis('LOGIN_INFO', $loginSessionArr);

        // 添加用户登陆日志
        $PublicFunctionObj = new PublicFunctionController();
        $PublicFunctionObj->addUserLog($userId, 1, 1);

        return true;
    }

    /**
     * 多台电脑登录当前账号，已最新账号为准，其他账号做退出操作
     * @return null
     * @author lirunqing 2017-10-9 10:50:07
     */
    protected function abnormalLoginOut()
    {
        $this->sessionObj->delSessionRedis('LOGIN_INFO');
    }
}