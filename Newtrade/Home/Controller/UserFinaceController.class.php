<?php
/**
 * 财务日志
 * 显示用户财务日志
 * @author yangpeng  2017-10-24
 */
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Model\CurrencyModel;
use Common\Api\RedisIndex;
use Home\Model\NoticeModel;

class UserFinaceController extends CommonController{
    private $currencyModel = null;
   
	/**
	 * 自动加载
	 * @author yangpeng 2017-10-26
	 */
	public function _initialize(){
		parent::_initialize();
		$this->currencyModel = new CurrencyModel();
	}


    /**
     * @method 财务日志
     * @param currency_id 币种；finance_type 财务日志类型
     * @author 杨鹏 2019年3月12日10:42:45
     * @return null
     */
   public function showFinace() {
       //1、接收表单数据
        $currency_id = I('currency_id')?I('currency_id'):1;
        $finance_type = I('finance_type');  
        if(!empty($currency_id) && $currency_id != -1){
            $where['currency_id'] = $currency_id;
        }
        if(!empty($finance_type) && $finance_type != -1){
            $where['finance_type'] = $finance_type;
            if($finance_type==5){ // p2p
                $where['finance_type'] = array('in',array(5,6,7,8,9,14,15,17,18));
            } elseif ($finance_type==6){ // c2c
                $where['finance_type'] = array('in',array(19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36));
            }elseif ($finance_type==7){ // 币币交易
                $where['finance_type'] = array('in',array(10,11,12,13,37,16));
            }
        }
        $where['add_time'] = ['between',[strtotime("-1 month"),time()]];
        $uid = getUserId();
        $where['uid'] = $uid;
        $table = getTbl('UserFinance', $uid);
        $M_User_Finance = M($table); // 实例化User对象
        $count      = $M_User_Finance->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Home\Tools\AjaxPage($count, 15,'changepage');// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        // 2、进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $finance_list = $M_User_Finance->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $currency = $this->currencyModel->getAllCurrencyList();
        $b_count = count($currency);//币种数量
        if(IS_AJAX){
            $fiance_data['list'] = $finance_list;
            foreach ($fiance_data['list'] as $key => $value) {
                $fiance_data['list'][$key]['finance_type'] = formatFinanceType($fiance_data['list'][$key]['finance_type']);
                $fiance_data['list'][$key]['add_time'] = date("Y-m-d H:i:s",$fiance_data['list'][$key]['add_time']);
                $fiance_data['list'][$key]['typesb']=$fiance_data['list'][$key]['type'];
                $fiance_data['list'][$key]['type'] = formatMoneyZhengFu($fiance_data['list'][$key]['type']);
            }
            $fiance_data['show'] = $show;          
            $this->ajaxReturn($fiance_data);
        }else{
            $this->assign('page',$show);// 赋值分页输出
            $this->assign('b_count',$b_count);// 赋值分页输出
            $this->assign('currency',$currency);
            $this->assign('finance_list',$finance_list);
            $this->assign('finance_type_list',getFinanceTypeList());
            $this->display();
        }
    }
   
}