<?php
namespace Common\Model;;
use Think\Model;

class BaseModel extends Model{
    public function __construct(){
        parent::__construct();
        
    }
    //最后一次错误代码
    public $errno = 0;
    //最后一次错误信息
    public $errmsg = '';

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
}