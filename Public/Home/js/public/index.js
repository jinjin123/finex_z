/**
 * 用户中心js相关操作
 */
/*买入订单列表循环*/
var Maintain_getPending = setInterval(getPendingPurchaseOrderList, 5000);
//在P2P交易里选择币种时记录到localStorage,作为下次刷新时返回已选择的币种
$(document).ready(function() {
    getMyCurrencyList(); // 获取用户币种余额

    var refresh = JSON.parse(localStorage.getItem("RefreshCurrency"));
    var whatbusiness = $(".logoCache").data("cache");
    var default_coin_whatbusiness = $('#coinMoneyList li:nth-child(1)').attr('currency-name'); //侧边栏币种第一个名字
    var default_coin_ID = $('#coinMoneyList li:nth-child(1)').attr('currency-id') //侧边栏币种第一个id
    var p2p_whatbusiness_name; //p2p卖出币种icon名字
    var refresh_business, refresh_currencyid, refresh_areaType; // 1.缓存交易模式名字 2.币种当前id 3.当前交易区
    if (refresh != null && whatbusiness == "P2P") { // 有缓存且当前页面在P2P
        switch (refresh[0]) {
            case null:
                refresh_currencyid = default_coin_ID;
                getKlineDataRaw(default_coin_whatbusiness);
                p2p_whatbusiness_name = default_coin_whatbusiness; //没有缓存默认第一个币种名字
                break;
            default:
                refresh_currencyid = refresh[0].currencyId;
        }
        refresh_business = "P2P";
        refresh_areaType = 1;
    } else if (refresh != null && whatbusiness == "C2C") { // 有缓存且当前页面在C2C
        switch (refresh[1]) {
            case null:
                refresh_currencyid = default_coin_ID;
                getKlineDataRaw(default_coin_whatbusiness);
                break;
            default:
                refresh_currencyid = refresh[1].currencyId;
        }
        refresh_business = "C2C";
        refresh_areaType = 0
    } else if (whatbusiness == "") { //钱包其他页面侧边栏判断
        var logo_href = $(".navbar-brand").attr("href").split("/")[1] || "UserCenter";
        switch (logo_href) {
            case "CtoCTransaction":
                refresh_business = "C2C";
                refresh_currencyid = refresh != null && refresh[1] != null ? refresh[1].currencyId : '';
                refresh_areaType = 0;
                break;
            default:
                refresh_business = "P2P";
                refresh_currencyid = refresh != null && refresh[0] != null ? refresh[0].currencyId : '';
                refresh_areaType = 1;
                break;
        }
    } else {
        refresh_business = whatbusiness == "P2P" ? refresh_areaType = 1 : refresh_areaType = 0;
        p2p_whatbusiness_name = default_coin_whatbusiness;
        refresh_currencyid = default_coin_ID;

    }
    getCoinInfoList(refresh_currencyid, refresh_business);
    target_public_aside(1, refresh_currencyid, refresh_areaType);
});


$(function() {

    // 复制

    var t = setInterval(function() {
        if ($('.copyBtn').length > 0) {
            clearInterval(t);
            var clipboard = new Clipboard('.copyBtn');
            clipboard.on('success', function(e) {
                BottomalertBox('bottom', FZCG, 'success', "center", "center");
            });
            clipboard.on('error', function(e) {
                BottomalertBox('bottom', FZSB, 'fail', "center", "center");
            });
        }
    }, 500)


    /**
     * 点击获取收藏币种相关
     * @author 2017-10-30T15:19:26+0800
     */
    $('body').on('click', '#getMyCurrencyList', function() {
        var myCollection = getMyCollection();
        check_user('/CoinTradeInfo/getCoinInfoList', {}, 'post').then(function(response) {
            var html = '';
            var coinInfoList = response.coinInfoList;
            for (var x in coinInfoList) {
                if (in_array(coinInfoList[x].currency_id, myCollection)) {
                    html += getCoinHtml(coinInfoList[x], 2);
                }
            }
            $('#tab-underline-profile3 .panel-body').html(html);
        });
    });

    /**
     * 点击切换币种信息
     * @author 2017-10-30T15:39:43+0800
     */

    $('body').on('click', '#coinMoneyList .coinName', function() {
        var _this = $(this);
        if (_this.hasClass("active")) {
            return;
        }
        var logo_href = $(".navbar-brand").attr("href").split("/")[1] || "UserCenter";
        var whatbusiness = "",
            businessType = 0;
        switch (logo_href) {
            case "UserCenter":
                whatbusiness = "P2P";
                businessType = 1;
                break;
            case "CtoCTransaction":
                whatbusiness = "C2C";
                businessType = 0;
                break;
        }
        var Refresh = localStorage.getItem("RefreshCurrency");
        var P2Pstr, C2Cstr;
        var arr = [];
        var currencyId = _this.attr('currency-id');
        // $('#coinMoneyList .coinName.active').removeClass('active');
        // _this.addClass('active');
        // var coinSelect_html = '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-' + whichJSON.currencyName + '1"></use></svg> ' + whichJSON.currencyName + '<i class="fa fa-angle-down"></i> ';
        // $(".coinSelect").html(coinSelect_html);

        var currencyName = _this.attr('currency-name');
        target_public_aside(1, currencyId, businessType);
        // getKlineDataRaw(currencyName);
        // 记录缓存 -开始
        var P2Pstr_C2Cstr = {
            "type": "",
            "currencyId": currencyId,
            "currencyName": currencyName,
        };

        switch (whatbusiness) {
            case "P2P":
                P2Pstr_C2Cstr.type = 'P2P';
                P2Pstr = P2Pstr_C2Cstr;
                break;
            case "C2C":
                P2Pstr_C2Cstr.type = 'C2C';
                C2Cstr = P2Pstr_C2Cstr;
                break;
        }
        if (Refresh == null) {
            arr.push(P2Pstr);
            arr.push(C2Cstr);
        } else {
            arr = JSON.parse(Refresh);
            switch (whatbusiness) {
                case "P2P":
                    arr[0] = P2Pstr;
                    break;
                case "C2C":
                    arr[1] = C2Cstr;
                    break;
            }
        }
        localStorage.setItem("RefreshCurrency", JSON.stringify(arr));
        // 记录缓存 -结束
        getCoinInfoList(currencyId, whatbusiness);
    });

    // 点击购买
    $('body').on('click', '.buyOrder', function() {
        //p2p下单模块维护 禁用卖出按钮
        if (Maintain(forbidOrder, P2P_Maintain_Place, P2P_Maintain_Pattern)) {
            return;
        }
        getBuyingInfo($(this));
    });

    //点击星星收藏
    $('body').on('click', '.myCollection', function() {
        var _this = $(this);
        var currency_id = _this.data('currency-id');
        var collection_status = _this.attr('collection-status');
        collectionMyCoin(_this, currency_id, collection_status);
    });

    // 建强补充  实名认证提示后自动请求改变不提示
    $('body').on('click', '.NameAlert button.close', function() {
        if (tips != 2) {
            $.ajax({
                type: 'post',
                url: '/PersonalCenter/changeStatus'
            });
        }
    });

    /*完善左边导航栏手机时点不了*/
    var coinSelect_trueF = false;
    $('body').on('click', '.coinSelect', function() {
        var $li_dropdown = $(this).parents('li.dropdown');
        if ($(document).width() < 1280) {
            if (coinSelect_trueF) {
                $li_dropdown.removeClass('open');
                coinSelect_trueF = false;
            } else {
                $li_dropdown.addClass('open');
                coinSelect_trueF = true;
            }
        }

    });

});
//左边导航栏余额块 页面刷新公共函数   
/**
 *  参数1 ：2 =  type没有缓存的时候默认第一个li加active ，1 = 有缓存记录加载liactive
 * 	参数2   ： 传入缓存上次选中得 active序号
 * 	参数3   ：0 =  ctwoc 需要调用的刷新函数
 */
function target_public_aside(type, target_public_refresh, CtwoC_type) {
    var find_active_coin, find_active_CoinName;
    if (type == 1) {
        find_active_coin = $("#coinMoneyList .coinName[currency-id='" + target_public_refresh + "']");
        if (CtwoC_type == 0) {
            $('.change_ctoc_coin').text(find_active_coin.attr('currency-name'));
        }
    } else {
        find_active_coin = $("#coinMoneyList li:nth-child(1)");
    }
    find_active_CoinName = find_active_coin.attr("currency-name") //获取当前币种名
        //动态侧边栏选中币种
    $('.coinSelect').html(find_active_coin.find('a').html() + '<i class="fa fa-angle-down"></i>');
    $('#coinMoneyList .coinName.active').removeClass('active');
    find_active_coin.addClass("active");
    getKlineDataRaw(find_active_CoinName); //获取当前币种名的k线图
    setTimeout(function() {
        var C_aside_Currency_balance = $('.currency-id' + '-' + find_active_coin.attr('currency-id')).text();
        if (C_aside_Currency_balance == '' || C_aside_Currency_balance == null) {
            C_aside_Currency_balance = '0.00000000';
        }
        $('.aside_Currency_balance').text(C_aside_Currency_balance);
    }, 1000);
    $('.aside_Currency_content').text(find_active_coin.attr('currency-name'));
}
/**
 * 获取待买入订单列表
 * @author 2017-11-08T16:54:53+0800
 * @param  {Number} p [description]
 * @return {[type]}   [description]
 */
function getPendingPurchaseOrderList(p) {
    //市场订单维护 清空订单信息
    if (Maintain(listOrder, P2P_Maintain_Marketorder, P2P_Maintain_Pattern)) {
        $('#dataPage_order_1').html('');
        $('#pendingOrder').html('');
        clearInterval(Maintain_getPending);
        return;
    }
    var currencyId = $('#getCurrencyId').val();
    var areaId = $('#buyAreaId').val();
    var bankId = $("#buyBankId").parents(".btn-group.bootstrap-select").children("button").html() ? $('#buyBankId').val() : "";
    var num1 = $('#num1').val();
    var num2 = $('#num2').val();
    var price1 = $('#price1').val();
    var price2 = $('#price2').val();
    if (p == "undefined") {
        p = 1;
    }
    var data = {
        'areaId': areaId,
        'currencyId': currencyId,
        'bankId': bankId,
        'num1': num1,
        'num2': num2,
        'price1': price1,
        'price2': price2,
        'p': p
    };
    check_user('/OffTrading/getPendingPurchaseOrderList', data, 'get').then(function(response) {
        if (response.code == 200) {
            var html = '';
            var page = response.data.page_show;
            var data = response.data.order_list;
            for (var x in data) {
                html += getPendigOrderListHtml(data[x]);
            }
            html += '<td >' + '</td>';
            $('#dataPage_order_1').html(page);
            $('#pendingOrder').html(html);

        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
    return data;
}
/**
 * 待买入订单html片段代码
 * @author 2017-11-08T17:36:23+0800
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function getPendigOrderListHtml(data) {

    if (!data.level) {
        data.level = 0;
    }
    var html = '<tr>';
    html += '<td>' + data.bank_name + '</td>';
    html += '<td class="Lwprice">' + data.price + '</td>';
    html += '<td class="count">' + data.num + '</td>';
    html += '<td class="count">' + data.total_rate + '</td>';
    html += '<td><img src="/Public/Home/img/VIP_' + data.level + '.svg" width="53" /></td>';
    html += '<td class="active">';
    html += '<a class=" BuyOrder1"><i  class="buyOrder iconfont icon-cart"   currency-id="' + data.currency_id + '"';
    html += ' price="' + data.price + '" num="' + data.num + '" area-id="' + data.area_id + '" ';
    html += ' user-bank-id="' + data.bank_id + '" order-id="' + data.id + '"></i></a>';
    html += '</td>';
    html += '</tr>';
    return html;
}
/**
 * promies 解决嵌套ajax
 */
var ajaxPromise = new Promise(function(resolve) {
    resolve();
});
/**
 * 获取购买订单信息，并验证购买者资格
 * @author 2017-11-22T10:52:56+0800
 * @param  {[type]} _this [description]
 * @return {[type]}       [description]
 */
function getBuyingInfo(_this) {
    ajaxPromise.then(function() {
        return isRealName();
    }).then(function(response) {
        if (response.code == 200) {
            //检测资金密码
            return checkTradePwd();
        }
        return response;

    }).then(function(response) {
        if (response.code == 200) {
            //检测用户封号
            return checkIsOvertime()
        }
        return response;
    }).then(function(response) {
        if (response.code == 200) {
            //检测用户未完成订单
            var currencyId = _this.attr('currency-id');
            return checkIsCompleteOrder(currencyId, 1);
        }
        return response;

    }).then(function(response) {
        if (response.code == 200) {
            $('#buyTradePwd').val('');
            $('#buyVerifyCode').val('');

            // 订单和用户所在区域不一致，文案显示不一样
            var areaId = _this.attr('area-id');
            var userAreaId = $('#defaultArra').val();
            if (areaId != userAreaId) {
                $('#otherArea').show();
                $('#areaTips').show();
            } else {
                $('#otherArea').hide();
                $('#areaTips').hide();
            }

            getBuyOrderBankInfo(_this); // 获取待买入订单汇款信息
        }
    });
}
/**
 * 获取用户是否实名认证
 * @author 2017-10-31T15:07:11+0800
 */
function isRealName() {
    return $.ajax({
        type: 'post',
        url: '/checkUserInfo/isUserRealName',
        data: '',
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            // 在顶部提示，用于首次实名验证
            if (response.code != 200) {
                // 需要返回isTrue的实名验证
                alertBox(response.msg);
            }
            return response;
        }
    });
}
/**
 * 检测用户买入/卖出是否有未完成的订单
 * @author 2017-11-21T14:39:45+0800
 * @return {[type]} [description]
 */
function checkIsCompleteOrder(currencyId, type) {
    var data = {
        'currencyId': currencyId,
        'type': type
    };
    return $.ajax({
        type: 'post',
        url: '/OffTrading/checkIsCompleteOrder',
        data: data,
        dataType: 'json',
        async: false,
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            if (response.code != 200) {
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
            return response
        }
    });

}

/**
 * 检测用户是否超时封号中
 * @author 2017-11-14T16:47:40+0800
 * @return {[type]} [description]
 */
function checkIsOvertime() {
    return $.ajax({
        type: 'post',
        url: '/checkUserInfo/checkUserIsOverTime',
        data: '',
        dataType: 'json',
        async: false,
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            if (response.code != 200) {
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
        }
    });
}

/**
 * 检测用户是否有交易密码
 * @author 2017-11-09T15:47:26+0800
 * @return {[type]} [description]
 */
function checkTradePwd() {
    return $.ajax({
        type: 'post',
        url: '/checkUserInfo/checkUserIsTradePwd',
        data: '',
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        async: false,
        success: function(response) {
            if (response.code != 200) {
                alertBox(response.msg, ($(document).width() / 2), ($(document).height() / 2));
            }
            return response
        }
    });

}

/**
 * 获取待买入汇款信息
 * @author 2017-11-09T14:57:16+0800
 * @param  {[type]} _this [description]
 * @return {[type]}       [description]
 */
function getBuyOrderBankInfo(_this) {

    var currencyId = _this.attr('currency-id');
    var orderId = _this.attr('order-id');
    var bankId = _this.attr('user-bank-id');
    var price = _this.attr('price');
    var num = _this.attr('num');
    var areaId = _this.attr('area-id');
    var coinName = $('#currencyName').html();
    price = ChangeFixed(price, 3);
    num = ChangeFixed(num, 5);
    var total = price * num;
    total = ChangeFixed(total, 3);
    var areaArr = getPriceBychangeCoin(areaId);
    var areaTotal = total * areaArr[1];
    areaTotal = ChangeFixed(areaTotal, 3);

    // 如果是台湾截取整数
    if (areaId == 2) {
        areaTotal = ChangeFixed(areaTotal, 1) + '00';
    }


    $('#buySubInfo').attr('buy-order-id', orderId).attr('user-bank-id', bankId).attr('currency-id', currencyId);


    check_user('/OffTrading/getUserBankInfo', {
        'order_id': orderId
    }, 'post').then(function(response) {
        if (response.code == 200) {
            var bankInfo = response.data.bank_info;
            $('#buyBankName').text(bankInfo.bank_name).attr('user-bank-id', bankId);
            $('#buyCoinName').text(coinName);
            $('#buyPrice').text(price);
            $('#buyNum').text(num);
            $('#buyTotalMoney').text(total);
            $('.buyReferMoney-price').text(areaArr[0]);
            $('#buyReferMoney').html(areaTotal);
            $('#static').modal('show');
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}

/**
 * 获取默认币种的价格
 * @author 2017-10-30T15:14:08+0800
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function getDefaultCoin(data) {

    var areaId = $('#userArea').val();
    var priceArr = getPriceBychangeCoin(areaId);
    var totalPrice = data.last * priceArr[1];

    totalPrice = ChangeFixed(totalPrice, 3);

    // 如果是台湾截取整数
    if (areaId == 2) {
        totalPrice = ChangeFixed(totalPrice, 1) + '00';
    }

    $('#referenceMoney').html(totalPrice); // 计算当前地区的价格
    $('#nowMoney').html(ChangeFixed(data.last, 3)); // 计算当前价格
    $('#coinNameOrderList').html(data.coin_name);
    $('#getCurrencyId').val(data.currency_id);

    $('#currencyName').html(data.coin_name);
    $('#subSellInfo').attr('currency-id', data.currency_id);
    $('#coinHeighMoney').html(data.heigh);
    $('#coinLowMoney').html(data.low);
    $('#coinRtqMoney').html(data.rtq);
    $('#coinNameMoney').html(data.coin_name + '/USD');
    $('#coinTotalMoney').html(data.money_usa);

}

/**
 * 切换币种，获取币种价格，及根据地区获取当前地区的价格
 * @author 2017-11-06T15:52:38+0800
 * @return array
 */
function getPriceBychangeCoin(areaId) {
    var unit, rate, $cityRate;
    var class_Area = {
        '1':$('#hkdRate'),
        '2':$('#twdRate')
    }
    $cityRate = class_Area[areaId] ? class_Area[areaId] : $('#cnyRate')
    unit = $cityRate.attr('unit');
    rate = $cityRate.val();

    return [unit, rate];
}

/**
 * 获取个人收藏的币种
 * @author 2017-10-26T20:05:29+0800
 * @return array
 */
function getMyCollection() {
    var returnData = null;
    $.ajax({
        type: 'post',
        url: '/CoinTradeInfo/getMycollection',
        data: '',
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        async: false,
        success: function(response) {
            returnData = response.data.currency_arr;
        }
    });

    return returnData;
}

/**
 * 获取币种信息列表
 * @author 2017-10-25T16:12:18+0800
 */
var XH_getCoinInfoList;
//牌价接口请求失败时，循环调用函数
function loop_getCoinInfoList(currencyId) {
    check_user('/CoinTradeInfo/getCoinInfoList', {
        'currencyId': currencyId
    }, 'post').then(function(response) {
        var coinInfoList = response.coinInfoList;
        var html = '';
        for (var x in coinInfoList) {
            html += getCoinHtml(coinInfoList[x]);
        }
        $('#tab-underline-home3 .panel-body').html(html); // 牌价
    });

}

function getCoinInfoList(currencyId, WhereFrom) {
    var ifreturn = 0;
    var default_coin = $('#coinMoneyList > li:nth-child(1)');
    currencyId = currencyId ? currencyId : default_coin.attr('currency-id');


    check_user('/CoinTradeInfo/getCoinInfoList', {
        'currencyId': currencyId
    }, 'post').then(function(response) {
        var coinInfoList = response.coinInfoList;
        var checkCoinInfo = response.checkCoinInfo;

        // 检测币种下架
        if (checkCoinInfo.status == 0) {
            clear_data(WhereFrom);
            return false;
        }

        //判断牌价true才渲染，false循环请求牌价
        if (coinInfoList != false) {
            var html = '';
            for (var x in coinInfoList) {
                html += getCoinHtml(coinInfoList[x]);
            }
            $('#tab-underline-home3 .panel-body').html(html); // 牌价
            clearInterval(XH_getCoinInfoList);
        } else {
            XH_getCoinInfoList = setInterval(function() {
                loop_getCoinInfoList(currencyId);
            }, 5000);
        }


        getDefaultCoin(checkCoinInfo); // 获取选中币种实时价格及交易额
        // 判断只有在P2P交易页面才调用此函数
        if (/UserCenter/.test(window.location.href) && /index/.test(window.location.href)) {
            getPendingPurchaseOrderList(1, currencyId); // 切换币种获取币种待交易的订单
        } else {
            clearInterval(Maintain_getPending);
        }

        //选择币种时----交易下的币种加上active
        $("#tab-underline-home3 .quoteprice_box[box-id='" + currencyId + "']").addClass("active");
        // 记录币种刷新缓存

        // var whichJSON = { currencyName: default_coin.attr('currency-name'), currencyId: default_coin.attr('currency-id') };
        // if (localStorage.getItem("RefreshCurrency")) {
        //     var refresh = JSON.parse(localStorage.getItem("RefreshCurrency"));
        //     //默认币种信息
        //     if (WhereFrom == "P2P") {
        //         whichJSON = refresh[0] == null ? whichJSON : refresh[0];
        //         target_public_aside(1, whichJSON.currencyId, 1);
        //         //确认订单模块的【出售币种】的icon
        //     } else if (WhereFrom == "C2C") {
        //         whichJSON = refresh[1] == null ? whichJSON : refresh[1];
        //         target_public_aside(1, whichJSON.currencyId, 0);
        //     }

        // }
        // var coinSelect_html = '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-' + whichJSON.currencyName + '1"></use></svg> ' + whichJSON.currencyName + '<i class="fa fa-angle-down"></i> ';
        // $(".coinSelect").html(coinSelect_html);
        // getKlineDataRaw(whichJSON.currencyName);
    });
}
/**
 * 获取用户币种余额
 * @author 2017-10-26T17:38:09+0800
 * @return {[type]} [description]
 */
function getMyCurrencyList() {

    check_user('/CoinTradeInfo/getUserCurrency', {}, 'post').then(function(response) {
        var html = '';
        var myCurrList = response.data.myCurrList;
        for (var x in myCurrList) {
            html += getCurrencyListHtml(myCurrList[x]);
        }
        $('#myCurrencyList').append(html);
    });
}

/**
 * 判断某元素是否存在数组中
 * @author 2017-10-26T15:34:51+0800
 * @param  {[type]} search [description]
 * @param  {[type]} array  [description]
 * @return {[type]}        [description]
 */
function in_array(search, array) {
    for (var i in array) {
        if (array[i] == search) {
            return true;
        }
    }
    return false;
}

/**
 * 收藏币种
 * @author 2017-10-25T16:48:50+0800
 */
function collectionMyCoin(_this, currency_id, collection_status) {

    check_user('/CoinTradeInfo/collectionMyCoin', {
        'currency_id': currency_id,
        'collection_status': collection_status
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

/**
 * 获取币种余额html
 * @author 2017-10-26T17:33:26+0800
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function getCurrencyListHtml(data) {
    var html = '<tr>';
    html += '<td>' + data.coin_name + '</td>';
    html += '<td class="text-right two_price"> <span class="currency-id-' + data.currency_id + '">' + data.num + '</span></td>';
    html += '</tr>';

    return html;
}

/**
 * 获取币种信息列表html
 * @author 2017-10-25T16:06:56+0800
 * @param  {array} data [description]
 * @return string
 */
function getCoinHtml(data, type) {
    type = type ? type : 1; // 1表示币种信息列表，2表示收藏币种列表
    var color, arrow;
    if (data.perc_status > 0) {
        color = '#4bd2b7';
        arrow = 'fa fa-long-arrow-up';
    } else {
        color = '#e8653b';
        arrow = 'fa fa-long-arrow-down';
    }

    var html = '<div class="quoteprice_box" box-id=' + data.currency_id + '><ul>';
    //quote_coinname
    html += '<li class="quote_coinname" >';
    html += '<div class="col-lg-6 col-md-6 col-xs-6 getImgicon"><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-' + data.coin_name + '1"></use></svg>  ' + data.coin_name + '</div>';
    html += '<div class="col-lg-6 col-md-6 col-xs-6 text-right">';
    if (type == 1) {
        var tag;
        if (data.col_status == 1) {
            tag = 'fill';
        } else {
            tag = 'line';
        }
        html += '<a href="" id="tag02" data-toggle="tab" aria-expanded="false">';
        html += ' <svg collection-status="' + data.col_status + '"  aria-hidden="true" class="tag02 myCollection icon icon_norepeat"';
        html += ' data-currency-id="' + data.currency_id + '"><use xlink:href="#icon-ic_left_collect_' + tag + '"></use></svg></a>';
    }
    html += '</div>';
    html += '<div class="clearfix"></div>';
    html += '</li>';
    //quote_nowprice
    html += '<li class="quote_nowprice text-center"><div class="col-lg-12" style="color:' + color + '">' + setZero(data.last_usa) + '</div><div class="clearfix"></div></li>';
    //quote_volume
    html += '<li class="quote_volume">';
    html += '<div class="col-lg-7 col-md-7 col-xs-7"><span class="font11">Volume:' + setZero(data.num) + ' ' + data.coin_name + '</span></div>';
    html += '<div class="col-lg-5 col-md-5 col-xs-5 text-right">';
    html += '<span class="font11">24H :</span><span class="vol_font font11" style="color:' + color + '">' + setZero(data.perc_per) + '  <i class="' + arrow + '"></i></span>';
    html += '<span class="kline"></span>';
    html += '</div>';
    html += '<div class="clearfix"></div>';
    html += '</li>';
    html += '</ul></div>';
    return html;
}

// 判断null设置为0
function setZero(num) {
    if (num == null) {
        return 0;
    } else {
        return num;
    }
}

/**
 * @author 宋建强 kline 数据渲染 
 * @param currency_id  币种id
 */
function getKlineDataRaw(CurrencyName) {
    $('#kline_iframe').attr('src', '/TradingView/rawTrade/CoinType/' + CurrencyName);
}