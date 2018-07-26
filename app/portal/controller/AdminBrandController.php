<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:kane < chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use app\portal\model\BrandModel;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;
use think\Request;

/**
 * Class AdminTagController 标签管理控制器
 * @package app\portal\controller
 */
class AdminBrandController extends AdminBaseController
{
    public function __construct()
    {
      parent::__construct();

      $this->typeModel = new BrandModel();
    }

    public function edit() {

      if($this->request->isPOST())
      {
        $arrData=$this->request->param();

        $is_true=Validate::make()
        ->rule('bname', 'require')
        ->check($arrData);

        if($is_true == false)
        {
            $this->error("不能为空");
        }

        $this->typeModel->isUpdate(true)->allowField(true)->save($arrData);

        $this->success(lang("SAVE_SUCCESS"));

      }else{

        $typeId=$this->request->param("id",'','intval');

        $typeInfo=$this->typeModel->get($typeId);
        $this->assign("typeinfo",$typeInfo);
        return $this->fetch();
      }
    }

    public function index()
    {

        $portalTagModel = new BrandModel();

        $tags           = $portalTagModel->paginate();

        $this->assign("arrStatus", $portalTagModel::$STATUS);
        $this->assign("tags", $tags);
        $this->assign('page', $tags->render());
        return $this->fetch();
    }



public function add() {







        $portalTagModel = new BrandModel();

      if($this->request->isPOST()) {

        $arrData=$this->request->param();

        $portalTagModel->isUpdate(false)->allowField(true)->save($arrData);

        $this->success(lang("SAVE_SUCCESS"));

      }else
      {
        return $this->fetch();
      }
    }



    public function delete()
    {
        $intId = $this->request->param("id", 0, 'intval');

        if (empty($intId)) {
            $this->error(lang("NO_ID"));
        }
        $portalTagModel = new BrandModel();

        $portalTagModel->where(['id' => $intId])->delete();

        $this->success(lang("DELETE_SUCCESS"));
    }
}
