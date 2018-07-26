<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\user\model\UserGroupModel;
use think\Validate;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *  操作记录 、 充值  提现 ，消费
 * )
 */
class AdminRecordController extends AdminBaseController
{
  public function index()
  {
    $where="1 =1";
    $typeid=$this->request->param('typeid');
    $where .= isset($typeid) && is_numeric($typeid) ? " and type = '$typeid'" : "";
    $keyword=$this->request->param('keyword');
    $where .= isset($keyword) ? " and b.user_login like '%$keyword%'" : "";
    $list=Db::name("record")->where($where)
                      ->alias('a')
                      ->join('__USER__ b','a.user_id = b.id')
                      ->field('a.*,b.user_login')
                      ->order("id DESC")
                      ->paginate(10);
    $this->assign('list',$list);
    $this->assign('page',$list->render());
    return $this->fetch();
  }
  public function delete()
  {
    $param           = $this->request->param();
    // 单个删除
    if(isset($param['id']))
    {
      $id           = $this->request->param('id', 0, 'intval');
      Db::name("record")->where(['id'=>$id])->delete();
      $this->success("删除成功");
    }
    // 批量删除
    if(isset($param['ids']))
    {
          $ids     = $this->request->param('ids/a');
          $result=Db::name("record")->where(['id' => ['in', $ids]])->delete();
            if ($result)
            {
                $this->success("删除成功！", '');
            }
    }
  }

}
