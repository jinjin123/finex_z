/* - - 定义属性  banner重力效果 - - */
var pageX = $(window).width();
var pageY = $(window).height();
var ImgX = $('#login_banner img').eq(3).offset().left;
var mouseY = 0;
var mouseX = ImgX + ImgX / 2;
var ticketViewHeight = $('#TickerView').height() * 2;
var height = $(window).height() - ticketViewHeight;
var padTop = 0;

/* - - 操作 - - */
$(document).ready(function() {
    // PC
    if ($(window).width() >= 768) {
        padTop = height / 2.7;
    }

    if ($(window).width() < 1024) { // ipad
        padTop = height / 1.8;
        // 交换币币交易的图片和文字位置
        var transLeft = $('.patterns .row:last-of-type .col-lg-6:first-of-type');
        var transRight = $('.patterns .row:last-of-type .col-lg-6:last-of-type');
        var transLeftText = transLeft.html();
        var transRightText = transRight.html();

        transLeft.html(transRightText).addClass('transLeft');
        transRight.html(transLeftText).removeClass('transLeft pull-right').addClass('imgbox');

        var oriText = $(".notice-board>div>a");
        if (oriText.text().length >= 24) {
            var cutText = oriText.text().substr(0, 24);
            oriText.text(cutText + '...');
        }
    }

    if ($(window).width() < 700) {
        padTop = height / 3;
        height = "auto";
    }

    //设置banner高度刚好一屏 减去行情滚动和公共的高度
    $('#login_banner').css({
        'height': height,
        'padding-top': padTop
    });


    // 地图动画
    bodymovin.loadAnimation({
        path: jsurl + '/bodymovin/map_bg.json', //json文件路径
        loop: false,
        autoplay: true,
        renderer: 'svg', //渲染方式，有"html"、"canvas"和"svg"三种
        container: document.querySelector('#map')
    });
    var PointAnimate = bodymovin.loadAnimation({
        path: jsurl + '/bodymovin/map.json', //json文件路径
        loop: true,
        autoplay: false,
        renderer: 'svg', //渲染方式，有"html"、"canvas"和"svg"三种
        container: document.querySelector('#point')
    });
    PointAnimate.setSpeed(1);
    setTimeout(function() {
        //背景隐藏
        $('#map').hide();
        //显示点
        $('#point').show();
        // 开启动画
        PointAnimate.play();
    }, 1800);




    // 重力效果
    $("#login_banner").on('mousemove', function(e) {
        anima(e, 2, 1.5, 0);
        anima(e, 3, 2.2, 1);

        anima(e, 2, 1.2, 2);
        anima(e, 1.5, 1.2, 4);

        anima(e, 1.5, 1.5, 5);
        anima(e, 2, 2.2, 6);
        anima(e, 1.5, 2.6, 7);

    });
});

window.onload = function(){
    // 执行滚动函数
    move();
}
/**
 * 行情滚动
 */
function move() {
    // 小于640获取div的margin值，大于640获取父级的padding值
    var ele_left = $(window).width() <= 640 ? parseInt($("#addTen").children().eq(0).offset().left * 2) : parseInt($(".TickerView_content_3z028").offset().left);
    // div的宽度
    var single_div_width = $("#addTen").children().eq(0).width();
    // div的数量
    var children_len = ($("#addTen").width() / single_div_width) / 2;
    // 取值一半
    var offsetLeft = (ele_left + single_div_width) * children_len;
    setInterval(function() {
            if (left >= offsetLeft) {
                left = 0;
                // move();
            }
            // timer = setTimeout("move()", 15);
            left++;
            $('#addTen').css({
                'transform': 'translate(' + (-left) + "px, 0px)"
            });
        }, 15)
        // clearTimeout(timer);


}

/**
 * 鼠标移动重力效果
 * @param e 对象
 * @param speed1 y轴的速度
 * @param speed2 x轴的速度
 * @param num 第几张图片
 * @author 曾友城
 */
function anima(e, speed1, speed2, num) {
    mouseY = e.pageY;
    mouseX = e.pageX;
    var yAxis = -((pageY / 2 - mouseY) / pageY) * speed1;
    var xAxis = (((pageX / 2 - mouseX) / pageX) * speed2);

    $('.banner_img>img').eq(num).css({ "transform": "translate(" + xAxis + "%," + yAxis + "%)" });
}