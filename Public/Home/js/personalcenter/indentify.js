/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/*
 *   判断图片格式和大小
 *   @author 何咏贤
 *   @param target触发的元素,id元素的id名
 */
//日期选取器初始化
$('#expTime').datepicker({
    todayBtn: "linked",
    autoclose: true,
    todayHighlight: true,
    format: "yyyy-mm-dd",
    orientation: "bottom",
    startDate: timestampToTime(target_datepicker),
    endDate: '2037-12-31'
});
/*判断是否进行了实名认证来打开*/
if (isReal == 0 || isReal == -1 || isReal == 2) {
    $("#account .panel:eq(0)>.panel-body").show();
}
// 护照有效期选择控制在今日或之后
$(function() {
    $('.btn-light-purple input[type="file"]').on('change', function() {
        var _this = $(this);
        fileAuthed(_this, _this.attr('id'));
    });

    // 点击银行卡开户人姓名的时候出现提示
    $("#openAccount").on("focus", function() {
        $(".bindCardWarn").show();
    });

    // 修改密码(第一步)
    $('.verifyImg').click(function() {
        fresh_Imgcode();
    });

    $('#confirmBtn').click(function() { //点击的时候
        //账号密码修改
        newpwd();

    });


    $('#moneypwdBtn').click(function() {
        //资金密码修改
        moneypwd();
    });


    //Ajax
    // 绑定邮箱
    $("#binding_email").on("click", function() {
        $.ajax({
            url: subSetEmailAdress,
            data: {
                'email': $("#addonLeftExample2").val()
            },
            dataType: 'json',
            type: 'post',
            success: function(data) {
                if (data.status == true) {
                    BottomalertBox("bottom", data.msg, "success", "center");
                    $('#Trapass_binding').modal('hide');
                    loadLate();
                } else {
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }
            }
        });

    });

    // 更改邮箱
    $("#saveEmail").on("click", function() {
        $.ajax({
            url: subModifyEmailAdress,
            data: {
                'email': $("#email2").val(),
                'vercode': $("#verCode").val()
            },
            dataType: 'json',
            type: 'post',
            success: function(data) {
                if (data.status == true) {
                    BottomalertBox("bottom", data.msg, "success", "center");
                    loadLate();
                } else {
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }
            }

        });
    });

    // 更改邮箱
    $("#Trapass_bindingBtn").on("click", function() {
        $.ajax({
            url: subSetTradePassword,
            data: {
                'Trapass1': $("#trapass").val(),
                'Trapass2': $("#repTrapass").val(),
                'Trapass3': $("#token").val()
            },
            dataType: 'json',
            type: 'post',
            success: function(data) {
                if (data.status == true) {
                    BottomalertBox("bottom", data.msg, "success", "center");
                    loadLate();
                } else if (data.code == 202) {
                    alertBox(nouserreal);
                } else {
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }
            }

        });
    });

    /* 解绑手机 */
    $("#token1").bind('input propertychange', function() {
        //检测正则
        EngNum($(this));
    });
    $("#token2").bind('input propertychange', function() {
        //检测正则
        EngNum($(this));
    });

    //令牌解绑获取验证码
    $('#getCode').click(function() {
        $.ajax({
            url: sendSmsUrl,
            dataType: 'json',
            type: 'post',
            success: function(data) {
                //提示发送成功 提示的内容：data.msg
                if (data.status == 200) {
                    BottomalertBox("bottom", data.msg, "success", "center");
                    setTimeSed($("#getCode"), $("#getCode").html());
                } else {
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }
            }

        });
    });

    // 解绑手机令牌
    $("#tokenUnBinding").on("click", function() {
        $.ajax({
            url: subUnbundingToken,
            data: {
                'serial_num': $("#token1").val(),
                'secret_key': $("#token2").val(),
                'sms': $("#mobile_vercode").val(),
                'checkver': "1",
                'vercode': $("#sellVerifyImg1").val(),
            },
            dataType: 'json',
            type: 'post',
            success: function(data) {
                if (data.status == true) {
                    BottomalertBox("bottom", data.msg, "success", "center");
                    setTimeout(reload1, 2000);
                } else {
                    fresh_Imgcode(); //刷新图片验证码
                    BottomalertBox("bottom", data.msg, "fail", "center");
                }
            }

        });
    });


    // ajax提交实名认证
    $("#smrz .panel-body").on("click", function() {
        var $expTime = $("#expTime"),
            $realform_filename = $("#realform .fileinput-filename");
        if ($expTime.val() != "") {
            $expTime.css("borderColor", "#2e3c44");
        }
        if ($realform_filename.eq(0).html() != "") {
            $realform_filename.eq(0).parents(".form-control").css("borderColor", "#2e3c44");
        }
        if ($realform_filename.eq(1).html() != "") {
            $realform_filename.eq(1).parents(".form-control").css("borderColor", "#2e3c44");
        }
    });
    $('#bton-yy').on("click", function() {

        if (testKong($("#realform input[type='text'] ,.fileinput-filename"), function(ele) {
                ele.focus();
                BottomalertBox("bottom", needinfo, "fail", "center");
            }, "fileinput-filename", true) == true) {
            var $inputName1 = $("#inputName1"),
                $inputName2 = $("#inputName2");
            $inputName1.val($inputName1.val().toUpperCase());
            $inputName2.val($inputName2.val().toUpperCase());

            var formData = new FormData();
            formData.append('up_img', document.getElementById("up_img").files[0]);
            formData.append('all_img', document.getElementById("all_img").files[0]);
            formData.append('idname1', $('#inputName1').val());
            formData.append('idname2', $('#inputName2').val());
            formData.append('bank_name', $('#openAccount').val());
            formData.append('idnum', $('#idnum').val());
            formData.append('date', $('#expTime').val());
            $.ajax({
                cache: false, // 不缓存
                processData: false, // jQuery不要去处理发送的数据
                contentType: false,
                type: 'post',
                url: "/PersonalCenter/subIdentify",
                data: formData,
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        BottomalertBox("bottom", data.info, "success", "center");
                        setTimeout(function() {
                            window.location.href = "/PersonalCenter/index";
                        }, 2000);
                    } else {
                        BottomalertBox("bottom", data.info, "fail", "center");
                    }
                    return true;
                }

            })
        }

    });
});
//实名认证上传图片
function fileAuthed(target, id) {
    //获取上传文件的文件名
    var filesname = target.val();
    var maxsize = 3 * 1024 * 1024;
    var ff = typeof(document.getElementById("up_img").files[0]) == "undefined" ? 0 : document.getElementById(id).files[0].size;
    //判断上传文件的后缀格式，错误则清空
    if (!filesname.match(/^.*\.(jpg|jpeg|gif|png|bmp)$/i) && filesname != "") {
        BottomalertBox('bottom', gsbzq, "fail", "center");
        target.val('');
        return false;
    }
    if (ff > maxsize) {
        BottomalertBox('bottom', tpgd, "fail", "center");
        target.val('');
        return false;
    }
}

//转时间函数
function timestampToTime(timestamp) {
    var date = new Date(timestamp * 1000); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
    Y = date.getFullYear() + '-';
    M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
    D = date.getDate() + ' ';
    return Y + M + D;
}

//跳转页面
function reload1() {
    window.location.href = loginoutUrl;
}

//修改密码
function newpwd() {
    var revisepwd = $('input[name=revisepwd]').val();
    var repeatpwd = $('input[name=repeatpwd]').val();
    var token = $('input[name=token]').val();
    $.ajax({
        url: subsetNewPassword,
        data: {
            'revisepwd': revisepwd,
            'repeatpwd': repeatpwd,
            'token': token,
        },
        dataType: 'json',
        type: 'post',
        success: function(data) {
            if (data.status == true) {
                BottomalertBox("bottom", data.msg, "success", "center");
                $('#pass_modify').modal('hide');
                setTimeout(reload1, 1700);
            } else {
                BottomalertBox("bottom", data.msg, "fail", "center");
                $('.feedback').text();
            }
        }
    });
}

// 修改资金密码
function moneypwd() {
    var Trapass1 = $('input[name=Trapass1]').val();
    var Trapass2 = $('input[name=Trapass2]').val();
    var Trapass3 = $('input[name=Trapass3]').val();
    $.ajax({
        url: subSetTradePassword,
        data: {
            'Trapass1': Trapass1,
            'Trapass2': Trapass2,
            'Trapass3': Trapass3,
        },
        dataType: 'json',
        type: 'post',
        success: function(data) {
            if (data.status == true) {
                BottomalertBox("bottom", data.msg, "success", "center");
                loadLate();
            } else if (data.code == 202) {
                // 未实名认证
                alertBox(nouserreal);
            } else {
                BottomalertBox("bottom", data.msg, "fail", "center");
                $('.feedback').text();
            }
        }
    });
}

//检测正则
function EngNum(e) {
    e.val(e.val().replace(/[^\w]/ig, '')); //只能输入英文字母和数字 
}