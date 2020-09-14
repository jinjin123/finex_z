<?php

namespace Home\Controller;

use Home\Logics\CommonController;
use Home\Logics\TradingView;
use Common\Api\RedisCluster;
use Common\Api\RedisIndex;
/**
 * tradingView 数据图表的渲染
 * @date 2019年5月5日 下午2:46:29
 *
 * @author Jungle
 */
class TradingViewController extends CommonController
{

    private $petcMap = [
        '60' => 1,
        '300' => 2,
        '900' => 3,
        '1800' => 4,
        '3600' => 5,
        '14400' => 6,
        '86400' => 7,
        '604800' => 8
    ];

    public function _initialize()
    {

    }

    /**
     * 初始化TradingView
     * @author Jungle 2019年5月6日 下午3:37:22
     */
    public function rawTrade()
    {
        $symbol = trim(I('CoinType'));
        redirect('/Public/Home/kline/index.html?symbol=' . $symbol);
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
     * 获取交易数据
     * @author Jungle 2019年5月9日 上午10:21:13
     */
    public function getCandles()
    {
		$redis  = RedisCluster::getInstance();
        $symbol = trim(I('symbol'));
        $granularity = trim(I('granularity'));
		//echo "symbol:".$symbol."<br/>";
		//echo "granularity:".$granularity."<br/>";
        if ($symbol == 'FEC-USDT') {
            $map = $this->petcMap;
			//$hhp = unserialize($redis->get('PetcKline'.$granularity));
			//dump($hhp);
			$row = array();
			//if($hhp){$row = $hhp;}
			
				$row = M('PetcKline')->where([
                'type' => $map[$granularity]
            ])->order('id DESC')->limit(70)->select();
				//dump($row);
				//dump(M()->_sql());die;
				$cao = array_column($row ,'add_time');
				array_multisort($cao,SORT_ASC,$row);
				//$sdiao = serialize($row);
				//$redis->setex('PetcKline'.granularity,60,$sdiao);
			
            

            $data = [];
            foreach ($row as $k => $v) array_push($data, [
                $v['add_time'],
                $v['kaipan_price'],
                $v['high'],
                $v['low'],
                $v['shoupan_price'],
                $v['vom_now']
            ]);
		//dump($data);
            $this->ajaxReturn(['code' => 0, 'data' => $data]);
        }

        $test = I('test');
        $size = 1000; // 默认1000条
        $url = "https://bird.ioliu.cn/v2?url=https://www.okex.me/v2/spot/instruments/%s/candles?granularity=%d&size=%d";
        $url = sprintf($url, $symbol, $granularity, $size);
        $data = json_decode(vget($url), true);

        if (is_array($data)) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k][0] = strtotime($data['data'][$k][0]);
            }
        }

        return $this->ajaxReturn($data);
    }

    /**
     * 获取交易数据
     * @author Jungle 2019年5月9日 上午10:21:13
     */
    public function getTrades()
    {
        $symbol = trim(I('symbol'));
        $granularity = trim(I('granularity'));
        $test = I('test');
        $size = 1000; // 默认1000条
       // $url = "https://bird.ioliu.cn/v2?url=https://www.okex.me/api/spot/v3/instruments/%s-USDT/trades";
        $url = "https://www.okex.com/api/spot/v3/instruments/%s-USDT/trades";
        $url = sprintf($url, $symbol, $granularity, $size);
        $data = json_decode(vget($url), true);
        return $this->ajaxReturn($data);
    }

    public function candle()
    {

    }
} 
