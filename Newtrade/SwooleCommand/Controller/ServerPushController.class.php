<?php
namespace SwooleCommand\Controller;
/**
 * @author  建强  2018年3月14日14:37:23
 * @desc    swoole 应用使用websockt协议 pc端推送
 * @example php cli.php  SwooleCommand/ServerPush/onStart
 * @example php cli.php  SwooleCommand/ClientPush/pushOrder/id/1/timer/9000/type/c2c 使用异步
 */
class ServerPushController extends BaseCommandController
{
    /**
     * @var object $serv  swoole全局对象 
    */  
    private $serv;
    /**
     * @author 建强 2019年7月15日 上午10:51:12
     * @method 初始化swoole 对象服务 
    */
    public function __construct(){
        $ip   = C('PRO.HOST_IP_NO_OUTSIDE');
        $port = C('PRO.PORT_ORDER_MSG');
        
        $this->serv          = new \swoole_server($ip,$port);
        $this->serv->set([
            'worker_num'      => 1,
            'daemonize'       => 1,     // 后台进程
            'task_worker_num' => 2,
            'max_request'     => 1000,
            'dispatch_mode'   => 2,
            'debug_mode'      => 1,
            'log_file'        => './swoole_push.log',
        ]);
      
        $this->serv->on('Start',   [$this, 'onStart']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on('Task',    [$this, 'onTask']);
        $this->serv->on('Finish',  [$this, 'onFinish']);
        $this->serv->on("Receive", [$this, 'onReceive']);
        $this->serv->on("Close",   [$this, 'onClose']);
        $this->serv->start();
    }
    /**
     * @author 建强 2019年7月23日 下午3:13:50
     * @method 启动服务 
     */
    public function onStart($serv){
        echo SWOOLE_VERSION . " onStart\n";
    }
    /**
     * @author 建强 2019年7月18日 上午10:16:06
     * @method 客户端链接握手 
     */
    public function onConnect($serv, $fd){
        echo $fd."Client Connect.\n";
    }
    /**
     * @author 建强 2019年7月15日 上午10:58:08
     * @method 收到客户端数据包 
    */
    public function onReceive($serv, $fd, $from_id, $data){  
        $serv->send($fd,200);
        $serv->task($data);
    }
    /**
     * @author 建强 2019年7月24日 上午11:45:41
     * @method
    */
    public function onTask($serv, $task_id, $from_id,$data){
        echo PHP_EOL.'recv client data --'.$data .PHP_EOL;
        $data = json_decode($data,true);
        $url  = base64_decode($data['url']);
        $params = [
            'id'=>$data['id'],'type'=>$data['type'],
            'pass'=>'~!@123456push'
        ];
        $url = $url.http_build_query($params);
        // C2C卖出 第一次通知买家付款
        if($data['type'] =='C2C'){
            echo $url.PHP_EOL;
            vget($url);
        }
        
        //at 过五分钟进行是否需要再次通知买家付款
        $timer = swoole_timer_after($data['timer'], function()use($url){
            echo $url.PHP_EOL;
            vget($url);
        });
        var_dump('timer set--'.$timer);
    }
    
    /**
     * @author 建强 2019年7月24日 上午11:48:57
     * @method 
     */
    public function onFinish($serv, $task_id, $data){
        echo "Task#$task_id finished, data_len=".strlen($data).PHP_EOL;
    }
    /**
     * @author 建强 2019年7月18日 上午10:16:48
     * @method 客户端关闭链接触发回调
    */
    public function onClose($serv, $fd){
        echo "- $fd --Client Close.\n";
    }
}
