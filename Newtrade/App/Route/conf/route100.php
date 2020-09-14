<?php
/**
 * 1.0.0版本的控制器路由類
 *
 * 劉富囯
 * 2017-10-19
 */
return array(
    //退出
    'LoginOut' => [
        'Server' => 'V100\\Sign',
        'Method' => 'loginOut'
    ],
    //语言版本设置
     'SetLang' => [
        'Server' => 'V100\\UserCommonApi',
        'Method' => 'setLang'
    ],
    // 发送验证码登录
    'SendCode' => [
        'Server' => 'V100\\Sign',
        'Method' => 'sendCode'
    ],
    // 发送验证码登录
    'SignIn' => [
        'Server' => 'V100\\Sign',
        'Method' => 'signIn'
    ],
    // 口令生成
    'CreateWatchwordToken' => [
        'Server' => 'V100\\Token',
        'Method' => 'createWatchwordToken'
    ],

    // 手機綁定
    'BindAccount' => [
        'Server' => 'V100\\Token',
        'Method' => 'bindAccount'
    ],

    // 校验手機綁定
    'CheckUserBind' => [
        'Server' => 'V100\\Token',
        'Method' => 'checkUserBind'
    ],

    //查看手機序列號
    'GetUserSerial' => [
        'Server' => 'V100\\Token',
        'Method' => 'getUserSerial'
    ],

    //查看用户登陆日志
    'GetUserLogByUid' => [
        'Server' => 'V100\\UserLog',
        'Method' => 'getUserLogByUid'
    ],

    //消息发送(仅后台API使用)
    'SendMsgToPerson' => [
        'Server' => 'V100\\UserCommonApi',
        'Method' => 'sendMsgToPerson'
    ],
    //消息发送(仅后台API使用)
    'SendMsgToPersonList' => [
        'Server' => 'V100\\UserCommonApi',
        'Method' => 'sendMsgToPersonList'
    ],
    //websocket消息发送(仅后台API使用)
    'SendWebsocketToUser' => [
        'Server' => 'V100\\UserCommonApi',
        'Method' => 'sendWebsocketToUser'
    ],



);