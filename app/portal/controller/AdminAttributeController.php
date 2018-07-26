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
use app\portal\model\AttributeModel;
use think\Validate;
use think\Db;
class AdminAttributeController extends AdminBaseController
{
    public function __construct()
    {
      parent::__construct();
      // 商品类别模型
      $this->typeModel=new TypeModel();
      $this->AttributeModel=new AttributeModel();
    }
    // 获取当前属性列表
    public function lists()
    {
      $typeId=$this->request->param("typeid");
      $where = " 1 =1";
      $where = $typeId == 0 ? $where : $where." and type_id = '$typeId'";
      if($this->request->param('keyword'))
      {
        $keyword=$this->request->param('keyword');
        $where .= " and a.name like '%$keyword%'";
      }
      $AttrInfo=$this->AttributeModel
                     ->alias('a')
                     ->join('__TYPE__ t','a.type_id=t.id')
                     ->field('a.*,t.name as typename')
                     ->where($where)
                     ->paginate(15);
      $this->assign('attrinfo',$AttrInfo);
      $this->assign('typeid',$typeId);
      $this->assign('page', $AttrInfo->render());
      // 所有类别
      $typesinfo=$this->typeModel->select();
      $this->assign("typesinfo",$typesinfo);
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
            $this->error("名称不能为空");
        }
        $arrData['inputtype'] == 0 ? $arrData['content']="" : $arrData['content']=$arrData['content'];
        $this->AttributeModel->isUpdate(true)->allowField(true)->save($arrData);
        $this->success(lang("SAVE_SUCCESS"),url('AdminAttribute/lists',array("id"=>$arrData['type_id'])));
      }else
      {
        $typeid=$this->request->param('typeid');
        $attrId=$this->request->param("id",'','intval');
        $attrInfo=$this->AttributeModel->get($attrId);
        // 获取所有商品类别
        $typesinfo=$this->typeModel->select();
        $this->assign("attrinfo",$attrInfo);
        $this->assign("typeid",$typeid);
        $this->assign("typesinfo",$typesinfo);
        return $this->fetch();
      }
    }
    // 属性添加
    public function add()
    {
      if($this->request->isPOST())
      {

        $arrData=$this->request->param();
        // 验证
        $is_true=Validate::make()
        ->rule('name', 'require')
        ->rule('attr_type', 'require')
        ->rule('inputtype', 'require')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("信息请填写完整");
        }
        $this->AttributeModel->isUpdate(false)->allowField(true)->save($arrData);
        $this->success(lang("SAVE_SUCCESS"),url('AdminAttribute/lists',array("id"=>$arrData['type_id'])));
      }else
      {
        $typeid=$this->request->param('typeid');

        // 获取所有商品类别
        $typesinfo=$this->typeModel->select();
        $this->assign("typesinfo",$typesinfo);
        $this->assign("typeid",$typeid);
        return $this->fetch();
      }
    }
    // 属性删除
    public function delete()
    {
        $attrId=$this->request->param("id",0,'intval');
        if(empty($attrId))
        {
          $this->error("ID错误");
        }else
        {
        
            //删除对应的商品属性数据
            Db::name('goods_attr')->where('goods_property_id',$attrId)->delete();
            $this->AttributeModel->where(['id'=>$attrId])->delete();
            $this->success("删除成功");
        }


    }

}
