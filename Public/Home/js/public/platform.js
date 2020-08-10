(function($, window) {
    "use strict";

    //FF下用JS实现自定义滚动条
    $(".aside .content-left").niceScroll({ cursorborder: "", cursorcolor: "rgba(0,0,0,0)", boxzoom: true });
    /*
      作者：何咏贤
      作用：初始化bootstrap的notify的插件
      参数：x是X轴，y是Y轴偏移值
    */
    $.notifyDefaults({
        offset: {
            y: 10,
            x: 20
        },
        onShow: function() {
            // Removes inline css that gives Bootstrap-notify plugin to the close button
            $(this).find('.close').removeAttr('style');
        }
    });


    /*
      作者：何咏贤
      作用：由上往下的实名认证前的弹窗
      参数：obj是元素，str是提示内容
    */
    function alertBox(str) {
        $.notify(str, {
            placement: {
                from: "bottom",
                align: "center"
            },
            offset: {
                y: 35
            },
            template: '<div data-notify="container" class="col-lg-4 col-md-6 col-sm-12 col-xs-12 p-0 before-certificate">' +
                '<div class="shiming alert alert-{0} border-{0} alert-extra alert-dismissible text-center"><button type="button" class="close" data-notify="dismiss">×</button>{2}</div>' +
                '</div>',
            z_index: 1080

        });
    }

    /*
      作者：何咏贤
      作用：由上往下的加分弹窗
      参数：str是提示内容,point是加的积分
    */
    function SuccessalertBox(str, point) {
        $.notify(str + "&nbsp;<img src='/Public/Home/img/jifen.png'> +" + point, {
            z_index: 1080,
            template: '<div data-notify="container" class="col-md-3 p-0">' +
                '<div class="alert alert-{2} border-{2} alert-success alert-dismissible text-center"><button type="button" class="close" data-notify="dismiss">×</button>{2}</div>' +
                '</div>'
        });
    }

    /*
      作者：何咏贤
      作用：由下往上的notify提示功能黑色弹窗
      参数：pos是位置,str是内容,ifsuccess是判断是否成功的样式，success是成功，fail是失败
    */
    function BottomalertBox(pos, str, ifsuccess, distance) {
        ifsuccess = ifsuccess ? "alert-info-" + ifsuccess : "";
        var rightWidth = $(".pbmin.lgNopadRight").width();
        var asideWidth = "",
            right_center_width = "";
        if (distance == "center" || $(document).width() <= 1024) {
            asideWidth = 0;
            right_center_width = 0;
        } else if (distance == "left") {
            asideWidth = $(".aside").width();
            right_center_width = (rightWidth / 2) - (rightWidth / 7.1);
        }
        $.notify(str, {
            template: '<div data-notify="container" class="col-md-3 col-xs-11 p-0 bieguan text-center">' +
                '<div class="alert alert-{0} border-{0} alert-bottom alert-dismissible ' + ifsuccess + ' text-center"><button type="button" class="close" data-notify="dismiss">×</button>{2}</div>' +
                '</div>',
            placement: {
                from: pos,
                align: distance
            },
            offset: {
                x: asideWidth + right_center_width,
                y: 35
            },
            z_index: 1080
        });
    }



    /*
      作者：何咏贤
      作用：切换皮肤
      参数：ColorPick是皮肤选择点击的元素
    */

    $('.ColorPick').on('click', function() {
        var locationHref = window.location.href;
        // var ThirdHref = locationHref.split("/")[locationHref.split("/").length - 1]; //获取三级页面文字
        // var SecondHref = locationHref.split("/")[locationHref.split("/").length - 2]; //获取二级页面文字
        var findID = $("link[data-depStyle=depStyle]").attr("id"); //获取当前页面link标签的id
        var $ColorPick_a = $('.ColorPick > a');
        var $ColorPick_ul_a = $('.ColorPick > ul > li a');
        var colorpick_title_one = $ColorPick_a.html();
        var colorpick_title_two = $ColorPick_ul_a.html();
        var type = $('.ColorPick_day').attr('type');
        var obj = $('.themStyle');
        var str = obj.attr('href');
        var Public_css, whatcolor, tvColor, LIindex, ParentSrc, whattext;
        var arr = [];
        str = str.substr(0, str.lastIndexOf('/'));
        $ColorPick_a.html(colorpick_title_two);
        $ColorPick_ul_a.html(colorpick_title_one);
        var day_svg_href = $('.ColorPick > a > svg >use').attr("xlink:href"),
            night_svg_href = $('.ColorPick > ul > li a>svg>use').attr("xlink:href");
        if (type == 1) {
            $('.ColorPick_day').attr('type', '0');
            obj.attr('href', str + "/Platform_purple.css");
            whatcolor = "#fff";
            tvColor = "light";
            whattext = "purple";
        } else if (type == 0) {
            $('.ColorPick_day').attr('type', '1');
            obj.attr('href', str + "/Platform_black.css");
            whatcolor = "#262A34";
            tvColor = "dark";
            whattext = "black";
        }
        ColorPick_getLocationHref(findID, whattext, locationHref)
        if ($("#kline_iframe")[0]) {
            // $("#kline_iframe")[0].contentWindow.Kfunc(whatcolor, $("#kline_iframe").contents().find("body #tv_chart_container")[0].id);
            var kline_Src = $("#kline_iframe").attr("src");
            $("#kline_iframe").attr("src", kline_Src);
        }
        Public_css = {
            "index": $('.ColorPick_day').attr('type'),
            "whatcolor": whatcolor,
            "tvColor": tvColor,
            "parentSrc": obj.attr('href'),
            "text": whattext,
            'day_svg_href': day_svg_href,
            'night_svg_href': night_svg_href
        };
        localStorage.setItem("tv_chart_css", JSON.stringify(Public_css));
    });

    /*
      作者：何咏贤
      作用：将3个弹窗定义为全局函数
      参数：无
    */
    window.alertBox = alertBox;
    window.BottomalertBox = BottomalertBox;
    window.SuccessalertBox = SuccessalertBox;

    /*
      作者：曾友城
      作用：主题缓存样式
    */
    //获取主题缓存
    var Public_css = JSON.parse(localStorage.getItem("tv_chart_css"));
    if (Public_css != null) {
        var locationHref = window.location.href;
        var findID = $("link[data-depStyle=depStyle]").attr("id"); //获取当前页面link标签的id
        //改变父窗口的主题样色 
        $('.themStyle').attr("data-them", Public_css.text).after('<link href="' + Public_css.parentSrc + '" rel="stylesheet" type="text/css" class="themStyle" data-them="' + Public_css.text + '">');
        //读取已切换的主题文字
        //获取ColorPick下的li的下标的文本
        $('.ColorPick > a>svg>use').attr("xlink:href", Public_css.day_svg_href);
        $('.ColorPick > ul > li a>svg>use').attr("xlink:href", Public_css.night_svg_href);


        $(".ColorPick .ColorPick_day").attr("type", Public_css.index);
        var _color_text;
        if (Public_css.index == 1) {
            _color_text = [$RJMS, $YJMS];
        } else {
            _color_text = [$YJMS, $RJMS];
        }
        $(".ColorPick > a >span").text(_color_text[0]);
        $('.ColorPick > ul > li a >span').text(_color_text[1]);
        ColorPick_getLocationHref(findID, Public_css.text, locationHref)
    }
})(jQuery, window);

/*
   作者：曾友城
   作用：主题切换时，对应的页面的样式也切换
   参数：颜色、二级页面路径、三级页面路径
 */
function ColorPick_getLocationHref(whicid, whicTheme, LastHref) {
    if (whicid) {
        var idEval = eval("/" + whicid + "/");
        if (idEval.test(LastHref)) {
            $("#" + whicid + "").attr("href", "/Public/Home/css/" + whicid + "_" + whicTheme + ".css");
        }
    }
}
/*
   作者：林婧瑜
   作用：积分详情tooltips引入
   参数：无
 */
$('[data-toggle="tooltip"]').tooltip();

/*
	作者：何咏贤
	作用：footer自适应
	@param ele：：需要自适应底部的页面的内容容器
 */

//钱包页面和财务流水右部分等于content容器的高度
var module1 = new Object({
    heightAuto: function(obj) {
        var minh = $(window).height() - $('.navbar').height() - $('.footer').height() * 2;
        $(obj).css('min-height', minh + 11);
    },
});

// 高度自适应
module1.heightAuto('.pbmin');

/*
  作者：龚舜华
	作用：输入的英文自动变大写
	@param ele：目标输入框元素；
 */
// 加强版：因为无法识别微软输入法的回车事件故而无法实现输入内容实时改变内容大小写；但当一开始使用微软中文输入法突然转换输入法便无法实现原本的实时大写，唯有失焦的时候才会统一转换为大写
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
upperInput($(".BigText"));
// 绑定资金密码
upperInput($("#token"));
// 修改密码口令
upperInput($("#pass_modify").find("*[name='token']"));
//更改密码口令
upperInput($("*[name='Trapass3']"));
// 账户页面的姓和名、令牌序列号和令牌秘钥
upperInput($("#inputName1:not([readonly])"));
upperInput($("#inputName2"));
upperInput($("#phone_modify #token1"));
upperInput($("#phone_modify #token2"));

/*
	作者：龚舜华
	作用：检测密码强度
	使用方法：为需要检验的表单元素(有value)
	@param obj  container：需要指定的容器；
				show："show"，使得密码强度提示框出现

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

            //如果含有字母、数字、特殊符号的两种及以上
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

/*--------------------------------------------------------------
*   author:龚舜华
*   用途  ：判断跳转页面特定的元素进行展开
*   参数  ：1.url：当前地址；
*           2.地址?后面的值
*           3.如果值符合则要执行的函数
---------------------------------------------------------------*/
function parseURL(url, name, fun) {
    var avg = url.split("?")[1];
    // 如果有指定标签传入跳它
    if (avg != "" && avg == name) {
        fun();
    }
}
//如果是从我的积分处绑定资金进入账户则资金密码打开并且显示绑定弹窗
parseURL(window.location.href, "costpwd", function() {
    if (isBindTradePwd == 0) {
        $("#account .panel:eq(1)>.panel-body").show();
        $('#Trapass_binding').modal("show");
    }
});
//如果是从我的积分处邮箱绑定进入账户则安全面板打开并且显示绑定弹窗
parseURL(window.location.href, "bindEmail", function() {
    if (isEmail == 0) {
        $("#account .panel:eq(1)>.panel-body").show();
        $('#Email_binding').modal("show");
    }
});

/*--------------------------------------------------------------
*   author:龚舜华
*   用途  ：点击下拉列表列表标题改为对应值
*   参数  ：1. list：dropdown元素
*           2. addition：除了获取的li的内容还要添加的其他内容
---------------------------------------------------------------*/
function changePdList(list, addition) {
    list.find(".dropdown-menu li").on("click", function() {
        list.find(".dropdown-toggle").html($(this).find("a").html() + addition);
    });
}
// 首页“正在交易”下拉
changePdList($(".trading li:eq(0)"), "<i class=\"fa fa-angle-down\"></i>");
// 绑定银行卡的国家下拉
changePdList($("#credit_card .nav-tabs li:eq(0)"), "&nbsp;&nbsp;&nbsp;<i class=\"fa fa-angle-down\"></i>");
// 左侧菜单币种选择的下拉
changePdList($(".coinSlide li:eq(0)"), "&nbsp;&nbsp;&nbsp;<i class=\"fa fa-angle-down\"></i>");
// 导航栏皮肤选择的下拉
changePdList($(".ColorPick"), "");
//积分查询
//平台交易收费
//增长积分
//升级VIP的好处
changePdList($(".upVipprofit li:eq(0)"), " <i class=\"fa fa-angle-down\"></i>");
//信用评级 平台交易收费选择币种下拉
changePdList($(".Plat-charg"), " <i class=\"fa fa-angle-down\"></i>");
//信用评级 积分详情选择时间下拉
changePdList($(".Integral_details"), " <i class=\"fa fa-angle-down\"></i>");



/*--------------------------------------------------------------
*   author:龚舜华
*   用途  ：ajax提交成功后延时刷新
*   参数： timer: 延时的时间，不给默认为2s

---------------------------------------------------------------*/
function loadLate(timer) {
    timer = timer || 2000;
    setTimeout(function() {
        window.location.reload();
    }, timer);
}

/*
   作者：龚舜华
   作用：遍历调试为空的输入元素
   参数：targetEles（必加）:目标元素集；
         fn（必加）:对空元素的处理；
         innerClass（必加）：需要读取html()的元素；
         brea（选加）：是否需要跳出循环(boolean)
   */
function testKong(targetEles, fn, innerClass, brea) {
    if (innerClass) {
        for (var i = 0; i < targetEles.length; i++) {
            if (targetEles.eq(i).hasClass(innerClass)) {
                if (targetEles.eq(i).html() == "") {
                    fn($(targetEles[i]));
                    if (brea) {
                        return false;
                    }
                }
            } else {
                if (targetEles.eq(i).val() == "") {
                    fn($(targetEles[i]));
                    if (brea) {
                        return false;
                    }
                }
            }
        }
        return true;
    } else {
        for (var k = 0; k < targetEles.length; k++) {
            if ($(targetEles[k]).val() == "") {
                fn($(targetEles[k]));
                if (brea) {
                    return false;
                }
            }
        }
        return true;
    }
}

/*
   作者：龚舜华
   作用：回车提交表单
   参数：enterEle:触发回车事件的元素；targetForm：触发提交表单事件的提交按钮
 */
function enterSub(enterEle, targetForm) {
    enterEle.on("keydown", function(e) {
        if (e.keyCode == 13) {
            targetForm.trigger("click");
        }
    });
}
// P2P卖出提交
enterSub($("#sellPwd"), $("#subSellInfo"));
// 确认收款
enterSub($("#confirmPayMoney").parents(".modal").find(".nofloat_auto"), $("#confirmPayMoney"));
// 订单详情（购物车）
enterSub($("#buySubInfo").parents(".modal"), $("#buySubInfo"));

//-------- 账户页面--------
// 更改密码
enterSub($("#confirmBtn").parents(".modal").find(".nofloat_auto"), $("#confirmBtn"));
// 绑定资金密码
enterSub($("#Trapass_bindingBtn").parents(".modal").find(".nofloat_auto"), $("#Trapass_bindingBtn"));
// 更改资金密码
enterSub($("#moneypwdBtn").parents(".modal").find(".nofloat_auto"), $("#moneypwdBtn"));
// 绑定邮箱
enterSub($("#addonLeftExample2"), $("#binding_email"));
// 更改邮箱
enterSub($("#saveEmail").parents(".modal").find(".nofloat_auto"), $("#saveEmail"));
// 解绑手机密令
enterSub($("#tokenUnBinding").parents(".modal").find(".nofloat_auto"), $("#tokenUnBinding"));

//-------- 钱包页面--------
// 绑定提币地址
enterSub($("#inputIconLeftExample11"), $("#bindBtn11"));
enterSub($("#inputIconLeftExample12"), $("#bindBtn12"));
enterSub($("#inputIconLeftExample13"), $("#bindBtn13"));
// 提币转出
enterSub($("#tibi11").parents(".modal").find(".nofloat_auto"), $("#tibi11"));
enterSub($("#tibi12").parents(".modal").find(".nofloat_auto"), $("#tibi12"));
enterSub($("#tibi13").parents(".modal").find(".nofloat_auto"), $("#tibi13"));

// -------- C2C页面 --------
// 卖出
enterSub($(".sellSub.data-block-sell").parents(".modal").find(".modal-body"), $(".sellSub.data-block-sell"));
// 买入
enterSub($(".buySub.data-block-buy ").parents(".modal").find(".modal-body"), $(".buySub.data-block-buy "));
// 订单发布
enterSub($(".chinaorder-buy ").parents(".modal").find(".buyBox"), $(".chinaorder-buy"));
enterSub($(".chinaorder-sell ").parents(".modal").find(".sellBox"), $(".chinaorder-sell"));

// -------- 币币页面 -------
enterSub($('#SaleIn_count_2'), $('.buyin'));
enterSub($('#SaleIn_count_1'), $('.buyin'));
enterSub($('#SaleOut_count_2'), $('.sellin'));
enterSub($('#SaleOut_count_1'), $('.sellin'));
enterSub($('#Confirmation-psd'), $('.sub-Con'));

//判断实名验证只有首页出现
var url = window.location.pathname;
var $isRealName = document.querySelector("#isRealName");
if (url.indexOf("UserCenter") > -1 || url.indexOf("CurrencyTrading") > -1 || url.indexOf("CtoCTransaction") > -1) {
    $isRealName ? $isRealName.style.display = "block" : false;
} else {
    $isRealName ? $isRealName.style.display = "none" : false;
}

//不能复制黏贴密码
$('input:password').bind("cut copy paste", function(e) {
    return false;
});

/*
   作者：龚舜华
   作用：手机横屏页面重新加载
 */
function orientate() {
    window.addEventListener("onorientationchange" in window ? "orientationchange" : "resize", function() {
        if (window.orientation === 180 || window.orientation === 0 || window.orientation === 90 || window.orientation === -90) {
            window.location.reload();
        }
    }, false);
}
orientate();

/*
   作者：龚舜华
   作用：账户页面小屏的时候导航跳转页面
 */
function mobileAccount() {
    $(".smshow-account .btn-group.bootstrap-select").on("click", function() {
        $(".smshow-account .selectpicker").on("change", function() {
            var i = $(this).val();
            window.location.href = $('.personalCenter_nav li').eq(i).find('a').attr('href');
        });
    });
}
mobileAccount();

/*
   作者：龚舜华
   作用：关于对应dropdown-menu的切换
 */
function toggleDropMenu(obj) {
    obj.eles.find(".dropdown-menu li").on("click", function() {
        var _this = $(this);
        var thisText = obj.child ? _this.find(obj.child).html() : _this.html();
        _this.parents(".dropdown").find('.dropdown-toggle').html(thisText + obj.addStr + "<i class=\"fa fa-angle-down\"></i>");
    });
}
toggleDropMenu({
    eles: $(".integral-details .tools-content"),
    child: "a",
    addStr: "  "
});
toggleDropMenu({
    eles: $("#platCharge .tools-content"),
    child: "a",
    addStr: "  "
});

///*
// 作者：龚舜华
// 作用：模态框module拖拽功能实现
// 参数：obj
// 		->targetEle：目标模态框，注意是数组类型；
// 		->controlEle：模态框中控制拖动的部分
// */
var clearSlct = "getSelection" in window ?
    function() {
        window.getSelection().removeAllRanges();
    } :
    function() {
        document.selection.empty();
    };

function dragModal(obj) {
    function addTouch() {
        if ($(window).width() <= 640) {
            //在此处可以为手机移动端添加触屏事件，项目只有PC端需要拖拽所以没有添加
        }
    }
    var controlEle = obj.controlEle ? " " + obj.controlEle : "",
        targetEle = obj.targetEle;
    var dragEvent = function(ele) {
        return function(e) {
            // 获取起始位置的坐标值与初始的margin值
            var x0 = e.clientX,
                y0 = e.clientY,
                marginL = parseFloat($(ele).css("marginLeft")),
                marginT = parseFloat($(ele).css("marginTop"));
            $("body").on("mousemove", function(e) {
                // 取消选中文字
                clearSlct();
                var x = e.clientX,
                    y = e.clientY,
                    // 最大和最小的marginLeft和marginTop值
                    _window_width = $(window).width(),
                    _window_height = $(window).height(),
                    maxLeft = _window_width / 2 - $(ele).width(),
                    minLeft = -_window_width / 2,
                    maxTop = _window_height / 2 - $(ele).height(),
                    minTop = -_window_height / 2,
                    // x和y的移动坐标差
                    xdiffer = x - x0,
                    ydiffer = y - y0,
                    // marginLeft值
                    leftNum = marginL + xdiffer,
                    // marginTop值
                    topNum = marginT + ydiffer;

                leftNum = leftNum >= maxLeft ? maxLeft : leftNum;
                leftNum = leftNum <= minLeft ? minLeft : leftNum;
                topNum = topNum >= maxTop ? maxTop : topNum;
                topNum = topNum <= minTop ? minTop : topNum;
                $(ele).css({
                    "marginLeft": leftNum,
                    "marginTop": topNum
                });
            });
            $("body").on("mouseup", function() {
                $("body").off("mousemove");
            });
        };
    };
    if (targetEle.length > 1) {
        $.each(targetEle, function(index, ele) {
            $(ele).attr("data-drag", "true");
            $(ele + controlEle).on("mousedown", dragEvent(ele));
        });
    } else if (targetEle.length == 1) {
        $(targetEle + controlEle).on("mousedown", dragEvent(targetEle));
    }
}

dragModal({
    targetEle: ['#order-issue', '#buy-issue', '#sell-issue'],
    controlEle: ".modal-title"
});

/*
 *	价格取2位,数量取8位
 *	@author 何咏贤
 *	@param str要取值的元素,i要取多少位+1,return 返回值
 */

function ChangeFixed(str, i) {
    var target_val1 = Number(str).toFixed(6);
    var target_val2 = target_val1.substring(0, target_val1.lastIndexOf('.') + i);
    return target_val2;
}

/**
 * 实时正则检验——不能输入数字以外的字符且保留v位小数
 * @author 何咏贤
 * @param e 为要取值的元素
 * @param v 为1代表保留2位小数，为2代表保留4位小数，为3代表保留3位小数,4代表保留8位小数
 */
function validationNumber(e, v) {
    // var replace_rule = {
    //     '1':[/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3'], //只能输入2个小数价格
    //     '2':[/^(\-)*(\d+)\.(\d\d\d\d).*$/, '$1$2.$3'], //只能输入4个小数数量
    //     '3':[/^(\-)*(\d+)\.(\d\d\d).*$/, '$1$2.$3'], //只能输入3个小数数量
    //     '4':[/^(\-)*(\d+)\.(\d\d\d\d\d\d\d\d).*$/, '$1$2.$3'] //只能输入8个小数数量
    // }
    e.val(e.val().replace(/[^\d.]/g, "")); //清除“数字”和“.”以外的字符
    e.val(e.val().replace(/^\./g, "")); //验证第一个字符是数字而不是.
    e.val(e.val().replace(/\.{2,}/g, ".")); //只保留第一个. 清除多余的.
    e.val(e.val().replace(".", "$#$").replace(/\./g, "").replace("$#$", ".")); //只保留第一个. 清除多余的.
    // e.val(e.val().replace(replace_rule[v][0]));
    switch (v) {
        case 1:
            e.val(e.val().replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3')); //只能输入2个小数价格
            break;
        case 2:
            e.val(e.val().replace(/^(\-)*(\d+)\.(\d\d\d\d).*$/, '$1$2.$3')); //只能输入4个小数数量
            break;
        case 3:
            e.val(e.val().replace(/^(\-)*(\d+)\.(\d\d\d).*$/, '$1$2.$3')); //只能输入3个小数数量
            break;
        case 4:
            e.val(e.val().replace(/^(\-)*(\d+)\.(\d\d\d\d\d\d\d\d).*$/, '$1$2.$3')); //只能输入8个小数数量
            break;
    }
}


/**
 * 判断是否新手第一次进入平台来自动显示新手流程
 * @author 龚舜华 2017-12-020T21:04:01+0800
 */
function needTour(extra) {
    /*
     * 创建新手流程的对象
     * @author 2017-12-20T21:04:01+0800
     * @param  {[type]} order_id [description]
     * */
    function introduction() {
        return introJs().setOptions({
            nextLabel: nextStep + ' &rarr;',
            prevLabel: '&larr; ' + beforeStep,
            skipLabel: skipStep,
            doneLabel: finishStep,
            exitOnOverlayClick: false,
            exitOnEsc: false,
            showBullets: false,
            hidePrev: true,
            hideNext: true,
            showStepNumbers: false,
            scrollToElement: true,
            scrollTo: "tooltip"
        }).start().oncomplete(function() {});
    }

    /*
     * 新手流程点击后的第一步骤操作
     * @author 2018-4-19T10:37:01+0800
     * */
    function introChanging(extra) {
        // 第一步：币种侧边栏出现
        setTimeout(function() {
            // 判断auto是为了兼容ie
            if ($(".coinSlide").css("left") == "0px" || $(".coinSlide").css("left") == "auto") {
                // 创建流程的对象
                var s = introduction();

                //给跳过按钮绑定跳过事件
                skipEvent();
                // 点击第一步的下一步按钮
                $(".coinMoneyListIntro .introjs-nextbutton").on("click", function() {
                    // 币种侧边栏回缩
                    s.exit();

                    setTimeout(function() {
                        // 牌价侧边栏出现
                        timer = 0;
                        setTimeout(function() {
                            // 等待牌价侧边栏出现后从第二步流程开始
                            var s = introduction();
                            s.goToStep(1).start();
                            $(".coinSlide").css("zIndex", "1032");
                            //具体操作
                            s.onbeforechange(function(targetElement) {
                                // 移动端跳到第五步或者跳回第一步的时候将会收起牌价侧边栏
                                // if (Ww <= 1024 && ($(targetElement).attr("data-step") == 4) || ($(targetElement).attr("data-step") == 1)) {
                                //     $('.left-logo').css('position', 'fixed');
                                //     $(".aside").css("left", "0px");
                                //     $('.left-logo.scroll-on').css('position', 'fixed');
                                // } else if (Ww <= 1024 && ($(targetElement).attr("data-step") == 5)) {
                                //     $(".aside").css("left", "-355px");
                                //     $('.left-logo').css('position', 'static');
                                //     $('.left-logo.scroll-on').css('position', 'static');
                                // }
                                if ($(targetElement).attr("data-step") == 4) {
                                    window.scrollTo(0, 0)
                                }
                            }).onafterchange(function(targetElement) {
                                extra ? afterChange(s, targetElement, extra) : afterChange(s, targetElement);
                            });
                            //跳过按钮
                            skipEvent();
                        }, timer);
                    }, 700);
                });
            }
        }, 700);
    }

    /*
     * 流程移动端的onafterchange执行事件
     * @author 2017-12-25T21:04:01+0800
     * @param  {[type]} s: 创建流程的对象；
     * 				targetElement： 当前步骤的目标对象；
     * 				extra： 需要判断的步骤:根据需要判断的步骤添加要执行的内容
     * */
    function afterChange(s, targetElement, extra) {
        //返回第一步
        if ($(targetElement).attr("data-step") == 1) {
            s.exit();
            //重新开始流程
            introChanging();
        }
        //屏幕大于1024时，第5-7步骤用绝对定位
        var data_step = $(targetElement).attr("data-step");
        if (data_step == 5 || data_step == 6 || data_step == 7 || data_step == 8 || data_step == 4) {
            $(".introjs-fixedTooltip").css("position", "absolute");
        }
    }

    /*
     * 新手流程点击跳过或者完成的时候收起侧边栏
     * @author 2018-1-5T11:04:01+0800
     * @param  fn 点击跳过或者完成按钮的时候还需要执行的函数，这里指getIfTour
     * */
    function closeMobilePanel(fn) {
        // 移动端时点击跳过或者完成都会收起牌价侧边栏
        $(".introjs-skipbutton").on("click", function() {
            // if (Ww <= 1024) {
            //     $(".aside").css("left", "-355px");
            //     $('.left-logo').css('position', 'static');
            // }
            if (fn) {
                fn();
            }
        });
    }

    /*
     * 给新手流程中跳过按钮添加事件
     * @author 2018-1-5T11:04:01+0800
     * @param  fn 点击跳过或者完成按钮的时候还需要执行的函数，这里指getIfTour
     * */
    function skipEvent() {
        // 按了跳过
        if (isTour == 0) {
            closeMobilePanel(getIfTour);
        } else {
            closeMobilePanel();
        }
    }

    var timer = 0;
    var Ww = $(window).width();
    var Wh = $(window).height();
    // 如果是新手
    if (isTour == 0) {
        $(".aside .content-left").getNiceScroll(0).doScrollTop(0);
        extra ? introChanging(extra) : introChanging();
    }
    $(".tour").unbind("click");
    $('.tour').click(function() {
        $(".aside .content-left").getNiceScroll(0).doScrollTop(0);
        extra ? introChanging(extra) : introChanging();
    });

    /*
     * 判断当前的交易模式并返回后台
     * @author 2017-12-20T21:04:01+0800
     * @param  {[type]} order_id [description]
     * */
    function getIfTour() {
        var tourType;
        var changeAs = ($(window).width() <= 640) ? $(".sm_changModel") : $(".changeModel");
        changeAs.find(".dropdown-menu a").each(function(index, item) {
            if (changeAs.find(".dropdown-toggle").text().trim() == $(item).text()) {
                if (index == 0) {
                    tourType = 2;
                } else if (index == 1) {
                    tourType = 1;
                } else if (index == 2) {
                    tourType = 3;
                }
                $('.TourBtn_state').hide();
            }
        });
        check_user('/CheckUserInfo/CheckUserIsTour', {
            'tourType': tourType
        }, 'post').then(function() {});
    }
}

/**
 * --------- 确认收款 付款 按钮---------
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

/*===================================
 *侧边栏弹出
 *author:贤
 * ===================================*/
var sideStr = { left: { num: 0 }, more: { num: 0 } };
$('body').on('click', '.sidebar-left', function() {
    var width = parseInt($('.aside').css('left'));
    if (width < 0) {
        $('.left-logo').addClass('scroll-on');
        $('.aside').css('left', '0');
        sideStr.left.num = 1;
    } else {
        $('.left-logo').removeClass('scroll-on');
        $('.aside').css('left', '-345px');
        sideStr.left.num = 0;
    }
    if (sideStr.more.num == 1) {
        sideStr.more.num = 0;
        $('.navbar').css('top', '0');
    }

});
$('body').on('click', '.sidebar-more', function() {
    var height = parseInt($('.navbar').css('top'));
    if (height <= 0) {
        $('.navbar').css('top', '54px');
        sideStr.more.num = 1;
    } else {
        $('.navbar').css('top', '0');
        sideStr.more.num = 0;
    }
    if (sideStr.left.num == 1) {
        sideStr.left.num = 0;
        $('.aside').css('left', '-345px');
    }
});
// 手机的时候 点击侧边栏div外的任意地方，收起侧边栏
if ($(document).width() < 640) {
    $(document).click(function(e) {
        if ($('.aside').position().left == 0) {
            var startX = e.clientX
            if (startX > $('.aside').width()) {
                $('.left-logo').removeClass('scroll-on');
                $('.aside').css('left', '-345px');
                sideStr.left.num = 0;
            }
        }
        if ($('.navbar').position().top > 0) {
            var startY = e.clientY;
            var navbar_offsetTop = $('.navbar').position().top + $('.navbar').height()
            if (startY > navbar_offsetTop) {
                $('.navbar').css('top', '0');
                sideStr.more.num = 0;
            }
        }
    })
}

/*币币交易 侧边栏实时价格 最高最低价截取8位*/
function target_Intercept_number(trad_coin_number, type) {
    if (type == 1) {
        if (trad_coin_number.indexOf(".") != -1) {
            if (trad_coin_number.length > 9) {
                return trad_coin_number.slice(0, 9);
            } else {
                for (i = trad_coin_number.length; i < 9; i++) {

                    trad_coin_number = trad_coin_number + '0';
                }
                return trad_coin_number;
            }
        } else {

            var wong_num = trad_coin_number + '.';
            for (i = wong_num.length; i < 9; i++) {

                wong_num = wong_num + '0';
            }
            return wong_num;
        }
    } else {
        return trad_coin_number;
    }
}




/**================================================================
 *作者：何咏贤
 *作用：手机端去除公共、FAQ、tour
 *参数：无
 =====================================================================*/

var windowWidth = $(window).width();
if (windowWidth <= 1024) {
    $('#TourBtn').remove();
}
if (windowWidth <= 640) {
    $('.Notice_li').remove();
    $('.Faq_li').remove();
}

/**================================================================
 *作者：黄俊铭
 *作用：target维护
 *参数：type:对应模式的php变量 msg:提示内容
 =====================================================================*/

function Maintain(type, msg1, msg2) {
    if (masterSwitch == 1) {
        if (msg2 != '') {
            BottomalertBox('bottom', msg2, "fail", "center");
        }
        return true;
    }
    if (type == 1) {
        if (msg1 != '') {
            BottomalertBox('bottom', msg1, "fail", "center");
        }
        return true;
    }
    return false;
}


/**================================================================
 *作者：何咏贤
 *作用：发送手机验证码倒计时
 *参数：倒计时元素，倒计时元素的文本
 =====================================================================*/
var wait = 120;

function setTimeSed(obj, sendText) {
    if (wait == 0) {
        obj.removeAttr("disabled").html(sendText);
        wait = 120;
    } else {
        obj.attr("disabled", true).html(' ' + wait + 's');
        wait--;
        var countdownInterval = setTimeout(function() {
            setTimeSed(obj, sendText);
        }, 1000);
    }
}


/**================================================================
 *作者：黄俊铭
 *作用：公共ajax  参数1 接口 2.数据data
 *参数：1 接口 2.数据data 3 接口类型  3个必填(空数据写空对象)
 =====================================================================*/
/**
 * 公共ajax  参数1 接口 2.数据data
 * @author 2018-2-27T10:19:53+0800
 * @param  resolve成功  reject失败
 * @return 
 */
function check_user(url_dz, data, type) {
    return new Promise(function(resolve) {
        $.ajax({
            url: url_dz,
            data: data,
            type: type,
            datatype: 'json',
            success: function(res) {
                resolve(res);
            }
        });
    });
}

/**================================================================
 *  清空币种localStorage和刷新页面
 *  @author 何咏贤
 *  @param type "P2P" , "C2C" , "D2D"
 =====================================================================*/
function clear_data(type) {
    var refreshCurrency = JSON.parse(localStorage.getItem("RefreshCurrency"));
    switch (type) {
        case "P2P":
            delete refreshCurrency[0];
            break;
        case "C2C":
            delete refreshCurrency[1];
            break;
        case "D2D":
            localStorage.removeItem("BtoB_ID");
            break;
    }
    localStorage.setItem("RefreshCurrency", JSON.stringify(refreshCurrency));
    window.location.reload();
}

/**================================================================
 *  websocket 币种下架 刷新
 *  @author 黄俊铭
 *  @param  type  obj：匹配对象 Lower_id：需要匹配得key  Lowerbusiness：当前交易模式
 =====================================================================*/
function coin_Lowershelf(obj) {
    var Lowerbusiness = $(".changeModel .navbar-nav .dropdown .dropdown-toggle").text().trim().substring(0, 3);
    var Lower_id = $("#coinMoneyList").find("li.active").attr('currency-id');
    obj.hasOwnProperty(Lower_id) ? true : clear_data(Lowerbusiness);
}


/**================================================================
 *  websocket 语言切换 获取当前语言改变透明度
 *  選中透明度為1
 *  @author 李春青
 *  @param  type  obj：匹配对象
 =====================================================================*/
dropdown_btn();

function dropdown_btn() {
    var language = getCookie("think_language") ? getCookie("think_language") : "zh-tw";
    if (language == "zh-tw") {
        $("body").addClass("fan");
    } else if (language == 'en-us') {
        $("body").addClass("en");
    }
    $(".dropdown_img_icon[href = '?l=" + language + "']").addClass("dropdown_img_active")
}

/**================================================================
 *  获取语言的cookie的函数
 *  @author 曾友城
 =====================================================================*/
function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg)) {
        return (arr[2]);
    } else {
        return null;
    }
}

/**================================================================
 *  更新图片验证码
 *  @author 何咏贤
 =====================================================================*/
function fresh_Imgcode() {
    $('.verifyImg').attr('src', '/CheckVerify/getVerify?' + Math.random());
}

/**================================================================
 *  模态框清空val值
 *  @author 黄俊铭
 =====================================================================*/
$('body').on('hidden.bs.modal', '.modal', function() {
    var _this = $(this);
    var _thisID = _this.attr('id');
    //判断不是p2p搜索框  账户更换邮箱清除第二个 否则清除全部val值
    _thisID != 'search' ? _thisID == 'Email_modify' ? _this.find('#email2').val('') : _this.find('input').val('') : false;
    // 带有图片验证码关闭的时候刷新一次验证码
    if (_thisID == "phone_modify" || _thisID == "takecoin") fresh_Imgcode();
});