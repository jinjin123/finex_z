<?php
namespace  Common\Api;

/**
 * @author 建强  2019年2月28日 
 * @desc   bch bsv币种 地址的合法性检测
 */
class  BchAddressApi{
    /**
     * @author 建强 2019年2月28日 下午6:59:11
     * @method 检验币种配置是否正确
     * @param  幣種 currency_id int 
     * @param  提幣地址  addr string
     * @return array 
     */
    public static function checkBCHaddrByApi($currency_id=1,$addr=''){
        try{
            $bch_currency_id= C('BCH_CURRENCY_IDS');
            $addr = trim($addr);
            $ret =[
                'code'=>50006,
                'msg'=>L('_TBDZ_').L('_GSBZQ_'),
                'addr'=>$addr,
            ];
            if(!in_array($currency_id,$bch_currency_id)){
                $ret['code']=200;
                $ret['msg']='success';
                return $ret;
            }
            $url = C('BCH_CHECK_ADDR_URL').'?address='.$addr;
            $res = vget($url);
            if(empty($res)){
                $ret['code']=50006;
                return $ret;
            }
            $res =json_decode($res,true);            
            if(!is_array($res)){
                $ret['code']=50007;
                return $ret;
            }            
            $ret['code']=200;
            $ret['msg']='success';
            //返回结果地址
            if(in_array($addr, $res)){
               return $ret;
            } 
            //如果不是完整地址截取 bitcoin 地址
            $bitcoionUrl = explode(':', $res['cashaddr'])[1];
            if(trim($bitcoionUrl) == $addr){
                $ret['addr']=$res['cashaddr'];
                return $ret;
            }
            return $ret;
        }catch(\Think\Exception $e){
            $ret['code'] =50009;
            return $ret;
        }
    }
}