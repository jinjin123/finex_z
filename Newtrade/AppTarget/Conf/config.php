<?php
return array(
    'LOAD_EXT_CONFIG'  => 'status_code',
    'LOG_RECORD' => false,                   // 开启日志记录
    'DB_SQL_LOG'    =>false,
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR,SQL',  // 只记录EMERG ALERT CRIT ERR 错误
    'APP_PLATFORM' => 'app_target', //APP平台来源的区别， app_watchword（口令APP），app_target（系统APP）
    //多语言配置项
    'LANG_SWITCH_ON'      => true,        //开启多语言支持开关
    'LANG_LIST'           => 'en-us,zh-cn,zh-tw', // 允许切换的语言列表 用逗号分隔
    'DEFAULT_LANG'        => 'zh-tw',    // 默认语言
    'LANG_AUTO_DETECT'    => true,    // 自动侦测语言
    'DB_FIELDS_CACHE'    =>false,       //////关闭数据库字段缓存
    'VAR_LANGUAGE'        => 'l', // 默认语言切换变量
    'LANG_EXPIRE'        => 30*86400, // 默认语言生存时间
    //rsa秘钥公钥
    'APP_PRIVATE_KEY' => '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAL9++D5acqdl1rM5
Kd7RvT6olmMFmCGGKunZcTIk9OAY1MAgQWf9Nst9G8VE+sFqG8KkOWm36gHAZMwb
YDqSnIi3s25uHQXMBoaM7cyfsCwJHtM3WOghZzuv2yZT9hhq2FHLI4M7C7OG3paP
xkS+thRc+dT6HqLbxMe6VGbWSSelAgMBAAECgYEAoiUchY+IbX2poe/RKD5oA1o0
nHvsKNa2F3RqiI8KWYYs/tFREIAzmXHBxfN2b7cs8k6j3oQ/vGPv9XNos6/YoeBq
ksWkO1HL3EhJrJc1AAJrO1ypj0FZF0ZAykxmuXkYjBmhmMJJVD8FIiscb13JUF1l
AElduRBQVvP5xXryDgECQQD+nsDCejEV8MsG4vuK9MfQrpwh4pOXGpT1b3PRkmrz
Gn6cAVH0klEbIyY0XNVykzKpdGNbhEx6Ez0pZ7BoiNtFAkEAwIikOLI8HPb65BH9
rZcZC5BaLqDF1DPY8TBxGyO14hiNqURR2HctRjXs1zOmpYBkX6n93ucXVh3wL5mA
Nc2w4QJABrWqQLW1m21n/Dt5A3Vl2pLvXFk7KG0z5a/VLn2cQeG92mCSh05fwsZP
WGvl2AoW+K4vfBblaQYew3uPA4IvvQJATX+y+s9juxT/cIZ9Yj6L6ke0xUgZ2Yz1
KkChhpcdQ2E2xIenmkZ+huB18TntPnkr7gXzFqJWlmd+oupa3U1qgQJBALGniqHV
6N9BwP9pO3I/jcbbLMJsE7/6kGAjqv7PSQQpCOYX8mjTWwUGf7gYR0tQ1ZewRPWy
mvY3NOZSKdgO024=
-----END PRIVATE KEY-----',

    'APP_PUBLIC_KEY' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC/fvg+WnKnZdazOSne0b0+qJZj
BZghhirp2XEyJPTgGNTAIEFn/TbLfRvFRPrBahvCpDlpt+oBwGTMG2A6kpyIt7Nu
bh0FzAaGjO3Mn7AsCR7TN1joIWc7r9smU/YYathRyyODOwuzht6Wj8ZEvrYUXPnU
+h6i28THulRm1kknpQIDAQAB
-----END PUBLIC KEY-----',
);