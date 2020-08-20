<?php
/**
 * Created by PhpStorm.
 * User: lijiang
 * Date: 2017/10/30
 * Time: 10:41
 */

namespace Home\Controller;


use Home\Model\ChongbiModel;
use Home\Model\CurrencyModel;
use Home\Model\EthUrlModel;
use Home\Model\TibiModel;
use Home\Model\UsdtOmniUrlModel;
use Home\Model\UserCurrencyModel;
use Think\Controller;
use Think\Log;

class WallentController extends Controller
{
    public $tmpl = '../../../Public/Home/fe';
    public $uid;
    public $user;

    public function __construct()
    {
        parent::__construct();
        $this->uid = getUserId();
        $this->user = getUserInfo();
    }

    public function qRcode($num, $level = 3, $size = 4)
    {
        $path = "Public/Uploads/"; //本地文件存储路径
        if (!is_dir($path)) {
            mkdir($path);
        }
        Vendor('phpqrcode.phpqrcode');;
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小 //生成二维码图片
        $object = new \QRcode();
        $QR = "Public/Uploads/$num.png";
        $object->png($num, $QR, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    /**
     * @method BTC.ETC.PTC 充币
     */
    public function depositIcon()
    {
        //根据ICONType 获取充币地址,没有随机选最近一个分配给她,更新地址表，
        $currencyName = I('get.currencyName'); //get参数币种名
        $currencyName = strtolower($currencyName);
        // 使用固定地址
        $useFixAddress = true;
        $iconAddress = '';
        $currency = false;
        // 默认的死地址
        if ($currencyName == 'btc') {
            $iconAddress = '3D6QwuJ6F5Zfbgf5WR1xow5nEEZe9kVkn8';
            $currency = BTC;
        }
        if ($currencyName == 'eth') {
            $iconAddress = '0xE68461c23ef054035090248E1BbCad52870035B3';
            $currency = ETH;
        }
        if (!$useFixAddress) {
            $tableName = "trade_" . $currencyName . "_" . 'url';
            $model = M();
            $iconAddress = $model->table($tableName)->where(['user_id' => $this->uid])->find();
            if (!$iconAddress) {
                $coins = 0;
                if ($currencyName == 'btc') {
                    $coins = 1;
                }
                if ($currencyName == 'eth') {
                    $coins = 4;
                }
                if ($coins != 0) {
                    $unbindSize = $model->table($tableName)->where(['user_id' => 0])->count();
                    if ($unbindSize < 100) {
                        $getAddress = getAddress($coins);
                        $address = $getAddress->$currency;
                        $dbData = [];
                        foreach ($address as &$addr) {
                            $data['cz_url'] = $addr;
                            $data['add_time'] = time();
                            array_push($dbData, $data);
                        }
                        if (sizeof($dbData) > 0)
                            $model->table($tableName)->addAll($dbData);
                    }
                }
                $iconAddressInfo = $model->table($tableName)->where(['user_id' => 0])->order('id asc')->find();
                $updateData = [];
                $updateData['user_id'] = $this->uid;
                $updateData['add_time'] = time();
                $res = $model->table($tableName)->where(['id' => $iconAddressInfo['id']])->save($updateData);
                if ($res) {
                    $iconAddress = $iconAddressInfo['cz_url'];
                }
            } else {
                $iconAddress = $iconAddress['cz_url'];
            }
        }
        $this->qRcode($iconAddress);
        $this->assign('currencyName', strtoupper($currencyName));
        $this->assign('iconAddress', $iconAddress);
        $this->assign('user', $this->user);
        $this->display($this->tmpl . '/depositB');
    }

    /**
     * @method USTD
     */
    public function depositUstd()
    {
        $currencyName = "USDT";
        $leftAddress = (new UsdtOmniUrlModel())->getLeftAddress($this->uid);  //左地址
        $rightAddress = (new EthUrlModel())->getRightAddress($this->uid); //右地址
        $this->qRcode($leftAddress);
        $this->qRcode($rightAddress);
        $this->assign('currencyName', $currencyName);
        $this->assign('leftAddress', $leftAddress);
        $this->assign('rightAddress', $rightAddress);
        $this->assign('user', $this->user);
        $this->display($this->tmpl . '/depositU');
    }

    //查询日志记录
    public function iconRecord()
    {
        $type = intval(I('get.type'));
        //var_dump($this->uid);
        $currencyInfo = (new CurrencyModel())->getCurrencyInfo();
        if ($type == 1) {
            //充币记录

            $list = (new ChongbiModel())->getChongbiIcon($this->uid);
            foreach ($list as &$value) {
                $value['currencyName'] = $currencyInfo[$value['currency_id']];
            }
            $this->assign('list', $list);
            $this->assign('user', $this->user);
            $this->display($this->tmpl . '/depositHistory');
        }
        if ($type == 2) {
            //提币记录
            $list = (new TibiModel())->getTibiIcon($this->uid);
            foreach ($list as &$value) {
                $value['currencyName'] = $currencyInfo[$value['currency_id']];
            }
            //var_dump($list);
            $this->assign('list', $list);
            $this->assign('user', $this->user);
            $this->display($this->tmpl . '/withdrawHistory');
        }
    }

    //渲染提币页面
    public function showWithDraw()
    {
        $currencyId = I('get.currencyId');
        $currencyName = I('get.currencyName');
        $model = new UserCurrencyModel();
        $userCurrencyinfo = $model->getUserCurrencyByUid($this->uid, $currencyId);
        $this->assign('currencyName', $currencyName);
        $this->assign('user', $this->user);
        $this->assign('money', $userCurrencyinfo['num']);
        $this->display($this->tmpl . '/withdraw');
    }


    //提币接口 currencyId ,WalletAddress,Quantity
    public function withDrawApi()
    {
        if (IS_AJAX) {
            $data = I("POST.");
            $currency_id = trim($data['id']);
            $walletAddress = trim($data['address']);
            $quantity = trim($data['num']);
            if (!$currency_id || !$walletAddress || !$quantity < 0) {
                return $this->ajaxReturn(['code' => 201, 'msg' => 'Input is wrong']);
            }
            $userMoney = (new UserCurrencyModel())->getUserCurrencyByUid($this->uid, $currency_id);
            if ($quantity > $userMoney['num']) {
                return $this->ajaxReturn(['code' => 203, 'msg' => 'Mention money excess']);
            }
            //写入提币日志 ，修改用户资金列表，
            $tibiData = $this->tibiData($currency_id, $walletAddress, $quantity);
            $updateArr = [
                'num' => bcsub($userMoney['num'], $quantity, 8),
            ];
            $financeData = [
                'uid' => $this->uid,
                'finance_type' => 1,
                'money' => $userMoney['num'],
                'after_money' => bcsub($userMoney['num'], $quantity, 8),
                'add_time' => time(),
                'currency_id' => $currency_id,
                'type' => 2,

            ];
            $msg = '';
            $model = M("");
            $model->startTrans();
            try {
                $model->table('trade_tibi')->data($tibiData)->add();
                $model->table('trade_user_currency')->where(['uid' => $this->uid, 'currency_id' => $currency_id])->save($updateArr);
                $uid = $this->uid;
                $mod = $uid % 4;
                $t = 'trade_user_finance';
                $table = $t . $mod;
                $model->table($table)->data($financeData)->add();   //增加财务日志
                $model->commit();
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $model->rollback();
            }
            if ($msg) return $this->ajaxReturn(['code' => 201, 'msg' => 'fail!!!']);
            return $this->ajaxReturn(['code' => 200, 'msg' => 'Submitted successfully, waiting for confirmation']);
        }
    }

    protected function tibiData($currency_id, $walletAddress, $quantity)
    {
        $data = [];
        $data['uid'] = $this->uid;
        $data['url'] = $walletAddress;
        $data['add_time'] = time();
        $data['num'] = $quantity;
        $data['currency_id'] = $currency_id;
        $data['ti_id'] = get_order();
        $data['collier_fee'] = bcmul($quantity, 0.001);
        $data['actual'] = bcsub($quantity, $data['collier_fee'], 8);
        return $data;
    }

    //币种交换记录
    public function iconChangeRecord()
    {
        $uid = $this->uid;
        $model = M();
        $sql = "select * from trade_usdt_area_order where sell_id = $uid or buy_id = $uid order by add_time desc";
        $list = $model->query($sql);

        //币种
        $_currencys = M('Currency')->select();
        $currencys = [];
        foreach ($_currencys as $k => $v) $currencys[$v['id']] = $v;

        $newData = [];
        foreach ($list as $key => $value) {

            if ($value['buy_id'] != 0) {
                $type = '买入';
                //买入
                $bChain = $currencys[$value['entrust_type']]['currency_name'];
                $bQuantity = $value['entrust_price'];
                $sChain = 'USDT';
                $sQuantity = $value['entrust_num'];
            } else {
                $type = '卖出';
                //卖出
                $sChain = 'USDT';
                $sQuantity = $value['entrust_price'];
                $bChain = $currencys[$value['entrust_type']]['currency_name'];
                $bQuantity = $value['entrust_num'];
            }

            array_push($newData, [
                'b_chain' => $bChain,
                'b_quantity' => $bQuantity,
                's_chain' => $sChain,
                's_quantity' => $sQuantity,
                'add_time' => date('Y-m-d H:i:s', $value['add_time'])
            ]);

//            $newData[$key]['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
//            $newData[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
//            if ($value['entrust_type'] == 1 && $value['sell_id'] == $uid) {
//                $newData[$key]['s_chain'] = 'BTC';
//                $newData[$key]['quantity1'] = $value['success_num'];
//                $newData[$key]['b_chain'] = 'USDT';
//                $newData[$key]['quantity2'] = bcmul($value['entrust_price'], $value['success_num'], 8);
//            }
//            if ($value['entrust_type'] == 1 && $value['buy_id'] == $uid) {
//                $newData[$key]['s_chain'] = 'USDT';
//                $newData[$key]['quantity1'] = bcmul($value['entrust_price'], $value['success_num'], 8);
//                $newData[$key]['b_chain'] = 'BTC';
//                $newData[$key]['quantity2'] = $value['success_num'];
//            }
//            if ($value['entrust_type'] == 4 && $value['sell_id'] == $uid) {
//                $newData[$key]['s_chain'] = 'ETH';
//                $newData[$key]['quantity1'] = $value['success_num'];
//                $newData[$key]['b_chain'] = 'USDT';
//                $newData[$key]['quantity2'] = bcmul($value['entrust_price'], $value['success_num'], 8);
//            }
//            if ($value['entrust_type'] == 4 && $value['buy_id'] == $uid) {
//                $newData[$key]['s_chain'] = 'USDT';
//                $newData[$key]['quantity1'] = bcmul($value['entrust_price'], $value['success_num'], 8);
//                $newData[$key]['b_chain'] = 'ETH';
//                $newData[$key]['quantity2'] = $value['success_num'];
//            }
        }
        // ico兑换
        $sql_ico = "select c.currency_name cn, i.dh_num dn, i.q_name qn, i.ico_user_num iun, i.add_time at from trade_user_ico_finance i, trade_currency c where i.user_id = $this->uid and i.currency_id=c.id";
        $list_ico = $model->query($sql_ico);
        foreach ($list_ico as $key => $value) {
            array_push($newData, [
                    'b_chain' => $value['cn'],
                    'b_quantity' => $value['dn'],
                    's_chain' => $value['qn'],
                    's_quantity' => $value['iun'],
                    'add_time' => date('Y-m-d H:i:s', $value['at'])
                ]
            );
        }
        $this->assign('list', $newData);
        $this->assign('user', $uid);
        $this->display($this->tmpl . '/exchangeHistory');
    }


    public function rechargeCall()
    {
        $returnData = [
            'code' => 0,
            'succeed' => true,
            'data' => []
        ];
        $callData = $this->initPostJsonData();
        $sign = $callData['sign'];
        $txs = $callData['txs'];
        $mySign = md5(json_encode($txs));
        if ($sign != $mySign) {
            \Think\Log::write("sign not match: " . $sign . "-->my: " . $mySign, Log::WARN);
            $returnData['data'] = $txs;
            $this->ajaxReturn($returnData, 'JSON');
        }
        foreach ($txs as &$tx) {
            $amount = floatval($tx['amount']);
            if (!$tx['txid']
                || !$tx['to']
                || !$tx['currency']
                || !$amount
                || $amount <= 0) {
                \Think\Log::write("tx info: " . json_encode($tx) . " error！", Log::WARN);
                array_push($returnData['data'], $tx);
                continue;
            }

            if (!$tx['Direction'] != 'IN') {
                \Think\Log::write("tx info: " . json_encode($tx) . " not in ignore this", Log::WARN);
                array_push($returnData['data'], $tx);
                continue;
            }
//            if (($tx['currency'] == 'USDT' && $amount < 100)
//                || ($tx['currency'] == 'BTC' && $amount < 0.01)
//                || ($tx['currency'] == 'ERC_USDT' && $amount < 100)
//                || ($tx['currency'] == 'ETH' && $amount < 0.1)
//            ) {
            if (($tx['currency'] == 'USDT' && $amount < 5)
                || ($tx['currency'] == 'BTC' && $amount < 0.001)
                || ($tx['currency'] == 'ERC_USDT' && $amount < 5)
                || ($tx['currency'] == 'ETH' && $amount < 0.01)
            ) {
                \Think\Log::write("tx amount: " . $tx . " error", Log::WARN);
                array_push($returnData['data'], $tx);
                continue;
            }
            if ($tx['currency'] == 'BTC') {
                $currencyName = "btc";
                $currencyId = 1;
            } else if ($tx['currency'] == 'ERC_USDT' || $tx['currency'] == 'ETH') {
                $currencyName = "eth";
	 	$tx['time'] = intval($tx['time'] / 1000);
                $currencyId = $tx['currency'] == 'ERC_USDT' ? 8 : 4;
            } else if ($tx['currency'] == 'USDT') {
                $currencyName = "usdt_omni";
                $currencyId = 8;
            } else {
                // 未知的充币类型
                \Think\Log::write("unknown currency" . $tx['currency'] . " error", Log::WARN);
                array_push($returnData['data'], $tx);
                continue;
            }
            $tableName = "trade_" . $currencyName . "_" . 'url';
            $model = M();
            $iconAddress = $model->table($tableName)->where(['cz_url' => $tx['to']])->find();
            if (!($iconAddress && $iconAddress['user_id'] > 0)) {
                \Think\Log::write("not found user address in table " . $tableName . " for:" . $tx['to'], Log::WARN);
                array_push($returnData['data'], $tx);
                continue;
            }
            $record = $model->table("trade_chongbi")->where(['ti_id' => $tx['txid'], 'currency_id' => $currencyId])->find();
            if ($record) { // 已记录
                \Think\Log::write("tx has been charged in table trade_chongbi for:" . $tx['to'], Log::INFO);
                array_push($returnData['data'], $tx);
                continue;
            } else if (empty($record)) { // 未记录
                $myzr_data['uid'] = $iconAddress['user_id']; //用户id
                $myzr_data['url'] = $tx['to']; //地址
                $myzr_data['add_time'] = $tx['time']; // 转为毫秒
                $myzr_data['num'] = $amount;
                $myzr_data['status'] = 2; //2 转入成功',
                $myzr_data['ti_id'] = $tx['txid'];//
                $myzr_data['check_time'] = time(); //币名称
                $myzr_data['currency_id'] = $currencyId; //币名称,USDT
                $myzr_data['fee'] = 0;
                $myzr_data['actual'] = $amount;
                $changestatus = $model->table('trade_chongbi')->add($myzr_data);
                if (!$changestatus) {
                    \Think\Log::write($iconAddress['user_id'] . " chongbi error！", Log::WARN);
                }
                $user_coin_db = $model->table("trade_user_currency")->where(['uid' => $iconAddress['user_id']])->find();
                $usermoneyadd = $model->table("trade_user_currency")->execute("UPDATE trade_user_currency SET num = num+" . $amount . "  WHERE uid = '" . $iconAddress['user_id'] . "' and currency_id =  " . $currencyId);
                $after_money = $user_coin_db['num'] + $amount;

                // C.加入充币财务记录
                $insert_data['uid'] = $iconAddress['user_id'];
                $insert_data['finance_type'] = 2;
                $insert_data['content'] = "充币";
                $insert_data['type'] = 1;
                $insert_data['after_money'] = $after_money;/////2017 9 28 增加一个用户余额aftermoney字段记录
                $insert_data['money'] = $amount;
                $insert_data['add_time'] = time();
                $insert_data['currency_id'] = $currencyId;
                $fenbiao_num = $iconAddress['user_id'] % 4;

                $caiwu = $model->table('trade_user_finance' . $fenbiao_num)->add($insert_data);
                if (!$caiwu) {
                    \Think\Log::write($iconAddress['user_id'] . "  add finance failed！", Log::WARN);
                }

                /////提交事务
                if ($changestatus && $usermoneyadd && $caiwu) {
                    //if($changestatus && $usermoneyadd){
                    //mysql_query('COMMIT');///////事务提交
                    /// mysqli_autocommit($this->db,true);///////事务提交
                    \Think\Log::write($iconAddress['user_id'] . ": " . $tx['from'] . "-->" . $tx['to'] . " charge " . $currencyName . " " . $amount . "-->" . $after_money . " success", Log::INFO);
                    array_push($returnData['data'], $tx);
                } else {
                    \Think\Log::write(" chongbi error", Log::WARN);
                    array_push($returnData['data'], $tx);
                    //mysql_query('ROLLBACK ');///事务回滚
                    //mysqli_rollback($this->db);///事务回滚
                }
            }

        }
        $this->ajaxReturn($returnData, 'JSON');
    }

    private function initPostJsonData()
    {
        if (empty($_POST)) {
            $content = file_get_contents('php://input');
            $post = (array)json_decode($content, true);
        } else {
            $post = $_POST;
        }
        return $post;
    }

}
