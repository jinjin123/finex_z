<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Home\Tools;

class Page{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 3;// 分页栏每页显示的页数
	public $lastSuffix = true; // 最后一页是否显示总页数

    private $p       = 'p'; //分页参数名
    private $url     = ''; //当前链接URL
    private $nowPage = 1;

	// 分页显示定制
    private $config  = array(
//        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',

//        'prev'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_prevpage"></use></svg>',
//        'next'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_nextpage"></use></svg>',
//        'first'  => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_startpage"></use></svg>',
//        'last'   => '<svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_lastpage"></use></svg>',
        'theme'  => '%FIRST%  %LINK_PAGE% %DOWN_PAGE% %END%',
    );

    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->rollPage   = $this->totalPages > $this->rollPage ? $this->rollPage : $this->totalPages;
    }

    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows) return '';
        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);

        //上一页
//        $up_row  = $this->nowPage - 1;
//        $up_page = $up_row > 0 ?
//            "<li class='prev_page'><a href='".$this->url($up_row)."'>" . $this->config['prev'] . '</a></li>' :
//            '<li class="prev_page"><a style="color:#a0a0a0" disabled="true" ><svg class="icon icon_norepeat" aria-hidden="true"><use xlink:href="#icon-ic_prevpage"></use></svg></a></li>';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ?
            "<li class='nav-next'><a href='".$this->url($down_row)."'>"."<i class='fa fa-angle-right'></i> ". '</a></li>'
            : '<li class="nav-next"><a ><i class="fa fa-angle-right"></i></a></li>';
        
        //第一页
        $the_first = '';
        if($this->nowPage!=1){
             $the_first = '<ul class="nav-links">';
        }else{
            $the_first = '<ul class="nav-links">';
        }

        //最后一页
        $the_end = '';
        $the_endRow = $this->totalPages;//总页数
        if($this->nowPage != $the_endRow){
                $the_end = '</ul>';
        }else{
                $the_end = '</ul>';
        }

        $linkPage = "";
        $nowPage = $this->nowPage;
        $limitNum = floor($this->rollPage / 2);
//        echo $limitNum;die;
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
//        echo  $this->rollPage;die;
//        echo $page.'<br/>';
//        echo $this->url(1);
        //循环输出页码
        for ($i = $page ;$i < $page + $this->rollPage;$i++){
            if( $i == $nowPage ){
                $linkPage .= "<li class='active'><a href='".$this->url($i)."'>".$i."</a></li>";
            }else{
                $linkPage .= "<li ><a href='".$this->url($i)."'>".$i."</a></li>";
            }
        }
//        echo $linkPage;die;
        //如果显示的页数只有1页 则显示当前页码
//        if( $this->rollPage == 1 ){
//            $linkPage = "<li class='active'><a href='".$this->url($nowPage)."'>".$nowPage."</a></li>";
//        }
        //替换分页内容
        $page_str = str_replace(
            array( '%NOW_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array( $this->nowPage, $down_page, $the_first, $linkPage, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']
        );

        return "<div class='dataTables_paginate paging_full_numbers' >{$page_str}</div>";
    }
}
