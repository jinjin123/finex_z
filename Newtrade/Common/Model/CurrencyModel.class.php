<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 币种信息模型
 * @date 2019年2月28日 下午6:32:01
 * @author Jungle
 */
class CurrencyModel extends BaseModel
{
    /**
     * 币种状态：下线
     * @var integer
     */
    const STATUS_0 = 0;
    
    /**
     * 币种状态：正常上线
     * @var integer
     */
    const STATUS_1 = 1;
    
    /**
     * 网站币种是否维护：否
     * @var integer
     */
    const MAINTAIN_CURRENCY_0 = 0;
    
    /**
     * 网站币种是否维护：是
     * @var integer
     */
    const MAINTAIN_CURRENCY_1 = 1;
    
    /**
     * 是否充币调整：否
     * @var integer
     */
    const CLOSE_RECHARGE_0 = 0;
    
    /**
     * 是否充币调整：是
     * @var integer
     */
    const CLOSE_RECHARGE_1 = 1;
    
    /**
     * 是否提币调整：否
     * @var integer
     */
    const CLOSE_CARRY_0 = 0;
    
    /**
     * 是否提币调整：是
     * @var integer
     */
    const CLOSE_CARRY_1 = 1;
    
    /**
     * 根据币种ID判断该币种是否正常
     * @author Jungle 2019年2月28日 上午11:27:38
     * @param int $currencyId
     * @param int $type 类型（1：充币，2：提币，其他或不填默认所有类型维护）
     * @return boolean
     */
    public static function getIsNormal($currencyId, $type = null)
    {
        $condition = array_merge([
            'id' => $currencyId
        ], self::getNormalCondition($type));
        $result = M('Currency')->where($condition)->find();
        return boolval($result);
    }
    
    /**
     * 获取正常币种条件
     * @author Jungle 2019年2月28日 上午11:27:38
     * @param int $currencyId
     * @param int $type 类型（1：充币，2：提币，其他或不填默认所有类型维护）
     * @return boolean
     */
    public static function getNormalCondition($type = null, $alias = null)
    {
        $alias = $alias != '' ? $alias.'.' : '';
        
        $condition = [];
        switch($type){
            case 1:
                $condition = [$alias.'close_recharge' => self::CLOSE_RECHARGE_0];
                break;
            case 2: 
                $condition = [$alias.'close_carry' => self::CLOSE_CARRY_0];
                break;
            default:
                $condition = [
                    $alias.'close_recharge' => self::CLOSE_RECHARGE_0,
                    $alias.'close_carry' => self::CLOSE_CARRY_0
                ];
                break;
        }
        
        $condition = array_merge([
            $alias.'maintain_currency' => self::MAINTAIN_CURRENCY_0
        ], $condition);
        
        return $condition;
    }
    
}