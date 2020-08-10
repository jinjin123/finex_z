<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 银行卡
 * zhanghanwen
 * 2017-11-07
 * Class PushUserModel
 * @package Common\Model
 */

class UserBankModel extends BaseModel{
    public function  __construct(){
        parent::__construct();
    }

    public function getUserBank( $uid,$om )
    {
        $bank_list = M('BankList')->alias('a')->field('a.country_code,a.bank_name,s.bank_num,s.bank_address,s.id,s.bank_list_id,s.default_status,a.bank_logo,a.bank_img')
            ->join("__USER_BANK__ as s ON a.id = s.bank_list_id ")
            ->where(array('a.country_code'=>$om,'s.uid'=>$uid,'s.status'=>1))
            ->select();
        return $bank_list;
    }

    public function getBankId($id){
        return M('UserBank')->field('bank_list_id')->where(array('id'=>$id))->find();
    }

    public function setUnDefaultForBankId($uid,$bank_id){
        M('UserBank')->where(array('bank_list_id'=>array('in',$bank_id),'uid'=>$uid))->save(array('default_status'=>0));
    }

    public function setDefaultForId($id){
        return M('UserBank')->where(array('id'=>$id))->save(array('default_status'=>1));
    }

    /**
     * 获取用户银行列表
     * 刘富国
     * 20180228
     */
    public function getUserBankByUserBankId($user_bank_id_arr){
        $where_arr['s.id'] = array('in',$user_bank_id_arr);
        $where_arr['s.status'] = 1;
        $bankList = M('BankList')->alias('a')
            ->field('a.country_code,a.bank_name,s.bank_num,s.bank_address,
                        s.id as user_bank_id,s.default_status,s.bank_list_id,
                        a.bank_logo,a.bank_img,s.bank_real_name')
            ->join("__USER_BANK__ as s ON a.id = s.bank_list_id ")
            ->where($where_arr)
            ->select();
        return $bankList;
    }
}