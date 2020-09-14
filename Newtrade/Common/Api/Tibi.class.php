<?php
/**
 * @desc 提币配置
 * @author 建强 2019年2月28日
 */
namespace Common\Api;
class Tibi{
    /**
     * @author 建强 2019年2月28日 下午3:51:54
     * @method 提币根据用户等级 币种不同进行配置
     * @param  $uid int 用户id
     * @param  $level int 用户等级
     * @param  $currency_id int 币种id
     * @param  $num  int  提币数量
     * @return array  
    */
    public static function CheckTibiConfigNum($uid,$level,$currency_id,$num){
        try{
            $ret = [
                'code'=>30006,
                'msg'=>L('_QBMKWHZZBYXCZ_'),
            ];
            $where=[
                'vip_level'=>$level,
                'currency_id'=>$currency_id,
            ];
            $config = M('LevelConfig')->where($where)->find();
            if(empty($config)) return $ret;
            //小于最小提币数量
            if($num < $config['min_tibi_amount']){
                $ret['code']=30003;
                $ret['msg']=L('_DBTBZXSL_'). $config['min_tibi_amount'];
                return $ret;
            }
            $start_time = strtotime(date('Y-m-d',time()).' 0:0:0');
            $end_time   = strtotime(date('Y-m-d',time()).' 23:59:59');
            $where_sum= [
                'uid'=>$uid,
                'currency_id'=>$currency_id,
                'add_time'=>['between',[$start_time,$end_time]]
            ];
            //当天已经提币数量
            $today_sum = M('Tibi')->where($where_sum)->sum('num');
            $day_num =$config['day_max_tibi_amount'];
            //用户剩余数量
            $user_num  =  M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->getField('num');
            $leave_num = ($day_num-$today_sum) > $user_num ? $user_num : ($day_num-$today_sum);
            $leave_num = number_format(floatval($leave_num),8,'.','');
            //不能大于剩余可提币的数量
            if($num > $leave_num){
               $ret['code']=30005;
               $ret['msg']=L('_ZCSLCGXZ_');
               return $ret;
            }
            //正常提币
            $ret['msg'] ='success';
            $ret['code']=200;
            return $ret;
        }catch(\Think\Exception $e){
            $ret['code'] = 30007;
            return $ret;
        }
    }
    /**
     * @author 建强 2019年3月7日 下午3:26:37
     * @method 提币数量的限制 
     * @return array  min 单笔最小的提币数量  
     */
    public static function getTibiNumConfigVal($uid,$currency_id){
        try{
            $num_val = [
                'min_num'=>0,
                'num'    =>0,
            ];
            $level = M('User')->where(['uid'=>$uid])->getField('level');
            $where = ['vip_level'=>$level,'currency_id'=>$currency_id];
            $amount_config = M('LevelConfig')->where($where)->find();
            if(empty($amount_config)) return $num_val;
            $where_sum =[
                'uid'=>$uid,'currency_id'=>$currency_id,
                'add_time'=>['between',
                    [strtotime(date('Y-m-d',time()).' 0:0:0'),
                     strtotime(date('Y-m-d',time()).' 23:59:59')]
                ],
            ];
            $day_num   = $amount_config['day_max_tibi_amount'];
            $min_num   = $amount_config['min_tibi_amount'];
            $today_sum = M('Tibi')->where($where_sum)->sum('num');
            $user_num  = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>$currency_id])->getField('num');
            //判断返回值
            $last_num  = ($day_num-$today_sum) > $user_num ? $user_num : ($day_num-$today_sum);
            $num_val['num']     =  number_format(floatval($last_num),8,'.','');
            $num_val['min_num'] =  $min_num;
            //如果最小的还要大于可提的币数量
            if($min_num >= $num_val['num']){
                $num_val['min_num']=$num_val['num'];
            }
            return $num_val;
        }catch(\Exception $e){
            return $num_val;
        }
    }
    /**
     * @author 建强 2019年3月7日 下午5:24:48
     * @method 数据返回
     * @return array 
    */
    public static function retMsg($code= 200,$msg='success',$data=''){
        return  [
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data,
        ];
    }
    /**
     * @author 建强 2019年3月7日 下午5:25:08
     * @method 提币地址绑定验证  分规则
     * @return array 
     */
    public static function checkAddress($curr_id,$address,$memo =''){
        $len_addr = trim(strlen($address));
        $len_memo = strlen($memo);
        $bool     = $curr_id == C('EOS_ID');
        //eos币种判断
        if($bool) return self::EosAddress($address,$memo,$len_addr,$len_memo);
        //其他币种判断  
        if($len_addr<15 || $len_addr>80){
            return  self::retMsg(50005,L('_DZCDBHG_'));
        }
        if(!regex($address,'addurl')){
            return  self::retMsg(50006,L('_BDDZGSCW_'));
        }
        return self::retMsg();
    }
    /**
     * @author 建强 2019年3月7日 下午5:51:14
     * @method EOS地址判断  
     * @param  钱包地址 
     * @param  memo钱包地址
     */
    public static function EosAddress($address,$memo,$len_addr,$len_memo){
        if($len_addr!=12){
            return  self::retMsg(50001,L('_DZCDBHG_'));
        }
        //([a-z])([1-5]*$)/'  eos 钱包地址部分
        if(empty(regex($address, 'address_eos'))){
            return  self::retMsg(50002,L('_DZGSCW_'));
        }
        if($len_memo<1 || $len_memo>128){
            return  self::retMsg(50003,L('_DZGSCW_'));
        }
        //[a-zA-Z0-9]$/ memo
        if(empty(regex($memo, 'address_memo_url'))){
            return  self::retMsg(50004,'memo'.L('_DZGSCW_'));
        }
        return self::retMsg();
    }
}