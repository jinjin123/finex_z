var Inner_socket = { msg: null, ws: {}, heartbeat_timer: null, last_health: -1, health_timeout: 60000, client_id: Date.parse(new Date()), config: { 'server': server_url, 'flash_websocket': true }, disConnect_type: 0, disConnect: null };
$(document).ready(function() {
    //使用原生WebSocket
    if (window.WebSocket || window.MozWebSocket) {
        Inner_socket.ws = new WebSocket(server_url);
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
    else {}
    listenEvent();
});

/**
 *  连接建立时触发的操作  首页滚动行情
 */
function listenEvent() {
    Inner_socket.ws.onopen = function(e) {
        target_web('join', Inner_socket.client_id, 1, 'pcMarketInfoList');
        Inner_socket.heartbeat_timer = setInterval(function() {
            keepalive();
        }, 10000); //每10秒发个心跳包
    };

    Inner_socket.ws.onmessage = function(e) {
        var message = JSON.parse(e.data);
        var cmd = message.data.method;
        var service_name = message.data.service_name;
        // 连接中
        if (cmd == 'connection') {
            if (message.data.status == 1) {}
        }
        // 接收到推送的信息
        else if (cmd == 'push' && service_name == 'pcMarketInfoList') {
            var server_data = message.data.data;
            setMarketData(server_data);
        }
    };

    /**
     * 异常事件
     */
    Inner_socket.ws.onerror = function(e) {
        $(".pubArea").hide();
    };
}

/**
 * 发送数据 onpen公共
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
 * @param data  渲染数据 输出模板
 * 
 */
function setMarketData(data) {
    var html = "";
    if (!data) {
        return;
    }
    for (var i in data) {
        // 大屏不滚动
        // if ($(window).width() > 640 && i >= 7) move();

        html += '<div class="ProductView_product-view_1v8XP">';
        html += '<div class="ProductView_details_2mwE3">';
        html += '<p class="ProductView_title_3siVi">' + data[i].currencyName + '/USD</p>';
        if (data[i].upordown == 200) {
            //+涨幅
            html += '<p class="ProductView_price_1Cyoa text-right"><span  class="ProductView_chart_2adJk _' + i + ' "></span><span class="ProductView_number_233Pz ProductView_up_3JTBt">' + data[i].lastPrice + '</span></p>';
        } else {
            html += '<p class="ProductView_price_1Cyoa text-right"><span class="ProductView_chart_2adJk _' + i + ' "></span><span class="ProductView_number_233Pz ProductView_down_3JTBt">' + data[i].lastPrice + '</span></p>';
        }
        html += '<p class="ProductView_volume_3XcuC">Volume: &nbsp;<span class="ProductView_number_233Pz">' + data[i].Volume + '</span> <span>' + data[i].currencyName + '</span></p></div>';
        html += '</div>';

    }
    var dom = html;
    $('#addTen').html(dom + dom);
    for (var k in data) {
        $("._" + k).sparkline(data[k].price, { type: "line", width: "30%", height: "20", lineColor: "#fff", fillColor: "transparent" });
    }
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
        if (Inner_socket.ws.bufferedAmount == 0 && Inner_socket.ws.readyState === 1) {
            Inner_socket.msg = {
                "method": 'heartbeat'
            };
            Inner_socket.ws.send(JSON.stringify(Inner_socket.msg));
            Inner_socket.last_health = get_time;
        }
    }
}
