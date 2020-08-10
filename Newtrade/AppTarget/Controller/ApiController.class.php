<?php
namespace AppTarget\Controller;

/**
 * API 入口类
 * 2017-10-16
 * @author 刘富国
 */
use AppTarget\Route\Route;
use Think\Controller;
use AppTarget\Base\AppBaseController;
use Think\Exception;

class ApiController extends AppBaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        header('Content-Type:application/json; charset=utf-8');
        
        $server = $this->postJson['server'];
        $ver = $this->postJson['ver'];
        $token = $this->postJson['token'];
        $data = $this->postJson['data'];
        try {
            // 根据接口配置获取路由信息
            $route_obj = new Route($server, $ver);
            if (! $route_obj->is_access) {
                $this->_outPut($route_obj->errno);
                return false;
            }
            $param = array(
                'data' => $data,
                'token' => $token,
                'ver' => $ver,
                'userId' => $this->userId,
                'os' => $this->_os,
                'langSet' => $this->langSet
            );
            $server_name = $route_obj->server_name;
            $server_obj = new $server_name();


            $server_obj->setParam($param);
            $server_obj->__construct();
            if(is_numeric($server_obj->errno) and $server_obj->errno > 0) {
                $this->_outPut($server_obj->errno,$server_obj->errmsg);
            }
            // 调用api的方法
            $method = $route_obj->method;
            if (!method_exists($server_obj, $method)) E('method ' . $method . ' not fund');
            $rst = $server_obj->$method();
            $this->_outPut($rst,$server_obj->errmsg);

        } catch (Exception $e) {
            $this->_handleError($e);
        } catch (\Exception $e) {
            $this->_handleError($e);
        }
    }
    
    private function _handleError($e)
    {
        $err_code = $e->getCode();
        if ($err_code && C('APP_CODE.'.$err_code)) {
            $this->_outPut($err_code);
        } else {
            $this->_outPut(9999, C('IS_DEBUG_VER') ? ('(' . $e->getMessage().')') : '');
        }
    }
}
