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


class AdminUserGroupController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->userGroupModel=new UserGroupModel();
    }
    public function index()
    {
      $userGroups           = $this->userGroupModel->paginate();
      $this->assign("userGroups", $userGroups);
      $this->assign('page', $userGroups->render());
      return $this->fetch();

    }
    // 添加会员级别
    public function add()
    {
      if($this->request->isPost())
      {
        // 验证数据
        $data=$this->request->param();
        $result = $this->validate($data, 'UserGroup');
        if ($result !== true) {
            $this->error($result);
        }else
        {
          // 数据入库
          $this->userGroupModel->isUpdate(false)->allowField(true)->save($data);
          $this->success("添加成功",url('AdminUserGroup/index'));
        }
      }else
      {
        return $this->fetch();
      }
    }
    // 编辑会员级别
    public function edit()
    {
      if($this->request->isPost())
      {
        // 验证数据
        $data=$this->request->param();
        $result = $this->validate($data, 'UserGroup');
        if ($result !== true) {
            $this->error($result);
        }else
        {
          $this->userGroupModel->isUpdate(true)->allowField(true)->save($data);
          $this->success("更新成功",url("AdminUserGroup/index"));
        }
      }else
      {
        $groupId=$this->request->param('id');
        $groupInfo=$this->userGroupModel->get($groupId);
        $this->assign('groupinfo',$groupInfo);
        return $this->fetch();
      }
    }
    // 删除会员组
    public function delete()
    {
      $groupId=$this->request->param("id",0,'intval');
      if(empty($groupId))
      {
        $this->error("类别ID错误");
      }else
      {
          $this->userGroupModel->where(['id'=>$groupId])->delete();
      }
      $this->success("删除成功");
    }
    // 启用折扣
    public function open()
    {
      $id=$this->request->param('id');
      $res=  $this->userGroupModel->isUpdate(true)->allowField(true)->save(['is_show'=>1],['id'=>$id]);
      if($res)
      {
        $this->success("启用成功");
      }
    }
    // 关闭折扣
    public function close()
    {
      $id=$this->request->param('id');
      $res=  $this->userGroupModel->isUpdate(true)->allowField(true)->save(['is_show'=>0],['id'=>$id]);
      if($res)
      {
        $this->success("关闭成功");
      }
    }
}
