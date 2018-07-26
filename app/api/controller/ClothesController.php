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
namespace app\api\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\GoodsModel;
use think\Db;
use think\Cookie;

class ClothesController
{
  public function index()
  {
     header('Content-Type:text/xml;charset=utf-8');
     $list=Db::name('goods')->where(['category_id'=>6])->select();
     $str="";
     foreach($list as $v)
     {
       $str.= "<pic title='".$v['gname']."' anclass='".$v['category_id']."' price='".$v['gprice']."' thumb='".cmf_get_image_url($v['goods_image'])."' swf='".cmf_get_image_url($v['goods_image'])."' back='".cmf_get_image_url($v['goods_image'])."' iid='".$v['gid']."' url='http://".$_SERVER['SERVER_NAME']."/goods/article/index/id/".$v['gid']."'></pic>";
     }
     exit("<data>".$str."</data>");
  }
}
