<?php
/**
 * 底层基类控制器
 * @author lirunqing 2017年9月25日17:04:34
 */
namespace Home\Logics;
use Think\Controller;
use Home\Model\ConfigModel;
use Common\Api\RedisIndex;
use Home\Model\NoticeModel;
use Common\Model\UserWatchwordModel; 
use Home\Model\CurrencyModel;
use Common\Api\Maintain;

class CommonController extends Controller {

	protected $userInfo      = array();
	protected $configList    = array();
	protected $loginCheckObj = null;

	/**
	 * 自动加载文件
	 * @author lirunqing 2017-9-30 10:03:34
	 * @return 
	 */
	public function _initialize(){
		$this->setObj();// 获取业务对象
		$this->checkUserIsLogin();// 检测用户是否登录
		$this->setConfig(); // 获取网站配置信息
		$this->checkCurrencyCount($this->userInfo['uid']);// 检测用户的个人钱包币种是否和网站币种数量匹配，如果不匹配则增加
        //todo 方便压测，上线后要恢复
     //   $this->isBindMobileToken();// 检测是否绑定手机密令
        $this->getP2PTradingList();// 获取p2p交易币种
		$this->getNotice(); // 获取新闻
		$this->getRealStatus();// 检测是否通过实名认证
        $this->checkIsWalAndFin();// 检测当前页面是否是钱包和财务页面，如果是，则不显示切换交易模式
	}
	
	// 是否实名认证
	private function getRealStatus(){
        $uid    = getUserId();
        $arr    = array('1'=>1,'0'=>2,'-1'=>-1);
            
        $userRealResault = M('UserReal')->where( array( 'uid'=>$uid ) )->find();
        $tips = M('User')->where(['uid'=>$uid])->getField('tips');  //建强补充
        
        if( isset( $userRealResault['status'] ) ){
            $userRealStatus = $arr[$userRealResault['status']];
            $realStatus     = $userRealResault['status'];
        }else{
            $userRealStatus = 0;
            $realStatus     = 9;// 没有上传则为9
        }

        // 距离护照快30天的时候，提醒用户更换护照
        if (!empty($userRealResault) && $userRealResault['status'] == 1 && ($userRealResault['expire_time'] - (time()+86400*30)) < 0) {
           $realStatus = 8;// 护照快要过期，提醒用户更换护照
        }

        // 护照过期
        if (!empty($userRealResault) && $userRealResault['status'] == 1 &&  $userRealResault['expire_status'] == 3) {
            $realStatus = 7;// 护照已经过期，提醒用户更换护照
        }

        // 护照过期
        if (!empty($userRealResault) && $userRealResault['status'] == 1 && (time() - $userRealResault['expire_time'] >= 0) && $userRealResault['expire_status'] != 3) {
            M('UserReal')->where(['uid' => $uid])->save(['expire_status' => 3]);
            $realStatus = 7;// 护照已经过期，提醒用户更换护照
        }

        $this->assign('userRealStatus', $userRealStatus );  
        $this->assign('tips', $tips );     
        $this->assign('yy_realStatus',$realStatus);//没有上传则为9;护照快要过期,提醒用户更换护照则为8;护照已经过期,提醒用户更换护照则为7;
        $this->assign('userReal', $userRealResault );
	}

    /**
     * 获取网站维护信息
     * @author lirunqing 2019-02-26T12:05:05+0800
     * @param  int $type  交易模块类型值
     * @return array
     */
    public function getWebMaiantainInfo($type){

        $maintainInfo = Maintain::getTradeMaintainVals($type);

        return $maintainInfo;
    }
	
	// 获取新闻 
	// 张锡文
	private function getNotice(){
		$languange = 'zh-tw';
        if(!empty( $_COOKIE['think_language'] )) {
            $languange = $_COOKIE['think_language'];
        }
        $fields = "`id`,`title` as title";
        $noticeModel = new NoticeModel();
        $notice = $noticeModel->getNoticeList(null, 5, $fields );
        $this->assign('noticeCount', count($notice['list']));
        foreach ($notice['list'] as $k => $val) {
            $notice['list'][$k]['url'] = U('Notice/details', array('notice_id' => $val['id']));
            $notice['list'][$k]['title'] = mb_substr( $notice['list'][$k]['title'] , 0,17).'...';
        }
		$this->assign('notice', $notice['list'] );
	}

    /**
     * 检测当前页面是否是钱包和财务页面，如果是，则不显示切换交易模式
     * @author lirunqing 2017-12-14T11:19:22+0800
     */
    private function checkIsWalAndFin(){
        $ruteUrl = CONTROLLER_NAME.'/'.ACTION_NAME;
        $urlArr  = array(
            'Wallent/index',
            'UserFinace/showFinace',
        );

        if (in_array($ruteUrl, $urlArr)) {
            $this->assign('is_show_change_trade', 1);
        }
    }

    /**
     * 获取交易模式
     * @author liruniqng 2017-12-11T10:08:58+0800
     */
    private function getP2PTradingList(){

        $currencyModel = new CurrencyModel();
        $currencyList = $currencyModel->getCurrencyList('currency_name,status,id,currency_logo');
        foreach ($currencyList as $key => $value) {
            if ($value['id'] == 6 || $value['status'] != 1) {
                unset($currencyList[$key]);
            }
        }

        $ruteUrl = CONTROLLER_NAME.'/'.ACTION_NAME;
        $urlArr  = array(
            'UserCenter/index'      => 0,
            'CurrencyTrading/index' => 1,
            'CtoCTransaction/index' => 2
        );

        $sessionObj = RedisIndex::getInstance(); // 获取session对象
        $loginInfo  = $sessionObj->getSessionValue('LOGIN_INFO');

        // 交易模式切换，则记住当前交易模式方便在其他页面返回到当前交易模式的首页
        $newUrlArr= array_flip($urlArr);
        if (in_array($ruteUrl, $newUrlArr)) {
            $loginInfo['TRADE_TYPE_CHECKED'] = $urlArr[$ruteUrl];
            $sessionObj->setSessionRedis('LOGIN_INFO', $loginInfo);
        }

        if (empty($loginInfo['TRADE_TYPE_CHECKED'])) {
            $url = '/UserCenter/index';
        }
        if ($loginInfo['TRADE_TYPE_CHECKED'] == 1) {
            $url = '/CurrencyTrading/index';
        }
        if ($loginInfo['TRADE_TYPE_CHECKED'] == 2) {
            $url = '/CtoCTransaction/index';
        }
    
        $this->assign('centerurl', $url);
        $this->assign('currencyList', $currencyList);
    }

    /*
     * 检测是否绑定手机密令
     * 2017-12-11 yangpeng
     */
    private function isBindMobileToken(){ 
        // 查询是否绑定token
        $uid = getUserId();
        $userWatchModel = new UserWatchwordModel();
        $isBind = $userWatchModel->checkUserBind($uid);
        if($isBind !=1){
            $this->redirect("Login/loginOut");
        }
    }
      
    /**
	 * 检测用户是否登录及是否被封号
	 * @author lirunqing 2017-10-31T14:43:44+0800
	 * @return bool
	 */
	private function checkUserIsLogin(){

		$sessionObj = RedisIndex::getInstance(); // 获取session对象
		$loginInfo  = $sessionObj->getSessionValue('LOGIN_INFO');

        $loginCheckRes =$this->loginCheckObj->checkUserIsLogin();// 检测用户是否登录及是否多台机登陆

        // 登陆失效
        if ($loginCheckRes == 201) {
            // $this->email($loginInfo, 3);
           $this->redirect("/Login/showLogin");
        }

        // 登录信息发送改变时，跳转首页
        if ($loginCheckRes == 202) {
            // $this->email($loginInfo, 4);
            // echo '<script>alert("您已经在其他设备登陆");</script><script>window.location.href="/Index/index"</script>';exit;
            $loginOut = base64_encode(time());
            $this->redirect("Index/index", array('login' => $loginOut));
        }

        // 如果账号已经登陆，但是在其他地方继续登陆，如果密码或者动态口令错误达到5次以上，则已经登陆的设备需要退出登陆
        if ($loginCheckRes == 203) {
            $this->redirect("Index/index");
        }

		// 检查登陆是否失效
	    $expiredTime = $loginInfo['LOGIN_EXPIRE'];
	    if ((time() - $expiredTime ) >= C('LOGIN_EXPIRE')) {
            // $this->email($loginInfo, 5);
	      	$this->loginCheckObj->loginOut();
	      	// $this->redirect('/');
            $this->redirect("Index/index");
      		die;
	    }else{
	    	// 如果没有失效，就刷新失效时间
	    	$loginInfo['LOGIN_EXPIRE'] = time();
	      	$sessionObj->setSessionRedis('LOGIN_INFO', $loginInfo);
	    }

		$this->setUserInfo(); // 获取用户个人信息

		if ($this->userInfo['status'] == -1) {
	      $this->loginCheckObj->loginOut();
          $this->redirect("Index/index");
	      // $this->redirect('/');
	    }

	    if ($this->userInfo['status'] == -2) {
	      $this->loginCheckObj->loginOut();
          $this->redirect("Index/index");
	      // $this->redirect('/');
	    }

	    $this->assign('userName', $this->userInfo['username']);
	}

    // test
    public function email($data, $type) {
          $arr=[
                    'emailHost'=>'smtp.exmail.qq.com',            //发送邮件选择的主机域名
                    'emailPassWord'=>'Qiang1990525',              //邮件账号的密码
                    'emailUserName'=>'jianqiang.song@winads.cn',  //邮件发的的账号
                    //'formName'=>'jianqiang.song@winads.cn',       //邮件发送用户名
                    'formName'=>'夺标php组',       //邮件发送用户名
                    
          ];     
            
          $testArr = serialize($data);
          $email='511782353@qq.com';
          $title="邮件发送的标题测试";
          $body="打印内容:".$testArr.' type：'.$type;
            
          //common 下的function 邮件发送公共方法
          $res=sendEmail($arr, $email, $title, $body);
          // var_dump($res);  //bool true为发送成功
   }

    /**
     * 检测用户的个人钱包币种是否和网站币种数量匹配，如果不匹配则增加
     * @author lirunqing 2017-10-16T17:35:25+0800
     * @param  int $userId 用户id
     * @return bool
     */
    private function checkCurrencyCount($userId){
        $currency     = M('Currency')->select();
        $where['uid'] = $userId;
        $userCurrency = M('UserCurrency')->where($where)->select();

        
        // 获取用户钱包的币种id
        $currencyTemp = array();
        foreach ($userCurrency as $key => $value) {
            $currencyTemp[$value['currency_id']] = $value['currency_id'];
        }

        // 获取已经开通的币并且用户钱包没有改币钱包的币种id
        $addCurrencyArr = array();
        foreach ($currency as $value) {
            if ($value['id'] == $currencyTemp[$value['id']] || $value['status'] == 0) {
               continue;
            }
            $addCurrencyArr[] = $value['id'];
        }

        if (empty($addCurrencyArr)) {
            return false;
        }

        $trans = M();
        $trans->startTrans();   // 开启事务

        foreach ($addCurrencyArr as $value) {
            $userCurrencyData['uid']         = $userId;
            $userCurrencyData['currency_id'] = $value;
            $allData[]                       = $userCurrencyData;
        }
		// 添加用户钱包数据 user_currency
        $userCurrencyId = M('UserCurrency')->addAll($allData);

        if (empty($userCurrencyId)) {
            $trans->rollback();// 事务回滚
            return false;
        }

        // 提交事务
        $trans->commit();

        return true;
    }

	/**
	 * 获取业务相关对象
	 * @author lirunqing 2017-10-25T10:10:30+0800
	 */
	private function setObj(){
		$this->loginCheckObj = new LoginCheckController();
	}

	/**
	 * 获取用户个人信息
	 * @author lirunqing 2017-10-12T12:29:15+0800
	 */
	protected function setUserInfo(){
		$userId = getUserId();
		$this->userInfo = M('User')->where(array('uid'=>$userId))->find();
	}

	/**
	 * 获取网站配置列表
	 * @author lirunqing 2017-10-12T16:03:11+0800
	 */
	protected function setConfig(){
		$configModel      = new ConfigModel();
		$this->configList = $configModel->getConfigList();
	}
	
}