<?php
/**
 * @author lirunqing 2018年3月14日14:48:29
 * @describe websocket服务，向websocket服务端推送数据
 */
namespace SwooleCommand\Controller;
use Think\controller;
use Home\Controller\CoinTradeInfoController;
use Common\Api\redisKeyNameLibrary;
use Common\Api\RedisCluster;
use Home\Tools\HttpCurl;
use Common\Api\Maintain;

class WebSocketPushClientController extends Controller{

	protected $host ;
	protected $port ;
    
    protected $redis=NULL;
	public  function  __construct()
	{
		$this->host=C("PRO.HOST_IP_WEB_CLIENT");
		$this->port=C('PRO.PORT_CLIENT_USER');
	    // $redisObj = new RedisCluster();
	    $this->redis= RedisCluster::getInstance();
	    parent::__construct();
	}

	/**
	 * PC数据包组装打包，发送tcp请求，推送数据到websocket服务端
	 * @author 2018-03-14T15:46:19+0800
	 * @return string
	 */
	public function pushDataToServer(){
        //PC端价格牌推送信息
        $pcPushData =  [
		    'method' => 'push',
            'service_name' => 'pcCoinInfoList',
			'data'   => [
				'time'      => time(),
				'message'   => 'PC端价格信息推送到服务端',
				'push_data' => []
			],
		];

		$coinInfo = $this->getP2PCoinInfo();
		$pcPushData['data']['push_data'] = $coinInfo['pcCoinInfoList'];
		$pcMessage    = json_encode($pcPushData);
		$retPcData       = $this->sendTcpMessage($pcMessage);
        
		$serverPcData = json_decode($retPcData, true);
        
		if($serverPcData['status'] == 1) {
		    echo "pc服务端已经处理推送请求\r\n";
		} else {
		    echo "无客户端订阅pc服务端价格信息\r\n";
		}
       
	}

    /**
     * APP数据包组装打包，发送tcp请求，推送数据到websocket服务端
     * @author lirunqing 2019年2月13日14:33:40
     * @return string
     */
    public function pushDataToAppServer(){
        //APP端价格牌推送信息
        $appPushData =  [
            'method' => 'push',
            'service_name' => 'appCoinInfoList',
            'data'   => [
                'time'      => time(),
                'message'   => 'APP端价格信息推送到服务端',
                'push_data' => []
            ],
        ];


        $appCoinInfoCacheKey = redisKeyNameLibrary::APP_COIN_INFO_LIST_BY_BIF;
        $redis               = RedisCluster::getInstance();
        $appInfo             = $redis->get($appCoinInfoCacheKey);
        $appCoinInfoList     = unserialize($appInfo);
        $appPushData['data']['push_data'] = $appCoinInfoList;
        $appMessage    = json_encode($appPushData);
        $retAppData    = $this->sendTcpMessage($appMessage);
        $serverAppData = json_decode($retAppData, true);

        if($serverAppData['status'] == 1) {
            echo "app服务端已经处理推送请求\r\n";
        } else {
            echo "无客户端订阅app服务端价格信息\r\n";
        }
    }

	/**
	 * 获取p2p各个币种信息
	 * @author lirunqing 2018-03-14T16:05:54+0800
	 * @return array
	 */
	public function getP2PCoinInfo(){
		// $redisObj = new RedisCluster();
        $redis                = RedisCluster::getInstance();
        $longPricArr          = array();
        $coinInfoList         = array();
        $coinAppInfoList      = array(); //app端返回格式
        $coinInfoKey          = redisKeyNameLibrary::COIN_INFO_LIST_BY_BIF;
        $coinInfoLongCacheKey = redisKeyNameLibrary::COIN_INFO_LIST_LONG_BY_BIF;
        $appCoinInfoCacheKey = redisKeyNameLibrary::APP_COIN_INFO_LIST_BY_BIF;
       // if(!empty($coinInfoList)) return unserialize($coinInfoList);
		$coinTradeInfoObj = new CoinTradeInfoController();
		$currencyList = M('Currency')->field('currency_mark,id,currency_name,currency_app_logo')->where(['status' =>1])->select();
		// 获取币种所属市场
		$currencyConfigList = M('CoinConfig')->field('currency_id,flag')->select();
		$configTemp         = array();
		foreach ($currencyConfigList as $key => $value) {
			$configTemp[$value['currency_id']] = $value['flag'];
		}
        foreach ($currencyList as $key => $value) {
            $app_logo[$value['id']] = $value['currency_app_logo'];
        }
		foreach ($currencyList as $key => $value) {
			if ($value['id'] == 6) {
				unset($currencyList[$key]);
				continue;
			}
			$testTemp['currency_name'] = $value['currency_name'];
			$testTemp['currency_id']   = $value['id'];
            $testTemp['currency_app_logo']   = $value['currency_app_logo'];
			$testTemp['flag']          = !empty($configTemp[$value['id']]) ? $configTemp[$value['id']] : 0;
			$coinArr[] = $testTemp;
		}

		$coinReturnArr = $coinTradeInfoObj->getCoinInfoFromBitfixByArr($coinArr);

		// 如果某币种返回的成交数量返回为空了，则用缓存数据
		if(empty($coinReturnArr[1]['num']) && empty($coinReturnArr[1]['last_usa'])){
			unset($coinReturnArr);
			$coinReturn = $redis->get($coinInfoLongCacheKey);
			$coinReturnArr = unserialize($coinReturn);
		}

        $coinAppInfoList = $this->fromatAppCoinInfo($coinReturnArr,$app_logo);
        $coinAppInfoListJson = serialize($coinAppInfoList);
        $redis->setex($appCoinInfoCacheKey, 300, $coinAppInfoListJson);// APP缓存5分钟
		$coinInfoListJson = serialize($coinReturnArr);
		$redis->setex($coinInfoKey, 300, $coinInfoListJson);// 缓存5分钟获取的b站数据


		$longPricArrJson  = serialize($coinReturnArr);
		$redis->setex($coinInfoLongCacheKey, 24*3600, $longPricArrJson);// 缓存2小时获取的b站数据
		return array('pcCoinInfoList' =>$coinReturnArr,'appCoinInfoList'=>$coinAppInfoList);
	}

    public function  fromatAppCoinInfo($coinReturnArr,$app_logo){
	    if(!is_array($coinReturnArr)) return array();
        foreach($coinReturnArr as  $coinValue){
            foreach($coinValue as $k =>$v){
                if(is_numeric($v)){
                    $coinValue[$k]= (string)$v;
                }
            }
            $conin_info['vol']          = $coinValue['num'];
            $conin_info['money_usa']    = $coinValue['money_usa'];
            $conin_info['high']         = $coinValue['high_usa'];
            $conin_info['rtq']          = $coinValue['last_usa'];
            $conin_info['low']          = $coinValue['low_usa'];
            $conin_info['rate']         = $coinValue['perc_per'];
            $conin_info['perc_status']         = $coinValue['perc_status'];
            $conin_info['currency_id']        = $coinValue['currency_id'];
            $conin_info['currency_name']      = $coinValue['currency_name'];
            $conin_info['currency_logo']      = 'https://www.btcsale.com/'.$app_logo[$coinValue['currency_id']];

            $currencyPic = strtolower($coinValue['currency_name']).'.png';
            $conin_info['app_index_log'] = C('app_index_log').$currencyPic;
            $conin_info['app_pull_checked_log'] = C('app_pull_checked_log').$currencyPic;
            $conin_info['app_pull_nochecked_log'] = C('app_pull_nochecked_log').$currencyPic;
            $conin_info['night_log'] = C('night_log').$currencyPic;
            $coinAppInfoList[] = $conin_info;
        }
        return $coinAppInfoList;
    }
    /**
	 * 发送消息到 tcp server
	 * @author lirunqing 2018年4月27日23:54:59
	 * @param  json $message 消息体
	 * @return json 服务端返回消息体
	 */
    public function sendTcpMessage($message){
    	$host = $this->host; 
		$port = $this->port;
	 	if(!class_exists('swoole_client')) {
	 	   // echo('无swoole_client类'.PHP_EOL);
            return false;
        }
    	$clientObj =new \swoole_client(SWOOLE_SOCK_TCP);
    	if(!$clientObj->connect($host, $port,1)) {
             echo "连接失败code:".socket_strerror($clientObj->errCode).PHP_EOL;
		    // echo "连接失败22".PHP_EOL;
		    return false;
		}
		// 发送消息给 tcp server服务器
        if(!$clientObj->send($message)){
           //  echo "发送消息失败".PHP_EOL;
            return false;
        }
		// 接受来自server 的数据
		$result = $clientObj->recv();
		$clientObj->close();
		return $result;
    }
  
     /**
      * @author 建强   push滚动行情数据到客户端  2018年6月12日16:45:26 
     */    
     public function PushMarketData()
     {
         $pcMarketPushData=[
                 'method' => 'push',
                 'service_name' => 'pcMarketInfoList',
                 'data'   => [
                         'time'      => time(),
                         'message'   => 'PC首页滚动行情数据',
                         'push_data' => []
                 ],
         ];
         $marketData = $this->PcIndexMarketData();
//	var_dump($marketData);
         $pcMarketPushData['data']['push_data'] = $marketData;
         $pcMessage       = json_encode($pcMarketPushData);
         $serverPcData    = $this->sendTcpMessage($pcMessage);
         $res=json_decode($serverPcData,true);  
         if($res['status'] == 1) {
             echo "pc服务端已经处理推送请求\r\n";
         } else {
             echo "无客户端订阅pc服务端价格信息\r\n";
         }
     }
    
     //缓存pc首页滚动key
     protected $pcMarketKey = 'INDEX_PAGE_MARKET_CURRS_INFO';
     //okexUrl
     //https://www.okex.me/api/spot/v3/instruments/BTC-USDT/ticker
    // protected $okexUrl     = "https://www.okex.me/api/v1/trades.do";
    protected $okexUrl = "https://www.okex.com/api/spot/v3/instruments/";
     //bitfinexUrl
     protected $bitfinexUrl = "https://api.bitfinex.com/v1/trades/";
     //马来西亚接口翻墙
     protected $mysiaUrl    = "http://45.123.100.9:17934/getCoinInfo.php";
     /**
      * @author 建强 2018年8月13日15:44:28
      * @method pc/Index首页 滚动行情
      * @param 'bch|5'=>"?symbol=bch_usdt&since=",
      * @return array
     */
     public function PcIndexMarketData(){
         $pcData=$this->redis->get($this->pcMarketKey);

         if(!empty($pcData)) return unserialize($pcData);
         $marketData=[];
         $currs = Maintain::getOnlineCurrencyList();

         if(empty($currs)) return $marketData;
         foreach($currs as $value){ 
             $params = strtolower($value['currency_name']).'-usdt/ticker';
             $tmp    = $this->getApiData($value['currency_name'],$params,$value['id']);
             if(!empty($tmp)) $marketData[]=$tmp;
         }


         $this->redis->setex($this->pcMarketKey, 300, serialize($marketData));
         return $marketData; 
     }
    /**
     * @method 获取api接口数据
     * @param  string $currs currens
     * @param  string $param  
     */
     protected function getApiData($currency_name,$param,$curr_id){
         try{
         	//okex 没有数据 获取bitfix
             if($curr_id==8) return $this->getbitfinexTrades($currency_name);
	       	 $okexUrl = $this->okexUrl.$param;
	       	 $apiRes  = vget($okexUrl);
	       	 if(empty($apiRes)) return [];
	       	 $apiData = $this->sigleOne(json_decode($apiRes,true),strtoupper($currency_name));
	       	 return $apiData;
         }catch(\Think\Exception $e){
       	     return [];
         }
     }
    /**
     * @method  单个数据渲染重组 
     * @param   array $apiData
     * @param   string $currency_name
     * @param   int $currency_id
     * @return  array
     */
    private function sigleOne($apiData,$currency_name){   
    	//保证滚动行情有数据  构造数据 
    	$data= [
    			'lastPrice'=>$apiData['last'],
    			'Volume'=>number_format($apiData['base_volume_24h'],4),
    			'currencyName'=>$currency_name,
    			'upordown'	=>($apiData['last']>$apiData['open_24h'])?200:100,
    	];
    	
    	//构造30个数据
    	for ($i = 0; $i < 30; $i++) {
    		$data['price'][] = rand($apiData['low_24h'],$apiData['high_24h']);
    	}
    	
    	return $data;
    	
    	
    	/* $arr=[];
    	if(!is_array($apiData) || count($apiData)<=0) return $arr;
        if(count($apiData)>=30){
        	$apiData=array_slice($apiData, 0,30);                      //取前三十个 
        }
        foreach($apiData as $v){
        	$arr['price'][]=$v['price'];                               //所有价格
        	$arr['Volume']+=$v['amount'];                              //sum总量总量
        	$arr['currencyName']=$currency_name;                       //加上币种的信息
        }
        $last=end($arr['price']);
        $arr['lastPrice']=$first=current($arr['price']);               //第一个价格
        $arr['Volume']=number_format($arr['Volume'],4);
        $arr['upordown']=100;
        //计算涨停
        if($last>$first) $arr['upordown']=200;
        return $arr; */
    }
    
    /**
     * @author 建强 2019年3月13日 上午10:37:48
     * @method 组装接口数据格式  usdt 行情数据
     * @return array
     */
    private function getbitfinexTrades($currency_name){
        $ret     = [];
        $url     ='https://api-pub.bitfinex.com/v2/trades/tUSTUSD/hist?limit=30';
        $baseEncodeUrl = base64_encode($url);
        $mysiaUrl= $this->mysiaUrl."?url={$baseEncodeUrl}&type=2";
        $res     = HttpCurl::postRequest($mysiaUrl);
        $coin_arr= json_decode($res,true);
        if(empty($coin_arr) || !is_array($coin_arr)) return $ret;
        //组装数据
        foreach($coin_arr as $value){
            $tmp_price     = ($value[3]>0)?$value[3]:0;
            $ret['price'][]= $tmp_price;                               //所有价格
            $ret['Volume']+= abs($value[2]);                           //sum总量总量
            $ret['currencyName']=$currency_name;                       //加上币种的信息
        }
        $last=end($ret['price']);
        $ret['lastPrice']=$first=current($ret['price']);               //第一个价格
        $ret['Volume']=number_format($ret['Volume'],4);
        $ret['upordown']=100;
        if($last>$first) $ret['upordown']=200;
        return $ret;
    }
}
