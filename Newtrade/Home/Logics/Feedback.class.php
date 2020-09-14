<?php
namespace Home\Logics;

/**
 * @author 建强  2018年10月26日 
 * @desc   工单系统feedback 问题反馈表
 */
class Feedback 
{
    public $res = ['code'=>200, 'msg'=>'','data'=>''] ; 
    /**
     * @author 建强 2018年10月26日 上午11:22:59
     * @method 获取所有工单状态信息
     */
    public function getAnswerByFeebIds($ids)
    {
        if(empty($ids) || !is_array($ids)) return [];
        $where =[
            'id'=> ['IN',$ids],
            'create_by'=>0    
        ];
        $feeds =M('Feedback',C("DB_CONFIG2_PREFIX"), C("DB_CONFIG2"))
            ->field('id,status')
            ->where($where)
            ->order('id desc')
            ->select();

        if(empty($feeds)) return [];
        //获取工单的回复列表
        $feedsAnswerArr =  [];
        $answersArr = $this->getAnswerByFeedIds($ids);
        foreach($feeds as $key =>$value)
        {
            $feedsAnswerArr['answer'][$key]['id'] =(int)$value['id'];
            // if($value['status']== 3)
            // {
            //     $feedsAnswerArr['answer'][$key]['feed_status'] =1;  //工单已经完成
            //     $feedsAnswerArr['answer'][$key]['list'] =[];
            //     continue;
            // }
            $feedsAnswerArr['answer'][$key]['feed_status']= 0;  //工单已经完成
            $feedsAnswerArr['answer'][$key]['list'] =array_values($answersArr[$value['id']]) ;
        }
        
        $this->res['data'] =$feedsAnswerArr;
        return $this->res;
    }
    
    /**
     * @author 建强 2018年10月26日 上午11:32:23
     * @method 获取工单的客服回复或者追问列表
     * @return array;
     */
    protected function getAnswerByFeedIds($ids)
    {   
        $where = ['f_id' => ['IN',$ids]];
        $answers = M('FeedbackAnswer',C("DB_CONFIG2_PREFIX"), C("DB_CONFIG2"))
           ->field('f_id,answer,add_time,type')
           ->order('add_time asc')
           ->where($where)
           ->select();
        if(empty($answers)) return  [];
        //组装数据
        $answersArr  = [] ;
        $feeds = array_unique(array_column($answers, 'f_id'));
        
        $answersKeyFeedIdArr = $this->getAnswersKeyFeedId($answers);
        
        foreach($ids as $feedId)
        { 
            if(in_array($feedId, $feeds))
            {  
                $answersArr[$feedId] =$answersKeyFeedIdArr[$feedId];
                continue; 
            }
            $answersArr[$feedId] = []; //没有回复
        }
        return $answersArr;
    }
    
    /**
     * @author 建强 2018年10月26日 上午11:45:39
     * @method 组装answer回复表数据 根据feedID 
     * @return array
    */
    protected function getAnswersKeyFeedId($answers)
    {    
        $answersKeyFeedIdArr = [];
        $i = 0;
        foreach ($answers as $value)
        {
            $answersKeyFeedIdArr[$value['f_id']][$i]['add_time']  = date('Y-m-d H:i:s',$value['add_time']);
            $answersKeyFeedIdArr[$value['f_id']][$i]['answer']    = htmlspecialchars_decode($value['answer']);
            $answersKeyFeedIdArr[$value['f_id']][$i]['type_flag'] = $value['type'];
            $answersKeyFeedIdArr[$value['f_id']][$i]['type']      = ($value['type']==1)?L('_KFHF_'):L('_ZHUIWEN_');
            $i++;
        }
        return $answersKeyFeedIdArr;
    }
    
}