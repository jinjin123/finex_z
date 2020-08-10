<?php
namespace Home\Controller;
use AppTarget\Service\V100\SignService;
use Common\Library\Tool\Token;
use Think\Controller;
use Home\Tools\SceneCode;
use Home\Sms\Yunclode;
use Common\Api\RedisCluster;
use Common\Api\RedisIndex;
use Home\Tools\HttpCurl;
use Org\Net\IpLocation;
use Org\Net\Ip;
use Think\Exception;
use Think\Log;
use Home\Logics\LoginCheckController;
use Home\Logics\ChartData;
use Home\Logics\PublicFunctionController;
use Common\Api\redisKeyNameLibrary;
use Common\Api\MobileReg;



/**前台测试类文件  继承框架基类
 * @author 宋建强
 * @Date  2017年9月25日 16:52
 */
class TestController extends Controller {
    public $id;
    protected $public_obj = null;
    //php环境
    
    
    public function info(){
        phpinfo();
    }

    function getImg(){
        $files = '<p>是佛擋殺佛是<img src=\"/Upload/Back/2019-07-26/1564123813670628.png\" title=\"1564123813670628.png\" alt=\"image.png\"/></p>';
     //  p(strtr($files,'src=\"','src=\"http://192.168.2.228:1338'));
        $str = str_replace('src=\"','src="http://192.168.2.228:1338',$files);
        $str = str_replace('\" title','" title',$str);
    echo($str);
    }


    /**
     * 消息推送 刘富国
     * 2019-7-17
     * @return bool
     */
    public function testPush(){
        p('======');
        $username = I('username');
        if(empty($username) ) {
            p('无用户名');
            return false;
        }
        $where_user['username'] = $username;
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('无此用户');
            return false;
        }
        $uid = $user_info['uid'];

        $title      = '开屏3';
        $content    = '测试开屏收消息3';
        $extras['send_modle']        = 'P2P';
        $extras['new_order_penging'] = '1';
        $ret = push_msg_to_app_person($title,$content,$uid,$extras,$app_platform = 'app_target');
        p($ret);
    }


    /**
     * 解绑用户 刘富国
     * 2017-11-08
     * @return bool
     * todo 方便测试用的，上线后要删
     */
    public function test_unbundling(){
        $username = I('username');
        if(empty($username) ) {
            p('无用户名');
            return false;
        }
        $where_user['username'] = $username;
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('无此用户');
            return false;
        }
        $uid = $user_info['uid'];
        $where_token['uid'] = $uid;
        $user_token   = M('user_watchword')->where($where_token)->find();
        if(empty($user_token) ) {
            p('绑定表没记录这用户');
            return false;
        }
        $token_data['secret_key'] = '';
        $token_data['serial_num'] = '';
        $token_data['is_bind'] = 0;
        M('user_watchword')->where(array('uid' => $uid))->save($token_data);
        // $redisObj = new RedisCluster();
        $redis_client_obj = RedisCluster::getInstance();
        $redis_client_obj->del('setUnbundlingUserToken'.$uid);
        p('解绑成功');
    }

    //解封用户
    function releaseUser(){
        // $redisObj = new RedisCluster();
        $redis = RedisCluster::getInstance();
        $this->public_obj = new PublicFunctionController();
        $username = I('username');
        $uid = I('uid');
        $where_user['username'] = $username;
        if(!empty($uid)) $where_user['uid'] = $uid;
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('===== 无此扑街 ====');
            return false;
        }
        $uid = $user_info['uid'];
        $redis->del('setNewTradePassword'.$uid);// 解封资金密码24小时限制
        $token_data['cc_break_time'] = 0;
        $token_data['break_order_time'] = 0;
        $token_data['cc_break_num'] = 0;
        $ret1[] = M('cc_complete')->where(array('uid' => $uid))->save($token_data);
        $user_data['status'] = 1;
        $user_data['overtime_time'] = 0;
        $user_data['overtime_num'] = 0;
        $user_data['trade_pwd_missnum'] = 0;

        $ret2[] = M('user')->where(array('uid' => $uid))->save($user_data);
        $user_miss_log['pass_miss_num'] = 0;
        $user_miss_log['token_miss_num'] = 0;
        $ret3[] = M('user_miss_log')->where(array('uid' => $uid))->save($user_miss_log);
        $this->public_obj->updateLoginMiss($uid,1);// 重置错误密码次数
        $this->public_obj->updateLoginMiss($uid,2);// 重置错误口令次数
        $redis->del(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$uid); // 重置错误口令次数
        $redis->del(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$uid); // 重置错误密码次数
        if(!$ret1 or !$ret2  or !$ret3){
            p('===== 解封失败 ====');
            return false;
        }
        p('===== 解封成功 ====');
    }


    /**
     * 让用户失信
     * 刘富国
     * 20190826
     */
    public function overtime(){
        $username = I('username');
        $uid = I('uid');
        $where_user['username'] = $username;
        if(!empty($uid)) $where_user['uid'] = $uid;
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('===== 无此扑街 ====');
            return false;
        }
        $uid = $user_info['uid'];
        $user_data['status'] = '-2';
        $user_data['overtime_time'] =  time();
        $user_data['overtime_num'] = 4;
        $ret1[] = M('user')->where(array('uid' => $uid))->save($user_data);
        if(!$ret1  ){
            p('===== 失信封号失败 ====');
            return false;
        }
        p('===== 失信封号成功 ====');
    }



    /**
     * 绑定用户 刘富国
     * 2017-11-08
     * @return bool
     * todo 方便测试用的，上线后要删
     */
    public function test_bundling(){
        $username = I('username');
        $uid = I('uid');
        if(empty($username) and empty($uid)) p('请输入username 或者 uid');

        if(!empty($uid)){
            $where_user['uid'] = $uid;
        }
        if(!empty($username)){
            $where_user['username'] = $username;
        }
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('无此用户1');
            return false;
        }
        $uid = $user_info['uid'];
        $where_token['uid'] = $uid;
        $token_data['secret_key'] = 1;
        $token_data['serial_num'] = 2;
        $token_data['is_bind'] = 1;
        $user_token   = M('user_watchword')->where($where_token)->find();
        if(empty($user_token) ) {
            $token_data['uid'] = $uid;
            $token_data['phone_imei'] = '---';
            $token_data['add_time'] = time();
            M('user_watchword')->add($token_data);
        }else{
            M('user_watchword')->where(array('uid' => $uid))->save($token_data);
        }
        // $redisObj = new RedisCluster();
        $redis_client_obj = RedisCluster::getInstance();
        $redis_client_obj->del('setUnbundlingUserToken'.$uid);
        p('绑定成功');
    }

    /**
     * 校验口令
     * @return bool
     */
    function  test_check_watchword(){
        $username = I('username');
        $uid = I('uid');
        $where_user['username'] = $username;
        if(!empty($uid)) $where_user['uid'] = $uid;
        $user_info = M('User')->where($where_user)->find();
        if(empty($user_info) ) {
            p('===== 无此扑街 ====');
            return false;
        }
        $uid = $user_info['uid'];
        //获取redis缓存中的数据
        $redis_client_obj = RedisCluster::getInstance();
        $current_time_key='WATCHWORD_CURRENT_TIME_'.$uid;
        $current_time = $redis_client_obj->get($current_time_key); //解密初始时间戳
        if(empty($current_time))  {
            p('===== 该用户无申请时间戳 ====');
            return false;
        } ;
        $secret_key = $redis_client_obj->get('USER_SECRET_KEY_'.$uid.$current_time); //解密密钥
        $now_time = time();
        $now_current_time = floor(($now_time-$current_time)/30)*30 + $current_time; //每30秒累加
        $p_token = md5(C('TOKENSUFFIX').$now_current_time.$secret_key);
        $this_wathchword = substr($p_token,5,6);
        p('$uid==='.$uid);
        p('$secret_key==='.$secret_key);
        p('$current_time==='.$current_time);
        p('$this_wathchword==='.$this_wathchword);
    }

}
