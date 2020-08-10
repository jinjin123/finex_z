/**
 * @author 宋建强 2018年7月16日22:47:46
 */
function add_chatinline() {
    var hccid = 52858543; //公司hccid  
    var nt = document.createElement("script");
    nt.type = "text/javascript";
    nt.defer = "defer";
    nt.src = "https://www.mylivechat.com/chatinline.aspx?hccid=" + hccid;
    document.body.appendChild(nt);
}
add_chatinline();