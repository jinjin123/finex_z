

// Swiper实例
var num = 0;
var mySwiper = new Swiper('.swiper-container', {
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
        hideOnClick: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    }
})

//点击下一页
$(".swiper-button-next").click(function() {
        num++;
        //箭头偏移量
        $("i.fa-angle-right").css({
            "left": 12.3 + (num * 25) + "%"
        });
        //步骤的进度，dropdown为其他进度，goUp为当前进度
        var _$progress_wrap = $(".The-progress .progress-wrap");
        _$progress_wrap.eq(num - 1).children(".progress_number").addClass("dropdown-numberWrap").removeClass("goUp-numberWrap");
        _$progress_wrap.eq(num).children(".progress_number").addClass("goUp-numberWrap").removeClass("dropdown-numberWrap");
        //如果为当前进度背景图片和数字变颜色
        _$progress_wrap.eq(num).children(".progress_icon-wrap").addClass("bg-icon-sel");
        _$progress_wrap.eq(num - 1).children(".progress_icon-wrap").removeClass("bg-icon-sel");
        //当前进度利用fadeIn效果，其他为fadeOut
        $(".OneRight").children(".StepWrap").eq(num-1).fadeOut(200);
        $(".OneRight").children(".StepWrap").eq(num).fadeIn(300);
        $("#step" + num + "").html("");
        bodymovin1(num + 1, true, true);//bodymovin进入下一个步骤
    })
    //点击上一页
$(".swiper-button-prev").click(function() {
    num--;
    //箭头偏移量
    $("i.fa-angle-right").css({
            "left": 12.3 + (num * 25) + "%"
        })
        //步骤的进度，dropdown为其他进度，goUp为当前进度
    var _$progress_wrap = $(".The-progress .progress-wrap");
    _$progress_wrap.eq(num).children(".progress_number").addClass("goUp-numberWrap").removeClass("dropdown-numberWrap")
    _$progress_wrap.eq(num + 1).children(".progress_number").removeClass("goUp-numberWrap").addClass("dropdown-numberWrap")
        //如果为当前进度背景图片和数字变颜色
    _$progress_wrap.eq(num).children(".progress_icon-wrap").addClass("bg-icon-sel")
    _$progress_wrap.eq(num + 1).children(".progress_icon-wrap").removeClass("bg-icon-sel")
        //当前进度利用fadeIn效果，其他为fadeOut
    $(".OneRight").children(".StepWrap").eq(num+1).fadeOut(200);
    $(".OneRight").children(".StepWrap").eq(num).fadeIn(300);
    $("#step" + (num + 2) + "").html("");
    bodymovin1(num + 1, true, true);//bodymovin进入上一个步骤

})
// 手机端触屏滑动步骤时的判断函数----为了判断是否为上一页或者下一页
function GetSlideAngle(dx, dy) {
  return Math.atan2(dy, dx) * 180 / Math.PI;
}

//根据起点和终点返回方向 1：向上，2：向下，3：向左，4：向右,0：未滑动
function GetSlideDirection(startX, startY, endX, endY) {
    var dy = startY - endY;
    var dx = endX - startX;
    var result = 0;

    //如果滑动距离太短
    if(Math.abs(dx) < 2 && Math.abs(dy) < 2) {
        return result;
    }

    var angle = GetSlideAngle(dx, dy);
    if(angle >= -45 && angle < 45) {
        result = 4;
    }else if (angle >= 45 && angle < 135) {
        result = 1;
    }else if (angle >= -135 && angle < -45) {
        result = 2;
    }
    else if ((angle >= 135 && angle <= 180) || (angle >= -180 && angle < -135)) {
        result = 3;
    }

    return result;
}

//滑动处理
var startX, startY;
var divswiper = document.getElementsByClassName("divswiper")[0];
divswiper.addEventListener('touchstart',function (ev) {
    startX = ev.touches[0].pageX;
    startY = ev.touches[0].pageY;  
}, false);
divswiper.addEventListener('touchend',function (ev) {
    var endX, endY;
    endX = ev.changedTouches[0].pageX;
    endY = ev.changedTouches[0].pageY;
    var direction = GetSlideDirection(startX, startY, endX, endY);
    switch(direction) {
        case 3:
        // 表示下一页
        if (num >= 3) {
          return;
        }
        num++;
        mySwiper.slideNext();
        $("#step" + num + "").html("");
        bodymovin1(num + 1, true, true);
        $(".OneRight").children(".StepWrap").eq(num-1).fadeOut(200);
        $(".OneRight").children(".StepWrap").eq(num).fadeIn(300);
            break;
        case 4:
        // 表示上一页
        if (num <= 0) {
          return;
        }
        num--;                 
        mySwiper.slidePrev();
        $("#step" + num+1 + "").html("");
        bodymovin1(num + 1, true, true);
        $(".OneRight").children(".StepWrap").eq(num+1).fadeOut(200);
        $(".OneRight").children(".StepWrap").eq(num).fadeIn(300);
            break;
        default:           
    }
}, false);