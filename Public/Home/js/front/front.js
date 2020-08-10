var target_enter = 0;
var left = 0;
var timer;
var server_url = 'ws://47.57.131.217:9502'; //WebSocket路径

/*
	作者：龚舜华
	作用：实现首页登录框PC固定移动端弹出效果
	参数：无
 */

function appearLLoad() {

    //登录框弹出
    var $login_banner_modal = $("#login_banner .modal");
    $("#loginBtn2").on("click", function() {
        $("#sign-up-sec .sign-up-sec").show();

        $login_banner_modal.addClass("mobile-sign-up").parent().removeClass("hidden-xs");
    });
    // 登录框关闭
    $("#sign-up-sec .close>span").on("click", function() {
        $login_banner_modal.removeClass("mobile-sign-up");
    });
}



/*
	作者：龚舜华
	作用：输入的英文自动变大写
	@param ele：目标输入框元素；
 */
function upperInput(ele) {
    var isPin = false;
    ele.on({
        "compositionend": function() {
            if (!isPin) {
                var eleVal = ele.val();
                ele.val(eleVal.toUpperCase());
                isPin = false;
            }
        },
        "compositionstart": function() {
            isPin = true;
        },
        "input propertychange": function() {
            if (!isPin) {
                var eleVal = ele.val();
                ele.val(eleVal.toUpperCase());
                isPin = false;
            }
        },
        "blur": function() {
            var eleVal = ele.val();
            ele.val(eleVal.toUpperCase());
        }
    });
}
upperInput($("#oneTimePwd"));
upperInput($("#token"));

/*
	作者：龚舜华
	作用：检测密码强度
	使用方法：为需要检验的表单元素(有value)
	@param container：：需要指定的容器；

 */
function strengthPwd(obj) {
    function fn(obj) {
        this.init(obj);
    }
    fn.prototype = {
        init: function(obj) {
            this.eles = this.container ? (obj.container.find("*[data-strength]")) : $("*[data-strength]");
            if (obj.show) {
                this.show();
            }
        },
        strengthHTML: function() {
            var html = "";
            html += "<div id='strength-check' >";
            html += "  <ul class='strength-check-heading'>";
            html += "    <p>" + strengthTitle1 + "</p>";
            html += "            <li class='tips'>" + strengthTips1 + "</li>";
            html += "            <li class='tips'>" + strengthTips2 + "</li>";
            html += "            <li class='tips'>" + strengthTips3 + "</li>";
            html += "  </ul>";
            html += "  <div class='strength-check-middle'>";
            html += "     <p>" + strengthTitle2 + "</p>";
            html += "     <div id='password-strength'>";
            html += "       <div class='box box1'>";
            html += "         <div class='bar-text'></div>";
            html += "         <div class='bar'></div>";
            html += "       </div>";
            html += "       <div class='box box2'>";
            html += "         <div class='bar'></div>";
            html += "       </div>";
            html += "       <div class='box box3'>";
            html += "         <div class='bar'></div>";
            html += "       </div>";
            html += "     </div>";
            html += "  </div>";
            html += "  <div class='strength-check-info'>";
            html += "    <span>" + strengthWarming + "</span>";
            html += "  </div>";
            html += "</div>";
            return html;
        },
        startStrength: function() {
            var this1 = this;
            this.eles.on({
                "focus": function() {
                    this1.focusFun($(this), this1);
                },
                "focusout": function() {
                    var _this = $(this);
                    _this.attr("data-strength", "focusout");
                    _this.parents(".form-group-strength").find("#strength-check").remove();
                },
                "keyup": function() {
                    var result = $("#password-strength");
                    var _this = $(this);
                    $("#password-strength .bar-text").html(this1.checkStrength(result, _this));
                }
            });
        },
        focusFun: function(ele, this1) {
            ele.attr("data-strength", "focus").parents(".form-group-strength").append(this1.strengthHTML());
            $("#strength-check").fadeIn(300);
            var result = $("#password-strength");
            var _this = ele;
            $("#password-strength .bar-text").html(this1.checkStrength(result, _this));
        },
        show: function() {
            this.focusFun(this.eles, this);
        },
        checkStrength: function(result, _this) {
            var password = _this.val();
            // 初始化长度
            $("#strength-check .tips").removeClass("turngreen");
            var strength = 0;
            if (password.length == 0) {
                result.removeClass();
                return '';
            }

            //如果长度大于6
            if (password.length >= 6) {
                $("#strength-check .tips").eq(0).addClass("turngreen");
                strength += 1;
            }

            //如果开头是字母
            if (password.match(/^[A-Z][A-Za-z\d\W_]*$/)) {
                $("#strength-check .tips").eq(1).addClass("turngreen");
                strength += 1;
            }

            //如果含有小写字母、大写字母、数字、特殊符号的两种及以上
            if (password.match(/(?!^(\d+|[a-zA-Z]+|[~!@#$%^&*?]+)$)^[\w~!@#$%^&*?]{6,}$/)) {
                $("#strength-check .tips").eq(2).addClass("turngreen");
                strength += 1;
            }
            //下面分别是三个强度的判断
            // if(strength == 1) {
            //     result.removeClass();
            //     result.addClass('normal');
            //     return 'Normal';
            // }else if (strength == 2) {
            //     result.removeClass();
            //     result.addClass('medium');
            //     return 'Medium';
            // }
            /*匹配大写字母、小写字母、特殊字符、数字的三种组合*/
            var reg1 = /^(?![a-zA-Z]+$)(?![A-Z0-9]+$)(?![A-Z\W_!@#$%^&*`~()-+=]+$)(?![a-z0-9]+$)(?![a-z\W_!@#$%^&*`~()-+=]+$)(?![0-9\W_!@#$%^&*`~()-+=]+$)[a-zA-Z0-9\W_!@#$%^&*`~()-+=]{3,}$/;
            // 大写字母匹配
            var reg2 = /^[A-Z]+$/;
            // 小写字母匹配
            var reg3 = /^[a-z]+$/;
            // 数字匹配
            var reg4 = /^[\d]+$/;
            // 特殊字符匹配
            var reg5 = /^([\W_!@#$%^&*`~()-+=]+)$/;
            if (strength == 3 && password.length >= 16 && (reg1.test(password) || (reg2.test(password) && reg3.test(password) && reg4.test(password) && reg5.test(password)))) {
                result.removeClass().addClass('strong');
                return 'Strong';
            } else if (strength == 3 && password.length >= 10) {
                result.removeClass().addClass('medium');
                return 'Medium';
            } else if (strength == 3) {
                result.removeClass().addClass('normal');
                return 'Normal';
            }
        }

    };
    return new fn(obj);
}

new strengthPwd({}).startStrength();

/*
 作者：黄俊铭
 作用：不能复制黏贴密码
 */
$('input:password').bind("cut copy paste", function(e) {
    return false;
});


/*
	作者：龚舜华
	作用：footer自适应
	@param ele：：需要自适应底部的页面的内容容器；
	@param pb：1."pb"：说明是该元素只需要padding-bottoom；
	           2."minH"：说明是该元素只需要设置minHeight；
 */
function footerAdaption(ele, pb, addition) {
    var winH = $(window).height(),
        eleH = ele.height(),
        navH = $(".navbar").outerHeight(),
        footerH = $(".downbottom").outerHeight(),
        aver = (winH - navH - footerH) / 2,
        _addition = addition ? addition : 0;
    if (pb == "pb") {
        ele.css({
            paddingBottom: (aver * 2 - eleH) + _addition + "px"
        });
    } else if (pb == "minH") {
        ele.css({
            minHeight: winH - navH - footerH + "px"
        });
    } else {
        if (typeof pb == "number") {
            _addition = pb;
        }
        ele.css({
            paddingTop: (aver - eleH / 2) + _addition + "px",
            paddingBottom: (aver - eleH / 2) + _addition + "px"
        });
    }
}

// IOS&Android页面
footerAdaption($(".iosCon"), "pb", -170);
if ($(window).width() > 2100) {
    // IOS&Android页面
    footerAdaption($(".iosCon"), "pb", -250);
}

// FAQ页面
footerAdaption($(".FAQALL .article-list"), "pb", -250);
footerAdaption($(".FAQALL .faq-list"), "pb", -250);
// 404页面
footerAdaption($(".four"), "pb", -9);
// Notice页面
footerAdaption($("#notice_view .notice_view_bot"), "pb", -350);


/*------------------------------------------
 作者：曾友城
 作用：手机端顶部导航条右侧icon-bar作动画
------------------------------------------ */
$(".navbar-toggle").click(function() {
    var _this = $(this);
    $('.navbar').addClass('nav_srcoll');
    if ($(".navbar-collapse").hasClass("in")) {
        _this.removeClass("navbar-toggle-open");
    } else {
        _this.addClass("navbar-toggle-open");
        window.onscroll = function() {
            _this.removeClass("navbar-toggle-open");
            $(".navbar-collapse").removeClass("in");
        };
    }
});


/*------------------------------------------
 作者：黄俊铭
 作用：点击返回顶部
------------------------------------------ */
$(window).scroll(function() {
    var top = $(window).scrollTop();
    if (top > 1) {
        $('.backTop').show();
    } else {
        $('.backTop').hide();
    }
});
$('.backTop').click(function() {
    $("html, body").animate({
        scrollTop: 0
    }, {
        duration: 1000,
        easing: "swing"
    });
});

/*------------------------------------------
 作者：何咏贤
 作用：滚动时顶部导航条添加class
------------------------------------------ */
$(window).scroll(function() {
    var top = $(window).scrollTop();
    if (top > 1) {
        $('.navbar').addClass('nav_srcoll');
    } else {
        $('.navbar').removeClass('nav_srcoll');
    }
});


/*------------------------------------------
 作者：何咏贤
 作用：判断如果是交易平台跳进来不显示底部链接
------------------------------------------ */
if ($('.target-nav').hasClass('backtpTrade')) {
    $('.knowMore .list-unstyled').remove();
}


/*------------------------------------------
 作者：李春青
 作用：获取当前语言改变透明度
------------------------------------------ */
function dropdown_btn() {
    var language = getCookie("think_language")? getCookie("think_language"): "zh-tw";
    if (language == "zh-tw") {
        $("body").addClass("fan");
    } else if (language == 'en-us') {
        $("body").addClass("en");
    }
    $(".dropdown_img_icon[href = '?l="+language+"']").addClass("dropdown_img_active")
}
dropdown_btn();

/*------------------------------------------
 作者：何咏贤
 作用：初始化wow
------------------------------------------ */
if (!(/msie [6|7|8|9]/i.test(navigator.userAgent))) {
    new WOW().init();
}

/*------------------------------------------
 作者：李春青
 作用：中间内容高度自适应 footer贴底部
       需要用到的页面 写入class:'registerFlex'  动态添加flex_name
       registerFlex  中间content样式名 用于找到父级body
       flex_name     body样式名
------------------------------------------ */
if ($(window).width() <= 1030){
    $('.registerFlex').parent().addClass('flex_home');
}else {
    $('.registerFlex').parent().removeClass('flex_home');
}
/*
    作者:曾友城
    作用:获取语言的cookie
*/
function getCookie(name){
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)){
        return (arr[2]);
    }else{
        return null;
    }      
}