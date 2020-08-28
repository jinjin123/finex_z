<?php

namespace Home\Controller;

use Home\Tools\Page;
use Think\Controller;
use Home\Model\NoticeModel;
use Common\Api\RedisIndex;
use Common\Api\RedisCluster;
use Think\Db;

class IndexController extends Controller
{
    protected $url = '';
    public $tmpl = '../../../Public/Home/fe';

    private $currencyMap = ['btc', 'eth', 'fec', 'bch', 'ltc', 'bsv', 'eos', 'xrp', 'xem', 'dash', 'xmr', 'neo', 'zrx', 'atom'];

    public function _initialize()
    {
        $this->assign('user', getUserInfo());
    }

    public function index()
    {
//        $ip = get_client_ip();
//        \Think\Log::write('----ldz-----index ip:'.$ip);
        $chain = $this->getChains();
        $ret = $this->getNoticeList([], 3, '*');
        $list = $ret['list'];
        foreach ($list as &$value) {
            $value['title'] = mb_substr($value['title'], 0, 10, 'utf-8');
            $value['content'] = mb_substr(strip_tags(htmlspecialchars_decode($value['content'])), 0, 35, 'utf-8') . '...';
        }
        $this->assign('news', $list);

        $this->assign('chain', $chain);
        $this->display($this->tmpl . '/index');
    }

    public function policy()
    {
        $this->display();
    }

    public function privacy()
    {
        $this->display($this->tmpl . '/privacy');
    }

    /*
     * 错误页面
     * 2017-12-26  yangpeng
     */
    public function errorPage()
    {
        $this->display('/Public/404');
    }

    /**
     * @author 建强   2018年6月13日15:31:23  获取首页行情
     */
    public function getMarketData()
    {
        $res = [
            'code' => 200, 'msg' => '', 'data' => '',
        ];
        $key = "INDEX_PAGE_MARKET_CURRS_INFO";
        $redis = RedisCluster::getInstance();
        $data = $redis->get($key);
        if ($data) {
            $res['data'] = unserialize($data);
            $this->ajaxReturn($res);
        }
        $res['code'] = 404;
        $this->ajaxReturn($res);
    }

    public function home()
    {
        $this->display($this->tmpl . '/index');
    }

    public function about()
    {
        $this->display($this->tmpl . '/about');
    }

    public function service()
    {
        $this->display($this->tmpl . '/service');
    }

    public function terms()
    {
        $this->display($this->tmpl . '/terms');
    }

    public function chart()
    {
        $name = strtoupper(I('get.name'));
        $uid = getUserId();
		$redis  = RedisCluster::getInstance();
        $currencys = $this->getCurrency();
        $map = array_column($currencys, 'currency_name');
        if (!in_array($name, $map)) redirect('/index');
		$chain = [];
        $chain = $this->getChain($name);
		
		if($name == 'FEC'){
			 
			 $chain = unserialize($redis->get('PetcKlineprice'));
			 if($chain){$chain['rate'] = (float)$chain['rate'];}
			if(empty($chain)){
				$chain = M('PetcKline')->where(array('type'=>1))->order('id desc')->find();
				$new_rate = $this->getPetcPrice();
				$chain['rate'] = $new_rate["rate"];
				$chain['open_24h'] = $chain['kaipan_price'];
				$sdiao = serialize($chain);
				$redis->setex('PetcKlineprice',60,$sdiao);
			}
			 
		}
		//dump($chain);
        $usdt = M('IcoIssue')->order('id desc')->find();

        //计算进度
        $usdt['progress'] = (($usdt['issue_num'] - $usdt['exchange_num']) / $usdt['issue_num']) * 100;

        $rate = $this->getRate();

        //判断是否过期
        $time = time();
        $isExpire = false;
        if ($time > $usdt['start_time'] && $time <= $usdt['end_time']) $isExpire = true;

        //获取用户余额
        $usdtCurrency = M('Currency')->where([
            'currency_name' => 'USDT'
        ])->find();
        $coinCurrency = M('Currency')->where([
            'currency_name' => $name
        ])->find();
        $usdtUserCurrency = M('UserCurrency')->where([
            'currency_id' => $usdtCurrency['id'],
            'uid' => $uid
        ])->find();
        $usdtUserCurrency = empty($usdtUserCurrency) ? 0 : $usdtUserCurrency['num'];
        $coinUserCurrency = M('UserCurrency')->where([
            'currency_id' => $coinCurrency['id'],
            'uid' => $uid
        ])->find();
        $coinUserCurrency = empty($coinUserCurrency) ? 0 : $coinUserCurrency['num'];

        $this->assign('usdt_currency', sprintf("%.8f", $usdtUserCurrency));
        $this->assign('coin_currency', sprintf("%.8f", $coinUserCurrency));

        $this->assign('expire', date('Y/m/d H:i:s', $usdt['end_time']));
        $this->assign('is_expire', $isExpire);
        $this->assign('rate', json_encode($rate));
        $this->assign('issue', $usdt);
        $this->assign('name', $name);
        $this->assign('chain', $chain);
        $this->display($this->tmpl . '/chart');
    }

    public function buy()
    {
//        $uid = getUserId();
        $userInfo = getUserInfo();
        if (empty($userInfo)) return $this->ajaxReturn([
            'status' => false, 'msg' => 'Please login first'
        ]);
        $uid = $userInfo['uid'];

        $post = I('post.');

        $chain = $post['chain'];
        $number = $post['number'];
        $qnum = $post['qnum'];
        $invite_code = $post['invite_code'];

        // if ($invite_code == '') return $this->ajaxReturn(['status' => false, 'msg' => 'Invite code cannot be empty']);

        if (!in_array($chain, [1, 2, 3])) return $this->ajaxReturn(['status' => false, 'msg' => 'The currency is incorrect']);

        if ($number <= 0) return $this->ajaxReturn(['status' => false, 'msg' => 'The quantity shall not be less than 0']);

        $time = time();
        $model = M('IcoIssue');
        $condition = ['q_name' => 'FEC', 'q_num' => $qnum];
        $exist = $model->where($condition)->order('id DESC')->find();
        if (empty($exist)) return $this->ajaxReturn(['status' => false, 'msg' => 'There are no issues']);
        if ($time < $exist['start_time'] || $time > $exist['end_time']) {
            return $this->ajaxReturn(['status' => false, 'msg' => 'Activity expired']);
        }

        $key = null;
        switch ($chain) {
            case 1:
                $key = 'btc';
                break;
            case 2:
                $key = 'eth';
                break;
            case 3:
                $key = 'usdt';
                break;
        }

        //绑定用户邀请码
        if (empty($userInfo["invite_code"]) && !empty($invite_code)){
            $save["invite_code"] = $invite_code;
            M("User")->where(["uid"=>$uid])->save($save);
        }
        //判断小于2000美元不让兑换
        if ($chain == 1){
            $btc = $this->getChain('btc');
            if ($btc["last"] *$number < 2000){
                return $this->ajaxReturn(['status' => false, 'msg' => 'Purchase quantity must be greater than 2000$']);
            }
        }elseif ($chain == 2){
            $eth = $this->getChain('eth');
            if ($eth["last"] *$number < 2000){
                return $this->ajaxReturn(['status' => false, 'msg' => 'Purchase quantity must be greater than 2000$']);
            }
        }elseif ($chain == 3){
            if ($number < 2000){
                return $this->ajaxReturn(['status' => false, 'msg' => 'Purchase quantity must be greater than 2000$']);
            }
        }

        //判断购买数量
        $rate = $this->getRate();
        $icoUserNum = round($number * $rate[$key], 8);
        if ($icoUserNum > $exist['issue_num']) return $this->ajaxReturn(['status' => false, 'msg' => 'The purchase quantity exceeds the total quantity of this round']);

        //用户余额
        $keyCurrency = M('Currency')->where([
            'currency_name' => strtoupper($key)
        ])->find();
        $userCurrency = M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $keyCurrency['id']
        ])->find();

        //判断余额
        if ($number > $userCurrency['num']) $this->ajaxReturn([
            'status' => false,
            'msg' => strtoupper($key) . ' balance is not enough.'
        ]);

        //获取邀请码
        $user = getUserInfo();
        $inviteCode = isset($user['invite_code']) ? $user['invite_code'] : '';

        $save = [
            'user_id' => $uid,
            'q_name' => $exist['q_name'],
            'q_num' => $qnum,
            'currency_id' => $keyCurrency['id'],
            'dh_num' => $number,
            'ico_user_num' => $icoUserNum,
            'add_time' => time(),
            'status' => 1,
            'invite_code' => $inviteCode
        ];
        $status = M('UserIcoFinance')->add($save);
        if (!$status) return $this->ajaxReturn(['status' => false, 'msg' => 'Purchase failed']);

        //减去币种
        $model->where($condition)->save([
            'issue_num' => $exist['issue_num'] - $icoUserNum,
            'exchange_num' => $exist['exchange_num'] + $icoUserNum
        ]);

        //用户币种
        $userIco = M('UserIco')->where(['user_id' => $uid, 'q_num' => $qnum])->find();
        if (empty($userIco)) {
            M('UserIco')->add([
                'user_id' => $uid, 'ico_num' => $icoUserNum, 'q_num' => $qnum
            ]);
        } else {
            M('UserIco')->where([
                'user_id' => $uid, 'q_num' => $qnum
            ])->save([
                'ico_num' => $icoUserNum + $userIco['ico_num']
            ]);
        }

        //扣除余额
        M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $keyCurrency['id']
        ])->save([
            'num' => $userCurrency['num'] - $number
        ]);

        //添加petc
        $petcCurrency = M('Currency')->where([
            'currency_name' => 'FEC'
        ])->find();
        $petcUserCurrency = M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $petcCurrency['id']
        ])->find();
        M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $petcCurrency['id']
        ])->save([
            'num' => $petcUserCurrency['num'] + $icoUserNum
        ]);

        return $this->ajaxReturn(['status' => true, 'msg' => 'Buy success']);
    }

    /**
     * 委托单
     */
    public function trade()
    {
        $data = I('post.');

        $uid = getUserId();
        if (!$uid) $this->ajaxReturn(['status' => false, 'msg' => 'please Sign In']);

        //数量不能少于0
        if ($data['number'] <= 0) $this->ajaxReturn(['status' => false, 'msg' => 'Number is invalid']);

        //查询币种是否存在
        $currency = M('Currency')->where([
            'currency_name' => $data['name']
        ])->find();
        if (empty($currency)) $this->ajaxReturn(['status' => false, 'msg' => 'Not Exist Currency']);

        //判断类型是否正确
        if (!in_array($data['type'], [
            'buy', 'sell'
        ])) $this->ajaxReturn(['status' => false, 'msg' => 'Type is invalid']);

        //USDT信息
        $usdt = M('Currency')->where(['currency_name' => 'USDT'])->find();

        //USDT余额信息
        $usdtCurrency = M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $usdt['id']
        ])->find();

        //币种余额信息
        $coinCurrency = M('UserCurrency')->where([
            'uid' => $uid, 'currency_id' => $currency['id']
        ])->find();

        //判断余额
        if ($data['type'] == 'buy') {
            $num = empty($usdtCurrency) ? 0 : $usdtCurrency['num'];
            if ($num < $data['number']) $this->ajaxReturn(['status' => false, 'msg' => 'Balance Is Not Enough']);
        } else {
            $num = empty($coinCurrency) ? 0 : $coinCurrency['num'];
            if ($num < $data['number']) $this->ajaxReturn(['status' => false, 'msg' => 'Balance Is Not Enough']);
        }

        //获取价格牌
        $chain = $this->getChain($data['name']);
        if (empty($chain)) $this->ajaxReturn(['status' => false, 'msg' => 'Chain is empty']);

        //计算价格
        $price = 0;
        if ($data['type'] == 'buy') {
            $price = round($data['number'] / $chain['last'], 8);
        }
        if ($data['type'] == 'sell') {
            $price = round($chain['last'] * $data['number'], 8);
        }

        $sellId = $data['type'] == 'sell' ? $uid : 0;
        $buyId = $data['type'] == 'buy' ? $uid : 0;

        $insert = [
            'order_num' => date('YmdHis') . rand(10000000, 99999999),
            'user_id' => $uid,
            'entrust_type' => $currency['id'],
            'entrust_num' => $data['number'],
            'entrust_price' => $price,
            'entrust_money' => $price * $data['number'],
            'success_num' => $data['number'],
            'sell_id' => $sellId,
            'buy_id' => $buyId,
            'status' => 3,
            'add_time' => time()
        ];

        $result = M('UsdtAreaOrder')->add($insert);

        if (!$result) $this->ajaxReturn(['status' => false, 'msg' => ucfirst($data['type']) . ' Fail']);

        //购买添加余额
        if ($data['type'] == 'buy') {
            //添加币余额
            M('UserCurrency')->where([
                'uid' => $uid, 'currency_id' => $currency['id']
            ])->save([
                'num' => $coinCurrency['num'] + $price
            ]);
            //扣除usdt
            M('UserCurrency')->where([
                'uid' => $uid, 'currency_id' => $usdt['id']
            ])->save([
                'num' => $usdtCurrency['num'] - $data['number']
            ]);
        }

        //抛出扣除余额
        if ($data['type'] == 'sell') {
            //扣除币余额
            M('UserCurrency')->where([
                'uid' => $uid, 'currency_id' => $currency['id']
            ])->save([
                'num' => $coinCurrency['num'] - $data['number']
            ]);
            //添加usdt
            M('UserCurrency')->where([
                'uid' => $uid, 'currency_id' => $usdt['id']
            ])->save([
                'num' => $usdtCurrency['num'] + $price
            ]);
        }

        return $this->ajaxReturn(['status' => true, 'msg' => 'Commit Success']);
    }

    public function getUserCurrency()
    {
        if (IS_AJAX) {
            $uid = getUserId();
            $name = strtoupper(I('get.name'));

            $table = M('Currency')->getTableName();

            $coinCurrency = M('UserCurrency')->alias('uc')->join($table . ' AS u ON u.id = uc.currency_id')->where([
                'uc.uid' => $uid,
                'u.currency_name' => $name
            ])->field([
                'uc.num'
            ])->find();

            $currency = M('UserCurrency')->alias('uc')->join($table . ' AS u ON u.id = uc.currency_id')->where([
                'uc.uid' => $uid,
                'u.currency_name' => 'USDT'
            ])->field([
                'uc.num'
            ])->find();

            $this->ajaxReturn([
                sprintf('%.8f', isset($currency['num']) ? $currency['num'] : 0),
                sprintf('%.8f', isset($coinCurrency['num']) ? $coinCurrency['num'] : 0),
            ]);
        }
        redirect('/');
    }

    //新闻列表
    public function news()
    {
        $ret = $this->getNoticeList([], 3, '*');
        $list = $ret['list'];
        foreach ($list as &$value) {
//            $value['title'] = $value['en-us-title'];
//            $content = strip_tags($value['en-us-content']);
//            $value['content'] = mb_substr($content, 0, 35, 'utf-8') . '...';
            $value['title'] = mb_substr($value['title'], 0, 10, 'utf-8');
            $value['content'] = mb_substr(strip_tags(htmlspecialchars_decode($value['content'])), 0, 35, 'utf-8') . '...';
        }
        $page = $ret['page'];
        $this->assign('url', $this->url);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display($this->tmpl . '/news');
    }

    //新闻详情
    public function getNewsDetail()
    {
        $model = new NoticeModel();
        $where['status'] = 1;
        $id = I('get.id');
        $newsInfo = $model->getNoticeForId($id, '*');
        $newsInfo['content'] = strip_tags(htmlspecialchars_decode($newsInfo['content']));

        $this->assign('info', $newsInfo);
        $this->assign('url', $this->url);
        $this->display($this->tmpl . '/newDetail');
    }

    private function getPetcPrice()
    {
        $row = M('PetcKline')->where([
            'type' => 1
        ])->order('add_time DESC')->limit(1)->find();

        //重新计算涨跌幅
        $condition = [
            'type' => 1,
            'add_time' => [
                ['gt', strtotime('-1 day', strtotime(date('Y-m-d')))],
                ['lt', strtotime(date('Y-m-d')) - 1]
            ]
        ];
        $yesterdayRow = M('PetcKline')->where(array('type'=>7))->order('add_time DESC')->limit(1)->find();

        $shoupan = isset($row['shoupan_price']) ? $row['shoupan_price'] : 0;
        $yesterdayShoupan = isset($yesterdayRow['shoupan_price']) ? $yesterdayRow['shoupan_price'] : 0;

        $rate = $row['rate'];
        if($yesterdayShoupan > 0){
            $rate = round(($shoupan - $yesterdayShoupan) / $yesterdayShoupan, 4) * 100;
        }

//        $issue = M('IcoIssue')->order('id DESC')->limit(1)->find();

        $last = isset($row['last']) ? $row['last'] : 0;

        return [
            'instrument_id' => 'FEC',
            'last' => $last,
            'rate' => $rate,
            'open_24h' => isset($row['kaipan_price']) ? $row['kaipan_price'] : 0,
            'high_24h' => isset($row['high_24h']) ? $row['high_24h'] : 0,
            'low_24h' => isset($row['low_24h']) ? $row['low_24h'] : 0,
            'quote_volume_24h' => isset($row['volume_24h']) ? ($row['volume_24h']+2000)*10 : 0,
            'base_volume_24h' => isset($row['vom_now']) ? ($row['vom_now']+rand(80000,100000)) : 0
        ];
    }

    private function getChains()
    {
        $currencys = $this->getCurrency();
        $coins = array_column($currencys, 'currency_name');

        foreach ($coins as $k => $v) {
            if ($v == 'FEC') {
                $coins[$k] = $coins[3];
                $coins[3] = 'FEC';
            }
        }

        $data = [];
        foreach ($coins as $k => $v) {
            if ($v != 'USDT') {
                $candle = $this->getChain($v);
                if ($candle) array_push($data, $candle);
            }
        }
        foreach ($data as $k => $v) {
            $data[$k]['instrument_id'] = str_replace('-USDT', '', $data[$k]['instrument_id']);
        }
        return $data;
    }

   /* private function getChain($name)
    {
		//$redis  = RedisCluster::getInstance();
        if (strtolower($name) == 'petc') return $this->getPetcPrice();
        $url = 'https://bird.ioliu.cn/v2?url=https://www.okex.me/api/spot/v3/instruments/%s-usdt/ticker';
        $url = sprintf($url, $name);
		$data = array();
		//if(time()%2 == 0){$redis->setex('Petclinegodprice'.$name,NULL);}
		//$data = unserialize($redis->get('Petclinegodprice'.$name)); 
		$data = json_decode(vget($url), true);
		//if(empty($data)){$data = json_decode(vget($url), true);}
//	if(time()%5 == 0){unset($data);}
        if (!empty($data)) {
            $data['instrument_id'] = str_replace('-USDT', '', $data['instrument_id']);
            $data['rate'] = round(($data['last'] - $data['open_24h']) / $data['open_24h'] * 100, 4);
			//$sdiao =  serialize($data);
			//$redis->setex('Petclinegodprice'.$name,300,$sdiao);
        }
        return $data;
    }
    */
    
  private function getChain($name)
    {
  	$redis  = RedisCluster::getInstance();
        if (strtolower($name) == 'fec') return $this->getPetcPrice();
        //$url = 'https://bird.ioliu.cn/v2?url=https://www.okex.me/api/spot/v3/instruments/%s-usdt/ticker';
		$url = 'https://www.okex.com/api/spot/v3/instruments/%s-usdt/ticker';
        $url = sprintf($url, $name);
  	 $data = array();
	  //if(time()%2 == 0){$redis->setex('Petclinegodprice'.$name,NULL);}
	  $data = unserialize($redis->get('Petclinegodprice'.$name)); 
	  //$data = json_decode(vget($url), true);
	  if(empty($data)){
	            $data = json_decode(vget($url), true);
	            $sdiao =  serialize($data);
	            $redis->setex('Petclinegodprice'.$name,300,$sdiao);
	        }
	// if(time()%5 == 0){unset($data);}
	        if (!empty($data)) {
	            $data['instrument_id'] = str_replace('-USDT', '', $data['instrument_id']);
	            $data['rate'] = round(($data['last'] - $data['open_24h']) / $data['open_24h'] * 100, 4);

	        }
	        return $data;
    }


    /**
     * 获取汇率
     * @return array
     */
    private function getRate()
    {
        //$usdt = M('IcoIssue')->find();
		$usdt =  M('PetcKline')->where(['type' => 1])->order('add_time DESC')->limit(1)->find();
		
        //获取btc和eth价格牌
        $btc = $this->getChain('BTC');
        $eth = $this->getChain('ETH');
        $rate = [
            'usdt' => round(1 / $usdt['last'], 8),
            'btc' => round($btc['last'] / $usdt['last'], 8),
            'eth' => round($eth['last'] / $usdt['last'], 8),
        ];
        return $rate;
    }

    private function getCurrency()
    {
        return M('Currency')->where(['status' => 1])->select();
    }

    private function getNoticeList($where = null, $limit = 10, $fidlds = null)
    {
        $count = M('NoticeNew')->where($where)->count();
        $page = new Page($count, $limit);
        $show = $page->show();
        if ($fidlds) {
            $list = M('NoticeNew')->where($where)->order('add_time desc')->field($fidlds)->limit($page->firstRow . ',' . $page->listRows)->select();
        } else {
            $list = M('NoticeNew')->where($where)->order('add_time desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        }
        $res['list'] = $list;
        $res['page'] = $show;
        return $res;
    }

//    /**
//     * 判断登录
//     */
//    private function isLogin()
//    {
//        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
//        $sessionObj = RedisIndex::getInstance(); // 获取session对象
//        $loginInfo = $sessionObj->getSessionValue('LOGIN_INFO');
//        if (!empty($loginInfo['USER_KEY_ID'])) {
//            $this->redirect("UserCenter/index");
//        }
//    }
}
