'use strict';

(function () {

    var apiData = [
        {
            name: '提币操作接口',
            author: '李江',
            content: '',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "TiBi",
                "ver": "1.0.0",
                "data": {
                    "number": "10",  //提币数量
                    "trade_pwd": "123456",  // 资金密码
                    "phoneCode": "0546", // 手机验证码
                    "currency_id": 1, //币种id
                    "address": "aaaaaaaaaaa",//提币地址
                    "collier_fee": 1//矿工费 提币数量需比矿工费多出至少0.01
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": {
                    "tibi_status": "1" // 发送成功
                }
            },
            successMap: [
                "tibi_status|int|提币状态(0:失败,1:成功)"
            ],
            errorRessult: {
                "data": {},
                "error": "错误码",
                "msg": "错误说明"
            }
        },
        {
            name: '提币记录查看接口',
            author: '李江',
            content: '只显示前30天的操作日志',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "ShowTiBi",
                "ver": "1.0.0",
                "data": {
                    "page": 1,  //当前分页
                    "limit": 10,  //每页显示数量
                    "currency_id": 1, //币种id
                    "status": 0//0为提币中 1为提币成功  2等待提出 -1提币失败
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": {
                    "list": [
                        {
                            "id": "1",  //id
                            "uid": "162",//用户id
                            "url": "sssssssssssssss", //用于提币的地址
                            "add_time": 15445645646,//提币时间
                            "num": 100.00000000,//提币数量
                            "status": -1,//状态 0为提币中 1为提币成功  2等待提出-1提币失败
                            "check_time": 0,//钱包产生提币时间
                            "currency_id": 1,//币种id
                            "fee": 10.00000000,//手续费
                            "actual": 90.00000000,//实际到账数量
                            "currency_name": "BTC" //币种名
                        }
                    ],
                    "pager": {
                        "current_page": "1",
                        "last_page": "",
                        "next_page": "",
                        "total_pages": "1"
                    },
                    "total": 4,//提币记录总数
                }
            },
            successMap: [
                "list|array|列表数组",
                "list[id]|int|id",
                "list[uid]|int|用户id",
                "list[url]|string|用于提币的地址",
                "list[add_time]|int|提币时间",
                "list[num]|number|提币数量",
                "list[status]|int|状态 0为提币中 1为提币成功  2等待提出-1提币失败",
                "list[check_time]|int|钱包产生提币时间",
                "list[currency_id]|int|币种id",
                "list[fee]|number|手续费",
                "list[actual]|number|实际到账数量",
                "list[currency_name]|string|币种名",

                "pager|array|分页数组",
                "pager[current_page]|int|当前页数",
                "pager[last_page]|int|最后一页页数",
                "pager[next_page]|int|下一页页数",
                "pager[total_pages]|int|总页数",

                "total|array|提币记录总数",
            ],
            errorRessult: {
                "data": {},
                "error": "错误码",
                "msg": "错误说明"
            }
        },
        {
            name: '充币记录查看接口',
            author: '李江',
            content: '只显示前30天的操作日志',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "ShowChongBi",
                "ver": "1.0.0",
                "data": {
                    "currency_id": 1,//搜索条件 币种id
                    "status": 1,  //充币记录状态  1为充值中    2为充值成功   3为充值失败
                    "page": 1,  //当前分页
                    "limit": 10,  //每页显示数量
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": {
                    "list": [
                        {
                            "id": "1", //id
                            "uid": "162", //用户id
                            "url": "sssssssssssssss",//冲币地址
                            "add_time": 15445645646,//充币时间
                            "num": 100.00000000,//数量
                            "status": 1,//状态值   1为充值成功  2为充值失败
                            "check_time": 0,
                            "currency_id": 1,//币种id
                            "fee": 10.00000000,//手续费
                            "actual": 90.00000000,//实际到账的数量
                            "currency_name": 'BTC'//币种名
                        }
                    ],
                    "pager": {
                        "current_page": "1",
                        "last_page": "",
                        "next_page": "",
                        "total_pages": "1"
                    },
                    "total": 4 //总记录数
                }
            },
            successMap: [
                "list|array|列表数组",
                "list[id]|int|id",
                "list[uid]|int|用户id",
                "list[url]|string|冲币地址",
                "list[add_time]|int|充币时间",
                "list[num]|number|充币数量",
                "list[status]|int|状态值(1为充值中 2为充值成功 3为充值失败)",
                "list[check_time]|int|钱包产生充币时间",
                "list[currency_id]|int|币种id",
                "list[fee]|number|手续费",
                "list[actual]|number|实际到账数量",
                "list[currency_name]|string|币种名",

                "pager|array|分页数组",
                "pager[current_page]|int|当前页数",
                "pager[last_page]|int|最后一页页数",
                "pager[next_page]|int|下一页页数",
                "pager[total_pages]|int|总页数",

                "total|array|充币记录总数",
            ],
            errorRessult: {
                "data": {},
                "error": "错误码",
                "msg": "错误说明"
            }
        },
        {
            name: '用户余额查看接口',
            author: '李江',
            content: '显示当前登录用户的币种余额',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "BalanceSearch",
                "ver": "1.0.0",
                "data": {
                    "currency_id": 1,  //要查看的币种  可以不填，表示查看所有币种余额情况
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": {
                    "currency_id": 1,//币种id
                    "num": 100.0000, //余额
                    'currency_name': 'BTC',//币种名
                }
            },
            successMap: [
                "currency_id|int|币种id",
                "num|number|余额",
                "currency_name|string|币种名",
            ],
            errorRessult: {
                "data": {},
                "error": 30011,
                "msg": "当前用户没有充值记录"
            }
        },
        {
            name: '获取充币币种列表',
            author: '李江',
            content: '显示系统中所有币种信息',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "GetAllCurrency",
                "ver": "1.0.0",
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        "currency_id": "1",  //币种id
                        "currency_name": "BTC", //币种名
                        "note": [
                            "1.充值需要區塊確認後才能到賬，請耐心等待",
                            "2.任何非BCH資產充入BCH地址後將無法找回"
                        ],
                        "enabled": "1" //是否生效（0:失效,1:生效）
                    },
                    {
                        "currency_id": "2",//币种id
                        "currency_name": "LTC", //币种名
                        "note": [
                            "1.充值需要區塊確認後才能到賬，請耐心等待",
                            "2.任何非BCH資產充入BCH地址後將無法找回"
                        ],
                        "enabled": "1" //是否生效（0:失效,1:生效）
                    },
                ],
            },
            successMap: [
                "currency_id|int|币种id",
                "currency_name|string|币种名",
                "note|array|注意事项数组",
                "enabled|int|是否生效（0:失效,1:生效）",
            ],
            errorRessult: {
                "data": {},
                "error": 30048,
                "msg": "币种正在维护，暂无可用币种"
            }
        },
        {
            name: '获取充币地址',
            author: '李江',
            content: '获取当前充币地址',
            method: 'POST',
            remark: '<p>1.生成二维码逻辑。若币种为<code>BCH</code>、<code>BSV</code>，则使用<code>legacy</code>字段生成二维码；若币种为<code>EOS</code>，则使用<code>eos_memo</code>生成二维码；其余的币种则使用<code>cashaddr</code>生成二维码。</p>' +
                '<p>2.APP判断地址是否为空逻辑。若币种为<code>EOS</code>，则判断<code>eos_memo</code>是否为空；其他币种则使用<code>cashaddr</code>是否为空。</p>',
            requestBody: {
                "token": "xxxx",
                "server": "GetChongBiAddress",
                "ver": "1.0.0",
                "data": {
                    "currency_id": "1",  //币种id
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        'cashaddr': 'sbhdjfkhjfdshjf',
                        'legacy_url': '',
                        'eos_memo': ''
                    },
                ],
            },
            successMap: [
                "cashaddr|string|当前用户绑定的充值地址",
                "legacy|string|旧充币地址（仅bch，bsv有效）",
                "eos_memo|string|eos币种memo（仅eos有效）"
            ],
            errorRessult: {
                "data": {},
                "error": 9999,
                "msg": "操作失败"
            }
        },
        {
            name: '绑定充币地址',
            author: '李江',
            content: '绑定充币地址',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "BindChongBiAddress",
                "ver": "1.0.0",
                "data": {
                    "currency_id": "1",  //币种id
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        'status': 1,//1 表示绑定成功
                        'cashaddr': 'sbhdjfkhjfdshjf',
                        'legacy': '',
                        'eos_memo': ''
                    },
                ],
            },
            successMap: [
                "status|int|1 表示绑定成功",
                "cashaddr|string|当前用户绑定的充值地址",
                "legacy|string|旧充币地址（仅bch，bsv有效）",
                "eos_memo|string|eos币种memo（仅eos有效）"
            ],
            errorRessult: {
                "data": {},
                "error": 9999,
                "msg": "操作失败"
            }
        },
        {
            name: '更新充币地址',
            author: '李江',
            content: '更新充币地址',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "ChangeChongBiAddress",
                "ver": "1.0.0",
                "data": {
                    "currency_id": "1",  //币种id
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        'status': 1,//1 表示绑定成功
                        'cashaddr': 'sbhdjfkhjfdshjf',
                        'legacy': '',
                        'eos_memo': ''
                    },
                ],
            },
            successMap: [
                "status|int|1 表示绑定成功",
                "cashaddr|string|当前用户绑定的充值地址",
                "legacy|string|旧充币地址（仅bch，bsv有效）",
                "eos_memo|string|eos币种memo（仅eos有效）"
            ],
            errorRessult: {
                "data": {},
                "error": 9999,
                "msg": "操作失败"
            }
        },
        {
            name: '获取提币币种列表',
            author: '李江',
            content: '根据currency_id获取币种地址和相应手续费等信息',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "GetCurrencyAddrInfo",
                "ver": "1.0.0",
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        "currency_info": [
                            {
                                "currency_id": 1,//币种id
                                "currency_name": "BTC",
                                "url_list": [
                                    "fdsf4f9sd4f9sdfs6df", //提币地址1
                                    "dsafsdyhgdfjgfk",//提币地址2
                                    "dsagdfhfgjhfgjfg",//提币地址3
                                ],
                                "coin_fee": "0.1000",//手续费比例
                                "max_tibi_number": "1000.00000000",//该币种最大提币数量
                                "enabled": "1",
                                "min_number": "0.00000000"
                            },
                            {
                                "currency_id": 2,//币种id
                                "currency_name": "LTC",
                                "url_list": [
                                    "aaaaaaaaaaa", //提币地址1
                                    "xxxxxxxxxxxx",//提币地址2
                                    "dddddddddddd",//提币地址3
                                ],
                                "coin_fee": "0.1000",//手续费比例
                                "max_tibi_number": "1000.00000000",//该币种最大提币数量
                                "enabled": "0",
                                "min_number": "0.00000000"
                            },
                        ]
                    },
                ],
            },
            successMap: [
                "currency_info|array|币种信息数组",
                "currency_info[currency_id]|int|币种id",
                "currency_info[currency_name]|string|币种名",
                "currency_info[url_list]|array|提币地址数组",
                "currency_info[coin_fee]|number|手续费比例",
                "currency_info[max_tibi_number]|number|该币种最大提币数量",
                "currency_info[enabled]|int|是否生效（0:失效,1:生效）",
                "currency_info[min_number]|number|单笔提币的最少数量"
            ],
            errorRessult: [
                {
                    "data": {},
                    "error": 30020,
                    "msg": "系统繁忙，请稍后再试"
                },
                {
                    "data": {},
                    "error": 30048,
                    "msg": "币种正在维护，暂无可用币种"
                }
            ]
        },
        {
            name: '获取提币手机验证码',
            author: '李江',
            content: '获取提币手机验证码',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "SendPhoneCode",
                "ver": "1.0.0",
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        'status': 1//状态码 1 发送成功
                    },
                ],
            },
            successMap: [
                "status|int|状态码 1 发送成功"
            ],
            errorRessult: {
                "data": {},
                "error": 30013,// 30014 短信发送失败
                "msg": "短信发送频率过快"
            }
        },
        {
            name: '删除提币地址',
            author: '李江',
            content: '删除提币地址',
            method: 'POST',
            remark: '',
            requestBody: {
                "token": "xxxx",
                "server": "DelAddress",
                "ver": "1.0.0",
                "data": {
                    "address": "aaaaaaaaaaaaaa",//需要删除的地址
                    "currency_id": 1,//删除币种对应的地址
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        'is_success': 1//状态码 1 发送成功
                    },
                ],
            },
            successMap: [
                "is_success|int|状态码 1 发送成功"
            ],
            errorRessult: {
                "data": {},
                "error": 9999,
                "msg": "操作失败"
            }
        },
        {
            name: '获取充提币记录的币种列表',
            author: '李江',
            content: '获取所有币种列表',
            method: 'POST',
            remark: '新增，可能存在相同逻辑接口',
            requestBody: {
                "token": "xxxx",
                "server": "GetAllCurrencyHasBalance",
                "ver": "1.0.0",
                "data": {
                    "type": "类型（0充币，1提币）"
                }
            },
            successResult: {
                "error": "0",
                "msg": "success",
                "data": [
                    {
                        "currency_id": "1",  //币种id
                        "currency_name": "BTC", //币种名
                        // "note": [
                        //     "1.充值需要區塊確認後才能到賬，請耐心等待",
                        //     "2.任何非BCH資產充入BCH地址後將無法找回"
                        // ],
                        "enabled": "1" //是否生效（0:失效,1:生效）
                    },
                    {
                        "currency_id": "2",//币种id
                        "currency_name": "LTC", //币种名
                        // "note": [
                        //     "1.充值需要區塊確認後才能到賬，請耐心等待",
                        //     "2.任何非BCH資產充入BCH地址後將無法找回"
                        // ],
                        "enabled": "1" //是否生效（0:失效,1:生效）
                    },
                ],
            },
            successMap: [
                "currency_id|int|币种id",
                "currency_name|string|币种名",
                "note|array|注意事项数组",
                "enabled|int|是否生效（0:失效,1:生效）"
            ],
            errorRessult: {
                "data": {},
                "error": 30048,
                "msg": "币种正在维护，暂无可用币种"
            }
        },
        /*{
            name: '绑定提币地址接口',
            author: '李江',
            content: '绑定提币地址接口',
            method: 'POST',
            remark: '新增，可能存在相同逻辑接口',
            requestBody: {
                "token": "xxxx",
                "server": "bindAddress",
                "ver": "1.0.0",
                "data": {
                    "currency_id": "1",  //币种id
                    "addr_index": "1",
                    "address": "askohfojdfhsadkjfds",
                    "memo": "zxdsaf"
                }
            },
            successResult: {
                "error":"0",
                "msg":"success",
                "data": [
                    {
                        'status': 1,
                    },
                ],
            },
            successMap: [
                "status|int|1 表示绑定成功"
            ],
            errorRessult: {
                "data": {},
                "error": 9999,
                "msg": "操作失败"
            }
        }*/
    ];

    var app = {
        init: function () {
            this.render();
            this.search();
        },
        render: function () {

            var activeIndex = 0;

            var apiDocs = apiData;
            for (var i in apiDocs) {

                var key = Number(i - 0 + 1);

                //导航标签
                var a = $('<a style="cursor: pointer;">' + key + '、' + apiDocs[i].name + '&nbsp;</a>');
                a.addClass('list-group-item');
                if(i == activeIndex) a.addClass('active');
                a.click(function(){
                    $(this).addClass('active').siblings().removeClass('active');
                    $('#api-content').children('.panel').eq($(this).index() - 1).show().siblings().hide();
                });
                $('#nav').find('.list-group').append(a);

                //获取成功返回字段表单
                var getTableHtml = function(){
                    var table = '';
                    if (apiDocs[i].hasOwnProperty('successMap') && apiDocs[i].successMap.length > 0) {
                        var obj = $('<table class="table table-bordered"><tr><th>参数名</th><th>类型</th><th>说明</th></tr></table>');

                        obj.find('tr').eq(0).css({
                            'background-color': 'rgb(64, 158, 255)',
                            'color': '#fff'
                        })

                        for (var j in apiDocs[i].successMap) {
                            var arr = apiDocs[i].successMap[j].split('|');
                            obj.append('<tr><td>' + arr[0] + '</td><td>' + arr[1] + '</td><td>' + arr[2] + '</td></tr>');
                        }
                        table = '<p style="font-weight: bolder;">成功返回说明：</p>' + obj.get(0).outerHTML;
                    }
                    return table;
                };

                //生成内容
                var html = '<div class="panel panel-success" style="display: none;">' +
                    '<div class="panel-heading">' +
                    '<h4><a name="api' + key + '" id="api' + key + '">' + key + '、' + apiDocs[i].name + '</a></h4>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    (apiDocs[i].content == '' ? '' : '<p>说明：' + apiDocs[i].content + '</p>') +
                    '<div class="row"><div class="col-md-9"><p>接口负责人：' + apiDocs[i].author + '</p></div></div>' +
                    '<div class="row"><div class="col-md-9"><p>请求方式：' + apiDocs[i].method + '</p></div></div>' +
                    '<p style="font-weight: bolder;">请求示例：</p><pre>' + JSON.stringify(apiDocs[i].requestBody, null, 4) + '</pre>' +
                    '<p style="font-weight: bolder;">成功返回：</p><pre>' + JSON.stringify(apiDocs[i].successResult, null, 4) + '</pre>' +
                    getTableHtml() +
                    '<p style="font-weight: bolder;">失败返回：（有多种情况）</p><pre>' + JSON.stringify(apiDocs[i].errorRessult, null, 4) + '</pre>' +
                    '</div>' +
                    '<div class="panel-footer"><p>补充: ' + apiDocs[i].remark + '</p></div>' +
                '</div>';
                var copyObj = $(html);

                if(i == activeIndex) copyObj.show();

                $('#api-content').append(copyObj);

            }
        },
        search: function(){
            var apiDocs = apiData;
            $('#search').keyup(function(event){
                var value = $(this).val().toLowerCase();
                if (event.keyCode == 13) {
                    var index = null;
                    for(var i in apiDocs){
                        var _name = apiData[i].name.toLowerCase()
                        var _server = apiData[i].requestBody.server.toLowerCase()
                        if(value == _name || value == _server){
                            index = i;
                        }
                    }
                    index = parseInt(index);
                    if(index >= 0){
                        $('#nav').find('.list-group').children('a').eq(index + 1).trigger('click');
                    }else{
                        alert('无匹配结果');
                    }
                }
            });
        }
    };

    $(function () {
        app.init();
    });

})();
