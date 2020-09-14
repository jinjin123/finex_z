// 重置密码验证(第一步)
function resetpassword_stepone() {
    var username = $('.Forget-password input[name=forget-username]').val();
    var phone = $('.Forget-password input[name=phone]').val();
    $.ajax({
        url: subForgetPassWorld,
        data: {
            'username': username,
            'phone': phone,
        },
        dataType: 'json',
        type: 'post',
        success: function(data) {
            $('.Forget-password .warming').hide().fadeIn();
            $('.Forget-password .warming .registerTips').text(data.msg);

            if (data.status == true) {
                uid = data.uid;
                $('.Forget-password').hide();
                $('.Forget-password-two').fadeIn(300);
                $('.warmingBox .warming').hide();
            }
        }
    });
}
// 重置密码验证(第二步)
function resetpassword_steptwo() {
    var token = $('input[name=token]').val();
    $.ajax({
        url: checkTokenUrl,
        data: {
            'token': token,
            'uid': uid,
        },
        dataType: 'json',
        type: 'post',
        success: function(data) {
            if (data.status == true) {
                $('.warmingBox .warming').hide();
                $('.Forget-password-two').hide();
                $('.Forget-password-three').fadeIn();
            } else {
                $('.Forget-password-two .warming').html(data.msg);
                $('.warmingBox .warming').hide().fadeIn();
            }
        }
    });
}

// 重置密码验证(第三步)

function resetpassword_stepthree() {
    var newpwd = $('input[name=newpwd]').val();
    var repeatpwd = $('input[name=repeatpwd]').val();
    // 前端判断密码是否为空或者两次密码不一致
    if (newpwd == '' || repeatpwd == '') {
        $('.Forget-password-three .warming').text(pswstr.putpsw).hide().fadeIn();
        return;
    } else if (newpwd != repeatpwd) {
        $('.Forget-password-three .warming').text(pswstr.pswnotsame).hide().fadeIn();
        return;
    }
    // 一致情况下请求修改
    $.ajax({
        url: subSetPassWordUrl,
        data: {
            'newpwd': newpwd,
            'repeatpwd': repeatpwd,
            'uid': uid,
        },
        dataType: 'json',
        type: 'post',
        success: function(data) {
            if (data.status == true) {
                $('.Forget-password-three .warming').addClass('rightTips').text(data.msg).hide().fadeIn();
                setTimeout(function() {
                    window.location.href = homeUrl;
                }, 1000);
                return;
            }
            $('.Forget-password-three .warming').text(data.msg).hide().fadeIn();

        }
    });
}

//重置密码第一步
$('#ResBtn1').click(function() {
    setTimeSecd($(this));
    resetpassword_stepone();
});
//重置密码第二部
$('#next').click(function() {
    setTimeSecd($(this));
    resetpassword_steptwo();
});
$('#back').click(function() {
    $("#reset_step2").hide();
    $("#reset_step1").fadeIn(1000, function() {
        //自适应页面
        footerAdaption($("#register"));
    });
});
//重置密码第三步
$('#reset_submitBut').click(function() {
    resetpassword_stepthree();
});