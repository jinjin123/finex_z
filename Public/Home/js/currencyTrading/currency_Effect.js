var supremeNum = 1;


$(document).ready(function() {


    //判断是否新手tour
    isTour == 0 ? $('.TourBtn_state').fadeIn() : false;
    //tour
    if ($(document).width() > 640) {
        needTour({
            4: extraFn4,
            7: extraFn7,
            6: extraFn6,
            8: extraFn8
        });
    }
    //判断屏幕小于1024 右边div高度小于870设置最小高度
    if ($(window).width() > 1024 && $('.content-right').outerHeight() < 870) {
        $('.content-right').css('min-height', '870px');
    }


});


// =====================点击 max  最大 =====================//
$('body').on('click', '.supreme', function() {
    //max按钮变量
    supremeNum++;
    var trans = $('.transactionType .active').attr("id");
    //当前余额变量
    //判断max按钮当前是否选中状态
    if (supremeNum % 2 == 0) {
        $(this).addClass("order-max-active");
        //市价 限制
        var transactionType_Array = [];
        if (trans == "transactionType-1") {
            //买入余额 1.数量 2.价钱
            transactionType_Array = [$('.currency-2').text(),$('#SaleIn_count_1').val(),$('#SaleIn_count_2'),$('.ZJE-btt1 .Total-amount')];
        } else if (trans == "transactionType-2") {
            //卖出余额
            transactionType_Array = [$('.currency-4').text(),$('#SaleOut_count_1').val(),$('#SaleOut_count_2'),$('.ZJE-btt3 .Total-amount')];
        }
        trad_coin_coinInfo(transactionType_Array[0], transactionType_Array[2], 2, 2);
        trad_coin_Calculation(1, transactionType_Array[0], transactionType_Array[1], transactionType_Array[3]);
    } else {
        target_appear($("#SaleIn_count_2"), 4);
        target_appear($("#SaleOut_count_2"), 4);
        target_appear($(".ZJE-btt1 .Total-amount"), 3);
        target_appear($(".ZJE-btt3 .Total-amount"), 3);
        $(this).removeClass("order-max-active");
    }


});

// =====================点击买入卖出 隐藏更新资金密码 =====================//
$('body').on('click', '#transactionType-1', function() {
    //维护 判断php变量 跳提示
    if (Maintain(forbidOrder, BB_Maintain_Place, BB_Maintain_Pattern) || Maintain(listOrder, BB_Maintain_Marketorder, BB_Maintain_Pattern)) {
        $(this).find('a').attr('href', 'javascript:void(0);').removeAttr('data-toggle');
        return;
    }
    //max按钮变量
    supremeNum = 1;
    //最大max按钮
    $(".supreme").removeClass("order-max-active");
    //获取市价单/限价单 val值
    var saletype1_val = $('#SaleType1').val();
    //判断 限价单显示/市价单显示
    if (saletype1_val == 1) {
        $('.ZJE-btt2').hide();
        $('.ZJE-btt1').fadeIn();
    } else if (saletype1_val == 2) {
        $('.ZJE-btt1').hide();
        $('.ZJE-btt2').fadeIn();
    }
    target_appear($('.BtoB_order_show'), 1);
    target_appear($('.Confirmation'), 2);
    target_appear($('#SaleIn_count_2'), 4);
    target_appear($('#SaleIn_mar_1'), 4);
    target_appear($('.Total-amount'), 3);

    //买入 去除红色按钮背景class 加上绿色背景class
    $('#transactionType-1 > #D2D_buy').removeClass('transactionType_normal').addClass('transactionType_buy');
    $('#transactionType-2 > #D2D_sell').removeClass('transactionType_sell').addClass('transactionType_normal');

    //买入 去除红色背景class 加上绿色背景class
    $('#trad_coin_confirmOrder').removeClass("trad_coin_confirmOrder_sell").addClass('trad_coin_confirmOrder_buy');

    $('#SaleOut').removeClass('active in');
    $('#SaleIn').addClass('active in');

});
/**
 * 币币交易卖出按钮
 */
$('body').on('click', '#transactionType-2', function() {
    if (Maintain(forbidOrder, BB_Maintain_Place, BB_Maintain_Pattern) || Maintain(listOrder, BB_Maintain_Marketorder, BB_Maintain_Pattern)) {
        $(this).find('a').attr('href', 'javascript:void(0);').removeAttr('data-toggle');
        return;
    }
    //max按钮变量
    supremeNum = 1;
    //最大max按钮
    $(".supreme").removeClass("order-max-active");
    //获取市价单/限价单 val值
    var saletype2_val = $('#SaleType2').val();
    //判断 限价单显示/市价单显示
    if (saletype2_val == 1) {
        $('.ZJE-btt4').hide();
        $('.ZJE-btt3').fadeIn();
    } else if (saletype2_val == 2) {
        $('.ZJE-btt3').hide();
        $('.ZJE-btt4').fadeIn();
    }
    target_appear($('.BtoB_order_show'), 1);
    target_appear($('.Confirmation'), 2);
    target_appear($('.Total-amount'), 3);
    target_appear($('#SaleOut_mar_1'), 4);
    target_appear($('#SaleOut_count_2'), 4);

    //卖出 去除红色按钮背景class 加上绿色背景class
    $('#transactionType-1 > #D2D_buy').removeClass('transactionType_buy').addClass('transactionType_normal');
    $('#transactionType-2 > #D2D_sell').removeClass('transactionType_normal').addClass('transactionType_sell');

    //卖出去除绿色背景class 加上红色背景class
    $('#trad_coin_confirmOrder').removeClass("trad_coin_confirmOrder_buy").addClass('trad_coin_confirmOrder_sell');

    $('#SaleIn').removeClass('active in');
    $('#SaleOut').addClass('active in');
});


// 选择市价和限价显示不同的内容
$('body').on('change', '#SaleType1', function() {
    var _this = $(this);
    var val = _this.val();
    if (val == 1) {
        supremeNum = 1;
        //最大max按钮
        $(".supreme").removeClass("order-max-active");
        limit($('.ZJE-btt2'), $('.ZJE-btt1'), $('.ZJE-btt1 .Total-amount'), $('#SaleIn_count_2'), _this, '.BuyMoney', '.market_sell');
    } else if (val == 2) {
        limit($('.ZJE-btt1'), $('.ZJE-btt2'), $('.ZJE-btt2 .Total-amount'), $('#SaleIn_mar_1'), _this, '.market_sell', '.BuyMoney');
    }
});
$('body').on('change', '#SaleType2', function(e) {
    var _this = $(this);
    var val = _this.val();
    if (val == 1) {
        supremeNum = 1;
        //最大max按钮
        $(".supreme").removeClass("order-max-active");
        limit($('.ZJE-btt4'), $('.ZJE-btt3'), $('.ZJE-btt3 .Total-amount'), $('#SaleOut_count_2'), _this, '.BuyMoney', '.market_sell');
    } else if (val == 2) {
        limit($('.ZJE-btt3'), $('.ZJE-btt4'), $('.ZJE-btt4 .Total-amount'), $('#SaleOut_mar_1'), _this, '.market_sell', '.BuyMoney');
    }

});

/*完善左边导航栏手机时点不了*/
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


//点击放大按钮触发事件
var expand = false;
$(".click-expand").click(function() {
    if (expand) {
        $(this).parents(".trad_coin_middlecontent").removeClass("remove-expand");
    } else {
        $(this).parents(".trad_coin_middlecontent").addClass("remove-expand");
    }
    expand = !expand;
});


/**
 * 币币交易限价市价切换div
 * @param {元素隐藏} zb_1 
 * @param {元素出现} zb_2 
 * @param {元素文本为0} zb_3 
 * @param {元素val为空} zb_4 
 * @param {限价单/市价单 切换} obj 
 * @param {显示的元素} zb_5 
 * @param {隐藏的元素} zb_6 
 */
function limit(zb_1, zb_2, zb_3, zb_4, obj, zb_5, zb_6) {
    zb_1.hide();
    zb_2.fadeIn(200);
    zb_3.text("0");
    zb_4.val("");
    obj.parents('.BuyForm').find(zb_5).fadeIn(200);
    obj.parents('.BuyForm').find(zb_6).hide();
}


// Tour新手流程
function extraFn6() {
    $(".introjs-fixedTooltip").css("top", "78px");
}

function extraFn4() {
    $(".introjs-fixedTooltip").css("left", "138px");
}

function extraFn7() {
    $(".introjs-fixedTooltip").css("top", "0px");
}

function extraFn8() {
    $(".introjs-fixedTooltip").css({
        "top": "auto",
        'bottom': '180px'
    });
}