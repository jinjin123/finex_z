<?php
namespace Home\Controller;
use Think\Controller;
use Home\Logics\ChartApi;
/**
 * @author 宋建强  2018年1月16日17:01:42
 * @method tradingView  数据图表的渲染
 * @App移动端   接口类   继承基础类(pc端必须要登录)
 */
class AppViewController extends  Controller
{   
	  /**
       * @method App配置k线入口配置
      */	 
	  public function  web(){
	  	  $currencyName= trim(I('symbol')); 
	  	  $theme       = trim(I('theme'));
	  	  $style       =1;   
	  	  $tempTheme   = ($theme==1)?'mobile':'mobileNight';  //1.白天主题  2.晚上主题
	  	  $lang        = trim(I('lang'));                     //语言包  
	  	  $time        = trim(I('time'));                     //切换时间的分辨率
	  	  $this->assign('symbol',$currencyName);
	  	  $this->assign('lang',$lang?$lang:'zh-cn');  
	  	  $this->assign("time",$time?$time:'30');
	  	  $this->assign("style",$style);
	  	  $this->display($tempTheme);
	  }
	 /**
	  *  @method TradingView 配置接口
	 */
	 public  function Api(){    
	     $res=['statu'=>400,'info'=>''];
	     $query_string = $_SERVER['QUERY_STRING'];
	     if(strpos($query_string,'symbols',0)!==false) $query_string= 'symbol';
	     if(strpos($query_string,'history',0)!==false) $query_string= 'history';
	     if(strpos($query_string,'config',0)!==false)  $query_string = 'config';
	     switch ($query_string) {
	         case 'config':
	             $res=ChartApi::config();
	             break;
	         case 'history':
	             $chat  = new ChartApi();
	             $res   = $chat->history();
	             break;
	         case 'time':
	             echo time();
	             die;
	         break;
	         case 'symbol':
	             $res=ChartApi::symbolsApp();
	             break;
	     }
	     $this->ajaxReturn($res);
	 }
} 