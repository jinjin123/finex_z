<?php
namespace SwooleCommand\Controller;
use SwooleCommand\Logics\SampleIndex;

class SymbolRevlDataController  extends BaseCommandController
{
    /**
     * @var integer 定时器毫秒
    */
    const timer  = 6000;
    /**
     * @var object 数据样本对象
     */
    private $sampleIndex = null;
    private $serv,$host,$port;
    /**
      * @author 建强   2019年5月22日10:23:50 
      * @method 设置回调方法 启动任务
     */
    public function __construct(){
        
        parent::__construct();
        $this->host  = '127.0.0.1';
        $this->port  = 9553;
        $this->serv  = new \swoole_server($this->host,$this->port);
        //set 设置启动参数
        $this->serv->set([
            'worker_num'     => 1,       
            'daemonize'      => 1,  
            'log_file'       =>'swoole.log',
            'dispatch_mode'  => 2,
           ]
        );
        //init 全局对象 
        $this->sampleIndex =  new SampleIndex();
        //call 回调方法 
        $this->serv->on('Start',   [$this, 'onStart']);
        $this->serv->on('WorkerStart',[$this, 'onWorkerStart']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on("Receive", [$this, 'onReceive']);
        $this->serv->start();
    }
    /**
     * @author 建强 2019年5月21日 下午2:24:55
     * @method 启动服务
     */
    public function onStart($serv){
        echo SWOOLE_VERSION . " onStart\n";
    }
    /**
     * @author 建强 2019年5月21日 下午2:37:28
     * @method 启动服务时执行
     */
    public function onWorkerStart($serv){
        //默認啟動會有三個進程  master主进程,管理进程，worker进程
        //避免重複 添加定時器 
        if($serv->worker_id == 0){
            $timer = self::timer;
            swoole_timer_tick($timer,function(){
                echo  'timer '.date('Y-m-d H:i:s'). PHP_EOL;
                $this->sampleIndex->setTime(time())->getApi();
            });
        }
    }
    /**
     * @author 建强 2019年5月21日 下午2:29:43
     * @method 客户端链接
     */
    public function onConnect($serv, $fd){
        echo $fd."Client Connect.\n";
    }
}