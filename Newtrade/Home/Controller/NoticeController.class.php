<?php
/**
 * 公告相关操作类
 * @author zhanghanwen
 * @time 2017年10月9日11:55:44
 */

namespace Home\Controller;
use Home\Model\NoticeModel;
use Think\Controller;

class NoticeController extends Controller{

    protected $noticeModel; // 定义公告类
    public function __construct(){
        parent::__construct();
        $this->noticeModel = new NoticeModel();
        $userId = getUserId();
        if( !$userId ) {
            $this->assign('plantformUrl','');
        } else{
            $this->assign('plantformUrl','<a href="'.U('UserCenter/index').'">'.L('_FHJYPT_').'</a>');
        }
    }

    /*
     * 公告列表显示页面
     * author zhanghanwen
     * @time   2017年10月9日12:16:22
     */
    public function index(){
        $this->assign( 'data',$this->noticeModel->getNoticeList() );
        $this->display();
    }

    /*
     * 公告详情显示页面
     * author zhanghanwen
     * @time   2017年10月9日12:16:22
     */
    public function details(){
        $notice_id = I('notice_id');
        $userId = getUserId();
        if(!$userId){
            $userId = 0;
        } else{
            $userId = 1;
        }
        // 根据语言选择新闻
        $languange = C('DEFAULT_LANG');
        if(!empty( $_COOKIE['think_language'] )) {
            $languange = $_COOKIE['think_language'];
        }
        $fields = "`$languange-title` as title,add_time,id";
        $noticeData = $this->noticeModel
            ->getNoticeForId(
                $notice_id ,
                "`$languange-title` as title,`$languange-content` as content,id,add_time"
            );
        $noticeData['content'] = html_entity_decode($noticeData['content'] );

            //前一条
            $isPrev = 1;
            $map['id'] = array('LT',$noticeData['id']);
            $preData = M('Notice')->where($map)->field($fields)->order('id desc')->find();

            if($preData){
                $pageData['prev'] = $preData;
                $pageData['prev']['url'] = U('Notice/details',array('notice_id'=>$preData['id']));
            }else{
                $isPrev = 0;
            }

            //后一条
            $isNext=1;
            $map['id'] = array('GT',$noticeData['id']);
            $nextData = M('Notice')->where($map)->field($fields)->order('id asc')->find();

            if($nextData){
                $pageData['next'] = $nextData;
                $pageData['next']['url'] = U('Notice/details',array('notice_id'=>$nextData['id']));
            }else{
                $isNext = 0;
            }

        $this->assign('is_prev',$isPrev);
        $this->assign('is_next',$isNext);
        $this->assign( 'is_back', $userId );
        $this->assign( 'page', $pageData );
        $this->assign( 'data', $noticeData );
        $this->display();
    }


    /**
     * @method 关于我们
     * @author 杨鹏 2019年2月22日16:38:52
     */
    public function aboutUs(){
        $this->display();
    }

    /**
     * @method 团队介绍
     * @author 杨鹏 2019年2月22日16:40:52
     */
    public function teamIntroduction(){
        $this->display();
    }



}