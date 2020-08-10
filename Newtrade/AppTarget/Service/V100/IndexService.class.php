<?php
/**
 * User: 李江
 * Date: 2017/12/14
 * Time: 15:09
 */

namespace AppTarget\Service\V100;
use AppTarget\Service\ServiceBase;
use Common\Api\RedisCluster;
use Common\Logic\OffTrading;
use Home\Controller\CoinTradeInfoController;
use Common\Api\Maintain;
use Home\Model\NoticeModel;
use SwooleCommand\Controller\WebSocketPushClientController;

class IndexService extends ServiceBase
{
    private $offTradingObj = null;
    private $redis=NULL;
    private $uid = 0;
    private $noticeModle = null;
    public function __construct()  {
        parent::__construct();
        $this->redis  = RedisCluster::getInstance();
        $this->offTradingObj = new OffTrading();
        $this->noticeModle = new NoticeModel();
    }

    /**
    * 公告详情显示页面
    * author 刘富国
    * @time   2019年7月27日
    */
    public function notice(){
        // 根据语言选择新闻
        $languange = C('DEFAULT_LANG');
        $langSet = $this->getData('var_language');

        if(!empty( $this->langSet ))  $languange = $this->langSet;
        $langList = C('LANG_LIST',null,'zh-tw');
        if(strstr($langList,$langSet) and !empty($langSet)) {
            $languange = $langSet;
        }
        $files = "`$languange-title` as title,`$languange-content` as content,id,add_time" ;
        $noticeData = $this->noticeModle->getLastNotice( $files );
        if(empty($noticeData)) return (object)[];
        $content = html_entity_decode($noticeData['content'] );
        preg_match_all("/(src=.*)Upload/isU", $content, $arr);
        for($i=0,$j=count($arr[1]);$i<$j;$i++){
            $content = str_replace($arr[1][$i],'"'.C('app_img_url'),$content);
        }
        $noticeData['content'] = $content;

        return $noticeData;
    }
    
    /**
     * 获取交易模块是否暂停记录
     * 刘富国
     * @return array
     */
    public function getTradeMaintainInfo(){
        return  Maintain::getTradeMaintainVals();
    }
    
    /**
     * 获取线下交易币种市场价格
     * @author 李江 2017年12月15日14:53:48
     * @return array
     */
    public function getCoinInfoList(){
        $coinInfoKey          = 'APP_COIN_INFO_LIST_BY_BIF';
        $app_logo = [];
        $this->uid = $this->getUserId();
        if($this->uid > 1 ) $this->offTradingObj->checkCurrencyCount($this->uid);
        $coinInfoList         = $this->redis->get($coinInfoKey);
        if(!empty($coinInfoList)) return unserialize($coinInfoList);
        $coinTradeInfoObj = new CoinTradeInfoController();
        $currencyList = M('Currency')
        ->field('currency_mark,id,currency_name,currency_app_logo')
        ->where(['status' =>1])
        ->select();
        foreach ($currencyList as $key => $value) {
            $app_logo[$value['id']] = $value['currency_app_logo'];
        }
        foreach ($currencyList as $key => $value) {
            if ($value['id'] == 6) {
                unset($currencyList[$key]);
                continue;
            }
            $testTemp['currency_name'] = $value['currency_name'];
            $testTemp['currency_id']   = $value['id'];
            $coinArr[] = $testTemp;
        }
        $coinReturnArr = $coinTradeInfoObj->getCoinInfo();
        $coinAppInfoList = (new WebSocketPushClientController())->fromatAppCoinInfo($coinReturnArr['coinInfoList'],$app_logo);
        $coinInfoList = serialize($coinAppInfoList);
        $this->redis->setex($coinInfoKey, 300, $coinInfoList);// 缓存5分钟获取的b站数据
        return $coinAppInfoList;
    }
}