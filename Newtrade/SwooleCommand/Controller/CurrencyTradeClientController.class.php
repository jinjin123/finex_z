<?php
namespace SwooleCommand\Controller;
use Think\Controller;

/**
 * swoole客户端处理币币交易订单业务
 * @author lirunqing 2018年7月16日10:34:27
 */
class CurrencyTradeClientController extends Controller {
	
	protected $host   = NULL;
	protected $port   = NULL;
	

	public function __construct()
	{
		$this->host=  C('PRO.HOST_IP_NO_OUTSIDE');
		$this->port=  C('PRO.PORT_CUREE_MATCH');
	}

	/**
	 * 推送信息到tcp服务器端
	 * @author 2018-07-19T16:08:22+0800
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	public function sendTcpMsgToServer($message){

		if(!class_exists('swoole_client')){
			// echo "9999";
			return false;
		} 

    	$clientObj =new \swoole_client(SWOOLE_SOCK_TCP);
    	if(!$clientObj->connect($this->host, $this->port, 3)) {
		    // echo "连接失败22";
		    return false;
		}
		// 发送消息给 tcp server服务器
        if(!$clientObj->send($message)){
            // echo "发送消息失败";
            return false;
        }

        $result = $clientObj->recv();
		
		$clientObj->close();
		return $result;

	}
}