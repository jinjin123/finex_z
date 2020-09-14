<?php
namespace Home\Controller;
use Think\Controller;
use SwooleCommand\Logics\TradePush;
/**
 * @author 建强  2019年7月17日 
 * @desc   定时推送 curl api 接口调用方式
 */
class CurlController extends Controller{
    
    //设置一个密码 
    private static $_pass = '~!@123456push';
    
    //订单类型
    private static $_typeArr = ['P2P','C2C'];
    
    /**
     * @author 建强 2019年7月17日 下午3:26:59
     * @method c2c 5分钟后推送 p2p 15分钟后推送
     */
    public function pushOrderMsg () {
        $id   = intval(trim($_REQUEST['id']));
        $type = strtoupper(trim($_REQUEST['type']));
        
        if($_REQUEST['pass']!=self::$_pass) die('deny');
        if($id <= 0 || !in_array($type, self::$_typeArr)) die('params error');
        $tradeLogic = new TradePush();
        
        $ret = $tradeLogic->pushTradeMsg($id,$type);
        echo json_encode($ret);
    }
}


