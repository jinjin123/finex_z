<?php
//==============================================
//  公共模块助手类函数    
//==============================================


/**
 * 2038年超限问题调整
 * @param  [type] $str_time [description]
 * @return [type]           [description]
 * @author liruqning 2019-06-18T16:09:55+0800
 */
if (!function_exists('newStrToTime')) {
    function newStrToTime($str_time)
    {
        $result = strtotime($str_time);
        if (empty($result)) {
            $date = new \DateTime($str_time);
            $result = $date->format('U');
        }
        return $result;
    }
}

/**
 * 富国
 * 二维数组排序
 * 20180611
 * 例子：
 * $data[] = array('volume' => 67, 'edition' => 2);
 * $data[] = array('volume' => 86, 'edition' => 1);
 * $data[] = array('volume' => 85, 'edition' => 6);
 * $sorted = array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
 */
if (!function_exists('array_orderby')) {
    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}

/**
 * 富国
 * 数组分页函数 核心函数 array_slice
 * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
 * $count  每页多少条数据
 * $page  当前第几页
 * $array  查询出来的所有数组
 * order 0 - 不变   1- 反序
 */
if (!function_exists('page_array')) {
    function page_array($count, $page, $array, $order = 0)
    {
        if (empty($array)) return false;
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1) {
            $array = array_reverse($array);
        }
        $pagedata = array();
        $pagedata = array_slice($array, $start, $count);
        return $pagedata; #返回查询数据
    }
}


if (!function_exists('big_digital_div')) {
    /**
     * 大数相除
     * 将参数整理成整数相除
     * @param $first_num  分子
     * @param $last_num  分母
     * @param int $decimal_len 保留小数点位数
     * @return string
     * 刘富国
     * 20180104
     */
    function big_digital_div($first_num, $last_num, $decimal_len = 8)
    {
        $arr_first_num = explode('.', $first_num);
        $arr_last_num = explode('.', $last_num);
        $int_first_num = $arr_first_num[0] . $arr_first_num[1];
        $int_last_num = ($arr_last_num[0] . $arr_last_num[1]);
        //分母的小数部分大于分子，要将分子往右移位
        if (strlen($arr_last_num[1]) > strlen($arr_first_num[1])) {
            $item_num = (strlen($arr_last_num[1]) - strlen($arr_first_num[1]));
            $item_len = '1';
            for ($i = 0; $i < $item_num; $i++) {
                $item_len = $item_len . '0';
            }
            $int_first_num = big_digital_mul($int_first_num, $item_len);
        } elseif (strlen($arr_last_num[1]) < strlen($arr_first_num[1])) {
            $item_num = (strlen($arr_first_num[1]) - strlen($arr_last_num[1]));
            $item_len = '1';
            for ($i = 0; $i < $item_num; $i++) {
                $item_len = $item_len . '0';
            }
            $int_last_num = big_digital_mul($int_last_num, $item_len);
        }
        $ret_div = bcdiv($int_first_num, $int_last_num, $decimal_len);
        return $ret_div;
    }
}

if (!function_exists('big_digital_mul')) {
    /**
     * 大数相乘
     * 将参数整理成整数相乘，然后分别获取整数位和小数位相加
     * @param $first_num
     * @param $last_num
     * @param int $decimal_len 保留小数点位数
     * @return string
     * 刘富国
     * 20180104
     */

    function big_digital_mul($first_num, $last_num, $decimal_len = 8)
    {
        $arr_first_num = explode('.', $first_num);
        $arr_last_num = explode('.', $last_num);
        $int_first_num = $arr_first_num[0] . $arr_first_num[1];
        $int_last_num = $arr_last_num[0] . $arr_last_num[1];
        $mul_decimal_len = strlen($arr_first_num[1]) + strlen($arr_last_num[1]); //乘积小数位数
        $ret_mul = bcmul($int_first_num, $int_last_num); //乘积结果
        $ret_first_value = 0;
        $ret_last_value = 0;
        //整数部分
        if (strlen($ret_mul) > $mul_decimal_len) {
            $ret_first_value = substr($ret_mul, 0, strlen($ret_mul) - $mul_decimal_len);
        }
        //小数部分
        if ($mul_decimal_len > 0) {
            //如果乘积小数位数小于乘积结果，用截取方式获取小数部分，否则用除的方式获取
            if (strlen($ret_mul) > $mul_decimal_len) {
                $ret_last_value = substr($ret_mul, strlen($ret_mul) - $mul_decimal_len);
                $ret_last_value = '0.' . $ret_last_value;
            } else {
                $item_len = '1';
                for ($i = 0; $i < $mul_decimal_len; $i++) {
                    $item_len = $item_len . '0';
                }
                $ret_last_value = bcdiv($ret_mul, $item_len, $decimal_len);
            }
        }
        $ret = bcadd($ret_first_value, $ret_last_value, $decimal_len);
        return $ret;
    }
}

if (!function_exists('pic_path')) {

    /**
     * 图片全路径
     * @param string $pic_path 数据库的路径
     * @param string param         路径参数
     * @param string which_cdn     采用那个cdn
     * @return string  返回文件路径
     * @author nk
     */
    function pic_path($pic_path, $param = '', $which_cdn = 'qiniu')
    {
        $cdn_path = '';
        if (C('IS_USE_CDN')) {
            // todo 如果cdn 路径存在，即返回cdn路径
        }
        if (strpos($pic_path, 'http://') === 0 || strpos($pic_path, 'https://') === 0)
            return $pic_path;
        $domain = C('PIC_DOMAIN');
        $pic_path = ltrim(str_replace('\\', '/', $pic_path), '/');
        if ($pic_path)
            return $domain . $pic_path;
        return '';
    }
}


/**
 * @param unknown $title
 * @param unknown $content
 * @param unknown $uid
 * @param string $extras
 * @param string $app_platform
 * @return boolean|boolean|string|number|unknown
 * @author 建强 立即推送
 */
if (!function_exists('push_right_now')) {
    function push_right_now($title, $content, $uid, $extras = '', $app_platform = 'app_target')
    {
        push_msg_to_app_person($title, $content, $uid, $extras, $app_platform);//统一用这方法

//        $uid = $uid*1;
//        $title = trim($title);
//        $content = trim($content);
//        if($uid<1 or empty($title) or empty($content)) return false;
//        //如果无swoole直接发送
//        $push_obj = new \Common\Logic\Jpush();
//        $ret =  $push_obj->pushToPerson($title,$content,$uid,$extras,$app_platform );
//        return $ret;
    }
}


if (!function_exists('push_msg_to_app_person')) {
    /**
     *
     * 发送消息给用户
     * 劉富國
     * 2017-11-08
     * @param $title //消息标题
     * @param $content //消息内容
     * @param $uid //用户ID
     * @param $title
     * @param $content
     * @param $uid
     * @param string $extras //功能拓展字段，
     * 例如：
     * "send_modle":"C2C"，//C2C，P2P订单模块
     * "new_order_penging":"1"   //有新订单要处理
     * @param string $app_platform
     * @return bool
     */
    function push_msg_to_app_person($title, $content, $uid, $extras = [], $app_platform = 'app_target')
    {
        $uid = $uid * 1;
        $title = trim($title);
        $content = trim($content);
        if ($uid < 1 or empty($title) or empty($content)) return false;
        //添加发送日志
        $pushUserLogModel = new \Common\Model\PushUserLogModel();
        $pushId = $pushUserLogModel->addMsg($title, $content, $uid);
        if (!$pushId) return false;
        $push_obj = new \Common\Logic\Jpush();
        $ret = $push_obj->pushToPerson($title, $content, $uid, $extras, $app_platform, $pushId);
        if ($ret) return $ret;
        //极光发送失败，用websoket发
        $pushData = array(
            'service_name' => 'JpushWebsocket',
            'data' => array(
                'push_id' => $pushId,
                'title' => $title,
                'content' => $content,
                'uid' => $uid,
                'extras' => $extras,
                'app_platform' => $app_platform,
            ),
        );
        $message = json_encode($pushData);
        $webSocketPushClientObj = new \SwooleCommand\Controller\WebSocketPushClientController();
        $retWebSocket = $webSocketPushClientObj->sendTcpMessage($message);
        $tcpSendData = json_decode($retWebSocket, true);
        if ($tcpSendData['status'] <> 1) return false;
        return $pushUserLogModel->setSendMsgSuccess($pushId);

    }
}


if (!function_exists('push_websocket_msg_to_person')) {

    /**
     * 发送websocket消息给用户
     * 劉富國
     * @param $data
     * @return bool
     */
    function push_websocket_msg_to_person($data)
    {
        if (empty($data)) return false;
        //   使用swoole异步发送
        $pushData = array(
            'method' => $data['method'],
            'data' => $data['data'],
            'service_name' => 'WebsocketMsg',
        );
        $message = json_encode($pushData);
        $webSocketPushClientObj = new \SwooleCommand\Controller\WebSocketPushClientController();
        $retWebSocket = $webSocketPushClientObj->sendTcpMessage($message);
        $tcpSendData = json_decode($retWebSocket, true);
        if ($tcpSendData['status'] <> 1) return false;
        return true;
    }
}

if (!function_exists('getDecimal')) {

    /**
     * 根据个人需求保留多位小数 默认保留8位小数不进行四舍五入
     * @param float $number
     * @param integer $position 需要保留小数的位数
     * @return float
     * @author lirunqing 2017-12-26T17:34:05+0800
     */
    function getDecimal($number, $position = 8)
    {

        $ary = explode('.', (string)$number);
        if (strlen($ary[1]) > $position) {
            $decimal = substr($ary[1], 0, $position);
            $result = $ary[0] . '.' . $decimal;
            return $result;
        } else {
            return $number;
        }
    }
}

if (!function_exists('check_watchword')) {
    /**
     * 校驗手機登入口令
     * 劉富國
     * 2017-10-19
     * @param $uid  用戶ID
     * @param $check_watch_code  手機登入口令
     * @return bool
     */
    function check_watchword($uid, $check_watchword)
    {
        return true; //todo 测试环境关闭口令校验，生产环境要去掉
        $uid = $uid * 1;
        $check_watchword = trim($check_watchword);
        if ($uid < 1 or empty($check_watchword)) return false;
        $userWatchwordModel = new Common\Model\UserWatchwordModel();
        $ret = $userWatchwordModel->checkWatchword($uid, $check_watchword);
        return $ret;
    }
}

if (!function_exists('formatBankType')) {
    /**
     * 格式化银行
     * 2017-10-28 yangpeng
     * @param unknown $num
     */
    function formatBankType($num)
    {
        switch ($num) {
            case 1 :
                $arr = L('_ZGGSYH_');
                break;
            case 2 :
                $arr = L('_ZGNYYH_');
                break;
            case 3 :
                $arr = L('_ZGJSYH_');
                break;
            case 4 :
                $arr = L('_ZGYH_');
                break;
            case 5 :
                $arr = L('_XGHFYH_');
                break;
            case 6 :
                $arr = L('_ZGYHXG_');
                break;
            case 7 :
                $arr = L('_XGDYYH_');
                break;
            case 8 :
                $arr = L('_HSYH_');
                break;
            case 9 :
                $arr = L('_TWB_');
                break;
            case 10 :
                $arr = L('_TDB_');
                break;
            case 11 :
                $arr = L('_HZSJKSYB_');
                break;
            case 12 :
                $arr = L('_DYSYB_');
                break;
            case 13 :
                $arr = L('_HNCB_');
                break;
            case 14 :
                $arr = L('_CHCB_');
                break;
            case 15 :
                $arr = L('_SHSYCXB_');
                break;
            case 16 :
                $arr = L('_TBCBCL_');
                break;
            case 17 :
                $arr = L('_CUBCL_');
                break;
            case 18 :
                $arr = L('_ZFGJSYB_');
                break;
            case 19 :
                $arr = L('_BOKCL_');
                break;
            case 20 :
                $arr = L('_ZGXTSYB_');
                break;
            case 21 :
                $arr = L('_HQTWSYB_');
                break;
            case 22 :
                $arr = L('_ASTWSYB_');
                break;
            case 23 :
                $arr = L('_WDSYB_');
                break;
            case 24 :
                $arr = L('_TWZXQYB_');
                break;
            case 25 :
                $arr = L('_ZDGJSYB_');
                break;
            case 26 :
                $arr = L('_TZSYB_');
                break;
            case 27 :
                $arr = L('_JCSYB_');
                break;
            case 28 :
                $arr = L('_HFTWSYB_');
                break;
            case 29 :
                $arr = L('_RXSYB_');
                break;
            case 30 :
                $arr = L('_HTSYB_');
                break;
            case 31 :
                $arr = L('_TWXGSYB_');
                break;
            case 32 :
                $arr = L('_YXSYB_');
                break;
            case 33 :
                $arr = L('_BXSYB_');
                break;
            case 34 :
                $arr = L('_SXSYB_');
                break;
            case 35 :
                $arr = L('_LBSYB_');
                break;
            case 36 :
                $arr = L('_YDGJSYB_');
                break;
            case 37 :
                $arr = L('_YDSYB_');
                break;
            case 38 :
                $arr = L('_YFSYB_');
                break;
            case 39 :
                $arr = L('_YSSYB_');
                break;
            case 40 :
                $arr = L('_KJSYB_');
                break;
            case 41 :
                $arr = L('_XZTWSYB_');
                break;
            case 42 :
                $arr = L('_TXGJSYB_');
                break;
            case 43 :
                $arr = L('_RSGJSYB_');
                break;
            case 44 :
                $arr = L('_ATSYB_');
                break;
        }
        return $arr ? $arr : false;
    }
}

if (!function_exists('p')) {
    /**格式化打印函数
     * 刘富国
     * * 2017-10-19
     */
    function p($var)
    {
        echo "<br><pre>";
        if (empty($var)) {
            var_dump($var);
        } else {
            if (!is_array($var)) {
                echo($var);
            } else {
                print_r($var);
            }
        }
        echo "</pre><br>";
    }
}

// 获取积分对应的图片
// author zhanghanwen
if (!function_exists('getIntegralAsImg')) {
    function getIntegralAsImg($integral)
    {
        if (0 <= $integral && $integral <= 100) {
            $imgName = 'D.png';
        }
        if (100 < $integral && $integral <= 1000) {
            $imgName = 'C.png';
        }
        if (1000 < $integral && $integral <= 2000) {
            $imgName = 'B.png';
        }
        if (2000 < $integral && $integral <= 3000) {
            $imgName = 'B.png';
        }
        if (6000 < $integral && $integral <= 10000) {
            $imgName = 'B.png';
        }
        if (16000 <= $integral) {
            $imgName = 'B.png';
        }
        return $imgName;
    }
}

if (!function_exists('pp')) {
    function pp($arr)
    {
        echo '<pre>';
        print_r($arr);
        die;
    }
}
/**
 * 获取用户userid
 * @return int
 * @author lirunqing 2017-09-30T11:23:19+0800
 */
function getUserId()
{
    $sessionObj = \Common\Api\RedisIndex::getInstance();
    $loginInfo = $sessionObj->getSessionValue('LOGIN_INFO');
    $userid = !empty($loginInfo['USER_KEY_ID']) ? $loginInfo['USER_KEY_ID'] : 0;
    return $userid;
}

function getUserInfo()
{
    $uid = getUserId();
    $userRow = M('User')->where(['uid' => $uid])->find();
    return $userRow;
}
//检查实名认证
function checkRealName(){
    $uid = getUserId();
    $ret = M('UserReal')->where(['uid' => $uid])->find();
    if($ret['status'] == 1){
        return 1;
    }
    return 2;
}
/** 下一级所需积分
 * @param $current_level
 * @param $integral
 */
function nextLevelIntegralRequired($current_level, $integral)
{
    $totalIntegral = 0;
    switch ($current_level) {
        case 0:
            $totalIntegral = 100;
            break;
        case 1:
            $totalIntegral = 1000;
            break;
        case 2:
            $totalIntegral = 3000;
            break;
        case 3:
            $totalIntegral = 6000;
            break;
        case 4:
            $totalIntegral = 16000;
            break;
        case 5:
            $totalIntegral = 16000;
            break;
    }
    return array('total' => $totalIntegral, 'need' => $totalIntegral - $integral);
}

function getUserForId($uid, $field = '*')
{
    return M('User')->field($field)->where(array('uid' => $uid))->find();
}

function chongbistatus($statu)
{
    switch ($statu) {
        case 1:
            return L('_CZZ_');
            break;
        case 2:
            return L('_CHONGZCG_');
            break;
        case 3:
            return L('_CHONGZSB_');
            break;
    }
}

function TibiStatus($status)
{
    switch ($status) {
        case 0:
            return L('_DDSH_');
            break;
        case 1:
            return L('_TIBICHENGGONG_');
            break;
        case 2:
            return L('_DDTC_');
            break;
        case -1:
            return L('_TIBISHIBAI_');
            break;
    }
}

/**
 * 通过用户名获取用户id
 * author zhanghanwen 2017年10月17日11:12:57
 * return int
 **/
function getUserIdForUserName($username, $addition = false)
{
    $whereArr['username'] = $username;
    if ($addition) {
        $whereArr += $addition;
    }
    $data = M('user')->where($whereArr)->field('uid')->find();
    return isset($data['uid']) ? $data['uid'] : 0;
}


/**
 *  给分页传参数
 * @param Object $Page 分页对象
 * @param array $parameter 传参数组
 */
function setPageParameter($Page, $parameter)
{
    foreach ($parameter as $k => $v) {
        if (isset($v)) {
            $Page->parameter[$k] = $v;
        }
    }
}

/**密码加密
 * @param string $password
 * @return string
 * @author 宋建强
 */
function passwordEncryption($password)
{
    return md5(md5($password) . C('PASSWORDSUFFIX'));
}

/**加密密码验证 $pwd
 * @param unknown $pwd
 * @param unknown $password
 * @return boolean
 */
function passwordVerification($pwd, $password)
{

    if (passwordEncryption($pwd) == $password) {
        return true;
    }
    return false;
}

/**
 * 发送邮件
 * @param array $fromData 发送人邮件信息
 *         string $fromData['emailHost'] 必传 企业邮局域名
 *         string $fromData['emailPassWord'] 必传 邮局密码
 *         string $fromData['emailUserName'] 必传 邮件发送者email地址
 *         string $fromData['formName'] 必传 邮件发送者名称
 * @param string $email 收件人邮箱
 * @param string $title 邮件标题
 * @param string $body 邮件内容
 * @return bool
 * @author lirunqing 2017-10-19T14:20:56+0800
 */
function sendEmail($fromData = array(), $email, $title, $body)
{

    $emailHost = $fromData['emailHost'];
    $emailPassWord = $fromData['emailPassWord'];
    $emailUserName = $fromData['emailUserName'];
    $formName = $fromData['formName'];


    /*以下内容为发送邮件  update by 建强*/
    require_once(APP_PATH . 'Common/PHPMailer/class.phpmailer.php'); //下载的文件必须放在该文件所在目录
    $mail = new PHPMailer();
    $mail->SMTPDebug = 0;  // 关闭debug调式模式


    //配置邮件选项 免除ssl证书效验
    $mail->SMTPSecure = "ssl";
//    $mail->SMTPOptions = array(
//        'ssl' => array(
//            'verify_peer' => false,
//            'verify_peer_name' => false,
//            'allow_self_signed' => true
//        )
//    );

    $mail->Port = 465;

    $mail->IsSMTP();//使用SMTP方式发送 设置设置邮件的字符编码，若不指定，则为'UTF-8
    $mail->Host = $emailHost;//'smtp.qq.com';//您的企业邮局域名
    $mail->SMTPAuth = true;//启用SMTP验证功能   设置用户名和密码。
    $mail->Timeout = 60;
    $mail->Username = $emailUserName;//'mail@koumang.com'//邮局用户名(请填写完整的email地址)
    $mail->Password = $emailPassWord;//'xiaowei7758258'//邮局密码
    $mail->From = $emailUserName;//'mail@koumang.com'//邮件发送者email地址

    $mail->FromName = $formName;//邮件发送者名称
    $mail->AddAddress($email);// 收件人邮箱，收件人姓名
    $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式

    $mail->Subject = "=?UTF-8?B?" . base64_encode($title) . "?=";
    $mail->Body = $body; //邮件内容
    $mail->AltBody = "这是一封HTML格式的电子邮件。"; //附加信息，可以省略
    $res = $mail->Send();  //发送邮件
    \Think\Log::write('-------send mail result:'.$res);
    return $res;  //bool
}

/*
 * 获取分表  
 * author 宋建强
 * Date  2017年8月10日
 * @parame string  table
 * @parame int  
 * @parame uid  
 * return string
 */
function getTbl($table, $uid, $mod = 4)
{
    return $table . $uid % $mod;
}

/*
 * 注册信息正则验证字段
 * author 宋建强
 * Date  2017年8月10日
 * @parame string  value
 * @parame string  reg
 * return  bool
 */
function regex($value, $rule)
{
    $validate = [
        'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'phone' => '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#',
        'Feedphone' => '/^(\d{4,20})$/',
        'password' => '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/',
        'password2' => '/^[A-Z][0-9A-Za-z]{5,17}$/',
        'interphone' => '/^[0-9]{6,11}$/',
        // 'username'  =>  '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/',   /^(?![A-Z]+$)(?![a-z]+$)(?!\d+$)(?![\W_]+$)\S{6,16}$/
        'password3' => '/^(?![A-Z]+$)(?![a-z]+$)(?!\d+$)(?![\W_]+$)\S{6,18}$/',//检验密码是含有小写字母、大写字母、数字、特殊符号的两种及以上
        'password4' => '/^[A-Z]{1}(.){5,17}$/',//首字母大写
        'password5' => '/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,20}$/',//检验密码是含有字母、数字、特殊符号的两种及以上
        'username' => '/^[a-zA-Z][A-Za-z0-9]{5,17}$/',
        'double' => '/^[-\+]?\d+(\.\d+)?$/',
        'bankcard_cn' => '/^(\d{16,19})$/',
        'bankcard_tw' => '/^(\d{9,19})$/',
        'bankcard_xg' => '/^(\d{9,19})$/',
        'card' => '/^[A-Za-z0-9]{4,20}$/',
        'bindtoken' => '/^[A-Za-z0-9]+$/',
        'qq_num' => '/^[0-9]{4,15}/',
//            'addurl'    =>  '/^[0-9|A-Za-z]*[A-Za-z]+[A-Za-z0-9_]*$/{15,58}$/',
        'addurl' => '/^[0-9|A-Za-z]*[A-Za-z]+[A-Za-z0-9_]*$/',//由字母+数字组合 不能为纯数字
        'qq' => '/^[1-9][0-9]{4,15}$/',
//            'passport'  =>  '/^((?!\d+$)(?![a-zA-Z]+$))[\da-zA-Z]{6,15}$/',
        'passport' => '/^(?![a-zA-Z]+$)[\da-zA-Z]{6,15}$/is',//数组加字母或数字
        'cardname1' => '/^[\x7f-\xff]+$/',//银行卡开户名中文
        'cardname2' => '/^(?=.*[a-zA-Z])[a-zA-Z ]{2,20}$/', //字母加空格
        'passportname' => '/^[a-zA-Z]{1,18}$/',//护照姓名  \x7f-\xff

        'psptNameHasBlank' => '/^([a-zA-Z]+\s[a-zA-Z])|^([a-zA-Z]){1,18}$/',
        //'addressname'  =>  '/^.{10,60}$/',//银行卡开户地址，只限制位数
        'addressname' => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]{5,60}$/u',//银行卡开户地址，只限制位数
        'address_eos' => '/^[a-z1-5]*[a-z]([a-z1-5])*$/',
        'address_memo_url' => '/[a-zA-Z0-9]$/',

    ];
    $rule = $validate[$rule];
    $sb = preg_match($rule, $value);
    if ($sb === 1) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检验短信验证码   redis   时间120s  宋建强 21:05
 * @param1  string 场景   $scene
 * @param2  string $code
 * @return boolean
 */
function checkSmsCode($uid, $phone, $scene, $code)
{
    $redisClient = Common\Api\RedisCluster::getInstance();
    $key = $scene . '_' . $uid . '_' . $phone;
    $res_code = $redisClient->get($key);
    if ($res_code) {
        if ($res_code == strtolower($code)) {
            return true;
        }
    }
    return false;
}

/**
 * 删掉短信验证码
 * @param1  string uid    uid
 * @param2  string phone  手机号
 * @param2  string 场景          发送的场景
 */
if (!function_exists('delSmsKey')) {
    function delSmsKey($uid, $phone, $scene)
    {
        // $redis=new Common\Api\RedisCluster();
        $redisClient = Common\Api\RedisCluster::getInstance();
        $key = $scene . '_' . $uid . '_' . $phone;
        $redisClient->del($key);
    }
}
/**
 * 格式化財務日誌搜鎖時間
 * @param $num
 * @author yangpeng
 * 2017-8-17
 */
function formatAddTime($num)
{
    $where = "";
    switch ($num) {
        case 1 :
            $time = time() - 7 * 24 * 3600; //進一個星期
            $where .= 'and';
            $where .= " add_time >=$time ";
            break;
        case 2 :
            $time = time() - 30 * 24 * 3600; //進一個月
            $where .= 'and';
            $where .= " add_time >=$time ";
            break;
        case 3 :
            $time = time() - 3 * 30 * 24 * 3600; //進三個月
            $where .= 'and';
            $where .= " add_time >=$time ";
            break;
        case 4 :
            break;
    }
    return $where;
}

/**
 * curl get请求
 * @param string $url 请求url地址
 * @return json
 * @author lirunqing 2017-10-17T11:09:54+0800
 */
function vget($url)
{ // 模拟获取内容函数
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);// TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
//	dump( curl_error ( $curl ));die;
    if (curl_errno($curl)) {
        return false;
        // echo 'Errno' . curl_error ( $curl );
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

/**
 * 显示日志类型
 * yangpeng
 * 2017年8月10日20:24:11
 * 1提币，2充币，3系统(充值),4系统(扣除)，5线下交易撤销返款，6线下交易购买人获取，7线下交易挂售人扣除，8线下交易手续费扣除，9线下交易手续费返还
 * 10扣除币币交易手续费，11返还币币交易手续费，12扣除币币交易数量，13返还币币交易数量，14线下交易(管理员)手续费返还，15线下交易(管理员)撤销返款，16币币交易(管理员)撤销返还
 * 17线下交易撤销返款(系统)，18线下交易撤销手续返还(系统)
 */
function getFinanceTypeList()
{
    $arr = array(
        array('id' => 1, 'name' => L('_TIBI_')),//1提币
        array('id' => 2, 'name' => L('__CB__')),//2充币
        array('id' => 3, 'name' => L('_XTCZ_')),//3系统(充值)
        array('id' => 4, 'name' => L('_XTKC_')),//4系统(扣除)

        array('id' => 5, 'name' => L('_PPCWRZ_')),//5p2p交易财务日志
        array('id' => 6, 'name' => L('_CCCWRZ_')),//c2c交易财务日志
        array('id' => 7, 'name' => L('_BBCWRZ_')),//币币交易财务日志
        /*
        array('id'=>5,'name'=>L('_XXJYCXFK_')),//5线下交易撤销返款
        array('id'=>6,'name'=>L('_XXJYGMRHQ_')),//6线下交易购买人获取
        array('id'=>7,'name'=>L('_XXJYGSRKC_')),//7线下交易挂售人扣除
        array('id'=>8,'name'=>L('_XXJYSXFKC_')),//8线下交易手续费扣除
        array('id'=>9,'name'=>L('_XXJYSXFFH_')),//9线下交易手续费返还
        
        array('id'=>14,'name'=>L('_XXJYGLYSXFFH_')),//14线下交易(管理员)手续费返还
        array('id'=>15,'name'=>L('_XXJYGLYCXFH_')),//15线下交易(管理员)撤销返款
        
        array('id'=>17,'name'=>L('_XXJYCXFKXT_')),//17线下交易撤销返款(系统)
        array('id'=>18,'name'=>L('_XXJYCXSXFFHXT_')),//18线下交易撤销手续返还(系统)
        
        array('id'=>10,'name'=>L('_KCBBJYSXF_')),//10扣除币币交易手续费
        array('id'=>11,'name'=>L('_FHBBJYSXF_')),//11返还币币交易手续费
        array('id'=>12,'name'=>L('_KCBBJYSL_')),//12扣除币币交易数量
        array('id'=>13,'name'=>L('_FHBBJYSL_')),//13币币交易成交入账
        
        array('id'=>37,'name'=>L('_BBJYCXFH_')),// 37.幣幣交易撤銷入賬,
//        array('id'=>14,'name'=>L('_XXJYGLYSXFFH_')),//14线下交易(管理员)手续费返还
//        array('id'=>15,'name'=>L('_XXJYGLYCXFH_')),//15线下交易(管理员)撤销返款
        array('id'=>16,'name'=>L('_BBJYGLYCXFH_')),//16币币交易(管理员)撤销返还
//        array('id'=>17,'name'=>L('_XXJYCXFKXT_')),//17线下交易撤销返款(系统)
//        array('id'=>18,'name'=>L('_XXJYCXSXFFHXT_')),//18线下交易撤销手续返还(系统)
        array('id'=>19,'name'=>L('_CGDKCB_')),//19 C2C挂单扣除币
        array('id'=>20,'name'=>L('_CGDCXFHB_')),//20 C2C挂单撤销返还币
        array('id'=>21,'name'=>L('_CGDBZJKC_')),//21 C2C挂单保证金扣除
        array('id'=>22,'name'=>L('_CGDBZJFH_')),//22 C2C挂单保证金返还
        array('id'=>23,'name'=>L('_CJYSXFKC_')),//23 C2C交易手续费扣除
        array('id'=>24,'name'=>L('_CJYSXFFH_')),//24 C2C交易手续费返还
        array('id'=>25,'name'=>L('_CCJYDDKC_')),//25.C2C交易订单扣除
        array('id'=>26,'name'=>L('_CCJYDDCXFH_')),//26.C2C交易订单撤销返还
        array('id'=>27,'name'=>L('_CCJYDDRZ_')),//27.C2C交易订单入账    
        array('id'=>28,'name'=>L('_CCGLYCZFBGMJ_')),//28.C2C管理员操作放币给买家
        array('id'=>29,'name'=>L('_CCGLYCZKCMJSXF_')),//29.C2C管理员操作扣除买家手续费
        array('id'=>30,'name'=>L('_CCGLYCZTBGMJ_')),//30.C2C管理员操作退币给卖家 
        array('id'=>31,'name'=>L('_CCGLYCZTHMASXF_')),// 31.C2C管理员操作退还卖家手续费
        array('id'=>32,'name'=>L('_CCMJHQBXT_')),//32.C2C买家获取币(系统)
        array('id'=>33,'name'=>L('_CCMJHQBKCSXF_')),//33.C2C买家获取币扣除手续费(系统)
        array('id'=>34,'name'=>L('_CCMJJSBXT_')),// 34.C2C卖家减少币(系统)'
        array('id'=>35,'name'=>L('_CCGDZDFHSXFXT_')),// 35.C2C挂单返还手续费(系统)
        array('id'=>36,'name'=>L('_CCGDFHBZJXT_')),// 36.C2C挂单返还保证金(系统)',
//        array('id'=>37,'name'=>L('_BBJYCXFH_')),// 37.幣幣交易撤銷入賬,
    */
    );
    return $arr;
}

/**
 * 格式化日志类型
 * @param 交易类型 $num
 * @author yangpeng
 * 2017-8-14
 *  1提币，2充币，3系统(充值),4系统(扣除)，5线下交易撤销返款，6线下交易购买人获取，7线下交易挂售人扣除，8线下交易手续费扣除，9线下交易手续费返还
 * 10扣除币币交易手续费，11返还币币交易手续费，12扣除币币交易数量，13返还币币交易数量，14线下交易(管理员)手续费返还，15线下交易(管理员)撤销返款，16币币交易(管理员)撤销返还
 * 17线下交易撤销返款(系统)，18线下交易撤销手续返还(系统)
 */
function formatFinanceType($num)
{
    switch ($num) {
        case 1 :
            $arr = L('_TIBI_');
            break;
        case 2 :
            $arr = L('__CB__');
            break;
        case 3 :
            $arr = L('_XTCZ_');
            break;
        case 4 :
            $arr = L('_XTKC_');
            break;
        case 5 :
            $arr = L('_XXJYCXFK_');
            break;
        case 6 :
            $arr = L('_XXJYGMRHQ_');
            break;
        case 7 :
            $arr = L('_XXJYGSRKC_');
            break;
        case 8 :
            $arr = L('_XXJYSXFKC_');
            break;
        case 9 :
            $arr = L('_XXJYSXFFH_');
            break;
        case 10 :
            $arr = L('_KCBBJYSXF_');
            break;
        case 11 :
            $arr = L('_FHBBJYSXF_');
            break;
        case 12 :
            $arr = L('_KCBBJYSL_');
            break;
        case 13 :
            $arr = L('_FHBBJYSL_');
            break;
        case 14 :
            $arr = L('_XXJYGLYSXFFH_');
            break;
        case 15 :
            $arr = L('_XXJYGLYCXFH_');
            break;
        case 16 :
            $arr = L('_BBJYGLYCXFH_');
            break;
        case 17 :
            $arr = L('_XXJYCXFKXT_');
            break;
        case 18 :
            $arr = L('_XXJYCXSXFFHXT_');
            break;
        case 19 :
            $arr = L('_CGDKCB_');
            break;
        case 20 :
            $arr = L('_CGDCXFHB_');
            break;
        case 21 :
            $arr = L('_CGDBZJKC_');
            break;
        case 22 :
            $arr = L('_CGDBZJFH_');
            break;
        case 23 :
            $arr = L('_CJYSXFKC_');
            break;
        case 24 :
            $arr = L('_CJYSXFFH_');
            break;

        case 25 :
            $arr = L('_CCJYDDKC_');
            break;
        case 26 :
            $arr = L('_CCJYDDCXFH_');
            break;
        case 27 :
            $arr = L('_CCJYDDRZ_');
            break;
        case 28 :
            $arr = L('_CCGLYCZFBGMJ_');
            break;
        case 29 :
            $arr = L('_CCGLYCZKCMJSXF_');
            break;
        case 30 :
            $arr = L('_CCGLYCZTBGMJ_');
            break;
        case 31 :
            $arr = L('_CCGLYCZTHMASXF_');
            break;
        case 32 :
            $arr = L('_CCMJHQBXT_');
            break;
        case 33 :
            $arr = L('_CCMJHQBKCSXF_');
            break;
        case 34 :
            $arr = L('_CCMJJSBXT_');
            break;
        case 35 :
            $arr = L('_CCGDZDFHSXFXT_');
            break;
        case 36 :
            $arr = L('_CCGDFHBZJXT_');
            break;
        case 37 :
            $arr = L('_BBJYCXFH_');
            break;
    }
    return $arr;
}


/*
 * 格式化地区
 * 2017-12-4  yangpeng
 */
function formatAreas($num)
{
    switch ($num) {
        case '+86' :
            $arr = L('_ZGDL_');
            break;
        case '+852' :
            $arr = L('_ZGXG_');
            break;
        case '+886' :
            $arr = L('_ZGTW_');
            break;
    }
    return $arr;
}

/**
 * 格式化日志类型
 * @param 交易类型 $num
 * @author yangpeng
 * 2017-8-14
 */
function formatMoneyZhengFu($num)
{
    if ($num == 1) {
        return L('_SHOURU_');
    }
    if ($num == 2) {
        return L('_ZHICHU_');
    }
}


/**
 * 格式化币种
 * @param 币种类型 $currency_id
 * @author yangpeng
 * 2017-8-15
 */
function getCurrencyName($currency_id)
{
    $currencyModel = new \Home\Model\CurrencyModel();
    $currencyInfo = $currencyModel->getCurrencyByCurrencyid($currency_id, 'currency_name');
    $name = '';
    if (!empty($currencyInfo)) $name = $currencyInfo['currency_name'];
    return $name;
}

function formatCardType($type)
{
    switch ($type) {
        case 1 :
            $arr = '護照';
            break;
    }
    return $arr ? $arr : '其他';
}

/**
 * 格式化日志类型
 * @param int $type
 * 黎玲  2017-10-12
 */
function formatLogType($type)
{
    switch ($type) {
        case 1 :
            $type = L('_DENGLU_');
            break;
        case 2 :
            $type = L('_XIUGAIMM_');
            break;
        case 3 :
            $type = L('_XGJYMM_');
            break;
        case 4 :
            $type = L('_ZHAOHMM_');
            break;
        case 5 :
            $type = L('_ZHJYMM_');
            break;
        case 6 :
            $type = L('_YONGHUZC_');
            break;
        case 7 :
            $type = L('_SZZJMM_');
            break;

    }
    return $type;
}


/**
 * 多语言实名认证日志
 * @param int $system_reply
 * 建强  2017-11-12
 */
function formatUserRealReply($system_reply)
{
    $word = [
        "1" => L('_NYTGSMRZQWCFRZ_'),//您的證件已註冊綁定，請勿重複註冊。
        "2" => L('_NDSCZJZMHQCXSC_'),//您的手持證件照片證件不清晰，無法查看證件姓名和證件號碼，請重新拍照上傳。
        "3" => L('_NCZZDZJHMHXM_'),//證件號碼或姓名被遮擋。
        "4" => L('_ZPGSCWBNCGLZ_'),//照片格式錯誤（標準格式為.jpg，照片體積不能超過 3 MB）。
        "5" => L('_TXDXMYZJXMHHMBF_'),//您所提交的實名信息與手持證件照不相符或被判定為後期處理照片。
        "6" => L('_NDZJNLCX_'),//您的證件年齡超限。
        "7" => L('_SFRZSH_'),//身份認證待審核
        "8" => L('_FHTJJYTG_'),//符合條件給予通過
        "9" => L('_NSYDZJBZQQZCHZ_'),//您使用的證件不正確，請使用護照進行註冊認證
    ];
    return $word[$system_reply] ? $word[$system_reply] : '';
}

/**
 * 格式化日志类型
 * @param int $type
 * 黎玲  2017年10月25日10:20:57
 */
function formatLogType1($type)
{
    switch ($type) {
        case 1 :
            $type = '登錄';
            break;
        case 2 :
            $type = '修改密碼';
            break;
        case 3 :
            $type = '修改資金密碼';
            break;
        case 4 :
            $type = '找回密碼';
            break;
        case 5 :
            $type = '找回資金密碼';
            break;
        case 6 :
            $type = '用戶註冊';
            break;
        case 7 :
            $type = '設置資金密碼';
            break;
    }
    return $type;
}

/**
 * 获取某个IP地址所在的位置
 * @param string $ip ip地址
 * @return Ambigous <multitype:, NULL, string>
 * 黎玲 2017 10 12
 */
function getIpArea($ip)
{
    $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
    $area = $Ip->getlocation($ip); // 获取某个IP地址所在的位置
    return $area ? $area['country'] . $area['area'] : "未知地址";
}

/*
 *  短信发送类型记录
 *  1.登录场景 2找回密码 3找回交易密码
*/
function FormatSmsType($type)
{
    $arr = [
        '1' => '注册',
        '2' => '解绑',
        '3' => '提币',
    ];

    return $arr[$type] ? $arr[$type] : '';
}

/*
 * 李江
 * 2017年11月3日14:55:17
 * 判断是否实名认证
 */
//function checkUserReal($uid)
//{
//    $status = M('UserReal')->where(['uid' => $uid])->getField('status');
//
//    if (!isset($status)) {
//        return -2; //未提交实名认证
//    } else {
//        return $status;//返回相应的状态
//    }
//}

/**
 * 隐藏银行卡前n位，只显示后4位
 * @yangpeng
 * 2017-8-24
 */
function hideBankNumber($str)
{
    $str_full = substr_replace($str, '**** **** **** ', 0, -4);
    return $str_full;
}

/**
 * 李江
 * 2017年11月23日16:39:31
 * 检查用户提币数量是否满足
 */
function getTibiMaxNum($uid, $vip_level, $currency_id)
{
    $day_max_tibi_amount = M('LevelConfig')->where(['vip_level' => $vip_level, 'currency_id' => $currency_id])->getField('day_max_tibi_amount');
    $start_time = strtotime(date('Y-m-d', time()) . ' 0:0:0');
    $end_time = strtotime(date('Y-m-d', time()) . ' 23:59:59');
    //当天已经提币数量
    $today_sum = M('Tibi')->where(['uid' => $uid, 'currency_id' => $currency_id, 'add_time' => ['between', [$start_time, $end_time]]])->sum('num');
    $user_num = M('UserCurrency')->where(['uid' => $uid, 'currency_id' => $currency_id])->getField('num'); //用户剩余数量
    $last_num = ($day_max_tibi_amount - $today_sum) > $user_num ? $user_num : ($day_max_tibi_amount - $today_sum);
    return number_format(floatval($last_num), 8, '.', '');
}

/**
 * 方法增强，根据$length自动判断是否应该显示...
 * 字符串截取，支持中文和其他编码
 * QQ:125682133
 *
 * @access public
 * @param string $str
 *            需要转换的字符串
 * @param string $start
 *            开始位置
 * @param string $length
 *            截取长度
 * @param string $charset
 *            编码格式
 * @param string $suffix
 *            截断显示字符
 * @return string
 */
function msubstr_local($str, $start = 0, $length, $charset = "utf-8")
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re ['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re ['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re ['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re ['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re [$charset], $str, $match);

        $slice = join("", array_slice($match [0], $start, $length));
    }
    return (strlen($str) > strlen($slice)) ? $slice . '...' : $slice;
}

/*html样式转换方法
 * @fuwen
 * @date2017年11月29日
 * @time16:52:11
 */
function formathtml($str)
{
    $data = htmlspecialchars_decode($str);
    return $data;

}/*
 * 积分对应转换
 * 2017-10-30  yangpeng
 */
function formatJifenLog($type)
{
    switch ($type) {
        case 1 :
            $type = L('_BDDHHM_');
            break;
        case 2 :
            $type = L('_BDYX_');
            break;
        case 3 :
            $type = L('_BDCZDZ_');
            break;
        case 4 :
            $type = L('_BDZCDZ_');
            break;
        case 5 :
            $type = L('_BDAPPLP_');
            break;
        case 6 :
            $type = L('_ZJMIMA_');
            break;
        case 7 :
            $type = L('_BDYHK_');
            break;
        case 8 :
            $type = L('_MTSCDL_');
            break;
        case 9 :
            $type = L('_DDJY_');
            break;
        case 10 :
            $type = L('_CZQ_');
            break;
        case 11 :
            $type = L('_CZB_');
            break;
        case 12 :
            $type = L('_VCZZCE_');
            break;
        case 13 :
            $type = L('_SMRZTG_');
            break;
    }
    return $type;
}


/*
 * 2017 12 5 chao
 * 钱包地址交易检查  16YVbcN2fiRanyQDhJFHQ8F6oSwiN4D5R9
 */
function checkAddress($url = "https://chain.api.btc.com/v3/address/16YVbcN2fiRanyQDhJFHQ8F6oSwiN4D5R9/tx")
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        return false;
        // echo 'Errno' . curl_error ( $curl );
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

/**
 * 检测用户是否绑定银行卡
 * @param int $userId 用户id
 * @return bool
 * @author lirunqing 2018年2月27日12:14:33
 */
if (!function_exists('checkUserBindBank')) {
    function checkUserBindBank($userId, $om = '')
    {
        $where = array();
        if (!empty($om)) {
            $where['b.country_code'] = '+' . $om;
        }
        $where['uid'] = $userId;
        $where['status'] = 1;
        $join1 = 'LEFT JOIN __BANK_LIST__ as b on a.bank_list_id = b.id';
        $res = M('UserBank')->alias('a')
            ->join($join1)
            ->where($where)
            ->find();

        if (empty($res)) {
            return false;
        }

        return true;
    }
}

/**
 * @author 建强  2018年2月27日17:30:20
 * 获取交易区的名称
 */
if (!function_exists('getAreaName')) {
    function getAreaName($om)
    {
        $arr = [
            '86' => L('_ZHONGGUO_'),
            '886' => L('_ZGTW_'),
            '852' => L('_ZGXG_'),
        ];
        return $arr[$om] ? $arr[$om] : L('');
    }


    /**
     * @author yangpeng  2018年7月9日
     * 将交易地区代号转换成地区
     */
    if (!function_exists('formatArea')) {
        function formatArea($area)
        {
            switch ($area) {
                case 0:
                    $arr = 'china';
                    break;
                case 1:
                    $arr = 'hk';
                    break;
                case 2:
                    $arr = 'taiwan';
                    break;
                default:
                    $arr = 'hk';
                    break;
            }
            return $arr;
        }
    }
    /**
     * 格式化问题反馈各级标题
     * yangpeng   2018年7月19日10:08:23
     * @return string
     */
    if (!function_exists('formatProblemTitle')) {
        function formatProblemTitle($id)
        {
            $language = cookie('think_language') ? cookie('think_language') : 'zh-cn';
            $data = M('Feed')->where(['id' => $id])->find();
            if ($language == 'zh-cn') {
                return $data['zh_title'];
            } elseif ($language == 'en-us') {
                return $data['en_title'];
            } else {
                return $data['tw_title'];
            }
        }
    }

    if (!function_exists('problemDetial')) {
        function problemDetial($id)
        {
            switch ($id) {
                //充币未到账问题
                case 8:
                    $msg = L('_BTCNBZHWDZ_');
                    break;//内部账户之间转账未到账
                case 9:
                    $msg = L('_BTCCZDCWDZ_');
                    break;//充值到错误地址导致未到账
                case 10:
                    $msg = L('_BTCQT_');
                    break;//其他充币问题
                case 37:
                    $msg = L('_BTCTGDZCBWDZ_');
                    break;//通过地址充币未到账
                //提币遇到问题
                case 11:
                    $msg = L('_BTCTBDDRGSH_');
                    break;//提币等待人工审核
                case 12:
                    $msg = '';
                    break;//未收到提币确认邮件
                case 13:
                    $msg = '';
                    break;//未收到提币确认短信
                case 14:
                    $msg = L('_BTCSQCXTB_');
                    break;//申请撤销提币
                case 15:
                    $msg = L('_BTCQTTBWT_');
                    break;//其他提币问题
                //账户安全问题
                case 16:
                    $msg = L('_BTCSQGHYX_');
                    break;//申请更换绑定邮箱
                case 17:
                    $msg = L('_BTCSQGHBDSJ_');
                    break;//申请更换绑定手机
                case 18:
                    $msg = L('_BTCWFXGDLMM_');
                    break;//无法修改登陆密码
                case 19:
                    $msg = L('_BTCWFXGJYMM_');
                    break;//无法修改交易密码
                case 20:
                    $msg = L('_BTCZHWFDR_');
                    break;//账号无法登陆
                case 21:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//其他账户与安全问题
                //身份认证问题
                case 22:
                    $msg = L('_BTCWFSCZJZL_');
                    break;//无法上传证件资料
                case 23:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//对认证驳回有疑问
                case 24:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//认证资料已提交尚未审核
                case 25:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//其他身份认证问题
                //交易问题
                case 26:
                    $msg = L('_BTCBBJYWT_');
                    break;//币币交易问题
                case 27:
                    $msg = L('_BTCPPCCJYWT_');
                    break;//p2p交易问题
                case 28:
                    $msg = L('_BTCPPCCJYWT_');
                    break;//c2c交易问题
                case 29:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//其他交易问题
                //投诉与建议
                case 30:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//充币问题投诉
                case 31:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//提币问题投诉
                case 32:
                    $msg = L('_BTCQTZHAQWT_');
                    break;//账户问题投诉
                case 33:
                    $msg = L('_BTCCPJY_');
                    break;//产品建议
                case 34:
                    $msg = L('_BTCFWJY_');
                    break;//服务建议


                //其他问题
                case 38:
                    $msg = L('_BTCQTZHAQWT_');
                    break;

                default:
                    '';
                    break;
            }
            return $msg;
        }
    }

    if (!function_exists('createRealNameToken')) {
        function createRealNameToken($id = "")
        {
            $code = md5(md5(microtime()));
            $redisIndex = \Common\Api\RedisIndex::getInstance();
            $redisIndex->setSessionRedis('HOME_REAL_NAME' . $id, $code);
            $value = $redisIndex->getSessionValue("HOME_REAL_NAME" . $id);
            return $value;
        }
    }

    if (!function_exists('checkRealNameToken')) {
        function checkRealNameToken($token, $uid)
        {
            $redisIndex = \Common\Api\RedisIndex::getInstance();
            $value = $redisIndex->getSessionValue("HOME_REAL_NAME" . $uid);
            if (!empty($value) && $value == $token) {
                $redisIndex->delSessionRedis("HOME_REAL_NAME" . $uid);
                return true;
            }
            return false;
        }
    }

    if (!function_exists('get_order')) {
        function get_order()
        {
            $rand = sprintf("%02s", mt_rand(0, 99));
            return date('YmdHis').$rand;

        }
    }

}
