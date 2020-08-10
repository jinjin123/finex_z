<?php
/**
 * Created by PhpStorm.
 * User: 李江
 * Date: 2017/11/30
 * Time: 15:07
 * Content:验证用户信息
 */

namespace Common\Logic;
use Common\Api\RedisCluster;

class CheckUser extends BaseLogic
{

    /**
     * 利用有序集合限制并发购买订单
     * 劉富國
     * 2019-03-01
     * @param $key  eg:redisKeyNameLibrary::OFF_LINE_SELL_ORDER.$orderId;
     * @param $uid
     */
    public  function checkConcurrencyControl($key,$uid){
        if(empty($key) or empty($uid)) {
            return $this->return_error(10000,L('_QQCSCC_'));
        }
        $redis  = RedisCluster::getInstance();
        //加入有序集合，时间最早并且是自己，则有权限购买
        $redis->zRemRangeByScore($key, 0, time() - 1);//清除1秒前的集合
        $redis->expire($key,3);
        $zRangeValue = build_rand_str(32).':'.$uid;
        $mtime=explode(' ',microtime());
        $startTime=$mtime[1]+$mtime[0];
        $redis->zAdd($key,$startTime,$zRangeValue);
        $zRangeArr = $redis->zRange($key,0,-1);
        if ($zRangeArr[0] <> $zRangeValue) {
            return $this->return_error(9999,L('_CZSB_'));
        }
        return true;
    }

}