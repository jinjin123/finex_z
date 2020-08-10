<?php
namespace SwooleCommand\Controller;
use Think\Controller;
use Swoole\Table;
use SwooleCommand\Logics\WebsocketPush;
/**
 * @author 建强  2018年3月14日14:37:23
富国  20180425 修改
 * @method swoole 应用使用websockt协议 pc端推送
 */

class SwooleServerController  extends Controller
{
    protected  $host=NULL;
    protected  $tcp_host =NULL;
    //服务连接端口
    protected  $server_port=NULL;
    //任务分发监听端口
    protected  $push_port=NULL;
    public $ws_serv_obj = null;
    public $tcp_server = null;
    public $table_obj = null;
    const TABLE_SIZE = 1048576; //内存表最大行数
    public $websocket_push_obj = null;
    protected  $ws_server_set_arr =  array(
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 601,
        'daemonize'=> 1, //1,后台守护进程
        'task_worker_num' => 4,
        'log_file'=>'Upload/swoole.log',
    );

    protected  $tcp_server_set_arr =  array(
        'reactor_num' => 2, //reactor thread num
        'worker_num' => 2,    //worker process num
        'backlog' => 128,   //listen backlog
        'max_request' => 50,
        'dispatch_mode' => 1
    );

    public function  __construct() {

        $this->host=C('PRO.HOST_IP_WEB_SOCKET_CLIENT');
        $this->tcp_host =C("PRO.HOST_IP_WEB_CLIENT");
        $this->server_port=C('PRO.PORT_WEBSOCKET_SERVER');
        $this->push_port  =C('PRO.PORT_CLIENT_USER');

        $this->ws_serv_obj =new \swoole_websocket_server($this->host,$this->server_port);
        $this->websocket_push_obj = new WebsocketPush();
        //监听推送的服务端口
        $this->tcp_server = $this->ws_serv_obj->addlistener($this->tcp_host,$this->push_port, SWOOLE_SOCK_TCP);
        $this->ws_serv_obj->set($this->ws_server_set_arr);
        $this->tcp_server->set($this->tcp_server_set_arr);
        $this->ws_serv_obj->on("workerStart",[$this,'onWorkerStart']);
        $this->ws_serv_obj->on("open", [$this, 'onOpen']);
        $this->ws_serv_obj->on("message", [$this, 'onMessage']);
        $this->ws_serv_obj->on("task", [$this, 'onTask']);
        $this->ws_serv_obj->on("finish", [$this, 'onFinish']);
        $this->ws_serv_obj->on("close", [$this, 'wsClose']);
        $this->tcp_server->on("connect", [$this, 'tcpConnect']);
        $this->tcp_server->on("receive", [$this, 'onReceive']);
        $this->tcp_server->on("close", [$this, 'tcpClose']);
        $this->table_obj = new \Swoole\Table(self::TABLE_SIZE); //内存表大小，行数
        $this->table_obj->column('fd',Table::TYPE_INT);
        $this->table_obj->column('data',Table::TYPE_STRING,512);
        $this->table_obj->column('service_name',Table::TYPE_STRING,128);
        $this->table_obj->create();
        $this->ws_serv_obj->table_obj = $this->table_obj;
        $this->ws_serv_obj->start();
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @author lirunqing 2018-07-16T10:39:04+0800
     * @param  object $server   swoole server 对象
     * @param  int $workerId    进程id
     * @return null
     */
    public function onWorkerStart($server, $workerId){
    }
    /**
     * 监听ws连接事件
     * @param $server
     * @param $req
     */
    public function onOpen($server, $req){
        $server->push($req->fd, $this->websocket_push_obj->responseJson(1,"success",
            ['method' => 'connection','status' => 1, 'error_code' => 0]));
    }

    /** 获取PC端或者APP端客户申请信息，并设置用户标识
     * @param $server
     * @param $frame
     * @param $redis
     * 刘富国
     * 20180409
     */
    public function onMessage($server, $frame) {
        $this->websocket_push_obj->subscriptionServices($server,$frame);
    }


    public function onTask($server, $task_id, $from_id, $data){
        $service_name   = $data['service_name'];
        if(empty($service_name)) return 'task_id:'.$task_id.' service_name is empty';
        $send_status = false;
        $ret_msg = ' no user on message:'.$service_name.' no  Websocket push';
        if($server->table_obj->count() == 0 )   return 'no user on message';
        //服务端推消息给websocket
        if($service_name == 'JpushWebsocket'  ){
            $send_status = $this->websocket_push_obj->pushJpush($server,$data);
        }
        //广播WebsocketMsg
        if($service_name == 'WebsocketMsg'){
            $send_status = $this->websocket_push_obj->pushWebsocketMsg($server,$data);
        }
        //广播price
        if($service_name == 'pcMarketInfoList'
            or $service_name == 'pcCoinInfoList'
            or $service_name == 'appCoinInfoList' ){
            $send_status = $this->websocket_push_obj->pushPrice($server,$data);
        }

        //二维码登录
        if($service_name == 'QRLogin'
            and !empty($data['data']['push_data']['php_session_id'])
            and $data['data']['push_data']['QRLoginSuccess'] == '1'){
            $send_status = $this->websocket_push_obj->pushQRLogin($server,$data);
        }
        if($send_status){
            $ret_msg = 'task_id:'.$task_id.' service_name: '.$service_name.'  push success';
        }else{
            $ret_msg = $ret_msg.':'.$this->websocket_push_obj->errmsg;
        }
        return $ret_msg;
    }

    //处理异步任务的结果
    public function onFinish($server, $task_id, $data)
    {
        echo "AsyncTask[$task_id] Finish: $data".PHP_EOL;
    }

    /**
     * 监听TCP服务推送消息给客户端
     * @param $server
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public  function onReceive($server, $fd, $from_id, $data){
        $data = json_decode($data, true);
        if( empty($data['data']) or  empty($data['service_name']) )  return false;
        $service_name   = $data['service_name'];  //服务名称
        $server->send($fd, $this->websocket_push_obj->responseJson(1,"success",
            ['service_name' => $service_name, 'error_code' => 0, 'status' => 1]));
        $server->task($data);
        return true;
    }

    /**
     * tcp连接
     * @param $server
     * @param $fd
     */
    public function tcpConnect($server, $fd){
        // echo " tcp Client Connect:  $fd.\n";
    }

    /**
     * WS连接断开 关闭集合的
     * @param $server
     * @param $fd
     */
    public function wsClose($server, $fd){
        $this->websocket_push_obj->delServiceKeyByFd($fd,$server);
        //  echo "ws connection close:  $fd.\n";
    }

    /**
     * tcp连接断开 关闭集合的
     * @param $server
     * @param $fd
     */
    public function tcpClose($server, $fd){
        // echo " tcp Client Close: $fd.\n";
    }
}
