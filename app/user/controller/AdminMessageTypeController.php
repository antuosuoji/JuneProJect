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
use think\validate;


class AdminMessageTypeController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();

    }
    public function index()
    {
      $MessageTypeQuery = Db::name('message_type');
      $messTypeInfo=$MessageTypeQuery->select();
      $this->assign("messTypeInfo", $messTypeInfo);
      return $this->fetch();

    }
    // 添加消息类别
    public function add()
    {
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();
        // 验证
        $is_true=Validate::make()
        ->rule('name', 'require')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("不能为空");
        }
        Db::name('message_type')->insert($arrData);
        $this->success("添加成功",url('AdminMessageType/index'));
      }else
      {
        return $this->fetch();
      }
    }
    // 编辑会员级别
    public function edit()
    {
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();
        $is_true=Validate::make()
        ->rule('name', 'require')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("不能为空");
        }
        Db::name('message_type')->update($arrData);
        $this->success("更新成功",url('AdminMessageType/index'));
      }else
      {
        $typeId=$this->request->param("id",'','intval');
        $typeInfo=Db::name('message_type')->where('id',$typeId)->find();
        $this->assign("typeinfo",$typeInfo);
        return $this->fetch();
      }
    }
    // 删除会员组
    public function delete()
    {
      $typeId=$this->request->param("id",0,'intval');
      if(empty($typeId))
      {
        $this->error("类别ID错误");
      }else
      {
          Db::name('message_type')->delete($typeId);
      }
      $this->success("删除成功");
    }
}
