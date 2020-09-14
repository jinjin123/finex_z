<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * JPUSH消息推送歷史記錄
 * zhanghanwen
 * 2017-11-07
 * Class PushUserModel
 * @package Common\Model
 */

class UserCurrencyModel extends BaseModel{
    public function getUserFinance( $uid,$where,$page,$limit )
    {
        $tbl = getTbl('user_finance',$uid,4);
        $count = M($tbl)->where($where)->count();//总记录数
        if(empty($count) or $count<1) return false;
        $log_list = M($tbl)->field('content,type,finance_type,money,add_time')
            ->where($where)
            ->limit($limit)
            ->page($page)
            ->order('add_time desc')
            ->select();
        if(!empty($log_list)){
            foreach ($log_list as $key =>$item){
                $log_list[$key]['content'] = formatFinanceType($item['finance_type']);
            }
        }
        return ['list'=>$log_list,'total'=>$count];
    }
    /*
     * 李江
     * 2017年12月18日11:02:21
     * UserCurrency表加减金额
     */
    public function setUserMoney($uid, $currency_id, $number,$field='num',$changeType='dec'){
        if( $changeType == 'dec' ){
            return $this->where(['uid'=>$uid,'currency_id'=>$currency_id])->setDec($field,$number);
        }else{
            return $this->where(['uid'=>$uid,'currency_id'=>$currency_id])->setInc($field,$number);
        }
    }
}