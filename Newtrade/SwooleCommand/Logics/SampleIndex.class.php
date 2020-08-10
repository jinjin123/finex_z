<?php
namespace SwooleCommand\Logics;
class SampleIndex
{
    /**
     * @var array 接口地址
    */
    private static $_urls = [
        'gemini'   =>'https://api.gemini.com/v1/pubticker/btcusd',
        'bitstamp' =>'https://www.bitstamp.net/api/v2/ticker/btcusd/',
        'kraken'   =>'https://api.kraken.com/0/public/Ticker?pair=XBTUSD',
        'coinbase' =>'https://api.pro.coinbase.com/products/BTC-USD/ticker',
        'okcoin'   =>'https://www.okex.me/api/spot/v3/instruments/BTC-USDT/ticker', 
    ];
    /**
     * @var array 分辨率 /分钟
    */
    private static  $_revloution_min  =  [1,3,5,15,30];
    /**
     * @var array 分辨率 /小时 
     */
    private static  $_revloution_hour = [1,2,4,6,12];
    /**
     * @var array 分辨率 /天
     */
    private static $_revloution_day   = [1,7];
    /**
     * @var array 默认数据格式   統一格式 (okex) 
     */
    private static $_data_format = ['O','H','L','C'];
    /**
     * @var int 刻度分辨率时间
     */
    private $_time ;
    /**
     * @author 建强 2019年5月22日 上午11:04:29
     * @method 设置时间刻度
    */
    public function setTime($time){
        $this->_time = $time;
        return $this;
    }
    /**
     * @author 建强 2019年5月21日 下午2:42:48
     * @method 获取接口样本指数 
     */
    public function getApi(){
         $data   = self::getIndexDataByThirdApi();
         if(empty($data)) return ;
         //OLCH  数据计算  /revloution 分辨率计算
         $OLCH = $this->DataCalculat($data);
         $times= $this->getRevloutionTimes();
         
         
        $OLCH = $this->adjustPrice($OLCH);
         
         //['O','H','L','C'];
         //at数据库操作时间  前一个时间 的收盘价等于下一个时间的开盘价 
         $add = [];
         foreach ($times as $value){
             $tmp_data = [
                 'open' =>$OLCH[0],
                 'high' =>$OLCH[1],
                 'low'  =>$OLCH[2],
                 'close'=>$OLCH[3],
             ];
             
             $where = [
                 'price_time'=>strtotime($value['time']),
                 'type'=>$value['type'],
             ];
             $lt_where = ['type'=>$value['type']];
             $res      = M('BtcKline')->where($where)->find();
             
             
             if(empty($res)){
                 $revlou = substr($value['type'], -1, 1);
                 $revlou = ($revlou == 'h') ? rtrim($value['type'],'h')*3600 : $value['type']*60;
                 $lt_where['price_time'] = $where['price_time'] - $revlou;
                
                 $tmp_data = array_merge($tmp_data,$where);
                 //at 查询上一个时间点
                 $last  = M('BtcKline')->where($lt_where)->find();  
                 $tmp_data['open']       = !empty($last) ? $last['close'] : $tmp_data['open'];
                 $tmp_data['price_date'] = date('Y-m-d H:i:s',$tmp_data['price_time']);
                 $tmp_data['type']       = $value['type'];

                 $add[] = $tmp_data; 
                 continue;
             }
             // at更新数据 注意别更新开盘价 
             unset($tmp_data['open']);
             $tmp_data['id'] = $res['id'];
             $res = M('BtcKline')->save($tmp_data);
         }
         if(!empty($add)) dump(M('BtcKline')->addAll($add));
         // push给swoole_websoceket 监听的服务 推送给客户端
         // $push = [ 'data' =>$OLCH,'symbol'=>'BTC'];
         // PushClient::getInstance($push)->pushMsg();
       
    }
    
    
    /**
     * @author 建强 2019年6月13日 下午4:38:16
     * @method 调整价格 
     */
    protected function adjustPrice($price){
        $data  = $price;
        sort($price);
        $max   = $price[3]-$price[0];
        if($max>20){
            $max     = $price[3] - $price[0];
            $data[2] = $data[2] + round($max,$max-10);
        }
        //at 最大最小 小于20  返回原始数据
        return $data;
    }
    
    /**
     * @author 建强 2019年5月30日 下午5:38:39
     * @method 推送数据分辨率 
     * @return int 
     */
    public function getTimeRevloution($time){
    }
    /**
     * @author 建强 2019年5月22日 上午10:58:57
     * @method 获取分辨率数据 
     * @return array 
    */
    public function getRevloutionTimes(){
       $min       = date('i',$this->_time);
       $hour      = date('H',$this->_time);
       //min hour分辨率时间
       $peng_min  = self::getModTime($min, self::$_revloution_min,'min');
       $peng_hour = self::getModTime($hour,self::$_revloution_hour,'hour');
       
       return array_merge($peng_min,$peng_hour);
      
    }
    /**
     * @author 建强 2019年5月23日 上午10:50:42
     * @method 获取模时间  
     * @param  int 时间分钟或者小时数
     * @param  int $revloution 分辨率数 
     * @param  string  $type 
     * @return array  time => ,type=>
     */
    public static function getModTime($hou_min,$revloution,$type ='hour'){
        $peng_time = [] ;
        foreach ($revloution as $value) {
            if($hou_min < $value) continue;
            $tmp     = [] ;
            $mod     = $hou_min%$value;
            $diff    = $mod > 0 ? $mod : 0;
            $tmp_time= $hou_min-$diff;
            
            if($type=='hour'){
                $tmp['type'] = $value.'h';
                $tmp['time'] = date('Y-m-d H:i:s',mktime($tmp_time,0,0));
            }else{
                // min分钟分辨率
                $tmp['type'] = $value;
                $tmp['time'] = date('Y-m-d H:i:s',mktime(date('H'),$tmp_time,0));
            }
            $peng_time[] = $tmp;
        }
        return $peng_time;
    }
    /**
     * @author 建强 2019年5月21日 下午2:55:56
     * @method 数据计算 
     * @return array 
     */
    public function DataCalculat($data){
        $third_name = array_keys($data);
        $olhc       = [];
        for ($i = 0; $i < 4; $i++) {
            $tmp = [];
            foreach ($third_name as $value){
                $tmp[] = $this->getPositionKey($data[$value],$value,$i);
            }
            $olhc[] = $this->cal($tmp);
        }
        return $olhc;
    }
    /**
     * @author 建强 2019年5月21日 下午4:18:28
     * @method 加权平均数
     * @return float
     */
    public function cal($data){
        $data   = array_filter($data); // 0值踢掉 
        $count  = count($data);
        if($count <= 3) return round(array_sum($data)/$count,4);
        
        sort($data);
        $middle =  floor($count/2);
        $top    =  $data[$count-1];
        $rate   =  ($top-$data[$middle])/$data[$middle];
        if($rate > 0.10) $data[$count-1] = $data[$middle]*(1+0.1);
        return floatval(round(array_sum($data)/$count,4));
    }
    /**
     * @author 建强 2019年5月21日 下午3:06:46
     * @method 获取正确的属性位置值
     * @param  array $arr 数据源
     * @param  string $third_name 第三方接口名称
     * @param  int $index 获取的指数
     * @return array
     */
    public function getPositionKey($arr,$third_name,$index){
        // chlo 四个价格排序
        switch ($third_name) {
            case 'gemini':
               //api 接口无标准价格 
               $tmp = [$arr['bid'],$arr['ask'],$arr['last']];
               sort($tmp);
               $tmp = [0,end($tmp),$arr['bid'],$arr['last']];
            break;
            case 'bitstamp':
                //api 接口有标准价格
                $tmp =  [$arr['open'],$arr['high'],$arr['low'],$arr['last']];
            break;
            case 'kraken':
                $arr = $arr['result']['XXBTZUSD'];
                $tmp[] = floatval($arr['o']);
                $tmp[] = floatval($arr['h'][0]);
                $tmp[] = floatval($arr['l'][0]);
                $tmp[] = floatval($arr['c'][0]);
             
            break;
            case 'coinbase':
                $tmp = [$arr['price'],$arr['bid'],$arr['ask']];
                sort($tmp);
                $tmp = [0,end($tmp),$arr['bid'],$arr['price']];
            break;
            case 'okcoin':
                //okcoin 服务器curl无法访问 ,改成okex 
                $tmp = [$arr['open_24th'],$arr['high_24th'],$arr['low_24th'],$arr['last']];
            break;
        }
        return $tmp[$index] ?:0;
    }
    /**
     * @author 建强 2019年5月21日 下午2:48:56
     * @method 调用第三方api获取数据进行计算
     * @return array
    */
    public static function getIndexDataByThirdApi(){
        $data = [];
        foreach(self::$_urls as $key =>$url){
            $res  = vget($url);
            if(empty($res)) continue;
            $res  = json_decode($res,true);
            if(!is_array($res)) continue;
            $data[$key] = $res;
        }
        return $data;
    }
}