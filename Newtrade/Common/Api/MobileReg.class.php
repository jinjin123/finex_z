<?php
/**
 * 国际手机号码正则验证
 * @author lirunqing   2017-11-10 16:59:41
 */
namespace Common\Api;

class MobileReg{
	 /**
     * 验证规则
     */
    static $validator_rules = array(
        86 => 
          array(
            'value' => 'CN',
            'mobileCode' => '86',
            'cnName' => '中国大陆',
            'enName' => 'China',
            'RE' => '/^(86){0,1}\-?1[3,4,5,7,8]\d{9}$/',
            'isTop' => true,
          ),
        852 => 
          array(
            'value' => 'HK',
            'mobileCode' => '852',
            'cnName' => '香港',
            'enName' => 'Hong Kong',
            // 'RE' => '/^(852){1}\-?0{0,1}[1,5,6,9](?:\d{7}|\d{8}|\d{12})$/',
            //'RE' => '/^(5[1-6,9]|6[0-9]|9[0-8])\d{6}$/',
            'RE' => '/^(852)(5[1-6,9]|6[0-9]|9[0-8])\d{6}$/',  //加区号匹配 2019年4月12日
            'isTop' => true,
          ),
        853 => 
          array(
            'value' => 'MO',
            'mobileCode' => '853',
            'cnName' => '澳门',
            'enName' => 'Macau',
            'RE' => '/^(853){1}\-?6\d{7}$/',
            'isTop' => true,
          ),
        886 => 
          array(
            'value' => 'TW',
            'mobileCode' => '886',
            'cnName' => '台湾',
            'enName' => 'Taiwan',
            // 'RE' => '/^(886){1}\-?0{0,1}[6,7,9](?:\d{7}|\d{8}|\d{10})$/',
            'RE'    => '/^([-_－—\s\(]?)([\(]?)((((0?)|((00)?))(((\s){0,2})|([-_－—\s]?)))|(([\)]?)[+]?))(886)?([\)]?)([-_－—\s]?)([\(]?)[0]?[1-9]{1}([-_－—\s\)]?)[0-9]{2}[-_－—]?[0-9]{3}[-_－—]?[0-9]{3}$/',
            'isTop' => true,
          ),
        855 => 
          array(
            'value' => 'KH',
            'mobileCode' => '855',
            'cnName' => '柬埔寨',
            'enName' => 'Cambodia',
            'group' => '亚洲',
            'RE' => '/^(855){1}\-?\d{7,11}/',
          ),
        91 => 
          array(
            'value' => 'IN',
            'mobileCode' => '91',
            'cnName' => '印度',
            'enName' => 'India',
            'group' => '亚洲',
            'RE' => '/^(91){1}\-?\d{7,11}/',
          ),
        62 => 
          array(
            'value' => 'ID',
            'mobileCode' => '62',
            'cnName' => '印度尼西亚',
            'enName' => 'Indonesia',
            'group' => '亚洲',
            'RE' => '/^(62){1}\-?[2-9]\d{7,11}$/',
          ),
        972 => 
          array(
            'value' => 'IL',
            'mobileCode' => '972',
            'cnName' => '以色列',
            'enName' => 'Israel',
            'group' => '亚洲',
            'RE' => '/^(972){1}\-?\d{7,11}/',
          ),
        81 => 
          array(
            'value' => 'JP',
            'mobileCode' => '81',
            'cnName' => '日本',
            'enName' => 'Japan',
            'group' => '亚洲',
            'RE' => '/^(81){1}\-?0{0,1}[7,8,9](?:\d{8}|\d{9})$/',
          ),
        962 => 
          array(
            'value' => 'JO',
            'mobileCode' => '962',
            'cnName' => '约旦',
            'enName' => 'Jordan',
            'group' => '亚洲',
            'RE' => '/^(962){1}\-?\d{7,11}/',
          ),
        996 => 
          array(
            'value' => 'KG',
            'mobileCode' => '996',
            'cnName' => '吉尔吉斯斯坦',
            'enName' => 'Kyrgyzstan',
            'group' => '亚洲',
            'RE' => '/^(996){1}\-?\d{7,11}/',
          ),
        60 => 
          array(
            'value' => 'MY',
            'mobileCode' => '60',
            'cnName' => '马来西亚',
            'enName' => 'Malaysia',
            'group' => '亚洲',
            'RE' => '/^(60){1}\-?1\d{8,9}$/',
          ),
        960 => 
          array(
            'value' => 'MV',
            'mobileCode' => '960',
            'cnName' => '马尔代夫',
            'enName' => 'Maldives',
            'group' => '亚洲',
            'RE' => '/^(960){1}\-?\d{7,11}/',
          ),
        976 => 
          array(
            'value' => 'MN',
            'mobileCode' => '976',
            'cnName' => '蒙古',
            'enName' => 'Mongolia',
            'group' => '亚洲',
            'RE' => '/^(976){1}\-?\d{7,11}/',
          ),
        63 => 
          array(
            'value' => 'PH',
            'mobileCode' => '63',
            'cnName' => '菲律宾',
            'enName' => 'Philippines',
            'group' => '亚洲',
            'RE' => '/^(63){1}\-?[24579](\d{7,9}|\d{12})$/',
          ),
        974 => 
          array(
            'value' => 'QA',
            'mobileCode' => '974',
            'cnName' => '卡塔尔',
            'enName' => 'Qatar',
            'group' => '亚洲',
            'RE' => '/^(974){1}\-?\d{7,11}/',
          ),
        966 => 
          array(
            'value' => 'SA',
            'mobileCode' => '966',
            'cnName' => '沙特阿拉伯',
            'enName' => 'Saudi Arabia',
            'group' => '亚洲',
            'RE' => '/^(966){1}\-?\d{7,11}/',
          ),
        65 => 
          array(
            'value' => 'SG',
            'mobileCode' => '65',
            'cnName' => '新加坡',
            'enName' => 'Singapore',
            'group' => '亚洲',
            'RE' => '/^(65){1}\-?[13689]\d{6,7}$/',
          ),
        82 => 
          array(
            'value' => 'KR',
            'mobileCode' => '82',
            'cnName' => '韩国',
            'enName' => 'South Korea',
            'group' => '亚洲',
            'RE' => '/^(82){1}\-?0{0,1}[7,1](?:\d{8}|\d{9})$/',
          ),
        94 => 
          array(
            'value' => 'LK',
            'mobileCode' => '94',
            'cnName' => '斯里兰卡',
            'enName' => 'Sri Lanka',
            'group' => '亚洲',
            'RE' => '/^(94){1}\-?\d{7,11}/',
          ),
        90 => 
          array(
            'value' => 'TR',
            'mobileCode' => '90',
            'cnName' => '土耳其',
            'enName' => 'Turkey',
            'group' => '亚洲',
            'RE' => '/^(90){1}\-?\d{7,11}/',
          ),
        66 => 
          array(
            'value' => 'TH',
            'mobileCode' => '66',
            'cnName' => '泰国',
            'enName' => 'Thailand',
            'group' => '亚洲',
            'RE' => '/^(66){1}\-?[13456789]\d{7,8}$/',
          ),
        971 => 
          array(
            'value' => 'AE',
            'mobileCode' => '971',
            'cnName' => '阿联酋',
            'enName' => 'United Arab Emirates',
            'group' => '亚洲',
            'RE' => '/^(971){1}\-?\d{7,11}/',
          ),
        84 => 
          array(
            'value' => 'VN',
            'mobileCode' => '84',
            'cnName' => '越南',
            'enName' => 'Vietnam',
            'group' => '亚洲',
            'RE' => '/^(84){1}\-?[1-9]\d{6,9}$/',
          ),
        43 => 
          array(
            'value' => 'AT',
            'mobileCode' => '43',
            'cnName' => '奥地利',
            'enName' => 'Austria',
            'group' => '欧洲',
            'RE' => '/^(43){1}\-?\d{7,11}/',
          ),
        375 => 
          array(
            'value' => 'BY',
            'mobileCode' => '375',
            'cnName' => '白俄罗斯',
            'enName' => 'Belarus',
            'group' => '欧洲',
            'RE' => '/^(375){1}\-?\d{7,11}/',
          ),
        32 => 
          array(
            'value' => 'BE',
            'mobileCode' => '32',
            'cnName' => '比利时',
            'enName' => 'Belgium',
            'group' => '欧洲',
            'RE' => '/^(32){1}\-?\d{7,11}/',
          ),
        359 => 
          array(
            'value' => 'BG',
            'mobileCode' => '359',
            'cnName' => '保加利亚',
            'enName' => 'Bulgaria',
            'group' => '欧洲',
            'RE' => '/^(359){1}\-?\d{7,11}/',
          ),
        45 => 
          array(
            'value' => 'DK',
            'mobileCode' => '45',
            'cnName' => '丹麦',
            'enName' => 'Denmark',
            'group' => '欧洲',
            'RE' => '/^(45){1}\-?\d{7,11}/',
          ),
        372 => 
          array(
            'value' => 'EE',
            'mobileCode' => '372',
            'cnName' => '爱沙尼亚',
            'enName' => 'Estonia',
            'group' => '欧洲',
            'RE' => '/^(372){1}\-?\d{7,11}/',
          ),
        358 => 
          array(
            'value' => 'FI',
            'mobileCode' => '358',
            'cnName' => '芬兰',
            'enName' => 'Finland',
            'group' => '欧洲',
            'RE' => '/^(358){1}\-?\d{7,11}/',
          ),
        33 => 
          array(
            'value' => 'FR',
            'mobileCode' => '33',
            'cnName' => '法国',
            'enName' => 'France',
            'group' => '欧洲',
            'RE' => '/^(33){1}\-?[168](\d{5}|\d{7,8})$/',
          ),
        49 => 
          array(
            'value' => 'DE',
            'mobileCode' => '49',
            'cnName' => '德国',
            'enName' => 'Germany',
            'group' => '欧洲',
            'RE' => '/^(49){1}\-?1(\d{5,6}|\d{9,12})$/',
          ),
        30 => 
          array(
            'value' => 'GR',
            'mobileCode' => '30',
            'cnName' => '希腊',
            'enName' => 'Greece',
            'group' => '欧洲',
            'RE' => '/^(30){1}\-?\d{7,11}/',
          ),
        36 => 
          array(
            'value' => 'HU',
            'mobileCode' => '36',
            'cnName' => '匈牙利',
            'enName' => 'Hungary',
            'group' => '欧洲',
            'RE' => '/^(36){1}\-?\d{7,11}/',
          ),
        353 => 
          array(
            'value' => 'IE',
            'mobileCode' => '353',
            'cnName' => '爱尔兰',
            'enName' => 'Ireland',
            'group' => '欧洲',
            'RE' => '/^(353){1}\-?\d{7,11}/',
          ),
        39 => 
          array(
            'value' => 'IT',
            'mobileCode' => '39',
            'cnName' => '意大利',
            'enName' => 'Italy',
            'group' => '欧洲',
            'RE' => '/^(39){1}\-?[37]\d{8,11}$/',
          ),
        370 => 
          array(
            'value' => 'LT',
            'mobileCode' => '370',
            'cnName' => '立陶宛',
            'enName' => 'Lithuania',
            'group' => '欧洲',
            'RE' => '/^(370){1}\-?\d{7,11}/',
          ),
        352 => 
          array(
            'value' => 'LU',
            'mobileCode' => '352',
            'cnName' => '卢森堡',
            'enName' => 'Luxembourg',
            'group' => '欧洲',
            'RE' => '/^(352){1}\-?\d{7,11}/',
          ),
        31 => 
          array(
            'value' => 'NL',
            'mobileCode' => '31',
            'cnName' => '荷兰',
            'enName' => 'Netherlands',
            'group' => '欧洲',
            'RE' => '/^(31){1}\-?6\d{8}$/',
          ),
        47 => 
          array(
            'value' => 'NO',
            'mobileCode' => '47',
            'cnName' => '挪威',
            'enName' => 'Norway',
            'group' => '欧洲',
            'RE' => '/^(47){1}\-?\d{7,11}/',
          ),
        48 => 
          array(
            'value' => 'PL',
            'mobileCode' => '48',
            'cnName' => '波兰',
            'enName' => 'Poland',
            'group' => '欧洲',
            'RE' => '/^(48){1}\-?\d{7,11}/',
          ),
        351 => 
          array(
            'value' => 'PT',
            'mobileCode' => '351',
            'cnName' => '葡萄牙',
            'enName' => 'Portugal',
            'group' => '欧洲',
            'RE' => '/^(351){1}\-?\d{7,11}/',
          ),
        40 => 
          array(
            'value' => 'RO',
            'mobileCode' => '40',
            'cnName' => '罗马尼亚',
            'enName' => 'Romania',
            'group' => '欧洲',
            'RE' => '/^(40){1}\-?\d{7,11}/',
          ),
        7 => 
          array(
            'value' => 'RU',
            'mobileCode' => '7',
            'cnName' => '俄罗斯',
            'enName' => 'Russia',
            'group' => '欧洲',
            'RE' => '/^(7){1}\-?[13489]\d{9,11}$/',
          ),
        381 => 
          array(
            'value' => 'RS',
            'mobileCode' => '381',
            'cnName' => '塞尔维亚',
            'enName' => 'Serbia',
            'group' => '欧洲',
            'RE' => '/^(381){1}\-?\d{7,11}/',
          ),
        34 => 
          array(
            'value' => 'ES',
            'mobileCode' => '34',
            'cnName' => '西班牙',
            'enName' => 'Spain',
            'group' => '欧洲',
            'RE' => '/^(34){1}\-?\d{7,11}/',
          ),
        46 => 
          array(
            'value' => 'SE',
            'mobileCode' => '46',
            'cnName' => '瑞典',
            'enName' => 'Sweden',
            'group' => '欧洲',
            'RE' => '/^(46){1}\-?[124-7](\d{8}|\d{10}|\d{12})$/',
          ),
        41 => 
          array(
            'value' => 'CH',
            'mobileCode' => '41',
            'cnName' => '瑞士',
            'enName' => 'Switzerland',
            'group' => '欧洲',
            'RE' => '/^(41){1}\-?\d{7,11}/',
          ),
        380 => 
          array(
            'value' => 'UA',
            'mobileCode' => '380',
            'cnName' => '乌克兰',
            'enName' => 'Ukraine',
            'group' => '欧洲',
            'RE' => '/^(380){1}\-?[3-79]\d{8,9}$/',
          ),
        44 => 
          array(
            'value' => 'GB',
            'mobileCode' => '44',
            'cnName' => '英国',
            'enName' => 'United Kingdom',
            'group' => '欧洲',
            'RE' => '/^(44){1}\-?[347-9](\d{8,9}|\d{11,12})$/',
          ),
        54 => 
          array(
            'value' => 'AR',
            'mobileCode' => '54',
            'cnName' => '阿根廷',
            'enName' => 'Argentina',
            'group' => '美洲',
            'RE' => '/^(54){1}\-?\d{7,11}/',
          ),
        1242 => 
          array(
            'value' => 'BS',
            'mobileCode' => '1242',
            'cnName' => '巴哈马',
            'enName' => 'Bahamas',
            'group' => '美洲',
            'RE' => '/^(1242){1}\-?\d{7,11}/',
          ),
        501 => 
          array(
            'value' => 'BZ',
            'mobileCode' => '501',
            'cnName' => '伯利兹',
            'enName' => 'Belize',
            'group' => '美洲',
            'RE' => '/^(501){1}\-?\d{7,11}/',
          ),
        55 => 
          array(
            'value' => 'BR',
            'mobileCode' => '55',
            'cnName' => '巴西',
            'enName' => 'Brazil',
            'group' => '美洲',
            'RE' => '/^(55){1}\-?\d{7,11}/',
          ),
        1 => 
          array(
            'value' => 'US',
            'mobileCode' => '1',
            'cnName' => '美国',
            'enName' => 'United States',
            'group' => '美洲',
            'RE' => '/^(1){1}\-?\d{10,12}$/',
          ),
        56 => 
          array(
            'value' => 'CL',
            'mobileCode' => '56',
            'cnName' => '智利',
            'enName' => 'Chile',
            'group' => '美洲',
            'RE' => '/^(56){1}\-?\d{7,11}/',
          ),
        57 => 
          array(
            'value' => 'CO',
            'mobileCode' => '57',
            'cnName' => '哥伦比亚',
            'enName' => 'Colombia',
            'group' => '美洲',
            'RE' => '/^(57){1}\-?\d{7,11}/',
          ),
        52 => 
          array(
            'value' => 'MX',
            'mobileCode' => '52',
            'cnName' => '墨西哥',
            'enName' => 'Mexico',
            'group' => '美洲',
            'RE' => '/^(52){1}\-?\d{7,11}/',
          ),
        507 => 
          array(
            'value' => 'PA',
            'mobileCode' => '507',
            'cnName' => '巴拿马',
            'enName' => 'Panama',
            'group' => '美洲',
            'RE' => '/^(507){1}\-?\d{7,11}/',
          ),
        51 => 
          array(
            'value' => 'PE',
            'mobileCode' => '51',
            'cnName' => '秘鲁',
            'enName' => 'Peru',
            'group' => '美洲',
            'RE' => '/^(51){1}\-?\d{7,11}/',
          ),
        58 => 
          array(
            'value' => 'VE',
            'mobileCode' => '58',
            'cnName' => '委内瑞拉',
            'enName' => 'Venezuela',
            'group' => '美洲',
            'RE' => '/^(58){1}\-?\d{7,11}/',
          ),
        1284 => 
          array(
            'value' => 'VG',
            'mobileCode' => '1284',
            'cnName' => '英属维尔京群岛',
            'enName' => 'Virgin Islands, British',
            'group' => '美洲',
            'RE' => '/^(1284){1}\-?\d{7,11}/',
          ),
        20 => 
          array(
            'value' => 'EG',
            'mobileCode' => '20',
            'cnName' => '埃及',
            'enName' => 'Egypt',
            'group' => '非洲',
            'RE' => '/^(20){1}\-?\d{7,11}/',
          ),
        212 => 
          array(
            'value' => 'MA',
            'mobileCode' => '212',
            'cnName' => '摩洛哥',
            'enName' => 'Morocco',
            'group' => '非洲',
            'RE' => '/^(212){1}\-?\d{7,11}/',
          ),
        234 => 
          array(
            'value' => 'NG',
            'mobileCode' => '234',
            'cnName' => '尼日利亚',
            'enName' => 'Nigeria',
            'group' => '非洲',
            'RE' => '/^(234){1}\-?\d{7,11}/',
          ),
        248 => 
          array(
            'value' => 'SC',
            'mobileCode' => '248',
            'cnName' => '塞舌尔',
            'enName' => 'Seychelles',
            'group' => '非洲',
            'RE' => '/^(248){1}\-?\d{7,11}/',
          ),
        27 => 
          array(
            'value' => 'ZA',
            'mobileCode' => '27',
            'cnName' => '南非',
            'enName' => 'South Africa',
            'group' => '非洲',
            'RE' => '/^(27){1}\-?\d{7,11}/',
          ),
        216 => 
          array(
            'value' => 'TN',
            'mobileCode' => '216',
            'cnName' => '突尼斯',
            'enName' => 'Tunisia',
            'group' => '非洲',
            'RE' => '/^(216){1}\-?\d{7,11}/',
          ),
        61 => 
          array(
            'value' => 'AU',
            'mobileCode' => '61',
            'cnName' => '澳大利亚',
            'enName' => 'Australia',
            'group' => '大洋洲',
            'RE' => '/^(61){1}\-?4\d{8,9}$/',
          ),
        64 => 
          array(
            'value' => 'NZ',
            'mobileCode' => '64',
            'cnName' => '新西兰',
            'enName' => 'New Zealand',
            'group' => '大洋洲',
            'RE' => '/^(64){1}\-?[278]\d{7,9}$/',
          ),
        );
}