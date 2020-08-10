<?php
namespace SwooleCommand\Logics;
use  Home\Tools\SceneCode;
/**
 * @author 建强  swoole 业务逻辑层   注意命名格式不要带controller 无需继承底层类
 */
class TradePush
{
    /**
     * @author 建强 2019年7月15日 下午12:12:53
     * @method swoole 换成同步发送 
     */
    public function pushExec($id,$timer,$type){
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if(!$client->connect(C('PRO.HOST_IP_NO_OUTSIDE'), C('PRO.PORT_ORDER_MSG'),2)){
            return false; 
        }
        $url  = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/curl/pushOrderMsg?';
        $data = [
            'id'=>(int)$id,'timer'=>(int)$timer,
            'type'=>strtoupper($type),'url'=>base64_encode($url)
        ];
        
        $client->send(json_encode($data));
        $res = $client->recv();
        if($res == 200) $client->close();
        return false;
    }
    /**
     * @param  int    $id    订单id
     * @param  string $type  交易模式  c2c
     * @return string
     */
    public function pushTradeMsg($id,$type){
        if(empty($type)) return 'type error';
        if($type=='C2C') return $this->CcTradePush($id);
        
        return  $this->OffTradePush($id);
    }
    /**
     * @author 建强 2019年7月15日 上午10:32:29
     * @method c2c订单通知 消息推送
     * @return string
     */
    protected function CcTradePush($id){
        $where = ['id' =>$id,'status'=>1];
        $order = M('CcTrade')->where($where)->find();
        if(empty($order)) return 'order status change c2c'.PHP_EOL;
           
        $orderInfo = [
            'orderNum'        => $order['order_num_buy'],
            'currencyName'    => getCurrencyName($order['currency_type']),
            'rate_total_money'=> $order['rate_total_money'],
            'num'             => $order['trade_num'],
        ];
        
        $extras     = ['send_modle'=>'C2C','new_order_penging'=>1];
        $contentStr = SceneCode::getC2CTradeTemplate(5,'+'.$order['om'],$orderInfo);
        $contentArr = explode('&&&', $contentStr);
        return  push_msg_to_app_person($contentArr[0], $contentArr[1], $order['buy_id'],$extras);
    }
    /**
     *
     * @author 建强 2019年7月15日 上午10:38:55
     * @method P2P 买入5分钟后推送订单通知信息
     * @return string
     */
    public function OffTradePush($id){
        $where = ['id' =>$id,'status'=>1];
        $order = M('TradeTheLine')->where($where)->find();
        if(empty($order)) return 'order status change p2p';
        
        $orderInfo = [
            'orderNum'=>explode('-',$order['order_num'])[0],
            'currencyName'=>getCurrencyName($order['currency_id']),
            'rate_total_money'=>$order['rate_total_money'],
            'num'=>$order['num']
        ];
        $extras     = ['send_modle'=>'P2P','new_order_penging'=>1];
        $contentStr = SceneCode::getP2PTradeTemplate(5,'+'.$order['om'],$orderInfo);
        $contentArr = explode('&&&', $contentStr);
        return  push_msg_to_app_person($contentArr[0], $contentArr[1], $order['buy_id'],$extras);
    }
}