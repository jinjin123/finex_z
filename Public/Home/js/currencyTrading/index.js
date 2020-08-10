var BtoB_storage;
var KlineArr = null;
var coinSelect_trueF = false;
// =========================每5秒执行市场概况模块数据刷新 =====================//
var real_mountain = setInterval(mountain, 5000);
setInterval(real_time, 5000);
setInterval(function() {
    update(2);

}, 20000);
var real_table = setInterval(real_insertTable, 5000);

$(document).ready(function() {
    // 1.初次加载时,默认选中第一个交易品种
    var default_coin;

    // 1.获取币种余额
    getCurrencyMarketList();

    if (window.localStorage) {
        //主逻辑业务
        BtoB_storage = window.localStorage;
        if (BtoB_storage.BtoB_ID == null) {
            default_coin = $('#coinMoneyList li.coinName').eq(0);
            default_coin.addClass('active');
            KlineDataRaw(default_coin);
        } else {
            var parse_BtoB = JSON.parse(BtoB_storage.BtoB_ID);
            $('#coinMoneyList .coinName.active').removeClass("active");
            $('#coinMoneyList .coinName[currency-id="' + parse_BtoB.childrenID + '"][area-id="' + parse_BtoB.trad_coin_area_id + '"]').addClass('active');
            default_coin = $('#coinMoneyList .coinName.active');
            setTimeout(function() {
                KlineDataRaw($('#coinMoneyList .coinName[currency-id=' + parse_BtoB.childrenID + ']'));
            }, 500);
        }
        //动态侧边栏选中币种
        var area_target = $('#coinMoneyList a[currency-id=' + default_coin.attr("area-id") + ']').attr('area-name');
        var coinSelect_html = '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-' + area_target + '1"></use></svg> <span>' + default_coin.attr('currency-str') + '</span> <i class="fa fa-angle-down"></i>';
        $('.coinSelect').html(coinSelect_html);
    }
    // 更改交易订单的表头名称
    // 3.填充交易订单数据(默认填充交易中订单)
    $('.trad').eq(0).addClass('papes');
    //订单信息
    insertTable();

    // 2.填充市场概况
    mountain();
});


$(function() {

    // =====================换大区名字 =====================//
    $('body').on("click", "#coinMoneyList .coinName", function() {
        var _this = $(this);
        if (_this.hasClass("active")) {
            return;
        }
        $('#coinMoneyList li.active').removeClass('active');
        _this.addClass('active');
        var currency_id = _this.attr("currency-id");
        KlineDataRaw(_this);
        update(1);
        changeTh();
        mountain();
        //交易订单数据填充;
        insertTable();
        //将当前交易订单切换成正在交易板块
        /*切换清空*/
        target_appear($('#SaleIn_count_2'), 4);
        target_appear($('#SaleOut_count_2'), 4);
        target_appear($('#SaleIn_mar_1'), 4);
        target_appear($('#SaleOut_mar_1'), 4);
        target_appear($('.BtoB_order_show'), 1);
        target_appear($('.Confirmation'), 2);

        //侧边栏币种active
        var area_id = _this.attr("area-id");
        var currency_img = _this.parents("li").find('img').attr('src');
        if (currency_id == 1 || currency_id == 6) {
            $('.coinSlide .fabtc').css('background', 'url("' + currency_img + '") center center no-repeat rgb(30, 43, 52)');
        }
        $('.coinSelect > span').text(_this.text());
        //获取父级的孩子的文本
        var parentText = _this.text().split("/");
        var currency_BTC = $('.currency-id' + '-' + area_id).text(),
            currency_banlance = $('.currency-id' + '-' + currency_id).text();

        trad_coin_coinInfo(currency_BTC, $('.currency-1'), 2, 1);
        trad_coin_coinInfo(currency_banlance, $('.aside_Currency_balance'), 2, 1);

        $('.aside_Currency_content').text(parentText[0]);
        //父级的孩子的currency-id
        var BtoB_ID = _this.attr('currency-id');
        //父级的孩子的文本
        var BtoB_Text = _this.text().split("/")[0];
        var price_and_mount = {
            "currencyID": currency_id,
            "childrenID": BtoB_ID,
            'trad_coin_area_id': '' + area_id + ''

        };
        BtoB_storage.setItem('BtoB_ID', JSON.stringify(price_and_mount));
        $(".Coin-price").text('(' + $.trim(parentText[1]) + ')');
        $(".Coin-mount").text('(' + $.trim(BtoB_Text) + ')');
        $('.change-area').text($.trim(parentText[1]));
        $(".Current-area").text($.trim(parentText[0]));
        $(".supreme").hasClass("order-max-active") ? ($(".supreme").removeClass("order-max-active"), supremeNum = 1) : false;
    });

    // =====================买入卖出发送请求 =====================//
    $('body').on('click', '.sub-Con', function() {
        //点击 disable 3秒
        setTimeSecd($(this));
        BS_order();

    });
    $('body').on('click', '.Return-Con', function() {
        target_appear($('.BtoB_order_show'), 1);
        target_appear($('.Confirmation'), 2);
    });

    // =====================数量 价格 实时运算乘法 =====================//
    $('#SaleIn_count_1').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation($('#SaleIn_count_2').val(), _this.val(), 9, 9, $('.ZJE-btt1 .Total-amount'), 1);
    });

    $('#SaleIn_count_2').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation($('#SaleIn_count_1').val(), _this.val(), 9, 9, $('.ZJE-btt1 .Total-amount'), 1);

    });
    $('#SaleOut_count_1').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation($('#SaleOut_count_2').val(), _this.val(), 9, 9, $('.ZJE-btt3 .Total-amount'), 1);

    });
    $('#SaleOut_count_2').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation($('#SaleOut_count_1').val(), _this.val(), 9, 9, $('.ZJE-btt3 .Total-amount'), 1);

    });
    //  =====================卖出买入   市价单    买入金额 =====================//
    $('#SaleIn_mar_1').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation($('#trade_buy_price').text(), _this.val(), 9, 9, $('.ZJE-btt2 .Total-amount'), 2);
    });
    $('#SaleOut_mar_1').bind('input propertychange', function() {
        var _this = $(this);
        validationNumber(_this, 4);
        Calculation(_this.val(), $('#trade_sell_price').text(), 9, 9, $('.ZJE-btt4 .Total-amount'), 1);
    });
    // =====================点击买入 判断不能为空 切换资金密码页面 =====================//
    $('body').on('click', '.buyin', function() {

        //维护
        if (Maintain(forbidOrder, BB_Maintain_Place, BB_Maintain_Pattern)) {
            return;
        }
        //区获取
        var active_Li = $('#coinMoneyList').find("li.active");
        //获取当前选中币种的大区id
        var aid = active_Li.attr("area-id");
        //委托类型
        var entrust_type = active_Li.attr("entrust-type");

        //获取当前市价/限价
        var SaleType = $('#SaleType1').val();
        // 1.限价单买入价格 2.市价单买入金额 3.限价单买入数量
        var entrustPrice, Business, leaveNum;
        //判断限价单/市价单 获取相应值
        if (SaleType == 1) {
            entrustPrice = $('#SaleIn_count_1').val();
            leaveNum = $('#SaleIn_count_2').val();
            if (leaveNum == '') {
                BottomalertBox('bottom', _QTXSL_, "fail", "center");
                return;
            }
        } else if (SaleType == 2) {
            Business = $('#SaleIn_mar_1').val();
            if (Business == '') {
                BottomalertBox('bottom', _QTXMRJE_, "fail", "center");
                return;
            }
        }
        check_user("/CurrencyTrading/checkUserPrice", {
            'tradeType': aid,
            'priceType': SaleType,
            'transactionType': 1,
            'entrustType': entrust_type,
            'entrustPrice': entrustPrice,
            'leaveNum': leaveNum,
            'totalPrice': Business
        }, 'post').then(function(response) {
            if (response.code == 200) {

                Real_name();
            } else if (response.code == 614) {
                // 未实名认证，待审核
                alertBox(userreal);
            } else {
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
        });
    });
    // =====================卖出判断不能为空 切换资金密码 =====================//
    $('body').on('click', '.sellin', function() {

        //维护判断php变量
        if (Maintain(forbidOrder, BB_Maintain_Place, BB_Maintain_Pattern)) {
            return;
        }
        //区获取
        var active_Li = $('#coinMoneyList').find("li.active");
        //获取当前选中币种的大区id
        var aid = active_Li.attr("area-id");
        //委托类型
        var entrust_type = active_Li.attr("entrust-type");
        //获取当前市价/限价 
        var SaleType = $('#SaleType2').val();
        // 1.限价单买入价格 2.市价单买入金额 3.限价单买入数量
        var entrustPrice, leaveNum, Business;
        //判断限价单/市价单 获取相应值
        if (SaleType == 1) {
            entrustPrice = $('#SaleOut_count_1').val();
            leaveNum = $('#SaleOut_count_2').val();
            if (leaveNum == '') {
                BottomalertBox('bottom', _QTXSL_, "fail", "center");
                return;
            }
        } else if (SaleType == 2) {
            Business = $('#SaleOut_mar_1').val();
            if (Business == '') {
                BottomalertBox('bottom', _QTXMCJE_, "fail", "center");
                return;
            }
        }
        check_user("/CurrencyTrading/checkUserPrice", {
            'tradeType': aid,
            'priceType': SaleType,
            'transactionType': 2,
            'entrustType': entrust_type,
            'entrustPrice': entrustPrice,
            'leaveNum': leaveNum,
            'totalPrice': Business
        }, 'post').then(function(response) {
            if (response.code == 200) {
                Real_name();
            } else if (response.code == 614) {
                // 未实名认证，待审核
                alertBox(userreal);
            } else {
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
        });
    });

    //点击星星 收藏
    $('body').on('click', '.myCollection', function() {
        var _this = $(this);
        var currency_id = _this.attr('currency-id');
        var collection_status = _this.attr('collection-status');
        var area_id = _this.attr('area-id');
        collectionMyCoin(_this, currency_id, collection_status, area_id);
    });

    //点击收藏列表
    $('body').on("click", "#getMyCurrencyList", function() {
        var active_Li = $("#coinMoneyList").find("li.active");
        var aid = active_Li.attr("area-id");

        check_user("/CoinTradeInfo/getMycollection", {
            'tradeArea': aid,
            'type': 2
        }, 'get').then(function(data) {
            if (data.code == 200) {
                getCoinTable(data.data.coin_arr, 2, 2);
            }
        });
    });

    // 建强补充  实名认证提示后自动请求改变不提示
    $('body').on('click', '.NameAlert button.close', function() {
        if (tips != 2) {
            $.ajax({
                cache: true,
                type: 'post',
                url: '/PersonalCenter/changeStatus'
            });
        }
    });

    // ========================= 交易订单中,切换历史订单和正在交易的数据填充 =====================//
    $('.trad').click(function() {
        var _this = $(this);
        //维护
        if (Maintain(dealOrder, BB_Maintain_order, BB_Maintain_Pattern)) {
            _this.find('a').attr('href', 'javascript:void(0)').removeAttr('data-toggle');
            return;
        }
        //去除上次选中状态
        $('.trad.papes').removeClass('papes');
        //给当前点击加上状态
        _this.addClass('papes');
        //获取列表内容
        insertTable();
    });

    /**
     * 撤销订单弹窗确认按钮
     */
    $('#revokeOrderById').click(function() {
        var order_id = $(this).attr('order-id');
        var aid = $('#coinMoneyList li.active').attr("area-id");

        check_user("/CurrencyTrading/revokeOrder", {
            'tradeArea': aid,
            'order_id': order_id,
        }, 'post').then(function(msg) {
            if (msg.code == 200) {
                $('.cancelRemove' + order_id + '').parents('tr').remove();
                BottomalertBox("bottom", revokeTips, "success", "center");
                $('#myCurrencyList').empty();
                getCurrencyMarketList();
            } else {
                BottomalertBox("bottom", msg.msg, "fail", "center");
            }
        });
    });

});

/**
 * 获取币种余额
 * @author 2017-12-06T10:29:31+0800
 * @return {[type]} [description]
 */
function getCurrencyMarketList() {
    check_user('/CoinTradeInfo/getUserCurrency', {}, 'post').then(function(response) {
        var html = '';
        var myCurrList = response.data.myCurrList;
        for (var x in myCurrList) {
            //币种余额html函数
            html += getCurrencyListHtml(myCurrList[x]);
        }

        $('#myCurrencyList').append(html);
        //成功之后填写数据
        update(1);
        changeTh();
    });
}

/**
 * 获取币种余额html
 * @author 2017-12-6 10:29:13
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function getCurrencyListHtml(data) {
    var html = '<tr>';
    html += '<td>' + data.coin_name + '</td>';
    html += '<td class="text-right two_price"><span class="currency-id-' + data.currency_id + '">' + data.num + '</span></td>';
    html += '</tr>';
    return html;
}

//========================= 市场概况模块 =====================//
//  定义生成买单数据函数
function makeBdata(ary) {
    var move1 = $(".movements:first");
    var bodata = $("#borders_data");
    move1.children().remove();
    bodata.children().remove();
    //变量 1：剩余数量，2：转化成数字，3价钱变量，4 卖出多少
    var ctd, scale, nprice, price, buy_quantity;
    for (var i = 0; i < ary.length; i++) {
        price = ary[i].price;
        nprice = Number(price);

        ctd = ary[i].ctd;
        scale = ary[i].total_num;
        buy_quantity = scale - ctd;
        bodata.append("<tr><td class='price_td'>" + ctd + "</td><td class='count_td'>" + price + "</td></tr>");
        lwidth = (buy_quantity / scale) * 100;
        lwidth = lwidth.toFixed(2);
        if (lwidth < 1 && lwidth != 0) {
            lwidth = 1;
        }
        move1.append("<div class='green_line-wrap' style='width:97%'><p class='green_line' style='width:" + (100 - lwidth) + "%" + "'></p></div>");
    }
}
//	定义生成卖单数据函数
function makeSdata(ary) {
    var move2 = $(".movements").eq(1);
    var sodata = $("#sorders_data");
    move2.children().remove();
    sodata.children().remove();
    //变量 1：剩余数量，2：转化成数字，3价钱变量，4 卖出多少
    var ctd, scale, nprice, price, buy_quantity;
    for (var i = 0; i < ary.length; i++) {
        price = ary[i].price;
        nprice = Number(price);
        ctd = ary[i].ctd;
        scale = ary[i].total_num;
        buy_quantity = scale - ctd;
        sodata.append("<tr><td class='count_td'>" + ctd + "</td><td class='price_td'>" + price + "</td></tr>");
        lwidth = (buy_quantity / scale) * 100;
        lwidth = lwidth.toFixed(2);
        if (lwidth < 1 && lwidth != 0) {
            lwidth = 1;
        }
        move2.append("<div class='orange_line-wrap' style='width:97%'><p class='orange_line' style='width:" + (100 - lwidth) + "%" + "'></p>");
    }
}

//	2. 调用 买卖生成函数,产生数据
function mountain() {
    //获取当前激活的交易品种
    if (Maintain(listOrder, BB_Maintain_Marketorder, BB_Maintain_Pattern)) {
        makeSdata('');
        makeBdata('');
        clearInterval(real_mountain);
        return;
    }
    var active_Li = $("#coinMoneyList").find("li.active");
    var area_id = active_Li.attr("area-id");
    var entrust_type = active_Li.attr("entrust-type");

    check_user(mountain_data, {
        'area_id': area_id,
        'entrust_type': entrust_type
    }, 'post').then(function(msg) {
        makeSdata(msg.data.sell);
        makeBdata(msg.data.buy);
    });
}
//========================= 市场概况模块 END =====================//

//========================= 交易订单数据 =====================//
function insertTable(p) {
    //个人信息模块维护
    if (Maintain(dealOrder, BB_Maintain_order, BB_Maintain_Pattern)) {
        $('#tab-tradeing-all').find('tbody').html('');
        $('#tab-tradeing-buy').find('tbody').html('');
        clearInterval(real_table);
        return;
    }
    //获取当前激活的交易品种
    var active_Li = $("#coinMoneyList").find("li.active");
    var aid = active_Li.attr("area-id");
    var etype = active_Li.attr("entrust-type");
    var listType = $('.trad.papes').attr('type');

    //填充表单数据
    check_user(table_data, {
        'area_id': aid,
        'entrust_type': etype,
        'type': listType,
        'p': p
    }, 'get').then(function(msg) {
        var ary = msg.data.order_list;
        var table = '';
        for (i = 0; i < ary.length; i++) {
            table += '<tr>';
            table += '<td>' + ary[i].coin + '</td>';
            table += '<td>' + ary[i].type_str + '</td>';
            table += '<td>' + ary[i].entrust_price + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].avg_price + '</td>';
            table += '<td>' + ary[i].entrust_num + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].success_num + '</td>';
            table += '<td>' + ary[i].entrust_money + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].sum_money + '</td>';
            table += '<td>' + ary[i].add_time + '</td>';

            if (listType == 1) {
                table += '<td><button type="button" class="btn btn-grey btn-xs de-cancel cancelRemove' + ary[i].order_id + '"  data-toggle="modal" data-target="#cancel" onclick="cancelOrder(' + ary[i].order_id + ')">' + chexiaoTips + '</button></td>';
            } else if (listType == 2) {
                table += '<td>' + ary[i].status_str + '</td>';
            }

            table += '</tr>';
        }
        if (listType == "1") {
            // 正在交易数据填充
            $('#tab-tradeing-all').find('tbody').html(table);

        } else if (listType == "2") {
            // 撤销订单数据填充
            $('#tab-tradeing-buy').find('tbody').html(table);
            $('.ttorder').eq(1).html(msg.data.show);

        }
    });

}




//========================= 交易订单数据 END =====================//

/**
 * @author 宋建强 kline 数据渲染 
 * @param  object
 */
function KlineDataRaw(t) {
    var currency_name = t.attr('currency-str');
    var str = currency_name.replace('/', '_');
    $('#kline_iframe').attr('src', '/TradingView/rawTrade/CoinType/' + str);
}

//========================= 根据激活交易品种控制交易订单表头 =====================//
function changeTh() {

    var active_Li = $('#coinMoneyList li.active');
    var acText = active_Li.text();

    //手续费百分比
    var buy = active_Li.attr("buy-fee");
    var sell = active_Li.attr("sell-fee");
    var Service1 = parseFloat(buy);
    var Service2 = parseFloat(sell);
    $('.Service-1').text(Service1 * 100);
    $('.Service-2').text(Service2 * 100);

    //切割 切换名字
    var Cuarea = active_Li.text();
    var arr = Cuarea.split('/');
    $('.Current-area').text($.trim(arr[0]));
    $('.change-btc').text($.trim(arr[0]));
    $('.change-area').text($.trim(arr[1]));
    $('#coinNameMoney').text(Cuarea);
    var acAry = acText.split("/");
    slaveCoin = "(" + $.trim(acAry[0]) + ")";
    mainCoin = "(" + $.trim(acAry[1]) + ")";
    $(".mainCoin").text(mainCoin);
    $(".slaveCoin").text(slaveCoin);

    //清空订单内容
    target_appear($('.Total-amount'), 3);
}

//========================= 撤销交易订单数据 =====================//
function cancelOrder(obj) {
    $('#revokeOrderById').attr('order-id', obj);
}

//========================= 买入卖出交易订单 =====================//
function BS_order() {
    //区获取
    var active_Li = $('#coinMoneyList li.active');
    var aid = active_Li.attr("area-id");
    //委托类型
    var entrust_type = active_Li.attr("entrust-type");

    //买入卖出
    var trans = $('.transactionType .active').attr("id");
    var transType = '';
    var SaleType, entrustPrice, leaveNum, Business;
    //判断 买入 卖出 市价限价
    if (trans == "transactionType-1") {
        transType = 1;
        SaleType = $('#SaleType1').val();
        if (SaleType == 1) {
            entrustPrice = $('#SaleIn_count_1').val();
            leaveNum = $('#SaleIn_count_2').val();
        } else if (SaleType == 2) {
            Business = $('#SaleIn_mar_1').val();
        }
    } else if (trans == "transactionType-2") {
        transType = 2;
        SaleType = $('#SaleType2').val();
        if (SaleType == 1) {
            entrustPrice = $('#SaleOut_count_1').val();
            leaveNum = $('#SaleOut_count_2').val();
        } else if (SaleType == 2) {
            Business = $('#SaleOut_mar_1').val();
        }
    }
    //资金密码
    var tradePwd = $('#Confirmation-psd').val();
    check_user("/CurrencyTrading/processTradeInfo", {
        'tradeType': aid,
        'priceType': SaleType,
        'transactionType': transType,
        'entrustType': entrust_type,
        'entrustPrice': entrustPrice,
        'leaveNum': leaveNum,
        'tradePwd': tradePwd,
        'totalPrice': Business
    }, 'post').then(function(response) {
        if (response.code == 200) {
            insertTable();
            // 清除数据
            target_appear($('.BtoB_order_show'), 1);
            target_appear($('.Confirmation'), 2);
            target_appear($('.Sale_clearjs'), 4);
            // 模态框小时
            $('.Confirmation').modal('hide');
            $('#myCurrencyList').empty();
            getCurrencyMarketList();
            var $currency_4 = $('.currency-4'),
            $currency_1 = $('.currency-1');
            trad_coin_coinInfo($currency_1.text(), $currency_1, 2, 1);
            trad_coin_coinInfo($currency_4.text(), $currency_4, 2, 1);
            // 提示信息
            BottomalertBox('bottom', response.msg, "success", "center");
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }

    });
}
/**
 * 检测实名认证ajax
 */
function Real_name() {
    check_user("/CheckUserInfo/isUserRealName", {}, 'get').then(function(msg) {
        if (msg.code == 200) {
            setpsd();

        } else {
            alertBox(msg.msg);
        }
    });

}
//点击 买卖 请求 判断用户是否设置资金密码
function setpsd() {
    check_user("/CheckUserInfo/checkUserIsTradePwd", {}, 'get').then(function(msg) {
        if (msg.code == 200) {
            //买入卖出
            var trans = $('.transactionType .active').attr("id");
            var trans_classA = [];

            //判断 买入 卖出 市价限价
            if (trans == "transactionType-1") {
                trans_classA = ['sub_Con_Rtips', 'sub_Con_Gtips', 'gradual_change_red', 'gradual_change_green'];
            } else if (trans == "transactionType-2") {
                trans_classA = ['sub_Con_Gtips', 'sub_Con_Rtips', 'gradual_change_green', 'gradual_change_red'];
            }
            $('.order-group-border').removeClass(trans_classA[0]).addClass(trans_classA[1]);
            $('.Confirmation .sub-Con').removeClass(trans_classA[2]).addClass(trans_classA[3]);

            target_appear($('.BtoB_order_show'), 2);
            target_appear($('.Confirmation'), 1);
            target_appear($('#Confirmation-psd'), 4);

        } else {
            alertBox(msg.msg);
            target_appear($('.BtoB_order_show'), 1);
            target_appear($('.Confirmation'), 2);
        }

    });
}


// =====================实时价格更新  low htq last =====================//
//参数   coin_trad_type1=2  为5秒循环刷新实时刷新币种信息数据 不刷新其他节点数据

function update(coin_trad_type1) {
    //获取当前选中的币种元素
    var active_Li = $("#coinMoneyList").find("li.active");
    //获取选中元素的 大区id
    var areaID = active_Li.attr("area-id");
    //获取选中元素的币种id
    var active_currency_id = active_Li.attr("currency-id");

    //获取选中元素的类型id
    var etype = active_Li.attr("entrust-type");

    //把账户里的区币余额放到【确认订单】下对应的区币余额
    var currency_BTC = $('.currency-id' + '-' + areaID).text(),
        currency_banlance = $('.currency-id' + '-' + active_currency_id).text();

    //分割当前选择币种的名字 需要前一个
    var parentText = active_Li.attr('currency-str').split("/");
    $(".Coin-price").text('(' + parentText[1] + ')');
    $(".Coin-mount").text('(' + parentText[0] + ')');
    $('.coinSelect > span').text(parentText[0] + '/' + parentText[1]);
    //左边导航栏当前币种名字
    $('.aside_Currency_content').text(parentText[0]);
    //左边导航栏当前大区名字 买入信息价钱
    trad_coin_coinInfo(currency_BTC, $('.currency-1'), 2, 1);
    //左边导航栏当前币种余额
    trad_coin_coinInfo(currency_banlance, $('.aside_Currency_balance'), 2, 1);


    check_user("/CoinTradeInfo/getAllCurrencyInfoList", {
        'areaId': areaID,
        'entrust_type': etype,
    }, 'get').then(function(msg) {
        if (coin_trad_type1 == 1) {
            //当前币种买入卖出实时价格
            var $trade_buy_price = $('#trade_buy_price');
            var $trade_sell_price = $('#trade_sell_price');
            //填充实时买入卖出价格
            $trade_sell_price.text(msg.coinInfo.sell);
            $trade_buy_price.text(msg.coinInfo.buy);

            //实时价格填充 价格 数量
            trad_coin_coinInfo(msg.coinInfo.sell, $('#SaleOut_count_1'), 2, 2);
            trad_coin_coinInfo(msg.coinInfo.buy, $('#SaleIn_count_1'), 2, 2);

            //获取区id  换区的名字和金额  买入
            var trans = $('.transactionType .active').attr("id");
            //获取选中币种的大区id
            var Large_area = active_Li.attr('area-id');

            //获取大区余额
            var Area_balance2 = $('.currency-id-' + Large_area).text();
            //获取实时价格
            var Can_buy1 = $trade_buy_price.text();
            //ajax运算买入价格
            trad_coin_Calculation(2, Area_balance2, Can_buy1, $('.currency-2'));

            //获取个体币种id  换区的名字和金额  卖出
            var currency_id = $('#coinMoneyList').find("li.active").attr('currency-id');
            var Area_balance3 = $('.currency-id-' + currency_id).text();
            var Can_buy2 = $trade_sell_price.text();
            //ajax运算卖出价格
            trad_coin_Calculation(1, Area_balance3, Can_buy2, $('.currency-5'));
            trad_coin_coinInfo(Area_balance3, $('.currency-4'), 2, 1);
            getCoinTable(msg.tradeAreaInfo, 1, 1);
            $("#tab-underline-home3 .quoteprice_box[box-id='" + active_currency_id + "']").addClass("active");

            //当前币种过去24小时交易总额
            var tfhour = msg.tradeAreaInfo[etype];
            $('#coinTotalMoney').text(tfhour.vol);
        } else {
            var result = [],
                arrNum = [];
            for (var i in msg.tradeAreaInfo) {
                result.push(msg.tradeAreaInfo[i]);
            }
            $.each(result, function(i, val) {
                var color, arrow;
                if (val.perc_status > 0) {
                    color = '#4bd2b7';
                    arrow = "fa fa-long-arrow-up";
                } else {
                    color = '#e8653b';
                    arrow = "fa fa-long-arrow-down";
                }
                var html = '' + val.rate + '  <i class="' + arrow + '"></i>';
                $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_nowprice div:eq(0)").css({
                    color: color
                });
                $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_nowprice div:eq(0)").text(target_Intercept_number(val.last, 1));
                $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_volume .vol_font").css({
                    color: color
                });
                $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_volume .vol_font").html(html);
                $("#tab-underline-home3 .quoteprice_box[box-id = " + val.currency_id + "] .quote_volume div:eq(0) .font11").text("Volume:" + parseInt(val.vol) + " " + val.coin_str.split("/")[0] + "");
                arrNum.push({});
                arrNum[i].area_id = val.area_id;
                arrNum[i].currency_id = val.currency_id;
                arrNum[i].last = [];
                arrNum[i].last.push(val.last);
                arrNum[i].color = color;
                if (KlineArr == null) {
                    KlineArr = arrNum;
                }
                KlineArr[i].area_id = val.area_id;
                KlineArr[i].currency_id = val.currency_id;
                KlineArr[i].color = color;
                if (KlineArr[i].last.length >= 10) {
                    KlineArr[i].last.shift();
                }
                KlineArr[i].last.push(val.last);

                $("#tab-underline-home3 .quoteprice_box[box-id = " + KlineArr[i].currency_id + "] .kline").sparkline(KlineArr[i].last, {
                    type: 'line',
                    spotColor: KlineArr[i].color,
                    lineColor: '#ffffff',
                    fillColor: false,
                    minSpotColor: KlineArr[i].color,
                    maxSpotColor: KlineArr[i].color,
                });
            });
            $("#tab-underline-home3 .quoteprice_box[box-id='" + active_currency_id + "']").addClass("active");
        }

        trad_coin_coinInfo(msg.coinInfo.heigh, $('#coinHeighMoney'), 1, 1);
        trad_coin_coinInfo(msg.coinInfo.low, $('#coinLowMoney'), 1, 1);
        trad_coin_coinInfo(msg.coinInfo.rtq, $('#coinRtqMoney'), 1, 1);
    });

}

// =====================获取币种收藏列表 =====================//
function getCoinTable(data, type, target_tab) {
    type = type ? type : 1; // 1表示币种信息列表，2表示收藏币种列表
    var table = '',
        color = '',
        arrow = '',
        result = [];

    for (var i in data) {
        result.push(data[i]);
    }
    $.each(result, function(i, val) {
        if (val.perc_status > 0) {
            color = '#4bd2b7';
            arrow = "fa fa-long-arrow-up";
        } else {
            color = '#e8653b';
            arrow = "fa fa-long-arrow-down";
        }
        var currency = val.coin_str;
        var _curr = currency.split("/");
        var vol = parseInt(val.vol);

        table += '<div class="quoteprice_box" box-id=' + val.currency_id + '><ul>';
        table += '<li class="quote_coinname" >';
        table += '<div id="getImgicon" class="col-lg-6 col-md-6 col-xs-6"><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-' + _curr[0] + '1"></use></svg>  ' + _curr[0] + '</div>';
        table += '<div class="col-lg-6 col-md-6 col-xs-6 text-right">';
        if (type == 1) {
            var tag;
            if (val.col_status == 1) {
                tag = 'fill';
            } else {
                tag = 'line';
            }
            table += '<a href="" id="tag02" data-toggle="tab" aria-expanded="false">';
            table += ' <svg collection-status="' + val.col_status + '"  aria-hidden="true" class="tag02 myCollection icon icon_norepeat"';
            table += ' currency-id="' + val.currency_id + '" area-id="' + val.area_id + '"><use xlink:href="#icon-ic_left_collect_' + tag + '"></use></svg></a>';
        }
        table += '</div>';
        table += '<div class="clearfix"></div>';
        table += '</li>';
        //quote_nowprice
        table += '<li class="quote_nowprice text-center"><div class="col-lg-12" style="color:' + color + '">' + target_Intercept_number(val.last, 1) + '</div><div class="clearfix"></div></li>';
        //quote_volume
        table += '<li class="quote_volume">';
        table += '<div class="col-lg-7 col-md-7 col-xs-7"><span class="font11">Volume:' + vol + ' ' + _curr[0] + '</span></div>';
        table += '<div class="col-lg-5 col-md-5 col-xs-5 text-right">';
        table += '<span class="font11">24H :</span><span class="vol_font font11" style="color:' + color + '">' + val.rate + '  <i class="' + arrow + '"></i></span>';
        table += '<span class="kline"></span>';
        table += '</div>';
        table += '<div class="clearfix"></div>';
        table += '</li>';
        table += '</ul></div>';

    });
    if (target_tab == 1) {
        $('#tab-underline-home3 .panel-body').html(table); // 牌价
    } else {
        $('#tab-underline-profile3 .panel-body').html(table);
    }
}
/**
 * 收藏币种
 * @author 2017-10-25T16:48:50+0800
 */
function collectionMyCoin(_this, currency_id, collection_status, area_id) {

    check_user('/CoinTradeInfo/collectionMyCoin', {
        'currency_id': currency_id,
        'collection_status': collection_status,
        'trade_area': area_id,
        'type': 2
    }, 'post').then(function(response) {
        if (response.code == 200 && response.data.collection_status == 0) {
            BottomalertBox("bottom", response.msg, "fail", "center");
            _this.attr('collection-status', '0').html('<use xlink:href="#icon-ic_left_collect_line"></use>');

        } else if (response.code == 200 && response.data.collection_status == 1) {
            BottomalertBox("bottom", response.msg, "success", "center");
            _this.attr('collection-status', '1').html('<use xlink:href="#icon-ic_left_collect_fill"></use>');
        }
    });
}

// =====================实时价格更新 =====================//
function real_time() {
    var active_Li = $("#coinMoneyList").find("li.active");
    var aid = active_Li.attr("area-id");
    var etype = active_Li.attr("entrust-type");


    check_user("/CoinTradeInfo/getCurrencyInfoByArea", {
        'areaId': aid,
        'entrust_type': etype
    }, 'get').then(function(msg) {
        trad_coin_coinInfo(msg.coinInfo.heigh, $('#coinHeighMoney'), 1, 1);
        trad_coin_coinInfo(msg.coinInfo.low, $('#coinLowMoney'), 1, 1);
        trad_coin_coinInfo(msg.coinInfo.rtq, $('#coinRtqMoney'), 1, 1);
    });
}

// =====================判断价格是否为空或为null =====================//
/*
 参数 1：需要判断的数字
 	2：需要填充数据的对象
 	3：target公共函数type 在 platform.js  1 =为补位小数 2=不需要补
 	4：1 = 对象text文本  2= 对象 val值
 * */
function trad_coin_coinInfo(trad_coinInfo_height, obj, type, text_val) {
    if (trad_coinInfo_height == '' || trad_coinInfo_height == null) {
        trad_coinInfo_height = '0.00000000';
    }
    if (text_val == 1) {
        obj.text(target_Intercept_number(trad_coinInfo_height, type));
    } else {
        obj.val(target_Intercept_number(trad_coinInfo_height, type));

    }
}

//实时刷新正在交易订单数据
function real_insertTable() {
    //获取当前激活的交易品种
    var active_Li = $("#coinMoneyList").find("li.active");
    var aid = active_Li.attr("area-id");
    var etype = active_Li.attr("entrust-type");
    var listType = $('.trad.papes').attr('type');
    //填充表单数据

    check_user(table_data, {
        'area_id': aid,
        'entrust_type': etype,
        'type': 1,
    }, 'get').then(function(msg) {
        var ary = msg.data.order_list;
        var table = '';
        for (var i = 0; i < ary.length; i++) {
            table += '<tr>';
            table += '<td>' + ary[i].coin + '</td>';
            table += '<td>' + ary[i].type_str + '</td>';
            table += '<td>' + ary[i].entrust_price + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].avg_price + '</td>';
            table += '<td>' + ary[i].entrust_num + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].success_num + '</td>';
            table += '<td>' + ary[i].entrust_money + ' <span class="coin_trad_shuxian">|</span> ' + ary[i].sum_money + '</td>';
            table += '<td>' + ary[i].add_time + '</td>';
            table += '<td><button type="button" class="btn btn-grey btn-xs de-cancel cancelRemove' + ary[i].order_id + '"  data-toggle="modal" data-target="#cancel" onclick="cancelOrder(' + ary[i].order_id + ')">' + chexiaoTips + '</button></td>';
            table += '</tr>';

        }
        // 正在交易数据填充
        $('#tab-tradeing-all').find('tbody').html(table);
    });

}

/**
 * 
 * @param {需要操作的元素} ap_1 
 * @param {根据type值操作} type 
 */
function target_appear(elements, type) {
    // var Operational = {
    //     '1': elements.fadeIn(),
    //     '2': elements.fadeOut(),
    //     '3': elements.text('0'),
    //     '4': elements.val('')
    // }
    // console.log(Operational[type]);
    // return Operational[type]
    switch (type) {
        case 1:
            elements.fadeIn();
            break;
        case 2:
            elements.fadeOut();
            break;
        case 3:
            elements.text("0");
            break;
        case 4:
            elements.val("");
            break;
    }

}

/**
 * 保留小数函数
 * @param {计算元素} cal_1 
 * @param {计算元素} cal_2 
 * @param {保留位数+1} cal_3 
 * @param {保留位数+1} cal_4 
 * @param {显示最终价格元素} cal_5 
 * @param {1.乘法 2.除法} type 
 */
function Calculation(cal_1, cal_2, cal_3, cal_4, cal_5, type) {
    var Num = Number(cal_1);
    var Pri = Number(cal_2);
    var Nu_pri = type == 1 ? Pri * Num : Pri / Num;
    var oper = Nu_pri.toFixed(cal_3);
    var ation = oper.substring(0, oper.lastIndexOf('.') + cal_4);
    cal_5.text(ation);
}
/**
 * 
 * @param {计算乘法除法} type 
 * @param {数量} num 
 * @param {价格} price 
 * @param {显示的元素} obj 
 */
function trad_coin_Calculation(type, num, price, obj) {
    check_user("/CurrencyTrading/getBuyNumByPriceAndNum", {
        'type': type,
        'num': num,
        'price': price
    }, 'post').then(function(msg) {
        trad_coin_coinInfo(msg.data.num, obj, 2, 1);
    });
}