<?php
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * 会员提现记录申请
 */
class AdminUserPutforwardController extends AdminBaseController
{

  public function index()
  {
    $params=$this->request->param();
    $where=array();
    if(isset($params['status']))
    {
      $where['status']=$params['status'];
    }
    if(isset($params['keyword']))
    {
      $where['u.user_login']=['like',$params['keyword']];
    }
    $list           = Db::name('put_forward')
                                ->alias('a')
                                ->where($where)
                                ->field('a.*,u.user_login')
                                ->join('__USER__ u','a.userid = u.id')
                                ->paginate();
    $this->assign("list",$list);
    $this->assign('page', $list->render());
    return $this->fetch();
  }
  //申请通过
  public function  access()
  {
    $id=$this->request->param('id');
    $userid=cmf_get_current_user_id();
    $res= Db::name('put_forward')->where(['id'=>$id])->update(['status'=>1,'pass_time'=>time(),'admin_userid'=>cmf_get_current_admin_id()]);
    if($res)
    {
      $info=Db::name('put_forward')->where(['id'=>$id])->find();
      // 将提现记录写入 交易记录
      $recordData['user_id']=$info['userid'];
      $recordData['type']=1;
      $recordData['money']=$info['money'];
      $recordData['log_name']="会员申请提现";
      $recordData['remarks']='';
      $recordData['time']=time();
      $recordData['admin_userid']=$info['admin_userid'];
      Db::name('record')->insert($recordData);
      $this->success("审核成功");
    }
  }
  // 申请拒绝
  public function refuse()
  {
    $id=$this->request->param('id');
    $res= Db::name('put_forward')->where(['id'=>$id])->update(['status'=>2,'pass_time'=>time(),'admin_userid'=>cmf_get_current_admin_id()]);
    if($res)
    {
      $info=Db::name('put_forward')->where(['id'=>$id])->find();
      Db::name('user')->where(['id'=>$info['userid']])->setInc('balance',$info['money']);
      $this->success("已拒绝");
    }
  }
}


?>
