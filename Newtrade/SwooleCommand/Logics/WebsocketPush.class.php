<?php
/**
 * User: 刘富国
 * Date: 19-2-26
 * Time: 下午2:19
 */

namespace SwooleCommand\Logics;


class WebsocketPush
{
    //最后一次错误代码
    public $errno = 0;
    //最后一次错误信息
    public $errmsg = '';


    //用户订阅相关服务，利用内存table保存
    public function subscriptionServices($server, $frame) {
        $rev_data   = json_decode($frame->data,true);
        $method     = isset($rev_data['method']) ? $rev_data['method'] : '';
        $service_name = isset($rev_data['service_name']) ? $rev_data['service_name']: '';
        $key = $frame->fd;  //使用用户的进程号进行标识
        if(empty($service_name) or empty($method) or $method=='heartbeat') {
            $server->push($key,$this->responseJson(1,$key,$rev_data));
            return;
        }
        $msg_data = $rev_data['data'];
        $msg_data['fd'] = $key;
        $server->table_obj->set($key,['fd'=>$key,'service_name'=>$service_name,'data'=>json_encode($msg_data)]);
        $server->push($key,$this->responseJson(1,"success",['method' => $method,'status' => 1]));
    }


    //广播 WebsocketMsg
    public function pushWebsocketMsg($server,$data){
        $ret_status = false;
        $service_name   = $data['service_name'];
        $method  = $data['method'];//广播方法名
        $user_data = $server->table_obj;
        foreach($user_data as $user_fd){
            if(!$this->checkFdExist($user_fd['fd'],$server))  continue;
            $server->push($user_fd['fd'], $this->responseJson(1,"success",
                [   'method' => $method,
                    'service_name' => $service_name,
                    'data' => $this->strval_array($data['data']),
                    'status' => 1]));
            $ret_status = true;
        }
        return $ret_status;
    }

    //服务端推消息给websocket
    public function pushJpush($server,$data){
        $ret_status = false;
        $service_name   = $data['service_name'];
        $user_data = $server->table_obj;
        foreach($user_data as $user_fd){
            //用户订阅了JpushWebsocket，则推送消息
            if(strstr($user_fd['service_name'],$service_name)) {
                //清除缓存已断开的连接
                if(!$this->checkFdExist($user_fd['fd'],$server)) continue;
                $tmp_data = json_decode($user_fd['data'], true);
                if($data['data']['uid'] <> $tmp_data['uid'])  continue;
                //广播
                $server->push($user_fd['fd'], $this->responseJson(1,"success",
                    [   'method' => 'push',
                        'service_name' => $service_name,
                        'data' => $this->strval_array($data['data'])
                    ]));
                $ret_status = true;
            }
        }
        return $ret_status;
    }

    //广播price
    public function pushPrice($server,$data){
        $ret_status = false;
        $service_name   = $data['service_name'];
        $user_data = $server->table_obj;
        foreach($user_data as $user_fd){
            if(strstr($user_fd['service_name'],$service_name)) {
                if(!$this->checkFdExist($user_fd['fd'],$server))  continue;
                $server->push($user_fd['fd'], $this->responseJson(1,"success",
                    [   'method' => 'push',
                        'service_name' => $service_name,
                        'data' => $this->strval_array($data['data']['push_data']),
                        'status' => 1]));
                $ret_status = true;
            }
        }
        return $ret_status;
    }

    //二维码登录
    public function pushQRLogin($server,$data){
        $ret_status = false;
        $service_name   = $data['service_name'];
        $user_data = $server->table_obj;
        foreach($user_data as $user_fd){
            if(strstr($user_fd['service_name'],$service_name)) {
                if(!$this->checkFdExist($user_fd['fd'],$server)) continue;
                $tmp_data = json_decode($user_fd['data'], true);
                if($data['data']['push_data']['php_session_id'] <> $tmp_data['session_id']
                    or  $data['data']['push_data']['login_time'] <> $tmp_data['login_time']) continue;

                $server->push($user_fd['fd'], $this->responseJson(1,"success",
                    [   'method' => 'push',
                        'service_name' => $service_name,
                        'data' => ['QRLoginSuccess' => '1'],
                        'status' => 1]));
                $ret_status = true;
            }
        }
        return $ret_status;

    }

    /**
     *  //清除缓存已断开的连接
     * @param $fd_key
     */
    public function checkFdExist($fd,$server){
        if(!$server->exist($fd)){
            $server->table_obj->del($fd);
            return false;
        }
        return true;
    }

    /**
     * 根据FD,删除对应健值
     * @param $fd_key
     */
    public function delServiceKeyByFd($fd_key,$server){
        if ($server->table_obj->exist($fd_key)) {
            $server->table_obj->del($fd_key);
        }
    }

    /**
     * 设置错误信息     *
     * @param int $errno
     * @param string $errmsg
     * @return bool
     * 刘富国
     */
    public function return_error($errno = 0, $errmsg = '' ){
        $this->errno  = $errno;
        $this->errmsg =	$errmsg;
        return false ;
    }

    //将空数组变为OBJECT
    public function strval_array($arr){
        if(is_array($arr) && !empty($arr)){
            foreach($arr as $n => $v){
                $b[$n] = $this->strval_array($v);
            }
            return $b;
        }else{
            if (is_object($arr)) return $arr;
            if (is_array($arr) && empty($arr)) return (object)array();
            if(empty($arr) and $arr<>0 and !is_array($arr)) return (object)array();
            return strval($arr);
        }
    }

    /**
     * @param number $status
     * @param string $message
     * @param array $data
     */
    public  function responseJson($status = 1, $message = '', $data = array()){
        $data = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
        return json_encode($data);
    }
}