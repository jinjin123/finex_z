/**
 * --------- 全局变量 ---------
 * zg_dl.汇率 大陆  zg_hk.汇率 香港  zg_tw.汇率台湾  zg_om.当前对应汇率区号  zg_id.当前地区id countLast.倒计时 result.倒计时补0 
 * @author 2018-2-27T10:19:53+0800
 * @param
 * @return
 */
var CtwoC_QBL = {
    zg_dl: null,
    zg_hk: null,
    zg_tw: null,
    zg_om: null,
    zg_id: null,
    countLast: null,
    result: null
};
/**
 * --------- 页面加载时加载数据列表 ---------
 * 我的订单和订单管理的数据加载和定时刷新
 * @author 2018-2-27T10:19:53+0800
 * @param  
 * @return 
 */
$(document).ready(function() {
    //交易模式换c2c名字
    $('.changeModel .navbar-nav li>a.dropdown-toggle').html($('.changeModel .dropdown-menu li:nth-of-type(3)>a').text() + ' <i class="fa fa-angle-down"></i>');
    // 我的订单
    Fillmytable();

    // 我的订单管理数据初始化填充，默认选中第几条数据
    order_management(0);

    //页面加载开关
    check_user('getOrderDisplay', {}, 'post').then(function(res) {
        var _bstype;
        if (res.status == '1') {
            _bstype = true;
        } else {
            _bstype = false;
        }
        $('.bs-switch').bootstrapSwitch('state', _bstype);
    });

});
// 我的订单定时刷新
var Maintain_Fillmy = setInterval(function() {
    Fillmytable();
}, 5000);
// 订单管理定时刷新
var Maintain_order = setInterval(function() {
    order_management(1);
    //刷新子订单
    var $orderCollect_tr_activebg = $(".orderCollect tbody tr.activebag");
    if ($orderCollect_tr_activebg.length != 0) {
        var target_collect = $orderCollect_tr_activebg.attr('order-id'),
            target_pay = $orderCollect_tr_activebg.attr('information');
        refresh_Suborders(target_collect, target_pay, 1);
    }
}, 5000);


$(function() {

    /**
     * --------- 币种切换 ---------
     * 1.我的订单重新请求数据
     * 2.我的订单旁边的币种色块要更改
     * 3.发布订单的币种要更改
     * @author 2018-2-27T10:19:53+0800
     * @param  获取并发送currency_id币种id,area_id地区id
     * @return 后台返回对应币种的列表数据，调用组装表格的公共函数插入html
     */
    $('#coinMoneyList .coinName').on('click', function() {
        //点击币种切换订单发布币种信息
        var current_coin = $(this).attr('currency-name');
        $('.change_ctoc_coin').text(current_coin);
        // 切换币种填充我的订单数据
        setTimeout(function() {
            Fillmytable();
        }, 300);
    });

    /**
     * --------- 我的订单 ---------
     * select选择地区后获取相应地区的数据填入表格
     * @author 2018-2-27T10:19:53+0800
     * @param  {Number} p [description]
     * @return {[type]}   [description]
     */
    // 小屏
    $('#china-area').on("change", function(e) {
        Fillmytable();
    });

    //大屏
    $('.area_choice li').click(function() {
        var _this = $(this);
        var area = _this.attr('id');
        _this.addClass('active').siblings().removeClass('active');
        if (area == 'area_cn') {
            $('#china-area').val(86);
        } else if (area == 'area_hk') {
            $('#china-area').val(852);
        }
        Fillmytable();
    });
    /**
     * --------- 我的订单 ---------
     * 数据块订单  点击买入弹窗 数据ajax
     * @author 2018-2-27T10:19:53+0800
     * @param  
     * @return 
     */

    $('body').on('click', '.china-buy', function() {
        //c2c下单模式维护
        if (Maintain(forbidOrder, C2C_Maintain_Place, C2C_Maintain_Pattern)) {
            return;
        }
        //获取当前id订单
        var _this = $(this);
        var Serial_numbe = _this.attr('order-id');
        var Singular = _this.parents('tr').find('td:nth-child(4)').text();
        var Completion_rate = _this.parents('tr').find('td:nth-child(5)').text();
        var target_area = $('#china-area').val();
        //验证密码 银行卡 实名认证 ajax
        check_user("/CtoCTransaction/checkIsLimitOrder", {
            'type': 2
        }, 'post').then(function(res) {
            if (res.code == 200) {
                // 成功
                //清除数据
                Eliminate($('.china-buynumber'), $('.china-buyamount'), $('.exchange-buyrate-price'), $('.china-buypassword'));

                //发送ajax 获取数据填弹窗
                check_user("/CtoCTransaction/getOrderInfoByIdApi", {
                    'id': Serial_numbe, //当前订单号
                    'type': 1, //卖出type
                    'om': target_area //当前交易区号
                }, 'post').then(function(response) {
                    var table = '';
                    //循环银行卡号填入
                    if (response.code == 200) {
                        $('#buy-issue').modal('show');
                        for (i = 0; i < response.data.bankInfo.length; i++) {
                            table += '<option value="' + response.data.bankInfo[i].user_bank_id + '">' + response.data.bankInfo[i].bank_address + '(' + response.data.bankInfo[i].bank_num + ')</option>';
                        }
                        //填入数据 函数
                        Entry(response.data,$('.buyorder-name'),$('.totaol_order'), Singular, $('.complete_rate'), Completion_rate, $('.china-buyregion'), $('.china-buyprice'),  $('#china-buyBank'), table, $('.exchange-buyrate'), $('.Reminder'));
                        //设置type值
                        $('.data-block-buy').attr('currency_type', response.data.orederInfo.currency_type).attr('order-id', Serial_numbe);
                    } else {
                        BottomalertBox('bottom', response.msg, "fail", "center");
                    }

                });
                //	审核中
            } else if (res.code == 602) {
                alertBox(userreal);
            } else if (res.code == 672) {
                BottomalertBox('bottom', res.msg, "fail", "center");
            } else {
                alertBox(res.msg);
            }
        });
    });


    /**
     * --------- 我的订单 ---------
     * 数据块订单  点击卖出弹窗 数据ajax
     * @author 2018-2-27T10:19:53+0800
     * @param  
     * @return 
     */
    $('body').on('click', '.china-sell', function() {
        //c2c下单模式维护
        if (Maintain(forbidOrder, C2C_Maintain_Place, C2C_Maintain_Pattern)) {
            return;
        }
        //获取当前订单id
        var _this = $(this);
        var Serial_numbe = _this.attr('order-id');
        var Singular = _this.parents('tr').find('td:nth-child(4)').text();
        var Completion_rate = _this.parents('tr').find('td:nth-child(5)').text();
        var target_area = $('#china-area').val();
        //验证密码 银行卡 实名认证 ajax
        check_user("/CtoCTransaction/checkIsLimitOrder", {
            'type': 3,
            'om': target_area
        }, 'post').then(function(res) {
            if (res.code == 200) {
                //清空数据
                Eliminate($('.china-sellnumber'), $('.china-sellamount'), $('.exchange-sellrate-price'), $('.china-sellpassword'));

                //发送ajax 获取当前订单信息填入
                check_user("/CtoCTransaction/getOrderInfoByIdApi", {
                    'id': Serial_numbe, //当前订单号
                    'type': 2, //卖出type
                    'om': target_area //当前交易区号
                }, 'post').then(function(response) {
                    if (response.code == 200) {
                        $('#sell-issue').modal('show');
                        var table = '';
                        //循环银行卡号填入
                        for (i = 0; i < response.data.bankInfo.length; i++) {
                            table += '<option value="' + response.data.bankInfo[i].user_bank_id + '">' + response.data.bankInfo[i].bank_address + '(' + response.data.bankInfo[i].bank_num + ')</option>';
                        }
                        //填入数据 函数
                        Entry(response.data,$('.sellorder-name'), $('.totaol_order'), Singular, $('.complete_rate'), Completion_rate, $('.china-sellregion'), $('.china-sellprice'), $('#china-sellBank'), table, $('.exchange-sellrate'), $('.Reminder'));
                        //设置type值
                        $('.data-block-sell').attr('currency_type', response.data.orederInfo.currency_type).attr('order-id', Serial_numbe);
                    } else {
                        BottomalertBox('bottom', response.msg, "fail", "center");
                    }
                });
                //	审核中
            } else if (res.code == 602) {
                alertBox(userreal);
            } else if (res.code == 672) {
                BottomalertBox('bottom', res.msg, "fail", "center");
            } else {
                alertBox(res.msg);
            }
        });
    });

    /**
     * --------- 我的订单 ---------
     * 数据块订单  点击确认买入 数据ajax
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $('.data-block-buy').on('click', function() {
        var order_id = CtwoC_QBL.zg_id,
            amount = $('.china-buyamount').val(),
            Num = $('.china-buynumber').val(),
            pass = $('.china-buypassword').val(),
            name = $('#coinMoneyList').find('.active').text(),
            price = $('.china-buyprice').val(),
            bank = $('#china-buyBank').val(),
            currency_type = $(this).attr('currency_type');
        switch (Num) {
            case '':
                BottomalertBox('bottom', C2C_SLBNWK_, "fail", "center");
                return;
            case '0':
                BottomalertBox('bottom', C2C_SLBNWFS_, "fail", "center");
                return;
        }
        switch (amount) {
            case '':
                BottomalertBox('bottom', C2C_JEBNWK_, "fail", "center");
                return;
            case '0':
                BottomalertBox('bottom', C2C_JEBNWFS_, "fail", "center");
                return;

        }
        if (pass == '') {
            BottomalertBox('bottom', C2C_JYMMBNWK_, "fail", "center");
            return;
        }
        check_user("/OrderBusiness/BuyingOrderApi", {
            'money': amount, //金额
            'num': Num, //数量
            'trade_pass': pass, //密码
            'user_bank_id': bank, //银行
            'id': order_id, //名字
            'type': 1, //1是买 2是卖
            'currency_type': currency_type
        }, 'post').then(function(response) {
            if (response.code == 200) {
                // 成功隐藏弹窗
                $('#buy-issue').modal('hide');

                //公共函数 填写表格
                order_management(1);

                //刷新数据块
                Fillmytable();

                //成功右下角显示成功信息
                BottomalertBox('bottom', response.msg, "success", "center");
                return;
            } else if (response.code == 614) {
                $('#buy-issue').modal('hide');
            }
            //失败右下角显示失败内容
            BottomalertBox('bottom', response.msg, "fail", "center");

        });
    });

    /**
     * --------- 我的订单 ---------
     * 数据块订单  点击确认卖出 数据ajax
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $('.data-block-sell').on('click', function() {
        var order_id = CtwoC_QBL.zg_id,
            amount = $('.china-sellamount').val(),
            Num = $('.china-sellnumber').val(),
            pass = $('.china-sellpassword').val(),
            name = $('#coinMoneyList').find('.active').text(),
            price = $('.china-sellprice').val(),
            bank = $('#china-sellBank').val(),
            currency_type = $(this).attr('currency_type');

        switch (Num) {
            case '':
                BottomalertBox('bottom', C2C_SLBNWK_, "fail", "center");
                return;
            case '0':
                BottomalertBox('bottom', C2C_SLBNWFS_, "fail", "center");
                return;
        }
        switch (amount) {
            case '':
                BottomalertBox('bottom', C2C_JEBNWK_, "fail", "center");
                return;
            case '0':
                BottomalertBox('bottom', C2C_JEBNWFS_, "fail", "center");
                return;

        }
        if (pass == '') {
            BottomalertBox('bottom', C2C_JYMMBNWK_, "fail", "center");
            return;
        }

        check_user("/OrderBusiness/BuyingOrderApi", {
            'money': amount, //金额
            'num': Num, //数量
            'trade_pass': pass, //密码
            'user_bank_id': bank, //银行
            'id': order_id, //名字
            'type': 2, //1是买 2是卖
            'currency_type': currency_type
        }, 'post').then(function(response) {
            if (response.code == 200) {
                // 成功隐藏弹窗
                $('#sell-issue').modal('hide');

                //公共函数 填写表格
                order_management(1);

                //刷新数据块
                Fillmytable();

                //成功后右下角显示成功信息
                BottomalertBox('bottom', response.msg, "success", "center");
                return;
            } else if (response.code == 614) {
                $('#sell-issue').modal('hide');
            }
            //失败右下角显示失败信息
            BottomalertBox('bottom', response.msg, "fail", "center");
        });

    });

    /**
     * --------- 我的订单 ---------
     * 发布弹窗 改变地区 汇率变化
     * @author 2018-2-27T10:19:53+0800
     * @param  当前地区id
     * @return
     */
    $('#order-buyrelease').on('change', function() {
        var _this = $(this);
        release_hl(_this); //切换区改变当前汇率
        $('#order-sellrelease').val(_this.val()).selectpicker('refresh'); //当select改变 买卖改变 //插件更新化
    });
    $('#order-sellrelease').on('change', function() {
        var _this = $(this);
        release_hl(_this); //切换区改变当前汇率
        $('#order-buyrelease').val(_this.val()).selectpicker('refresh'); //当select改变 买卖改变//插件更新化
    });

    /**
     * --------- 我的订单 ---------
     * 发布订单 点击买入 当前订单数据发送
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $('.chinaorder-buy').on('click', function() {
        var release = $('#order-buyrelease').val(),
            price = $('.release-buyprice').val(),
            Num = $('.release-buynumber').val(),
            currency_id = $('#coinMoneyList li.active').attr('currency-id'),
            name = $('#coinMoneyList li.active').attr('currency-name'),
            amount = $('.release-buyamount').val(),
            pass = $('.release-buypass').val();

        check_user("/CtoCTransaction/subTrade", {
            'currency_type': currency_id, //币种id
            'price': price, //单价
            'num': Num, //数量
            'tradepwd': pass, //密码
            'om': release, //地区
            'type': 1 //买入
        }, 'post').then(function(response) {
            if (response.code == 200) {

                //刷新数据块
                Fillmytable();
                order_management(1);

                //隐藏弹窗
                $('#buy-issue').modal('hide');
                $('#order-issue').modal('hide');

                // 成功右下角显示成功信息
                BottomalertBox('bottom', response.msg, "success", "center");
                return;
            }
            // 失败右下角显示失败信息
            BottomalertBox('bottom', response.msg, "fail", "center");

        });

    });

    /**
     * --------- 我的订单 ---------
     * 发布订单 点击卖出 当前订单数据发送
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $('.chinaorder-sell').on('click', function() {
        var release = $('#order-sellrelease').val(),
            currency_id = $('#coinMoneyList li.active').attr('currency-id'),
            price = $('.release-sellprice').val(),
            Num = $('.release-sellnumber').val(),
            amount = $('.release-sellamount').val(),
            name = $('#coinMoneyList li.active').attr('currency-name'),
            pass = $('.release-sellpass').val();

        check_user("/CtoCTransaction/subTrade", {
            'currency_type': currency_id, //币种id
            'price': price, //单价
            'num': Num, //数量
            'tradepwd': pass, //密码
            'om': release, //地区
            'type': 2 //卖出
        }, 'post').then(function(response) {
            if (response.code == 200) {

                //刷新数据块
                Fillmytable();
                order_management(1);

                //隐藏弹窗
                $('#buy-issue').modal('hide');
                $('#order-issue').modal('hide');
                //成功右下角显示成功信息
                BottomalertBox('bottom', response.msg, "success", "center");
                return;
            }
            BottomalertBox('bottom', response.msg, "fail", "center");

        });
    });

    /**
     * --------- 我的订单 ---------
     * 订单发布买卖    获取实时价格填取
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $('.release-order').on('click', function() {
        //c2c市场订单 维护  
        if (Maintain(listOrder, C2C_Maintain_Marketorder, C2C_Maintain_Pattern)) {
            return;
        }
        //c2c下单模式维护
        if (Maintain(forbidOrder, C2C_Maintain_Place, C2C_Maintain_Pattern)) {
            return;
        }
        //验证密码 银行卡 实名认证 ajax
        check_user("/CtoCTransaction/checkIsLimitOrder", {
            'type': 1
        }, 'post').then(function(res) {
            if (res.code == 200) {
                $('#order-issue').modal('show');
                //获取实时价格
                var rtq = $('#coinRtqMoney').text();

                //获取当前币种id
                var currency_id = $('#coinMoneyList li.active').attr('currency-id');
                //获取汇率运算
                release_hl($('#order-buyrelease'));

                check_user("/CtoCTransaction/getCurrencyFee", {
                    'currency_id': currency_id //币种序号
                }, 'post').then(function(response) {
                    $('.promiseCash').text(response.data.bond_num);
                    $('.serviceFee-buy').text(response.data.buy_fee + '%'); //发布订单买入手续费
                    $('.serviceFee-sell').text(response.data.sell_fee + '%'); //发布订单卖出手续费
                });

                //清空数据
                Eliminate($('.release-buynumber'), $('.release-buyamount'), $('.exchange-buyrate-price'), $('.release-buypass'));
                Eliminate($('.release-sellnumber'), $('.release-sellamount'), $('.exchange-sellrate-price'), $('.release-sellpass'));

                //实时价格
                $('.release-rtq').text('$' + ChangeFixed(rtq, 3));


                //买入卖出 价格
                $('.release-buyprice').val(ChangeFixed(rtq, 3));
                $('.release-sellprice').val(ChangeFixed(rtq, 3));
                //	审核中
            } else if (res.code == 602) {
                alertBox(userreal);
            } else if (res.code == 672) {
                BottomalertBox('bottom', res.msg, "fail", "center");
            } else {
                alertBox(res.msg);
            }
        });
    });

    /**
     * --------- 订单管理 ---------
     * 左边选择相应订单请求右边显示的数据并填入
     * @author 2018-2-27T10:19:53+0800
     * @param  获取对应订单的order_id请求数据
     * @return
     */
    $("body").on("click", ".orderCollect tbody tr", function() {
        var _this = $(this),
            order_id = _this.attr('order-id'),
            information = _this.attr('information');

        // 加背景色
        $('#tab-trading-now .orderCollect tbody tr.activebag').removeClass('activebag');
        _this.addClass('activebag');

        //如已存在倒计时循环,先清除
        if (CtwoC_QBL.countLast != "undefined") {
            clearInterval(CtwoC_QBL.countLast);
        }

        //订单列表生成后开始倒计时循环
        refresh_Suborders(order_id, information, 0);
        timeLast();
    });

    /**
     * --------- 订单管理 ---------
     * 点击撤销按钮后将order_id传给弹窗为之后的传参做铺垫
     * @author 2018-2-27T10:19:53+0800
     * @param  获取对应订单的order_id
     * @return
     */
    $("body").on("click", ".revokeNowOrder", function() {
        var _this = $(this);
        var order_id = _this.parents('tr').attr('order-id');
        var gement = _this.parents('tr').attr('gement');
        var $cancel_revokeOrderById = $('#cancel #revokeOrderById');

        // 显示对应的文本信息
        var type = _this.attr('order-type');
        $('#cancel .cancel-text p').eq(type - 1).show().siblings().hide();

        $cancel_revokeOrderById.attr('order_id', order_id).attr('gement', gement);
    });

    /**
     * --------- 订单管理 ---------
     * 点击弹窗的确定按钮撤销订单
     * @author 2018-2-27T10:19:53+0800
     * @param  获取对应订单的order_id传给后台
     * @return 撤销成功，右下角弹窗，关闭撤销窗口，并remmove该tr
     */
    $("body").on("click", '#revokeOrderById', function() {
        var _this = $(this);
        var order_id = _this.attr('order_id');
        var gement = _this.attr('gement');
        check_user("/CtoCTransaction/revokeBigOrder", {
            'orderNum': order_id //订单id
        }, 'post').then(function(response) {
            // 成功
            if (response.code == 200) {
                // 关闭当前弹窗
                $('#cancel').modal('hide');
                // 右下角弹窗
                BottomalertBox("bottom", response.msg, "success", "center");

                // 选取order_id为该id的操作按钮和状态改变
                $('.gement' + gement + ' td:nth-child(8)').html('/');
                $('.gement' + gement + ' td:nth-child(7)').text(daichex);
                return;
            } else {
                // 失败
                BottomalertBox('bottom', response.msg, "fail", "center");
            }
        });
    });

    /**
     * --------- 订单管理 ---------
     * 确定付款 收款
     * @author 2018-2-27T10:19:53+0800
     * @param  获取对应订单的order_id传给后台
     * @return 操作成功，右下角弹窗并remmove该div
     */
    $("body").on("click", ".opt-BS", function(e) {
        var _this = $(this);
        var opt_type = _this.attr('target_BS');
        var order_id = _this.attr('order_id');
        var $orderCollect_tr = $(".orderCollect tbody");
        var num_index = $orderCollect_tr.find('tr.activebag').attr('gement');

        //点击 disable 3秒
        setTimeSecd(_this);
        check_user("/CtoCTransaction/confirmOrderAcceptOrPaid", {
            'type': opt_type, //订单类型 1:打款 2:收款
            'orderId': order_id, //订单id
        }, 'post').then(function(response) {
            // 成功
            if (response.code == 200) {
                // 右下角弹窗
                BottomalertBox("bottom", response.msg, "success", "center");
                // 成功即移除按钮
                _this.remove();
                // 刷新该订单块信息
                order_management(1);
                //获取id  刷新子订单
                var $orderCollect_tr_activebg = $(".orderCollect tbody tr.activebag"),
                    target_collect = $orderCollect_tr_activebg.attr('order-id'),
                    target_pay = $orderCollect_tr_activebg.attr('information');
                $orderCollect_tr.find('tr').removeClass('activebag');


                refresh_Suborders(target_collect, target_pay, 0);


                setTimeout(function() {
                    //判断上一条数据存在
                    if ($('.order-management tbody .gement' + num_index + '').length != 0) {
                        //存在加效果 active
                        $(".orderCollect tbody .gement" + num_index + "").addClass('activebag');
                    } else {
                        // 判断是否存在操作记录，没有默认第一条click
                        $orderCollect_tr.find('tr:nth-child(1)').trigger('click');
                    }
                }, 500);
            } else {
                // 失败弹窗并灰掉按钮
                BottomalertBox('bottom', response.msg, "fail", "center");
                // 设置3秒倒计时
            }

        });
    });

    /**
     * --------- 订单管理 ---------
     * 未收到款项
     * @author 2018-2-27T10:19:53+0800
     * @param  获取对应订单的order_id传给后台，由客服人员查看状态联系客户
     * @return 操作成功，右下角弹窗
     */
    $('body').on('click', '.unreceivable', function() {
        var _this = $(this);
        var order_id = _this.attr('order_id'),
            $orderCollect_tr_activebg = $(".orderCollect tbody tr.activebag"),
            target_collect = $orderCollect_tr_activebg.attr('order-id'),
            target_pay = $orderCollect_tr_activebg.attr('information');
        //点击 disable 3秒
        setTimeSecd(_this);
        //ajax
        check_user("/CtoCTransaction/unReceiptTradeOrderPaid", {
            'orderId': order_id //订单id
        }, 'post').then(function(response) {
            // 成功
            if (response.code == 200) {
                // 成功即移除按钮
                // 刷新表格
                _this.remove();
                refresh_Suborders(target_collect, target_pay, 0);
                // 右下角弹窗
                BottomalertBox("bottom", response.msg, "success", "center");
            } else {
                // 失败
                BottomalertBox('bottom', response.msg, "fail", "center");
            }

        });
    });

    /**
     * --------- 订单管理 ---------
     * 历史 已完成/已撤销
     * @author 2018-2-27T10:19:53+0800
     * @param  调用TransitPage函数发送ajax
     * @return
     */
    $('body').on('click', '.small-Trading', function() {
        //c2c订单管理维护 历史信息按钮
        if (Maintain(dealOrder, C2C_Maintain_order, C2C_Maintain_Pattern)) {
            return;
        }
    });
    $('body').on("click", '.small-Trading .dropdown-menu li', function() {
        var _this = $(this);
        var current = _this.text();
        $('.small-Trading > a').html(current + "<i class='fa fa-angle-down'></i>");
        TransitPage(undefined, _this.index() + 1);
    });
    var firstone = $('.small-Trading > a').text();
    $('.small-Trading').prev().click(function() {
        //c2c 订单管理维护  
        if (Maintain(dealOrder, C2C_Maintain_order, C2C_Maintain_Pattern)) {
            $(this).find('a').attr('href', '#');
            return;
        }
        $('.small-Trading > a').html(firstone + "<i class='fa fa-angle-down'></i>");
    });


    /**
     * --------- 订单管理 ---------
     * 点击详情弹窗
     * @author 2018-2-27T10:19:53+0800
     * @param  获取属性填入弹窗
     * @return
     */
    $('body').on('click', '.getDetails', function() {
        var _this = $(this),
            order_num = _this.attr('order-num'),
            trade_time = UnixToDate(_this.attr('trade-time'), 2),
            dakuang_time = UnixToDate(_this.attr('buy-moneytime'), 2),
            type = _this.attr('data-type');
        $('#detail_order_num').html('<span id="d_order_num">' + order_num + '</span><button type="button" id="copy" style="position:relative;background:inherit"  class="btn btn-green copyBtn" data-clipboard-action="copy" data-clipboard-target="#d_order_num"><svg class="icon input-icon icon-bz" style="left:5px;top:0px;" aria-hidden="true"><use xlink:href="#icon-ic_wallet_copy"></use></svg></button>');
        $('#detail_trade_time').html(trade_time);
        $('#detail_dakuang_time').html(dakuang_time);
        // 判断是已完成显示收款时间，撤销显示撤销时间
        if (type == 1) {
            var end_time = UnixToDate(_this.attr('end-time'), 2);
            $('#detail_end_time').html(end_time);
            $('.end_time').show();
            $('.cancel_time').hide();
        } else if (type == 2) {
            var update_time = UnixToDate(_this.attr('update-time'), 2);
            $('#detail_cancle_time').html(update_time);
            $('.cancel_time').show();
            $('.end_time').hide();
        }
    });

    /**
     * --------- 订单管理 ---------
     * switch初始化，正在接单
     * @author 2018-2-27T10:19:53+0800
     * @param  获取属性填入弹窗
     * @return
     */
    $(".bs-switch").bootstrapSwitch({
        onSwitchChange: function(event, state) {
            var status;
            if (state == true) {
                status = 1;
            } else {
                status = 0;
            }
            check_user('setOrderDisplay', {
                'status': status
            }, 'post').then(function(res) {
                // 我的订单管理数据初始化填充，默认选中第几条数据
                order_management(1);
            });
        },
        onText: c2c_openJD,
        offText: c2c_closeJD
    });


    /**
     * --------- 我的订单 ---------
     * 实时改变价格运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    var $release_buynumber = $('.release-buynumber'),
        $release_buyamount = $('.release-buyamount'),
        $exchange_buyrate_price = $('.exchange-buyrate-price'),
        $china_buyamount = $('.china-buyamount'),
        $release_buyprice = $('.release-buyprice'),
        $release_sellnumber = $('.release-sellnumber'),
        $release_sellprice = $('.release-sellprice'),
        $release_sellamount = $('.release-sellamount'),
        $exchange_sellrate_price = $('.exchange-sellrate-price'),
        $china_sellamount = $('.china-sellamount'),
        $china_buyprice = $('.china-buyprice'),
        $china_buynumber = $('.china-buynumber'),
        $china_sellprice = $('.china-sellprice'),
        $china_sellnumber = $('.china-sellnumber');

    /**
     * --------- 我的订单 ---------
     * 订单发布买入    实时改变价格运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $release_buyprice.bind('input propertychange', function() {
        data_Ablock($(this), 1, $release_buynumber, 1, $release_buyamount, $exchange_buyrate_price, 1, $china_buyamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单发布买入    实时改变价格运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $release_buynumber.bind('input propertychange', function() {
        data_Ablock($(this), 2, $release_buyprice, 1, $release_buyamount, $exchange_buyrate_price, 1, $release_buyamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单发布卖出   实时改变价格运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $release_sellprice.bind('input propertychange', function() {
        data_Ablock($(this), 1, $release_sellnumber, 1, $release_sellamount, $exchange_sellrate_price, 1, $china_sellamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单发布卖出    实时改变价格运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $release_sellnumber.bind('input propertychange', function() {
        data_Ablock($(this), 2, $release_sellprice, 1, $release_sellamount, $exchange_sellrate_price, 1, $china_sellamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单买入    实时改变数量运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $china_buynumber.bind('input propertychange', function() {
        data_Ablock($(this), 2, $china_buyprice, 1, $china_buyamount, $exchange_buyrate_price, 1, $china_buyamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单买入    实时改变价格运算   数量/价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $china_buyamount.bind('input propertychange', function() {
        data_Ablock($(this), 1, $china_buyprice, 2, $china_buynumber, $exchange_buyrate_price, 2, $(this));
    });

    /**
     * --------- 我的订单 ---------
     * 订单卖出    实时改变数量运算   数量*价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $china_sellnumber.bind('input propertychange', function() {
        data_Ablock($(this), 2, $china_sellprice, 1, $china_sellamount, $exchange_sellrate_price, 1, $china_sellamount);
    });

    /**
     * --------- 我的订单 ---------
     * 订单卖出    实时改变价格运算   数量/价格
     * @author 2018-2-27T10:19:53+0800
     * @param
     * @return
     */
    $china_sellamount.bind('input propertychange', function() {
        data_Ablock($(this), 1, $china_sellprice, 2, $china_sellnumber, $exchange_sellrate_price, 2, $(this));
    });

});
/**
 * --------- 我的订单 ---------
 * 获取币种和交易区请求数据并填充表格
 * @author 2018-2-27T10:19:53+0800
 * @param  
 * @return 
 */
function Fillmytable() {
    //市场订单 维护  
    if (Maintain(listOrder, C2C_Maintain_Marketorder, C2C_Maintain_Pattern)) {
        clearInterval(Maintain_Fillmy);
        $('.sell-table tbody').html('');
        $('.buy-table tbody').html('');
        return;
    }
    var refresh = JSON.parse(localStorage.getItem("RefreshCurrency"));
    var currency_id = refresh && refresh[1] ? refresh[1].currencyId : $('#coinMoneyList li:nth-child(1)').attr('currency-id');
    var area_id = $('#china-area').val();

    // 判断当前大屏默认是什么地区添加选中样式
    if (area_id == 852) {
        $('#area_hk').addClass('active').siblings().removeClass('active');
    } else if (area_id == 86) {
        $('#area_cn').addClass('active').siblings().removeClass('active');
    }

    check_user("/CtoCTransaction/getTrade", {
        'currency_type': currency_id, //币种序号
        'om': area_id //交易区
    }, 'post').then(function(response) {
        // 填充左边买入的表格
        var html1 = RestructTable(response.data.sell, 1);
        $('.sell-table tbody').html(html1);
        // 填充右边卖出的表格
        var html2 = RestructTable(response.data.buy, 2);
        $('.buy-table tbody').html(html2);
        // .bar最大长度范围
        var $china_buy = $(".china-buy");
        var $china_sell = $(".china-sell");
        var buyFullLen = $china_buy.parent().width() - ($china_buy.width() / 2);
        var sellFullLen = $china_sell.parent().width() - ($china_sell.width() / 2);
        //宽度小于3的时候 等于3 显示微小条
        buyFullLen < 3 ? buyFullLen = 3 : false;
        sellFullLen < 3 ? sellFullLen = 3 : false;
        //买入区
        $(".buybar").each(function(i, ele) {
            $(ele).width($(ele).attr("data-width") * buyFullLen);
        });
        //卖出区
        $(".sellbar").each(function(i, ele) {
            $(ele).width($(ele).attr("data-width") * sellFullLen);
        });
    });
}

/**
 * --------- 我的订单 ---------
 * 公用填写我的订单table数据的函数
 * @author 2018-2-27T10:19:53+0800
 * @param  data为后台返回的数据
 * @return 返回组装好的字符串
 */
function RestructTable(data, tpye) {
    var len = data.length;
    var table = "";
    var table_data = []
    for (var i = 0; i < len; i++) {
        table += '<tr>';
        table += '<td>' + data[i].price + '</td>';
        table += '<td>' + data[i].leave_num + '</td>';
        table += '<td>' + data[i].money + '</td>';
        table += '<td>' + data[i].total_order + '</td>';
        table += '<td>' + data[i].complete_rate + '</td>';

        if (tpye === 1) {
            table_data = ['china-buy','china-Bbutton',mairu,'buybar'];
        } else if (tpye === 2) {
            table_data = ['china-sell','china-Sbutton',maichu,'sellbar'];
        }
        table += '<td order-id="' + data[i].id + '" class="' + table_data[0] + '"><button class="' + table_data[1] + '">' + table_data[2] + '</button></td>';
        table += '<td class="' + table_data[3] + '" style="width:' + data[i].completion * 100 + '%" data-width="' + data[i].completion + '"></td>';
        table += '</tr>';
    }
    return table;
}


/**
 * --------- 我的订单 ---------
 * 数据块订单  点击买卖清除数据
 * @author 2018-2-27T10:19:53+0800
 * @param  
 * @return 
 */
function Eliminate(Eliminate_num, Eliminate_amount, Eliminate_price, Eliminate_pass) {
    Eliminate_num.val('0');
    Eliminate_amount.val('0');
    Eliminate_price.text('0.0000');
    Eliminate_pass.val('');
}

/**
 * --------- 我的订单 ---------
 * 数据块订单  买卖点击弹窗   公共回调函数  参数1.汇率86  2.汇率852  3.汇率886 4.当前订单id  5.当前地区汇率id 6.用户名字标签  7.当前订单名字  8.当前订单号等级  9.用户单数 10 填充单数 11.用户完成率  12.填充完成率  13.当前地区标签  14.当前地区名字  15.弹窗订单价格标签  16.弹窗价格填写  17.用户绑定银行标签 18.循环银行数组填数据 19.汇率运算填写
 * @author 2018-2-27T10:19:53+0800
 * @param
 * @return
 */
function Entry(res,business_name, business_Singular, Singular_content, Completion_rate, Singular_rate, business_region, business_price,  business_bank, business_table, business_rate, business_Reminder) {
    CtwoC_QBL.zg_dl = res.huilv[86];
    CtwoC_QBL.zg_hk = res.huilv[852];
    CtwoC_QBL.zg_tw = res.huilv[886];
    CtwoC_QBL.zg_id = res.orederInfo.id;
    CtwoC_QBL.zg_om = res.orederInfo.om;
    business_name.html(res.orederInfo.username);
    business_Singular.text(Singular_content);
    Completion_rate.text(Singular_rate);
    business_region.text(res.orederInfo.areaName);
    business_price.val(res.orederInfo.price);
    business_bank.html(business_table);
    business_bank.selectpicker('refresh');
    switch (res.orederInfo.om) {
        case '86':
            business_rate.text('CNY');
            break;
        case '852':
            business_rate.text('HKD');
            break;
        case '886':
            business_rate.text('TWD');
            break;
    }
    switch (res.orederInfo.tips) {
        case 0:
            business_Reminder.hide();
            break;
        case 1:
            business_Reminder.show();
            business_Reminder.parents(".modal-content").addClass("less-t-h");
            break;
    }
}

/**
 * --------- 我的订单 ---------
 * 发布弹窗公共函数
 * @author 2018-2-27T10:19:53+0800
 * @param  当前地区id
 * @return
 */
function release_hl(obj) {
    var om = obj.val(),
        rtq = $('#coinRtqMoney').text(), //获取实时价格
        cny = $('#rate1').val(), //获取 RMB汇率
        hkd = $('#rate3').val(), //获取hk汇率
        twd = $('#rate2').val(), //获取tw汇率
        $reference_left = $('.reference-left'),
        $reference_price = $('.reference-price'),
        Nu_pri;

    //判断全局om 区号 进行运算
    switch (om) {
        //86 大陆
        case '86':
            Nu_pri = rtq * cny;
            $reference_left.text('CNY');
            break;
            //852香港
        case '852':
            Nu_pri = rtq * hkd;
            $reference_left.text('HKD');
            break;
            //886台湾
        case '886':
            Nu_pri = rtq * twd;
            $reference_left.text('TWD');
            break;
    }
    $reference_price.text(ChangeFixed(Nu_pri, 3));

}
/**
 * --------- 我的订单 ---------
 * @param {自身this} data_Athis 
 * @param {*} data_ASHU 
 * @param {要乘的价格} data_Aprice 
 * @param {显示的金额小数保留type} decimal 
 * @param {显示金额的元素} data_Aamount 
 * @param {兑换人民币后显示的元素} data_A1price 
 * @param {乘除type值} division 
 * @param {汇率type} exchange_ation 
 */
function data_Ablock(data_Athis, data_ASHU, data_Aprice, decimal, data_Aamount, data_A1price, division, exchange_ation) {
    validationNumber(data_Athis, data_ASHU);
    var Num = data_Aprice.val();
    var Pri = data_Athis.val();
    var Nu_pri;
    if (division === 1) {
        Nu_pri = Pri * Num;
    } else if (division === 2) {
        Nu_pri = Pri / Num;
    }
    if (decimal == 1) {
        data_Aamount.val(ChangeFixed(Nu_pri, 3));
    } else if (decimal == 2) {
        data_Aamount.val(ChangeFixed(Nu_pri, 5));
    }

    var Total_om1;
    switch (CtwoC_QBL.zg_om) {
        case '86':
            Total_om1 = exchange_ation.val() * CtwoC_QBL.zg_dl;
            break;
        case '852':
            Total_om1 = exchange_ation.val() * CtwoC_QBL.zg_hk;
            break;
        case '886':
            Total_om1 = exchange_ation.val() * CtwoC_QBL.zg_tw;
            break;
    }
    data_A1price.text(ChangeFixed(Total_om1, 3));

}

/**
 * --------- 订单管理 ---------
 * 获取子订单信息 刷新按钮
 * @author 2018-2-27T10:19:53+0800
 * @param  获取对应订单的order_id
 * @return
 */
function refresh_Suborders(order_id, now_id, type) {
    check_user("/CtoCTransaction/getUserTradeOrderList", {
        'pid': order_id, //订单id
        'orderId': now_id
    }, 'post').then(function(response) {
        // 获取当前卡片的长度
        var target_remittanceInfo = $('.remittanceInfo').length;

        //获取后台返回数据的长度
        var target_data_leng = response.data.length;

        //A：确认付/收款按钮
        var response_commonA;
        //type 1 刷新局部
        if (type == 1) {
            //判断 卡片的长度 == 后台数据返回的长度
            if (target_remittanceInfo == target_data_leng) {

                //循环后台数据
                $.each(response.data, function(i, val) {
                    //获取后台时间填入
                    $('.remittanceInfo[order_id=' + val.order_id + '] .remainTime').attr('time', val.time_limit);

                    //获取后台 订单信息填入
                    $('.remittanceInfo[order_id=' + val.order_id + '] .remit_tips').html("<svg class='icon icon_norepeat vertical_middle' aria-hidden='true'><use xlink:href='#icon-ic_warn_white'></use></svg>   " + val.remark);

                    //获取此订单状态填入
                    if (val.status_logo == 1 || val.status_logo == 2) {
                        val.status_logo = 1;
                    } else if (val.status_logo == 3 || val.status_logo == 4) {
                        val.status_logo = 3;
                    }
                    $('.remittanceInfo[order_id=' + val.order_id + '] .waitPay').html('<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_overtime' + val.status_logo + '"></use></svg>   ' + val.status_name + '');

                    //判断确认收/汇款按钮不为空的时候加入 && //获取每一块子订单按钮的长度 当按钮长度等于0的时候填入 防止加入多个按钮
                    if (val.opt_str_confirm != '' && $('.remittanceInfo[order_id=' + val.order_id + '] .opt-BS').length <= 0) {
                        // 判断是付款确认按钮显示确认放币
                        $('.remittanceInfo[order_id=' + val.order_id + '] .target-optstr').append('<div class="delete-button btn btn-confirm">' + '<div class="confirm">' + '<button type="button" class="yes opt-BS" target_BS="' + val.type + '" order_id="' + response.data[i].order_id + '">' + (val.type == 2 ? confirmLang : confirmPayLang) + '</button>' + '<button type="button" class="no">' + cancelLang + '</button>' + '</div>' + '<div class="button-face">' + val.opt_str_confirm + '</div>' + '</div>');
                    }

                    //判断未收到款项按钮不为空的时候加入 && 当按钮长度等于0的时候填入 防止加入多个按钮
                    if (val.opt_str_unreceipt != '' && $('.remittanceInfo[order_id=' + val.order_id + '] .target-optstr .unreceivable').length <= 0) {
                        $('.remittanceInfo[order_id=' + val.order_id + '] .target-optstr').append('<button  type="button" class="btn btn-confirm unreceivable gradual_change_Cquxiao" order_id="' + val.order_id + '">' + val.opt_str_unreceipt + '</button>');
                    }
                });
            } else {
                //当子订单有新的一条数据出现 执行 初始化
                var $orderCollect_tr_activebg = $(".orderCollect tbody tr.activebag"),
                    target_collect = $orderCollect_tr_activebg.attr('order-id'),
                    target_pay = $orderCollect_tr_activebg.attr('information');
                // 数据长度不一致重填
                refresh_Suborders(target_collect, target_pay, 0);
            }
        } else {
            // 初始化数据
            var table = SubordersHtml(response.data);
            $('#tab-trading-now  .orderListBox').html(table);
        }

    });
}

/**
 * 公共调用子订单组装function
 * @author 2017-12-25T14:56:35+0800
 * @return {[type]} [description]
 */
function SubordersHtml(data) {
    var table = '';
    var contact_time;
    var buy_sell_data = []
    for (var i = 0; i < data.length; i++) {
        table += '<div class="remittanceInfo gather" order_id="' + data[i].order_id + '">';

        table += '<div class="row clearfix">';

        if (data[i].time_limit > 0) {
            contact_time = '' + sysj + '：00:00:00';
        } else {
            contact_time = '';
        }
        table += '<div class="orderList-coin clearfix"><svg class="icon icon_norepeat" aria-hidden="true" style="margin-right:5px"><use xlink:href="#icon-' + data[i].currency_name + '1"></use></svg>' + data[i].currency_name + '<p class="remainTime" time="' + data[i].time_limit + '">' + contact_time + '</p></div>';

        if (data[i].status_logo == 1 || data[i].status_logo == 2) {
            data[i].status_logo = 1;
        } else if (data[i].status_logo == 3 || data[i].status_logo == 4) {
            data[i].status_logo = 3;
        }
        table += '<div class="waitPay"><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_overtime' + data[i].status_logo + '"></use></svg>   ' + data[i].status_name + '</div>';
        table += '<div class="remit_tips"><svg class="icon icon_norepeat vertical_middle" aria-hidden="true"><use xlink:href="#icon-ic_warn_white"></use></svg>   ' + data[i].remark + '</div>';

        table += '</div>';

        table += '<div class="row text-left">';
        table += '<div class="col-md-12 col-xs-12">';

        table += '<div class="threeCol clearfix">';
        // 买家付款/等待放币
        if (data[i].type == 1) {
            buy_sell_data = [sell_mj,data[i].sell_username,skyh,data[i].bank_name,'threeCol',skr,data[i].bank_real_name,skfs,data[i].pay_type_name];
            //	卖家等待买家付款
        } else if (data[i].type == 2) {
            buy_sell_data = [buy_mj,data[i].buy_username,skfs,data[i].pay_type_name,'doubleCol',skyh,data[i].bank_name,yhkh,data[i].bank_num];
        }
       
        table += '<p>' + buy_sell_data[0] + ': <span>' + buy_sell_data[1] + '</p>';
        table += '<p>' + jyq + ': <span>' + data[i].om_name + '</span></p>';
        table += '<p>' + buy_sell_data[2] + ': <span>' + buy_sell_data[3] + '</span></p>';
        table += '</div>';
        table += '<div class="' + buy_sell_data[4] + ' clearfix">';
        table += '<p>' + buy_sell_data[5] + ': <span>' + buy_sell_data[6] + '</span></p>';
        table += '<p>' + buy_sell_data[7] + ': <span>' + buy_sell_data[8] + '</span></p>';
        //买家付款才显示银行卡号，付款后不显示
        data[i].status == '1' ? table += '<p>' + yhkh + ': <span>' + data[i].bank_num + '</span></p>' : false;
        table += '</div>';
        
        if (data[i].bank_address != '') {
            table += '<div class="clearfix">';
            table += '<p>' + hkdz + ': <span>' + data[i].bank_address + '</span></p>';
            table += '</div>';
        }
        table += '</div>';

        table += '<div class="col-md-9 col-xs-12">';
        table += '<div class="clearfix">';
        table += '<p>' + danjia + '(USD): <span class="price">' + data[i].trade_price + '</span></p>';
        table += '</div>';

        table += '<div class="clearfix">';
        table += '<p>' + shuliang + '(' + data[i].currency_name + '): <span class="number">' + data[i].trade_num + '</span></p>';
        table += '</div>';

        table += '<div class="clearfix">';
        table += '<p>' + zonge + '(USD): <span class="sum">' + data[i].trade_money + '</span><span class="reference-price-currency"> [' + ckze + '(' + data[i].om_of_currency_symbol + ')：' + data[i].reference_price + ']</span></p>';
        table += '</div>';

        table += '<div class="clearfix">';
        table += '<p>' + ddhm + ': <span id="C2C_orderNum' + i + '">' + data[i].order_num + '</span><button type="button" id="copy" style="position:relative;background-color: inherit;color:#fff;"  class="btn btn-green copyBtn" data-clipboard-action="copy" data-clipboard-target="#C2C_orderNum' + i + '"><svg class="icon input-icon icon-bz" style="left:5px;top:0px;" aria-hidden="true"><use xlink:href="#icon-ic_wallet_copy"></use></svg></button></p>';
        table += '</div>';

        table += '</div>';

        table += '<div class="col-md-3 col-xs-4 text-right target-optstr">';

        if (data[i].opt_str_confirm != '') { //type 1是买--确认打款
            // 判断是付款确认按钮显示确认放币
            table += '<div class="delete-button btn btn-confirm"><div class="confirm"><button type="button" class="yes opt-BS" target_BS="' + data[i].type + '" order_id="' + data[i].order_id + '">' + (data[i].type == 2 ? confirmPayLang : confirmLang) + '</button><button type="button" class="no">' + cancelLang + '</button></div><div class="button-face">' + data[i].opt_str_confirm + '</div></div>';
        }
        if (data[i].opt_str_unreceipt != '') { //收款异常
            table += '<button  type="button" class="btn btn-confirm unreceivable gradual_change_Cquxiao" order_id="' + data[i].order_id + '">' + data[i].opt_str_unreceipt + '</button>';
        }
        table += '</div>';

        table += '</div>';
        table += '</div>';
    }

    return table;
}

/**
 * 剩余时间倒计时循环
 * @author 2017-12-25T14:56:35+0800
 * @return {[type]} [description]
 */

function timeLast() {
    // 获取订单时间开始倒计时
    var target_DDD;
    CtwoC_QBL.countLast = setInterval(function() {
        $(".remainTime").each(function() {
            var _this = $(this);
            var second = Number(_this.attr("time"));
            second--;
            if (second > 0) {
                _this.attr("time", second).text(C2C_formatSeconds(second));
            }
            //剩余秒数归0,加上超时告示class
            if (second <= 0) {
                target_DDD = _this.attr('target_DJS');
                if (target_DDD != 1) {
                    _this.text('').attr('target_DJS', 1).parents('.remittanceInfo.gather').find('.target-optstr').empty();
                }
            }

        });
    }, 1000);

}
/**
 * 转换时间格式00:00:00
 * @author 2017-12-25T14:56:35+0800
 * @parem  getzf 为补0函数
 * @return {[type]} [description]
 */
function C2C_formatSeconds(value) {
    var s = "",
        m = "",
        h = "";
    var secondTime = parseInt(value); // 秒
    var minuteTime = 0; // 分
    var hourTime = 0; // 小时
    if (secondTime > 60) {
        minuteTime = parseInt(secondTime / 60);
        secondTime = parseInt(secondTime % 60);
        if (minuteTime > 60) {
            hourTime = parseInt(minuteTime / 60);
            minuteTime = parseInt(minuteTime % 60);
        }
    }

    // 小于10在数字前加0
    CtwoC_QBL.result = getzf(parseInt(secondTime));

    if (minuteTime > 0) {
        // 小于10在数字前加0
        CtwoC_QBL.result = getzf(parseInt(minuteTime)) + ":" + CtwoC_QBL.result;
    } else {
        // 如果未到1小时显示00
        CtwoC_QBL.result = "00:" + CtwoC_QBL.result;
    }
    if (hourTime > 0) {
        // 小于10在数字前加0
        CtwoC_QBL.result = getzf(parseInt(hourTime)) + ":" + CtwoC_QBL.result;
    } else {
        // 如果未到1小时显示00
        CtwoC_QBL.result = "00:" + CtwoC_QBL.result;
    }
    CtwoC_QBL.result = '' + sysj + '：' + CtwoC_QBL.result;
    return CtwoC_QBL.result;
}

/**
 * --------- 订单管理 ---------
 * 我的订单管理信息初始化调用函数
 * @author 2018-2-27T10:19:53+0800
 * @param  获取对应订单的order_id
 * @return
 */
function order_management(ww_type) {
    var $order_management_tbody = $('.order-management tbody'),
        num_management,
        num_index;

    //我的订单 维护  
    if (Maintain(dealOrder, C2C_Maintain_order, C2C_Maintain_Pattern)) {
        clearInterval(Maintain_order);
        $order_management_tbody.html('');
        $('.small-Trading > ul').remove();
        $(".bs-switch").bootstrapSwitch('disabled', true);
        return;
    }
    // 记录上一次操作条数
    num_index = $order_management_tbody.find('tr.activebag').attr('gement');
    num_management = $order_management_tbody.find('tr.activebag').index();
    check_user("/CtoCTransaction/getUserMainOrderList", {}, 'post').then(function(response) {
        var table = '',
            len = response.data.length,
            leave_num,
            filldot;
        for (var i = 0; i < len; i++) {
            if (response.data[i].penging === 1) {
                filldot = '<span class="filldot"></span>';
            } else if (response.data[i].penging === 0) {
                filldot = '';
            }

            table += '<tr gement="' + (response.data[i].pid + response.data[i].order_id) + '" order-id="' + response.data[i].pid + '" information="' + response.data[i].order_id + '" class="gement' + (response.data[i].pid + response.data[i].order_id) + '" >';
            table += '<td>' + filldot + '' + response.data[i].type_name + '</td>';
            table += '<td>' + response.data[i].currency_name + '</td>';
            table += '<td>' + response.data[i].price + '</td>';
            table += '<td>' + response.data[i].num + '</td>';
            table += '<td>' + response.data[i].money + '</td>';

            if (response.data[i].self_trade_order === 0) {
                table += '<td>' + response.data[i].leave_num + '</td>';
            } else if (response.data[i].self_trade_order === 1) {
                table += '<td>/</td>';
            }

            table += '<td>' + response.data[i].opt_status_str + '</td>';

            if (response.data[i].self_trade_order == 0) {
                //不等于空的时候 加撤销  等于空的时候 待撤销 /
                if (response.data[i].opt_str == '') {
                    table += '<td>/</td>';
                } else {
                    table += '<td><button type="button" order-type = "' + response.data[i].type + '" data-toggle="modal" data-target="#cancel" class="btn btn-xs revokeNowOrder">' + chexiao + '</button></td>';
                }
            } else if (response.data[i].self_trade_order == 1) {
                table += '<td>/</td>';
            }
        }
        table += '</tr>';
        $order_management_tbody.html(table);
        if (num_management >= 0) {
            //给上一条添加active
            $(".orderCollect tbody .gement" + num_index + "").addClass('activebag');
        } else {
            // 判断是否存在操作记录，没有默认第一条click
            $order_management_tbody.find('tr:nth-child(1)').trigger('click');

            var windowWidth = $(window).width();
            if (ww_type == 0 && windowWidth <= 640 && $order_management_tbody.html() == '') {
                $('#zoom-P2B').trigger('click');
            }
            if (ww_type == 1 && windowWidth <= 640) {
                $('.orderManageBox').find(".panel-body").css({ display: "block" });
                $("#zoom-P2B").removeClass("btn-plus").addClass("btn-min");
            }
        }
    });

}
/**
 * --------- 订单管理 ---------
 * 历史公用发送ajax函数,为了方便后台调用
 * @author 2018-2-27T10:19:53+0800
 * @param  p为页码,type 1:已完成 2:已撤销
 * @return
 */
function TransitPage(p, type) {
    var $myOverSaleOrder_myRevokeOrder;
    var finsh_page_backout_page;
    // 如果找不到p，默认第一页
    if (typeof(p) == "undefined") {
        p = 1;
    }

    // 定位type
    if (typeof(type) == "undefined") {
        if ($('#getFinishOrder').hasClass('active')) {
            type = 1;
        } else if ($('#getRevokeOrder').hasClass('active')) {
            type = 2;
        }
    }
    // 发送ajax
    check_user("/CtoCTransaction/getUserHistoryTradeOrderList", {
        'type': type, // 类型 1:完成订单 2:已撤销
        "ajax_func": "TransitPage", //和后台统一的分页请求函数名
        'p': p //页码
    }, 'post').then(function(response) {
        if (response.code == 200) {
            // 判断是否有数据，有才调用组装函数，没有则为空
            var html = response.data != "" ? RestructTypeTable(response.data.list, type) : "";
            if (type === 1) {
                $('#myOverSaleOrder').html(html);
                $('#finsh-page').html(response.data.show);
            } else if (type === 2) {
                $('#myRevokeOrder').html(html);
                $('#backout-page').html(response.data.show);
            }
        }
    });
}

/**
 * --------- 订单管理 ---------
 * 公用填写历史详细table数据的函数
 * @author 2018-2-27T10:19:53+0800
 * @param  data为后台返回的数据,type为区别已完成和已撤销
 * @return 返回组装好的字符串
 */
function RestructTypeTable(data, type) {
    var table = '';
    var len = data.length;
    for (var i = 0; i < len; i++) {
        table += '<tr>';
        table += '<td>' + data[i].type_name + '</td>';
        table += '<td>' + data[i].currency_name + '</td>';
        table += '<td>' + data[i].trade_price + '</td>';
        table += '<td>' + data[i].trade_num + '</td>';
        table += '<td>' + data[i].trade_money + '</td>';
        table += '<td>' + data[i].reference_price + '</td>';
        table += '<td><button type="button" data-toggle="modal" data-target="#detail" data-type="' + type + '" order-num="' + data[i].order_num + '" trade-time="' + data[i].trade_time + '" buy-moneytime="' + data[i].shoukuan_time + '" end-time="' + data[i].end_time + '" update-time="' + data[i].update_time + '" class="btn btn-xs getDetails">' + xiangqing + '</button></td>';
        // 已完成显示完成时间
        if (type === 1) {
            table += '<td>' + UnixToDate(data[i].end_time, 2) + '</td>';
        } else {
            // 已撤销显示状态
            table += '<td>' + data[i].status_name + '</td>';
        }

        table += '</tr>';
    }

    return table;
}

/**         
 * 时间戳转换日期               
 * @param <int> unixTime    待时间戳(秒)               
 * @param <bool> isFull     返回完整时间(格式1:Y-m-d 或者 格式2:Y-m-d H:i:s)               
 * @param <int>  timeZone   时区               
 */

function UnixToDate(unixTime, isFull, timeZone) {
    // 如果时间戳为0，返回-
    if (unixTime <= 0) {
        return "-";
    }

    if (typeof(timeZone) == 'number') {
        unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
    }
    var time = new Date(unixTime * 1000);
    var ymdhis = "";
    ymdhis += time.getFullYear() + "-";
    ymdhis += getzf(time.getMonth() + 1) + "-";
    ymdhis += getzf(time.getDate());
    if (isFull === 2) {
        ymdhis += " " + getzf(time.getHours()) + ":";
        ymdhis += getzf(time.getMinutes()) + ":";
        ymdhis += getzf(time.getSeconds());
    }
    return ymdhis;
}

//补0操作
function getzf(num) {
    if (parseInt(num) < 10) {
        num = '0' + num;
    }
    return num;
}

function order_issue_show(a, b, c) {
    a.removeClass("active");
    b.hide();
    c.fadeIn(500);
}