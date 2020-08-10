<?php
return array(
    'LOAD_EXT_CONFIG'  => 'status_code',
    'LOG_RECORD' => false,                   // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR,SQL',  // 只记录EMERG ALERT CRIT ERR 错误
    'APP_PLATFORM' => 'app_watchword', //APP平台来源的区别， app_watchword（口令APP），app_target（系统APP）
    //多语言配置项
    'LANG_SWITCH_ON'      => true,        //开启多语言支持开关
    'LANG_LIST'           => 'en-us,zh-cn,zh-tw', // 允许切换的语言列表 用逗号分隔
    'DEFAULT_LANG'        => 'zh-tw',    // 默认语言
    'LANG_AUTO_DETECT'    => true,    // 自动侦测语言
    'VAR_LANGUAGE'        => 'l', // 默认语言切换变量
    'LANG_EXPIRE'        => 30*86400, // 默认语言生存时间
    //rsa秘钥公钥
    'APP_PRIVATE_KEY' => '-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMBYyGC86hsajhHz
W5vjHPbbF9j5LBENioeZmlz6WAKonkPwRTFGUJnsVV+jz7jMTzGjfONG8Zboq4kM
NkS+CjKsM+mAiPNZdAEDir0aRZ2VNswK4vUt/3dXwowgeI/S61DXR4pmW7Ot3B3+
GgGnsOLbDmJbLLNgyA1He0P1p/QPAgMBAAECgYA0+z+WhfLmLFZd0260bcaYqJjV
By2ANP6ng0PlwH9lnBScGh61B+1DuLl7cp/RA1X9Ek9gOzZonwZA5cA9Byu/dy40
fZYjtBAu+w6nUF5UjY0euwjoLy8KsUAE8UmOi3kVEVwuK+TEWOsn5SruQcnp/B+D
q5uqBAblOaHwFMwvYQJBAOOdcEx5HF8u/6bUZihi7Vu6zyGGWl5My+XXoLMM4UHu
zH40CxFdmDAhA8WrzJHBspxLkqEerTgM4aI3MNFE7R0CQQDYVWoeEm5J6b+SaF2m
/DOZ8ynHY3rCqYKn0x7qSy5L5o6GGR6r6Q8Z5uHx0xKWL+o3tLCY4U09bD+Mbqal
JhobAkEAoV6aIi1u9vB8IUvOTW2td+4eMVduNBLgL8hKzwYfoT3qzsKY4ivn3J9b
bEYbl26q8XIGt6HnDqjbQsU8H/Fs5QJAeOgkCvKaGU0++IDD9tP1sxEoRHvg3HMI
xutD2AZ0tY8CEQhxD/uNqRhVJ2akeLQG32NpX8hr9uxNCBu/n4WQCQJAEgLsRKv8
CSK56qNiRExuacRkxCiSvbHY+mQPtGN9A9AIGkWVq1xtetof1xSHYOnwrNFDuRa3
I9gZRnSOzQIyBw==
-----END PRIVATE KEY-----
',
    'APP_PUBLIC_KEY' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDAWMhgvOobGo4R81ub4xz22xfY
+SwRDYqHmZpc+lgCqJ5D8EUxRlCZ7FVfo8+4zE8xo3zjRvGW6KuJDDZEvgoyrDPp
gIjzWXQBA4q9GkWdlTbMCuL1Lf93V8KMIHiP0utQ10eKZluzrdwd/hoBp7Di2w5i
WyyzYMgNR3tD9af0DwIDAQAB
-----END PUBLIC KEY-----',
);
