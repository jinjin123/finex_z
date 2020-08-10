<?php

namespace Home\Controller;

use Home\Logics\CommonController;
use Home\Model\UserModel;
use Common\Api\RedisIndex;

class UserCenterController extends CommonController
{
    public $tmpl = '../../../Public/Home/fe';
    protected $userobj;
    private $areaArr = array(
        '+86' => 3,// 大陆
        // '+886' => 2,//台湾
        '+852' => 1,//香港
    );// 获取默认地区

    private $areaName = array(
        1 => 'HK',
        // 2 => 'TW',
        3 => 'CN',
    );

    public function __construct()
    {
        parent::__construct();
        $this->userobj = M('User')->where(['uid' => getUserId()])->find();
    }

    public function index()
    {
        $uid = getUserId();

        $table = getTbl('UserLog', $uid);
        $log = M($table)->where([
            'uid' => $uid,
            'type' => 1
        ])->order('add_time desc')->find();

        $userCurrency = M('UserCurrency')->alias('uc')->join('trade_currency AS c ON c.id = uc.currency_id')->where([
            'uc.uid' => $uid,
            'c.currency_name' => ['IN', ['BTC', 'ETH', 'FEC', 'USDT']]
        ])->field([
            'c.currency_name', 'uc.num'
        ])->select();

        //获取汇率
        $btcChain = $this->getChain('btc');
        $ethChain = $this->getChain('eth');
        $petcChain = $this->getChain('fec');

        foreach ($userCurrency as $k => $v) {
            if ($v['currency_name'] == 'BTC') $v['usdt_num'] = sprintf('%.8f', $v['num'] * $btcChain['last']);
            if ($v['currency_name'] == 'ETH') $v['usdt_num'] = sprintf('%.8f', $v['num'] * $ethChain['last']);
            if ($v['currency_name'] == 'FEC') $v['usdt_num'] = sprintf('%.8f', $v['num'] * $petcChain['last']);
            if ($v['currency_name'] == 'USDT') $v['usdt_num'] = sprintf('%.8f', $v['num']);

            $userCurrency[$v['currency_name']] = $v;
            unset($userCurrency[$k]);
        }

        $this->assign('currency', $userCurrency);
        $this->assign('user', getUserInfo());
        $this->assign('real', checkRealName());
        $this->assign('log', $log);
        $this->display($this->tmpl . '/User');
    }

//	public function index(){
//		$defaultArra   = $this->areaArr[$this->userInfo['om']];// 根据用户注册地区，获取默认地区银行
//		$defaultArra   = !empty($defaultArra) ? $defaultArra : 1;// 如果用户非大陆，台湾，香港用户，则默认为香港
//		$BankListModel = new BankListModel();
//		$bankList      = $BankListModel->getBankListName();
//		$userReal      = M('UserReal')->where(['uid'=> getUserId()])->find();
//		$configModel   = new ConfigModel();
//		$configList    = $configModel->getConfigList();
//		$isMaintain    =  $this->getWebMaiantainInfo(Maintain::P2P);
//
//		$this->assign('isMaintain', $isMaintain);
//		$this->assign('configList', $configList);
//        $this->assign('userReal',$userReal);
//		$this->assign('areaName', $this->areaName[$defaultArra]);
//        $this->assign('defaultArra', $defaultArra);
//        $this->assign('isTour', 0);
//        $this->assign('is_p2p_tour', $this->userInfo['is_p2p_tour']);
//		$this->display();
//	}

//    public function deposit()
//    {
//        $this->display($this->tmpl . '/User_Chain_Deposit_History');
//    }
//
//    public function withdraw()
//    {
//        $this->display($this->tmpl . '/User_Chain_Withdraw_History');
//    }
//
//    public function exchange()
//    {
//        $this->display($this->tmpl . '/User_Chain_Exchange_History');
//    }

    /**
     * 获取用户绑定的银卡卡信息
     * @return [type] [description]
     * @author lirunqing 2017-11-06T14:20:54+0800
     */
    public function getUserBindBank()
    {

        $userId = getUserId();
        $areaArr = array_flip($this->areaArr);
        $areaId = (int)I('post.areaId');
        $countryCode = $areaArr[$areaId];
        $where['a.uid'] = $userId;
        $where['a.status'] = 1;
        $where['b.country_code'] = $countryCode;
        $field = 'a.id,a.bank_num,a.default_status,b.bank_name,a.bank_list_id';
        $res = M('userBank')->alias('a')->field($field)->join('__BANK_LIST__ b ON b.id= a.bank_list_id')->where($where)->select();

        foreach ($res as $key => $value) {
            $value['bank_name'] = formatBankType($value['bank_list_id']);
            unset($value['bank_list_id']);
            $value['bank_num'] = substr_replace($value['bank_num'], '**** **** **** ', 0, -4);
            $res[$key] = $value;
        }

        $this->ajaxReturn($res);
    }

    /**
     * 根据地区获取银行相关信息
     * @return [type] [description]
     * @author 2017-11-08T18:07:27+0800
     */
    public function getBankInfoByArea()
    {

        $arr = array(
            1 => L('_ZGXG_'),
            2 => L('_ZGTW_'),
            3 => L('_ZGDL_')
        );
        $areaArr = array_flip($this->areaArr);
        $areaId = (int)I('post.areaId');
        $countryCode = $areaArr[$areaId];
        $where['country_code'] = $countryCode;
        $bankInfo = M('BankList')->where($where)->select();

        $area = '';
        if (!empty($bankInfo)) {
            $area = $arr[$areaId];
        }

        foreach ($bankInfo as $key => $value) {
            $value['bank_name'] = formatBankType($value['id']);
            unset($value['country_code']);
            $bankInfo[$key] = $value;
        }

        $allAreaBank = array(
            "id" => "0",
            "bank_name" => L('_QUANBU_')
        );

        array_unshift($bankInfo, $allAreaBank);

        $data = array(
            'area' => $area,
            'bankInfo' => $bankInfo
        );

        $this->ajaxReturn($data);
    }

    /**
     * 检测用户是否绑定银行卡
     * @return [type] [description]
     * @author lirunqing 2017-11-16T10:51:40+0800
     */
    public function checkUserBindBank()
    {
        $res = array(
            'msg' => '',
            'code' => 201,
            'data' => array(),
        );

        $userId = getUserId();
        if (!checkUserBindBank($userId)) {
            $res['msg'] = L('_ZHUYI_') . L('_QXJX_') . L('_GJYDQ_') . '<a href="/PersonalCenter/showBankCardBind" style="color:#00dcda;">' . L('_BDYHK_') . '</a>' . L('_ZJXMCCZ_');
            $this->ajaxReturn($res);
        }

        $res['code'] = 200;
        $this->ajaxReturn($res);
    }

    /**
     * 汇率前台显示
     * @author fuwen
     * @date 2018年3月6日10:51:45
     */
    public function exchangeRate()
    {
        $rate_list = M('Rate')
            ->order('add_time desc')
            ->limit(10)
            ->select();
        foreach ($rate_list as $k => $v) {
            if ($rate_list[$k]['rate_avg'] == 0) {
                $rate_list[$k]['rate_avg'] = '-';
            }
        }
        $this->assign('rate_list', $rate_list);
        $this->display();
    }

    /**
     * 修改密码
     */
    public function changePass()
    {
        if (IS_AJAX) {
            $data = I('post.');
            $key = ($this->userobj)['email'] . '_change_password_email_code';
            $redis = RedisIndex::getInstance();
            $rcode = $redis->getSessionValue($key);
            $msg = $this->checkPass($data, $rcode);
            if ($msg) $this->ajaxReturn(['code' => 201, 'msg' => $msg]);
            //插入数据表
            $ret = M('User')->where([
                'uid' => getUserId()
            ])->save([
                'pwd' => passwordEncryption(trim($data['password']))
            ]);
            if (!$ret) $this->ajaxReturn(['code' => 202, 'msg' => 'Modify the failure']);
            $redis->delSessionRedis($key);
            $this->ajaxReturn(['code' => 200, 'msg' => 'Modify the success']);
        } else {
            $this->assign('user', getUserId());
            $this->assign('email', $this->userobj['email']);
            $this->display($this->tmpl . '/changePass');
        }

    }

    //获取参数
    public function getParams()
    {
        $data = I('get.');
        (new UserModel())->getList($data['type'], $data['num'], $data['username']);
    }

    protected function checkPass($data, $rcode)
    {
        $msg = '';
        if (!$data['code'] || !data['password'] || !$data['rpassword']) {
            $msg = 'Incorrect input information';
        }
        if (trim($data['password']) != trim($data['rpassword'])) {
            $msg = 'The password is inconsistent between the two entries';
        }
        if (trim($data['code']) != $rcode) {
            $msg = 'Email verification code error';
        }
        return $msg;
    }

    public function sendEmail()
    {
        $code = rand(100000, 999999);
        //判断邮箱是否存在
        $userInfo = $this->userobj;
        if (empty($userInfo)) $this->ajaxReturn(['msg' => 'The user not exist']);
        $redis = RedisIndex::getInstance();
        $redis->setSessionRedis($userInfo['email'] . '_change_password_email_code', $code);
        $email_data  = M("EmailConf")->find();
        $smtp =[
            'emailHost' => $email_data["emailhost"],
            'formName' => $email_data["formname"],
            'emailPassWord' => $email_data['emailpassword'],
            'emailUserName' => $email_data['emailusername'],
        ];
//        C('smtp')
        $status = sendEmail($smtp, $userInfo['email'], 'Change Password', '[SpaceFinEX]Your verification code is ' . $code . '. If it is not your operation, please ignore it.');
        $this->ajaxReturn(['msg' => 'Send ' . ($status ? 'Successful' : 'Fail')]);
    }

    //我的莲
    public function myChain()
    {
        $uid = getUserId();
        $userCurrency = M('UserCurrency')->alias('uc')->join('trade_currency AS c ON c.id = uc.currency_id')->where([
            'uc.uid' => $uid,
            'c.currency_name' => ['IN', ['BTC', 'ETH', 'FEC', 'USDT']]
        ])->field([
            'c.currency_name', 'uc.num'
        ])->select();

        //获取汇率
        $btcChain = $this->getChain('btc');
        $ethChain = $this->getChain('eth');
        $petcChain = $this->getChain('fec');

        foreach ($userCurrency as $k => $v) {
            if ($v['currency_name'] == 'BTC') $v['usdt_num'] = sprintf('%.8f', bcmul($v['num'] , $btcChain['last']));
            if ($v['currency_name'] == 'ETH') $v['usdt_num'] = sprintf('%.8f', bcmul($v['num'] , $ethChain['last']));
            if ($v['currency_name'] == 'FEC') $v['usdt_num'] = sprintf('%.8f', bcmul($v['num'],$petcChain['last']));
            if ($v['currency_name'] == 'USDT') $v['usdt_num'] = sprintf('%.8f', $v['num']);

            $userCurrency[$v['currency_name']] = $v;
            unset($userCurrency[$k]);
        }
		//dump($userCurrency);die;
        $this->assign('currency', $userCurrency);
        $this->assign('user',$uid);
        $this->assign('real', checkRealName());
        $this->display($this->tmpl . '/User_Chain');
    }

    //实名认证
    public function realName()
    {
        $data = M('UserReal')->where(['uid' => getUserId()])->find();
        $str = explode('-', $data['card_name']);
        $data['first_name'] = $str[0];
        $data['last_name'] = $str[1];
        $data['id_card'] = $data['card_num'];
        $this->assign('data', $data);
        $this->display($this->tmpl . '/real');
    }

    public function subRealName()
    {
        $data = I("post.");
        $firstName = trim($data['FirstName']);
        $LastName = trim($data['LastName']);
        $IDCard = trim($data['IDCard']);
        $IDCardPhoto = trim($data['IDCardPhoto']);
        $msg = $this->checkData($data);
        if ($msg != '') return $this->ajaxReturn(['msg' => $msg]);

        //判断是否存在
        $row = M('UserReal')->where(['uid' => getUserId()])->find();

        if (empty($row)) {
            $newData = [];
            $newData['card_name'] = $firstName . '-' . $LastName;
            $newData['card_num'] = $IDCard;
            $newData['up_img'] = $IDCardPhoto;
            $newData['uid'] = getUserId();
            $newData['add_time'] = time();
            $newData['check_time'] = time();
            $newData['status'] = 1;
            $rst = M('UserReal')->add($newData);
            if (!$rst) $this->ajaxReturn(['msg' => 'Real-name authentication failed']);
            $this->ajaxReturn(['msg' => 'Real-name authentication passed']);
        }

        $status = M('UserReal')->where(['uid' => getUserId()])->save([
            'card_name' => $firstName . '-' . $LastName,
            'card_num' => $IDCard,
            'up_img' => $IDCardPhoto,
            'check_time' => time()
        ]);

        if (!$status) $this->ajaxReturn(['msg' => 'Real-name authentication failed']);
        $this->ajaxReturn(['msg' => 'Real-name authentication passed']);
    }

    protected function checkData($data)
    {
        $msg = '';
        if (empty($data['FirstName'])) {
            $msg = 'firstName is empty';
        }
        if (empty($data['LastName'])) {
            $msg = 'lastName is empty';
        }
        if (empty($data['IDCard'])) {
            $msg = 'IDCard is empty';
        }
        if (empty($data['IDCardPhoto'])) {
            $msg = 'IDCardPhoto is empty';
        }
        return $msg;
    }

    /**
     * 文件上传类
     * @param string $name 传入图片的name
     * @return array
     * @author yangpeng 2017-10-11
     */
    public function uploadOne()
    {
        /*1、实例化上传类并初始化相关值*/
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 312312 * 1024 * 1024;// 设置附件上传大小3M
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Upload/Home/realname/'; // 设置附件上传根目录
        $upload->saveName = time() . '_' . rand(100000, 999999);
        /*2、上传单个文件，正确返回图片路径，错误返回错误信息*/
//                $info   =   $upload->uploadOne($_FILES[$name]);
        $info = $upload->uploadOne($_FILES['files']);
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功 获取上传文件信息
            $result['imgurl'] = '/Upload/Home/realname/' . $info['savepath'] . $info['savename'];
            $result['code'] = 200;
            $this->ajaxReturn($result);
        }
    }

    private function getPetcPrice()
    {
        $row = M('PetcKline')->where([
            'type' => 1
        ])->order('add_time DESC')->limit(1)->find();

        $issue = M('IcoIssue')->order('id DESC')->limit(1)->find();
        $last = isset($issue['rate']) ? $issue['rate'] : 0;

        return [
            'instrument_id' => 'FEC',
            'last' => $row['last'],
            'rate' => isset($row['rate']) ? $row['rate'] : 0,
            'open_24h' => isset($row['kaipan_price']) ? $row['kaipan_price'] : 0,
            'high_24h' => isset($row['high_24h']) ? $row['high_24h'] : 0,
            'low_24h' => isset($row['low_24h']) ? $row['low_24h'] : 0,
            'quote_volume_24h' => isset($row['volume_24h']) ? $row['volume_24h'] : 0,
            'base_volume_24h' => isset($row['vom_now']) ? $row['vom_now'] : 0
        ];
    }

    private function getChains()
    {
        $currencys = $this->getCurrency();
        $coins = array_column($currencys, 'currency_name');

        foreach ($coins as $k => $v) {
            if ($v == 'FEC') {
                $coins[$k] = $coins[3];
                $coins[3] = 'FEC';
            }
        }

        $data = [];
        foreach ($coins as $k => $v) {
            if ($v != 'USDT') {
                $candle = $this->getChain($v);
                if ($candle) array_push($data, $candle);
            }
        }
        foreach ($data as $k => $v) {
            $data[$k]['instrument_id'] = str_replace('-USDT', '', $data[$k]['instrument_id']);
            $data[$k]['rate'] = round(($v['last'] - $v['open_24h']) / $v['open_24h'] * 100, 4);
        }
        return $data;
    }

    private function getChain($name)
    {
        if (strtolower($name) == 'fec') return $this->getPetcPrice();
        $url = 'https://www.okex.com/api/spot/v3/instruments/%s-usdt/ticker';
//        $url = 'https://bird.ioliu.cn/v2?url=https://www.okex.me/api/spot/v3/instruments/%s-usdt/ticker';
        $url = sprintf($url, $name);
        $data = json_decode(vget($url), true);
        if (!empty($data)) {
            $data['instrument_id'] = str_replace('-USDT', '', $data['instrument_id']);
            $data['rate'] = round(($data['last'] - $data['open_24h']) / $data['open_24h'] * 100, 4);
        }
        return $data;
    }

    /**
     * 获取汇率
     * @return array
     */
    private function getRate()
    {
        $usdt = M('IcoIssue')->find();
        //获取btc和eth价格牌
        $btc = $this->getChain('btc');
        $eth = $this->getChain('eth');
        $rate = [
            'usdt' => round(1 / $usdt['rate'], 8),
            'btc' => round($btc['last'] / $usdt['rate'], 8),
            'eth' => round($eth['last'] / $usdt['rate'], 8),
        ];
        return $rate;
    }

    private function getCurrency()
    {
        return M('Currency')->where(['status' => 1])->select();
    }
}