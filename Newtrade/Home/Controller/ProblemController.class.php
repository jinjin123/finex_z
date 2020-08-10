<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/16
 * Time: 22:01
 */
namespace Home\Controller;
use Home\Logics\CommonController;
use Home\Logics\Feedback;
use Common\Api\RedisCluster;
use Common\Api\redisKeyNameLibrary;
class ProblemController extends CommonController
{
    public $language;
    public $language_title=[
        'zh-cn'=>'zh_title',
        'zh-tw'=>'tw_title',
        'en-us'=>'en_title'
    ];

    private $dbInfo = [];

    public function __construct()
    {
        parent::__construct();
        $this->language = cookie('think_language')?cookie('think_language'):'zh-tw';
        $this->__getDbInfo();//获取跨库数据库名称及前缀
    }

    /**
     * 获取跨库数据库名称及前缀
     * @author lirunqing 2018-09-19T15:45:44+0800
     * @return null
     */
    private function __getDbInfo(){
        $this->dbInfo['db_prefix'] = C("DB_CONFIG2_PREFIX");
        $this->dbInfo['db_name'] = C("DB_CONFIG2");
    }

    /**
     * 获取图片地址，防止暴露上传路径地址
     * @author lirunqing 2019-05-10T14:20:03+0800
     * @return string
     */
    public function getImg(){
        $imgName   = I('get.img_name');

        if (empty($imgName)) {
            return $this->display('/Public/404');
        }

        $imgPath   = think_decrypt($imgName);
        $imgArr    = explode('.', $imgPath);
        $imgPath   = '.'.$imgPath;
        $imgSource = file_get_contents($imgPath, true);
        if (empty($imgSource)) {
            return $this->display('/Public/404');
        }

        $headerStr = "Content-Type: image/".$imgArr[1].";text/html; charset=utf-8";
        header($headerStr);
        echo $imgSource;
    }


    /**
     * 追问列表
     * @author Kevinlee 2019-05-06T12:19:14+0800
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function showUnResponse($id){
        if(!$id){
           return redirect(U('Problem/ProblemList'));
        }

        $dataOriginal = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->alias('m')
            ->where(['id'=>$id])
            ->find();

        if (empty($dataOriginal)) {
            return redirect(U('Problem/ProblemList'));
        }

        if ($dataOriginal['status'] == 3) {
            return redirect(U('Problem/showResponse/id/'.$id));
        }

        $upWhere = [
            'f_id' => $id,
            'type' => 1,
        ];

        $upData = [
            'is_read' => 1
        ];
        // 管理员回复用户，用户已读
        $upRes = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where($upWhere)->save($upData);

        $feedWhere = [
            'id' => ['in' , [$dataOriginal['f_pid'], $dataOriginal['f_cid']]]
        ];
        $feedData = M('Feed', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where($feedWhere)->select();
        $feedArr = [];
        foreach ($feedData as $key => $value) {
            $feedArr[$value['id']] = $value;
        }
        $data_admins = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->order('add_time ASC')
            ->where(['f_id'=>$id])
            ->select();
        $dataOriginal['username'] = $this->userInfo['username'];

        $language = $this->language;

        $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['zh_title'];
        $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['zh_title'];
        if ($language == 'zh-tw') {
            $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['tw_title'];
            $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['tw_title'];
        }
        if ($language == 'en-us') {
            $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['en_title'];
            $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['en_title'];
        }


        $num = 0;
        foreach ($data_admins as $key => $value) {
            $value['img_list'] = !empty($value['images']) ? explode(',', $value['images']) : [];

            foreach ($value['img_list'] as $k => $val) {
                $str = explode('/', $val);
                $imgEncrypt = think_encrypt($val);
                $temp['img_url'] = '/Problem/getImg.html?img_name='.$imgEncrypt;
                $temp['img_name'] = $str['5'];
                $value['img_arr'][$k] = $temp;
            }
            unset($value['img_list']);

            if ($value['type'] == 2) {
                $num ++;
            }

            $data_admins[$key] = $value;
        }

        $dataOriginal['img_list'] = [];
        if (!empty($dataOriginal['images'])) {

            $imgArr = explode(',', $dataOriginal['images']);
            $imgList = [];
            foreach ($imgArr as $key => $value) {
                $str = explode('/', $value);
                $imgEncrypt = think_encrypt($value);

                $temp1['img_url'] = '/Problem/getImg.html?img_name='.$imgEncrypt;
                $temp1['img_name'] = $str['5'];
                $imgList[] = $temp1;
            }
            $dataOriginal['img_list'] = $imgList;
        }

        $isQuestion = ($num >= 1) ? 1 : 0;

        $dataOriginal['order_num'] = !empty($dataOriginal['order_num']) ? $dataOriginal['order_num'] : null;
        
        $this->assign('id',$id);
        $this->assign('is_question', $isQuestion);
        $this->assign('data',$data_admins);
        $this->assign('data_original',$dataOriginal);

        $this->display();
    }

    /**
     * 查看客服回复
     * yangpeng 2018年7月18日17:11:28
     */
    public function showResponse($id){
        if(!$id){
           return redirect(U('Problem/ProblemList'));
        }

        $dataOriginal = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->alias('m')
            ->where(['id'=>$id])
            ->find();

        if (empty($dataOriginal)) {
            return redirect(U('Problem/ProblemList'));
        }

        if ($dataOriginal['status'] != 3) {
            return redirect(U('Problem/showUnResponse/id/'.$id));
        }

        $feedWhere = [
            'id' => ['in' , [$dataOriginal['f_pid'], $dataOriginal['f_cid']]]
        ];
        $feedData = M('Feed', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where($feedWhere)->select();
        $feedArr = [];
        foreach ($feedData as $key => $value) {
            $feedArr[$value['id']] = $value;
        }
        $data_admins = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->order('add_time ASC')
            ->where(['f_id'=>$id])
            ->select();
        $dataOriginal['username'] = $this->userInfo['username'];

        $language = $this->language;

        $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['zh_title'];
        $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['zh_title'];
        if ($language == 'zh-tw') {
            $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['tw_title'];
            $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['tw_title'];
        }
        if ($language == 'en-us') {
            $dataOriginal['first_name'] = $feedArr[$dataOriginal['f_pid']]['en_title'];
            $dataOriginal['last_name'] = $feedArr[$dataOriginal['f_cid']]['en_title'];
        }

        foreach ($data_admins as $key => $value) {
            $value['img_list'] = !empty($value['images']) ? explode(',', $value['images']) : [];

            foreach ($value['img_list'] as $k => $val) {
                $str = explode('/', $val);
                $imgEncrypt = think_encrypt($val);
                $temp['img_url'] = '/Problem/getImg.html?img_name='.$imgEncrypt;
                $temp['img_name'] = $str['5'];
                $value['img_arr'][$k] = $temp;
            }
            unset($value['img_list']);

            $data_admins[$key] = $value;
        }

        $dataOriginal['img_list'] = [];
        if (!empty($dataOriginal['images'])) {

            $imgArr = explode(',', $dataOriginal['images']);
            $imgList = [];
            foreach ($imgArr as $key => $value) {
                $str = explode('/', $value);
                $imgEncrypt = think_encrypt($value);

                $temp1['img_url'] = '/Problem/getImg.html?img_name='.$imgEncrypt;
                $temp1['img_name'] = $str['5'];
                $imgList[] = $temp1;
            }
            $dataOriginal['img_list'] = $imgList;
        }

        $dataOriginal['order_num'] = !empty($dataOriginal['order_num']) ? $dataOriginal['order_num'] : null;

        $this->assign('data',$data_admins);
        $this->assign('data_original',$dataOriginal);
        $this->display();
    }
    /**
     * 问题反馈
     * yangpeng  2018年7月16日22:08:13
     *
     */
    public function showProblem(){

        $field    = $this->language_title[$this->language];
        $redis    = RedisCluster::getInstance();
        $userId   = getUserId();
        $problems = $redis->get(redisKeyNameLibrary::PC_FEEDBACK_PROBLEM_LIST.$userId);
        $language = $redis->get(redisKeyNameLibrary::PC_FEEDBACK_PROBLEM_LIST_LANGUAGE.$userId);
        $problems = unserialize($problems);
        if ($field != $language || empty($problems)) {
           $problems = M('Feed', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where(['pid'=>0,'status'=>1])->field("id,$field as title")->select();
           $problemsString = serialize($problems);
           $redis->setex(redisKeyNameLibrary::PC_FEEDBACK_PROBLEM_LIST.$userId, 300, $problemsString);
           $redis->setex(redisKeyNameLibrary::PC_FEEDBACK_PROBLEM_LIST_LANGUAGE.$userId, 300, $field);
        }
        
        $this->assign('problems',$problems);
        $this->assign('language',$this->language);
        $this->assign('user',$this->userInfo);
        $this->display();
    }

    /**
     * 异步获取二级标题
     * yangpeng 2018年7月18日11:59:58
     */

    public function ajaxGetProblemTitle($id){
        if(!$id || !IS_AJAX){
            $this->ajaxReturn(['status'=>500,'info'=>L('_FWQFM_')]);
        }
        $field = $this->language_title[$this->language];
        $data = M('Feed', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where(['pid'=>$id,'status'=>1])->field("id,$field as title")->select();
        if(!empty($data)){
            $this->ajaxReturn(['status'=>200,'info'=>$data]);
        }
            
        $this->ajaxReturn(['status'=>201,'info'=>L('_FWQFM_')]);
    }

    /**
     * @method 获取问题反馈得提示信息
     * @author yangpeng 2018年11月16日14:48:18
     * @param string $id
     */
    public function ajaxGetProblemDetialById($id=''){
        if(!$id) $this->ajaxReturn(['status'=>203,'info'=>L('_FWQFM_')]);
        $this->ajaxReturn(['status'=>200,'info'=>problemDetial($id)]);
    }

    /**
     * 获取ajax获取问题列表
     * @author lirunqing 2018-09-19T16:53:31+0800
     * @param  int $uid   用户id
     * @param  int $type  展示问题列表类型，1表示未解决，2表示解决
     * @param  string $field 查询显示字段
     * @return json
     */
    private function problemListIsAjax($uid, $type, $field){ 

        // 工单状态转换
        $typeArr = [
            '1' => [1,2,4],
            '2' => [3]
        ];
        $newType = $typeArr[$type];

        $feedbackWhere = [
            'fe.uid'=>$uid, 
            'fe.source' =>1, 
            'fe.status'=> ['in', $newType]
        ];


        // 获取3个月前的的问题反馈不显示
        $threeMonth = strtotime("-0 year -3 month -0 day");
        $feedbackWhere['fe.add_time'] = ['egt', $threeMonth];

        $count = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->alias('fe')
            ->field("fe.*,fd.$field as first_title,ft.$field as last_title")
            ->where($feedbackWhere)
            ->join('left join __FEED__ as fd on fe.f_pid = fd.id')
            ->join('left join __FEED__ as ft on fe.f_cid = ft.id')
            ->count();

        $page = new \Home\Tools\AjaxPage($count,4,'getProblemList','',$type);
        $show = $page->show();
        $data = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])
            ->alias('fe')
            ->field("fe.*,fd.$field as first_title,ft.$field as last_title,fd.status f_status,ft.status l_status")
            ->where($feedbackWhere)
            ->order('fe.id desc')
            ->join('left join __FEED__ as fd on fe.f_pid = fd.id')
            ->join('left join __FEED__ as ft on fe.f_cid = ft.id')
            ->limit($page->firstRow,4)
            ->select();

        $tempArr = [];
        foreach($data as $key=> $value){
            $tempArr[] = $value['id'];

            $value['order_num'] = !empty($value['order_num']) ? $value['order_num'] : null;
            $data[$key] = $value;
            // 1表示未关闭工单，2表示已关闭工单
            $data[$key]['type'] = 1;
            if ($value['status'] == 3) {
                $data[$key]['type'] = 2;
            }

            $data[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data[$key]['is_answer'] = 0;
            

//            //
//            if($value['l_status']!=1){
//                unset($data[$key]['last_title']);
//            }
        }

        if (!empty($tempArr)) {

            $fIdStr = implode(',', $tempArr);
            // 查询工单客服人员是否回复,先对回答列表按条件筛选并且倒序排序，然后再对先前结果进行分组
            // 这样就能获取最新的追问或者回复，来判断当前工单客服是否最新回复
            $sql = 'SELECT * FROM (SELECT * FROM work_feedback_answer WHERE f_id in ('.$fIdStr.') ORDER BY id desc ) a  GROUP BY a.f_id';
            $answerList = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->query($sql);

            // $answerWhere = [
            //     'f_id' => ['in', $tempArr]
            // ];
            // $answerList = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->order('id desc')->where($answerWhere)->select();

            $answerTemp = [];
            foreach ($answerList as $key => $value) {
                $answerTemp[$value['f_id']] = $value;
            }

            // is_answer 0表示未回复，1表示已回复，2表示已读回复
            foreach($data as $key => $value){
                $answerTempVal = $answerTemp[$value['id']];
                if ($answerTempVal['type'] == 1 && $answerTempVal['is_read'] == 0) {
                    $data[$key]['is_answer'] = 1;
                }else if ($answerTempVal['type'] == 1 && $answerTempVal['is_read'] == 1) {
                    $data[$key]['is_answer'] = 2;
                }else{
                    $data[$key]['is_answer'] = 0;
                }

                // $data[$key]['is_answer'] = ($type == 1) ? 1 : 0;
            }
        }

        return ['data' => $data, 'page' => $show, 'code' =>200];
        // $this->ajaxReturn(['status'=>200,'msg'=>'没有数据', 'list' => $data, 'page' => $show]);
    }

    /**
     * 问题列表
     * yangpeng  2018年7月16日22:08:13
     *
     */
    public function ProblemList(){
        $uid = getUserId();
        $type = I('type')?I('type'):1;//未解决

        
        $field = $this->language_title[$this->language];


        $proRes = $this->problemListIsAjax($uid, $type, $field);
        if(IS_AJAX){
            $this->ajaxReturn(['status'=>$proRes['code'],'msg'=>'没有数据', 'list' => $proRes['data'], 'page' => $proRes['page']]);
        }

        $this->assign('list',$proRes['data']);
        $this->assign('page',$proRes['page']);
        $this->display();

    }

    /**
     * 客户问题反馈后追问
     * yangpeng 2018年7月18日17:49:39
     */
    public function reTalkWithme(){
        $data = I('post.');
        $time= time();
        $format_time =date('Y-m-d H:i:s',$time);

        if(empty($data['id']) || empty($data['answer'])){
            $this->ajaxReturn(['status'=>500,'time'=>$format_time,'info'=>L('_FWQFM_')]);
        }
        if(strlen($data['answer'])>600){//2018年11月9日14:56:55  yangpeng 添加
            $this->ajaxReturn(['status'=>201,'time'=>$format_time,'info'=>L('_MSTOLONG_')]);
        }
        $feedbackInfo = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where(['id' => $data['id']])->find();

        // 工单已经关闭不能追问 add by lirunqing2018年10月23日10:40:44
        if ($feedbackInfo['status'] == 3) {
            $this->ajaxReturn(['status'=>204,'time'=>$format_time,'info'=>L('_CZSBQSHZS_')]);
        }

        $data_file_path = $this->checkFileData();
        if(!$data_file_path['status'] )$this->ajaxReturn(['status'=>201,'info'=>L('_tpscsb_')]);


        $images = !empty($data_file_path['data']) ? implode(',', $data_file_path['data']) : 0;

        //15分钟之内只能追问一次
        $where=[
            'f_id'=>$data['id'], 
        ];
        $Last_data = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where($where)->order('id desc')->find();
        if($time-$Last_data['add_time']<15*60  && $Last_data['type'] ==2)
        {
            $this->ajaxReturn(['status'=>203,'time'=>$format_time,'info'=>L('_ZKFHFHQFFZZNF_')]);
        }

        $answerCount = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where(['f_id'=> $data['id'], 'type' => 2])->count();

        if ($answerCount >= 1) {
            $this->ajaxReturn(['status'=>205,'time'=>$format_time,'info'=>L('_CZSBQSHZS_')]);
        }

        $sub_data['uid'] = getUserId();
        $sub_data['f_id'] = $data['id'];
        $sub_data['answer'] = $data['answer'];
        $sub_data['type'] = 2;
        $sub_data['add_time'] = $time;
        $sub_data['images'] = $images;
        
        $res = M('FeedbackAnswer', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->add($sub_data);
        $update_time = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->where(['id'=>$data['id']])->setField('update_time',time());
        if(!$res && !$update_time){
           $this->ajaxReturn(['status'=>202,'time'=>$format_time,'info'=>L('_FWQFM_')]);
        }

        $imgListTemp = !empty($images) ? explode(',', $images) : [];
        $imgList = [];
        if (!empty($imgListTemp)) {
             foreach ($imgListTemp as $key => $value) {
                $str = explode('/', $value);
                $imgEncrypt = think_encrypt($value);

                $temp['img_url'] = '/Problem/getImg.html?img_name='.$imgEncrypt;
                $temp['img_name'] = $str['5'];
                $imgList[] = $temp;
            }
        }

        $returnData = [
            'answer'   => trim($data['answer']),
            'img_list' => $imgList
        ];
        $this->ajaxReturn(['status'=>200,'time'=>$format_time, 'msg'=>L('_CZCG_'),'info'=>$returnData]);
    }
    
    /**
     * 问题反馈提交
     * yangpeng  2018年7月16日22:09:32
     */
    public function sub_Problem(){
        $uid = getUserId();
        $data = I('post.');
        //表单数据检测
        $checkRes = $this->checkData($data);
        // 防止用户重复提交 add by lirunqing 2018年10月31日15:03:38
        // $redisObj = new RedisCluster();
        $redisClientObj = RedisCluster::getInstance();
        $isSub = $redisClientObj->get(redisKeyNameLibrary::PC_FEEDBACK.$uid);
        if (!empty($isSub)) {
            $this->ajaxReturn(['status'=>201,'info'=>L('_QWCFCZ_')]);
        }

        //文件数据检测
        $data_file_path = $this->checkFileData();
        if(!$data_file_path['status'] )$this->ajaxReturn(['status'=>201,'info'=>L('_tpscsb_')]);

        $images = !empty($data_file_path['data']) ? implode(',', $data_file_path['data']) : 0;
        $time = time();
        $om = !empty($data['telCode']) ? $data['telCode'].'-' : '';
        
        //组装数据
        $sub_data['uid']       = $uid ;
        $sub_data['f_pid']     = $data['m_title'];
        $sub_data['f_cid']     = $data['v_title'];
        $sub_data['describe']  = $data['describe'];
        $sub_data['images']    = $images;
        $sub_data['add_time']  = $time;
        $sub_data['order_num'] = !empty($data['ex_orderNumber']) ? $data['ex_orderNumber'] : '0';
        $sub_data['order_id']  = !empty($checkRes['order_id']) ? $checkRes['order_id'] : '0';
        $sub_data['status']    = 1;
        $sub_data['level_id']  = 4;
        // $sub_data['type']   = $data['m_title'];
        $sub_data['ex_email']  = $data['ex_email'];
        $sub_data['ex_phone']  = $data['ex_phone']?$om.$data['ex_phone']:"";
        $sub_data['language']  = cookie('think_language')?cookie('think_language'):'zh-cn';
        $res                   = M('Feedback', $this->dbInfo['db_prefix'], $this->dbInfo['db_name'])->add($sub_data);

        if($res){
            $redisClientObj->setex(redisKeyNameLibrary::PC_FEEDBACK.$uid, 5, 1);
            $this->ajaxReturn(['status'=>200,'info'=>L('_CZCG_'), 'data'=> ['id' => $res]]);
        }

        $this->ajaxReturn(['status'=>201,'info'=>L('_CZSB_')]);
    }

    /**
     * 问题反馈提交数据检测
     * yangpeng 2018年7月16日22:28:53
     */
    private function checkData($data){
        if(!$data){
            $this->ajaxReturn(['status'=>201,'info'=>L('_SRSJBNWK_')]);
        }
        if(!$data['m_title'] ){//主标题
            $this->ajaxReturn(['status'=>201,'info'=>L('_ZBTBNWK_')]);
        }
        if(!$data['v_title'] || $data['v_title']==-1){//副标题
            $this->ajaxReturn(['status'=>201,'info'=>L('_FBTBNWK_')]);
        }
        if(!trim($data['describe'])){//描述
            $this->ajaxReturn(['status'=>201,'info'=>L('_MSBNWK_')]);
        }
        if(strlen($data['describe'])>600){//描述
            $this->ajaxReturn(['status'=>201,'info'=>L('_MSTOLONG_')]);
        }
        if($data['ex_email'] && !regex($data['ex_email'],'email')){
            $this->ajaxReturn(['status'=>201,'info'=>L('_YXGSCW_')]);
        }
        if($data['ex_phone'] && !regex($data['ex_phone'],'Feedphone')){
            $this->ajaxReturn(['status'=>201,'info'=>L('_SJGSBZQ_')]);
        }

        if (in_array($data['v_title'], [27,28]) && empty($data['ex_orderNumber'])) {
            // $this->ajaxReturn(['status'=>201,'info'=>'订单号不能为空']);
            $this->ajaxReturn(['status'=>201,'info'=>L('_DDHBNWK_')]);
        }

        // 判断p2p交易订单号是否存在
        if (in_array($data['v_title'], [27]) && !empty($data['ex_orderNumber'])) {
            $tradeWhere = [
                'order_num' => ['like', '%'.$data['ex_orderNumber'].'%']
            ];
            $res = M('tradeTheLine')->where($tradeWhere)->find();

            if (empty($res)) {
                $this->ajaxReturn(['status'=>201,'info'=>L('_GDDBCZ_')]);
            }

            $data['order_id'] = $res['id'];
        }

        // 判断c2c交易订单号是否存在
        if (in_array($data['v_title'], [28]) && !empty($data['ex_orderNumber'])) {
            $ccTradeWhere = [
                'order_num_buy|order_num' => $data['ex_orderNumber']
            ];
            $res = M('ccTrade')->where($ccTradeWhere)->find();

            if (empty($res)) {
                $this->ajaxReturn(['status'=>201,'info'=>L('_GDDBCZ_')]);
            }

            $data['order_id'] = $res['id'];
        }

        return $data;
    }

    /**
     * 问题反馈提交文件类型数据检测
     * yangpeng 2018年7月16日22:28:53
     * @return array  上传图片的路径
     */
    private function checkFileData(){
        $arr = array(
            'status'=>true,
            'msg'=>'',
            'data'=>[],
        );

        if($_FILES['image_one']['error']) $arr['status']=false;
        if($_FILES['image_two']['error']) $arr['status']=false;
        if($_FILES['image_three']['error']) $arr['status']=false;

        if(!empty($_FILES['image_one']) && $_FILES['image_one']['size'] >0){
            $res = $this->upFile(1,'image_one',$arr['data']);
            if(!$res['status']){
                $arr['status'] =false;
                $arr['msg'] = $res['info'];
                return $arr;
            }
            $arr['data']['image_one'] = $res['info'];
        }

        if(!empty($_FILES['image_two']) && $_FILES['image_two']['size'] >0 ){
            $res = $this->upFile(2,'image_two',$arr['data']);
            if(!$res['status']) {
                $arr['status'] =false;
                $arr['msg'] = $res['info'];
                return $arr;
            }
            $arr['data']['image_two'] = $res['info'];
        }
        if(!empty($_FILES['image_three']) && $_FILES['image_three']['size'] >0){
            $res = $this->upFile(3,'image_three',$arr['data']);
            if(!$res['status']){
                $arr['status'] =false;
                $arr['msg'] = $res['info'];
                return $arr;
            }
            $arr['data']['image_three'] = $res['info'];
        }
        return $arr;
    }
    /**
     *
     */
    private function upFile($num, $name, $arr){
        $res = $this->uploadOne($name);
        if ($num == 1) {
            return $res;
        } else if ($num == 2 && $res['status'] == false) {
            //证明第二次上传失败
            unlink($_SERVER['DOCUMENT_ROOT'] . $arr['image_one']);
        }else if($num == 3 && $res['status'] == false){
            //证明第三次上传失败
            unlink($_SERVER['DOCUMENT_ROOT'] . $arr['image_one']);
            unlink($_SERVER['DOCUMENT_ROOT'] . $arr['image_two']);
        }
        return $res;
    }
    /**
     * 问题文件上传
     * @author yangpeng 2017-10-11
     * @param string $name 传入图片的name
     * @return array
     */
    protected function uploadOne($name)
    {
        /*1、实例化上传类并初始化相关值*/
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3*1024*1024;// 设置附件上传大小3M
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Upload/Home/feedback/'; // 设置附件上传根目录
        $upload->saveName = $name . '_' . time() . '_' . rand(100000, 999999);
        /*2、上传单个文件，正确返回图片路径，错误返回错误信息*/
        $info = $upload->uploadOne($_FILES[$name]);
        if (!$info) {// 上传错误提示错误信息
            $data['info'] = $upload->getError();
            $data['status'] = false;
            return $data;
        } else {// 上传成功 获取上传文件信息
            $data['info'] = '/Upload/Home/feedback/' . $info['savepath'] . $info['savename'];
            $data['status'] = true;
            return $data;
        }
    }


    /**
     * @author 建强 2018年10月26日 上午11:14:01
     * @method 获取客服回复的最新内容  [工单为完成状态需要标注]
     *     * @desc   ajax 轮询 5s/次
     * @return string  json
     */
    public function getServiceReplyList()
    {
        $data = ['code'=>202,'msg'=>'error','data'=>''];
        if(!IS_AJAX || !IS_POST) $this->ajaxReturn($data);
        $feedIds = I('post.feedback_ids');

        if (!is_array($feedIds)) {
            $feedIds = [$feedIds];
        }

        if(empty($feedIds))
        {
            $data['code'] = 206;
            $this->ajaxReturn($data);
        }
        $FeedbackLogic = new Feedback();
        $res = $FeedbackLogic->getAnswerByFeebIds($feedIds);

        $data['msg']  ='success';
        $data['code'] = 200;
        $data['data'] = $res['data'];
        $this->ajaxReturn($data);
    }
}