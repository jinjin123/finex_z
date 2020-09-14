<?php 
namespace SwooleCommand\Controller;
use Think\Controller;
 /**
  * @author 建强   2018年7月12日16:04:39
  * @desc   swoole  tcp链接异步定时推送 客户端代码
  */
 class ClientPushController extends Controller
 {
     /**
      * @param  id    int => 子订单id 
      * @param  timer int =>毫秒 推送时间间隔, 
      * @param  type  tableType =>c2c/p2p表
      * @return null
      */
     function pushOrder($id  = 0,$timer =0, $type){  
         $data = [
             'id'   => (int)$id,'timer'=> (int)$timer,  
             'type' =>strtoupper($type),
         ];
         $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
         //at  链接成功 发送数据包 
         $client->on("connect",  function($cli)use($data){
             $cli->send(json_encode($data));
         });
        
         $client->on("receive", function($cli, $data){
             echo "Received: ".$data."\n";
         });
         $client->on("error", function($cli){
             echo "Connect failed\n";
         });
         $client->on("close", function($cli){
             echo "Connection close\n";
         }); 
         //at 链接服务端 
         $client->connect(C('PRO.HOST_IP_NO_OUTSIDE'), C('PRO.PORT_ORDER_MSG'),2);
     }
     
}