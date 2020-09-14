<?php
/**
 * 充币记录模型类
 * @author lirunqing 2017-10-9 12:01:29
 */

namespace Home\Model;

use Think\Model;

class EthUrlModel extends Model
{
    protected $tableName = 'eth_url';

    /**获取
     * @param $uid  用户id
     * @return string
     */
    public function getRightAddress($uid)
    {
        $useFixAddress = false;
        $address = '0xE68461c23ef054035090248E1BbCad52870035B3';
        if (!$useFixAddress) {
            $info = $this->where(['user_id' => $uid])->find();
            if (!$info) {
                $unbindSize = $this->where(['user_id' => 0])->count();
                if ($unbindSize < 100) {
                    $getAddress = getAddress(4);
                    $address = $getAddress->ETH;
                    $dbData = [];
                    foreach ($address as &$addr) {
                        $data['cz_url'] = $addr;
                        $data['add_time'] = time();
                        array_push($dbData, $data);
                    }
                    if (sizeof($dbData) > 0)
                        $this->addAll($dbData);
                }
                $ustdInfo = $this->where(['user_id' => 0])->order('id asc')->find();
                $updateArr = [];
                $updateArr['user_id'] = $uid;
                $updateArr['add_time'] = time();
                $res = $this->where(['id' => $ustdInfo['id']])->save($updateArr);
                if ($res) {
                    $address = $ustdInfo['cz_url'];
                }
            } else {
                $address = $info['cz_url'];
            }
        }
        return $address;
    }
}