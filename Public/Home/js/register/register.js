/*
	作者：龚舜华
	作用：不支持国家面板切换
	参数：无
 */
$("#checkService").on("click", function() {
    $("#registration_form1").hide();
    $(".register-Support").fadeIn(300);
});
$("#backbut").on("click", function() {
    $(".register-Support").hide();
    $("#registration_form1").fadeIn(300);
    $('#terms1').iCheck('check');
});



/*
    作者：何咏贤
    作用：调用icheck和手机区号插件
    参数：无
*/
// 手机插件
$("#mobile-number").intlTelInput();
// icheck
$('input.icheck-minimal-grey').iCheck({
    checkboxClass: 'icheckbox_minimal-grey',
    radioClass: 'iradio_minimal-grey'
});

// 设置不支持国家框架的高度
if (window.innerWidth > 1024) {
    var H1 = $('#registration_form1').outerHeight();
    var H2 = $('#register_form2').outerHeight();
    $('#register_form2 .smfullBtn').css('margin-top', (H1 - H2) + 95);
}


