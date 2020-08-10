$(function(){
    $('.owl-carousel').each(function(){
        let owl = $(this);
        let options = owl.data();
        // console.log(options);
        owl.owlCarousel(options);
    });
})

function toClipboard(str){
    var input = document.createElement("input");
    input.value = str;
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, input.value.length), document.execCommand('Copy');
    document.body.removeChild(input);
    toastr.options = {
        "debug": false,
        "progressBar": true,
        "positionClass": "toast-bottom-full-width",
        "onclick": null,
        "fadeIn": 300,
        "fadeOut": 1000,
        "timeOut": 5000,
        "extendedTimeOut": 1000
    };
    toastr.info(str+"\n<br>has been copied to the clip edition. You can paste it now");
}