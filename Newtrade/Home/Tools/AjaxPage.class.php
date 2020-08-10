<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Page.class.php 2712 2012-02-06 10:12:49Z liu21st $
namespace Home\Tools;
class AjaxPage {
    // 分页栏每页显示的页数
    public $rollPage = 5;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 默认列表每页显示行数
    public $listRows = 20;
    // 起始行数
    public $firstRow ;
    // 分页总页面数
    protected $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage    ;
    // 分页的栏的总页数
    protected $coolPages   ;
    //ajax_funciton的参数传递
    protected $function_paramter;
    // 分页显示定制
    //protected $config  = array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' %totalRow% %header% %nowPage%/%totalPage% 页 %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage% %end%');
    private $config  = array(
        'header' => '<span class="rows">共 %totalRow% 条记录</span>',
        'prev'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_prevpage"></use></svg>',
        'next'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_nextpage"></use></svg>',
        'first'  => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_startpage"></use></svg>',
        'last'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_lastpage"></use></svg>',
        'theme'  => '%FIRST% %upPage% %linkPage% %downPage% %END%',
    );

    // 默认分页变量名
    protected $varPage;

    public function __construct($totalRows,$listRows='',$ajax_func='',$parameter='',$function_paramter=null) {
        $this->totalRows = $totalRows;
        $this->ajax_func = $ajax_func;
        $this->parameter = $parameter;
        $this->function_paramter = $function_paramter;
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->nowPage  = !empty(I($this->varPage)) ? intval(I($this->varPage)) : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        //rollPages取小者
        $this->rollPage = $this->totalPages > $this->rollPage ? $this->rollPage : $this->totalPages;
    }
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    public function show() {
        if(0 == $this->totalRows) return '';
        $p = $this->varPage;
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?') ? '' : "?").$this->parameter;
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        //第一页
        $theFirst = '<ul class="pagation clearfix pull-right">';
        if( $this->nowPage!=1 ){
            if( $this->function_paramter != null ){
                $theFirst = '<ul class="pagation clearfix pull-right"><li class="first"><a tabindex="0" class="first paginate_button" id="DataTables_Table_2_first" href="javascript:'.$this->ajax_func."(1,".$this->function_paramter.")".'">' .
                    $this->config['first'] . '</a></li>';
            }else{
                $theFirst = '<ul class="pagation clearfix pull-right"><li class="first"><a tabindex="0" class="first paginate_button" id="DataTables_Table_2_first" href="javascript:'.$this->ajax_func."(1)".'">' .
                    $this->config['first'] . '</a></li>';
            }
        }else{
            $theFirst = '<ul class="pagation clearfix pull-right"><li class="first"><a style="color:#a0a0a0" disabled="true" tabindex="0" class="first paginate_button" id="DataTables_Table_2_first" >' .
                $this->config['first'] . '</a></li>';
        }

        //上一页
        $upRow   = $this->nowPage-1;
        if( $this->function_paramter != null ){
            $pageStr = ",".$this->function_paramter;
        }else{
            $pageStr = "";
        }
        $upPage = $upRow > 0 ?
            "<li class='prev_page'><a href='javascript:".$this->ajax_func.'('.$upRow."$pageStr)'>" . $this->config['prev'] . '</a></li>' :
            '<li class="prev_page"><a style="color:#a0a0a0" disabled="true" ><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_prevpage"></use></svg></a></li>';
        //下一页
        $downRow  = $this->nowPage + 1;
        $downPage = ($downRow <= $this->totalPages) ?
            "<li class='next_page'><a href='javascript:".$this->ajax_func.'('.$downRow.$pageStr.")'>" . $this->config['next'] . '</a></li>'
            : '<li class="next_page"><a style="color:#a0a0a0" disabled="true" ><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_nextpage"></use></svg></a></li>';

        //最后一页
        $theEnd = $this->totalPages;//总页数
        if( $this->nowPage != $theEnd ){
            $theEnd = '<li class="last"><a tabindex="0" class="last paginate_button" id="DataTables_Table_2_last" href="javascript:'.$this->ajax_func."(".$theEnd.$pageStr.')">' . $this->config['last'] . '</a></li></ul>';
        }else{
            $theEnd = '<li class="last"><a style="color:#a0a0a0" disabled="true" tabindex="0" class="last paginate_button" id="DataTables_Table_2_last" >' . $this->config['last'] . '</a></li></ul>';
        }

        if($this->nowPage == 1){
            $prePage = "";
        }else{
            $prePage = $this->nowPage -  1;
        }
        if($this->nowPage == $this->totalPages){
            $nextPage = "";
        }else{
            $nextPage = $this->nowPage + 1;
        }

        $linkPage = "";
        $nowPage = $this->nowPage;
        $limitNum = floor($this->rollPage / 2);
        //确定从哪一页还是循环输出
        if( ($nowPage - $limitNum >= 1) && ($nowPage + $limitNum <= $this->totalPages) ){
            $page = $nowPage - $limitNum;
        }elseif( $nowPage - $limitNum < 1 ){
            $page = 1;
        }elseif( $nowPage + 1 == $this->totalPages ){
            $page = $nowPage - $this->rollPage + 2;
        }elseif( $nowPage == $this->totalPages ){
            $page = $this->totalPages - $this->rollPage + 1;
        }
        //循环输出页码
        for ($i = $page ;$i < $page + $this->rollPage;$i++){
            if( $i == $nowPage ){
                $linkPage .= "<li class='pagation-active'><a href='javascript:".$this->ajax_func."(".$i.$pageStr.")'><span class='current-page'>".$i."</span></a></li>";
            }else{
                $linkPage .= "<li class='pagation_button'><a href='javascript:".$this->ajax_func."(".$i.$pageStr.")'>".$i."</a></li>";
            }
        }
        //如果显示的页数只有1页 则显示当前页码
        if( $this->rollPage == 1 ){
            $linkPage = "<li class='pagation-active'><a href='javascript:".$this->ajax_func."(".$nowPage.$pageStr.")'><span class='current-page'>".$nowPage."</span></a></li>";
        }
        $pageStr  =  str_replace(
                array('%header%','%nowPage%','%upPage%','%downPage%','%FIRST%','%prePage%','%linkPage%','%nextPage%','%END%'),
                array($this->config['header'],$this->nowPage,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$this->config['theme']);
        return "<div class='dataTables_paginate paging_full_numbers clearfix' id='DataTables_Table_2_paginate'>{$pageStr}</div>";

    }
}
?>