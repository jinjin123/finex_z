<?php
namespace Home\Controller;

use Think\Controller;
use Home\Logics\TradingView;

/**
 * K线图
 * @date 2019年5月6日 下午3:31:47
 * 
 * @author Jungle
 */
class CandleController extends Controller
{

    public function _initialize()
    {
        header("Access-Control-Allow-Origin: *");
    }
    
    /**
     * 初始化TradingView
     * 
     * @author Jungle 2019年5月6日 下午3:37:22
     */
    public function web()
    {
        $str = http_build_query(I('get.'));
        redirect('/Public/Home/tradeview/build/mobile.html?' . $str);
    }

    /**
     * 获取tradingview配置数据
     * @author Jungle 2019年5月9日 上午10:21:42
     */
    public function getSymbol()
    {
        $symbol = trim(I('symbol'));
        $data = [
            'symbol' => TradingView::getSymbol($symbol),
            'option' => TradingView::getOption(),
            'config' => TradingView::getConfig()
        ];
        return $this->ajaxReturn($data);
    }
    
    /**
     * 获取tradingview配置数据
     * 
     * @author Jungle 2019年5月9日 上午10:21:42
     */
    public function getAppSymbol()
    {
        $lang = trim(I('lang')); // 语言包
        $symbol = trim(I('symbol')); // 商品名称
        $interval = trim(I('time')); // 切换时间的分辨率
        $data = [
            'symbol' => TradingView::getSymbolForWeb($symbol),
            'option' => TradingView::getOptionForWeb($lang, $interval),
            'config' => TradingView::getConfig()
        ];
        return $this->ajaxReturn($data);
    }

    /**
     * 获取交易数据
     * 
     * @author Jungle 2019年5月9日 上午10:21:13
     */
    public function getCandles()
    {
        
        $symbol = trim(I('symbol'));
        $granularity = trim(I('granularity'));
        $size = 1000; // 默认1000条
        $url = "https://www.okex.me/v2/spot/instruments/%s/candles?granularity=%d&size=%d";
        $url = sprintf($url, $symbol, $granularity, $size);
        $data = vget($url);
        return $this->ajaxReturn(json_decode($data));

//         $result = M('BtcKline')->where([
//             'type' => $granularity,
//             'price_time' => ['egt', 1561357620]
//         ])->field([
//             'open', 'high', 'low', 'close', 'price_time as time', 'volume'
//         ])->order('time desc')->limit(0, 1000)->select();
        
//         $_result = [];
//         foreach($result as $k => $v){
//             array_push($_result, [
//                 $v['time'] * 1000, 
                
//                 $v['open'], 
//                 $v['high'], 
//                 $v['low'],
//                 $v['close'],
                
// //                 $v['open'],
// //                 $v['low'],
// //                 $v['high'],
// //                 $v['close'],
                
//                 $v['volume']
//             ]);
//         }
        
//         $result = array_reverse($_result);
        
//         $data = [
//             'code' => 0, 'detailMsg' => '', 'msg' => '', 'data' => $result
//         ];
        
//         return $this->ajaxReturn($data);
        
    }
} 