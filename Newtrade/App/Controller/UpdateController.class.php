<?php
namespace App\Controller;
use App\Base\AppBaseController;
use Common\Logic\AppUpdateM;
use Common\Library\Tool\Token;

class UpdateController extends AppBaseController{

    public function __construct(){
        parent::__construct();
    }
    
    public function index(){
        $ver     = $this->postJson['ver'];
        $os      = $this->postJson['os'];
        $os_ver  = $this->postJson['os_ver'];
        $hash    = $this->postJson['hash'];
        $app_platform    = $this->postJson['app_platform'];
        $phone_imei    = $this->postJson['phone_imei'];
        // 错误代号： 请求参数出错
        if(!in_array($os, array('ios', 'android')) or empty($app_platform))$this->_outPut(10000);
        $o = new AppUpdateM($os, $ver, $hash,$app_platform);
        $info = $o->getUpdateInfo();
        $param = array(
            'access' => $info['is_need_update'] == 2 ? 'no' : 'yes',
            'time'   => time(),
            'sign'   => $this->_sign ? $this->_sign : build_rand_str(6),
            'uid'    => $this->userId,
            'ver'    => strval($ver),
            'os'     => $os,
            'phone_imei'     => $phone_imei,
        );
        $info['userToken'] = Token::buildToken($param);
      //  unset($info['call_ver'], $info['last_ver']);
        $this->_outPut($info);
    }
}