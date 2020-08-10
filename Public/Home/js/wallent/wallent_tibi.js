var index = 1; //地址栏索引值
var currencyid; //币种id

$(function() {
    // 初始化数据
    wallet_data("");

    // PC切换币种
    $("body").on("click", ".coinType_tab li", function() {
        var _this = $(this);
        var id = _this.attr('coin_id');
        var currency_name = _this.attr("coin_name");
        var is_close = _this.attr("is_close");
        EOS_change_refresh(is_close, id, 1, _this);
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
        EOS_change_refresh(is_close, newvalue, 2) ? true : mysel.data("last", oldvalue).val(oldvalue).selectpicker('refresh');
    });
    // 给索引和币种id值赋值
    $(".input-group-btn button").on("click", function() {
        switch (user_real_code) { //先判断实名状态
            case '603': //未实名
                alertBox(userRealFailed);
                break;
            case '602': //审核中
                alertBox(checkUserReal);
                break;
            default:
                var _this = $(this);
                index = _this.parents(".input-group").index();
                currencyid = _this.attr("coin_id");
                break;
        }
    });

    // 绑定提币地址
    $("#bindBtn").on("click", function() {
        var address = currencyid == 7 ? $("#EOSnewAddress").val() : $("#newAddress").val();
        var memoAddress = $("#memoAddress").val();
        if (!address) { // 判断是否为空
            BottomalertBox('bottom', bddzbnwk, "fail", "center", "center");
        } else if (currencyid == 7 && !memoAddress) {
            BottomalertBox('bottom', memobddzbnwk, "fail", "center", "center");
        } else {
            bind_address(currencyid, address, memoAddress, index);
        }
    });

    // 删除提币地址
    $("#delAddressOk").on("click", function() {
        delete_address(currencyid, index);
    });

    // 转出数量旷工费正则
    // validationNumber 为正则函数，转出数量保留8位，旷工费保留3位
    $("#tibi_num").bind('input propertychange', function() {
        validationNumber($(this), 4);
    });
    $("#collier_fee").bind('input propertychange', function() {
        validationNumber($(this), 3);
    });

    // 提币获取最大数
    $(".btn-take").on("click", function() {
        get_Maxnum(currencyid);
    });

    // 点击最大提币数填入input框
    $("#Maxnum").on("click", function() {
        var max = $(this).attr("max");
        $("#tibi_num").val(max);
    });

    // 更新图形验证码
    $('.verifyImg').click(function() {
        fresh_Imgcode();
    });

    // 发送手机验证码倒计时
    $('.countdown').click(function() {
        var verifyImg = $("#sellVerifyImg").val();
        sendPhoneCode(verifyImg);
    });

    //提交提币表单
    $("#tibi").on("click", function() {
        var num = $("#tibi_num").val();
        var collier_fee = $("#collier_fee").val();
        var trade_pwd = $("#trade_pwd").val();
        var img_code = $("#sellVerifyImg").val();
        var phone_code = $("#phone_code").val();
        // 通过验证才提交表单
        if (check_form(num, collier_fee, trade_pwd, phone_code))
            send_form(currencyid, num, collier_fee, trade_pwd, img_code, phone_code, index);
    });

});

/**
 * 提币页面更新地址栏和表格数据
 * @param is_close  币种type
 * @param id 币种id
 * @param type pc端需要加的active
 */
function EOS_change_refresh(is_close, id, type, obj) {
    if (is_close == 1) { //维护中
        BottomalertBox('bottom', bzwh, 'fail', "center", "center");
        return false;
    } else {
        if (wallet_data(id)) { // 更新地址栏和表格数据
            set_info(id, currency_name); // 更新提示信息
            type == 1 ? obj.addClass("active").siblings().removeClass("active") : false; //添加选中状态和
        }
    }

    if (id == 7) { // EOS
        $(".defaultBind").hide();
        $("#EOS_Memo").show();
        $(".Withdraw_money_address").addClass("EOS_show");
    } else {
        $(".defaultBind").show();
        $("#EOS_Memo").hide();
        $(".Withdraw_money_address").removeClass("EOS_show");
    }
    return true;
}

/**
 * 更新提示信息
 * @param id  币种id
 * @param currency_name 币种名
 */
function set_info(id, currency_name) {
    $(".currency_name").text(currency_name); //更新提示币种名
}


/**
 * 循环显示币种
 * @param data  接口数据
 */
function loop_coin(data) {
    var html = ""; //组装PC字符串
    var mhtml = ""; //组装手机端字符串
    $.each(data, function(i, val) {
        var active = val.active_tab ? "class='active'" : ""; // 初始化选中状态
        var checked = val.active_tab ? "selected='true'" : ""; // 初始化选中状态
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
 * 地址栏展示
 * @param address 提币地址
 * @param memo Memo地址
 * @param index 地址索引
 */
function set_address(address, memo, index) {
    var memoUrl = memo ? ":" + memo : "";
    var url = address + memoUrl;
    $("#address" + index).val(url); // 填充地址栏
    if (address) {
        $("#btn-take" + index + ",#btn-delete" + index).show();
        $("#btn-chain" + index).hide();
    } else {
        $("#btn-take" + index + ",#btn-delete" + index).hide();
        $("#btn-chain" + index).show();
    }
}


/**
 * 操作完成后重置数据
 * @function fresh_Imgcode 刷新图片验证码
 */
function clear_data() {
    $(".modal").modal("hide");
    $(".modal input").val("");
}


/**
 * 填充表格
 * @param data 表格数据
 */
function restruct_table(data) {
    var html = "";
    var href = "";
    $.each(data.list, function(i, val) {
        href = val.ti_id ? "&nbsp;&nbsp;" + val.tibi_detial_url : "";
        html += '<tr>';
        html += '<td>' + val.id + '</td>';
        html += '<td>' + val.url + href + '</td>';
        html += '<td>' + val.num + '</td>';
        html += '<td>' + val.add_time + '</td>';
        html += '<td>' + val.collier_fee + '</td>';
        html += '<td>' + val.actual + '</td>';
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
function tibi_currency(pager, currency_id) {
    $.ajax({
        method: "GET",
        url: "/newWallent/getTiBiList",
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
    });
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
        url: "/newWallent/getTiBiPageData",
        data: {
            currency_id: currency_id
        },
        success: function(data) {
            if (data.code == 200) {
                clear_data(); // 重置数据
                // 渲染循环币种
                loop_coin(data.data.tab_currency);
                // 展示地址栏
                var url = data.data.tibiUrl;
                $(".input-group-btn button").attr("coin_id", url.currency_id); //给button绑定币种id方便后面弹窗操作

                // 如果数据为空操作
                var nullUrl = { "addr": "", "memo": "" };
                var addr1 = (url == null || url.length < 1) ? nullUrl : url.pack_url1;
                var addr2 = (url == null || url.length < 2) ? nullUrl : url.pack_url2;
                var addr3 = (url == null || url.length < 3) ? nullUrl : url.pack_url3;
                set_address(addr1.addr, addr1.memo, 1);
                set_address(addr2.addr, addr2.memo, 2);
                set_address(addr3.addr, addr3.memo, 3);

                // 填充表格
                restruct_table(data.data.res);
            } else {
                if (data.data.refresh) loadLate(); //币种下架刷新页面
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
                return false;
            }
        }
    })
}


/**
 * 绑定提币地址
 * @param currency_id   币种id
 * @param address   提币地址
 * @param memoAddress   Memo地址
 * @param address_index    地址索引值
 * @function set_address 更新地址栏状态
 * @function clear_data 重置数据
 */
function bind_address(currency_id, address, memoAddress, address_index) {
    $.ajax({
        method: "POST",
        url: "/newWallent/bindTiBiUrl",
        data: {
            currency_id: currency_id,
            address: address,
            memo: memoAddress,
            address_index: address_index
        },
        success: function(data) {
            if (data.code == 200) {
                set_address(data.data.addr, data.data.memo, address_index); //更新地址栏
                clear_data(); // 重置数据
                BottomalertBox('bottom', data.msg, "success", "center", "center");
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
            }
        }
    })
}


/**
 *  删除提币地址
 * @param currency_id   币种id
 * @param address_index    地址索引值
 * @function set_address 更新地址栏状态
 * @function clear_data 重置数据
 */
function delete_address(currency_id, address_index) {
    $.ajax({
        method: "POST",
        url: "/newWallent/delTiBiUrl",
        data: {
            currency_id: currency_id,
            address_index: address_index
        },
        success: function(data) {
            if (data.code == 200) {
                set_address("", "", address_index); //更新地址栏
                clear_data(); // 重置数据
                BottomalertBox('bottom', data.msg, "success", "center", "center");
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
            }
        }
    })
}


/**
 * 获取当前最大最小转出数
 * @param currency_id   币种id
 */
function get_Maxnum(currency_id) {
    $.ajax({
        method: "GET",
        url: "/newWallent/getMaxTransferNum",
        data: {
            currency_id: currency_id,
        },
        success: function(data) {
            if (data.code == 200) {
                $("#Maxnum").attr("max", data.data.num).attr("min", data.data.min_num);
                $("#tibi_num").attr("placeholder", dbzx + ' : ' + data.data.min_num);
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
            }
        }
    })
}

/**
 *  发送手机验证码
 * @param verifyImg 图片验证码值
 * @function fresh_Imgcode 刷新图片验证码
 */
function sendPhoneCode(verifyImg) {
    $.ajax({
        method: "POST",
        url: "/newWallent/sendPhoneCode",
        success: function(data) {
            if (data.status == 200) {
                setTimeSed($(".countdown"), $(".countdown").html());
                BottomalertBox('bottom', data.msg, "success", "center", "center");
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
            }
        }
    })
}

/**
 * 检测表单值
 * @param num 转出数量
 * @param collier_fee 矿工费
 * @param trade_pwd 资金密码
 * @param phone_code 验证码
 * @returns {boolean} true/false
 */
function check_form(num, collier_fee, trade_pwd, phone_code) {
    //验证输入的提币数量是否符合
    if (!num) {
        BottomalertBox('bottom', tibinumbernotnull, "fail", "center", "center");
        return false;
    }
    var Maxnum = $("#Maxnum").attr("max");
    var Minnum = $("#Maxnum").attr("min");
    if (parseFloat(num) > parseFloat(Maxnum)) { //比较tibi_num和最大值得大小
        BottomalertBox('bottom', tibinumberbig, "fail", "center", "center");
        return false;
    } else if (parseFloat(num) < parseFloat(Minnum)) {
        BottomalertBox('bottom', tibinumbermin, "fail", "center", "center");
        return false;
    }
    if (!collier_fee) {
        BottomalertBox('bottom', collierfeeerror, "fail", "center", "center");
        return false;
    } else if (collier_fee < 0.001) { //矿工费不能小于0.01
        BottomalertBox('bottom', collierfeeegt, "fail", "center", "center");
        return false;
    }
    if (!trade_pwd) { //验证资金密码
        BottomalertBox('bottom', tradepwdnotnull, "fail", "center", "center");
        return false;
    }
    if (!phone_code) { //验证手机号码
        BottomalertBox('bottom', phonecodenotnull, "fail", "center", "center");
        return false;
    }

    return true;
}

/**
 * 提币表单提交
 * @param currency_id 币种id
 * @param num 转出数量
 * @param collier_fee 矿工费
 * @param trade_pwd 资金密码
 * @param phone_code 验证码
 * @param address_index 手机验证码
 */
function send_form(currency_id, num, collier_fee, trade_pwd, img_code, phone_code, address_index) {
    $.ajax({
        method: "POST",
        url: "/newWallent/subTransferNum",
        data: {
            currency_id: currency_id,
            num: num,
            collier_fee: collier_fee,
            trade_pwd: trade_pwd,
            phone_code: phone_code,
            img_code: img_code,
            address_index: address_index
        },
        success: function(data) {
            if (data.code == 200) {
                wait = 0;
                clear_data(); // 重置数据
                if (data.data.page_code == 200) {
                    restruct_table(data.data.res);
                }
                BottomalertBox('bottom', data.msg, "success", "center", "center");
            } else {
                BottomalertBox('bottom', data.msg, "fail", "center", "center");
                fresh_Imgcode(); // 更新图片验证码
            }
        }
    })
}