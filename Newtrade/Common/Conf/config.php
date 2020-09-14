<?php
return array(
        'LOAD_EXT_CONFIG'   => 'configdb,coinurlconfig', // 加载扩展配置文件
        'PASSWORDSUFFIX'    => 'awioghiowqegoqajhgoi32jgo23',
        'MODULE_ALLOW_LIST' => array('Home', 'App','AppTarget','SwooleCommand'),
        'MODULE_DENY_LIST'  =>  array('Common','Logics','Runtime','Api'),
        'LOAD_EXT_FILE'     => 'func_app_common',
        'DEFAULT_TIMEZONE' => 'America/New_York',//时区
        'URL_MODEL' => 2,         ///默认是1 PATHIFO路由
        'URL_ROUTER_ON' => true,  // 是否开启URL路由
        'SHOW_PAGE_TRACE'=>false,  //页面追踪
        'IS_DEBUG_VER'         => true, // 测试环境，上线后要设置为false
        'SYS_AUTO_SECRET_KEY'  => '7V/s-h<@Lrk+EV1/y&}-sY=4YQZc-v!O', // 系统加密随机字符串，不能改动SYS_AUTO_SECRET_KEY
		//===============特别注意此配置不要随便动========================//
		'SESSION_AUTO_START' => false,
  //       'TMPL_EXCEPTION_FILE'=>'./Newtrade/Home/View/Public/404Transfer.html' ,//关闭debug模式错误跳入404
		// 'TMPL_ACTION_ERROR'  =>'./Newtrade/Home/View/Public/404Transfer.html', // 默认错误跳转对应的模板文件'
//        'DB_SQL_LOG'    =>false,
		//'LOG_TYPE'      =>"File",
		'URL_CASE_INSENSITIVE' =>FALSE,         //严格区分大小写
         'LOG_RECORD' => true,                   // 开启日志记录
         'LOG_LEVEL' =>'EMERG,ALERT,CRIT,ERR,SQL', // 只记录EMERG ALERT CRIT ERR 错误
         'TOKENSUFFIX'    => 'dea25b0af6cc5ea9da4961', //app登录口令密钥
         'API_TOKEN_SUFFIX'    => 'Lrk+@EV1/y&@k#$aEKf@$45E6', //api接口密钥

         'DB_CONFIG2' => 'mysql://root:wen123456@47.57.131.217:3306/ordersys',// 工单数据库相关连接
         //'DB_CONFIG2' => 'mysql://root:root@127.0.0.1:3306/sys',// 工单数据库相关连接         
         'DB_CONFIG2_PREFIX' => "work_",// 工单数据库前缀 

    
        //多语言配置项
        'LANG_SWITCH_ON'      => true,        //开启多语言支持开关
        'LANG_LIST'           => 'en-us,zh-cn,zh-tw', // 允许切换的语言列表 用逗号分隔
        'DEFAULT_LANG'        => 'zh-tw',    // 默认语言
        'LANG_AUTO_DETECT'    => true,    // 自动侦测语言
        'VAR_LANGUAGE'        => 'l', // 默认语言切换变量
        'LANG_EXPIRE'        => 30*86400, // 默认语言生存时间

        //极光消息推送,这是测试环境账号，如果上线要换成生产环境
        'PUSH' => array( 
            'APP_KEY' => '227de5b92e7ddf2c8303ce79',
            'MASTER_SECRET' => 'f7ac3a9ba849cc10ebbff625',
        ),

        //图片路径
        'PIC_DOMAIN'                => $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"].'/' ,
    //rsa秘钥公钥
    'WEB_PRIVATE_KEY' => '-----BEGIN PRIVATE KEY-----
MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAO6YoP9jDj2xGoY+
AltBdjl2+dDkuQW8akjN08YoGSuZ2Ri11DOYulmuHF42NgV9ZlhT09fFpnQueklA
vP73o0A6BXrKYKDxBxUktSx325kge7ZBKp0UMVK3RV3Fb4uG1Qn0eWMorlgK8+Y+
MV4lVcvlKeAxTqjPaZFyQMb1jm3zAgMBAAECf2O5Z5FW1ZzzaSKyyElcEw3xrHij
ILJFDidf4CPynpKauyKY3RizvS++lhzKi8m/oAdLAkAtXGUzB+mWJWhsGM7w0Sw2
b+3wcaEnlG6LleIB1/2MLtShaSFg5ViQtCCAa5vXFbFwjS5FOa7NHEvj6UFS5/gg
1A6IxnIC+RVhuTkCQQD7PoWhaxC9zgqiPrCUeSRpUfkI79FJQCxf77fsrCofgGjI
DMYBMsKIagn5DpO6j+WLRjNBxvDqahMKIeC3SzxtAkEA8xzRM3ZraVuRc5tcfHb2
4uH4GLZNb3LrCFGFpAJSjrGsg0nXtnLsER5PDDWyfWqizFg1Oi7AmNbeniXZbpMX
3wJBAIywry38AWz4IzZFeqY5zCz6DUV23bByMicq2si6hAAN7R8RIBPts8el8Z25
Dvqqt7StA+jymVf7PFwxKmAZzmUCQQCH6+emzyF0kUZ8DTOjPtv/s3kDUAFxOx3e
071VGhtQPlQGPyXEkvIbDAgD+o2lgxYsC3EO646wpQloAB9VrBWfAkB+EFKuW7zQ
JCfF58xVZsolaTlTw2/c7SHM1CfdO87XFKSFtyLVXpGb+hmOXchu+avk/FbKGNOa
FVNrb4zOiYed
-----END PRIVATE KEY-----',
    'WEB_PUBLIC_KEY' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDumKD/Yw49sRqGPgJbQXY5dvnQ
5LkFvGpIzdPGKBkrmdkYtdQzmLpZrhxeNjYFfWZYU9PXxaZ0LnpJQLz+96NAOgV6
ymCg8QcVJLUsd9uZIHu2QSqdFDFSt0VdxW+LhtUJ9HljKK5YCvPmPjFeJVXL5Sng
MU6oz2mRckDG9Y5t8wIDAQAB
-----END PUBLIC KEY-----',


    //app币种图标，APP只能访问pt环境的图标，只能写死
    'app_index_log' => 'https://www.btcsale.com/Public/AppTrade/price_index/' ,
    'app_pull_checked_log' => 'https://www.btcsale.com/Public/AppTrade/price_index/' ,
    'app_pull_nochecked_log' => 'https://www.btcsale.com/Public/AppTrade/pull/' ,
    'night_log' => 'https://www.btcsale.com/Public/AppTrade/night/' ,
    'app_img_url' => 'http://192.168.2.228:1338/' , //APP图片路径
    
    'cli_push_order' => 'http://nat1.target.com/curl/pushOrderMsg/id/%d/type/%s/pass/~!@123456push',
    //swooleCommand任务 IP端口
    'PRO' =>[
        'HOST_IP_WEB_SOCKET_CLIENT'     =>'0.0.0.0',  //swooleServer host
        'HOST_IP_WEB_CLIENT'     =>'0.0.0.0',  //WebSocketPushClient host
        'HOST_IP_NO_OUTSIDE'            =>'0.0.0.0',  //9511  9512  写内网IP      
      
        'PORT_CLIENT_USER'         =>9503,            //WebSocketPushClient   (swooleserver $push_port)定时任务退市
        'PORT_ORDER_MSG'           =>9511,	          //建强 p2p c2c交易定时推送买家付款
        'PORT_CUREE_MATCH'         =>9512,            //CurrencyTradeClient 匹配订单
        'PORT_WEBSOCKET_SERVER'    =>9502,            //swooleserver $server_port websocket服务
    ],

    'smtp' => [
		"河北","山西","辽宁","吉林","黑龙","江苏","浙江",
        "安徽","福建","江西","山东","河南","湖北","湖南",
        "广东","海南","四川","贵州","云南","陕西","甘肃",
        "青海","台湾","内蒙","广西","西藏","宁夏","新疆",
        "北京","天津","上海","重庆","香港","澳门"

    ], 
	'count' => [
        'emailHost' => 'smtp.gmail.com',
        'formName' => 'SpaceFinEX',

        'emailPassWord' => 'Xiongmao88',
        'emailUserName' => 'spacefinex@gmail.com',


    ]

);
