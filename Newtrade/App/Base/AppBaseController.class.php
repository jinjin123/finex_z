<?php
namespace App\Base;
use Think\Controller;
use Common\Model\UserTokenModel;
use Common\Api\RedisCluster;

class AppBaseController extends Controller{
    
    protected $postJson 	= '';
    protected $userId 		= 0;
    protected $baseModel    = null;
    protected $get          = null;
    protected $isLogin      = false;
    protected $_access      = 'no';
    protected $_sign        = '';
    protected $_ver         = '';
    protected $_os          = '';
    protected $_phone_imei   = '';
    //最后一次错误代码
    public $errno = 0;
    //最后一次错误信息
    public $errmsg = '';
    
    public function __construct(){
        header('Content-Type:application/json; charset=utf-8');
        parent::__construct();
        if(C('IS_DEBUG_VER') && isset($_POST['test_key'])){
            post_log();
        }
        fliter_post();
        $this->postJson = get_post('parse_data', array());
        if(empty( $this->postJson))  $this->_outPut(10000);
        // 必须先获取token信息
        $ret = $this->_getTokenInfo();
        //加载用户定义语言包
        $this->check_language();
        if(!$ret)  $this->_outPut($this->errno,$this->errmsg);
        $this->get = I('get.');
        $this->baseModel  = M();
    }

    /**
     * 根据token，获取用户信息
     */
    private function _getTokenInfo(){
        $this->_access = 'yes';
        $this->userId  = 0;
        $input_data = $this->postJson['data'];
        $this->_phone_imei= $input_data['phone_imei'];
        if(empty($this->postJson['token'])) return true;
        $info = \Common\Library\Tool\Token::getToken($this->postJson['token']);
        $this->userId  = isset($info['uid']) && $info['uid'] ? intval($info['uid']) : 0;
        $this->_sign = $info['sign'];
        $this->_ver  = strval($info['ver']) ? $info['ver'] : '';
        $this->_os  = strval($info['os']) ? $info['os'] : '';
        if(!empty($info['phone_imei'])){
            $this->_phone_imei = $info['phone_imei'];
        }
        //系统api调用接口，校验_sign是否正确
        if($this->_os == 'api_interface' ){
            $token_time = $info['time'];
            $serial_num = C('API_TOKEN_SUFFIX').$token_time;
            $data['sign'] = $serial_num;
            $check_sign = sys_md5(data_auth_sign($data));
            if($this->_sign <> $check_sign){
                $this->_access = 'no';
                $this->userId = 0;
                $this->_sign  = '';
                return $this->return_error(9998);
            }
        }
        //app的api调用接口,如果有userid,校验登陆TOKEN的sign
        if($this->userId and $this->_os <> 'api_interface'){
            $user_token_model = new UserTokenModel();
            //校驗token
            $input_data['uid'] = $this->userId;
            $input_data['phone_token'] = $this->_sign;
            $input_data['app_platform'] = C('APP_PLATFORM');
            $ret = $user_token_model->checkUserLoginToken($input_data);
            if(!$ret){
                $this->_access = 'no';
                $this->userId = 0;
                $this->_sign  = '';
                return $this->return_error($user_token_model->errno,$user_token_model->errmsg);
            }
        }
        return true;
    }

    //加载用户定义语言包
    protected function check_language(){
        // $redisObj = new RedisCluster();
        $redis  = RedisCluster::getInstance();
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON',null,false)){
            return;
        }
        $langSet = C('DEFAULT_LANG');
        $langList = C('LANG_LIST',null,'zh-tw');

        if (C('LANG_AUTO_DETECT',null,true)){
            if($this->userId ){
                $langSet =   $redis->get('APP_VAR_LANGUAGE'.$this->userId);
            }
            if($this->_phone_imei){
                $langSet =   $redis->get('APP_VAR_LANGUAGE'.$this->_phone_imei);
            }
            if(false === stripos($langList,$langSet) ) { // 非法语言参数
                $langSet = C('DEFAULT_LANG');
            }
        }
        // 读取应用公共语言包
        $file   =  LANG_PATH.$langSet.'.php';
        if(is_file($file))  L(include $file);
    }
    
    /**
     * 输出接口数据，json格式
     * @param unknown $data 如果data是错误代号：数字，错误代号在配置文件status_code里面查看
     * @param string $pager
     */
    protected function _outPut($data, $error_msg = ''){
        if(!is_array($data)){
            if(empty($error_msg)){
                $error_msg = C('APP_CODE.'.$data) ? L(C('APP_CODE.'.$data)) : '';
            }
            if($error_msg){
                $status = array(
                    'data'    => (object)array(),
                    'error'   => strval($data),
                    'msg'     => $error_msg,
                );
                die(json_encode($status));
            }
        }
        if(is_array($data) && isset($data['data'])){
            $data = $data['data'];
        }
        $data = array_merge(array('data' => $data), array('error' => 0, 'msg' => 'success'));

        $data = $this->_strval_array($data);
        die(json_encode($data));
    }
    
    private function _strval_array($arr){
        if(is_array($arr) && !empty($arr)){
            foreach($arr as $n => $v){
                $b[$n] = $this->_strval_array($v);
            }
            return $b;
        }else{
            if (is_object($arr)) return $arr;
            if (is_array($arr) && empty($arr)) return array();
            return strval($arr);
        }
    }


    /**
     * 空方法，请求方法不合法时将自动调用该函数
     */
    public function _empty(){
        $this->_outPut(404);
    }

    /**
     * 页码信息
     * @param unknown $curr_page
     * @param unknown $total_page
     */
    protected function _pager($curr_page, $total_page){
        $pager = array(
            'current_page' => $curr_page <= 0 ? 1 : $curr_page,
            'last_page'    => $curr_page - 1 <=0 ? '' : $curr_page - 1,
            'next_page'    => ($curr_page + 1 > $total_page) ? '' : $curr_page + 1,
            'total_pages'  => $total_page,
        );
        return $pager;
    }
    
    
    /**
     * 设置事务标识
     * @param unknown $rst
     * @param unknown $is_commit
     * @return boolean
     */
    protected function _setCommit($rst, $is_commit){
        return $rst && $is_commit;
    }
    
    /**
     * 开始事务
     */
    protected function _trans(){
        $this->baseModel->startTrans();
        return true;
    }
    
    /**
     * 事务提交或回滚
     * @param unknown $is_commit
     */
    protected function _commitOrRollback($is_commit){
        if($is_commit){
           return $this->_commit();
        }else{
            $this->_rollback();
            return false;
        }
    }
    
    /**
     * 提交事务
     */
    protected function _commit(){
       return $this->baseModel->commit();
    }
    
    /**
     * 回滚事务
     */
    protected function _rollback(){
        $this->baseModel->rollback();
        return false; // 添加数据失败
    }

    private function _isAccess(){
        if(strtolower($this->_access) == 'yes') return true;
        // 错误代号：9998 没有权限访问
        $this->_outPut(9998);
    }

    /**
     * 设置错误信息     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     * 刘富国
     */
    public function return_error($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return $errno == 0 ;
    }



}