<?php
namespace Home\Logics;

/**
 * TradingView相关配置
 * @date 2019年5月6日 下午3:34:46
 * @author Jungle
 */
class TradingView
{
    
    /**
     * 商品名称
     *
     * @var string
     */
    const EXCHANGE = 'BTCS';
    
    /**
     * 商品
     *
     * @var string
     */
    const DEFAULT_SYMBOL = 'BTC';
    
    /**
     * 时间分辨率
     *
     * @var string
     */
    const DEFAULT_RESOLUTIONS = 5;
    
    /**
     * TradingView初始化配置
     *
     * @author Jungle 2019年5月5日 下午3:10:33
     * @return boolean[]|string[]
     */
    public static function getOption()
    {
        $lang = [
            'en-us' => 'en',
            'zh-tw' => 'zh_TW',
            'zh-cn' => 'zh'
        ];
        $cookieLang = cookie('think_language');
        return [
            'debug' => false,
            'symbol' => self::DEFAULT_SYMBOL,
            'interval' => self::DEFAULT_RESOLUTIONS,
            'locale' => array_key_exists($cookieLang, $lang) ? $lang[$cookieLang] : $lang['zh-tw']
        ];
    }
    
    /**
     * TradingView初始化配置（Web版使用）
     *
     * @author Jungle 2019年5月5日 下午3:10:33
     * @return boolean[]|string[]
     */
    public static function getOptionForWeb($locale, $interval)
    {
        //一天
        if($interval == 1440){
            $interval = '1D';
        }
        $lang = [
            'en-us' => 'en',
            'zh-tw' => 'zh_TW',
            'zh-cn' => 'zh'
        ];
        return [
            'debug' => false,
            'symbol' => self::DEFAULT_SYMBOL,
            'interval' => $interval ? $interval : self::DEFAULT_RESOLUTIONS,
            'locale' => array_key_exists($locale, $lang) ? $lang[$locale] : $lang['zh-tw']
        ];
    }
    
    /**
     * 默认配置
     *
     * @author Jungle 2019年5月5日 下午2:47:38
     * @return boolean[]|string[][]
     */
    public static function getConfig()
    {
        return [
            'supports_search' => true,
            'supports_group_request' => false,
            'supported_resolutions' => [
                '1',
                '3',
                '5',
                '15',
                '30',
                '60',
                '120',
                '240',
                '360',
                '720',
                '1D',
                '1W'
            ],
            'supports_marks' => true,
            'supports_timescale_marks' => true,
            'supports_time' => true
        ];
    }
    
    /**
     * 默认商品信息
     *
     * @author Jungle 2019年5月5日 下午2:47:24
     * @return string[]|number[]|boolean[]|string[][]
     */
    public static function getSymbol($symbol)
    {
        
        $symbol = empty($symbol) ? self::DEFAULT_SYMBOL : $symbol;
        
        $symbol = strtoupper($symbol);
        
        $symbolArr = explode('_', $symbol);
        
        // 判断是否币币交易
        if (count($symbolArr) == 2) {
            $desc = implode('/', $symbolArr);
            $symbol = implode('-', $symbolArr);
            $pricescale = 1e8;
        } else {
            $desc = $symbol . '/USDT';
            $symbol .= '-USDT';
            $pricescale = 1e4;
        }
        
        return [
            'exchange' => self::EXCHANGE,
            'symbol' => $symbol,
            'description' => $desc,
            'timezone' => 'Asia/Shanghai',
            'minmov' => 1,
            'minmov2' => 0,
            'pointvalue' => 1,
            'fractional' => false,
            'session' => '24x7',
            'has_intraday' => true,
            'has_no_volume' => false,
            'pricescale' => $pricescale,
            'ticker' => $symbol,
            'supported_resolutions' => [
                '1',
                '3',
                '5',
                '15',
                '30',
                '60',
                '120',
                '240',
                '360',
                '720',
                '1D',
                '1W'
            ],
            'volume_precision' => 8
        ];
    }
    
    /**
     * 默认商品信息（Web版使用）
     * @author Jungle 2019年5月5日 下午2:47:24
     * @return string[]|number[]|boolean[]|string[][]
     */
    public static function getSymbolForWeb($symbol)
    {
        $symbol = self::resetSymbol($symbol);
        
        return [
            'exchange' => self::EXCHANGE,
            'symbol' => $symbol['symbol'],
            'description' => $symbol['desc'],
            'timezone' => 'Asia/Shanghai',
            'minmov' => 1,
            'minmov2' => 0,
            'pointvalue' => 1,
            'fractional' => false,
            'session' => '24x7',
            'has_intraday' => true,
            'has_no_volume' => false,
            'pricescale' => $symbol['pricescale'],
            'ticker' => $symbol['symbol'],
            'supported_resolutions' => [
                '1',
                '3',
                '5',
                '15',
                '30',
                '60',
                '120',
                '240',
                '360',
                '720',
                '1D',
                '1W'
            ],
            'volume_precision' => 8
        ];
    }

    private static function resetSymbol($symbol)
    {
        $symbol = strtoupper(empty($symbol) ? self::DEFAULT_SYMBOL : $symbol);
        $symbolArr = explode('_', $symbol);
        if (strtoupper($symbolArr[1]) == 'USD') {
            $desc = implode('/', [strtoupper($symbolArr[0]), 'USDT']);
            $symbol = implode('-', [strtoupper($symbolArr[0]), 'USDT']);
            $pricescale = 1e4;
        } else {
            $desc = $symbolArr[0] . '/' . $symbolArr[1];
            $symbol = $symbolArr[0] . '-' . $symbolArr[1];
            $pricescale = 1e8;
        }
        return ['symbol' => $symbol, 'desc' => $desc, 'pricescale' => $pricescale];
    }
    
}