/*------------------------------------------------------------
踢下线弹窗提示
-------------------------------------------------------------*/
function BottomalertBox(str) {
    $.notify(str, {
        placement: {
            from: "top",
            align: "right"
        },
        offset: {
            x:0,
            y:70
        },
        template: '<div data-notify="container" class="col-md-3 p-0">' +
            '<div class="alert alert-{0} border-{0} alert-bottom alert-dismissible" style="position:relative;"><button type="button" class="close" data-notify="dismiss">×</button>{2}</div>' +
            '</div>'
    });
}

// 获取上一次页面的url
var prevState = localStorage.getItem('loginTip');
// 获取当前页面的url
var nextState = loginTip;

 //页面每次加载都存一次路径到cookie中
localStorage.setItem('loginTip',nextState);

/*  localStorage不为null,即不是第一次打开该页面
*   上一次当前页面的路径和这次的不一样,证明是踢下线跳转到该页面,不是刷新当前页
*   loginTip    证明是踢下线操作
*/
if(prevState!=null && prevState!=nextState && loginTip){
    BottomalertBox(backout_tips);
}
