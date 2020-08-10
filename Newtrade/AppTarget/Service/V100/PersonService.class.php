<?php
namespace AppTarget\Service\V100;
use AppTarget\Service\ServiceBase;
use Common\Api\RedisCluster;
use Common\Model\UserTokenModel;
use Common\Logic\Jpush;
use Common\Logic\Notice;
use Common\Model\UserCurrencyModel;
use Common\Model\UserBankModel;
/**
 * 个人中心
 * Class TokenService
 * @package AppTarget\Service\V110
 * 张锡文
 * 2017-10-19
 */
class PersonService extends ServiceBase{
    //构造方法
    private $jpush_log_obj;
    private $notice_obj;
    private $currency_obj;
    private $user_bank_obj;

    public function  __construct()
    {
        $this->jpush_log_obj = new Jpush();
		$this->notice_obj = new Notice();
		$this->currency_obj =  new UserCurrencyModel();
        $this->user_bank_obj = new UserBankModel;
    }
    /**
     * 财务总账
     * author 张锡文
     * time 2017年11月27日10:47:33
     */
    public function getUserFinance(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $page = intval($data['page']);
        $limit = intval($data['limit']);
        $page = $page <= 0 ? 1 : $page;
        $limit = $limit <=0 ? 10 : $limit;
        $where['uid'] = $uid;
        $finance_type = $data['finance_type'];
        if($data['currency_id']){
            $where['currency_id'] = intval($data['currency_id']);
        }
        if(!empty($finance_type) && $finance_type != -1){
            $where['finance_type'] = $finance_type;
            if($finance_type==5){ // p2p
                $where['finance_type'] = array('in',array(5,6,7,8,9,14,15,17,18));
            } elseif ($finance_type==6){ // c2c
                $where['finance_type'] = array('in',array(19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36));
            }elseif ($finance_type==7){ // 币币交易
                $where['finance_type'] = array('in',array(10,11,12,13,37,16));
            }
        }
        $ret_list = $this->currency_obj->getUserFinance($uid,$where,$page,$limit);
        $total = $ret_list['total']*1;
        if(empty($ret_list)){
            $ret_list['list'] = [];
        }

        $ret = array(
            'total'  => $total,
            'list'   => $ret_list['list'],
            'pager'  => $this->_pager($page, ceil($total/$limit)),
        );

        return $ret;
    }

    /**
     * 财务总账类型
     */
    public function getUserFinanceType(){
        $currency = M('Currency')->field('id as currency_id,currency_name')->select();
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $tbl = getTbl('user_finance',$uid,4);
        $crrrencyData = M($tbl)->where(array('uid'=>$uid))->field('finance_type')->group('finance_type')->select();
        $newCurrencyData = null;
        foreach($crrrencyData as $data){
            $newCurrencyData[] = $data['finance_type'];
        }
        $newfinanceType = null;
        $financeType = getFinanceTypeList();

        foreach($financeType as $key=>$data){
            if(!in_array($data['id'],$newCurrencyData)){
                unset($financeType[$key]);
            }
        }
        $crrrencyData = M($tbl)->where(array('uid'=>$uid))->field('currency_id')->group('currency_id')->select();
        $newCurrencyData = null;
        foreach($crrrencyData as $data){
            $newCurrencyData[] = $data['currency_id'];
        }
        $new_currency = null;
        /*foreach($currency as $k=>$data){
            if(!in_array($data['currency_id'],$newCurrencyData)){
                unset($currency[$k]);
            }
        }
		*/
        if(empty($financeType)){
            $financeType = [];
        }
        else{
            $financeType = array_values($financeType);
        }
        if(empty($currency)){
            $currency = [];
        }
        else{
            $currency = array_values($currency);
        }
        return [
          'finance_type'=>$financeType,
            'currency_type'=>$currency
        ];
    }

    /**
     * 获取国家下拉列表
     */
    public function getCountryDropDown(){
        return array('list'=>array(['om'=>'+86','country'=>L('_ZHONGGUO_')],
                    ['om'=>'+852','country'=>L('_ZGXG_')],
                 //   ['om'=>'+886','country'=>L('_ZGTW_')]
        ));
    }

    /**
     * 获取用户银行
     */
    public function getUserBank(){
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data = $this->getData();
        $om = $data['om'];
        $bankList = $this->user_bank_obj->getUserBank($uid,$om);
        if(empty($bankList))
            $bankList= (object)array();
        else{
            foreach($bankList as $key=>$bank){
                $bankList[$key]['bank_name'] = formatBankType($bank['bank_list_id']);
                $bankList[$key]['bank_num'] = '***************'.substr($bank['bank_num'],-4);
                $bankList[$key]['bank_logo'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$bank['bank_logo'];
                $bankList[$key]['bank_img'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$bank['bank_img'];
            }
        }
        return $bankList;
    }

    /**
     * 银行卡设为默认银行
     */
    public function setDefaultCard(){
        $data = $this->getData();
        $uid = $this->getUserId();
        if($uid < 1) return 9998;
        $data['id'] = intval($data['id']);
        if(empty($data['id'])) return 8000;
        $bank = $this->user_bank_obj->getBankId($data['id']);
        if(empty($bank)) return 20003;
        $bankDetail = M('BankList')->where(array('id'=>$bank['bank_list_id']))->find();
        $newbankDetail = M('BankList')->where(array('country_code'=>$bankDetail['country_code']))->field('id')->select();
        $bankId = null;
        if(empty($newbankDetail)) return 20003;
        foreach($newbankDetail as $val){
            $bankId .= $val['id'].',';
        }
        if($bankId) {
            $bankId = substr($bankId, 0, -1);
        } else{
            return 9999;
        }
        if($bankId){
            $this->user_bank_obj->setUnDefaultForBankId($uid,$bankId);

            $res = $this->user_bank_obj->setDefaultForId($data['id']);
            if(!$res) return 9999;
            return ['status'=>1];
        } else{
            return 9999;
        }

    }

    /**
     * 检测是否通过实名认证
     * author 张锡文
     */
    public function checkIsUserReal(){
        $uid = $this->getUserId();
		 if($uid < 1) return 9998;
        $userReal = M('UserReal')->where(['uid'=>$uid])->field('status')->find();
        if($userReal['status']==1){
            return ['is_real'=>1];
        }else{
            return ['is_real'=>0];
        }
    }
}