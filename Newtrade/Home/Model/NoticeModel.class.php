<?php
/**
 * user模型类
 * @author lirunqing 2017-9-27 18:03:21
 */
namespace Home\Model;
use Think\Model;
use Home\Tools\Page;
class NoticeModel extends Model {
	protected $tableName = 'notice_new';

	/**
	 * 获取公告内容
	 * @author zhanghaNwen 2017年10月11日11:07:42
	 * @param  string $notice_id 公告id
	 * @return array
	 */
	public function getNoticeForId($notice_id, $fields ){
		return $this->where(['id'=>$notice_id])->field($fields)->find();
	}

	/**
     * 公告列表
     * @author zhanghanwen 2017年10月11日11:18:21
     * @param array $where
     * @param int   $limit
     */
	public function getNoticeList( $where = null, $limit = 10 ,$fidlds = null ){
        $count = $this->where($where)->count();
        $page  = new Page($count,$limit);
        $show  = $page->show();
//        setPageParameter( array( 'notice_id'=>I('notice_id') ),[] ); // 将某一个选中的新闻id 放入其中
        if( $fidlds ){
            $list        = $this->where($where)->order('add_time desc')->field( $fidlds )->limit($page->firstRow.','.$page->listRows)->select();
        } else{
            $list        = $this->where($where)->order('add_time desc')->limit($page->firstRow.','.$page->listRows)->select();
        }
        $res['list'] = $list;
        $res['page'] = $show;
        return $res;
    }

    public function getLastNotice( $fields ){
	   return $this->order('id desc')->field( $fields )->find();
    }
}