<?php
namespace App\Service\V100;
use App\Service\ServiceBase;
/**
 * 測試模塊
 * Class TestService
 * @package App\Service\V110
 * 劉富囯
 * 2017-10-19
 */
class TestService extends ServiceBase{

    //构造方法
    public function  __construct()
    {
        parent::__construct();
    }

    /*
     * 用户提币方法
     */
    public function tiBi(){
        $data = $this->getData();
//        $uid = $this->getUserId();
        $uid = 181;
        $number = intval($data['number']);
        $trade_pwd = strval($data['trade_pwd']);
        $phoneCode = strval($data['phoneCode']);
        $currency_id = $data['currency_id'];
        $url_index = $data['addr_index'];  //用的哪一个钱包地址
        $userinfo = M('User')->where(['uid'=>$uid])->find();

        if( empty($number) || $number == 0 ){
            //提币数量不能为空
        }
        if( empty($trade_pwd) || $trade_pwd == '' ){

        }
        if( empty($phoneCode) || $phoneCode == '' ){

        }

        if( passwordEncryption($trade_pwd) != $userinfo['trade_pwd'] ){
            //资金密码不对
        }
//        //验证手机验证码
//        if(  $phoneCode ){
//
//        }
        $userCurrency = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->find();
        switch ($url_index){
            case 1:
                $tibi_data['url'] = $userCurrency['my_mention_pack_url1'];
                $add_time = $data['url_date1'];
                break;
            case 2:
                $tibi_data['url'] = $userCurrency['my_mention_pack_url2'];
                $add_time = $data['url_date2'];
                break;
            case 3:
                $tibi_data['url'] = $userCurrency['my_mention_pack_url3'];
                $add_time = $data['url_date3'];
                break;
            default:
                $this->ajaxReturn(['status'=>404,'msg'=>L('_FEIFAQQ_')]);
                break;
        }

        //验证地址是否在24小时之内
        if( time() - $add_time < 24*60*60 ){
            $this->ajaxReturn(['status'=>404,'msg'=>L('_GDZWCGYTBNYYTB_')]);
        }
        //验证是否超过今日提币总数
        $user_level = $userinfo['level'];
        $start_time = strtotime(date('Y-m-d',time()).' 0:0:0');
        $end_time = strtotime(date('Y-m-d',time()).' 23:59:59');

        $per_curr_tibi_num = M('Tibi')->where(['uid'=>$uid,'currency_id'=>$currency_id,['add_time'=>['between',[$start_time,$end_time]]]])->sum('num');

        //获取当前币种每天最大提取数量
        $day_max_tibi_amount = M('LevelConfig')->where(['vip_level'=>$user_level,'currency_id'=>$currency_id])->getField('day_max_tibi_amount');
        //该用户还能提取的数量
        $today_can_tibi_num = $day_max_tibi_amount - $per_curr_tibi_num;
        //获取该用户资金余额
        $fund_balance = $userCurrency['num'];
        $last_num = 0;
        if( $fund_balance > $today_can_tibi_num ){
            $last_num = $today_can_tibi_num;
        }else{
            $last_num = $fund_balance;
        }
        if( $number > $last_num ){
            //提币数量超过上限
        }

        $tibi_data['uid'] = $uid;
        $tibi_data['add_time'] = time();
        $tibi_data['num'] = $number;
        $tibi_data['status'] = 0;
        $tibi_data['currency_id'] = $currency_id;

        //根据手续费获取实际到账
        $ti_fee = M('LevelConfig')->where(['vip_level'=>$user_level,'currency_id'=>$currency_id])->getField('coin_fee');
        $actual_ti_fee = $number * $ti_fee;
        if( $actual_ti_fee < 0.00000001 ){
            $tibi_data['actual'] = $number;
            $tibi_data['fee'] = 0;
        }else{
            $tibi_data['actual'] = $number * ( 1 - $ti_fee );//计算出扣除手续费后的价格
            $tibi_data['fee'] = $number - $tibi_data['actual'];
        }
        $res = M('Tibi')->add($tibi_data);
        if( $res ){
            return ['status'=>200,'msg'=>'提币成功'];
        }
    }


}