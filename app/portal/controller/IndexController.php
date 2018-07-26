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
use think\Db;
use app\portal\model\GoodsModel;
use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
class IndexController extends HomeBaseController
{
    public function index() {

    //精品
      $jingping  = Db::name('goods')->where('goods_status','like','%1%')->select()->toArray();

    //新品推荐2
      $xinpin    = Db::name('goods')->where('goods_status','like','%2%')->select()->toArray();

    //热销产品3
      $rexiao    = Db::name('goods')->where('goods_status','like','%3%')->select()->toArray();
      //微信登录
      $this->assign('jingping',$jingping);
      $this->assign('xinpin',$xinpin);
      $this->assign('rexiao',$rexiao);
      return $this->fetch(':index');
    }
   public function nav()
   {
     // 获取一级分类
     $categoryModel=new PortalCategoryModel();
     $cats=$categoryModel->getCategorys();
     $this->assign("cats",$cats);
     return $this->fetch('../public/nav');
   }


}
