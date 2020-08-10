<?php
namespace AppTarget\Service;
/**
 * APP服務基類
 * Class ServiceBase
 * @package AppTarget\Service
 * 劉富囯
 * 2017-10-19
 */
class ServiceBase
{
    
    /**
     * App传来的data数据段
     * @var mixed
     */
    private $data;
    
    /**
     * App传来的token
     * @var string
     */
    protected $token;
    
    /**
     * app版本号
     * @var string
     */
    protected $ver;
    
    /**
     * 客户端os
     * @var string
     */
    protected $os;
    
    /**
     * 登录人ID
     * @var int
     */
    private $userId;

    //语言版本
    public $langSet;

    //最后一次错误代码
    public $errno = 0;
    //最后一次错误信息
    public $errmsg = '';

    public function __construct()
    {
    }

    /**
     * 设置错误信息     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     * 刘富国
     */
    public function return_error_num($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return $errno ;
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
        return false ;
    }



    /**
     * 方法执行前的操作
     * @return boolean
     */
    public function beforeAction()
    {
        return true;
    }
    
    /**
     * 设置参数
     * @param array $param
     * @return
     */
    final public function setParam($param){
        foreach ($param as $k => $v) {
            property_exists($this, $k) && $this->$k = $v;
        }
    }
    
    /**
     * 获取登录用户的ID
     * @return number
     */
    final protected function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * 获取app传来的业务data数据
     * @param string key，如果为空，则返回所有
     * @return number
     */
    final protected function getData($key = '')
    {
        if (empty($key)) return $this->data;
        
        return isset($this->data[$key]) ? $this->data[$key] : '';
    }

    
    /**
     * 验证是否登录
     * @return boolean 成功返回true，否则返回false
     */
    protected function isLogin()
    {
        return $this->userId > 0;
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
    
    protected function commitReturn($rst)
    {
        M()->commit();
        return $rst;
    }
    

}
