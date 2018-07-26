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
use app\user\model\LevelPutModel;

use think\Validate;

/**
 * 后台分销佣金提现申请记录
 * )
 */
class AdminPutForwardController extends AdminBaseController
{
  public function _initialize()
  {
      parent::_initialize();
      $this->putForwardModel=new LevelPutModel();
  }
  public function index()
  {
    $where=" 1= 1";
    $status=$this->request->param('status');
    $where.= isset($status) && is_numeric($status) ? " and a.status= '$status'" : "";
    $keyword=$this->request->param('keyword');
    $where .= !empty($keyword) ? " and u.user_login like '%$keyword%' or u.mobile = $keyword" : "";
    $list           = $this->putForwardModel
                                ->alias('a')
                                ->where($where)
                                ->field('a.*,u.user_login')
                                ->join('__USER__ u','a.userid = u.id')
                                ->paginate(10);
    $this->assign("list",$list);
    $this->assign('page', $list->render());
    return $this->fetch();
  }
  // 通过审核
  public function access()
  {
    $id=$this->request->param('id');
    $userid=cmf_get_current_user_id();
    $res=  $this->putForwardModel->isUpdate(true)->allowField(true)->save(['status'=>1,'pass_time'=>time(),'admin_userid'=>cmf_get_current_admin_id()],['id'=>$id]);
    if($res)
    {
      $info=$this->putForwardModel->where(['id'=>$id])->find();
      // 佣金提现至余额
      Db::name('user')->where(['id'=>$userid])->setInc('balance',$info['money']);
      // 将提现记录写入 交易记录
      $recordData['user_id']=$info['userid'];
      $recordData['type']=1;
      $recordData['money']=$info['money'];
      $recordData['log_name']="佣金提现至余额";
      $recordData['remarks']='';
      $recordData['time']=time();
      $recordData['admin_userid']=$info['admin_userid'];
      Db::name('record')->insert($recordData);
      $this->success("审核成功");
    }
  }
  // 拒绝审核
  public function refuse()
  {
    $id=$this->request->param('id');
    $userid=cmf_get_current_user_id();
    $res=  $this->putForwardModel->isUpdate(true)->allowField(true)->save(['status'=>2,'pass_time'=>time(),'admin_userid'=>cmf_get_current_admin_id()],['id'=>$id]);
    if($res)
    {
      $info=$this->putForwardModel->where(['id'=>$id])->find();
      // 将佣金返还给用户
      Db::name('user_commission')->where(['userid'=>$userid])->setInc('total',$info['money']);
      Db::name('user_commission')->where(['userid'=>$userid])->setDec('already_put',$info['money']);
      $this->success("已拒绝");
    }
  }
// 删除
public function delete()
{
  $param           = $this->request->param();
  // 单个删除
  if(isset($param['id']))
  {
    $id           = $this->request->param('id', 0, 'intval');
    $this->putForwardModel->where(['id'=>$id])->delete();
    $this->success("删除成功");
  }
  // 批量删除
  if(isset($param['ids']))
  {
        $ids     = $this->request->param('ids/a');
        $result=$this->putForwardModel->where(['id' => ['in', $ids]])->delete();
          if ($result)
          {
              $this->success("删除成功！", '');
          }
  }

}

}
