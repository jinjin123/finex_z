<?php
/**
 * 币币交易配置
 * @author  lirunqing   2017-11-30 19:20:35
 */
namespace Common\Api;

class CurrencyTradingConfig{
	// ['币种id，也是交易区id' => 表名]
	static $tradeAreaArr = array(// 根据交易区获取交易区表名
		1 => 'BtcAreaOrder',
		2 => 'VpAreaOrder'
	);

	// ['币种id，也是交易区id' => 表名]
	static $tradeAreasuccessArr = array(// 根据交易区获取交易成功表名
		1 => 'BtcSuccessRecord',
		2 => 'VpSuccessRecord'
	);

	// ['币种id，也是交易区id' => 表名]
	static $tradeAreaMachineArr = array(// 根据交易区获取机器人刷单表名
		1 => 'BtcMachineBrush',
		2 => 'VpMachineBrush'
	);

	/**
	 * 获取所有交易交易区的币种
	 * @author lirunqing 2017-12-07T14:21:42+0800
	 * @return [type] [description]
	 */
	public static function getTradeAreaInfo(){

		// $VPTradeAreaArr = self::getVPTradeArea();// 创新交易区币种配置
		$tradeAreaArr   = self::getTradingArea();// 主流交易区币种配置

		$arr = array();
		// $arr = $tradeAreaArr['tradingAreaArr'] + $VPTradeAreaArr['limitTradeAreaArr'];
		$arr = $tradeAreaArr['tradingAreaArr'];

		return $arr;
	}

	/**
	 * 获取所有交易交易区的币种
	 * @author 2017-12-07T14:21:42+0800
	 * @return [type] [description]
	 */
	public static function getTradeAreaInfoList(){

		// 暂时屏蔽VP交易区 add by lirunqing 2018年6月4日10:16:34
		// $VPTradeAreaArr = self::getVPTradeArea();// 创新交易区币种配置
		// $vpareaBuyCurrencyIdArr = self::getVPTradeAreaArr();
		$tradeAreaArr         = self::getTradingArea();// 主流交易区币种配置
		$areaBuyCurrencyIdArr = self::getAreaBuyCurrencyIdArr();

		$primaryMarkArr = array();
		$secMarkArr = array();
		foreach ($tradeAreaArr['coinArr'] as $key => $value) {
			if (empty($value)) {
				continue;
			}
			$temp['parent_info']['str']         = $tradeAreaArr['tradeAreaArr'][$key];
			$temp['parent_info']['currency_id'] = $areaBuyCurrencyIdArr[$key];
			$temp['child_info'] = $value;
			$primaryMarkArr[$key] = $temp;
		}

		// 暂时屏蔽VP交易区 add by lirunqing 2018年6月4日10:16:34
		// foreach ($VPTradeAreaArr['VPConfigArr'] as $key => $value) {
		// 	$temp['parent_info']['str']         = $VPTradeAreaArr['tradeAreaArr'][$key];
		// 	$temp['parent_info']['currency_id'] = $vpareaBuyCurrencyIdArr[$key];
		// 	$temp['child_info'] = $value;
		// 	$secMarkArr[$key] = $temp;
		// }

		$arr = array();
		// $arr = $primaryMarkArr + $secMarkArr;
		$arr = $primaryMarkArr;

		return $arr;
	}

	/**
	 * 获取创新交易区相关币种配置
	 * @author liruniqng 2017-12-07T11:45:49+0800
	 * @return array
	 */
	public static function getVPTradeArea(){

		$arr          = array();
		$currencyArr  = array();
		$secdRes      = M('SecondClassCoinConfig')->select();
		$currencyArr  = self::getCurrencyInfo();
		$VPVolArr     = self::getVPTradeAreaVol();
		$tradeAreaArr = array();
		foreach ($secdRes as $key => $value) {
			$temp['trade_area']      = $currencyArr[$value['main_currency_id']];
			$temp['area_id']         = $value['trade_area_id'];
			$temp['coin_price']      = $value['main_coin_price'];
			$temp['sell_fee']        = $value['sell_fee'];
			$temp['buy_fee']         = $value['buy_fee'];
			$temp['p_currency_id']   = $value['main_currency_id'];
			$temp['entrust_type']    = $value['exchange_currency_id'];
			$temp['currency_id']     = $value['exchange_currency_id'];
			$temp['coin_str']        = $currencyArr[$value['exchange_currency_id']].'/'.$currencyArr[$value['main_currency_id']];
			$temp['high']            = 0;
			$temp['vol']             = !empty($VPVolArr[$value['exchange_currency_id']]) ? $VPVolArr[$value['exchange_currency_id']] : 0;
			$temp['last']            = '0';
			$temp['low']             = '0';
			$temp['buy']             = '0';
			$temp['sell']            = '0';
			$temp['rate']            = '0%';
			$temp['perc_status']     = '0';
			$arr[$temp['area_id']][] = $temp;// 获取币种具体信息
			$limitTradeAreaArr[$temp['area_id']][$value['exchange_currency_id']] = $value['exchange_currency_id'];// 获取该交易区可以交易币种 
			$tradeAreaArr[$temp['area_id']] = $currencyArr[$value['main_currency_id']];// 获取该交易区的名称
			$newList[$value['exchange_currency_id']] = $currencyArr[$value['exchange_currency_id']].'/'.$currencyArr[$value['main_currency_id']];
			$coinList[$value['trade_area_id']] = $newList;
		}
		
		$data = array(
			'limitTradeAreaArr' => $limitTradeAreaArr,
			'VPConfigArr'       => $arr,
			'VPConlist'         => $coinList,
			'tradeAreaArr'      => $tradeAreaArr,
		);

		return $data;
	}

	/**
	 * 获取VP交易区个币种成交数量
	 * @author lirunqing 2018-03-27T14:38:05+0800
	 * @return array
	 */
	public static function getVPTradeAreaVol(){
		$tableName = self::$tradeAreasuccessArr[2];
		$time = time()-24*3600;//获取最近24H成交数量
		$where = [
			'trade_time' => array('egt', $time),
		];
		$volArr = M($tableName)->field('entrust_type,sum(trade_num) as vol')->where($where)->group('entrust_type')->select();
		
		$volList = array();
		foreach ($volArr as $key => $value) {
			$volList[$value['entrust_type']] = $value['vol'];
		}

		return $volList;
	}


	/**
	 * 获取币种信息
	 * @author lirunqing 2017-12-07T10:54:02+0800
	 * @return [type] [description]
	 */
	protected static function getCurrencyInfo(){

		$currencyArr  = array();
		$currencyList = M('Currency')->field('currency_name,id')->select();
		foreach ($currencyList as $key => $value) {
			$currencyArr[$value['id']] = $value['currency_name'];
		}

		return $currencyArr;
	}

	/**
	 * 获取交易区币种配置
	 * @author lirunqing 2019-03-13T14:54:58+0800
	 * @return array
	 */
	public static function getAllTradeAreaArr(){

		$prim = self::getAreaBuyCurrencyIdArr();
		// $sec  = self::getVPTradeAreaArr();

		$newArr = $prim;
		if (!empty($sec)) {
			unset($newArr);
			$newArr = $prim + $sec;
		}

		return 	$newArr;
	}

	/**
	 * 获取二级交易区币种信息
	 * @author lirunqing 2019-03-13T14:47:56+0800
	 * @return array  ['交易区id' => 币种id]
	 */
	public static function getVPTradeAreaArr(){
		$secdRes = M('SecondClassCoinConfig')->field('trade_area_id,main_currency_id')->group('trade_area_id')->select();

		$areaBuyCurrencyIdArr = array_column($secdRes, 'main_currency_id', 'trade_area_id');

		return $areaBuyCurrencyIdArr;
	}

	/**
	 * 获取主流交易区主币种信息
	 * @author lirunqing 2019-03-13T11:47:53+0800
	 * @return array  ['交易区id' => 币种id]
	 */
	public static function getAreaBuyCurrencyIdArr(){

		$res = M('BiBiConfig')->select();
		$areaBuyCurrencyIdArr = array_column($res, 'currency_id', 'trade_area_id');

		return $areaBuyCurrencyIdArr;
	}

	/**
	 * 获取交易区对应币种
	 * @author lirunqing 2019-03-13T14:18:47+0800
	 * @return array ['交易区id' => ['币种id' => 币种id] ]
	 */
	public static function getAreaSellCurrencyIdArr(){
		$res = M('CanExchangeConfig')->alias('a')->join('__BI_BI_CONFIG__ b ON b.trade_area_id= a.entrust_id')->select();

		foreach ($res as $key => $value) {
			if (!empty($value['can_exchange_currencys'])) {
				$oldTemp = explode(',', $value['can_exchange_currencys']);
				foreach ($oldTemp as $k => $val) {
					$newTemp[$val] = $val;// 获取对应委托类型值
				}
			}
			$areaSellCurrencyIdArr[$value['trade_area_id']] = $newTemp;
		}
		return $areaSellCurrencyIdArr;
	}


	/**
	 * 获取BTC交易区相关数据
	 * @author lirunqing 2017-12-01T16:47:02+0800
	 * @return array  $coinArr参考上面静态变量$coinArr,$tradingAreaArr参考上面静态变量areaSellCurrencyIdArr
	 */
	public static function getTradingArea(){

		$tradingAreaArr = array();
		$coinArr        = array();
		$currencyArr    = array();
		$coinList       = array();
		$otherArr       = array();
		$tradeArea      = array();

		$areaBuyCurrencyIdArr = self::getAreaBuyCurrencyIdArr();

		$res = M('CanExchangeConfig')->alias('a')->join('__BI_BI_CONFIG__ b ON b.trade_area_id= a.entrust_id')->select();
		foreach ($res as $key => $value) {
			if (!empty($value['can_exchange_currencys'])) {
				$oldTemp = explode(',', $value['can_exchange_currencys']);
				foreach ($oldTemp as $k => $val) {
					$newTemp[$val] = $val;// 获取对应委托类型值
				}
			}
			$otherArr[$value['entrust_id']]       = $value;
			$tradingAreaArr[$value['entrust_id']] = $newTemp;// 获取交易区允许交易的币种
			unset($newTemp);
		}

		$currencyArr = self::getCurrencyInfo();

		foreach ($tradingAreaArr as $key => $value) {
			$areaCoin = $areaBuyCurrencyIdArr[$key];
			foreach ($value as $k => $val) {
				$temp['sell_fee']      = $otherArr[$key]['sell_fee'];
				$temp['buy_fee']       = $otherArr[$key]['buy_fee'];
				$temp['trade_area']    = $currencyArr[$areaCoin];
				$temp['area_id']       = $key;
				$temp['p_currency_id'] = $areaCoin;
				$temp['entrust_type']  = $k;
				$temp['currency_id']   = $val;
				$temp['coin_str']      = $currencyArr[$val].'/'.$currencyArr[$areaCoin];
				$newArr[]              = $temp;
				$newList[$k]           = $currencyArr[$val].'/'.$currencyArr[$areaCoin];
				unset($temp);
			}

			$coinArr[$key]      = $newArr;
			$coinList[$key]     = $newList;
			$tradeAreaArr[$key] = $currencyArr[$areaCoin];
			unset($newArr);
		}

		$data = array(
			'coinArr'        => $coinArr,// 交易区允许交易的币种名称
			'tradingAreaArr' => $tradingAreaArr,// 交易区允许交易币种
			'coinList'       => $coinList,
			'tradeAreaArr'   => $tradeAreaArr,
		);

		return $data;
	}
}