<?php
/**
 * 会员中心信息控制器
 */
namespace app\user\controller;

use cmf\controller\UserBaseController;
use think\Db;
use think\Validate;
use think\Cache;
use app\user\model\MessageModel;
class MessageController extends UserBaseController
{

  function _initialize()
  {
      parent::_initialize();
  }
  public function index()
  {
    // 获取消息类别
    $userid= cmf_get_current_user_id();

    $type=Db::name('message_type')->select();
    $this->assign('type',$type);
    $where['userid']=$userid;
    $where['type_id']=$this->request->param('type');
    $list=Db::name('user_message')->alias('a')
                                  ->join('__MESSAGE__ m','a.mess_id = m.id')
                                  ->field('a.id,a.mess_id,a.status,m.type_id,m.inputtime,m.title')
                                  ->where($where)
                                  ->paginate();
    $this->assign("list", $list);
    $this->assign('page', $list->render());
    return $this->fetch();
  }
  // 消息详情
  public function detail()
  {
    $id=$this->request->param('id');
    $mess_id=$this->request->param('mess_id');
    // 更改消息 未读状态
    Db::name('user_message')->where(['id'=>$id])->setField('status',1);
    $model=new MessageModel();
    $info=$model->where(['id'=>$mess_id])->find();
    $this->assign('info',$info);
    return $this->fetch();
  }
}
