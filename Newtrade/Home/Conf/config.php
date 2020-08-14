<?php
return array(
    //'配置项'=>'配置值'
    'LOAD_EXT_CONFIG' => 'sysconfigdb',            //数据库配置

    'TMPL_PARSE_STRING' => array(
        '__HOME__' => 'https://d27u2p8o1zgaev.cloudfront.net/Public/Home',
        '__HOME_CSS__' => '/Public/Home/css',
        '__HOME_JS__' => '/Public/Home/js',
        '__HOME_IMG__' => '/Public/Home/img',
        '__HOME_PLUG__' => '/Public/Home/plugins',
        '__HOME_CHART__' => '/Public/Home/charting_library',
        '__CDN1__'=>'https://d27u2p8o1zgaev.cloudfront.net', // For images
        '__CDN2__'=>'https://d27u2p8o1zgaev.cloudfront.net', // For other files
        '__CDN3__'=>'https://d16madtmbsmyfy.cloudfront.net', // for fonts files
    // CDN路径最后不要有 '/'
    ),
    'DEFAULT_CONTROLLER' => 'Index', // 默认控制器名称
    'DEFAULT_ACTION' => 'index', // 默认操作名称
    'DB_FIELDS_CACHE' => false,       //////关闭数据库字段缓存


    //  'ERROR_PAGE'  =>'./Newtrade/Home/View/Public/404Transfer.html', // 默认错误跳转对应的模板文件'
    //  'ERROR_PAGE'=>'/Index/error',//默认错误跳转对应的模板文件'

    //多语言配置项
    'LANG_SWITCH_ON' => true,        //开启多语言支持开关
    'LANG_LIST' => 'en-us,zh-cn,zh-tw', // 允许切换的语言列表 用逗号分隔
    'DEFAULT_LANG' => 'zh-tw',    // 默认语言
    'LANG_AUTO_DETECT' => true,    // 自动侦测语言
    'VAR_LANGUAGE' => 'l', // 默认语言切换变量
    'LOAD_EXT_FILE' => 'myfunction',
    'LOGIN_EXPIRE' => 24 * 60,// 登陆过期时间24分钟

);
