var problem_loopset;

/**
 * @function页面渲染时---客服回复转义
 *            转义字符设置为html后获取text
 *            创建一个虚拟div设置其html值在通过text获取就是转义好的标签值
 * @author  何咏贤
 */
function htmlDecode(value) {
    return $('<div/>').html(value).text();
}
for (var i = 0; i < $('.feedback_kefu_con').length; i++) {
    var html = $('.feedback_kefu_con').eq(i).html();
    html = htmlDecode(html);
    $('.feedback_kefu_con').eq(i).html(html);
}
$(document).ready(function() {

    /**
     * @function 获取localstorage中的状态值
     *            判断是否从对话页跳过来
     *            是的话显示已解决tab
     * @author   何咏贤
     */
    var back_status = window.localStorage.getItem('problem_back');
    if (back_status == 1) {
        $('.feedback_lg_buse').eq(0).click();
        window.localStorage.setItem('problem_back', 0);
    }
    answer_loop(pagation_active_type, 1);
    var _target_tab_type,
        pagation_active_type;
    var answer_loopval = setInterval(function() {
        _target_tab_type = $('.target_tab_control.active').attr('feedback_control');
        pagation_active_type = $('.dataPage .pagation-active .current-page').text();
        _target_tab_type == 1 ? answer_loop(pagation_active_type, 1) : false;
    }, 10000)


});

$(function() {
    /**
     * Tab切换
     */
    $('.feedback_lg_buse').click(function() {
        var _this = $(this);
        var index = _this.attr("feedback_control");
        $(".feedback_lg_buse").removeClass("active");
        _this.addClass("active");
        // 2 已解决 1 未解决
        getProblemList(1, index, 1);
    });

});



/**
 * 获取问题反馈列表
 * @param {页码} p 
 * @param {未解决，已解决} type 
 */
function getProblemList(p, type) {
    $.ajax({
        type: "get",
        url: ProblemList,
        data: { type: type, p: p },
        dataType: "json",
        success: function(res) {
            if (res.list != null && res.list.length > 0) {
                problem_list_false(res, type);
            } else {
                problem_list_true(type);
            }
        }
    });
}

/**
 * 默認沒有問題的時候顯示圖片公共函數
 */
function problem_list_true(type) {
    var html = "";
    html += '<div class="Unsolved_img">';
    html += '<img src="' + homeImg + '/icon_qs_emp.png" />';
    if (type == 1) {
        html += '<p>' + unsolved + '</p>';
    } else {
        html += '<p>' + resolved + '</p>';
    }
    html += '</div>';
    $("#Unsolved>.mkl").html(html);
    $('.dataPage').hide();
}

/**
 * 添加問題列表公共函數
 * @param {返回data} res 
 * @param {1：未解決} type 
 */
function problem_list_false(res, type) {
    var html = '';
    var pageHtml = res.page;
    var _answer, _answer_class = '';
    for (var i = 0; i < res.list.length; i++) {

        html += '<div class="col-lg-12 col-md-12 col-sm-12 List_problems clearfix m-left-30 p-left-0">';
        html += '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bank_card feedback_Inquiries p-left-20 m-bottom-20">';
        html += '<div class="bank_city text-left">';
        html += '<p class="feedback_resolved_p">' + wentibianhao + ':';
        html += '<span class="m-left-10 fproblem_id">' + res.list[i].id + '</span>';
        res.list[i].is_answer == 0 ? (_answer = problem_WHF, _answer_class = '') : res.list[i].is_answer == 1 ? (_answer = problem_YHF, _answer_class = 'pro_pro_after') : (_answer = problem_YD, _answer_class = '');
        type == 1 ? html += '<span class="problem_progress ' + _answer_class + '">' + _answer + '</span>' : false;
        res.list[i].type == 1 ? html += '<a class="feedback_resolved_span Inquiries_a" href=" ' + unlook + '?id=' + res.list[i].id + ' ">' + see + '</a>' : false;
        res.list[i].type == 2 ? html += '<a class="feedback_resolved_span" href=" ' + look + '?id=' + res.list[i].id + ' ">' + see + '</a>' : false;
        html += '</p>';
        html += '<p class="feedback_resolved_p">' + wentileixing + ': ';
        html += '<span class="m-left-10 feedback_resolved_span">' + res.list[i].first_title + '-' + res.list[i].last_title + ' </span>';
        res.list[i].order_num != null && res.list[i].order_num != 0 ? html += '<span>' + ddhm + ': ' + res.list[i].order_num + '</span>' : false;
        html += '<span class="time-on-submit">' + sendTime + ': ' + res.list[i].add_time + '</span>';
        html += '</p>';
        html += '<p class="feedback_resolved_p p-top-10 m-bottom-0 the-qest-desrcibe"> ' + res.list[i].describe + ' </p>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

    }
    $("#Unsolved>.mkl").html(html);
    $("#Unsolved>.dataPage").html(pageHtml);
    $('.dataPage').show();
}

/**
 * 循環未解決列表獲取狀態實時添加
 * @param {頁碼} p 
 * @param {1：未解決，2：已解決} type 
 */
function answer_loop(p, type) {
    $.ajax({
        type: "get",
        url: ProblemList,
        data: { type: type, p: p },
        dataType: "json",
        success: function(res) {
            if (res.list != null && res.list.length > 0) {
                var _answer, _answer_class = '';
                var _fproblem_id = $('.fproblem_id');
                for (var index = 0; index < res.list.length; index++) {
                    if (res.list.length == _fproblem_id.length && res.list[index].id == _fproblem_id.eq(index).text()) {
                        res.list[index].is_answer == 0 ? (_answer = problem_WHF, _answer_class = '') : res.list[index].is_answer == 1 ? (_answer = problem_YHF, _answer_class = 'pro_pro_after') : (_answer = problem_YD, _answer_class = '');
                        type == 1 ? $('.problem_progress').eq(index).text(_answer).addClass(_answer_class) : false;
                    } else {
                        problem_list_false(res, type);
                        return;
                    }
                }
            } else {
                problem_list_true(type);
            }
        }
    });
}