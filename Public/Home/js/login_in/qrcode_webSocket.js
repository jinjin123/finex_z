var server_url = 'ws://47.57.131.217:9502'; //WebSocket路径
var qrcode_obj = {
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
    _session_id: '',
    disConnect_type: 0,
    disConnect: null,
    _timestamp: null,
    Training_QRcode: null
};
$(document).ready(function() {
    //使用原生WebSocket
    if (window.WebSocket || window.MozWebSocket) {
        qrcode_obj.ws = new WebSocket(qrcode_obj.config.server);
    }
    //使用flash websocket
    else if (qrcode_obj.config.flash_websocket) {}
    //使用http xhr长轮循
    else {
        qrcode_obj.ws = new Comet(qrcode_obj.config.server);
    }
    //发送websocket
    listenEvent();
});
//发送数据 onpen公共
function target_web(type, data, num, service_name) {
    qrcode_obj.msg = {
        "method": type,
        "uid": data,
        "hobby": num,
        "service_name": service_name,
        "data": {
            "session_id": "" + getSessionId() + "",
            "login_time": "" + qrcode_obj._timestamp + ""
        }
    };
    qrcode_obj.ws.send(JSON.stringify(qrcode_obj.msg));
}

function listenEvent() {
    /**
     * 连接建立时触发
     */
    qrcode_obj.ws.onopen = function(e) {
        //target_web('join_QR_login', client_id, 1, 'QRLogin');
        qrcode_obj.heartbeat_timer = setInterval(function() {
            keepalive();
        }, 10000); //每10秒发个心跳包
        console.log(qrcode_obj.heartbeat_timer)
            //二维码载入
        target_QRcode();
        //二维码循环1分钟切换
        qrcode_obj.Training_QRcode = setInterval(function() {
            target_QRcode();
        }, 60000);
    };

    qrcode_obj.ws.onmessage = function(e) {
        var message = JSON.parse(e.data);
        var cmd = message.data.method;
        var service_name = message.data.service_name;
        if (cmd == 'connection') {
            if (message.data.status == 1) {
                console.log('connection ok');
            }
        } else if (cmd == 'push' && service_name == 'QRLogin') {
            var server_data = message.data.data;
            var html = '';
            if (server_data.QRLoginSuccess == '1') {
                window.location.href = UserCenter;
            }

        }
    };

    /**
     * 连接关闭事件
     */
    qrcode_obj.ws.onclose = target_close(0);

    /**
     * 异常事件
     */
    qrcode_obj.ws.onerror = function(e) {
        $(".pubArea").hide();
    };
}

function getSessionId() {
    var c_name = 'PHPSESSID';
    if (document.cookie.length > 0) {
        c_start = document.cookie.indexOf(c_name + "=");
        if (c_start != -1) {
            c_start = c_start + c_name.length + 1;
            c_end = document.cookie.indexOf(";", c_start);
            if (c_end == -1) c_end = document.cookie.length;
            return unescape(document.cookie.substring(c_start, c_end));
        }
    }
}
//定时发送握手心跳包
function keepalive() {
    var time = new Date();
    if (qrcode_obj.last_health != -1 && (time.getTime() - qrcode_obj.last_health > qrcode_obj.health_timeout)) {
        //连接断开，可设置重连或者关闭连接
        qrcode_obj.ws.close();
    } else {
        if (qrcode_obj.ws.bufferedAmount == 0 && qrcode_obj.ws.readyState === 1) {
            qrcode_obj.msg = {
                "method": 'heartbeat'
            };
            qrcode_obj.ws.send(JSON.stringify(qrcode_obj.msg));
            qrcode_obj.last_health = time.getTime();
        }
    }
}
//websocket关闭函数
function target_close(type) {
    clearInterval(qrcode_obj.heartbeat_timer);
    clearInterval(qrcode_obj.disConnect);
    if (type == 0) {
        qrcode_obj.disConnect = setInterval(function() {
            //如果已连接则不重连,return 跳出函数, 清除定时器
            qrcode_obj.last_health = -1;
            qrcode_obj.disConnect_type++;
            if (qrcode_obj.disConnect_type != 3) {
                if (qrcode_obj.ws.readyState == 1) {
                    return;
                }
                // 断开连接则重新连接并执行操作
                qrcode_obj.ws = new WebSocket(qrcode_obj.config.server);
                listenEvent();
            }
        }, 5000);
    }
}
//二维码1分钟切换
function target_QRcode() {
    $('#code').html('');
    qrcode_obj._timestamp = Math.round(new Date() / 1000);
    qrcode_obj._session_id = getSessionId();
    _QR_str = '{"s_id":"' + qrcode_obj._session_id + '","QR":"btcsale","t":"' + qrcode_obj._timestamp + '"}';
    $('#code').qrcode(_QR_str);
    target_web('join_QR_login', qrcode_obj.client_id, 1, 'QRLogin');

}