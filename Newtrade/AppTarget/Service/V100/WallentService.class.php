<?php
namespace AppTarget\Service\V100;

use App\Service\ServiceBase;
use Common\Api\Tibi;
use Common\Api\Wallent;

/**
 * APP钱包接口
 * @date 2019年2月28日 上午11:54:17
 *
 * @author Jungle
 */
class WallentService extends ServiceBase
{

    /**
     * 钱包对象
     *
     * @var object
     */
    // protected $wallentInstance = null;
    
    /**
     * 初始化
     *
     * @author Jungle 2019年3月6日 下午8:52:02
     */
    public function __construct()
    {
        parent::__construct();
        
        // 判断是否登录
        if (! $this->isLogin()) {
            $this->errno = 9998;
            return;
        }
        
        // 钱包对象（初始化，并把用户ID赋值，方便前置动作判断实名认证）
        $this->wallentInstance = Wallent::instance($this->getUserId());
        
        $this->errno = 0;
    }

    /**
     * 提币操作接口
     *
     * @author Jungle 2019年2月27日 下午5:29:53
     * @return number|boolean|number[]
     */
    public function tiBi()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $number = isset($data['number']) ? floatval($data['number']) : 0;
        
        $collierFee = isset($data['collier_fee']) ? floatval($data['collier_fee']) : 0;
        
        $tradePwd = isset($data['trade_pwd']) ? trim(strval($data['trade_pwd'])) : '';
        
        $verifyCode = isset($data['phoneCode']) ? trim(strval($data['phoneCode'])) : '';
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $address = isset($data['address']) ? trim($data['address']) : ''; // 提币地址
        
        $result = $this->wallentInstance->tibi($userId, $currencyId, $number, $address, $collierFee, $verifyCode, $tradePwd);
        
        if ($result['code'] != 0)
            return $this->return_error_num($result['code'], $result['msg']);
        
        return $result['data'];
    }

    /**
     * 提币记录查看接口（不作币种维护处理）
     *
     * @author Jungle 2019年2月27日 下午5:30:02
     * @return number|StdClass|unknown[]|string[][]|number[][]|unknown[][]|array[]
     */
    public function showTiBi()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $page = isset($data['page']) && $data['page'] > 0 ? intval($data['page']) : 1;
        
        $limit = isset($data['limit']) && $data['limit'] > 0 ? intval($data['limit']) : 10;
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $status = isset($data['status']) ? intval($data['status']) : null;
        
        $result = $this->wallentInstance->getTibiList($page, $limit, $userId, $currencyId, $status);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 充币记录查看接口（不作币种维护处理）
     *
     * @author 建强 2019年2月26日16:03:21
     * @method 查看充值记录接口
     * @param
     *            currency_id 币种id
     * @param
     *            status 充币记录状态 1为充值中 2为充值成功 3为充值失败
     * @param
     *            page 当前分页
     * @param
     *            limit 每页显示数量
     * @return array
     */
    public function showChongBi()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $page = isset($data['page']) && $data['page'] > 0 ? intval($data['page']) : 1;
        
        $limit = isset($data['limit']) && $data['limit'] > 0 ? intval($data['limit']) : 10;
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $status = isset($data['status']) ? intval($data['status']) : null;
        
        $result = $this->wallentInstance->getChongbiList($page, $limit, $userId, $currencyId, $status);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 用户余额查看接口（不作币种维护处理）
     *
     * @author Jungle 2019年2月27日 下午5:01:59
     * @return number|unknown
     */
    public function balanceSearch()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : null;
        
        $result = $this->wallentInstance->getUserBalance($userId, $currencyId);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 获取充币币种列表
     *
     * @author Jungle 2019年2月27日 下午4:34:46
     * @return number[]|unknown[]
     */
    public function getAllCurrency()
    {
        $result = $this->wallentInstance->getChongbiCurrency(2);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data']['data'];
    }

    /**
     * 获取充币地址
     *
     * @author Jungle 2019年2月27日 下午5:31:37
     * @return number|mixed[]|NULL[]|unknown[]|string[][]|unknown[][]|object[]
     */
    public function getChongBiAddress()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : null;
        
        $result = $this->wallentInstance->getMyChargePackUrl($userId, $currencyId);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 绑定充币地址
     *
     * @author Jungle 2019年2月27日 下午5:31:46
     * @return number|number[]
     */
    public function bindChongBiAddress()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $result = $this->wallentInstance->updateMyChargePackUrl($userId, $currencyId);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 更新充币地址
     *
     * @author Jungle 2019年2月27日 下午5:31:54
     * @return number|number[]|unknown[]
     */
    public function changeChongBiAddress()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $result = $this->wallentInstance->updateMyChargePackUrl($userId, $currencyId);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 获取提币币种列表（包含手续费信息）
     *
     * @author Jungle 2019年2月27日 下午5:32:11
     * @return number|string
     */
    public function getCurrencyAddrInfo()
    {
        $userId = $this->getUserId();
        
        $result = $this->wallentInstance->getTibiCurrency($userId);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 获取提币手机验证码（不作币种维护处理）
     *
     * @author Jungle 2019年2月27日 下午5:31:24
     * @return number|number[]
     */
    public function sendPhoneCode()
    {
        $sence = 'APP_TIBI';
        $sms = new \Home\Sms\Yunclode();
        
        $uid = $this->getUserId();
        
        $userinfo = M('User')->where([
            'uid' => $uid
        ])->find();
        $om = $userinfo['om'];
        $phone = $userinfo['phone'];
        
        $res = $sms->ApiSendPhoneCode($uid, $om, $phone, $sence, 2, $userinfo['username']);
        
        if ($res == 0)
            return [
                'status' => 1
            ]; // 短信发送成功
        
        if ($res == 403 || $res == 413)
            return 30013; // 短信发送频率过快
        
        return 30014; // 短信发送失败
    }

    /**
     * 删除提币地址
     *
     * @deprecated
     *
     * @author Jungle 2019年2月27日 下午5:30:48
     * @return number|number[]
     */
    public function delAddress()
    {
        $userId = $this->getUserId();
        
        $data = $this->getData();
        
        $address = isset($data['address']) ? trim(strval($data['address'])) : null;
        
        $currencyId = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $result = $this->wallentInstance->deleteMentionAddress($userId, $currencyId, $address);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }

    /**
     * 查看系统中满足条件的币种信息列表
     *
     * @author Jungle 2019年2月27日 上午11:08:14
     * @return number|number[]|unknown[]
     */
    public function getAllCurrencyHasBalance()
    {
        $userId = $this->getUserId();

        $data = $this->getData();
        
        $type = isset($data['type']) ? intval($data['type']) : 0;
        
        $result = $this->wallentInstance->getAllCurrencyForList($userId, $type);
        
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data']['data'];
    }

    /**
     * 绑定提币地址接口
     *
     * @deprecated 不使用
     * @author Jungle 2019年2月27日 下午5:30:39
     * @return number|number[]
     */
    public function bindAddress()
    {
        $uid = $this->getUserId();
        
        $data = $this->getData();
        
        $addr_index = isset($data['addr_index']) ? intval($data['addr_index']) : null;
        
        $currency_id = isset($data['currency_id']) ? intval($data['currency_id']) : 0;
        
        $address = isset($data['address']) ? trim(strval($data['address'])) : null; // 提币地址
        
        $memo = isset($data['memo']) ? trim(strval($data['memo'])) : null; // eos的memo
        
        $result = $this->wallentInstance->updateMyMentionPackUrl($uid, $currency_id, $address, $addr_index, $memo);
        if ($result['code'] != 0)
            return $result['code'];
        
        return $result['data'];
    }
}