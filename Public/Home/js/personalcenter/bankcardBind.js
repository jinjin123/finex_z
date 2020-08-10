$(document).ready(function() {
    /* =========== 初始化select ==========*/
    $('select').selectpicker({
        //        noneSelectedText: "没有可选项",
    });
    //1、绑定银行卡成功后，下面对应显示刚绑的那个地区的卡
    var getCode = $("#country-area").val();
    //获取区号给li加active
    activeChange(getCode);
    //显示对应地区银行卡信息
    activeChangeHH(getCode);
    //获取该地区未绑定银行卡
    addcou(getCode);
});
setTimeout(ifMoren, 500);

$(function() {

    /* =========== 展开卡片时显示完整银行卡名称 ==========*/
    $('.credit_list .panel-tools .btn:first-of-type').click(function() {
        var _this = $(this);
        if (_this.hasClass('ellipsisText')) {
            _this.removeClass('ellipsisText').parents('.credit_list .panel').find('.panel-title').css('white-space', 'nowrap');
        } else {
            _this.addClass('ellipsisText').parents('.credit_list .panel').find('.panel-title').css('white-space', 'normal');
        }
    });
    //2、地区与开户银行的二级联动，用户银行信息对应显示
    $('#country-area').on('change', function() {
        var country = $('#country-area').val();
        //显示对应地区银行卡信息
        activeChangeHH(country);
        //获取区号给li加active
        activeChange(country);
        //获取该地区未绑定银行卡
        addcou(country);
        credit_card_first_add();
    });


    //4、银行卡绑定的ajax提交*/
    $('#btn_submit').click(function() {
        // 开户地区以及支行地址（不能含有连续11位数字）
        var countReg = /.*[\d]{11,}.*$/;
        // 如果通过了实名
        if (real_status == 1) {
            var country = $('#country_area').val();
            var address = $("#inputAddress1").val();
            //循环元素是否为空
            testKong($(".setting-tabs").find("input[type='text'],textarea,.select2-selection__rendered"),
                function(ele) {
                    if (ele.hasClass("select2-selection__rendered")) {
                        ele.parents(".select2").css("borderColor", "#509CF1");
                    } else {
                        ele.css("borderColor", "#509CF1");
                    }
                    ele.on("blur", function() {
                        if (ele.hasClass("select2-selection__rendered")) {
                            if (ele.html() != "") {
                                ele.parents(".select2").css("borderColor", "#2e3c44");
                            }
                        } else {
                            if (ele.val() != "") {
                                ele.css("borderColor", "#2e3c44");
                            }
                        }
                    });
                }, "select2-selection__rendered");
            if (testKong($(".setting-tabs").find(".form-group:not([style*='none']) input[type='text'],textarea:not([style*='none']),.select2-selection__rendered"), function(ele) {
                    ele.focus();
                    BottomalertBox("bottom", fullBankInfo, "fail", "center");
                }, "select2-selection__rendered", true) == true) {
                if (countReg.test(address)) {
                    BottomalertBox("bottom", numerror, "fail", "center");
                    return false;
                }
                $.ajax({
                    type: 'post',
                    url: subBankUrl,
                    data: $("#showBank_form").serialize(), ///表单数据序列化
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {
                            BottomalertBox("bottom", data.info, "success", "center");
                            setTimeout(function() {
                                window.location.href = "/PersonalCenter/showBankCardBind?type=1";
                            }, 1500);
                        } else {
                            BottomalertBox("bottom", data.info, "fail", "center");
                        }
                    }
                });
            }
        } else {
            alertBox(sm_status0 + "<a href='/PersonalCenter/index.html' style='color:#00dcda;'>" + smrz + "</a>");
        }
    });

    //银行卡点击设为默认
    $(".credit_list .panel-action>span").on("click", function() {
        var _this = $(this);
        var bankid = _this.attr("id");
        var country = $("#credit_card .dropdown-menu li.active a").attr("value");
        $.ajax({
            cache: true,
            type: 'post',
            url: bankDefult,
            data: {
                'bankid': bankid,
                'country': country
            },
            dataType: 'json',
            success: function(data) {
                //设置成功
                if (data.status == 200) {
                    //	所有三角形隐藏
                    _this.parents(".credit_list").children().find(".panel-default").removeClass("bank_city")

                    _this.parents(".panel-default").addClass('bank_city');
                    //			    选中的三角形显示
                    _this.parents(".panel-default").addClass('bank_city');
                    _this.parents(".credit_list").find(".panel-action>span").each(function(index, item) {
                        $(item).html(shemoren);
                    });
                    $(".credit_list .panel-action>span").removeClass('china_block_content');
                    _this.text(mrxz).addClass('china_block_content');
                    BottomalertBox("bottom", data.info, "success", "center");
                } else if (data.status == 201) {
                    BottomalertBox("bottom", data.info, "fail", "center");
                }
            }

        });
    });

    //解决点击国家列表的bug
    $("#credit_card > .nav-tabs .dropdown-menu li").on("click", function() {
        var _this = $(this);
        var _this_text = _this.text();
        $("#credit_card .dropdown-menu li").each(function(index, i) {
            $(i).removeClass("active");
        });
        _this.addClass("active").parents('li.dropdown').find('a.dropdown-toggle').html(_this_text + ' <i class="fa fa-angle-down"></i>');
        var countryId = _this.find("a").first().attr("href").slice(1);
        $("#credit_card .tab-content .tab-pane").each(function(index, item) {
            $(item).removeClass("active");
        });
        $("#" + countryId).addClass("active");
        credit_card_first_add();
    });

    //删除银行卡
    var _this = null;
    $(".delete_bank").click(function() {
        _this = $(this);
    });
    $("#revokeOrderById").click(function() {
        //获取银行卡id
        var id = _this.parents(".panel-body").siblings(".panel-heading").find('.panel-tools span').attr("title");
        //获取手机号码的值
        var LocalPhoneNum_value = $(".underline-tabs").find('#default_card .active a').attr("value");
        //获取手机号码的长度
        var LocalPhoneNum_length = LocalPhoneNum_value.length;
        //截取手机号码带有+号
        var LocalPhoneNum = LocalPhoneNum_value.substring(1, LocalPhoneNum_length);
        $.ajax({
            cache: true,
            type: "post",
            url: DeleteBank,
            data: {
                id: id,
                om: LocalPhoneNum
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 200) {
                    _this.parents(".col-lg-6").remove();
                    BottomalertBox("bottom", data.msg, "success", "center");
                    setTimeout(function() {
                        window.location.href = "/PersonalCenter/showBankCardBind?om=+" + LocalPhoneNum + "";
                    }, 1500);
                } else {
                    //第一个参数显示的位置，第二返回的信息，第三参数成功(success)或者失败(fail)的标志
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }

            }
        });
    });

    $("#inputNumber1").bind("input propertychange paste", function() {
        var _this = $(this);
        _this.val(_this.val().replace(/\D/g, ''));
    });

});




//给li标签添加active属性
function activeChange(country) {
    $("#default_card li").each(function(index, item) {
        $(item).removeClass("active");
    });
    var arr = [];
    arr['+86'] = "ch";
    arr['+852'] = 'hong';
    arr['+886'] = "taw";
    $("#" + arr[country]).parent().addClass("active");
}



//判斷是否有設為默認的銀行卡并給予其對應的樣式
function ifMoren() {
    var data = userbanks;
    //页面加载的时候进行判断是否有设为默认的银行卡
    if ($.isEmptyObject(data) != true) {
        $(".credit_list .panel-action>span").each(function(index, item) {
            $(item).html(shemoren);
        });
        for (var i = 0; i < data.length; i++) {
            //默认选用的三角形显示
            $("#" + data[i].bank_list_id).addClass('china_block_content').html(mrxz).parents(".panel-default").addClass("bank_city");
        }
    }
    credit_card_first_add();
}



//显示对应tab内容
function activeChangeHH(country) {
    var conuntry_type;
    switch (country) {
        case "+86":
            $("#hk").removeClass("active");
            $("#taiwan").removeClass("active");
            $("#china").addClass("active");
            conuntry_type = "#ch";
            break;
        case "+852":
            $("#china").removeClass("active");
            $("#taiwan").removeClass("active");
            $("#hk").addClass("active");
            conuntry_type = "#hong";
            break;
        case "+886":
            $("#hk").removeClass("active");
            $("#china").removeClass("active");
            $("#taiwan").addClass("active");
            conuntry_type = "#taw";
            break;
    }
    $("#credit_card .underline-tabs .dropdown-toggle").html($(conuntry_type).text() + ' <i class="fa fa-angle-down"></i>');
}



//3、根据地区获取该地区的银行
function addcou(country) {
    /*台湾用户不用填写开户地址*/
    if (country == "+886") {
        $('#bank_address').hide();
    } else {
        $('#bank_address').show();
    }
    $.ajax({
        cache: true,
        type: 'post',
        url: bankDataGet,
        data: {
            'country': country
        },
        dataType: 'json',
        success: function(data) {
            var list = "",
                first;
            if (data.data.length > 0) {
                for (i = 0; i < data.data.length; i++) {
                    if (i == 0) {
                        list += "<option value='" + data.data[i].id + "'>" + data.data[i].bank_name + "</option>";
                        continue;
                    }
                    list += "<option value='" + data.data[i].id + "'>" + data.data[i].bank_name + "</option>";
                }
                first = data.data[0].id;
            } else {
                list = "<option value='-1'>" + data.msg + "</option>";
                first = -1;
            }
            $("#inputTypeBank1").html(list).selectpicker('refresh').val(first).change();

        }
    });
}

function credit_card_first_add() {
    //获取银行卡是否默认选中
    var china_block_active = $('#credit_card .tab-content .credit_list.active > div.col-lg-6 > .panel-default');
    //判断没有则默认第一个选中
    if (!china_block_active.hasClass('bank_city')) {
        var $credit_card_firstdiv = $('#credit_card .tab-content .credit_list.active div:nth-child(1) .panel-default');
        $credit_card_firstdiv.addClass("bank_city").find('span').addClass('china_block_content').html(mrxz);
    }
}