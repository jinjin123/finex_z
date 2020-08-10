/**
 * 交易相关js
 */

var type = 1; // 全局变量，订单类型;1正在交易全部订单;2正在交易买入订单;3正在交易卖出订单;4历史买入订单;5历史卖出订单;6撤销订单;
var countLast, unConfirmCountLast;
// 定时获取正在交易订单列表
var seconds = 10000;
var timeInt = startInter(10000);


$(document).ready(function() {
    getOrderList(1, 1); // 获取用户订单列表
    getPriceBychangeCoin(); // 获取币种建议价格
    // 页面刷新时记录 筛选条件
    var box_pai = JSON.parse(localStorage.getItem("box-pai"));
    //P2P搜索框数据
    if (box_pai) {
        $("#search_tab .SearchArea").find('i').eq(0).html(box_pai.areaIdHTML);
        $("body #buyAreaId").val(box_pai.areaId);
        getBankInfoByArea(box_pai.areaId);
        setTimeout(function() {
            $("body #buyBankId").val(box_pai.bankId).trigger('change')
        }, 1000)
        $('#num1').val(box_pai.num1);
        $('#num2').val(box_pai.num2);
        $('#price1').val(box_pai.price1);
        $('#price2').val(box_pai.price2);
    }
})
$(function() {
    // 正在交易 全部订单
    $('body').on('click', '#getAllOrder', function() {
        type = 1;
        getOrderList(1, 1);
        stopInter(timeInt);
        timeInt = startInter(seconds);
    });
    // 正在交易 买入订单
    $('body').on('click', '#getBuyOrder', function() {
        type = 2;
        getOrderList(1, 2);
        stopInter(timeInt);
        timeInt = startInter(seconds);
    });
    // 正在交易 卖出订单
    $('body').on('click', '#getSellOrder', function() {
        type = 3;
        getOrderList(1, 3);
        stopInter(timeInt);
        timeInt = startInter(seconds);
    });
    // 历史订单 买入订单
    $('body').on('click', '#getOverBuyOrder', function() {
        type = 4;
        getOrderList(1, 4);
        timeInt = stopInter(timeInt);
    });
    // 历史订单 卖出订单
    $('body').on('click', '#getOverSellOrder', function() {
        type = 5;
        getOrderList(1, 5);
        timeInt = stopInter(timeInt);
    });
    // 历史订单 撤销订单
    $('body').on('click', '#getRevokeOrder', function() {
        type = 6;
        getOrderList(1, 6);
        timeInt = stopInter(timeInt);
    });
    // 查找待买入订单
    $('body').on('click', '#queryPendingOrder', function() {
        var data = getPendingPurchaseOrderList();
        $("#search").modal('hide');
        // 首页的订单簿的国家标签改变
        var countryStandrad = ["HK", "TW", "CN"];
        $("#search_tab .SearchArea").find('i').eq(0).html(countryStandrad[data.areaId - 1]);
        var str = {
            areaId: data.areaId,
            areaIdHTML: countryStandrad[data.areaId - 1],
            bankId: data.bankId,
            currencyId: data.currencyId,
            num1: data.num1,
            num2: data.num2,
            price1: data.price1,
            price2: data.price2
        };
        localStorage.setItem("box-pai", JSON.stringify(str));
    });

    // 查找待买入订单，切换交易区，变更银行
    $('body').on('change', '#buyAreaId', function() {
        var val = $(this).val();
        getBankInfoByArea(val);
    });

    // 打开卖出弹窗初始化弹窗数据
    $('body').on('click', 'button[data-target="#sell"]', function() {
        getUserBindBank(); // 获取银行卡列表
        getNowMoney(); // 获取实时价格
    });

    // 切换地区，获取用户的绑定银行卡信息，及计算参考价格
    $('body').on('change', '#userArea', function() {
        getUserBindBank();
        getNowMoney();
        referenceTotalPrice();
    });

    // 切换银卡卡信息
    $('body').on('change', '#userBindBank', function() {
        var $userBindBank = $("#userBindBank");
        var bankNum = $userBindBank.find("option:selected").attr("bank-num"),
            bankName = $userBindBank.find("option:selected").text();
        $('#bankName').html(bankName);
        $('#bankNum').html(bankNum);
    });

    // 卖出提交
    $('body').on('click', '#subSellInfo', function() {
        var _this = $(this);
        //点击 disable 3秒
        setTimeSecd(_this);
        // 检测卖出参数
        checkSubParams(_this);
    });

    // 关闭卖出弹窗清空总额和参考总额
    $('body').on('click', '#sell .close', function() {
        $(".saletotal,.referenceSaletotal").text("0.00");
    });


    // 点击银行卡选项的时候进行展示当前和参考价格
    // $("#userBindBank").siblings(".dropdown-menu").find("li").on("click", function() {
    //     getNowMoney();
    // });

    // 确认打款
    $('body').on('click', '#confirmPayMoney', function() {
        var _this = $(this);
        setTimeSecd(_this);
        confirmOrderBuyOrAccept(_this);
    });

    // 确认收款
    $('body').on('click', '#confirmAccept', function() {
        var _this = $(this);
        setTimeSecd(_this);
        confirmOrderPayOrAccept(_this);
    });

    // 提交购买订单
    $('body').on('click', '#buySubInfo', function() {
        var _this = $(this);
        subBuyInfo(_this);
        setTimeSecd(_this);
    });

    // 撤销订单
    $('body').on('click', '#revokeOrderById', function() {
        var orderId = $(this).attr('order-id');
        revokeOrderById(orderId);
    });
    // 获取订单详情
    $('body').on('click', '.getDetails', function() {
        getOrderDeatil($(this));
        $('#detail').show();
    });
    // 撤销订单触发confirm事件
    $('body').on('click', '.revokeNowOrder', function() {
        $('#revokeOrderById').attr('order-id', $(this).attr('order-id'));
    });
    // 确认打款获取银行卡信息
    $('body').on('click', '.confirmOrder', function() {
        getBankInfo($(this), 1);
    });
    // 匯款信息获取银行卡信息
    $('body').on('click', '.bankInfo', function() {
        getBankInfo($(this), 2);
    });
    // 确认收款
    $('body').on('click', '.confirmAccept', function() {
        var _this = $(this);
        var orderId = _this.attr('order-id');
        var amount_count = _this.attr('amount_count');

        $('#confirmAccept').attr('order-id', orderId);
        $('#unconfirmAccept').attr('order-id', orderId);

        // 判断剩余秒数是否为0
        if (amount_count > 0) {
            $('#unconfirmAccept').attr('disabled', true);
        }
        //如已存在倒计时循环,先清除
        if (unConfirmCountLast != "undefined") {
            clearInterval(unConfirmCountLast);
        }
        unconfirmCountdown(amount_count);

        // 寫入銀行卡信息
        getBankInfo(_this, 3);
    });
    /**
     * --------- 订单薄 ---------
     */

    $('body').on('click', '.small-Trading', function() {
        //p2p我的订单维护 全部 历史 按钮禁用
        if (Maintain(dealOrder, P2P_Maintain_order, P2P_Maintain_Pattern)) {
            $(this).find('ul').remove();
            return;
        }
    });

    //卖出单价
    $("#touchspin-example4").bind('input propertychange', function() {
        var _this = $(this);
        if (_this.val() == '') {
            $(".price-show").text("0.00");
            SaleTotal();
            referenceTotalPrice();
            return;
        }
        validationNumber(_this, 1);
        $(".price-show").text(_this.val());
        SaleTotal();
        referenceTotalPrice();

    });
    //卖出量
    $("#coinNum").bind('input propertychange', function() {
        var _this = $(this);
        if (_this.val() == '') {
            $(".quantity-show").text("0.0000");
            SaleTotal();
            referenceTotalPrice();
            return;
        }
        validationNumber(_this, 2);
        $(".quantity-show").text(_this.val());
        SaleTotal();
        referenceTotalPrice();
    });

    $('body').on('click', '#unconfirmAccept', function() {
        //点击 disable 3秒
        var _this = $(this);
        setTimeSecd(_this);
        var orderId = _this.attr('order-id');
        var tradePwd = $("#payTradePwd").val();

        check_user('/OffTrading/unGetMoney', {
            'order_id': orderId,
            'tradePwd': tradePwd,
        }, 'post').then(function(response) {
            if (response.code == 200) {
                BottomalertBox('bottom', response.msg, "success", "center");
                $('#static1').modal('hide');
                $('.confirmOrder-' + orderId).remove();
                $('.confirmBtn-' + orderId).html(response.data.status_str);
                loadLate(2000);
            } else {
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
        });
    });
});



/**
 * 暂停定时器
 * @author 2017-12-21T10:55:02+0800
 */
function stopInter(interval) {
    if (interval) {
        clearInterval(interval);
        interval = null;
    }

    return interval;
}

/**
 * 开始定时器
 * @author 2017-12-21T10:57:20+0800
 * @param int second 秒;2000表示2秒
 */
function startInter(second) {
    var interval = setInterval(function() {
        var _currentpage = $('#dataPage' + type + ' li.pagation-active .current-page').text();
        getOrderListForPage(_currentpage);
    }, second);
    return interval;
}

function getNowMoney() {
    var areaId = $('#userArea').val();
    var priceArr = getPriceBychangeCoin(areaId);
    var coinPrice = $('#coinRtqMoney').html();
    coinPrice = ChangeFixed(coinPrice, 3);
    var totalPrice = coinPrice * priceArr[1];
    // 如果是台湾截取整数
    if (areaId == 2) {
        totalPrice = ChangeFixed(totalPrice, 1) + '00';
    }
    totalPrice = ChangeFixed(totalPrice, 3);


    $('#nowMoney').html(coinPrice);
    $('.referenceMoney-price').text(priceArr[0]);
    $('#referenceMoney').html(totalPrice);

}



/**
 * 提交购买订单信息
 * @author 2017-11-09T16:50:23+0800
 * @param  {[type]} _this [description]
 * @return {[type]}       [description]
 */
function subBuyInfo(_this) {
    var orderId = _this.attr('buy-order-id');
    var bankId = _this.attr('user-bank-id');
    var currencyId = _this.attr('currency-id');
    var tradePwd = $('#buyTradePwd').val();
    var verifyCode = $('#buyVerifyCode').val();

    if (!tradePwd) {
        BottomalertBox('bottom', pwdMsg, "fail", "center");
        return false;
    }

    check_user('/OffTrading/buying', {
        'orderId': orderId,
        'bankId': bankId,
        'currencyId': currencyId,
        'tradePwd': tradePwd,
        'verifyCode': verifyCode
    }, 'post').then(function(response) {
        if (response.code == 200) {
            BottomalertBox("bottom", response.msg, "success", "center");
            // 订单提交成功后延时刷新
            loadLate(2000);
        } else if (response.code == 233) {
            BottomalertBox('bottom', response.msg, "fail", "center");
        } else {
            $('#static').modal('hide');
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}

/**
 * 根据地区获取银行卡信息
 * @author 2017-11-08T18:09:34+0800
 * @param  {[type]} areaId [description]
 * @return {[type]}        [description]
 */
function getBankInfoByArea(areaId) {
    check_user('/UserCenter/getBankInfoByArea', {
        'areaId': areaId,
    }, 'post').then(function(response) {
        var bankInfo = response.bankInfo;
        var $buyBankId = $("#buyBankId");
        // 开户银行显示框
        var select_box = $buyBankId.parents(".btn-group.bootstrap-select");
        // 设置开户银行显示框默认id为空
        select_box.children("button").attr("title", "");
        $buyBankId.empty();
        if (bankInfo) {
            $buyBankId.find('optgroup').remove();
            var html = '';
            for (var x in bankInfo) {
                html += '<option value="' + bankInfo[x].id + '" >' + bankInfo[x].bank_name + '</option>';
            }
            html += '</optgroup>';

            $buyBankId.append(html).selectpicker('refresh');
        }
    });
}

/**
 * 检测用户是否绑定银行卡
 * @author 2017-11-16T10:52:42+0800
 * @return {[type]} [description]
 */
function checkUserBindBank() {
    return $.ajax({
        type: 'post',
        url: '/UserCenter/checkUserBindBank',
        data: '',
        dataType: 'json',
        success: function(response) {
            if (response.code != 200) {
                alertBox(response.msg);
            }
            return response;
        }
    });

}

/**
 * 检测用户是否填写单价及数量
 * @author 2017-11-06T16:58:23+0800
 * @return {[type]} [description]
 */
function checkSubParams(_this) {
    var coinPrice = $('#touchspin-example4').val();
    var coinNum = $('#coinNum').val();
    var userBankId = $('#userBindBank').val();

    var isTradePwd = true;
    var tradePwdMsg = '';
    var currencyId = _this.attr('currency-id');
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
            //检测银行卡
            return checkUserBindBank();
        }
        return response;
    }).then(function(response) {
        if (response.code == 200) {
            //检测用户超时封号
            return checkIsOvertime();
        }
        return response;

    }).then(function(response) {
        if (response.code == 200) {
            //检测用户未完成订单
            return checkIsCompleteOrder(currencyId, 2);
        }
        return response;

    }).then(function(response) {
        if (response.code == 200) {
            var userArea = $('#userArea').val();
            var userBankId = $('#userBindBank').val();
            var coinPrice = $('#touchspin-example4').val();
            var coinNum = $('#coinNum').val();
            var sellPwd = $('#sellPwd').val();
            if (!userBankId) {
                //检测银行卡
                alertBox(bankMsg);
                return false;
            }

            if (coinPrice <= 0) {
                //检测金额
                BottomalertBox('bottom', priceMsg, "fail", "center");
                return false;
            }

            if (coinNum <= 0) {
                //检测数量
                BottomalertBox('bottom', numMsg, "fail", "center");
                return false;
            }
            if (!sellPwd) {
                //检测资金密码
                BottomalertBox('bottom', pwdMsg, "fail", "center");
                return false;
            }
            $.ajax({
                type: 'post',
                url: '/OffTrading/selling',
                data: {
                    'currencyId': currencyId,
                    'userArea': userArea,
                    'transpwd': sellPwd,
                    'userBankId': userBankId,
                    'coinPrice': coinPrice,
                    'coinNum': coinNum
                },
                dataType: 'json',
                success: function(response) {
                    if (response.code == 200) {
                        $('#ConfirmPass').modal('hide');
                        BottomalertBox('bottom', response.msg, "success", "center");
                        loadLate(2000);
                    } else {
                        BottomalertBox('bottom', response.msg, "fail", "center");
                    }
                }
            });
        }
    });
}

/**
 * 获取用户绑定银行卡信息
 * @author 2017-11-06T14:59:28+0800
 * @return {[type]} [description]
 */
function getUserBindBank() {
    var areaVal = $('#userArea').val(),
        html = '';

    check_user('/UserCenter/getUserBindBank', {
        'areaId': areaVal
    }, 'post').then(function(response) {
        var isSelected = '',
            bankNum = '',
            bankName = '';
        if (response != '') {
            for (var x in response) {

                if (response[x].default_status == 1 || x == 0) {
                    isSelected = 'selected="true"';
                    bankName = response[x].bank_name;
                    bankNum = response[x].bank_num;
                }
                html += '<option bank-num="' + response[x].bank_num + '" value="' + response[x].id + '" ' + isSelected + '>' + response[x].bank_name + '</option>';
                isSelected = '';
            }
        } else {
            bankName = bindBankTips;
            html = '<option value="">' + bankName + '</option>';
        }
        $('#bankName').html(bankName);
        $('#bankNum').html(bankNum);
        $('#userBindBank').html(html).selectpicker("refresh");
        $('#select2-userBindBank-container').attr('title', bankName).html(bankName);
    });
}

/**
 * 获取id属性名称
 * @author 2017-11-01T15:01:57+0800
 * @param  {[type]} idType [description]
 * @return {[type]}        [description]
 */
function getIdAttr(idType) {
    var idAttrName = {
        '1':'myOrderListAll',
        '2':'myOrderListBuy',
        '3':'myOrderListSale',
        '4':'myOverBuyOrder',
        '5':'myOverSaleOrder',
        '6':'myRevokeOrder'
    };
    return idAttrName[idType] ? idAttrName[idType] : idAttrName['1'];
}

/**
 * 后端分页用的方法，方便后端使用
 * @author 2017-12-21T11:00:45+0800
 * @param  int p 页码
 */
function getOrderListForPage(p) {
    getOrderList(p, type);
}

/**
 * 剩余时间倒计时循环
 * @author 2017-12-25T14:56:35+0800
 * @return {[type]} [description]
 */

function timeLast(idType) {

    countLast = setInterval(function() {
        $("#" + idType + " .remaining_time").each(function() {
            var _this = $(this);
            var second = Number(_this.attr("time"));
            second--;
            if (second > 0) {
                _this.attr("time", second).text(P2P_formatSeconds(second));
            }
            //剩余秒数归0,加上超时告示class
            if (second <= 0) {
                _this.text(_this.attr('status-str')).removeClass("remaining_time").next().html(remainingTime_staus);
            }
        });
    }, 1000);

}
/**
 * 剩余时间倒计时循环停止
 * @author 2017-12-25T14:56:35+0800
 * @return {[type]} [description]
 */

function clearLast() {
    if (countLast != "undefined") {
        clearInterval(countLast);
    }
}

/**
 * 收款异常按钮开启时间倒计时
 * @author 2017-12-25T14:56:35+0800
 * @return {[type]} [description]
 */

function unconfirmCountdown(time) {
    var second = Number(time);
    unConfirmCountLast = setInterval(function() {
        second--;
        //剩余秒数归0,加上开启收款异常按钮
        if (second <= 0) {
            $('#unconfirmAccept').attr('disabled', false);
        }
    }, 1000);

}

/**
 * 获取订单列表
 * @author 2017-10-31T21:32:35+0800
 * @return {[type]} [description]
 */

function getOrderList(p, types) {
    if (p == "undefined") {
        p = 1;
    }
    var idType = getIdAttr(types);

    check_user('/OffTrading/getOrderList', {
        'type': types,
        'p': p
    }, 'get').then(function(response) {
        var html = '';
        var data = response.data;
        var page = response.page_show;
        for (var x in data) {
            html += getOrderListHtml(data[x]);
        }
        html += '<td >' + '</td>';
        $('#dataPage' + types).html(page);
        $('#' + idType).html(html);
        //如已存在倒计时循环,先清除
        if (countLast != "undefined") {
            clearInterval(countLast);
        }
        //订单列表生成后开始倒计时循环
        timeLast(idType);

    });
}

/**
 * 获取汇款银行卡信息
 * @author 2017-11-02T16:40:15+0800
 * @param  {[type]} _this [description]
 * 					type	根據不同狀態值修改不同的彈窗
 * 							1、确认打款获取银行卡信息
 * 							2、匯款信息获取银行卡信息
 * 							3、确认收款获取银行卡信息
 * @return {[type]}       [description]
 */
function getBankInfo(_this, type) {
    var orderId = _this.attr('order-id');

    check_user('/OffTrading/getUserBankInfo', {
        'order_id': orderId
    }, 'post').then(function(response) {
        if (response.code == 200) {
            processBankInfo(response.data.bank_info, type);
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}


/**
 * 处理银行卡信息
 * @author 2017-11-02T16:44:32+0800
 * @param  {[type]} data [description]
 * 					type	根據不同狀態值修改不同的彈窗
 * 							1、确认打款获取银行卡信息
 * 							2、匯款信息获取银行卡信息
 * 							3、确认收款获取银行卡信息
 * @return {[type]}      [description]
 */
function processBankInfo(data, type) {
    var obj;
    switch (type) {
        case 1:
            $('#bank_num').val(data.bank_num);
            $('#bank_user_name').val(data.bank_real_name);
            $('#bank_name').val(data.bank_name);
            $('#confirmPayMoney').html(data.btn_str).attr('order-id', data.order_id);

            // 判断是否有银行开户行地址
            if (data.bank_address) {
                $('#bank_user_address').val(data.bank_address);
                $('#bank_address').show();
            } else {
                $('#bank_address').hide();
            }
            break;
        case 2:
            obj = $('#payinfoMess');
            obj.find('#payname').val(data.bank_real_name);
            obj.find('#pay_bankname').val(data.bank_name);
            obj.find('#pay_time').val(data.pay_time);
            break;
        case 3:
            obj = $('#static1');
            obj.find('.bank_uname').text(data.buy_name);
            obj.find('.bank_name').text(data.bank_name);
            obj.find('.pay_time').text(data.pay_time);
            break;
    }
}


/**
 * 确认打款
 * @author 2017-11-02T17:38:05+0800
 * @param  {[type]} _this [description]
 * @return {[type]}       [description]
 */
function confirmOrderBuyOrAccept(_this) {
    var orderId = _this.attr('order-id');

    check_user('/OffTrading/confirmOrderPaidOrOrderAccept', {
        'order_id': orderId
    }, 'post').then(function(response) {
        if (response.code == 200) {
            BottomalertBox('bottom', response.msg, "success", "center");
            $('#payinfo').modal("hide");
            $('.confirmOrder-' + orderId).remove();
            $('.confirmBtn-' + orderId).html(response.data.status_str);
            loadLate(2000);
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}


/**
 * 确认收款
 * @author 2017-11-02T17:38:05+0800
 * @param  {[type]} _this [description]
 * @return {[type]}       [description]
 */
function confirmOrderPayOrAccept(_this) {
    var orderId = _this.attr('order-id');
    var tradePwd = $("#payTradePwd").val();

    check_user('/OffTrading/confirmOrderPaidOrOrderAccept', {
        'order_id': orderId,
        'tradePwd': tradePwd,
    }, 'post').then(function(response) {
        if (response.code == 200) {
            BottomalertBox('bottom', response.msg, "success", "center");
            $("#static1").modal("hide");
            $('.confirmOrder-' + orderId).remove();
            $('.confirmBtn-' + orderId).html(response.data.status_str);
            loadLate(2000);
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}


/**
 * 获取订单列表html代码
 * @author 2017-11-01T14:38:32+0800
 */
function getOrderListHtml(data) {
    var html = '<tr class="order-list-id-' + data.id + '">';
    html += '<td>' + data.buy_str + '</td>';
    html += '<td>' + data.coin_name + '</td>';
    html += '<td>' + data.price + '</td>';
    html += '<td>' + data.num + '</td>';
    html += '<td>' + data.total_price + '</td>';
    html += '<td>' + data.total_rate + '</td>';

    if (data.status_flag != 1) { // 判断用户是否撤单；1表示撤单
        html += getHtmlByFlage(data);
    } else {
        html += getDetailHtml(data);
        html += '<td>' + data.status_str + '</td>';
    }

    html += '</tr>';
    return html;
}

/**
 * 获取订单列表html代码片段
 * @author 2017-11-01T16:14:01+0800
 */
function getHtmlByFlage(data) {
    var html;
    if (data.complete_flag == 1) {
        html = getDetailHtml(data); // 获取历史订单html片段
    } else { // 获取正在交易订单html片段
        html = '<td>' + data.status_str + '</td>';
        html += getDetailHtml(data);
    }

    var className = 'confirmOrder';
    var dataTarget = 'payinfo';
    if (data.is_sell == 2) { // 确认收款
        className = 'confirmAccept';
        dataTarget = 'static1';
    }

    if (data.status == 1 || data.status == 2) {
        var rtime = P2P_formatSeconds(data.remaining_time);
        if (data.remaining_time <= 0) {
            html += '<td>' + data.remaining_time_str + '</td>';
        } else {
            html += '<td class="remaining_time" status-str="' + data.remaining_time_str + '" status="' + data.remaining_time_status + '" time="' + data.remaining_time + '">' + rtime + '</td>';
        }
    } else if (data.status == 8 || data.status == 0) {
        html += '<td>-</td>';
    } else {
        html += '<td>' + data.time + '</td>';
    }

    if (data.type_status == 1 && data.complete_flag == 0 && data.status == 2) { // 买入，等待賣家收款顯示匯款信息按鈕
        html += '<td><button type="button" data-toggle="modal" data-target="#payinfoMess"';
        html += ' order-id="' + data.id + '"';
        html += ' class="btn btn-xs bankInfo">' + data.type_str + '</button></td>';
    } else if (data.type_status == 1 && data.complete_flag == 0 && data.status != 0) { // 被买入，暂时无需操作
        html += '<td>' + data.type_str + '</td>';
    } else if (data.type_status == 0 && data.complete_flag == 0 && data.status != 0) { // 被买入，等待用户打款/确认收款
        html += '<td class="confirmBtn-' + data.id + '" ><button type="button" data-toggle="modal" data-target="#' + dataTarget + '"';
        html += ' order-id="' + data.id + '"';
        html += ' amount_count="' + data.abnormal_second + '"';
        html += ' class="btn btn-red btn-xs ' + className + ' confirmOrder-' + data.id + '">' + data.type_str + '</button></td>';
    } else if (data.type_status == 0 && data.complete_flag == 0 && data.status == 0) { // 挂单成功，没有被买入,可以撤销
        html += '<td><button type="button" data-toggle="modal" data-target="#cancel"';
        html += ' order-id="' + data.id + '"';
        html += ' class="btn btn-xs btn-red revokeNowOrder revokeNowOrder-' + data.id + '">' + data.type_str + '</button></td>';
    }

    if (data.complete_flag == 1) {
        html += '<td>' + data.status_str + '</td>';
    }
    return html;
}

/**
 * 获取订单详情按钮html片段
 * @author 2017-11-01T17:05:19+0800
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function getDetailHtml(data) {
    var html = '';
    html += '<td><button type="button" data-toggle="modal" data-target="#detail"';
    html += ' order-num="' + data.order_num + '"';
    html += ' add-time="' + data.add_time + '"';
    html += ' trade-time="' + data.trade_time + '"';
    html += ' buy-moneytime="' + data.shoukuan_time + '"';
    html += ' end-time="' + data.end_time + '"';
    html += ' class="btn btn-xs getDetails">' + data.details + '</button></td>';

    return html;
}

/**
 * 设置订单详情信息
 * @author 2017-11-01T17:07:47+0800
 */
function getOrderDeatil(_this) {
    var order_num = _this.attr('order-num');
    var add_time = _this.attr('add-time');
    var trade_time = _this.attr('trade-time');
    var dakuang_time = _this.attr('buy-moneytime');
    var end_time = _this.attr('end-time');

    $('#detail_order_num').html('<span id="d_order_num">' + order_num + '</span><button type="button" id="copy" style="position:relative;background-color: inherit;"  class="btn btn-green copyBtn" data-clipboard-action="copy" data-clipboard-target="#d_order_num"><svg class="icon input-icon icon-bz" style="left:5px;top:0px;" aria-hidden="true"><use xlink:href="#icon-ic_wallet_copy"></use></svg></button>');
    $('#detail_add_time').html(add_time);
    $('#detail_trade_time').html(trade_time);
    $('#detail_dakuang_time').html(dakuang_time);
    $('#detail_end_time').html(end_time);
}

/**
 * 撤销订单
 * @author 2017-11-01T21:04:01+0800
 * @param  {[type]} order_id [description]
 * @return {[type]}          [description]
 */
function revokeOrderById(orderId) {

    check_user('/OffTrading/revokeOrder', {
        'order_id': orderId
    }, 'post').then(function(response) {
        if (response.code == 200) {
            BottomalertBox('bottom', response.msg, "success", "center");
            $('.order-list-id-' + orderId).remove();
            loadLate(800);
        } else {
            BottomalertBox('bottom', response.msg, "fail", "center");
        }
    });
}

//计算卖出总额和参考总额
function SaleTotal() {
    if (!$("#coinNum").val() || !$("#touchspin-example4").val()) {
        $(".saletotal,.referenceSaletotal").text('0.00');
        return;
    }
    var SaleTotal = $("#coinNum").val() * $("#touchspin-example4").val();
    SaleTotal = ChangeFixed(SaleTotal, 3);
    $(".saletotal").text(SaleTotal);
}

//计算卖出参考总额
function referenceTotalPrice() {
    var SaleTotal = $(".saletotal").text();
    var areaId = $('#userArea').val();
    var priceArr = getPriceBychangeCoin(areaId);
    var referenceTotalPrice = SaleTotal * priceArr[1];
    // 如果是台湾截取整数
    if (areaId == 2) {
        referenceTotalPrice = ChangeFixed(referenceTotalPrice, 1) + '00';
    }
    referenceTotalPrice = ChangeFixed(referenceTotalPrice, 3);
    $(".referenceSaletotal").text(referenceTotalPrice);
}

//剩余时间倒计时
function P2P_formatSeconds(value) {
    var secondTime = parseInt(value); // 秒
    var minuteTime = 0; // 分
    var hourTime = 0; // 小时
    if (secondTime > 60) {
        minuteTime = parseInt(secondTime / 60);
        secondTime = parseInt(secondTime % 60);
        if (minuteTime > 60) {
            hourTime = parseInt(minuteTime / 60);
            minuteTime = parseInt(minuteTime % 60)
        }
    }
    var result = "" + parseInt(secondTime) + "s";

    if (minuteTime > 0) {
        result = "" + parseInt(minuteTime) + "m" + result;
    }
    if (hourTime > 0) {
        result = "" + parseInt(hourTime) + "h" + result;
    }
    return result;
}