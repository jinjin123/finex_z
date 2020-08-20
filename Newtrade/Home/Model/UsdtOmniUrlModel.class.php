<?php
/**
 * 充币记录模型类
 * @author lirunqing 2017-10-9 12:01:29
 */

namespace Home\Model;

use Think\Model;

class UsdtOmniUrlModel extends Model
{
    protected $tableName = 'usdt_omni_url';

    /**
     * 获取网站配置项列表
     * @return array
     * @author lirunqing 2017-10-9 12:03:03
     */
    public function getLeftAddress($uid)
    {
        // 使用固定地址
        $useFixAddress = true;
        $address = '3D6QwuJ6F5Zfbgf5WR1xow5nEEZe9kVkn8';
        if (!$useFixAddress) {
            $info = $this->where(['user_id' => $uid])->find();
            if (!$info) {
                $unbindSize = $this->where(['user_id' => 0])->count();
                if ($unbindSize < 100) {
                    $getAddress = getAddress(2);
                    $address = $getAddress->USDT;
                    $dbData = [];
                    foreach ($address as &$addr) {
                        $data = [
                            'cz_url' => $addr,
                            'add_time' => time()
                        ];
                        array_push($dbData, $data);
                    }
                    if (sizeof($dbData) > 0)
                        $this->addAll($dbData);
                }
                $ustdInfo = $this->where(['user_id' => 0])->order('id asc')->find();
                if ($ustdInfo) {
                    $updateArr = [];
                    $updateArr['user_id'] = $uid;
                    $updateArr['add_time'] = time();
                    $res = $this->where(['id' => $ustdInfo['id']])->save($updateArr);
                    if ($res) {
                        $address = $ustdInfo['cz_url'];
                    }
                }
            } else {
                $address = $info['cz_url'];
            }
        }
        return $address;
    }
}
