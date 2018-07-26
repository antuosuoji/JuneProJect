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
use app\portal\model\TypeModel;
use think\Validate;
class AdminTypeController extends AdminBaseController
{
    public function __construct()
    {
      parent::__construct();
      
      $this->typeModel=new TypeModel();
    }

    public function index()
    {
      $types           = $this->typeModel->paginate();
      $this->assign("types", $types);
      $this->assign('page', $types->render());
      return $this->fetch();

    }
    // 编辑 修改
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
        $this->typeModel->isUpdate(true)->allowField(true)->save($arrData);
        $this->success(lang("SAVE_SUCCESS"));
      }else
      {
        $typeId=$this->request->param("id",'','intval');
        $typeInfo=$this->typeModel->get($typeId);
        $this->assign("typeinfo",$typeInfo);
        return $this->fetch();
      }
    }
    // 添加类别
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
        $this->typeModel->isUpdate(false)->allowField(true)->save($arrData);
        $this->success(lang("SAVE_SUCCESS"));
      }else
      {
        return $this->fetch();
      }
    }
    // 标签删除
    public function delete()
    {
        $typeId=$this->request->param("id",0,'intval');
        if(empty($typeId))
        {
          $this->error("类别ID错误");
        }else
        {
            $this->typeModel->where(['id'=>$typeId])->delete();
        }
        $this->success("删除成功");

    }

}
