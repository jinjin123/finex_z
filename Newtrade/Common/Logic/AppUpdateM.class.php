<?php
namespace Common\Logic;
/**
 * APP强制更新逻辑
 * Class AppUpdateM
 * @package Common\Logic
 * 刘富国
 * 2017-10-23
 */
class AppUpdateM {
    private $_token         = '';    
    private $_access        = 'no';  // 是否可以访问
    private $_isNeedUpdate  = 2;      // 0-最新版本，1-有新版本更新，2-需强制更新
    private $_updateLink   = '';
    private $_callVer      = '';
    private $_lastVer      = '';
    private $_hash         = '';
    private $_update_tip   = '';
    
    private $_errCode = 0;
    private $_errMsg  = '';
    
    public function __construct($os, $ver, $hash,$app_platform){
        $_os = 0;
        if ($os == 'android')
            $_os = 2;
        elseif ($os == 'ios')
            $_os = 1;
        else {
            $this->_setError(1);
            return;
        }
        $app_info = M('AppList')->where(array('os' => $_os,
            'app_ver' => $ver,
            'is_open' => 1,
            'app_platform' => $app_platform
            ) )->find();
        if($app_info['app_hash'] != $hash){
            $this->_setError(2);
            return;
        }
        $last = M('AppList')->where(array(
            'os' => $_os,
            'is_open' => 1 ,
            'app_platform' => $app_platform))
            ->order('vid desc')->find();
        if($app_info['app_ver'] !=  $last['app_ver']){
            if ($last['level'] == 3 || $this->isForce($_os, $ver)) {
                $this->_access = 'no';
                $this->_isNeedUpdate = 2;
            }elseif($last['level'] == 2){
                $this->_access = 'yes';
                $this->_isNeedUpdate = 1;
            }elseif($last['level'] == 1){
                $this->_access = 'yes';
                $this->_isNeedUpdate = 0;
            }
        }elseif($app_info['app_ver'] == $last['app_ver']){
            $this->_access = 'yes';
            $this->_isNeedUpdate = 0;
        }else{
            $this->_setError(3);
            return;
        }
        
        $this->_hash    = $last['app_hash'];
        $this->_callVer = $ver;
        $this->_lastVer = $last['app_ver'];
        $this->_update_link = $last['update_link'];
        $this->_update_tip  = $last['update_tip'] ? $last['update_tip'] : '';
    }
    
    protected function isForce($_os, $ver)
    {
        // 查询大于本版本的，如果有强制更新的就强制更新
        $condition = array(
            'os' => $_os,
            'is_open' => 1,
            'app_ver' => array('gt', $ver),
            'level' => 2
        );
        $is_foce = M('AppList')->where($condition)->count();
        
        return $is_foce > 0;
    }
    
    
    public function getUpdateInfo(){
        return array(
            'is_need_update' => $this->_isNeedUpdate,
            'update_link'    => www_path($this->_update_link),
            'call_ver'       => $this->_callVer,
            'last_ver'       => $this->_lastVer,
            'update_tip'     => $this->_update_tip,
        );
    }

    public function getErrorMsg(){
        return array(
            'err_code' => $this->_errCode,
            'err_msg'  => $this->_errMsg
        );
    }
    
    private function _setError($code){
        switch($code){
            case 1:
                $this->_errCode = 1;
                $this->_errMsg  = '不存在app系统版本';
                break;
            case 2:
                $this->_errCode = 2;
                $this->_errMsg  = 'app签名认证不正确';
                break;
            case 3:
                $this->_errCode = 3;
                $this->_errMsg  = '不存在app版本号';
                break;
            case 4:
                $this->_errCode = 4;
                $this->_errMsg  = '';
                break;
            case 5:
                $this->_errCode = 5;
                $this->_errMsg  = '';
                break;
        }
    }
}












