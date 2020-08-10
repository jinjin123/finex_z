<?php
namespace SwooleCommand\Controller;
use Think\Controller;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
use Home\Logics\CurrencyTradingLogicsController;

/**
 * swoole服务端处理币币交易订单业务
 * @author lirunqing 2018年7月16日10:34:27
 */

class CurrencyTradeServerController extends Controller {

	private $server   = null;
	private $redis    = null;
	protected $host   = NULL;
	protected $port   = NULL;
	private $rediskey = "CURTENCY_ORDER_TO_ORDER_FOR_ORDER_INFO_BY_ORDER";

	private $setArr   = [
		'worker_num'      => 8,
		'daemonize'       => 0, //是否作为守护进程,此配置一般配合log_file使用
		'max_request'     => 10000,
	    'daemonize'       =>1, // 1后台守护进程  是否作为守护进程,此配置一般配合log_file使用
		'dispatch_mode'   => 2,
		'debug_mode'      => 1,
		// 'task_worker_num' => 4,         //异步task
		// 'log_file'     => './swoole.log',
	]; 
	/**
	 * 
	 * @author lirunqing 2018-07-16T10:48:02+0800
	 * @param  string $host 
	 * @param  string $port 
	 */
	public function __construct()
	{

		$this->host=  C('PRO.HOST_IP_NO_OUTSIDE');
		$this->port=  C('PRO.PORT_CUREE_MATCH');

		$this->server = new \swoole_server($this->host, $this->port);
		$this->server->set($this->setArr);
		$this->server->on('Start', [$this, 'onStart']);
		$this->server->on('workerStart', [$this, 'onWorkerStart']);
		$this->server->on('Connect', [$this, 'onConnect']);
		$this->server->on('Receive', [$this, 'onReceive']);
		// $this->server->on('task', [$this, 'onTask']);
		// $this->server->on('finish', [$this, 'onFinish']);
		$this->server->on('Close', [$this, 'onClose']);
		$this->server->start();
	}

	public function index(){
		echo "swoole start\n";
	}

	/**
	 * 启动swoole服务
	 * @author lirunqing 2018-07-16T10:16:00+0800
	 * @param  object $server    swoole server 对象
	 * @return null
	 */
	public function onStart($server){
		echo "swoole server on start success\n";
	}

	/**
	 * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
	 * @author lirunqing 2018-07-16T10:39:04+0800
	 * @param  object $server   swoole server 对象
	 * @param  int $workerId    进程id
	 * @return null
	 */
	public function onWorkerStart($server, $workerId){
		// $redisObj      = new RedisCluster();
		$redis         = RedisCluster::getInstance();
		$server->redis = $redis;
		$key           = $this->rediskey;
        if ($server->worker_id <= $this->setArr['worker_num']) {
			// 定时生成订单
			$server->tick(500, function()use($key, $server){
				while (true) {
                    $orderData = $server->redis->brpop($key,60);
                    if (empty($orderData))continue;
                    $data    = unserialize($orderData[1]);
                    $userId  = $data['userId']*1;
                    $orderId = $data['orderId']*1;
                    if($userId < 1 or $orderId < 1) continue;
					$this->matchingOrder($data, $userId, $orderId);
				}
			});
		}
	}

	/**
	 * 接收tcp客户端数据
	 * @author lirunqing 2018-07-19T14:36:54+0800
	 * @param  [type] $server    [description]
	 * @param  [type] $fd        [description]
	 * @param  [type] $reactorId [description]
	 * @param  [type] $data      [description]
	 * @return [type]            [description]
	 */
	public function onReceive($server, $fd, $reactorId, $data) {
		echo "on receive\n";
	}

	/**
	 * 建立swoole连接成功
	 * @author lirunqing 2018-07-16T10:18:10+0800
	 * @param  object $server    swoole server 对象
	 * @param  int $fd        	 TCP客户端连接的唯一标识符
	 * @param  int $reactorId 	 TCP连接所在的Reactor线程ID
	 * @return bull
	 */
	public function onConnect($server, $fd, $reactorId){
		echo "swoole server on connect success\n";
	}

	/**
	 * task_worker进程内被调用
	 * @author lirunqing 2018-07-16T15:30:04+0800
	 * @param  object $server  swoole server 对象
	 * @param  int $taskId     任务id
	 * @param  int $wokerId    进程id
	 * @param  string $data    
	 * @return 
	 */
	// public function onTask($server, $taskId, $wokerId, $data){
	// 	echo "task is start.\n";
	// }

	/**
	 * task_worker进程中完成
	 * @author lirunqing 2018-07-16T15:30:04+0800
	 * @param  object $server  swoole server 对象
	 * @param  int $taskId     任务id
	 * @param  int $wokerId    进程id
	 * @param  string $data    
	 * @return 
	 */
	// public function onFinish($server, $taskId, $data){

	// }

	/**
	 * TCP客户端连接关闭后，在worker进程中回调此函数
	 * @author lirunqing 2018-07-16T10:25:58+0800
	 * @param  object $server    swoole server对象
	 * @param  int $fd        	 TCP客户端连接的唯一标识符
	 * @param  int $reactorId 	 TCP连接所在的Reactor线程ID
	 * @return null
	 */
	public function onClose($server, $fd, $reactorId){
		echo "server is closed by ".$fd."\n";
	}

	/**
	 * 生成交易订单
	 * @author lirunqing 2018-07-16T14:43:36+0800
	 * @param  array $data              用户提交的交易信息
	 * @param  int $userId            用户id
	 * @return bool|int
	 */
	// public function addOrder($data, $userId){

	// 	if (empty($data) || empty($userId)) {
	// 		echo "111000000 error code\n";
	// 		return false;
	// 	}

	// 	$currencyLogicsObj = new CurrencyTradingLogicsController();

	// 	$orderRes = $currencyLogicsObj->addOrderByUserSubData($data, $userId);
	// 	if (empty($orderRes) || $orderRes['code'] != 200) {
	// 		echo "生成订单失败 :".$orderRes['code']."\n";
	// 		return false;
	// 	}

	// 	if (empty($orderRes['data']['id'])) {
	// 		echo "555 error code : ".$orderRes['code']."\n";
	// 		return false;
	// 	}

	// 	echo "must success 订单生成成功，id : ".$orderRes['data']['id']." \n";

	// 	return $orderRes['data']['id'];
	// }

	/**
	 * 匹配订单
	 * @author lirunqing 2018-07-16T14:43:36+0800
	 * @param  array $data              用户提交的交易信息
	 * @param  int $userId            用户id
	 * @param  int $pendingId         新订单的id
	 * @return bool
	 */
	public function matchingOrder($data, $userId, $pendingId){

		if (empty($data) || empty($userId) || empty($pendingId)) {
			echo "订单生成信息出错 \n";
			return false;
		}

		$currencyLogicsObj = new CurrencyTradingLogicsController();
		$matRes            = $currencyLogicsObj->matchingOrder($data, $userId, $pendingId);

		if (empty($matRes) || $matRes['code'] != 200) {
			echo "暂无匹配订单 : ".$matRes['code']."\n";
			return false;
		}

		echo "6666 success 订单匹配成功，id: ".$matRes['data']['id']." \n";

		return true;
	}
}

$swooleServer = new CurrencyTradeServerController();
$swooleServer->index();