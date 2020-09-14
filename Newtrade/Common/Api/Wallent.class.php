<?php
namespace Common\Api;

use Think\Exception;
use Common\Model\CurrencyModel;
use Home\Logics\PublicFunctionController;

/**
 * 钱包操作相关方法
 * @date 2019年3月1日 下午5:29:44
 *
 * @author Jungle
 */
class Wallent
{

    /**
     * 用户ID
     * @var int
     */
    public $userId = null;
    
    /**
     * 币种ID数组
     *
     * @var array
     * @example ['BTC'=>1,........]
     */
    public $currencyIds = [];

    /**
     * 初始化
     * @author Jungle 2019年3月1日 下午5:50:05
     */
    public function __construct($userId = null)
    {
        $this->userId = $userId;
        $this->currencyIds = C('currency_ids');
    }
    
    /**
     * 前置方法
     * @author Jungle 2019年3月13日 下午2:29:42
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) 
    {
        //需实名认证的方法
        $validateFuncs = [
            'tibi', //提币
            'getTibiList', //获取提币记录
            'getChongbiList', //获取充币记录
            'getUserBalance', //获取用户余额
            'getAllCurrency', //获取充币币种列表
            'getMyChargePackUrl', //获取充币地址
            'updateMyChargePackUrl', //绑定或更新充币地址
            'updateMyMentionPackUrl', //更新提币地址
            'getMentionCurrencyInfo', //获取提币时币种相关信息
            'getAllCurrencyForList' //获取充提币记录的币种列表
        ];
        if(in_array($method, $validateFuncs) && !empty($this->userId)){
            
            //判断用户ID有效性
            if ($this->userId < 1) return $this->setMsg(9998);
            
            //实名认证
            $result = $this->validateUserReal($this->userId);
            if($result['code'] != 0) return $result;
            
        }
        
        return call_user_func_array([$this, $method], $args);
    }
    
    /**
     * 实例化当前实例，并返回
     * @author Jungle 2019年3月13日 上午10:17:48
     * @param int $userId 用户ID，若不为空，则验证用户一系列相关逻辑
     * @return \Common\Api\Wallent
     */
    public static function instance($userId = null)
    {
        return new self($userId);
    }
    
    /**
     * 提币操作
     * @author Jungle 2019年3月13日 上午10:07:06
     * @param int $userId 用户ID
     * @param int $currencyId 币种ID
     * @param float $number 提币数量
     * @param string $address 提币地址
     * @param float $collierFee 旷工费
     * @param string $verifyCode 验证码
     * @param string $tradePwd 交易密码
     * @return array
     */
    protected function tibi($userId, $currencyId, $number, $address, $collierFee, $verifyCode, $tradePwd)
    {
        $number = is_numeric($number) ? floatval($number) : 0;
        
        $collierFee = is_numeric($collierFee) ? floatval($collierFee) : 0;
        
        $tradePwd = !empty($tradePwd) ? trim(strval($tradePwd)) : '';
        
        $verifyCode = !empty($verifyCode) ? trim(strval($verifyCode)) : '';
        
        $currencyId = !empty($currencyId) ? intval($currencyId) : 0;
        
        $address = !empty($address) ? trim($address) : '';  //提币地址
        
        $redisClient = RedisCluster::getInstance();
        
        if ($currencyId < 1) return $this->setMsg(10000);
        
        //提币数量不能为空
        if (empty($number) || $number == 0) return $this->setMsg(30004);
        
        //矿工费填写出错
        if (empty($collierFee) || $collierFee < 0.001 || !is_numeric($collierFee) ) return $this->setMsg(30040);
        
        //提币数量需比矿工费多出至少0.01
        if( bcsub($number, $collierFee, 3) < 0.001 ) return $this->setMsg(30043);
        
        //验证交易密码是否为空
        if (empty($tradePwd) || $tradePwd == '') return $this->setMsg(30002);
        
        //验证手机验证码是否为空
        if (empty($verifyCode) || $verifyCode == '') return $this->setMsg(30005);
        
        //验证提币地址是否为空
        if( empty($address) || $address == '' ) return $this->setMsg(30028);
        
        //验证提币地址
        $validateAddress = BchAddressApi::checkBCHaddrByApi($currencyId, $address);
        if($validateAddress['code'] != 200) return $this->setMsg(30045, [], $validateAddress['msg']);
        
        //检测用户资金密码错误次数及重置资金错误次数
        $check_pwd_res = PublicFunctionController::checkUserTradePwdMissNum($userId, $tradePwd);
        if($check_pwd_res['code'] != 200) return $this->setMsg(30006, [], $check_pwd_res['msg']);
        
        //判断币种是否正在维护
        if(!CurrencyModel::getIsNormal($currencyId, 2)) return $this->setMsg(30044);
        
        //验证提币地址是否正确
        $where = ['uid' => $userId, 'currency_id' => $currencyId];
        $userCurrency = M('UserCurrency')->where($where)->find();
        $allAddress = [
            $userCurrency['my_mention_pack_url1'],
            $userCurrency['my_mention_pack_url2'],
            $userCurrency['my_mention_pack_url3']
        ];
        if(!in_array($address, $allAddress)) return $this->setMsg(30029);
        
        //验证地址是否在24小时之内
        foreach ($allAddress as $key => $value) {
            if( $value == $address ){
                $index = $key + 1;
                break;
            }
        }
        $add_time = $userCurrency['url_date' . $index];
        if ( time() - $add_time < 24 * 60 * 60 ) return $this->setMsg(30008);
        
        //获取用户信息
        $userinfo = M('User')->where(['uid' => $userId])->find();
        
        //判断提币数量是否正确
        $user_level = $userinfo['level'];
        $can_tibi_max_num = Tibi::CheckTibiConfigNum($userId, $user_level, $currencyId, $number);
        if($can_tibi_max_num['code'] != 200) return $this->setMsg(30009, [], $can_tibi_max_num['msg']);
        
        //验证手机验证码
        $phone_key = 'APP_TIBI_'.$userId.'_'.$userinfo['phone'];
        $res = checkSmsCode($userId, $userinfo['phone'], 'APP_TIBI', trim($verifyCode));
        if( !$res ) return $this->setMsg(30007);
        
        $tibi_data['url'] = $address;
        $tibi_data['uid'] = $userId;
        $tibi_data['add_time'] = time();
        $tibi_data['num'] = $number;
        $tibi_data['status'] = 0;
        $tibi_data['currency_id'] = $currencyId;
        $tibi_data['actual'] = $number - $collierFee;
        $tibi_data['collier_fee'] = $collierFee;
        
        $model = M();
        $model->startTrans();
        
        try{
            $res[] = M('Tibi')->add($tibi_data);
            
            $UserMoneyApi = new \Home\Logics\UserMoneyApi;
            $res[] = $UserMoneyApi->setUserMoney($userId, $currencyId, $number, 'num', 'dec');
            $after_money = bcsub($userCurrency['num'],$number,8);
            $user_finance_data['uid'] = $userId;
            $user_finance_data['currency_id'] = $currencyId;
            $user_finance_data['finance_type'] = 1;
            $user_finance_data['money'] = $number;
            $user_finance_data['after_money'] = $after_money;
            $user_finance_data['add_time'] = time();
            $user_finance_data['type'] = 2;
            $user_finance_data['content'] = '提币';
            
            $res[] = M(getTbl('UserFinance', $userId))->add($user_finance_data);//UserFinance中增加财务记录
            if ( !in_array(false, $res) ) {
                $model->commit();
                //提币成功，删除缓存中对象
                $redisClient->del($phone_key);
                return $this->setMsg(0, ['tibi_status' => 1]); //提币成功
            }
            
            $model->rollback();
            return $this->setMsg(0, ['tibi_status' => 0]); //提币失败
            
        }catch(Exception $e){
            $model->rollback();
            return $this->setMsg(0, ['tibi_status' => 0]); //提币失败
        }
    }
    
    /**
     * 更新用户充币地址
     * @author Jungle 2019年3月4日 上午10:50:43
     * @param int $userId
     * @param int $currencyId
     * @return array
     */
    protected function updateMyChargePackUrl($userId, $currencyId)
    {
        if ($currencyId < 1) return $this->setMsg(10000);
        
        //判断币种是否正在维护
        if(!CurrencyModel::getIsNormal($currencyId, 1)) return $this->setMsg(30044);
        
        //是否已绑定
        $isFirstBinding = $this->getIsFirstBinding($userId);
        
        //获取币种地址池表名
        $tableName = $this->getTableName($userId, $currencyId);
        
        //获取币种地址表字段名
        $field = $this->getAddrField($currencyId);
        
        //查询原充币地址
        $where = ['uid' => $userId, 'currency_id' => $currencyId];
        $result = M('UserCurrency')->where($where)->find();
        
        //判断该币种是否绑定过
        if($result['my_charge_pack_url'] != ''){
            $myChargePackUrl = $result['my_charge_pack_url'];
            
            //注意！充币地址绑定24小时内，请勿频繁更换
            $addTime = M($tableName)->where([$field => $myChargePackUrl, 'user_id' => $userId])->getField('add_time');
            if ( $addTime > 0 && time() < $addTime + 24 * 3600 ) return $this->setMsg(30021);
            
            //存在充币地址 判断是否存在
            $condition = [
                'url' => $myChargePackUrl, 'status' => 2,
                'uid' => $userId, 'currency_id' => $currencyId
            ];
            $count = M('Chongbi')->where($condition)->count();
            if (!$count) return $this->setMsg(30022);//地址没有充币成功记录,无法更换
        }
        
        //开始事务
        $model = M();
        $model->startTrans();
        try {
            
            //删除之前绑定的地址记录
            M($tableName)->where(['user_id' => $userId])->delete();
            
            //随机抽取一条没有绑定的地址
            $res = M($tableName)->where([
                'user_id' => 0
            ])->field($field)->order('rand()')->find();
            if( !$res ) return $this->setMsg(9999);//操作失败
            
            //充币地址
            $myChargePackUrl = $res[$field];
            
            //更新充币地址到用户表
            $status = M('UserCurrency')->where(['uid' => $userId, 'currency_id' => $currencyId])->save(['my_charge_pack_url' => $myChargePackUrl]);
            if(!$status){
                $model->rollback();
                return $this->setMsg(9999);
            }
            
            //更新用户ID到币种地址表
            $status = M($tableName)->where([$field => $myChargePackUrl])->save(['user_id' => $userId, 'add_time' => time()]);
            if(!$status){
                $model->rollback();
                return $this->setMsg(9999);
            }
            
            //未绑定
            if ($isFirstBinding) {
                $status = $this->insertUserScoreLog($userId);
                if(!$status){
                    $model->rollback();
                    return $this->setMsg(9999);
                }
            }
            
            $model->commit();
            
            $data = [
                'status' => 1,
                'cashaddr' => $this->getBchBsvAddress($currencyId, $myChargePackUrl),
                'legacy' => '',
                'eos_memo' => ''
            ];
            
            //判断是否bch或者bsv币种
            if($this->getIsBch($currencyId) || $this->getIsBsv($currencyId)) $data['legacy_url'] = $res['old_cz_url'];
            
            //判断是否eos币种
            if($this->getIsEos($currencyId)) {
                $data['eos_memo'] = $data['cashaddr'];
                $data['cashaddr'] = C('EOS_FIX_URL');
            }
            
            return $this->setMsg(0, $data);
            
        }catch(Exception $e){
            $model->rollback();
            return $this->setMsg(9999);
        }
    }
    
    /**
     * 更新用户提币地址
     * @author Jungle 2019年3月4日 上午11:34:32
     * @param int $userId
     * @param int $currencyId
     * @return array
     */
    protected function updateMyMentionPackUrl($userId, $currencyId, $address, $addressIndex, $memo = '')
    {
        $addressIndex = intval($addressIndex);
        $address = trim(strval($address));
        
        //币种ID判断
        if ($currencyId < 1) return $this->setMsg(10000);
        
        //地址不能为空
        if (empty($address)) return $this->setMsg(20001);
        
        //判断是否eos币种
        if($this->getIsEos($currencyId)) {
            //判断memo是否为空
            if(empty($memo)) return $this->setMsg(20001);
            //判断memo长度
            if(strlen($memo) < 3) return $this->setMsg(30019);
        }else{
            //提币地址长度不对
            if( strlen($address) < 15 || strlen($address) > 48 ) return $this->setMsg(30019);
        }
        
        //地址格式有误
        if (!regex($address, 'addurl')) return $this->setMsg(20002);
        
        //数据请求有误
        if ( $addressIndex < 1 || $addressIndex > 3 ) return $this->setMsg(30010);
        
        //判断币种是否正在维护
        if(!CurrencyModel::getIsNormal($currencyId, 2)) return $this->setMsg(30044);
        
        //bch,bsv地址验证
        $result = BchAddressApi::checkBCHaddrByApi($currencyId, $address);
        if($result['code'] != 200) return $this->setMsg(30047);
        
        //判断是否实名认证
        $status = checkUserReal($userId);
        if( $status == -2 ) return $this->setMsg(30000);//未进行实名认证
        if( $status == 0 ) return $this->setMsg(30017);//等待认证
        if( $status == -1 ) return $this->setMsg(30018);//失败
        
        //判断是否eos并对地址作处理
        if($this->getIsEos($currencyId)) $address = $address.':'.$memo;
        
        //判断是否三个地址相同
        $status = $this->validateMyMentionPackUrl($userId, $currencyId, $address);
        if(!$status) return $this->setMsg(30046);
        
        $model = M();
        $model->startTrans();
        
        try{
            
            //是否已绑定
            $isFirstBinding = $this->getIsFirstBinding($userId);
            if ($isFirstBinding) {
                $status = $this->insertUserScoreLog($userId, 1);
                if(!$status){
                    $model->rollback();
                    return $this->setMsg(9999);
                }
            }
            
            //更新用户提币地址
            $status = M('UserCurrency')->where([
                'uid' => $userId, 
                'currency_id' => $currencyId
            ])->save([
                'my_mention_pack_url' . $addressIndex => $address, 
                'url_date' . $addressIndex => time()
            ]);
            if (!$status) {
                $model->rollback();
                return $this->setMsg(9999);
            }
            
            $model->commit();
            return $this->setMsg(0, ['status' => 1]);
            
        }catch (Exception $e){
            $model->rollback();
            return $this->setMsg(9999);
        }
    }
    
    /**
     * 插入用户赠送积分记录
     * @author Jungle 2019年3月5日 上午11:50:54
     * @param int $userId
     * @param number $type 类型（0：充币，1：提币）
     * @return boolean
     */
    public function insertUserScoreLog($userId, $type = 0)
    {
        $PublicFun = new PublicFunctionController();
        $status = $PublicFun->calUserIntegralAndLeavl($userId, 10, [
            'operationType' => 'inc',
            'scoreInfo' => '第一次綁定'.($type == 0 ? '充' : ' 提').'币地址贈送積分',
            'status' => $type == 0 ? 3 : 4
        ]);
        return $status;
    }
    
    /**
     * 删除提币地址
     * @author Jungle 2019年3月13日 下午12:33:10
     * @param int $userId
     * @param int $currencyId
     * @param string $address
     * @return array
     */
    public function deleteMentionAddress($userId, $currencyId, $address)
    {
        $address = !empty($address) ? trim(strval($address)) : null;
        
        $currencyId = !empty($currencyId) ? intval($currencyId) : 0;
        
        if( $currencyId < 1 ) return $this->setMsg(10000);
        
        if ( !$address ) return $this->setMsg(10000);
        
        //判断币种是否正在维护
        if(!CurrencyModel::getIsNormal($currencyId, 2)) return $this->setMsg(30044);
        
        $uc_info = M('UserCurrency')->where([
            'uid' => $userId,
            'currency_id' => $currencyId
        ])->find();
        $pack_str = '';
        foreach ($uc_info as $k=>$v){
            if( $v == $address ){
                $pack_str = $k;
                break;
            }
        }
        $del_data[$pack_str] = '';
        $res = M('UserCurrency')->where(['uid' => $userId, 'currency_id' => $currencyId])->save($del_data);
        if ($res) return $this->setMsg(0, ['is_success' => 1]);
        
        return $this->setMsg(9999);
    }
    
    /**
     * 判断提交的地址是否与我的提币地址一致
     * @author Jungle 2019年3月5日 上午11:10:40
     * @param int $userId
     * @param int $currencyId
     * @param string $address
     * @return boolean
     */
    public function validateMyMentionPackUrl($userId, $currencyId, $address)
    {
        $result = M('UserCurrency')->where(['uid' => $userId, 'currency_id' => $currencyId])->field([
            'my_mention_pack_url1',
            'my_mention_pack_url2',
            'my_mention_pack_url3'
        ])->find();
        return in_array($address, $result) ? false : true;
    }
    
    /**
     * 判断是否实名认证
     * @author Jungle 2019年3月13日 下午2:25:38
     * @param int $userId
     * @return array
     */
    private function validateUserReal($userId)
    {
        $status = checkUserReal($userId);
        switch($status){
            case 0: return $this->setMsg(30000);break;//未进行实名认证
            case -1: return $this->setMsg(30017);break;//等待认证
            case -2: return $this->setMsg(30018);break;//失败
        }
        return $this->setMsg();
    }

    /**
     * 提币记录查看接口
     * @author Jungle 2019年3月13日 下午12:08:20
     * @param array $filter
     * @return array
     */
    protected function getTibiList($page, $limit, $userId, $currencyId, $status)
    {
        $result = [
            'total' => 0, 'list' => [], 'page' => 0
        ];
        
        $page = $page > 0 ? intval($page) : 1;
        
        $limit = $limit > 0 ? intval($limit) : 10;
        
        $currencyId = !empty($currencyId) ? intval($currencyId) : 0;
        
        $status = is_numeric($status) ? intval($status) : null;
        
        $c = M('Currency')->getTableName();
        
        //关联
        $join = $c.' AS c ON c.id = t.currency_id';
        
        //排序
        $orderBy = ['t.add_time DESC'];
        
        //字段选择
        $field = ['t.*', 'c.currency_name'];
        
        $model = D('Tibi')->alias('t')->join($join, 'LEFT');
        
        //筛选条件
        $condition = ['t.uid' => $userId];
        
        if(!empty($currencyId)) $condition['t.currency_id'] = $currencyId;
        
        if(is_numeric($status) && in_array($status, [-1, 0, 1, 2])) $condition['t.status'] = $status;
        
        //條件篩選、字段選擇、排序
        $model->where($condition)->field($field)->order($orderBy);
        
        //獲取總條數
        $total = (clone $model)->count();
        
        $result['total'] = $total;
        
        if($total > 0) {
            $data = $model->limit($limit)->page($page)->select();
            $result['list'] = $data;
            $result['page'] = $this->setPager($page, ceil($total / $limit));
        }
        
        return $this->setMsg(0, $result);
    }
    
    /**
     * 获取充币记录
     * @author Jungle 2019年3月13日 下午5:07:21
     * @param int $page
     * @param int $limit
     * @param int $userId
     * @param int $currencyId
     * @param int $status
     * @return array
     */
    protected function getChongbiList($page, $limit, $userId, $currencyId, $status)
    {
        $result = [
            'total' => 0, 'list' => [], 'page' => 0
        ];
        
        $page = $page > 0 ? intval($page) : 1;
        
        $limit = $limit > 0 ? intval($limit) : 10;
        
        $currencyId = !empty($currencyId) ? intval($currencyId) : 0;
        
        $c = M('Currency')->getTableName();
        
        //关联
        $join = $c.' AS c ON c.id = cb.currency_id';
        
        //排序
        $orderBy = ['cb.id DESC'];
        
        //字段选择
        $field = ['cb.*', 'c.currency_name'];
        
        $model = D('Chongbi')->alias('cb')->join($join, 'LEFT');
        
        //筛选条件
        $condition = ['cb.uid' => $userId];
        
        if(!empty($currencyId)) $condition['cb.currency_id'] = $currencyId;

        if(is_numeric($status) && in_array($status, [1, 2, 3])) $condition['cb.status'] = $status;
        
        //條件篩選、字段選擇、排序
        $model->where($condition)->field($field)->order($orderBy);
        
        //獲取總條數
        $total = (clone $model)->count();
        
        $result['total'] = $total;
        
        if($total > 0) {
            $data = $model->limit($limit)->page($page)->select();
            $result['list'] = $data;
            $result['page'] = $this->setPager($page, ceil($total / $limit));
        }
        
        return $this->setMsg(0, $result);
    }
    
    /**
     * 获取用户余额
     * @author Jungle 2019年3月13日 下午12:18:39
     * @param int $userId
     * @param int $currencyId
     * @return array
     */
    protected function getUserBalance($userId, $currencyId = null)
    {
        $map = [
            'uc.uid' => $userId,
            'uc.num' => ['gt', 0]
        ];
        
        if ($currencyId) $map['uc.currency_id'] = $currencyId;
        
        $result = M('UserCurrency')->alias('uc')->join('__CURRENCY__ AS c ON c.id = uc.currency_id', 'LEFT')
        ->where($map)->order(Wallent::getOrderBy('c'))
        ->field([
            'c.id AS currency_id', 
            'c.currency_name, uc.num'
        ])->select();
        
        return $this->setMsg(0, $result);
    }
    
    /**
     * 充币币种列表
     * @author Jungle 2019年3月5日 下午12:13:16
     * @param int $clientType 客户端类型（1：web端，2：移动端）
     * @return array
     */
    protected function getChongbiCurrency($clientType = 1)
    {
        $data = M('Currency')->where([
            'close_recharge' => CurrencyModel::CLOSE_RECHARGE_0
        ])->order(self::getOrderBy())->select();
        
        //web端处理方法
        $webFunc = function($_data){
            $field = ['id', 'currency_name', 'maintain_currency'];
            foreach ($_data as $k => $v){
                foreach ($_data[$k] as $k1 => $v1) {
                    if(!in_array($k1, $field)) unset($_data[$k][$k1]);
                }
            }
            return $this->setMsg(0, $_data);
        };
        
        //安卓/IOS端处理方法
        $mobileFunc = function($_data){
            
            $field = ['currency_id', 'currency_name', 'enabled', 'note'];
            
            foreach($_data as $k => $v){
                
                $_data[$k]['currency_id'] = $_data[$k]['id'];
                
                $_data[$k]['note'] = $this->getChargeNote($_data[$k]['id'], $_data[$k]['currency_name']);
                
                //是否维护
                $isMaintain = $_data[$k]['maintain_currency'] == CurrencyModel::MAINTAIN_CURRENCY_1;
                
                //若其中一个状态生效，则返回为0（失效），否则为1（正常）
                $_data[$k]['enabled'] = $isMaintain ? 0 : 1;
                
                foreach ($_data[$k] as $k1 => $v1) {
                    if(!in_array($k1, $field)) unset($_data[$k][$k1]);
                }
                
            }
            
            //判断是否所有币种都正在维护
            $count = 0;
            foreach ($_data as $k => $v){
                if($v['enabled'] == 0) $count++;
            }
            if($count == count($_data)) return $this->setMsg(30048);
            
            return $this->setMsg(0, ['data' => $_data, 'total' => count($_data)]);
        };
        
        $result = [];
        switch($clientType){
            case 1 : $result = $webFunc($data); break;
            case 2 : $result = $mobileFunc($data); break;
        }
        
        return $this->setMsg(0, $result);
    }
    
    /**
     * 获取充提币记录的币种筛选列表
     * @author Jungle 2019年9月17日 上午11:50:41
     * @param unknown $userId
     * @param unknown $type 类型（0：充币，1：提币）
     * @return array
     */
    protected function getAllCurrencyForList($userId, $type)
    {
        
        $model = M('Currency')->field([
            'id AS currency_id', 'currency_name', 'maintain_currency'
        ]);
        
        //根据充提币类型返回币种
        $model->where($type == 1 ? [
            'close_carry' => CurrencyModel::CLOSE_CARRY_0
        ] : [
            'close_recharge' => CurrencyModel::CLOSE_RECHARGE_0
        ]);
        
        $data = $model->order(Wallent::getOrderBy())->select();
        
        foreach($data as $k => $v){
            //是否维护
            $isMaintain = $data[$k]['maintain_currency'] == CurrencyModel::MAINTAIN_CURRENCY_1;
            
            //若其中一个状态生效，则返回为0（失效），否则为1（正常）
            $data[$k]['enabled'] = $isMaintain ? 0 : 1;
            
            unset($data[$k]['maintain_currency']);
        }
        
        //判断是否所有币种都正在维护
//         $count = 0;
//         foreach ($data as $k => $v){
//             if($v['enabled'] == 0) $count++;
//         }
//         if($count == count($data)) return 30048;
        
        return $this->setMsg(0, ['data' => $data, 'total' => count($data)]);
    }
    
    /**
     * 获取充币地址
     * @author Jungle 2019年3月6日 下午12:28:45
     * @param int $userId
     * @param int $currencyId
     * @return array
     */
    protected function getMyChargePackUrl($userId, $currencyId)
    {
        //判断币种是否正在维护
        if(!CurrencyModel::getIsNormal($currencyId, 1)) return $this->setMsg(30044);
        
        $result = [
            'cashaddr' => '', 'legacy' => '', 'eos_memo' => ''
        ];
        
        //判断是否bch或者bsv币种
        if($this->getIsBch($currencyId) || $this->getIsBsv($currencyId)){
            $perfix = C('BCH_PREFIX_STR');
            
            $field = $this->getAddrField($currencyId);
            $tableName = $this->getTableName($userId, $currencyId);
            $childSql = M($tableName)->alias('b')->where('uc.my_charge_pack_url = b.'.$field)->field('old_cz_url')->select(false);
            
            $data = M('UserCurrency')->alias('uc')->where([
                'uc.uid' => $userId,
                'uc.currency_id' => $currencyId
            ])->field([
                'uc.my_charge_pack_url AS cashaddr',
                '('.$childSql.') AS legacy'
            ])->find();
            
            if(empty($data) || !$data['cashaddr']) $data['cashaddr'] = '';
            
            $data['cashaddr'] = $data['cashaddr'] != '' ? $perfix.$data['cashaddr'] : $data['cashaddr'];
            
            $result = array_merge($result, $data);
            
            return $this->setMsg(0, $result);
        }
        
        //其他币种
        $data = M('UserCurrency')->where([
            'uid' => $userId,
            'currency_id' => $currencyId
        ])->field([
            'my_charge_pack_url AS cashaddr'
        ])->find();
        
        if(empty($data) || !$data['cashaddr']) $data['cashaddr'] = '';
        
        $result = array_merge($result, $data);
        
        //若为eos，需加上memo
        if($this->getIsEos($currencyId)) {
            $result['eos_memo'] = $result['cashaddr'];
            $result['cashaddr'] = C('EOS_FIX_URL');
        }
        
        return $this->setMsg(0, $result);
    }
    
    /**
     * 提币币种列表
     * @author Jungle 2019年3月13日 下午12:26:07
     * @param int $userId
     * @return array
     */
    protected function getTibiCurrency($userId)
    {
        
        $level = M('User')->where(['uid' => $userId])->getField('level');
        
//         $where['uc.num'] = ['gt', 0];
        $where['c.close_carry'] = CurrencyModel::CLOSE_CARRY_0;
        $where['_logic'] = 'or';
        
        $map['_complex'] = $where;
        $map['uc.uid'] = $userId;
        $currency_info = M('UserCurrency')->alias('uc')
        ->join('__CURRENCY__ AS c ON c.id = uc.currency_id', 'left')
        ->where($map/* [
            'uc.num' => ['gt', 0],
            'c.close_carry' => CurrencyModel::CLOSE_CARRY_0,
            'uc.uid' => $userId
        ] */)->field([
            'c.id AS currency_id', 'c.currency_name',
            'uc.my_mention_pack_url1', 'uc.my_mention_pack_url2',
            'uc.my_mention_pack_url3', 'maintain_currency'
        ])->order(Wallent::getOrderBy('c'))->select();
        
        $currencyInfo = [];
        foreach ($currency_info as $value){
            
            //获取绑定地址
            $currency_id = $value['currency_id'];
            
            //旷工费
            $coinFee = M('LevelConfig')->where([
                'vip_level' => $level,
                'currency' => $currency_id
            ])->getField('coin_fee');
            $temp['max_tibi_number'] = getTibiMaxNum($userId, $level, $currency_id);
            
            $temp = [
                'currency_id' => $currency_id,
                'currency_name' => $value['currency_name'],
                'url_list' => [],
                'coin_fee' => $coinFee,
                'max_tibi_number' => getTibiMaxNum($userId, $level, $currency_id)
            ];
            
            for($i = 1; $i <= 3; $i++){
                $url = $value['my_mention_pack_url'.$i];
                if($url != '') array_push($temp['url_list'], $url);
            }
            
            if(empty($temp['url_list'])) unset($temp['url_list']);
            
            //判断是否维护
            $condition = ($value['maintain_currency'] == CurrencyModel::MAINTAIN_CURRENCY_1);
            $temp['enabled'] = intval(!$condition);
            
            //单笔提币最少数量
            $number = Tibi::getTibiNumConfigVal($userId, $currency_id);
            $temp['min_number'] = $number['min_num'];
            
            array_push($currencyInfo, $temp);
        }
        
        //判断是否所有币种都正在维护
        $count = 0;
        foreach ($currencyInfo as $k => $v){
            if($v['enabled'] == 0) $count++;
        }
        if($count == count($currencyInfo)) return $this->setMsg(30048);
        
        if(!empty($currencyInfo)) return $this->setMsg(0, ['currency_info' => $currencyInfo]);
        
        return $this->setMsg(30020);
    }
    
    /**
     * 判断是否第一次绑定充提币地址
     * @author Jungle 2019年3月5日 上午11:57:25
     * @param int $userId
     * @param number $type 类型（0：充币，1：提币）
     * @return boolean
     */
    public function getIsFirstBinding($userId, $type = 0)
    {
        $table = getTbl('UserScoreLog', $userId);
        $status = $type == 0 ? 3 : 4;
        $bindTimes = M($table)->where(['status' => $status, 'uid' => $userId])->count();
        return $bindTimes <= 0 ? true : false;
    }
    
    /**
     * 判断是否bch币种
     * @author Jungle 2019年3月6日 下午3:14:28
     * @param int $currencyId
     * @return boolean
     */
    public function getIsBch($currencyId)
    {
        return $currencyId == $this->currencyIds['BCH'];
    }
    
    /**
     * 判断是否bsv币种
     * @author Jungle 2019年3月6日 下午3:14:28
     * @param int $currencyId
     * @return boolean
     */
    public function getIsBsv($currencyId)
    {
        return $currencyId == $this->currencyIds['BSV'];
    }
    
    /**
     * 判断是否eos币种
     * @author Jungle 2019年3月6日 下午3:14:28
     * @param int $currencyId
     * @return boolean
     */
    public function getIsEos($currencyId)
    {
        return $currencyId == $this->currencyIds['EOS'];
    }
    
    /**
     * 获取币种排序条件
     * @author Jungle 2019年3月11日 上午11:48:20
     * @param string $alias
     * @return string
     */
    public static function getOrderBy($alias = null)
    {
        return ($alias ? $alias.'.' : '').'id ASC';
    }

    /**
     * 获取充币页面的注意事项
     * @author Jungle 2019年3月11日 下午4:43:39
     * @param int $currencyId
     * @param string $currencyName
     * @return array
     */
    public function getChargeNote($currencyId, $currencyName)
    {
        $noteArr = [
            sprintf(L('_APP_WALLENT_NOTE_1_'), $currencyName),
            sprintf(L('_APP_WALLENT_NOTE_2_'), $currencyName, $currencyName)
        ];
        switch ($currencyId) {
            case $this->currencyIds['EOS']:
                $noteArr = array_merge($noteArr, [
                    L('_QJEOSCSZXFCZDZMEMO_'),
                    L('_CZEOSTSXYYGMEMO_')
                ]);
                break;
            case $this->currencyIds['BCH']:
                $noteArr = array_merge($noteArr, [
                    L('_PTJWNDCZDZTGCLGS_')
                ]);
                break;
            case $this->currencyIds['BSV']:
                $noteArr = array_merge($noteArr, [
                    L('_PTJWNDCZDZTGCLGS_')
                ]);
                break;
        }
        foreach ($noteArr as $k => $v) $noteArr[$k] = ($k+1).'.'.$noteArr[$k];
        return $noteArr;
    }
    
    /**
     * 获取表名 根据币种ID取各币种表的模
     *
     * @author Jungle 2019年3月1日 下午5:43:51
     * @param int $uid            
     * @param int $currencyId            
     * @return string
     */
    public function getTableName($userId, $currencyId)
    {
        $name = M('Currency')->where([
            'id' => $currencyId
        ])->getField('currency_name');
        switch ($currencyId) {
            case $this->currencyIds['BTC']:
                $tableName = ucfirst(strtolower($name)) .'Url'. $userId % 2;
                break;
            case $this->currencyIds['EOS']:
                $tableName = ucfirst(strtolower($name)) . 'Memo';
                break;
            default:
                $tableName = ucfirst(strtolower($name)) . 'Url';
                break;
        }
        return $tableName;
    }

    /**
     * 获取地址池字url
     *
     * @author Jungle 2019年3月1日 下午5:44:29
     * @param int $currencyId            
     * @return string
     */
    public function getAddrField($currencyId)
    {
        switch ($currencyId) {
            case $this->currencyIds['EOS']:
                $field = 'memo';
                break;
            default:
                $field = 'cz_url';
                break;
        }
        return $field;
    }

    /**
     * 獲取完整幣種url地址 bch,bsv 特殊前綴
     * 
     * @author Jungle 2019年3月1日 下午5:45:27
     * @param int $currencyId            
     * @param string $addr            
     * @return string|unknown
     */
    public function getBchBsvAddress($currencyId, $addr)
    {
        $condition = in_array($currencyId, C('BCH_CURRENCY_IDS'));
        return $condition ? C('BCH_PREFIX_STR') . $addr : $addr;
    }
    
    /**
     * 
     * @author 建强 2019年3月5日 上午11:54:08
     * @method bch bsv地址逆向方法进行  前缀去掉
     * @param  $currencyId int 
     * @param  $addr string 
     * @return string 
     */
    public function getBchNoPrefixAddress($currencyId, $addr)
    {
        $condition = in_array($currencyId, C('BCH_CURRENCY_IDS'));
        return $condition ? str_replace(C('BCH_PREFIX_STR'), '', $addr) : $addr;
    }
    
    /**
     * 设置返回信息
     * @author Jungle 2019年3月13日 上午10:30:39
     * @param number $code
     * @param array $data
     * @return array
     */
    private function setMsg($code = 0, $data = [], $msg = null)
    {
        if(!$msg){
            $msg = C('APP_CODE.'.$code) ? L(C('APP_CODE.'.$code)) : 'success';
        }
        return ['msg' => $msg, 'code' => $code, 'data' => $data];
    }
    
    /**
     * 页码信息
     * @author Jungle 2019年3月13日 下午4:01:25
     * @param int $curr_page
     * @param int $total_page
     * @return array
     */
    private function setPager($curr_page, $total_page)
    {
        $pager = [
            'current_page' => $curr_page <= 0 ? 1 : $curr_page,
            'last_page'    => $curr_page - 1 <=0 ? '' : $curr_page - 1,
            'next_page'    => ($curr_page + 1 > $total_page) ? '' : $curr_page + 1,
            'total_pages'  => $total_page,
        ];
        return $pager;
    }
    
}