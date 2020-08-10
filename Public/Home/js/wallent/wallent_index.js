$(function() {
    // 初始化数据
    wallet_data("");

    // PC和手机切换币种
    $("body").on("click", ".coinType_tab li", function() {
        var _this = $(this);
        var id = _this.attr('coin_id');
        var currency_name = _this.attr("coin_name");
        var is_close = _this.attr("is_close");
        if (is_close == 1) { //维护中
            BottomalertBox('bottom', bzwh, 'fail', "center", "center");
        } else {
            if (wallet_data(id)) { // 更新地址栏和表格数据
                _this.addClass("active").siblings().removeClass("active"); //添加选中状态和
                set_info(id, currency_name); // 更新提示信息
            }
        }
    });

    //手机切换币种
    var mysel = $("#coin_select");
    mysel.data("last", mysel.val()).change(function() {
        var oldvalue = mysel.data("last"); //这次改变之前的值
        oldvalue == null ? oldvalue = mysel.children(':first').val() : false;
        mysel.data("last", mysel.val()); //每次改变都附加上去，以便下次变化时获取 改变之前获取并保存
        var newvalue = mysel.val(); //当前选中值
        var currency_name = $("#coin_select option[value='" + newvalue + "']").attr("coin_name");
        var is_close = $("#coin_select option[value='" + newvalue + "']").attr("is_close");
        if (is_close == 1) { //维护中
            mysel.data("last", oldvalue).val(oldvalue).selectpicker('refresh');
            BottomalertBox('bottom', bzwh, 'fail', "center", "center");
        } else {
            if (wallet_data(newvalue)) { // 更新地址栏和表格数据
                set_info(newvalue, currency_name); // 更新提示信息
            }
        }
    });

    // 充币更新绑定地址
    $("button[id^=fresh]").on("click", function() {
        var AddressObj = $(this).parents(".coin_money_address").find("input[id^=address]");
        get_url(AddressObj.attr("coin_id"), 2, AddressObj.val());
    });
    $("button[id^=sign]").on("click", function() { get_url($(this).parents(".coin_money_address").find("input[id^=address]").attr("coin_id"), 1, ""); });
});



/**
 * 更新提示信息
 * @param id  币种id
 * @param currency_name 币种名
 */
function set_info(id, currency_name) {
    $(".currency_name").text(currency_name); //更新提示币种名
    var _$alert_info_content = $('.coin_money_content').eq(0).find('.alert-info');
    if (id == 7) { //EOS
        $("#eos_modal").modal("show");
        $('.coin_money_content,.coin_money_address').hide();
        $('#EOS-info,#EOS-address').show();
    } else if (id == 5 || id == 9) { //BCH
        $('.coin_money_address,#EOS-info').hide();
        $('#BCH-address,.coin_money_content[id!="EOS-info"]').show();
        //添加第五条标题
        if (_$alert_info_content.find('p').length == 4) {
            _$alert_info_content.append("<p>5. " + _PTJWNDCZDZTGCLGS_ + "</p>");
        }

    } else {
        _$alert_info_content.find('p:nth-child(5)').remove();
        $('.coin_money_content,.default_address').show();
        $('#BCH-address,#EOS-info,#EOS-address').hide();
    }
}



/**
 * 循环显示币种
 * @param data  接口数据
 */
function loop_coin(data) {
    var html = ""; //组装PC字符串
    var mhtml = ""; //组装手机端字符串
    $.each(data, function(i, val) {
        var active = val.tab_active ? "class='active'" : ""; // 初始化选中状态
        var checked = val.tab_active ? "selected='true'" : ""; // 初始化选中状态
        if (active) set_info(val.id, val.currency_name); // 初始化币种信息
        html += "<li " + active + " coin_id='" + val.id +
            "' coin_name='" + val.currency_name +
            "' is_close='" + val.closed + "'><a>" +
            "<svg class='icon icon_norepeat' aria-hidden='true'>" +
            "<use xlink:href='#icon-" + val.currency_name + "1'></use>" +
            "</svg> " + val.currency_name + val.maintain_str +
            "</a></li>";
        mhtml += "<option coin_name='" + val.currency_name +
            "' is_close='" + val.closed + "' " +
            checked + " value='" + val.id + "'>" +
            val.currency_name + val.maintain_str +
            "</option>";
    });

    // 填充字符串
    $('.coinType_tab').html(html);
    $("#coin_select").html(mhtml).selectpicker('refresh');
}


/**
 * 展示地址栏
 * @param url 接口地址数据
 */
function set_address(url) {
    $("input[id^=address]").attr("coin_id", url.currency_id); //绑定id用作后期更新绑定地址
    if (url.url_bool) {
        if (url.currency_id == 7) { //EOS
            $("#address2").val(url.my_charge_pack_url); // eos地址
            $("#address3").val(url.eos_pack_url);
        } else if (url.currency_id == 5 || url.currency_id == 9) { //BCH
            $("#address4").val(url.my_charge_pack_url);
            $("#address5").val(url.legacy_url);
        } else {
            $("#address").val(url.my_charge_pack_url); // 填充地址栏
        }
        // 显示对应按钮
        $("input[id^=address],button[id^=copy],button[id^=fresh]").show();
        $(".NoUrl,button[id^=sign]").hide();
    } else {
        if (url.currency_id == 7) {
            $("#address3").val(url.eos_pack_url); // eosMemo地址
            $("#address2,#address3+.NoUrl,#fresh2,#copy1").hide();
            $("#address2+.NoUrl,#address3,#sign2,#copy2").show();
        } else {
            $("input[id^=address],button[id^=copy],button[id^=fresh]").hide();
            $(".NoUrl,button[id^=sign]").show();
        }
    }
}


/**
 * 填充表格
 * @param data 表格数据
 */
function restruct_table(data) {
    var html = "";
    $.each(data.list, function(i, val) {
        html += '<tr>';
        html += '<td>' + val.id + '</td>';
        html += '<td>' + val.url + "&nbsp;&nbsp;" + val.third_url + '</td>';
        html += '<td>' + val.num + '</td>';
        html += '<td>' + val.actual + '</td>';
        html += '<td>' + val.add_time + '</td>';
        html += '<td>' + val.status + '</td>';
        html += '</tr>';
    });
    $("#list_body").html(html);
    $("#dataPage").html(data.page);
}


/**
 * 分页ajax重构表格
 * @param pager 页码
 * @param currency_id 币种ID
 */
function currenty(pager, currency_id) {
    $.ajax({
        method: "GET",
        url: "/newWallent/getChargeList",
        data: {
            currency_id: currency_id,
            p: pager
        },
        success: function(data) {
            if (data.code == 200) {
                restruct_table(data.data); // 填充表格
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
            }
        }
    })
}


/**
 *  获取并渲染页面数据
 * @param currency_id  币种id
 * @function loop_coin 循环币种
 * @function set_address 展示地址栏
 * @function currenty 填充表格
 * @function loadLate(platform.js) 2S后刷新页面
 */
function wallet_data(currency_id) {
    $.ajax({
        method: "POST",
        url: "/newWallent/getChargeInfo",
        data: {
            currency_id: currency_id
        },
        success: function(data) {
            if (data.code == 200) {
                // 渲染循环币种
                loop_coin(data.data.tab_currency);
                // 展示地址栏
                set_address(data.data.url_charge);
                // 填充表格
                restruct_table(data.data.res);
            } else {
                if (data.data.refresh) loadLate(); //币种下架刷新页面
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
                return false;
            }
        }
    });
}

/**
 * 充币更新绑定地址
 * @param currency_id 币种id
 * @param type(1/2) 类型
 * 2:更新 1:绑定
 * @param pack_url 地址
 * 2:必须传地址 1:地址必须为空
 * @function set_address 更新地址
 */
function get_url(currency_id, type, address) {
    $.ajax({
        method: "POST",
        url: "/newWallent/bindChargeAddr",
        data: {
            currency_id: currency_id,
            type: type,
            pack_url: address
        },
        success: function(data) {
            switch (data.code) {
                case 603:
                    alertBox(data.msg);
                    break;
                case 200:
                    set_address(data.data);
                    BottomalertBox('bottom', data.msg, "success", "center", "center");
                    break;
                default:
                    BottomalertBox('bottom', data.msg, "fail", "center", "center");
                    break;
            }
        }
    })
}