//密码不能输入空格
$('#password').on('input', function() {
    this.value = this.value.replace(/[, ]/g, '');
});
$('#pass_confirm').on('input', function() {
    this.value = this.value.replace(/[, ]/g, '');
});


/**
 * 验证用户名、密码
 * @author 2017-11-17T17:46:49+0800
 * @param  {[type]} ){	var     username           [description]
 * @param  {[type]} beforeSend: function()         {		}        [description]
 * @param  {[type]} success:    function(response) {			if       (response.code [description]
 * @return {[type]}             [description]
 */
$('#loginBtn').click(function() {
    var username = $('#username').val();
    var password = $('#password').val();
    var data = {
        'account': username,
        'password': password
    };
    var $warming = $('.warming');
    //错误提示元素
    var $registerTips = $('.registerTips');
    if (username == '') {
        $warming.hide().fadeIn(300);
        $registerTips.text(usernameTips);
        return;
    } else if (password == '') {
        $warming.hide().fadeIn(300);
        $registerTips.text(passwordTips);
        return;
    }
    $.ajax({
        type: 'post',
        url: loginUrl,
        data: data,
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            if (response.code == 200) {
                //登录成功跳转动态密令div 隐藏登录div
                $('.register-warp-right > .showlogin-title').hide();
                //点击 disable 三秒
                setTimeSecd($("#loginBtn"));
                $('.QRcode').hide();
                $('.register-right').hide();
                $('.Dynamic').fadeIn(300);
                $('#oneTimePwd').val('');
                $warming.hide();
            } else if (response.code == 202) {
                $warming.hide().fadeIn(300);
                $registerTips.html($('#downLoadApp').html());
            } else {
                $warming.hide().fadeIn(300);
                $registerTips.text(response.msg);
            }
        }
    });
});


/**
 * 验证动态密令及登录
 * @author 2017-11-17T17:46:36+0800
 * @param  {[type]} ){	var     username          [description]
 * @param  {[type]} beforeSend: function()        {		}        [description]
 * @param  {[type]} success:    function(response [description]
 * @return {[type]}             [description]
 */
$('#loginNow').click(function() {
    var username = $('#username').val();
    var password = $('#password').val();
    var oneTimePwd = $('#oneTimePwd').val();
    var data = {
        'account': username,
        'password': password,
        'oneTimePwd': oneTimePwd
    };
    $.ajax({
        type: 'post',
        url: logingInUrl,
        data: data,
        dataType: 'json',
        error: function(request) {},
        beforeSend: function() {},
        success: function(response) {
            if (response.code == 200) {
                setTimeSecd($("#loginNow"));
                // 开启动画
                loginAnimate.play();
                $('.Dynamic-warmingBox .warming').addClass('rightTips').hide().fadeIn(300);
                $('.Dynamic-warmingBox .registerTips').text(czcg);
                // 判断动画完成后跳转页面
                loginAnimate.addEventListener('complete', function() {
                    setTimeout(function() {
                        window.location.href = UserCenter;
                    }, 800);
                });
            } else {
                $('.Dynamic-warmingBox .warming').hide().fadeIn(300);
                $('.Dynamic-warmingBox .registerTips').text(response.msg);
            }
        }
    });
});


/**
 * --------- 登录按钮---------
 * 3秒按钮倒计时
 * @author 2018-2-27T10:19:53+0800
 * @param  
 * @return
 */
function setTimeSecd(obj) {
    obj.attr("disabled", true);
    setTimeout(function() {
        obj.removeAttr("disabled");
    }, 3000);
}


//websocket 登录二维码
var showlogin_num = 0;
$(".QRcode").on("click", function() {
    if (showlogin_num % 2 == 0) {
        $('.register-warp-right > .showlogin-title').hide();
        $(".register-right").hide();
        $(".code-right").fadeIn();
        target_load('../../../../Public/Home/js/login_in/qrcode_webSocket.js');
    } else {

        $('.register-warp-right > .showlogin-title').fadeIn(300);
        $(".register-right").fadeIn();
        $(".code-right").hide();
        $('#login-QRcode').remove();
        window.qrcode_obj.ws.close();
        window.target_close(1);
        clearInterval(window.Training_QRcode);
    }
    showlogin_num++;
});

function target_load(url) { //url：需要加载js路径
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = url;
    script.id = 'login-QRcode';
    document.body.appendChild(script);
}

$('.showlogin-forget > a').on('click', function() {
    $('.warmingBox .warming').hide();
    $('.register-warp-right > .showlogin-title').hide();
    $('.register-right').hide();
    $('.Forget-password').hide().fadeIn(300);

});
$('.forget-Support-prev > span').on('click', function() {
    $('.Forget-password').hide();
    $('.register-warp-right > .showlogin-title').hide().fadeIn(300);
    $('.register-warp-right > .register-right').hide().fadeIn(300);

});

function enterSub(enterEle, targetForm) {
    enterEle.on("keydown", function(e) {
        if (e.keyCode == 13) {
            targetForm.trigger("click");
        }
    });
}
// -------- 登录页面回车 -------
enterSub($('#username'), $('#loginBtn'));
enterSub($('#password'), $('#loginBtn'));
enterSub($('#oneTimePwd'), $('#loginNow'));
// -------- 充值密码回车 -------
enterSub($('#phone'), $('#ResBtn1'));
enterSub($('#forget-username'), $('#ResBtn1'));
enterSub($('#token'), $('#next'));
enterSub($('.Forget-password-three input[name="newpwd"]'), $('#reset_submitBut'));
enterSub($('.Forget-password-three input[name="repeatpwd"]'), $('#reset_submitBut'));

//点击丢失密令打开客服聊天窗口
$('.Dynamic-forget').on('click', function() {

    $('.mylivechat_collapsed').trigger('click');
});
