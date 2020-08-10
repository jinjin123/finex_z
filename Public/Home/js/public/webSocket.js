var server_url = 'ws://47.57.131.217:9502'; //WebSocket路径
var Inner_socket = {
    msg: null,
    ws: {},
    heartbeat_timer: null,
    last_health: -1,
    health_timeout: 60000,
    client_id: Date.parse(new Date()),
    config: {
        'server': server_url,
        'flash_websocket': true
    },
    disConnect_type: 0,
    disConnect: null
};
$(document).ready(function() {
    //使用原生WebSocket
    if (window.WebSocket || window.MozWebSocket) {
        Inner_socket.ws = new WebSocket(Inner_socket.config.server);
    }
    //使用flash websocket
    else if (Inner_socket.config.flash_websocket) {
        WEB_SOCKET_SWF_LOCATION = "./flash-websocket/WebSocketMain.swf";
        $.getScript("./flash-websocket/swfobject.js", function() {
            $.getScript("./flash-websocket/web_socket.js", function() {
                Inner_socket.ws = new WebSocket(Inner_socket.config.server);
            });
        });
    }
    //使用http xhr长轮循
    else {
        window.setInterval(function() {
            var currencyId = $('#coinMoneyList li.active').attr('currency-id');
            currencyId = currencyId ? currencyId : $('#coinMoneyList li:nth-child(1)').attr('currency-id');
            target_webgetCoin(currencyId);

        }, 20000);
        Inner_socket.ws = new Comet(Inner_socket.config.server);
    }
    listenEvent();
});

/**
 * 连接建立时触发的操作
 */
function listenEvent() {
    Inner_socket.ws.onopen = function(e) {
        target_web('join', Inner_socket.client_id, 1, 'pcCoinInfoList');
        Inner_socket.heartbeat_timer = setInterval(function() {
            keepalive();
        }, 10000); //每10秒发个心跳包
    };
    //每次访问传各个币种的市值合成一个数组
    var KlineArr = [];
    var rememberIndex = "";
    Inner_socket.ws.onmessage = function(e) {
        //获取数据
        var message = JSON.parse(e.data);
        var cmd = message.data.method;
        var service_name = message.data.service_name;
        // 连接中
        if (cmd == 'connection') {
            if (message.data.status == 1) {}
        }
        // 接收到推送的信息
        else if (cmd == 'push' && service_name == 'pcCoinInfoList') {
            var server_data = message.data.data;
            //币种下架刷新页面
            coin_Lowershelf(server_data);
            setGMV(server_data);
            var we = setCoinSocket(server_data);
            //每次访问传各个币种的颜色合成一个数组
            if (rememberIndex == "") {
                rememberIndex = we.length;
                for (var i = 0; i < we.length; i++) {
                    KlineArr.push({});
                    KlineArr[i].coinName = we[i].coinName;
                    KlineArr[i].arr = [];
                    KlineArr[i].arr.push(0);
                }
            } else {
                rememberIndex = rememberIndex;
            }
            if (rememberIndex == we.length) {
                for (var k = 0; k < we.length; k++) {
                    if (KlineArr[k].arr.length >= 10) {
                        KlineArr[k].arr.shift();
                    }
                    KlineArr[k].arr.push(we[k].lastUsa);
                    KlineArr[k].color = we[k].color;
                    KlineArr[k].id = we[k].currencyID;
                    KlineArr[k].arrow = we[k].arrow;
                    KlineArr[k].lastUsa = we[k].lastUsa;
                    KlineArr[k].html = we[k].html;
                }
            }
            for (var j = 0; j < KlineArr.length; j++) {
                $("#tab-underline-home3 .quoteprice_box[box-id = " + KlineArr[j].id + "] .kline").sparkline(KlineArr[j].arr, {
                    type: 'line',
                    spotColor: KlineArr[j].color,
                    lineColor: '#ffffff',
                    fillColor: false,
                    minSpotColor: KlineArr[j].color,
                    maxSpotColor: KlineArr[j].color,
                });
            }
        }
    };

    /**
     * 连接关闭事件
     */
    Inner_socket.ws.onclose = function(e) {
        /**
         *   连接关闭时执行
         */
        // websocket断开提示弹窗出现
        $(".websocketWarm").modal("show");
        clearInterval(Inner_socket.heartbeat_timer);
        Inner_socket.disConnect = setInterval(function() {
            //如果已连接则不重连,return 跳出函数, 清除定时器
            Inner_socket.last_health = -1;
            Inner_socket.disConnect_type++;
            clearInterval(Inner_socket.disConnect);
            if (Inner_socket.disConnect_type == 3) {
                window.setInterval(function() {
                    var currencyId = $('#coinMoneyList li.active').attr('currency-id');
                    currencyId = currencyId ? currencyId : $('#coinMoneyList li:nth-child(1)').attr('currency-id');
                    target_webgetCoin(currencyId);
                }, 20000);
            } else {
                if (Inner_socket.ws.readyState == 1) {
                    $(".websocketWarm").modal("hide");
                    return;
                }
                // 断开连接则重新连接并执行操作
                Inner_socket.ws = new WebSocket(server_url);
                listenEvent();
            }
        }, 5000);
    };

    /**
     * 异常事件
     */
    Inner_socket.ws.onerror = function(e) {
        $(".pubArea").hide();
    };
}

/**
 *   发送数据 onpen公共
 */
function target_web(type, data, num, service_name) {
    Inner_socket.msg = {
        "method": type,
        "uid": data,
        "hobby": num,
        "service_name": service_name
    };
    Inner_socket.ws.send(JSON.stringify(Inner_socket.msg));
}

/**
 * 公共币种信息列表(打竖第一块)的实时成交总额(简称GMV)板块
 * @author 2018-3-20T16:06:56+0800
 * @param  {array} data [description]
 * @return string
 */
function setGMV(data) {
    // 获取当前所选币种currecy-id
    var coinCurrecyId = $("#coinMoneyList li.active").attr("currency-id");
    coinCurrecyId = coinCurrecyId ? coinCurrecyId : $('#coinMoneyList li:nth-child(1)').attr('currency-id');

    $("#coinHeighMoney").html(data[coinCurrecyId].high_usa);
    $("#coinLowMoney").html(data[coinCurrecyId].low_usa);
    $("#coinRtqMoney").html(data[coinCurrecyId].last_usa);
    $("#coinTotalMoney").html(data[coinCurrecyId].money_usa);
    if (/CtoCTransaction/.test(window.location.href)) {
        //实时价格
        $('.release-rtq').text('$' + ChangeFixed(data[coinCurrecyId].last_usa, 3));
        $('.release-buyprice').val(ChangeFixed(data[coinCurrecyId].last_usa, 3));
        $('.release-sellprice').val(ChangeFixed(data[coinCurrecyId].last_usa, 3));
        //获取汇率运算
        release_hl($('#order-buyrelease'));
        //买入卖出 价格
    }
    if (/UserCenter/.test(window.location.href)) {
        var areaId = $('#userArea').val();
        var priceArr = getPriceBychangeCoin(areaId);
        var totalPrice = ChangeFixed(data[coinCurrecyId].last_usa, 3) * priceArr[1];
        // 如果是台湾截取整数
        if (areaId == 2) {
            totalPrice = ChangeFixed(totalPrice, 1) + '00';
        }
        totalPrice = ChangeFixed(totalPrice, 3);
        $('#referenceMoney').html(totalPrice); // 计算当前地区的价格
        $('#nowMoney').html(ChangeFixed(data[coinCurrecyId].last_usa, 3)); // 计算当前价格
    }
}

/**
 * 更新k线图
 * @author 2017-10-25T16:06:56+0800
 * @param  {array} data [description]
 * @return string
 */
function setCoinSocket(data) {
    var result = [];
    var arrNum = [];
    for (var i in data) {
        result.push(data[i]);
    }
    // 循环修改每条tr的相对td的值
    $.each(result, function(i, val) {
        var color, arrow;
        if (val.perc_status > 0) {
            color = '#4bd2b7';
            arrow = "fa fa-long-arrow-up";
        } else {
            color = '#e8653b';
            arrow = "fa fa-long-arrow-down";
        }
        var html = '' + val.perc_per + '  <i class="' + arrow + '"></i>';
        $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_nowprice div:eq(0)").css({ color: color }).text(val.last_usa);
        $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_volume .vol_font").css({ color: color }).html(html);
        $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_volume div:eq(0) .font11").text("Volume:" + val.num + " " + val.coin_name + "");
        arrNum.push({});
        arrNum[i].coinName = val.coin_name;
        arrNum[i].lastUsa = val.last_usa;
        arrNum[i].color = color;
        arrNum[i].currencyID = val.currency_id;
        arrNum[i].arrow = arrow;
        arrNum[i].html = html;
    });
    var arr = arrNum;
    return arr;
}
//定时发送握手心跳包
function keepalive() {
    var time = new Date();
    var get_time = time.getTime();
    var out_time = get_time - Inner_socket.last_health;
    if (Inner_socket.last_health != -1 && (out_time > Inner_socket.health_timeout)) {
        //连接断开，可设置重连或者关闭连接
        Inner_socket.ws.close();
    } else {
        if (Inner_socket.ws.readyState === 1) {
            Inner_socket.msg = {
                "method": 'heartbeat'
            };
            Inner_socket.ws.send(JSON.stringify(Inner_socket.msg));
            Inner_socket.last_health = get_time;
        }
    }
}
/**
 * 获取币种信息列表
 * @author 2017-10-25T16:12:18+0800
 */
function target_webgetCoin(currencyId) {
    $.ajax({
        cache: true,
        type: 'post',
        url: '/CoinTradeInfo/getCoinInfoList',
        data: {
            'currencyId': currencyId
        },
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            setCoinSocket(response.coinInfoList);
            setGMV(response.coinInfoList); // 获取选中币种实时价格及交易额
        }
    });
}

// 监听窗口关闭事件，当窗口关闭时，主动去关闭websocket连接，防止连接还没断开就关闭窗口，server端会抛异常。
window.onbeforeunload = function() {
    Inner_socket.ws.close();
}