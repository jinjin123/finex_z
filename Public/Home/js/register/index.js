$(function() {
    /**
     * 注册相关js
     */

    $('#sendPhoneCode').click(function() {
        // 获取输入的手机号码
        var phone = $('#mobile-number').val();
        var _this = $(this);
        var sendText = _this.text();
        // 获取拨号字冠和国家代码
        var phoneOm = $('.telCode').val();
        if (!tips(phone, CheckObj[2].tips)) {
            return false;
        }

        $.ajax({
            type: 'post',
            url: sendPhoneCodeUrl,
            data: {
                'phone': phone,
                'om': phoneOm
            },
            dataType: 'json',
            error: function(request) {},
            beforeSend: function() {},
            success: function(response) {
                // 如果手机获取验证码成功
                if (response.code == 200) {
                    tips(false, response.msg);
                    $('.registration_form_div .warming').addClass('rightTips');
                    // 验证码获取按钮倒计时
                    count_setTimeSecd(_this, sendText);
                } else {
                    tips(false, response.msg);
                }
            }
        });
    });
    //登录 账户名检测
    $('#username').blur(function() {
        checkParams($(this).val(), CheckObj[1].tips, CheckObj[1].name, 1);
    });
    //手机号码
    $('#mobile-number').blur(function() {
        checkParams($(this).val(), CheckObj[2].tips, CheckObj[1].name, 2);
    });
    //登录 密码检测
    $('#password').blur(function() {
        checkParams($(this).val(), CheckObj[3].tips, CheckObj[3].name, 3);
    });
    //再次确认密码
    $('#pass_confirm').blur(function() {
        var _this = $(this);
        var val = _this.val();
        var val1 = $('#password').val();

        if (!tips(val, pwdTips2)) {
            return false;
        }

        var flag = (val != val1) ? false : true;
        tips(flag, pwdTips3);
    });
    //手机验证码
    $('#phoneCode').blur(function() {
        tips($(this).val(), phoneCodeTips);
    });
    //填写图形验证码input
    $('#vercode').blur(function() {
        tips($(this).val(), vercodeTips);
    });
    //图形验证码更换
    $('#verifyImg').click(function() {
        changeverify();
    });

    //卖出单价不能输入空格
    $('#password').on('input', function() {
        this.value = this.value.replace(/[, ]/g, '')
    });
    $('#pass_confirm').on('input', function() {
        this.value = this.value.replace(/[, ]/g, '')
    });
    // 服务条款复选框
    //ifChanged是插件方法
    $("input[name='terms1']").on('ifChanged', checkBoxcc);
    /**
     * 提交注册相关数据
     * @author lirunqing 2017-10-23T15:37:55+0800
     */
    // 提交按钮事件
    $('#regBtn').click(function() {
        var username = $('#username').val();
        var phoneNum = $('#mobile-number').val();
        var phoneOm = $('.telCode').val();
        var password1 = $('#password').val();
        var password2 = $('#pass_confirm').val();
        var phoneCode = $('#phoneCode').val();
        var vercode = $('#vercode').val();

        if (!checkAllParams()) {
            return false;
        }

        if (!checkBoxcc()) {
            return false;
        }

        var data = {
            'username': username,
            'password': password1,
            'repassword': password2,
            'om': phoneOm,
            'phoneNum': phoneNum,
            'verfiycode': vercode,
            'phoneCode': phoneCode
        };
        $.ajax({
            cache: true,
            type: 'post',
            url: registerUrl,
            data: data,
            dataType: 'json',
            error: function(request) {},
            beforeSend: function() {},
            success: function(response) {
                if (response.code == 200) {
                    // 开启动画
                    loginAnimate.play();

                    // 提示语
                    $('#registration_form1').hide();
                    $('.register-Success').fadeIn(300);
                } else {
                    changeverify();
                    tips(false, response.msg);
                }
            }
        });
    });


});

var waitSecd =120;
// 获取验证码倒计时时长
function count_setTimeSecd(obj, sendText, type) {
    if (waitSecd == 0) {
        obj.text(sendText).removeAttr("disabled");
        // 验证码图片更换
        changeverify();
        waitSecd = 120 ;
    } else {
        obj.text(waitSecd + 's').attr("disabled", true);
        waitSecd-- ;
        setTimeout(function() {
            count_setTimeSecd(obj, sendText);
        }, 1000);
    }
}

// 随机生成验证码
function changeverify() {
    $("#verifyImg").attr("src", verifyUrl + "?" + Math.random());
}

function checkBoxcc() {
    // 获取当前服务条款复选框的勾选项数
    var checkedBox = $('input[name="terms1"]:checked').length;
    if (checkedBox < 2) {
        $('.warming').fadeIn(500);
        $('.registerTips').html(checkBoxTips);
        return false;
    } else {
        $('.warming').fadeOut(500);
        return true;
    }
}

/**
 * [tips description]
 * @author 2017-10-23T18:04:04+0800
 * @param  {String} val  
 * @param  {String} name 
 * @param  {String} tip  
 * @param  {String} val1 
 * @param  {String} tip2    
 * @return bool
 */
// 提示显示
function tips(val, tips) {
    var flag = false;
    // 去除提示框绿色样式
    $('.registration_form_div .warming').removeClass('rightTips');
    if (!val) {
        $('.warming').fadeIn(500);
        $('.registerTips').html(tips);
    } else {
        $('.warming').fadeOut(500);
        $('.registerTips').html('');
        flag = true;
    }

    return flag;
}

/**
 * 检测用户名和手机
 * @author 2017-10-23T16:25:10+0800
 * @param  string val  
 * @param  string name 
 * @param  string tips 
 * @param  string type 
 */
function checkParams(val, tip, name, type) {

    if (!tips(val, tip)) {
        return false;
    }

    var phoneOm = $('.telCode').val();

    $.ajax({
        cache: true,
        type: 'post',
        url: checkRegisterParamUrl,
        data: {
            'param': name,
            'val': val,
            'type': type,
            'phoneOm': phoneOm
        },
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            if (response.code == 200) {
                $('.warming').fadeOut(500);
                $('.registerTips').html('');
            } else {
                tips(false, response.msg);
            }
        }
    });
}

/**
 * 检测注册提交的参数
 * @author 2017-10-24T10:02:14+0800
 * @return bool
 */
// 检查所有提交表单需要的值
function checkAllParams() {
    var username = $('#username').val();
    var phoneNum = $('#mobile-number').val();
    var password1 = $('#password').val();
    var password2 = $('#pass_confirm').val();
    var phoneCode = $('#phoneCode').val();
    var vercode = $('#vercode').val();

    if (!tips(username, CheckObj[1].tips)) {
        return false;
    }

    if (!tips(phoneNum,  CheckObj[2].tips)) {
        return false;
    }
    if (!tips(phoneCode, phoneCodeTips)) {
        return false;
    }
    if (!tips(password1,  CheckObj[3].tips)) {
        return false;
    }
    flag = tips(password2, pwdTips2);
    if (!tips(password2, pwdTips2)) {
        return false;
    }



    if (!tips(vercode, vercodeTips)) {
        return false;
    }

    return true;
}