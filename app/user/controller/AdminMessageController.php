<?php

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\validate;
use app\user\model\MessageModel;


class AdminMessageController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();

    }
    public function index()
    {

      $where=" 1 =1";
      if($this->request->param('typeid') && $this->request->param('typeid') !=0)
      {
        $typeid=$this->request->param('typeid');
        $where.= " and a.type_id ='$typeid'";
      }
      if($this->request->param('keyword'))
      {
        $keyword=$this->request->param('keyword');
        $where.= " and a.title like '%$keyword%'";
      }
      $messageModel=new MessageModel();

      $messInfo=$messageModel->where($where)
                             ->alias('a')
                             ->join('__MESSAGE_TYPE__ b','a.type_id =b.id')
                             ->field('a.*,b.name')
                             ->order("id DESC")
                             ->paginate(10);
                             // 获取消息类别
      $typesInfo=Db::name('message_type')->select();
      $this->assign("messInfo", $messInfo);
      $this->assign("typesInfo", $typesInfo);
      return $this->fetch();
    }
    // 添加消息
    public function add()
    {
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();

        // 验证
        $is_true=Validate::make()
        ->rule('title', 'require')
        ->rule('content', 'require')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("请完整信息后提交");
        }
        if($arrData['mode'] == 0)
        {
          if(empty($arrData['groups']))
          {
              $this->error("请您选择会员组");
          }
          $arrData['userids']=implode(",",$arrData['groups']);
          unset($arrData['groups']);
        }
        if($arrData['mode'] ==1)
        {
          if(empty($arrData['userids']))
          {
              $this->error("请您选择会员");
          }
          $arrData['userids']=implode(",",$arrData['userids']);
        }
        // 写入数据库消息表
        $arrData['inputtime']=time();
        $arrData['admin_userid']=cmf_get_current_admin_id();
        $messageModel=new MessageModel();
        $messageModel->isUpdate(false)->allowField(true)->save($arrData);
        // 写入用户消息表
          if($arrData['mode'] == 0)
          {
           $userids_o= Db::name('user')->field('id')->where('user_type','=','2')->where('group_id','in',$arrData['userids'])->select();
           foreach($userids_o as $v){
             $userids[]=$v['id'];
           }
         }else
         {
           $userids=$this->request->param("userids/a");
         }
         $user_mess=array();
         foreach($userids as $k=>$v)
         {
           $user_mess[$k]['mess_id']=$messageModel->id;
           $user_mess[$k]['userid']=$v;
           $user_mess[$k]['status']=0;
         }
         Db::name('user_message')->insertAll($user_mess);
        $this->success("添加成功",url('AdminMessage/index'));
      }else
      {
        // 获取消息类别
        $typesInfo=Db::name('message_type')->select();
        // 会员组
        $groupsInfo=Db::name('user_group')->select();
        // 会员
        $usersInfo=Db::name('user')->where('user_type',2)->select();
        $this->assign('typesinfo',$typesInfo);
        $this->assign('groupsInfo',$groupsInfo);
        $this->assign('usersInfo',$usersInfo);
        return $this->fetch();
      }
    }
    // 编辑消息
    public function edit()
    {
      $messageModel=new MessageModel();
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();

        // 验证
        $is_true=Validate::make()
        ->rule('title', 'require')
        ->rule('content', 'require')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("请完整信息后提交");
        }
        // if($arrData['mode'] == 0)
        // {
        //   if(empty($arrData['groups']))
        //   {
        //       $this->error("请您选择会员组");
        //   }
        //   $arrData['userids']=implode(",",$arrData['groups']);
        //   unset($arrData['groups']);
        // }
        // if($arrData['mode'] ==1)
        // {
        //   if(empty($arrData['userids']))
        //   {
        //       $this->error("请您选择会员");
        //   }
        //   $arrData['userids']=implode(",",$arrData['userids']);
        // }
        $arrData['admin_userid']=cmf_get_current_admin_id();
        $messageModel->isUpdate(true)->allowField(true)->save($arrData);
        $this->success("更新成功",url('AdminMessage/index'));
      }else
      {
        $Id=$this->request->param("id",'','intval');

        $messInfo=$messageModel->get($Id);
        // 获取消息类别
        $typesInfo=Db::name('message_type')->select();
        // 会员组
        $groupsInfo=Db::name('user_group')->select();
        // 会员
        $usersInfo=Db::name('user')->where('user_type',2)->select();
        $this->assign('typesinfo',$typesInfo);
        $this->assign('groupsInfo',$groupsInfo);
        $this->assign('usersInfo',$usersInfo);
        $this->assign("messInfo",$messInfo);
        return $this->fetch();
      }
    }
    // 查看
    public function view()
    {
      $messageModel=new MessageModel();
      $Id=$this->request->param("id",'','intval');
      $messInfo=$messageModel::get(function($query)use($Id){
        $query->alias('a')
              ->join('__MESSAGE_TYPE__ b','a.type_id =b.id')
              ->field('a.*,b.name')
              ->where("a.id",$Id);
      });
      // 会员组
      $groupsInfo=Db::name('user_group')->select();
      // 会员
      $usersInfo=Db::name('user')->where('user_type',2)->select();
      $this->assign('groupsInfo',$groupsInfo);
      $this->assign('usersInfo',$usersInfo);
      $this->assign("messInfo",$messInfo);
      return $this->fetch();

    }
    // 删除会员组
    public function delete()
    {
      $messageModel=new MessageModel();
      $Id=$this->request->param("id",0,'intval');
      if(empty($Id))
      {
        $this->error("类别ID错误");
      }else
      {
        $messageModel->destroy($Id);
        // 删除会员消息表数据
        Db::name("user_message")->where('mess_id','=',$Id)->delete();
      }
      $this->success("删除成功");
    }
}
