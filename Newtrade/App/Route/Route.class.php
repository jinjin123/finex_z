<?php
namespace App\Route;

/**
 * APP路由类
 * 2017-10-16
 * @author 刘富国
 */
class Route
{
    
    /**
     * 当前版本的路由配置数组
     * @var array
     */
    protected $_map = array();
    
    /**
     * 版本号
     * @var string
     */
    protected $_ver;
    
    /**
     * server类名
     * @var string
     */
    protected $server_name;
    
    /**
     * 调用的server类中的方法名
     * @var string
     */
    protected $method;
    
    /**
     * 接口是否允许访问
     * @var boolean
     */
    protected $is_access;

    //最后一次错误代码
    public $errno = 0;

    //最后一次错误信息
    public $errmsg = '';
    /**
     * 设置错误信息
     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     * 刘富国
     */
    public function return_error($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return false ;
    }

    /**
     * 获取最后一次错误代码
     * 刘富国
     */
    public function last_error(){
        if($this->errno > 0 ){
            return   $this->errmsg ? $this->errmsg : 'UNKNOW_ERROR';
        }
        else{
            return '';
        }
    }
    
    /**
     * hook
     * @var array
     */
    protected $hook;

    public function __construct($api, $ver){
        $this->_ver = $ver;
        // 取大版本号，去掉点
        $ver_str = implode('', array_slice(explode('.', $ver), 0, 2));
        $ver_str .= strval(intval($ver_str)) === strval($ver_str) ? '0' : '';
        $route_file = __DIR__ . DIRECTORY_SEPARATOR .'conf' . DIRECTORY_SEPARATOR  . 'route' . $ver_str . '.php';

        if(!file_exists($route_file)) return $this->return_error(10015); //不支持版本
        $route_map = require_once ($route_file);
        if(!isset($route_map[$api]) ) return $this->return_error(10016); //無相關服務名
        $this->_map = $route_map[$api];
        $this->_init();
    }

    /**
     * 初始化
     */
    protected function _init() {
        if(empty($this->_map['Server']))  return $this->return_error(10015); //不支持版本
        $this->server_name = '\\App\\Service\\' . $this->_map['Server'] . 'Service';
        if(!class_exists($this->server_name)) return $this->return_error(10016); //無相關服務名
        if(empty($this->_map['Method'])) return $this->return_error(10017); //無相關方法
        $this->method = $this->_map['Method'];
        $this->is_access = true;
    }

    // _开头的不能外部访问
    public function __get($attr){
        return substr($attr, 0, 1) == '_' || !property_exists($this, $attr) ? E('Attribute('.$attr.') is not fund') : $this->$attr;
    }
}
