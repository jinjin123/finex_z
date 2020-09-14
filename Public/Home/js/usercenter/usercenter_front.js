$(document).ready(function() {
    //判断是否新手tour
    isTour == 0 ? $('.TourBtn_state').fadeIn() : false;

    //tour
    if ($(document).width() > 1024) {
        needTour({ 4: extraFn4, 6: extraFn6 });
    }

});


//卖出单价不能输入空格
$('#touchspin-example4').on('input', function() {
    this.value = this.value.replace(/[, ]/g, '');
});

var drop_open = $('.drop-open-con').text();
var drop_down = $('.drop-down-Trading').text();
var fa_angle_down = ' <i class="fa fa-angle-down"></i>';
//p2p我的订单 下拉显示文字
$('.drop-down-Trading').next().find('a').on('click', function() {
    $('.drop-down-Trading').html($(this).text() + fa_angle_down);
    $('.drop-open-con').html(drop_open + fa_angle_down);

});
$('.drop-open-con').next().find('a').on('click', function() {
    $('.drop-open-con').html($(this).text() + fa_angle_down);
    $('.drop-down-Trading').html(drop_down + fa_angle_down);
});

// 点击查找按钮
$('body').on('click', '.search-box button[data-target="#search"]', function() {
    //p2p市场订单模块维护
    if (Maintain(listOrder, P2P_Maintain_Marketorder, P2P_Maintain_Pattern)) {
        $(this).attr('data-target', '');
        return;
    }
    // 获取当前用户所选币种
    var coinNameMoney_text = $(".coinName.active").text();
    $("#search .numberBox span").html(coinNameMoney_text);
    var search_bankid = $("#search #buyBankId").parents('.btn-group').find('button span');
    if (search_bankid.text() == '') {
        search_bankid.text('--');
    }
});
$('body').on('click', '.target_order_buse', function() {
    //p2p下单模块维护 禁用卖出按钮
    var _this = $(this);
    var $target_order_buse = $('.target_order_buse');
    if (Maintain(forbidOrder, P2P_Maintain_Place, P2P_Maintain_Pattern)) {
        _this.find('a').attr('href', 'javascript:void(0);').removeAttr('data-toggle');
        return;
    }
    //p2p市场订单维护 禁用卖出按钮
    if (Maintain(listOrder, P2P_Maintain_Marketorder, P2P_Maintain_Pattern)) {
        _this.find('a').attr('href', 'javascript:void(0);').removeAttr('data-toggle');
        return;
    }
});

/**
 * 设置订单簿input框
 */
$('.number').on({
    "input": function() {
        validationNumber($(this), 2);

    }
});
$('.number-pri').on({
    "input": function() {

        validationNumber($(this), 1);

    }
});


// Tour新手流程
function extraFn6() {
    $(".introjs-fixedTooltip").css("top", "40%");
}

function extraFn4() {
    $(".introjs-fixedTooltip").css("left", "138px");
}