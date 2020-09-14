<?php
/**
 * redis集群驱动
 */

namespace Common\Api;

use Think\Cache\Driver\Redis;

class RedisCluster
{

    protected $servers = array(
//        'redis:6379',
        //'192.168.2.155:6001',
        //'192.168.2.155:6002',
        //'192.168.2.175:6003',
        //'192.168.2.175:6004',
        //'192.168.2.160:6005',
        //'192.168.2.160:6006',

        '127.0.0.1:6379',
        // '127.0.0.1:7001',
        // '127.0.0.1:7002',
        // '127.0.0.1:7003',
        // '127.0.0.1:7004',
        // '127.0.0.1:7005',
        // '127.0.0.1:7006',

//        '8.129.172.166:7001',
//        '8.129.172.166:7002',
//        '8.129.172.166:7003',
//        '8.129.172.166:7004',
//        '8.129.172.166:7005',
//        '8.129.172.166:7006',

    );


    private static $_instance = null;
    private $handler;

    protected $optionParam = array(
        'timeOut' => 8,
        'readTime' => 8,
        'persistent' => false     //是否复用链接
    );

    private function __construct($servers = array(), $optionParam = array())
    {
        if (!empty($servers) && is_array($servers)) {
            $this->servers = $servers;
        }

        if (!empty($optionParam) && is_array($optionParam)) {
            $this->optionParam = $optionParam;
        }


        if (!$this->handler) {
            $this->handler = new Redis();
//            $this->handler = new \Redis();
//            $this->handler->connect('redis', 6379);

//            $this->handler = new \RedisCluster(null, $this->servers,
//                $this->optionParam['timeOut'],
//                $this->optionParam['readTime'],
//                $this->optionParam['persistent']
//            );
//            $this->handler->setOption(
//                \RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES
//            );
        }
    }

    /**
     * 获取predis对象
     * @author 建强  2018年6月25日14:50:36
     */
    public static function getInstance($servers = array(), $optionParam = array())
    {

        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($servers = array(), $optionParam = array());
        }

        return self::$_instance->handler;
    }

    /**
     * 关闭redis
     * @author lirunqing 2019-04-09T10:42:49+0800
     */
    public function __destruct()
    {
        self::$_instance->handler->close();
    }
}
