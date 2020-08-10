//绑定函数

$(".newsPage").click(function(){
    operateLocalStorage($(this).attr('newsID'));
});

function getLocalStorage(obj) {
    var newsID = $(obj).attr("newsID");
    operateLocalStorage(newsID);
}

function operateLocalStorage(newsID) {
    var viewNews;
    if( localStorage.newsArr == null){
        viewNews = [];
        viewNews.push(newsID);
        viewNews =  JSON.stringify(viewNews);
    }else{
        viewNews = JSON.parse(localStorage.newsArr); //转换为json对象
        if(viewNews.indexOf(newsID) != -1){
            var index = viewNews.indexOf(newsID);
            viewNews.splice(index,1);
        }

        if( viewNews.length > 4){
            //如果数组长度 > 4, 则删除最后一个id
            viewNews.pop();
        }
        //数组最前插入新id
        viewNews.unshift(newsID);
        viewNews =  JSON.stringify(viewNews);
    }

    //处理数组后重新写入localstorage
    localStorage.setItem("newsArr",viewNews);
}




