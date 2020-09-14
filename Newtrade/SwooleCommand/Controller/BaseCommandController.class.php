<?php
namespace SwooleCommand\Controller;
use  Think\Controller;

/**
 * @author 建强   2018年7月16日10:11:32 命令行运行基类 
 */
class BaseCommandController extends Controller
{
     
     public function _initialize()
     {
         if(!IS_CLI)    
         {
            // exit('can not process web Httprequest');
         }
     }
}
