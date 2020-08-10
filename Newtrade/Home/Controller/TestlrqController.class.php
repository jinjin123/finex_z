<?php
namespace Home\Controller;
use Think\Controller;
use Home\Tools\SceneCode;
use Home\Sms\Yunclode;
use Common\Api\RedisCluster;
use Common\Api\RedisClusterTest;
use Common\Api\RedisIndex;
use Common\Api\RedisIndexTest;
// use Home\Tools\HttpCurl;
// use Org\Net\IpLocation;
// use Org\Net\Ip;
// use Think\Exception;
// use Think\Log;
// use Home\Logics\LoginCheckController;
// use Home\Logics\ChartData;
// use Home\Logics\PublicFunctionController;
// use Common\Api\redisKeyNameLibrary;
// use Common\Api\MobileReg;
// use Common\Api\Redis;

use Think\Cache\Driver\Redis;
use Think\Cache\Driver\Redisd;

/**前台测试类文件  继承框架基类
 * @author 宋建强
 * @Date  2017年9月25日 16:52
 */
class TestlrqController extends Controller {


    public function wstest(){
        $host = '192.168.2.228';
        $port = '9502';

        $clientObj =new \swoole_client(SWOOLE_SOCK_TCP);
        $connect = $clientObj->connect($host, $port,1);
        if(!$connect) {
             echo "连接失败code:".socket_strerror($clientObj->errCode).PHP_EOL;
            // echo "连接失败22".PHP_EOL;
            return false;
        }

        $msg = ["method"=> "join", "uid"=> '1555647215000', "hobby"=> 1, "service_name"=>"pcCoinInfoList"];

        // $msg =  [
        //     'method' => 'push',
        //     'service_name' => 'pcCoinInfoList',
        //     'data'   => [
        //         'time'      => time(),
        //         'message'   => 'PC端价格信息推送到服务端',
        //         'push_data' => []
        //     ],
        // ];
        $message = json_encode($msg);
        $sendRes = $clientObj->send($message);
        if(!$sendRes){
            echo "发送消息失败".PHP_EOL;
            return false;
        }

        echo "<pre>";
        var_dump($connect);
        var_dump($sendRes);

        // 接受来自server 的数据
        $result = $clientObj->recv();
        $clientObj->close();

        echo "222<Pre>";
        var_dump($result);
    }


    public function genUserBankSql(){
        $startTime = microtime(true);
        $fileName = "E:\\user2BW.sql";
        $handle     = fopen($fileName, "w+");
        for ($i=1; $i <= 2000000; $i++) { 

            $str = '';
            if ($i < 10) {
                $str = '000000';
            }elseif ($i < 100 && $i >= 10) {
                $str = '00000';
            }elseif ($i < 1000 && $i >= 100) {
                $str = '0000';
            }elseif ($i < 10000 && $i >= 1000) {
                $str = '000';
            }elseif ($i < 100000 && $i >= 10000) {
                $str = '00';
            }elseif ($i < 1000000 && $i >= 100000) {
                $str = '0';
            }else {
                $str = '';
            }
            $temp = [
                'uid' => $i,
                'username' => 'test'.$str.$i,
                'pwd' =>  '2115b0a453ce3fac0df23ad89579b39b',
                'trade_pwd' => '2115b0a453ce3fac0df23ad89579b39b',
                'is_bank' => 1,
                'status' => 1,
                'reg_time' => time(),
            ];

            $tt = implode($temp, "','");
            $sql = "insert into trade_user (uid,username,pwd,trade_pwd,is_bank,status,reg_time) values ('".$tt."');\n";
            fwrite($handle, $sql);
        }

        fclose($handle);
        echo microtime(true)-$startTime;
    }


    public function testkk(){
        // insert into trade_cc_order (id,order_num,currency_type,price,money,num,uid,om,status,add_time,type) values ('1','2018080211005599504','1','7678','767800','100','1','86','1','1533183597','1');
        $startTime = microtime(true);
        $fileName = "E:\\c2c_sell_2BW.sql";
        $handle     = fopen($fileName, "w+");
        for ($i=1; $i <= 2000000; $i++) { 
            $orderId = date('Ymd').$i.substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 10);//买家显示订单号
            $temp = [
                'id' => $i,
                'order_num' => $orderId,
                'currency_type' => '1',
                'price' => '4200',
                'money' => '4200',
                'num' => '1',
                'uid' => $i,
                'om'=>'86',
                'status' => 1,
                'add_time' => time(),
                'type' => 2,
                'leave_num' => 100,
            ];

            $tt = implode($temp, "','");
            $sql = "insert into trade_cc_order (id,order_num,currency_type,price,money,num,uid,om,status,add_time,type,leave_num) values ('".$tt."');\n";

            fwrite($handle, $sql);
        }

        fclose($handle);
        echo microtime(true)-$startTime;
    }

     public function addCurrency(){
        $startTime = microtime(true);
        $fileName = "E:\\userCurrencyLTC.sql";
        $handle     = fopen($fileName, "w+");
        for ($i=1; $i <= 2000000; $i++) { 
            $temp = [
                // 'id' => $i,
                'uid' => $i,
                'currency_id' => '2',
                'num' => '1000000',
            ];

            $tt = implode($temp, "','");
            $sql = "insert into trade_user_currency (uid,currency_id,num) values ('".$tt."');\n";
            fwrite($handle, $sql);
        }

        fclose($handle);
        echo microtime(true)-$startTime;
    }

    public function redistest(){
        $redis = RedisCluster::getInstance();

        var_dump($redis);
        $m = $redis->set("test2333444", 10, 50);
        // $n = $redis->ttl("test2333444");
        var_dump($redis);
        var_dump($m);
        // var_dump($n);

        die;
        $a = $redis->zRemRangeByScore('test', 0, time()-1);
        $redis->zadd('test', microtime(true), '1');
        $redis->zadd('test', microtime(true), '2');
        $b = $redis->zRange('test', 0, -1);
        var_dump($b);
        var_dump($a);
    }


    public function index(){
    	echo phpinfo();
    }


    public function test($a=[]){

        // $a = new Redis();
        // $b = new Redis();

        // $b = Redisd::getInstance();
        // $a = Redisd::getInstance();

        // echo "<pre>";
        // var_dump($a, $b);die;

	// $redisObj = new RedisCluster();
 //        $redis    = $redisObj->getInstance();
 //        $redis2    = $redisObj->getInstance();

 //        var_dump($redis2);
 //        var_dump($redis);die;


        $redisObj = RedisClusterTest::getInstance();
        

        // $redisObj2->setex('tests1111', 30, 1);
        // $a = $redisObj2->get('tests1111');

        $a = RedisIndexTest::getInstance();

        $redisObj2 = RedisClusterTest::getInstance();
        $b = RedisIndexTest::getInstance();

        echo "<pre>";
        var_dump($redisObj);
        var_dump($a);
        var_dump($redisObj2);
        var_dump($b);die;
        // $userId = 99999;
        // $expreTime = 99999;

        // $redis->setex(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId, $expreTime, redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM);
        // $redis->setex(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId, $expreTime, redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM);


        // $passMissRes  = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        // $tokenMissRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);

        // echo "<Pre>";
        // var_dump($passMissRes);
        // var_dump($tokenMissRes);

        // $a = $redis->del(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        // $b = $redis->del(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);

        // var_dump($a);
        // var_dump($b);

        // $passMissRes  = $redis->get(redisKeyNameLibrary::PC_LOGIN_PASS_MISS_NUM.$userId);
        // $tokenMissRes = $redis->get(redisKeyNameLibrary::PC_LOGIN_TOKEN_MISS_NUM.$userId);

        // var_dump($passMissRes);
        // var_dump($tokenMissRes);
    }
}