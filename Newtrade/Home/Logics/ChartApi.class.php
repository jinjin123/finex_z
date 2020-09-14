<?php
namespace Home\Logics;
use Home\Tools\HttpCurl;
use Common\Api\RedisCluster;
/**
 * @author 宋建强 2018年1月16日17:50:33
 * @desc   k线行情接口数据   okex网站 对外用户接口升级到v3版本 ,内部采用v2版本
 *         okex.com 需要翻墙访问, 香港主机   （okex.me不需要进行翻墙访问）
 */
class ChartApi{
    protected $redis;
    public function  __construct(){
        $this->redis = RedisCluster::getInstance();
    }
    /**
     * v3接口   okex 分辨率  [60 180 300 900 1800 3600 7200 14400 21600 43200 86400 604800]
     * 对应的时间周期     [1min 3min 5min 15min 30min 1hour 2hour 4hour 6hour 12hour 1day 1week]
     * @var array 支持分辨率调整
     */
    protected static $timeFrame = ["1","5","15","30","60","240","D","W"];
    /**
     * @method config配置接口  参考文档说明
     * @return array
     */
    public static function config(){
        return [
            'supports_marks' =>false,
            'supports_time'  =>true,
            'supports_search'=>true,
            'supports_group_request' => false,
            'supported_resolutions'  => self::$timeFrame,
        ];
    }
    /**
     * @author 建强  2019年2月20日10:23:16
     * @method 商品搜索参数配置接口
     * @return array
     */
    public static function symbols(){
        $symbols= trim(I('symbols?symbol'));
        if(strpos($symbols,'_')!==false){
            //币币交易对
            $name   = str_replace('_', '-', $symbols);
            $descr  = strtoupper(str_replace('_', '/', $symbols)) .'. BTCS';
        }else{
            //法币交易对
            $name   = strtoupper($symbols).'-USDT';
            $descr  = strtoupper($symbols).'/USDT' .'. BTCS';
        }
        return [
            "name"                  =>$name,
            'timezone'              =>"Asia/Shanghai",
            'session'               =>"24x7",
            'supported_resolutions'	=>self::$timeFrame,
            'has_intraday'          =>true,
            "has_weekly_and_monthly"=>true,
            "type"                  =>"stock",
            "exchange_listed"       =>"",
            "exchange_trade"        =>"",
            "description"           =>$descr,
            "pointvalue"            =>1,
            "minmov"                =>1,
            "minmov2"               =>0,
            "has_no_volume"         =>false,
            "pricescale"            =>10000,
            "ticker"                =>$name,
        ];
    }
    /**
     * @author 建强  2019年2月20日10:23:16
     * @method 商品搜索参数配置接口
     * @return array
     */
    public static function symbolsApp(){
        $symbols= strtoupper(trim(I('symbols?symbol')));
        if(strpos($symbols,'USD')!==false){
            //法币交易对
            $name   = str_replace('_','-',str_replace('USD', 'USDT', $symbols));
            $descr  = str_replace('-','/',$name).'. BTCS';
        }else{
            //币币交易对
            $name   = str_replace('_', '-', $symbols);
            $descr  = str_replace('_', '/', $symbols) .'. BTCS';
        }
        return [
            "name"                  =>$name,
            'timezone'              =>"Asia/Shanghai",
            'session'               =>"24x7",
            'supported_resolutions'	=>self::$timeFrame,
            'has_intraday'          =>true,
            "has_weekly_and_monthly"=>true,
            "type"                  =>"stock",
            "exchange_listed"       =>"",
            "exchange_trade"        =>"",
            "description"           =>$descr,
            "pointvalue"            =>1,
            "minmov"                =>1,
            "minmov2"               =>0,
            "has_no_volume"         =>false,
            "pricescale"            =>10000,
            "ticker"                =>$name,
        ];
    }
    /**
     * @author 建强 2019年4月1日 下午2:11:03
     * @method tv字符串分辨率转换成接口请求的参数
     * @return int sec
     */
    protected function getResolutionByStr($resolution){
        $resolutionArr= [
            '1'  => 60,
            '5'  => 5*60,
            '15' => 15*60,
            '30' => 30*60,
            '60' => 60*60,
            '240'=> 240*60,
            '1440'=>24*3600,  //适配app分辨率
            'D'  => 24*3600,
            'W'  => 7*24*3600
        ];
        return array_key_exists($resolution, $resolutionArr) ? $resolutionArr[$resolution]:900;
    }
    /**
     * @author 建强 2019年2月20日10:20:29
     * @method k线历史数据接口
     * @param  int from 开始时间
     * @param  int to   结束时间
     * @param  string   resolution 分辨率
     * @param  string   symbol 货币符号
     * @return array
     */
    public function history($symbol=''){
        $from        = I('from');
        $to          = I('to');
        $resolution  = trim(I('resolution'));
        $symbol      = trim(I('history?symbol'));
        if(empty($from) || empty($resolution) || empty($symbol)) return ['s'=>'no_data','error'=>'参数错误'];
        if($resolution =='W' && $to < time()) return ['s'=>'no_data','error'=>''];
        $symbol      = strtoupper($symbol);
        $key         = 'kline_'.$resolution.'_time_'.$symbol;
        $res         = $this->redis->get($key);
        if($res)     return unserialize($res);
        $cacheTime   = $this->getRedisCacheTime($resolution);
        $resolution  = $this->getResolutionByStr($resolution);
        return $this->getOkexApi($from,$to,$resolution,$symbol,$key,$cacheTime);
    }
    
    /**
     * @author 建强 2019年4月1日 上午11:38:02
     * @method 获取缓存时间
     * @param  string $resolution
     * @return int
     */
    protected static function getRedisCacheTime($resolution){
        $time =[
            '1'  =>5*60,  '5'   =>10*60,
            '15' =>30*60, '30'  =>30*60,
            '60' =>1800,  '240' =>1800,
            'D'  =>1800,  'W'   =>1800,
        ];
        return array_key_exists($resolution, $time) ? $time[$resolution] : 600;
    }
    /**
     * @method 线下交易数据
     * @param  int    $from
     * @param  int    $to
     * @param  int    $resolution
     * @param  string $symbol
     * @param  string $key       缓存key
     * @param  int    $cacheTime 缓存时间
     */
    public  function getOkexApi($from,$to,$resolution,$symbol,$key,$cacheTime){
        try{
            $url       = "https://www.okex.me/v2/spot/instruments/{$symbol}/candles?granularity={$resolution}&size=1000";
            $res       = HttpCurl::postRequest($url);
            if(empty($res)) return ['s'=>'no_data','error'=>'okex api error'];
            $res = json_decode($res,true);
            if(empty($res) || !is_array($res)) return ['s'=>'no_data','error'=>'okex api data format error'];
            $list= $this->arrFormatToTv($res);
            $this->redis->setex($key, $cacheTime, serialize($list));
            return $list;
        }catch(\Exception $e){
            return ['s'=>'no_data','error'=>$e->getMessage()];
        }
    }
    /**
     * @author 建强 2019年4月1日 下午12:11:57
     * @method 返回tv 格式的数据
     * @return array
     */
    public function arrFormatToTv($arr){
        $data = [];
        foreach($arr['data'] as $key =>$value){
            $data[$key][0] = strtotime($value[0])*1000;
            $data[$key][1] = floatval($value[1]);
            $data[$key][2] = floatval($value[2]);
            $data[$key][3] = floatval($value[3]);
            $data[$key][4] = floatval($value[4]);
            $data[$key][5] = floatval($value[5]);
        }
        return ['s'=>'ok', 't'=>array_values($data)];
    }
}