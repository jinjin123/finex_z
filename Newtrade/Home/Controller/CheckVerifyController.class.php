<?php
/**
 * 验证码及相关验证
 * @author lirunqing
 */
namespace Home\Controller;
use Think\Controller;
use Home\Tools\Page;
use Home\Message\Yunclode;
use Home\Logics\GetNewPrice;
use Think\Cache\Driver\Redis;

class CheckVerifyController extends Controller {

  /**
   * 发送验证码
   * @author 2017-08-18T15:15:51+0800
   * @param  [type]  $om    区号
   * @param  [type]  $phone 手机号
   * @param  [type]  $phoneCodeType   验证码保存到session中的类型
   * @param  integer $uid   用户id
   * @param  integer $type  验证码类型 '1.登陆 2.修改登录密码 3.修改交易密码'
   * @return [type]         [description]
   */
  public function sendPhoneCode($om, $phone, $phoneCodeType, $uid=0, $type=1){

    $res = array(
        'status' => 201,
        'info'   => ''
    );

    try {
      
      $om = !empty($om) ? $om : '+86';

      if (empty($phone)) {
        throw new \Exception(L('__QSRSJHM__'));
      }

      if (empty($phoneCodeType)) {
        throw new \Exception(L('__CSCW__'));
      }

      if (empty($phoneCodeType)) {
        throw new \Exception(L('__CSCW__'));
      }

      // 添加发送验证码日志
      if (!empty($uid)) {
        $user = M('User')->where(array('uid'=>$uid))->find();

        if (empty($user)) {
          throw new \Exception(L('__YHBCZ__'));
        }

        $datalog['uid']      = $user['uid'];
        $datalog['username'] = $user['username'];
        $datalog['phonenum'] = $user['phone'];
        $datalog['type']     = $type;
        $datalog['add_time'] = time();
        M('SendcodeLog')->add($datalog);
      }

      $sendObj = new Yunclode();
      if (empty($uid)) {
        $sendRes = $sendObj->getYunPhoneCode_use_session($om,$phone,$phoneCodeType);
      }else{
        $sendRes = $sendObj->getYunPhoneCode($uid, $om,$phone,$phoneCodeType);
      }

      if($sendRes==0){
        $res['info'] = L('__HOMECZCG__');
        $res['status'] = 200;
      }else if($sendRes == 33){
        throw new \Exception(L('__CZSBMSSMZNHQYT__'));
      }else if($sendRes == 22){
        throw new \Exception(L('__CZSBMXSZDHQST__'));
      }else{
         throw new \Exception(L('__CZSBQSHZS__'));
      }
    } catch (\Exception $e) {
      $res['info'] = $e->getMessage();
    }

    return $res;
  }

  /**  
  * ajax获取当前币种的最新价格   价格为配置项配置
   * @param string xxx
   * @return void|int|string|boolean|array        comment 
   * @author      ZhangYi <1425568992@qq.com>
   * @version   v1.0.0 
   * @copyright  2016-10-27 上午10:00:57
  */
  public function ajaxGetNewpriceByCurrencyid(){
    
    $exchange_id = I('exchange_id');
    $currency_id = I('currency_id');
    $is_online = I('is_online');
    $config = $this->getConfig();
    if($exchange_id == 1){
      $quyu = 'cny';
      $quyu_guojia = 'cn';
      $fuhao = '¥';
    }else{
      $quyu = 'usd';
      $quyu_guojia = 'com';
      $fuhao = '$';
    }

    // 从redis缓存中获取币种交易价格 add by lirunqing
    $redis = new Redis();
    $coinInfo = $redis->get('s_data');
    
    //如果没有获取到，则拿最新的数据 add by lirunqing
    if (empty($coinInfo) && in_array($currency_id, array(1,2))) {
        $BTC = $redis->get('BTC_PRICE_INFO');
        $LTC = $redis->get('LTC_PRICE_INFO');
        if (empty($BTC)) {
			//https://www.okex.me/api/spot/v3/instruments/BTC-USDT/ticker
			//https://www.okex.me/api/v1/ticker.do?symbol=btc_usdt
          $BTC     = vget('https://www.okex.com/api/spot/v3/instruments/BTC-USDT/ticker');
          $BTC     = json_decode($BTC, true);
          $redis->set('BTC_PRICE_INFO', $BTC, 900);
        }
        if (empty($LTC)) {
          $LTC     = vget('https://www.okex.com/api/spot/v3/instruments/LTC-USDT/ticker');
          $LTC     = json_decode($LTC, true);
          $redis->set('LTC_PRICE_INFO', $LTC, 900);
        }
        $coinInfo['BTC'] = $BTC;
        $coinInfo['LTC'] = $LTC;
    }
    // 更换获取币种信息网站来源 add by lirunqing 2017-9-28 15:40:42
    if ($currency_id == 1) {
      // [BID, BID_SIZE, ASK, ASK_SIZE, DAILY_CHANGE, DAILY_CHANGE_PERC, LAST_PRICE, VOLUME, HIGH, LOW]
      // [买入最高价格, 买入最大数量, 卖出最大价格, 卖出最大数量, 每日涨幅数量, 每日涨幅比例, 最后成交价, 成交量, 最高价, 最低价]
      // $BTC     = vget('https://api.bitfinex.com/v2/ticker/tBTCUSD');
      $BTC     = $coinInfo['BTC'];
      // $BTC     = json_decode($BTC, true);
      $btc_rmb = $BTC['last'] * $config['HUILV'];
      $price   = ($exchange_id == 1) ? $fuhao.$btc_rmb : $fuhao.$BTC['last'];
    }

    if($currency_id == 2){
      // $LTC     = vget('https://api.bitfinex.com/v2/ticker/tLTCUSD');
      $LTC     = $coinInfo['LTC'];
      // $LTC     = json_decode($LTC, true);
      $ltc_rmb = $LTC['last'] * $config['HUILV'];
      $price   = ($exchange_id == 1) ? $fuhao.$ltc_rmb : $fuhao.$LTC['last'];
    }

    // 线上交易VP获取15分钟内的最新价格 add by lirunqing 2017年8月29日17:26:50
    if($currency_id == 3){
      // $r = $this->getNewKuaijieCoinPrice($exchange_id, $config);
      if ($exchange_id == 1 ) {
        $r = $redis->get('AV_PRICE_FU');
      }else{
        $r = round($redis->get('AV_PRICE_FU')/$config['HUILV'], 2);
      }

     // $r = round($redis->get('AV_PRICE_FU')/$config['HUILV'], 2);
      $price = $fuhao.$r;
    }

//    if($currency_id == 4){
//      $BTC     = vget('https://www.okex.me/api/v1/ticker.do?symbol=ltc_usdt');
//      $LTC = json_decode($LTC, true);
//      $ltc_rmb = $LTC['ticker']['last'] * $config['HUILV'];
//      $price   = ($exchange_id == 1) ? $fuhao.$ltc_rmb : $fuhao.$LTC['last'];
//    }

    $this->ajaxReturn($price);
  }

  /**
   * 获取公告信息
   * @return [type] [description]
   */
  public function getNoticeInfo(){

    $noticeRes  = $this->getNoticeList(5);
    $noticeList = !empty($noticeRes['list']) ? $noticeRes['list'] : array();

    foreach ($noticeList as $key => $value) {

        if ($key != 0) {
          $value['title'] = str_replace("News", "", $value['title']);
          $value['news']  = 0;
        }else{
          $titleArr = explode('-', $value['title']);
          $value['title'] = ' - '.trim($titleArr[1]);
          $value['news']  = 1;
        }

        $value['add_time'] = date('Y-n-j', $value['add_time']);
        $value['content']  = html_entity_decode($value['content']);
        $noticeList[$key]  = $value;
    }

    $data = array(
      'status' => 200,
      'info'   => '成功',
      'data'   => $noticeList,
    );

    $this->ajaxReturn($data);
  }

  /**
   * 获取消息list
   * @author lirunqing 2017年8月11日15:19:56
   * @param  $num 用户一页多少个
   * @return array
   */
  public function getNoticeList($num=10){

    if (empty($_SESSION['USER_KEY_ID'])) {
      return array();
    }

    $count = M('Notice')->where()->count();
    $page  = new Page($count,$num);
    $show  = $page->show();
          
    $list        = M('Notice')->where()->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
    $res['list'] = $list;
    $res['page'] = $show;

    return $res;
  }

  /**
   * 获取公告总数
   * @author lirunqing 2017年8月11日15:17:54
   * @return int
   */
  public function getNoticeCount(){

    if (empty($_SESSION['USER_KEY_ID'])) {
      return array();
    }
    
    return M('Notice')->where()->count();
  }


  /**
   * 获取线下快捷币交易挂单信息
   * @return [type] [description]
   */
  public function getEntrustInfo($n=1){

    $uid = $_SESSION['USER_KEY_ID'];

    if (empty($uid)) {
      return false;
    }

    // 获取当天交易数据，如果当天没有数据，则获取前一天的数据
    if ($n == 1) {
      $addTime = strtotime(date("Y-m-d"));
      $endTime = strtotime("+1 day", strtotime(date("Y-m-d")));
    }else{
      $addTime = strtotime("-1 day", strtotime(date("Y-m-d")));
      $endTime = strtotime(date("Y-m-d"));
    }

    $where['currency_id'] = 3;
    $where['status'] = 0;
    $where['add_time'] = array(array('EGT', $addTime),array('LT', $endTime), 'AND');
    $orderBy = 'price ASC';
    $res = M('TradeTheLine')->where($where)->limit(5)->order($orderBy)->select();

    // 递归获取前一天数据
    if (empty($res) && $n < 2) {
      $n += 1;
      $this->getEntrustInfo($n);
    }

    $data = $this->getUserInfo($res);

    return $data;
  }

  /**
   * 获取卖出人名称
   * @author lirunqing 2017年8月11日14:21:47
   * @param  array $data 
   * @return array       
   */
  private function getUserInfo($data){

      if (empty($data)) {
        return array();
      }

      $temp = array();
      foreach ($data as $value) {
        $temp[$data['sell_id']] = $value['sell_id'];
      }

      $where['uid'] = array('in', $temp);
      $userList = M('User')->field('username,uid')->where($where)->select();

      $userTemp = array();
      foreach ($userList as $key => $value) {
          if (empty($value['sell_id'])) {
            unset($userList[$key]);
            continue;
          }
          $userTemp[$value['uid']] = $value['username'];
      }

      foreach ($data as $key => $value) {
          $name = $userTemp[$value['sell_id']];
          $value['sellName'] = $name;
          $data[$key] = $value;
      }

      return $data;
  }


  /**
   * 获取所有交易币种的数量
   * @author lirunqing 2017年8月10日15:45:35
   * @param  int $currency_id 
   * @param  int $exchange_id 
   * @return float  
   */
  private function getTheLineAllNumByCurrencyId($currency_id,$exchange_id){


    $where['currency_id'] = $currency_id;
    $where['status']      = array('in','3,4');
    $where['end_time']    = array('gt',strtotime(date('Y-m-d')));
    $where['exchange_id'] = $exchange_id;
    $r = M('TradeTheLine')->where($where)->sum('num');

    return $r?$r:0.00;
  }

  /**
   * 获取所有交易币种的价值总数
   * @author lirunqing 2017年8月10日15:51:42
   * @param  int $currency_id 
   * @param  int $exchange_id 
   * @return float  
   */
  private function getTheLineAllMoneyByCurrencyId($currency_id,$exchange_id){

    $where['currency_id'] = $currency_id;
    $where['status']      = array('in','3,4');
    $where['end_time']    = array('gt',strtotime(date('Y-m-d')));
    $where['exchange_id'] = $exchange_id;
    $r = round(M('TradeTheLine')->where($where)->sum('num*price'),4);
    return $r?$r:0.00;
  }

  /**
   * 获取网站配置项
   * @author lirunqing 2017年8月9日15:29:08
   */
  private function getConfig(){
    $config = M('Config')->select();
    foreach($config as $k=>$v){
      $config[$v['key']]=$v['value'];
    }
    
    return $config;
  }

  //获取快捷币的最新价格
  protected function  getNewKuaijieCoinPrice($exchange_id, $config){
       
    //开关开，取后台设置的 价格
    
      if($exchange_id == 1){
        $r = $config['KUAIJIE_COIN_PRICE']*$config['HUILV'];
      }else{
        $r = $config['KUAIJIE_COIN_PRICE'];
      }
             
    return $r;
  }

  /**
   * 获取最新的币交易价格
   * @author lirunqing 2017年8月10日16:50:56
   * @param  integer $exchange_id
   * @return array
   */
  protected function  getNewKuaijieCoinOtherPrice($config, $exchange_id=1){

    $redis = new Redis();
    
    $exchange_id = 0;//////默认设置为0 表示不乘以汇率去计算  就是按照人民币计算
    if($config['KUAIJIE_COIN_PRICE_SWITCH'] == 1){
      $r['htjg'] = $this->WEB_CONFIG['KUAIJIE_COIN_PRICE'];
      if($exchange_id == 1){
        $r['high'] = $config['KJB_ZGJ']*$config['HUILV'];
        $r['low'] = $config['KJB_ZDJ']*$config['HUILV'];
        $r['average'] = ( $r['high'] + $r['low'] ) / 2;
      }else{
        $r['high'] = $config['KJB_ZGJ'];
        $r['low'] = $config['KJB_ZDJ'];
        $r['average'] = $config['KJB_DQJG'];
      }
    }else{
      ////     1. 把数据库里的数据写入缓存
      /////   2.    15分钟计算一次线下的成交单  统计最高价格 最低价格 和平均价格

      $k1 =  $redis->get('HIGH_PRICE');
      $k2 =  $redis->get('LOW_PRICE');
      $k3 =  $redis->get('AV_PRICE');
      if(empty($k1) && empty($k2) && empty($k3)){
          $redis->set('HIGH_PRICE',$config['KJB_ZGJ'],960);
          $redis->set('LOW_PRICE',$config['KJB_ZDJ'],960);
          $redis->set('AV_PRICE',$config['KJB_DQJG'],960);
          $redis->set('HIGH_PRICE_FU',$config['KJB_ZGJ'],86400);
          $redis->set('LOW_PRICE_FU',$config['KJB_ZDJ'],86400);
          $redis->set('AV_PRICE_FU',$config['KJB_DQJG'],86400);
      } 

      $time1              = time()-901;////15分钟内
      $map['currency_id'] = 3;//快捷币
      $map['status']      = 3;////已成交
      $map['end_time']    = array('gt',$time1);////统计刚刚15分钟内的
      $map['exchange_id'] = 1;
      $order              = 'end_time desc';

      $arr = M('TradeTheLine')->where($map)->order($order)->limit(10)->field('price')->select();
                     
      if($arr){
         $sb_num = count($arr);
         $max_num = 0;
         $sb_sum = 0;
         $min_num = $arr[0]['price'];
         for($i = 0;$i < $sb_num; $i++){
             if($arr[$i]['price'] > $max_num){
                 $max_num = $arr[$i]['price'];
             }
             if($arr[$i]['price'] < $min_num){
                $min_num = $arr[$i]['price'];
             }
             $sb_sum += $arr[$i]['price'];
         }
          $r['low']  =  $min_num;
          $r['high'] =  $max_num;
          $r['average'] = $sb_sum /$sb_num;
          $r['average'] = number_format($r['average'],4);////实时平均价格
          /////获得数据   然后将数据放入缓存--更新缓存
          $redis->set('HIGH_PRICE', $r['high'],960);
          $redis->set('LOW_PRICE', $r['low'],960);
          $redis->set('AV_PRICE',$r['average'],960);
          $redis->set('HIGH_PRICE_FU',$r['high'],86400);
          $redis->set('LOW_PRICE_FU',$r['low'],86400);
          $redis->set('AV_PRICE_FU',$r['average'],86400);    
      }else{
          ///如果没有数据就读取缓存  读取缓存
          $r['high']    =  $redis->get('HIGH_PRICE');
          $r['low']     = $redis->get('LOW_PRICE');
          $r['average'] = $redis->get('AV_PRICE');
          if(empty($r['high']) && empty($r['low']) && empty($r['average']) ){
              $r['high']    = $redis->get('HIGH_PRICE_FU'); // 如果前十五分钟没有产生订单就取最大的数据
              $r['low']     = $redis->get('LOW_PRICE_FU'); // 如果前十五分钟没有产生订单就取最大的数据
              $r['average'] = $redis->get('AV_PRICE_FU'); // 如果前十五分钟没有产生订单就取最大的数据
          }                           
      }
      $r['htjg'] = $r['low'];
    }


    $priceWhere['currency_id'] = 3;
    $priceWhere['status']      = array('in','3,4');
    $priceWhere['end_time']    = array('gt',strtotime(date('Y-m-d')));
    $priceWhere['trade_time']  = array('gt',strtotime(date('Y-m-d')));
    $priceWhere['exchange_id'] = $exchange_id;
    $order                = 'id asc';

    $buy = M('TradeTheLine')->where($priceWhere)->order($order)->getField('price');

    $r['buy']     = !empty($buy) ? $buy : 0;
    $r['num']     = $this->getTheLineAllNumByCurrencyId(3,1);
    $r['num']     = $r['num']+$config['KCOIN_FIRST_TRADE_NUM'];
    $r['money']   = $this->getTheLineAllMoneyByCurrencyId(3,1);
    $r['money']   = $r['money']+$config['KCOIN_FIRST_TRADE_MONEY'];

    return $r;    
  }

  /**
   * 获取币种实时信息
   * @author lirunqing 2017年8月10日15:39:41
   * @return [type] [description]
   */
  public function getBBinfo(){
       $redis = new Redis();
	//echo "22222";die;
	//var_dump(vget('https://www.okex.com/api/spot/v3/instruments/BTC-USDT/ticker'));die;
      //1.判定缓存，缓存有就读取缓存  
      $sb_data =  $redis->get('s_data');
      //var_dump($sb_data);die;
      if(empty($sb_data)){
          if(IS_AJAX){
                        $this->ajaxReturn($sb_data);
                     }
          return($sb_data);
      }else{
                $config = $this->getConfig();
                $usa    = $config['HUILV'];

                // 当天剩余时间
                $lastTime = date("Y-m-d")." 23:59:59";
                $surplusTime = strtotime($lastTime) - time();

                // 更改获取币种信息网站来源 add by lirunqing 2017-9-28 15:35:05 
                // https://www.okex.me/api/v1/ticker.do?symbol=bch_usdt
				//https://www.okex.me/api/spot/v3/instruments/BTC-USDT/ticker
                // {"date":"1576814222","ticker":{"high":"189.87","vol":"90435.37","last":"186.38","low":"183.49","buy":"186.36","sell":"186.39"}}
                
                $BTC                     = vget('https://www.okex.com/api/spot/v3/instruments/BTC-USDT/ticker');
                $data['BTC']                     = json_decode($BTC, true);
		//var_dump($data);die;
                $btc_dd = $data['BTC'];
                $btcAsk = $redis->get('bitfinex_btc_ask');// 设置开盘价
                if (empty($btcAsk)) {
                  $redis->set('bitfinex_btc_ask', $data['BTC']['open_24h'], $surplusTime);
                  $btcAsk = $data['BTC']['open_24h'];
                }
                $data['BTC']['num']        = $data['BTC']["base_volume_24h"]; // 总成交量
                $data['BTC']['money']      = $data['BTC']["quote_volume_24h"] * $usa; // 总成交值，换算成RMB 
                $data['BTC']['buy']        = round($btc_dd["best_bid"] * $usa, 2); // 最新价，换算成RMB
                $data['BTC']['last']       = round($btc_dd['last'] * $usa, 2); // 最新价，换算成RMB
                $data['BTC']['btc_usa']    = $btc_dd['last']; // 最新价 美元
                $data['BTC']['kaipan']     = round($btcAsk * $usa, 2); // 开盘价，换算成RMB
                $data['BTC']['kaipan_usa'] = $btcAsk; // 开盘价 美元
                $data['BTC']['high']       = round($btc_dd['high_24h'] * $usa, 2); // 最高价RMB
                $data['BTC']['high_usa']   = $btc_dd['high_24h']; // 最高价美元
                $data['BTC']['low']        = round($btc_dd['low_24h'] * $usa, 2); // 最低价RMB
                $data['BTC']['low_usa']    = $btc_dd['low_24h']; // 最低价RMB
                 //var_dump($data);die;
                // 更改获取币种信息网站来源 add by lirunqing 2017-9-28 15:35:05 
                // $LTC                     = vget('https://www.okcoin.cn/api/v1/ticker.do?symbol=ltc_cny');
                // [BID, BID_SIZE, ASK, ASK_SIZE, DAILY_CHANGE, DAILY_CHANGE_PERC, LAST_PRICE, VOLUME, HIGH, LOW]
                // // {"date":"1576814222","ticker":{"high":"189.87","vol":"90435.37","last":"186.38","low":"183.49","buy":"186.36","sell":"186.39"}}
                $LTC                     = vget('https://www.okex.com/api/spot/v3/instruments/LTC-USDT/ticker');
                $data['LTC']                     = json_decode($LTC, true);
                $ltc_dd = $data['LTC'];
                $ltcAsk = $redis->get('bitfinex_ltc_ask');// 设置开盘价
                if (empty($ltcAsk)) {
                  $redis->set('bitfinex_ltc_ask', $data['LTC']['open_24h'], $surplusTime);
                  $ltcAsk = $data['LTC']['open_24h'];
                }
                $data['LTC']['num']        = $data['LTC']["base_volume_24h"]; // 总成交量
                $data['LTC']['money']      = $data['LTC']["quote_volume_24h"] * $usa; // 总成交值，换算成RMB 
                $data['LTC']['buy']        = round($ltc_dd["best_bid"] * $usa, 2); // 最新价，换算成RMB
                $data['LTC']['last']       = round($ltc_dd['last'] * $usa, 2); // 最新价，换算成RMB
                $data['LTC']['btc_usa']    = $ltc_dd['last']; // 最新价 美元
                $data['LTC']['kaipan']     = round($ltcAsk * $usa, 2); // 开盘价，换算成RMB
                $data['LTC']['kaipan_usa'] = $ltcAsk; // 开盘价 美元
                $data['LTC']['high']       = round($ltc_dd['high_24h'] * $usa, 2); // 最高价RMB
                $data['LTC']['high_usa']   = $ltc_dd['high_24h']; // 最高价美元
                $data['LTC']['low']        = round($ltc_dd['low_24h'] * $usa, 2); // 最低价RMB
                $data['LTC']['low_usa']    = $ltc_dd['low_24h']; // 最低价RMB
               // var_dump( $data['LTC']);die;
                // 更改获取币种信息网站来源 add by lirunqing 2017-9-28 15:35:05 
                // $BCC = vget('https://www.okcoin.cn/api/v1/ticker.do?symbol=bcc_cny');
                // [BID, BID_SIZE, ASK, ASK_SIZE, DAILY_CHANGE, DAILY_CHANGE_PERC, LAST_PRICE, VOLUME, HIGH, LOW]
                // [买入最高价格, 买入最大数量, 卖出最大价格, 卖出最大数量, 每日涨幅数量, 每日涨幅比例, 最后成交价, 成交量, 最高价, 最低价]
                $BCC                     = vget('https://www.okex.com/api/spot/v3/instruments/BCH-USDT/ticker');
                $data['BCC']                      = json_decode($BCC, true);
                          
                $bcc_data = $data['BCC'];
                $bccAsk = $redis->get('bitfinex_bcc_ask');// 设置开盘价
                
                 if (empty($bccAsk)) {
                  $redis->set('bitfinex_ltc_ask', $data['LTC']['open_24h'], $surplusTime);
                  $bccAsk = $data['BCC']['open_24h'];
                }
                $data['BCC']['num']        = $data['BCC']["base_volume_24h"]; // 总成交量
                $data['BCC']['money']      = $data['BCC']["quote_volume_24h"] * $usa; // 总成交值，换算成RMB 
                $data['BCC']['buy']        = round($bcc_data["best_bid"] * $usa, 2); // 最新价，换算成RMB
                $data['BCC']['last']       = round($bcc_data['last'] * $usa, 2); // 最新价，换算成RMB
                $data['BCC']['btc_usa']    = $bcc_data['last']; // 最新价 美元
                $data['BCC']['kaipan']     = round($bccAsk * $usa, 2); // 开盘价，换算成RMB
                $data['BCC']['kaipan_usa'] = $bccAsk; // 开盘价 美元
                $data['BCC']['high']       = round($bccAsk['high_24h'] * $usa, 2); // 最高价RMB
                $data['BCC']['high_usa']   = $bcc_data['high_24h']; // 最高价美元
                $data['BCC']['low']        = round($bcc_data['low_24h'] * $usa, 2); // 最低价RMB
                $data['BCC']['low_usa']    = $bcc_data['low_24h']; // 最低价RMB
                
                // 更改获取币种信息网站来源 add by lirunqing 2017-9-28 15:35:05 
                // $ETH = vget('https://www.okcoin.cn/api/v1/ticker.do?symbol=eth_cny');
                $ETH                     = vget('https://www.okex.com/api/spot/v3/instruments/ETH-USDT/ticker');
                $data['ETH']                       = json_decode($ETH, true);
                        
                $eth_data = $data['ETH'];
                $ethAsk = $redis->get('bitfinex_eth_ask');// 设置开盘价
               
                if (empty($ethAsk)) {
                  $redis->set('bitfinex_ltc_ask', $data['LTC']['open_24h'], $surplusTime);
                  $ethAsk = $data['ETH']['open_24h'];
                }
                $data['ETH']['num']        = $data['ETH']["base_volume_24h"]; // 总成交量
                $data['ETH']['money']      = $data['ETH']["quote_volume_24h"] * $usa; // 总成交值，换算成RMB 
                $data['ETH']['buy']        = round($eth_data["best_bid"] * $usa, 2); // 最新价，换算成RMB
                $data['ETH']['last']       = round($eth_data['last'] * $usa, 2); // 最新价，换算成RMB
                $data['ETH']['btc_usa']    = $eth_data['last']; // 最新价 美元
                $data['ETH']['kaipan']     = round($ethAsk * $usa, 2); // 开盘价，换算成RMB
                $data['ETH']['kaipan_usa'] = $ethAsk; // 开盘价 美元
                $data['ETH']['high']       = round($ethAsk['high_24h'] * $usa, 2); // 最高价RMB
                $data['ETH']['high_usa']   = $eth_data['high_24h']; // 最高价美元
                $data['ETH']['low']        = round($eth_data['low_24h'] * $usa, 2); // 最低价RMB
                $data['ETH']['low_usa']    = $eth_data['low_24h']; // 最低价RMB
               
                // KJB
                //$data['KJB']['houtaijiage'] = $this->isHouTaiJiaGe();
                $data['KJB']              = $this->getNewKuaijieCoinOtherPrice($config);/////获取快捷币相关的价格信息（最高价格、最低价格、平均价格）并存入redis缓存
                //$data['KJB']['last']      = $this->getNewKuaijieCoinPrice(1, $config);
                $data['KJB']['last']      = $redis->get('AV_PRICE_FU');
                // 后台设置成交总量 lirunqing 2018年1月9日16:14:55
                $setNumRes       = $this->getSetTradeNnum();
                $todayData       = $setNumRes['todayData'];
                $lastdayData     = $setNumRes['lastdayData'];
                $todayNum        = !empty($todayData['num']) ? $todayData['num'] : 0;
                $todayTotalPrice = !empty($todayData['total_price']) ? $todayData['total_price'] : 0;
                $lastdayNum        = !empty($lastdayData['num']) ? $lastdayData['num'] : 0;
                $lastdayTotalPrice = !empty($lastdayData['total_price']) ? $lastdayData['total_price'] : 0;
                // $data['KJB']['qc_usa'] = round($this->getNewKuaijieCoinPrice(1, $config)/$usa, 2);
                //$data['KJB']['high']      = $config['KJB_KPJ'];
                //$data['KJB']['high_usa']  = round($config['KJB_KPJ']/$usa, 2);
                $data['KJB']['high']      = $data['KJB']['high'];
                $data['KJB']['num']       = $data['KJB']['num']+$todayNum;
                $data['KJB']['money']     = round($data['KJB']['money']+$todayTotalPrice, 4);
                $data['KJB']['high_usa']  = round($data['KJB']['high']/$usa, 2);
                $data['KJB']['qc_usa']    = round($redis->get('AV_PRICE_FU')/$usa, 2);////$config['KUAIJIE_COIN_PRICE'];
                $data['KJB']['low_usa']   = round(($data['KJB']['low'])/$usa, 2);
                $KJB_yesInfo = $this->getYesterdayVPNum();
                //$data['KJB']['yester_day_num']  = $KJB_yesInfo['sum'];
                //$data['KJB']['total_price']  = $KJB_yesInfo['total_price'];
                // 获取后台设置24h成交量 lirunqing 2018年1月9日16:15:15
                $data['KJB']['yester_day_num']  = round($KJB_yesInfo['sum'],4)+$lastdayNum;
                $data['KJB']['total_price']  = round($KJB_yesInfo['total_price'],4)+$lastdayTotalPrice;
                $data['KJB']['kaipan']      = $config['KJB_KPJ'];
                $data['KJB']['kaipan_usa']  = round($config['KJB_KPJ']/$usa, 2);
//                 echo "<pre>";
//                var_dump($data);
//                echo "</pre>";die;
                $redis->set('s_data', $data, 900);
                if(IS_AJAX){
                        $this->ajaxReturn($data);
                    }
                return($data);
            }
  }

  /**
   * 获取刷单数据
   * @author 2018-01-09T21:03:24+0800
   * @return [type] [description]
   */
  private function getSetTradeNnum(){

    $startTime = strtotime(date("Y-m-d"));
    $endTime   = strtotime(date("Y-m-d"))+86400-1;

    $todayWhere  = array(
      'add_time' => array(  
        array('EGT', $startTime),
        array('elt', $endTime), 
        'AND',
      )
    );
    $todayRes  = M('SetTradeNum')->where($todayWhere)->find();

    $lastStartTime = strtotime("-1 day", strtotime(date("Y-m-d")));
    $lastEndTime = strtotime(date("Y-m-d"));
    $lastDayWhere = array(
        'add_time' => array(
          array('EGT', $lastStartTime),
          array('LT', $lastEndTime), 
          'AND'
        )
    );

    $lastdayRes  = M('SetTradeNum')->where($lastDayWhere)->find();

    $data = array(
        'todayData' => $todayRes,
        'lastdayData' => $lastdayRes,
    );

    return $data;
  }

  /**
   * 获取VP前一天交易量总数及总价
   * @author lirunqing 2017-08-25T16:55:41+0800
   * @return [type] [description]
   */
  protected function getYesterdayVPNum(){

    $redis = new Redis();

     $VP_num_val = 'get_VP_yesnum';
     $get_VP_yesnum = $redis->get($VP_num_val);///读取缓存数据

     if (!empty($get_VP_yesnum)) {
       return $get_VP_yesnum;
     }

    $where['status'] = 3;
    $where['currency_id'] = 3;
    $startTime = strtotime("-1 day", strtotime(date("Y-m-d")));
    $endTime = strtotime(date("Y-m-d"));
    $where['end_time'] = array(
      array('EGT', $startTime),
      array('LT', $endTime), 
      'AND'
    );

    $sum = M('TradeTheLine')->where($where)->sum('num');
    $total_price = M('TradeTheLine')->where($where)->sum('num*price');
    $sum = $sum ? $sum : 0;
    $total_price = $total_price ? $total_price : 0.0000;

    $data['sum'] = $sum;
    $data['total_price'] = $total_price;
    $redis->set($VP_num_val, $data, 9600);

    return $data;
  }
	
	/** 
  * 获取图形验证码
  * @author lirunqing 2017年8月8日14:04:05
  * @return string
  */
  public function getVerify(){
   	
    $config =    array(
     	'fontSize'  =>  14,              // 验证码字体大小(px)
     	'useCurve'  =>  false,            // 是否画混淆曲线
     	'useNoise'  =>  true,            // 是否添加杂点 
     	'imageH'    =>  40,               // 验证码图片高度
     	'imageW'    => 100,               // 验证码图片宽度
     	'length'    =>  4,               // 验证码位数
     	'fontttf'   =>  '4.ttf',         // 验证码字体，不设置随机获取
    );

    $Verify = new \Think\Verify($config);
    $Verify->entry();
  }

  /**
  * 检测输入的验证码是否正确
  * @author lirunqing 2017年8月8日14:06:02
  * @param  string $code 用户输入的验证码字符串
  * @param  string $id
  * @return boolean
  */
  public function checkVerify($code, $id = ''){
   $verify = new \Think\Verify();
   return $verify->check($code,$id);
  }
  
  /** 伪首页的快捷币实时价格 4h小时内
   *  @author 宋建强 2017年8月22日
   *  
   */
   public function getEchat()
   {
         //获取数据
	     $config = $this->getConfig();  
         $date_time=$this->BeforeDate();
         
	     $filename =$_SERVER['DOCUMENT_ROOT'].'/Public/Json/kjb_json_chat.txt';
	     $model=new GetNewPrice();
		 $model->KuaijiebiAvPirce($config);

		 $str=file_get_contents($filename);
		 $price_arr=[];
		 if($str)
		 {
		 	 $price=json_decode($str,true);
		 	 foreach ($price as $v)
		 	 {
		 	 	$price_arr[]=explode('-', $v)[1];
		 	 }
		 }

	   	 if(IS_AJAX)
	   	 {   
	   	 	$result=['date'=>$date_time,'price'=>$price_arr];
	   	 	$this->ajaxReturn($result);
	   	 } 	 
   }
   
   /*
    *构建现在开始的往前推迟四个小时的数据 
   */
   public  function  BeforeDate()
   {  
	   	$time=time();
	   	$min=date('i',$time);
	   	 
	   	$min_int=date('i',$time);
	   	$hour=date('H',$time);
	   	$h_m=date('H:i',$time);
	   	 
	   	if( $min_int>=0 && $min_int<15)
	   	{
	   		//补充不够的时间
	   		$date_time[]=($hour-4).':30';
	   		$date_time[]=($hour-4).':45';
	   		 
	   		//前三个小时
	   		$date_time[]=($hour-3).':00';
	   		$date_time[]=($hour-3).':15';
	   		$date_time[]=($hour-3).':30';
	   		$date_time[]=($hour-3).':45';
	   	
	   		//前两个小时
	   		$date_time[]=($hour-2).':00';
	   		$date_time[]=($hour-2).':15';
	   		$date_time[]=($hour-2).':30';
	   		$date_time[]=($hour-2).':45';
	   		 
	   		//上一个小时
	   		$date_time[]=($hour-1).':00';
	   		$date_time[]=($hour-1).':15';
	   		$date_time[]=($hour-1).':30';
	   		$date_time[]=($hour-1).':45';
	   	
	   		//当前的时间
	   		$date_time[]=($hour).':00';
	   		$date_time[]=$h_m;
	   	}
	   	elseif($min_int>=15 && $min_int<=30)
	   	{
	   		//补充不够的时间
	   		$date_time[]=($hour-4).':30';
	   		$date_time[]=($hour-4).':45';
	   	
	   		//前三个小时
	   		$date_time[]=($hour-3).':00';
	   		$date_time[]=($hour-3).':15';
	   		$date_time[]=($hour-3).':30';
	   		$date_time[]=($hour-3).':45';
	   		 
	   		//前两个小时
	   		$date_time[]=($hour-2).':00';
	   		$date_time[]=($hour-2).':15';
	   		$date_time[]=($hour-2).':30';
	   		$date_time[]=($hour-2).':45';
	   	
	   		//上一个小时
	   		$date_time[]=($hour-1).':00';
	   		$date_time[]=($hour-1).':15';
	   		$date_time[]=($hour-1).':30';
	   		$date_time[]=($hour-1).':45';
	   		 
	   		//当前的时间
	   		$date_time[]=($hour).':00';
	   		$date_time[]=($hour).':15';
	   	}
	   	else if($min_int>=30 && $min_int<=45)
	   	{
	   		//补充不够的时间
	   		$date_time[]=($hour-4).':45';
	   		 
	   		//前三个小时
	   		$date_time[]=($hour-3).':00';
	   		$date_time[]=($hour-3).':15';
	   		$date_time[]=($hour-3).':30';
	   		$date_time[]=($hour-3).':45';
	   	
	   		//前两个小时
	   		$date_time[]=($hour-2).':00';
	   		$date_time[]=($hour-2).':15';
	   		$date_time[]=($hour-2).':30';
	   		$date_time[]=($hour-2).':45';
	   		 
	   		//上一个小时
	   		$date_time[]=($hour-1).':00';
	   		$date_time[]=($hour-1).':15';
	   		$date_time[]=($hour-1).':30';
	   		$date_time[]=($hour-1).':45';
	   	
	   		//当前的时间
	   		$date_time[]=($hour).':00';
	   		$date_time[]=($hour).':15';
	   		$date_time[]=($hour).':30';
	   	}
	   	elseif ($min_int>45)
	   	{
	   		//前三个小时
	   		$date_time[]=($hour-3).':00';
	   		$date_time[]=($hour-3).':15';
	   		$date_time[]=($hour-3).':30';
	   		$date_time[]=($hour-3).':45';
	   		 
	   		//前两个小时
	   		$date_time[]=($hour-2).':00';
	   		$date_time[]=($hour-2).':15';
	   		$date_time[]=($hour-2).':30';
	   		$date_time[]=($hour-2).':45';
	   	
	   		//上一个小时
	   		$date_time[]=($hour-1).':00';
	   		$date_time[]=($hour-1).':15';
	   		$date_time[]=($hour-1).':30';
	   		$date_time[]=($hour-1).':45';
	   		 
	   		$date_time[]=($hour).':00';
	   		$date_time[]=($hour).':15';
	   		$date_time[]=($hour).':30';
	   		$date_time[]=($hour).':45';
	   	}
	   	return $date_time;
   }
}
