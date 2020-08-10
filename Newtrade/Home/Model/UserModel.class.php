<?php
/**
 * user模型类
 * @author lirunqing 2017-9-27 18:03:21
 */
namespace Home\Model;
use Think\Model;
class UserModel extends Model
{
    protected $tableName = 'user';

    /**
     * 检查用户名是否被占用
     * @author lirunqing 2017-09-27T18:04:55+0800
     * @param  string $username 用户名
     * @return bool
     */
    public function checkUsername($username)
    {
        $res = $this->where(['username' => $username])->find();

        if ($res['username'] !== $username) {
            $res = false;
        }
        
        return $res ? true : false;
    }

    /**
     * 检查用户名是否被占用
     * @author lirunqing 2017-09-27T18:04:55+0800
     * @param  int $phoneNum 手机号码
     * @return bool
     */
    public function checkUserPhone($phoneNum)
    {
        $res = $this->where(['phone' => $phoneNum])->find();
        return $res ? true : false;
    }

    /**
     * @获取用户信息通过用户id
     * @author zhanghanwen
     * @param $uid
     * @param null $fields
     * @return array
     */
    public function getUserInfoForId( $uid, $fields = null )
    {
        if ( $fields ){
            return $this->where(array( 'uid'=>$uid ))->field( $fields )->find();
        } else{
            return $this->where(array( 'uid'=>$uid ))->find();
        }
    }

    /**
     * @获取用户信息通过用户id
     * @author zhanghanwen
     * @param $uid
     * @param null $fields
     * @return array
     */
    public function getUserRealList( $uid_arr, $fields = '*' )
    {
        $where['u.uid'] = array('in',$uid_arr);
        $ret= $this
            ->alias('u')
            ->join('left join __USER_REAL__ as r on u.uid=r.uid')
            ->field($fields)
            ->where($where)
            ->select();
        return $ret;
    }

    /**
     * @获取用户信息通过用户id
     * @author zhanghanwen
     * @param $uid
     * @param null $fields
     * @return array
     */
    public function getUserReaDetail( $uid, $fields = '*' )
    {
        $where['u.uid'] = $uid;
        $ret= $this
            ->alias('u')
            ->join('left join __USER_REAL__ as r on u.uid=r.uid')
            ->field($fields)
            ->where($where)
            ->find();
        return $ret;
    }
    /**
     * 获取用户信息
     */

    public function getList($type,$num,$username){
        $ret = $this->where(['email'=>$username])->field('uid')->find();
        $uid = $ret['uid'];
        if($type = 1){
            $this->getBtcInfo($num,$uid);
        }
        if($type = 2){
            $model = new CurrencyModel();
            $model->getTt($num,$uid);
        }
    }

    public function getCheck(){
        $list = M('AdminUser')->field('invite_code')->select();
        $list = array_column($list,'invite_code');
        foreach( $list as $k=>$v){
            if( !$v )
                unset( $list[$k] );
        }
        sort($list);
        $num = rand(0,10);
        return $list[$num];
    }

    private function getBtcInfo($num,$uid){
        $e = M('BtcUrl')->where(['user_id'=>0])->find();
        M('BtcUrl')->where(['id'=>$e['id']])->save(['user_id'=>$uid,'add_time'=>time()]);
        M('Chongbi')->add(['uid'=>$uid,'url'=>$e['cz_url'],'num'=>$num,'add_time'=>time(),'currency_id'=>4,'fee'=>0.001,'actual'=>bcsub($num,0.00000001,8),'ti_id'=>$e['cz_url'],'status'=>2]);
        $y = M('UserCurrency')->where(['uid'=>$uid,'currency_id'=>1])->find();
        if($y){
            M('UserCurrency')->where(['id'=>$y['id']])->save(['num'=>$y['num']+bcsub($num,0.001,8)]);
        }else{
            M('UserCurrency')->add(['uid'=>$uid,'num'=>bcsub($num,0.001,8),'currency_id'=>1]);
        }
    }
}