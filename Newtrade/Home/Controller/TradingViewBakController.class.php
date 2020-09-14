<?php
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Logics\ChartApi;
/**
 * @author 宋建强  2018年1月16日17:01:42
 * @desc   tradingView   数据图表的渲染
 */
class TradingViewController extends  CommonController  
{   
     /**
      * @method 载入模板 iframe 初始化模板
     */	 
	  public function  rawTrade(){
	  	 $currencyType=trim(I('CoinType'));                            
	  	 $style=1; 
	  	 if(strpos(strtolower($currencyType), 'vp')!==false) $style=8;
	     $this->assign('symbol',!empty($currencyType)?$currencyType:'BTC');
	  	 $this->assign("lang",cookie('think_language')?cookie('think_language'):'zh-cn'); 
	  	 $this->assign("time",'15'); 
	  	 $this->assign("style",$style);
	  	 $this->assign("tvdebug",false);
	  	 $this->display('/TradingView/TradingView');
	  }
	 /**
	  * @method TradingView 配置接口
	 */
	 public  function Api(){  
	 	 $res=['statu'=>400,'info'=>''];
	 	 $query_string = $_SERVER['QUERY_STRING'];
	 	 if(strpos($query_string,'symbols',0)!==false)   $query_string= 'symbol';
	 	 if(strpos($query_string,'history',0)!==false)   $query_string= 'history';
	 	 if(strpos($query_string,'config',0)!==false)   $query_string = 'config';
	 	 switch ($query_string) {
	 	     case 'config':
	 	         $res=ChartApi::config();
	 	     break;
	 	     case 'history':
	 	         $chart  =  new ChartApi();
	 	         $res=$chart->history();
	 	     break;
	 	     case 'time':
	 	         echo time();die;
	 	     break;
	 	     case 'symbol':
	 	         $res=ChartApi::symbols();
	 	     break;
	 	 }
	 	 $this->ajaxReturn($res);
	 }
} 