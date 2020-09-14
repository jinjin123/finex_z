// 初始化动作
$(function() {


    $("#tab-ctlmenu li:nth-child(1)").addClass("active"); //打开页面默认第一颗选中


    /*   电脑端菜单控制   */
    $("#tab-ctlmenu li").click(function() {
        //控制亮灯
        var _this = $(this);
        $("#tab-ctlmenu li.active").removeClass("active");
        _this.addClass("active");
        $('#finacetype').selectpicker('val', '');
        //请求Ajax
        sendTong("getcoin_info", _this.attr("currency_type"));
    });



    /*   手机端菜单控制*/
    $("body").on('click', ".smshow .bootstrap-select ul.dropdown-menu li", function() {
        //请求Ajax
        sendTong("getcoin_info", $(this).attr("data-original-index"));
    });


    //搜索框异步刷新
    $('body').on('change', '#finacetype', function() {
        sendTong("find");
    });
});



//分页类异步刷新
function changepage(id) {
    var pid = id;
    sendTong("pagination", pid);
}



//ajax 通配优化
function sendTong(way, id) {
    var chuancan, type;
    switch (way) {
        //分页时使用
        case "pagination":
            //分页所需要的data传参
            chuancan = {
                'p': id,
                'currency_id': $('#tab-ctlmenu li.active').attr("currency_type"),
                'finance_type': $("#finacetype").val(),
            };
            type = "get";
            break;
            //搜索使用
        case "find":
            //搜索使用所需要的data传参
            chuancan = {
                'currency_id': $('#tab-ctlmenu li.active').attr("currency_type"),
                'finance_type': $("#finacetype").val(),
            };
            type = "post";
            break;
            //点击币种获取数据使用
        case "getcoin_info":
            //点击币种获取数据使用所需要的data传参
            chuancan = {
                'currency_id': id,
            };
            type = "post";
            break;
    }
    //ajax部分
    $.ajax({
        type: type,
        url: showfinace, //传入url变量
        data: chuancan, //传入data变量
        dataType: "json",
        success: function(data) {
            successFeedback(data);
        }
    });
}
//  Ajax数据返回处理函数
function successFeedback(data) {
    var list = "";
    for (var j = 0; j < data.list.length; j++) {
        list += "<tr>";
        list += "<td>" + data.list[j].finance_type + "</td>";
        list += "<td>" + data.list[j].type + "</td>";
        list += "<td class='money'>";

        if (data.list[j].typesb == 1) {
            list += "<span style='color:#65D8C1'>+" + data.list[j].money + "</span>";
        } else {
            list += "<span style='color:#FF5F5F'>-" + data.list[j].money + "</span>";
        }
        list += " </td>";
        list += "<td class='money'>";

        if (data.list[j].after_money > 0) {
            list += "<span style='color:#65D8C1'>+" + data.list[j].after_money + "</span>";
        } else {
            list += "<span style='color:#FF5F5F'>-" + data.list[j].after_money + "</span>";
        }
        list += " </td>";
        list += "<td>" + data.list[j].add_time + "</td>";
        list += " </tr>";
    }
    $("#log_area").html(list);
    $('#dataPage').html(data.show);
}