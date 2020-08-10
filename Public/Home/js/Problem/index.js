$(document).ready(function() {
    // 自适应
    if ($(window).width() <= 768) {
        var cl12 = $('.feedback_box .col-lg-2').html();
        var cl15 = $('.feedback_box .the-describe-bottom').html();
        var cl15_2 = $('.feedback_box .provided_data_box').html();

        $('.feedback_box').html("<div class='col-lg-2 showbink_select'>" + cl12 + "</div><div class='col-lg-5 provided_data_box'>" + cl15_2 + "</div><div class='col-lg-5 showbink_select the-describe-bottom'>" + cl15 + "</div>");
    }

    /* - - - - - - - - - - - - - - - - - - - -
    填充区号
    * - - - - - - - - - - - - - - - - - - - - */
    $("#inputName1").intlTelInput();
    var countryList = $("#inputName1").intlTelInput.getCountryData();
    var country = '';
    $.each(countryList, function(index, item) {
        if (item.dialCode == userOm.substr(1)) {
            country = item.iso2;
        }
    });
    $("#inputName1").intlTelInput("selectCountry", country);


});

$(function() {

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

    /*
    问题反馈  问题类型二级联动
    * */
    $('body').on('click', '#type_problem_1 li', function() {
        var _this = $(this);
        var val = _this.attr('value');
        $('.Question_type_1 ').find('p').html('<span>' + _this.find("span").text() + '</span> <i class=\"fa fa-angle-down pull-right\"></i>');
        $('#type_problem_1 li').removeClass('active');
        _this.addClass('active');
        feedback_problem(val);
        $('.provided_data_box').hide();
        $('input[name="v_title"]').val('');
        $('input[name="m_title"]').val(val);

    });

    $('body').on('click', '#type_problem_2 li', function() {
        var _this = $(this);
        $('#type_problem_2').parent().find('p').html('<span>' + _this.find("span").html() + '</span> <i class=\"fa fa-angle-down pull-right\"></i>');
        $('#type_problem_2 li').removeClass('active');
        _this.addClass('active');
        $(".pro_orderNumber").hide();

        // 获取type2的值
        var val = _this.attr('value');
        $('input[name="v_title"]').val(val);

        if (val == -1) {
            $('.provided_data_box').hide();
            return;
        }

        // 如果加p2p或者c2c顯示訂單號碼
        if (val == 27 || val == 28) {
            $(".pro_orderNumber").show();
        }

        //根据不同的二级选择显示不同的提示
        $.ajax({
            type: 'POST',
            url: ajaxGetProblemDetialById,
            data: {
                'id': val
            },
            dataType: 'json',
            success: function(res) {
                res.status == 200 && res.info != '' ? (cutShowStr(res.info), $('.provided_data_box').show()) : $('.provided_data_box').hide();
            }

        });

    });

    /*
 问题反馈  点击发送 form表单数据
 * */
    $('#btn_Psubmit').click(function() {
        // 设置区号
        var _this = $(this);
        _this.attr("disabled", true);
        var code = $("#inputName1").intlTelInput("getSelectedCountryData");
        $('.telCode').val('+' + code.dialCode);
        $('#feedback_form').ajaxSubmit({
            type: "POST",
            url: "/Problem/sub_Problem",
            contentType: false,
            processData: false,
            success: function(data) {
                if (data.status == 200) {
                    BottomalertBox("bottom", data.info, "success", "center");
                    // loadLate();
                    setTimeout(function() {
                        window.location.href = "/Problem/showUnResponse/id/" + data.data.id + ".html";
                    }, 1000)

                } else {
                    BottomalertBox("bottom", data.info, "fail", "center");
                }
                _this.removeAttr("disabled");
            }
        });
    });
});




//3、根据类型获取问题名字
function feedback_problem(country) {
    var type_problem_2_content = '<li value="-1" class="active"><span>' + first_TIPS + '</span></li>';
    var $type_problem_2 = $('#type_problem_2');
    $type_problem_2.prev().html('<span>' + first_TIPS + '</span><i class=\"fa fa-angle-down pull-right\"></i>');
    if (country == -1) {
        $type_problem_2.html(type_problem_2_content);
    } else {

        $.ajax({
            cache: true,
            type: 'get',
            url: ajaxGetProlblemTitle,
            data: {
                'id': country
            },
            dataType: 'json',
            success: function(data) {
                if (data.status == 200) {
                    var list = "";
                    for (i = 0; i < data.info.length; i++) {
                        list += "<li value='" + data.info[i].id + "'><span>" + data.info[i].title + "</span></li>";
                    }
                    $type_problem_2.html(type_problem_2_content + list);
                }
            }
        });
    }
}

/**
 *  @function   分割数组并循环输出
 *  @param      str
 *               被分割的字符串
 */
function cutShowStr(str) {
    var strArr = str.split(' | ');
    var html = "";
    $.each(strArr, function(index, item) {
        item = item.split('.')[1] ? item.split('.')[1] : item.split('.');
        html += "<li>" + item + "</li>";
    });
    $('.provided_data .provided_list').html(html);
}