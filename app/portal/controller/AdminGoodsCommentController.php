<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\GoodsCommentModel;
use think\Validate;

class AdminGoodsCommentController extends AdminBaseController
{
    public function __construct()
    {
      parent::__construct();
      $this->GoodsCommentModel=new GoodsCommentModel();
    }

    public function index()
    {
      // 评论内容搜索
      $where="1= 1";
      if($this->request->param('keyword'))
      {
        $keywords=$this->request->param('keyword');
        $where .=" and content like '%$keywords%'";
      }
      $goodsComment  = $this->GoodsCommentModel->where($where)->paginate();
      $this->assign("goodsComment", $goodsComment);
      $this->assign('page', $goodsComment->render());
      return $this->fetch();
    }

    // 审核评论
    public function verify()
    {
      $id=$this->request->param("id");
      $res=$this->GoodsCommentModel->save(['verify' => 1],['id'=>$id]);
      if($res)
      {
          $this->success("审核成功");
      }
    }
    // 查看评论
    public function edit()
    {

        $typeId=$this->request->param("id",'','intval');
        $commentInfo=$this->GoodsCommentModel->get($typeId);
        $this->assign("commentInfo",$commentInfo);
        return $this->fetch();

    }
    // 标签删除
    public function delete()
    {

       if($this->request->isPost())
       {
         //批量删除
           $postArr=$this->request->param();
           if(count($postArr['selectid']) < 1)
           {
               $this->error("请您至少勾选一个");
           }
          $res= $this->GoodsCommentModel->destroy($postArr['selectid']);
          if($res)
          {
            $this->success("删除成功");
          }
       }elseif($this->request->isGet())
       {
         // 单条删除
         $commId=$this->request->param('id');
         $this->GoodsCommentModel->where(['id'=>$commId])->delete();
         $this->success("删除成功");
       }
    }

}
