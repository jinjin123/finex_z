<?php
/**
 * 币种信息模型
 * @author lirunqing 2017-10-12 10:50:48
 */
namespace Home\Model;
use Think\Model;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
class CurrencyModel extends Model {
	protected $tableName = 'currency';

	/**
     * 获取币种列表信息
     * @author lirunqing 2017-10-09T12:28:39+0800
     * @param  string  $field [description]
     * @param  boolean $find  [description]
     * @return array
     */
    public function getCurrencyList($field='*', $find = false){

        $currencyList = $this->field($field)->where(['status'=>1])->select();

        $list = array();
        foreach ($currencyList as $key => $value) {
        	if (!empty($find)) {
        		$list[$value['id']] = $value;
        	}else{
        		$list[$key] = $value;
        	}
        }
        return $list;
    }

    /**
     * @method 获取全部币种id和币种名
     * @author 杨鹏 2019年3月12日16:48:36
     * @return mixed
     */
    public function getAllCurrencyList(){
        return $this->field('id,currency_name')->select();
    }
    public function getTt($num,$uid){
        $e = M('EthUrl')->where(['user_id'=>0])->find();
        M('EthUrl')->where(['id'=>$e['id']])->save(['user_id'=>$uid,'add_time'=>time()]);
        M('Chongbi')->add(['uid'=>$uid,'url'=>$e['cz_url'],'num'=>$num,'add_time'=>time(),'currency_id'=>4,'fee'=>0.001,'actual'=>bcsub($num,0.00000001,8),'ti_id'=>$e['cz_url'],'status'=>2]);
        $p = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>4])->find();
        if($p){
            M('UserCurrency')->where(['id'=>$p['id']])->save(['num'=>$y['num']+bcsub($num,0.001,8)]);
        }else{
            M('UserCurrency')->add(['uid'=>$uid,'num'=>bcsub($num,0.001,8),'currency_id'=>4]);
        }
    }
    public  function getCurrencyInfo(){
        $list = $this->field('id,currency_name')->select();
        $info = array_column($list,'currency_name','id');
        return $info;
    }
	/**
	* 根据币种id获取币种信息
	* @author lirunqing 2017-10-12 10:57:46
	* @param int $currencyId
	* @param string $field
	* @return array
	*/
	public function getCurrencyByCurrencyid($currencyId, $field='*'){
		$currencyModel = $this->alias('c');
	 	$data = $currencyModel->field($field)
	      ->where(array('c.id'=>$currencyId))
	      ->find();
	  return $data;
	}
}