<?php
/**
 *前台FAQ标题内容控制器
 * @author fuwen
 * @date 2017年11月29日
 * @Time:21:19:29

 */

namespace Home\Controller;
use Think\Controller;
use Think\Exception;
use Home\Tools\NewPage;
class QuestionController extends Controller {
/**
 * @$lang_content_arr:FAQ内容字段可选数组
 * @$lang_title_arr：FAQ标题字段可选数组
 */
    private  $lang_content_arr = array(
        'zh-cn' => 'zh-cn-content',
        'zh-tw' => 'zh-tw-content',
        'en-us' => 'en-us-content',
    );
    private  $lang_title_arr = array(
        'zh-cn' => 'zh-cn-title',
        'zh-tw' => 'zh-tw-title',
        'en-us' => 'en-us-title',
    );
    //默认中文字体
    private  $langSet = 'zh-cn';
    /**
     * 父类构造函数每次先执行
     * 判断cookie('think_language')是否已存在，
     * 读取session中think_language值选择相应的语言显示
     */
    public function __construct() {
        parent::__construct();
        if(!empty(cookie('think_language'))){
             $this->langSet =  cookie('think_language');
        }else{
           $this->langSet =  C('DEFAULT_LANG');
        } 
    }
    /**
     * FAQ主頁面展示
     * @根据父id（pid=0）查询FaqTitle表中主标题以及主标题下面的副标题
     * @限制每个主标题下输出6条副标题
     * $title_search:FAQ标题变量
     * $faq_p_list:主标题
     * $faq_c_title_list：副标题
     */
    public function index(){
        $uid = getUserId();
        $isBack = 0;
        if($uid){
            $isBack = 1;
        }
        $p_where['pid'] = 0;
        $title_search = $this->lang_title_arr[$this->langSet];
        $faq_p_list = M('FaqTitle')
                ->field('`'.$title_search.'` as title,id')
                ->where($p_where)
                ->order('order_number asc')
                ->select();
        foreach ($faq_p_list as $key => $item ){
            $c_where['pid'] = $item['id'];
            $faq_c_title_list = M('FaqTitle')
                    ->field('`'.$title_search.'` as title,id')
                    ->where($c_where)
                    ->limit(6)
                    ->select();
            $faq_p_list[$key]['data'] = $faq_c_title_list;
            $faq_p_list[$key]['count'] = M('FaqTitle')
                ->where($c_where)
                ->count();
        }
        $this->assign('list',$faq_p_list);
        $this->assign('is_back',$isBack);
        $this->display();
    }

    /**
     * 展示主标题及下属副标题内容
     * @auther:wangfuw
     * @date：2017年11月30日
     * @time:14:59:38
     */
    public function getFaqWholeTitle(){
        $uid = getUserId();
        $isBack = 0;
        if($uid){
            $isBack = 1;
        }
        $id  =  I('id');
        $where['pid'] = $id;
        $model = M('FaqTitle');
        $count = $model->where($where)->count();
        $page = new NewPage($count,6);
        $title_search = $this->lang_title_arr[$this->langSet];
        $faq_p_title = $model->field('`'.$title_search.'` as title,id as pid')
                        ->where(['id'=>$id])
                        ->find();
        $faq_c_title_list = $model
            ->field('`'.$title_search.'` as title,id')
            ->where($where)
            ->limit($page->firstRow,6)
            ->order('add_time desc')
            ->select();
        $this->assign('title',$faq_p_title);
        $this->assign('list',$faq_c_title_list);
        $this->assign('page',$page->show());
        $this->assign('is_back',$isBack);
        $this->display();
    }


    /**
     * $pid:每个副标题的父id，返回/getFaqWholeTitle
     * $title_search :FAQ标题
     * $content_search：FAQ内容
     */
    public function showFaqContent(){
        $uid = getUserId();
        $isBack = 0;
        if($uid){
            $isBack = 1;
        }
        $id     =   I('id');//187
        $pid    = M('FaqTitle')->where(['id'=>$id])->find()['pid'];
        $title_search     = $this->lang_title_arr[$this->langSet];
        $content_search   = $this->lang_content_arr[$this->langSet];
        $where['id']  = array('neq',$id);
        $where['pid'] = array('eq',$pid);
        $faq_title_list = M('FaqTitle')
            ->field('`'.$title_search.'` as title,id')
            ->where($where)
            ->order('add_time desc')
            ->limit(5)
            ->select();
        $p_title = M('FaqTitle')->field('`'.$title_search.'` as title')->where(['id'=>$pid])->find();
        $faq_title_info   = M('FaqTitle')
                ->field('`'.$title_search.'` as title,id')
                ->where(['id'=>$id])
                ->find();
        $faq_title_info['data'] = M('FaqContent')
                ->field('`'.$content_search.'` as title_content,add_time')
                ->where(['title_id'=>$id])
                ->find();
        $this->assign('id',$pid);
        $this->assign('p_title',$p_title['title']);
        $this->assign('list_title',$faq_title_list);
        $this->assign('list',$faq_title_info);
        $this->assign('is_back',$isBack);
        $this->display();
    }

    /**
     *js獲取最近查看文章
     */
    public function getContentByIds(){
        if( IS_AJAX ){
            $ids = I('ids');
            $title_search = $this->lang_title_arr[$this->langSet];
            $temp = [];
            foreach ($ids as $k=>$id){
                $temp[$k] = M('FaqTitle')->where(['id'=>$id])->field('`'.$title_search.'` as title,id')
                    ->find();
            }
            $this->ajaxReturn($temp);
        }
    }
}