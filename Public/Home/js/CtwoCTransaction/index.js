$(document).ready(function() {

    if ($(document).width() > 640) {
        needTour({ 4: extraFn4, 6: extraFn6, 7: extraFn7 });
    }

    //判断是否新手tour
    isTour == 0 ? $('.TourBtn_state').fadeIn() : false;

});

$(function() {

    /* ===================== button下滑效果 =====================*/
    $('body').on('click', '.button-face', function() {
        $(this).parents('.delete-button').addClass("activate");
    });
    $('body').on('click', '.delete-button .no', function() {
        $(this).parents('.delete-button').removeClass("activate");
    });
    $('body').on('click', '.delete-button .yes', function() {
        $(this).parents('.delete-button').remove();
    });

    // ----------------  “订单管理”模块  -----------------
    /*
        作者：龚舜华
        作用：订单管理右边订单详情面板的切换行为
     */
    $(".orderCollect tbody tr").on("click", function() {
        var tabId = $(this).index();
        var $orderListBox = $(".orderListBox");
        $orderListBox.hide().eq(tabId).fadeIn(800);
        $('.orderListBox-null').fadeOut(200);
    });

    // ----------------  “订单管理”模块  -----------------
    /*
        作者：龚舜华
        作用：订单管理右边订单详情面板的切换行为
     */
    $(".orderCollect tbody tr").on("click", function() {
        var tabId = $(this).index();
        var $orderListBox = $(".orderListBox");
        $orderListBox.hide().eq(tabId).fadeIn(800);
        $('.orderListBox-null').fadeOut(200);
    });

    /*
    作者：龚舜华
    作用：订单发布弹窗的切换
    */
    $("#order-issue-buy").on("click", function() {
        $(this).addClass("active");
        order_issue_show($("#order-issue-sell"), $("#order-issue .sellBox"), $("#order-issue .buyBox"));
    });
    $("#order-issue-sell").on("click", function() {
        $(this).addClass("active");
        order_issue_show($("#order-issue-buy"), $("#order-issue .buyBox"), $("#order-issue .sellBox"));
    });

    /*
    作者：龚舜华
    作用：设置代表订单拼单进度的半透明条
    */
    $(".orderBook tbody tr").each(function(index, ele) {
        var rate = "38.8%";
        var $ele = $(ele);
        $ele.append('<td class="bar"></td>');
        $ele.find(".bar").animate({
            width: rate
        }, 300);
    });


});




// Tour新手流程
function extraFn6() {
    $(".introjs-fixedTooltip").css({ 'top': '0px' });
}

function extraFn7() {
    $(".introjs-fixedTooltip").css({ "top": "auto", 'left': '0px', 'bottom': '100px' });
}

function extraFn4() {
    $(".introjs-fixedTooltip").css("left", "138px");
}