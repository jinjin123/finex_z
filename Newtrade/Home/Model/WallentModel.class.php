<?php
namespace Home\Model;
use Think\Model;
use Home\Logics\PublicFunctionController;
use Common\Api\Tibi;
use Common\Api\Wallent;
class WallentModel extends Model{
    
    protected $tableName = 'currency';
    /**
     * @author 建强 2019年3月4日 下午3:36:28
     * @method 获取入口tab的数据进行渲染
     * @param  uid int 用户uid
     * @param  $curr_id int 币种id  
     * @return array 
     */
    public function getChargeTabPage($uid=0,$curr_id=''){
        try{
            $ret = [
                'code'=>70003,
                'msg'=>L('_CSCW_'),
                'data'=>'',
            ];
            //判断 是否存下线币种 
            $status = $this->checkCurrConfig($curr_id);
            if($status){
               $ret['data']['refresh']=1;
               $ret['msg'] = L('_BZWHZBZBYXCZ_');
               return $ret;
            }
            $currencys = $this->getCurrency();
            if(empty($currencys)){
                $ret['msg'] = L('_BZWHZBZBYXCZ_');
                $ret['data']['refresh']=0;
                return $ret;
            }
            if(empty($curr_id)){
                $curr_id= self::searchCurrId($currencys);
            } 
            //组装头部tab
            foreach($currencys as $key=>$val){
                $currencys[$key]['closed'] = 0;
                $currencys[$key]['maintain_str']='' ;
                $currencys[$key]['tab_active']  =0 ;
                unset($currencys[$key]['maintain_currency']);
                if($val['id']==$curr_id){
                    $currencys[$key]['tab_active'] =1 ; //选中当前tab
                }
                if($val['maintain_currency']==1){
                    $currencys[$key]['closed']=1;
                    $currencys[$key]['maintain_str']= L('_BZWHZ_');
                    continue;
                }
            }
            //成功返回 
            $ret['code']=200;
            $ret['msg'] ='success';
            $ret['data'] =[
                'refresh'=>0,
                'tab_currency' => $currencys,
                'url_charge'   => self::getUserCurrencyInfoByUid($uid,$curr_id),
                'res'          => self::getChargelistByCurrencyId($uid,$curr_id,$currencys)
            ];
            return $ret;
        }catch(\Exception $e){
            $ret['data']=$e->getMessage();
            return $ret;
        }
    }
    /**
     * @author 建强 2019年3月14日 下午5:40:01
     * @method 充提币维护验证 
     * @param  int $curr_id 
     * @param  int type 充币 1  提币2
     * @return bool;
     */
    public function checkCurrConfig($curr_id,$type=1){
        if(empty($curr_id)) return false;
        $curr_info = $this->getCurrencyInfoById($curr_id);
        if(empty($curr_info)) return true;
        //如果是维护 
        $field =  $type == 1 ?'close_recharge':'close_carry';
        if($curr_info['maintain_currency']==1 || $curr_info[$field]==1){
            return true;
        }
        return false;
    }
    /**
     * @author 建强 2019年3月4日 下午6:24:43
     * @method 绑定充币地址  
     * @return array
     */
    public function bindMyChargeUrl($curr_id,$uid,$type,$charge_url=''){
        try{
            $bind_type = [1,2];  //1.绑定 2.更新地址
            //判断基本参数
            if(empty($curr_id) || empty($uid) || !in_array($type,$bind_type)){
                return self::retMsg(30001,L('_CSCW_'));                
            }
            //检测是否正在维护
            $curr_info = $this->getCurrencyInfoById($curr_id);
            if($curr_info['close_recharge']==1 || $curr_info['maintain_currency']==1){
                return self::retMsg(30006,L('_BZWHZBZBYXCZ_'));
            }
            //第一次绑定 
            if($type ==1){
                return $this->bindPackUrl($uid,$curr_id);
            }
            //更新地址 
            if(empty($charge_url)) return self::retMsg(30006,L('_CSCW_'));
            return $this->UpdatePackUrl($charge_url,$curr_id,$uid);
        }catch(\Exception $e){
            return self::retMsg(30008,$curr_id);
        }
    }
    /**
     * @author 建强 2019年3月4日 下午8:16:58
     * @method 第一次绑定地址url  
     * @param  uid  int 
     * @param  curr_id int 
     * @return array
     */
    public function bindPackUrl($uid,$curr_id){
        try{
            $where=[
                'uid'=>$uid, 'currency_id'=>$curr_id
            ];
            //效验是否首次绑定
            $user_curr = $this->getUserCurrencyInfoByUid($uid,$curr_id);
            if($user_curr['url_bool']==1){
                return self::retMsg(50003,L('_CSCW_'));
            }
            //获取表名
            $table = Wallent::instance()->getTableName($uid, $curr_id);
            //获取字段名称 
            $field = Wallent::instance()->getAddrField($curr_id);
            
            $packUrl=M($table)->where(['user_id'=>0])->order('rand()')->find();
            if(empty($packUrl)){
                return self::retMsg(50004,L('_CZSB_'));
            }
            //开启事物 
            $ret =[];
            M()->startTrans();
            $ret[]      = M('UserCurrency')->where($where)
                ->save(['my_charge_pack_url'=>$packUrl[$field]]);
            $ret[]      = M($table)->where([$field=>$packUrl[$field]])
                ->save(['user_id'=>$uid,'add_time'=>time()]);
            //首次绑定加积分
            $PublicFun  = new PublicFunctionController();
            $table_score= getTbl('UserScoreLog', $uid);
            $count      = M($table_score)->where(['status'=>3,'uid'=>$uid])->count();
            if($count<=0){
                $scoreAdd =[ 
                    'operationType'=>'inc',
                    'scoreInfo'=>'第一次綁定充值地址贈送積分',
                    'status'=>3
                ];
                $ret[] = $PublicFun->calUserIntegralAndLeavl($uid,10,$scoreAdd);
            }
            //判断事物 
            if(in_array(false, $ret)){
                M()->rollback();
                return self::retMsg(60001,L('_CZSB_'));
            }
            //绑定成功返回值 
            M()->commit();
            $bitcoin_url = Wallent::instance()->getBchBsvAddress($curr_id,$packUrl[$field]);
            $data = [
                'currency_id'=>$curr_id,
                'my_charge_pack_url'=>$bitcoin_url,
                'url_bool'=>1,
                'legacy_url'=>'',
                'eos_pack_url'=>'',
            ];
            //bch,bsv旧地址
            if(in_array($curr_id, C('BCH_CURRENCY_IDS'))){
                $data['legacy_url'] = $packUrl['old_cz_url'];
            }
            if($curr_id==C('EOS_ID')){
                $data['eos_pack_url']=C('EOS_FIX_URL');
            }
            return self::retMsg(200,L('_CZCG_'),$data); 
        }catch(\Exception $e){
            return self::retMsg(60009,L('_CZSB_'));
        }
    }
    /**
     * @author 建强 2019年3月4日 下午6:38:30
     * @method 更充币地址
     * @return array
     */
    public function UpdatePackUrl($charge_url,$curr_id,$uid){
        try{
            //效验地址是否为空
            $user_curr = $this->getUserCurrencyInfoByUid($uid,$curr_id);
            if($user_curr['url_bool']==0){
                return self::retMsg(50003,L('_CSCW_'));
            }
            //转换地址
            $charge_url  =trim(Wallent::instance()->getBchNoPrefixAddress($curr_id, $charge_url));
            $db_pack_url =trim(Wallent::instance()->getBchNoPrefixAddress($curr_id, $user_curr['my_charge_pack_url']));
            //验证原地址是否正确 
            if($db_pack_url!=$charge_url){
                return self::retMsg(70004,L('_CSCW_'));
            }
            //获取表名
            $table = Wallent::instance()->getTableName($uid, $curr_id);
            //获取字段名称
            $field = Wallent::instance()->getAddrField($curr_id);
            $ret   = self::checkPackUrlBindTimeAndHaveRecord($uid,$curr_id,$charge_url,$table,$field);
            if($ret['code']!=200){
                return $ret;
            }
            $where=['user_id'=>0,];
            $map=[
                'uid'=>$uid, 'currency_id'=>$curr_id
            ];
            $delCondition=[
                'uid'=>$uid, $field=>$charge_url,
            ];
            $pack=M($table)->where($where)->order('rand()')->find();
            if(empty($pack)){
               return self::retMsg(80005,L('_CZSB_'));
            }
            //开始事物操作
            M()->startTrans();
            $ret  = [];
            $ret[]= M('UserCurrency')->where($map)->save(['my_charge_pack_url'=>$pack[$field]]);                
            $ret[]= M($table)->where([$field=>$pack[$field]])->save(['user_id'=>$uid,'add_time'=>time()]);
            $ret[]= M($table)->where($delCondition)->delete();
            if(in_array(false, $ret)){
                M()->rollback();
                return self::retMsg(80006,L('_CZSB_'));
            }
            //成功返回
            M()->commit();
            //返回值
            $data = [
                'currency_id'=>$curr_id,
                'my_charge_pack_url'=>Wallent::instance()->getBchBsvAddress($curr_id,$pack[$field]),
                'url_bool'=>1,
                'legacy_url'=>'',
                'eos_pack_url'=>'',
            ];     
            if($curr_id==C('EOS_ID')){
                $data['eos_pack_url']=C('EOS_FIX_URL');
            }
            //bch,bsv旧地址
            if(in_array($curr_id, C('BCH_CURRENCY_IDS'))){
                $data['legacy_url'] = $pack['old_cz_url'];
            }
            return self::retMsg(200,L('_DZGXCG_'),$data); 
        }catch(\Exception $e){
            return self::retMsg(80007,L('_CZSB_'));
        }
    }
    /**
     * @author 建强 2019年3月4日 下午8:42:51
     * @method 更换地址的检验 
     * @return array 
     */
    protected static function checkPackUrlBindTimeAndHaveRecord($uid,$currency_id,$oldUrl,$table,$field)
    {
        $map = [
            'uid'=>$uid,'currency_id'=>$currency_id,
            'url'=>$oldUrl,'status'=>2,    //成功充值记录
        ];
        $where = [
            $field=>$oldUrl,'user_id'=>$uid
        ];
        //绑定24小时内
        $add_time=M($table)->where($where)->getField('add_time');
        if($add_time>0 && time()<$add_time+24*3600){
            return self::retMsg(40006,L('_CBDZBDYTNQWPFCZ_'));
        }
        //判断是否有充币成功的记录
        $count=M('Chongbi')->where($map)->count();
        if($count<=0){
            return self::retMsg(40007,L('_DZMYCBJLWFGH_'));
        }
        return self::retMsg();
    }
    /**
     * @author 建强 2019年3月4日 下午8:36:32
     * @method 返回值
     * @return array
     */
    public static function retMsg($code=200,$msg='success',$data=''){
        return [
            'code'=>$code,
            'msg' =>$msg,
            'data'=>$data,
        ];
    }
    /**
     * @author 建强 2019年3月4日 下午3:58:25
     * @method 找第一个不是维护状态下的币种的id 
     * @param  array currecnys
     * @return int curr_id 
     */
    public static function searchCurrId($currency=[]){
        $curr_id =0;
        foreach($currency as $value) {
            if($value['maintain_currency']==0){
                $curr_id=$value['id'];
                break;
            }
        }
        return $curr_id;
    }
    /**
     * @author 建强 2019年3月4日 下午4:03:57
     * @method 获取所有的币种名称
     * @return array 
     */
    public function getAllCurrencyName(){
        $names = $this->field('id,currency_name')->select();
        return array_column($names, 'currency_name','id');
    }
    /**
     * @author 建强 2019年3月4日 下午3:22:35
     * @method 获取所有上线的币种  
     * @param  type  充提幣選項  1.充幣 2.提幣
     * @return array 
    */
    public function getCurrency($type =1){
        $type_feild = ($type ==1)?'close_recharge':'close_carry';
        $where =[$type_feild=>0]; 
        $field = 'id,currency_name,maintain_currency';
        $currecnys = $this->where($where)->field($field)->select();
        if(empty($currecnys)) return [];
        return $currecnys;
    }
    /**
     * @author 建强 2019年3月4日 下午3:46:14
     * @method 获取用户某个币种的充币地址  单个值
     * @param  uid int 用户uid 
     * @param  currency_id int 
     * @return array 
     */
    public static function getUserCurrencyInfoByUid($uid=0,$currency_id=0){
       $url = [
           'currency_id'=>$currency_id,
           'my_charge_pack_url' =>'',
           'url_bool'=>0,  //没有充值地址
           'eos_pack_url'=>'',
           'legacy_url'=>'',
       ];
       $where = [
           'currency_id' =>$currency_id, 
           'uid' =>$uid,
       ];
       if($currency_id == C('EOS_ID')){
           $url['eos_pack_url'] = C('EOS_FIX_URL');
       }
       $ret = M('UserCurrency')->where($where)->getField('my_charge_pack_url');
       if(empty($ret)) return $url;
       //bch bsv地址进行展示新老地址 
       if(in_array($currency_id, C('BCH_CURRENCY_IDS'))){
           $url['legacy_url'] = self::getBchBsvlegacyUrl($ret,$currency_id);
       }
       $url['my_charge_pack_url'] = Wallent::instance()->getBchBsvAddress($currency_id, $ret);;
       $url['url_bool'] =1;
       return $url;
    }
    /**
     * @author 建强 2019年3月6日 下午12:16:33
     * @method 获取 legacy_url bch bsv
     * @return  string
     */
    public static function getBchBsvlegacyUrl($charge_url,$currency_id){
         $table = $currency_id ==5 ? 'BchUrl':'BsvUrl';
         $where = ['cz_url'=>$charge_url];
         $legacy_url =M($table)->where($where)->getField('old_cz_url');
         return $legacy_url?$legacy_url :'';
    }
    /**
     * @author 建强 2019年3月4日 下午3:26:25
     * @method 获取指定币种充币记录的数据 (AJAX格式)
     * @param  currency_id 币种id
     * @param  uid  用户uid
     * @return array
     */
    public static function getChargelistByCurrencyId($uid=0,$currency_id=1,$currencys){
        $names =  array_column($currencys, 'currency_name','id');
        $curr_name = $names[$currency_id];
        $where =[
            'currency_id'=>$currency_id,
            'uid' =>$uid,
        ];
        $count = M("Chongbi")->where($where)->count();
        $data  = ['page'=>'', 'list'=>''];
        if(empty($count)) return $data;
        $Page  = new \Home\Tools\AjaxPage($count,10,'currenty','',$currency_id);       
        $show  = $Page->show();
        $list  = M("Chongbi")->where($where)
             ->order("id desc")
             ->limit($Page->firstRow.','.$Page->listRows)
             ->select();
        
        $str =L('_LOOK_');
        foreach ($list as $k=>$value){
            $list[$k]['num']      = number_format($value['num'],8,'.','');
            $list[$k]['add_time'] = date('Y-m-d H:i:s', $value['add_time'] );
            $list[$k]['status']   = chongbistatus($value['status']);
            $list[$k]['third_url']= '';
            if($value['ti_id']){
                $url =  self::getCoinDetialUrl($curr_name).$value['ti_id'];
                $list[$k]['third_url']='<a class=\'query_url\' href="'.$url.'" target="_blank">'.$str.'</a>';
            }
        }
        return ['page'=>$show, 'list'=>$list];
    }
    /**
     * @author 建强 2019年3月4日 下午5:30:13
     * @method 获取地址渲染数据
     * @return string 
     */
    public static function getCoinDetialUrl($currency_name){
        $currency_name = strtolower($currency_name);
        return C('coinurl.'.$currency_name);
    }
    /**
     * @method 根据币种id获取币种名
     * @author 杨鹏 2019年3月4日16:52:22
     * @param string $currency_id 币种id
     * @return array 一维数组
     */
    private static function getCurrencyNameById($currency_id=""){
       return  M('Currency')->where(['id'=>$currency_id])->getfield('currency_name');
    }
    /**
     * @method 获取钱包页面币种列表
     * @author 杨鹏 2019年3月4日16:53:31
     * @param $currency_id int  当前选中的币种id
     * @return array 二维数组
     */
    public function getWalletCurrencyList($currency_id=''){
        //注意币种筛选字段为2
        $currencyList = $this->getCurrency(2);
        foreach ($currencyList as $k=>$v){
            $currencyList[$k]['active_tab'] =0;
            $currencyList[$k]['closed']     =0;
            $currencyList[$k]['layer_maintain_str']= '';
            $currencyList[$k]['maintain_str'] ='';
            if($currency_id==$v['id']) $currencyList[$k]['active_tab'] =1;
            if($v['maintain_currency']==1){
                $currencyList[$k]['closed']     =1;
                $currencyList[$k]['maintain_str']= L('_BZWHZ_');
                $currencyList[$k]['layer_maintain_str']= L('_BZWHZBZBYXCZ_');
            }
        }
        return $currencyList;
    }
    /**
     * @method 获取用户绑定的提币地址
     * @author 杨鹏 2019年3月4日17:09:11
     * @param string $currency_id
     * @param int  $uid
     * @return array 一维数组
     */
    public function getUserTiBiUrl($uid,$currency_id=""){
        $ret = [];
        $where =[
            'uid'=>$uid,
            'currency_id'=>$currency_id,
        ];
        $field ='currency_id,my_mention_pack_url1,my_mention_pack_url2,my_mention_pack_url3';
        $res   = M("UserCurrency")->field($field)->where($where)->find();
        if(empty($res)) return $ret;
        $url_1 = $res['my_mention_pack_url1'];
        $url_2 = $res['my_mention_pack_url2'];
        $url_3 = $res['my_mention_pack_url3'];
        $ret = [
            'currency_id'=>$res['currency_id'],
            'pack_url1'=>[
                'addr'=>$url_1,
                'memo'=>'',
            ],
            'pack_url2'=>[
                'addr'=>$url_2,
                'memo'=>'',
            ],
            'pack_url3'=>[
                'addr'=>$url_3,
                'memo'=>'',
            ],
        ];
        if($currency_id== C('EOS_ID')){
            if(strpos($url_1, ':')!==false){
                $tmp = explode(':', $url_1);
                $ret['pack_url1']['addr']=$tmp[0];
                $ret['pack_url1']['memo']=$tmp[1];
            }
            if(strpos($url_2, ':')!==false){
                $tmp = explode(':', $url_2);
                $ret['pack_url2']['addr']=$tmp[0];
                $ret['pack_url2']['memo']=$tmp[1];
            }
            if(strpos($url_3, ':')!==false){
                $tmp = explode(':', $url_3);
                $ret['pack_url3']['addr']=$tmp[0];
                $ret['pack_url3']['memo']=$tmp[1];
            }
        }
        return $ret;
    }
    /**
     * @method 获取用户某一币种的提币记录
     * @author 杨鹏 2019年3月4日17:31:30
     * @param  string $currency_id 币种id
     * @param  int $uid 
     * @return array 三维数组
     */
    public function getTiBIRecord($uid,$currency_id=''){
        $currency_name = self::getCurrencyNameById($currency_id);
        $count = M('Tibi')->where(['uid'=>$uid,'currency_id'=>$currency_id])->count();
        $page = new \Home\Tools\AjaxPage($count,10,'tibi_currency','',$currency_id);
        $tibiInfo = M('Tibi')->where(['uid'=>$uid,'currency_id'=>$currency_id])
            ->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
        $str =L('_LOOK_');
        foreach ($tibiInfo as $key=>$value){
            $tibiInfo[$key]['add_time']        = date('Y-m-d H:i:s',$value['add_time']);
            $tibiInfo[$key]['num']             = number_format($value['num'],8,'.','');
            $tibiInfo[$key]['status']          = Tibistatus( $value['status'] );
            if($value['ti_id']){
                $url = $this->getCoinDetialUrl($currency_name).$value['ti_id'];
                $jump_url = '<a class=\'query_url\' href="'.$url.'" target="_blank">'.$str.'</a>';
                $tibiInfo[$key]['tibi_detial_url'] = $jump_url;
            }
        }
        $data =[
            "page"=>$page->show(),
            "list"=>$tibiInfo
        ];
        return $data;
    }
    /**
     * @author 建强 2019年3月6日 下午3:48:21
     * @param  $currency_id int 返回单条币种信息
     * @method array 
     */
    public function getCurrencyInfoById($currency_id=''){
        return M("Currency")->find($currency_id);
    }
    /**
     * @method 提币校验
     * @author 杨鹏 2019年3月4日20:18:39
     * @param  $input array
     * @param  $userinfo array
     * @return array 
     */
    public function checkTiBiParam($input,$uid){
        $userinfo = M('User')->where(['uid'=>$uid])->find();
        $data =[
            'currency_id'=>$input['currency_id'],
            'num'        =>floatval(trim($input['num'])),
        ];
        $url_index       = $input['address_index'];
        $tradePwd        = trim($input['trade_pwd']);
        $phoneCode       = trim($input['phone_code']);
        $imgCode         = trim($input['img_code']);
        $collierFee      = floatval(trim($input['collier_fee']));
        //验证当前用户账号状态
        if($userinfo['status'] ==-2){
            return ['status'=>40001,'msg'=>L('_ZHFXLXPT_')];
        }
        if($userinfo['status']==-1){
            return ['status'=>40002,'msg'=>L('_NDZHBSD_')];
        }
        //基本判断 验证控制
        if(empty($data['num']) || $data['num'] == 0){
            return ['status'=>40003,'msg'=>L('_TBZJSRBNWK_')];//提币资金输入不能为空
        }
        //判断旷工费 
        if(empty($collierFee) || $collierFee < 0.001 || !is_numeric($collierFee) ){
            return ['status'=>40004,'msg'=>L('_KGFBNXY_')];//旷工费填写错误
        }
        if(bcsub($data['num'],$collierFee,3) < 0.001 ){
            return ['status'=>40005,'msg'=>L('_KGFBNCGTBSL_')];//提币数量太小
        }
        if(!regex($data['num'],'double') ){
            return ['status'=>40006,'msg'=>L('_TBSLGSBZQ_')];//提币数量格式不正确
        }
        if(empty($tradePwd)){
            return ['status'=>40007,'msg'=>L('_ZJMMBNWK_')];//资金密码不能为空
        }
        //图片验证码
        if(empty($imgCode)){
            return ['status'=>40028,'msg'=>L('_TXYZMBNWK_')];//图片验证码不能为空
        }
        
        
        $verify = new \Common\Api\VerifyApi();
        $bool   = $verify->check($imgCode);
        if(empty($bool)){
            return ['status'=>40028,'msg'=>L('_TXYZMCW_')];//图片验证码不能为空
        } 
        //验证图片验证码是否正确 
        if(empty($phoneCode)){
            return ['status'=>40008,'msg'=>L('_SJYZMBNWK_')];//手机验证码不能为空
        }
        //提币地址后缀索引
        if(!in_array($url_index, [1,2,3]) || empty($data['currency_id'])){
            return ['status'=>40009,'msg'=>L('_FEIFAQQ_')];
        }
        $currencyInfo = $this->getCurrencyInfoById($data['currency_id']);
        //币种是否正常上线(币种维护，关闭提币)
        if($currencyInfo['close_carry']==1 || $currencyInfo['maintain_currency']==1 ){
            return ['status'=>40010,'msg'=>L('_BZWHZBZBYXCZ_')]; 
        }
        //提币条件判断
        $where= ['uid'=>$uid,'currency_id'=>$data['currency_id']];
        $user_currency_info = M('UserCurrency')->where($where)->find();
        $add_time = $user_currency_info['url_date'.$url_index];
        //验证地址是否在24小时之内
        if(time()-$add_time<24*60*60){
            return ['status'=>40011,'msg'=>L('_GDZWCGYTBNYYTB_')];
        }
        //判断提币的资金密码 
        $publicFunObj = new PublicFunctionController();
        $pwd_status = $publicFunObj->checkUserTradePwdMissNum($uid,$input['trade_pwd']);

        if($pwd_status['code'] != 200){
            return(['status'=>40012,'msg'=>$pwd_status['msg']]);
        }
        //转出数量是否超过单天最大数量   单笔最小转币数量判断 建强 2019年2月28日
        $tibiConfig = Tibi::CheckTibiConfigNum($uid,$userinfo['level'],$data['currency_id'],$data['num']);
        //没有配置提币数据 不允许提币
        if($tibiConfig['code']!=200){
            return(['status'=>40013,'msg'=>$tibiConfig['msg']]);
        }
        //手机验证码
        $res = checkSmsCode($uid, $userinfo['phone'], 'TIBI', trim($input['phone_code']));
        if(empty($res)){
            return ['status'=>40014,'msg'=>L('_SJYZMBZQ_')];//手机验证码不正确
        }
        //返回值
        return [
                'status'  => 200,'msg'=>'',
                'tibi_url'=> $user_currency_info['my_mention_pack_url'.$url_index],
                'num'     => $user_currency_info['num'],
                'phone'   => $userinfo['phone']
        ];
    }
}