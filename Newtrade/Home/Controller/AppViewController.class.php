<?php
namespace Home\Controller;

use Think\Controller;
use Home\Logics\TradingView;

/**
 * TradingView APP版
 * @date 2019年5月6日 下午3:31:47
 * 
 * @author Jungle
 */
class AppViewController extends Controller
{

    // public function _initialize()
    // {
    // header("Access-Control-Allow-Origin: *");
    // }
    
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
     * 
     * @author Jungle 2019年5月9日 上午10:21:42
     */
    public function getSymbol()
    {
        $lang = trim(I('lang')); // 语言包
        $theme = trim(I('theme'));
        $symbol = trim(I('symbol')); // 商品名称
        $interval = trim(I('time')); // 切换时间的分辨率
                                     
        // 是否数字
        if (is_numeric($theme)) {
            $theme = $theme == 2 ? 'dark' : 'light';
        }
        
        $data = [
            'symbol' => TradingView::getSymbolForWeb($symbol),
            'option' => TradingView::getOptionForWeb($lang, $interval),
            'config' => TradingView::getConfig(),
            'theme' => $theme
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
    }
} 