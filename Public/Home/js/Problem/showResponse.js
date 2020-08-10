/**
 * @function页面渲染时---客服回复转义
 *            转义字符设置为html后获取text
 *            创建一个虚拟div设置其html值在通过text获取就是转义好的标签值
 * @author  何咏贤
 */
function htmlDecode(value) {
    return $('<div/>').html(value).text();
}
$('.changhtml').each(function() {
    var _this = $(this);
    var html = _this.html();
    html = htmlDecode(html);
    _this.html(html);
});
//客服回復循環name
var problem_loopset;
$(document).ready(function() {

    /* 问题反馈追问实时轮询信息 */
    /showUnResponse/.test(window.location.href) ? problem_loop() : false;

});

$(function() {

    /**
     *  返回问题列表记录状态
     */
    $('.issues_back').click(function() {
        window.localStorage.setItem('problem_back', 1);
    });
    $('.leave_text').click(function() {
        $('.Questioning_wrap').addClass('Questioning_after').show();
        $('.leave_messages').hide();

    })

    // 上传图片
    $(".size_map").on('change', function() {
        var _this = $(this),
            size = _this.get(0).files[0],
            size_map = 3 * 1024 * 1024,
            _this_val = _this.val(),
            _fileName = _this_val.substring(_this_val.lastIndexOf(".") + 1).toLowerCase();
        if (size) {
            // 图片格式
            if (_fileName !== "png" && _fileName !== "jpg" && _fileName !== "jpeg" && _fileName !== "gif") {
                BottomalertBox("bottom", sizefalse, "fail", "center");
                _this.parents('span.btn-file').next().trigger('click');
                return;
            }
            // 图片大小
            if (size.size > size_map) {
                // 调用官方api的clear
                _this.$element = _this.parents(".fileinput");
                _this.$element.fileinput("clear");
                BottomalertBox("bottom", over2M, "fail", "center");

            }
            _this.parents('div.fileinput').next().fadeIn();
        }
    });

    /*问题列表 未解决 追问按钮显示输入框 点击发送追问内容*/

    $("body").on("click", ".Inquiries_button", function() {
        var Inquiries_textarea = $('textarea[name=describe]').val();
        var formData = new FormData();
        formData.append('image_one', document.getElementById("feedback_img1").files[0]);
        formData.append('image_two', document.getElementById("feedback_img2").files[0]);
        formData.append('image_three', document.getElementById("feedback_img3").files[0]);
        formData.append('id', id);
        formData.append('answer', Inquiries_textarea);
        if (Inquiries_textarea == '') {
            BottomalertBox("bottom", MSBNWK, "fail", "center");
            return;
        }
        $.ajax({
            cache: false, // 不缓存
            processData: false, // jQuery不要去处理发送的数据
            contentType: false,
            type: 'post',
            url: reTalkWithme,
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.status == 200) {
                    var name = $('.user_icon span').text();
                    var html = '';
                    html += '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bank_card List_problems showbink_select clearfix Customer_service_Reply">';
                    html += '<div class="pull-left col-lg-1 Customer_service_portrait">';
                    html += '<img src="' + homeImg + '/userpic.svg"/>';
                    html += '<p class="m-top-10">' + name + ' </p>';
                    html += '</div>';
                    html += '<div class="pull-left col-lg-10 bank_city text-left feedback_Inquiries pull-left p-left-25">';
                    html += '<p class="Customer_service_data p-bottom-5">' + data.time + '</p>';
                    html += '<p class="m-bottom-0">' + data.info.answer + '</p>';
                    if (data.info.img_list.length != 0) {
                        for (var index = 0; index < data.info.img_list.length; index++) {
                            html += '<div class="feedback_resolved_p image_mess">';
                            html += '' + messFJ + '' + (index + 1) + ': <a target="_blank" href="' + data.info.img_list[index].img_url + '">' + data.info.img_list[index].img_name + '</a>';
                            html += '</div>';
                        }
                    }

                    html += '</div>';

                    html += '</div>';
                    $('.Customer_service_Reply').last().after(html);
                    $('.leave_messages').hide();
                    $('.Questioning_wrap').hide().removeClass('Questioning_after');
                    // $('.fileinput').hide().eq(0).show();
                    // document.getElementById('feedback_form').reset();
                    BottomalertBox("bottom", data.msg, "success", "center");
                } else {
                    BottomalertBox("bottom", data.info, "fail", "center");
                }

            }
        });

    });
});


//循环未解決追問頁面
function problem_loop() {
    problem_loopset = setInterval(function() {
        $.ajax({
            url: '/Problem/getServiceReplyList',
            type: 'post',
            dataType: 'json',
            data: { feedback_ids: id },
            success: function(res) {
                var _Customer_service = $('.Customer_service_Reply');
                var list = res.data.answer[0].list;
                if (res.code == 200 && list != null && list.length > 0 && (_Customer_service.length - 1) != list.length) {
                    var html = '';
                    html += '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bank_card List_problems showbink_select clearfix Customer_service_Reply">';
                    html += '<div class="pull-right col-lg-1 Customer_service_portrait">';
                    html += '<img src="' + homeImg + '/btcspic.svg" />';
                    html += '<p class="m-top-10">' + list[list.length - 1].type + '</p>';
                    html += '</div>';
                    html += '<div class="pull-right col-lg-10 bank_city text-right Customer_service p-right-25">';
                    html += '<div class="customer_services_answer">';
                    html += '<p class="Customer_service_data">' + list[list.length - 1].add_time + '</p>';
                    html += '<div class="changhtml">' + list[list.length - 1].answer + '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    _Customer_service.last().after(html);
                }
            }
        });
    }, 10000);
}