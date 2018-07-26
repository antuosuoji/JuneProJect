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
namespace app\goods\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\model\GoodsModel;
class ListController extends HomeBaseController
{
    public function index()
    {
        $params= $this->request->param();
        $keyword = $this->request->param('keyword');
        $where = "1 = 1";

        if (!empty($keyword)) {
        $where  .= " and g.gname like '%$keyword%'";
        }

        // 商品分类筛选
        $id=isset($params['id']) ? $params['id'] : "";
        $map=array();
        if(isset($id) && is_numeric($id))
        {
          $sub_cats=getChildCategorys($id);
          $sub_cats[]=intval($id);
          $map['category_id']=['in',$sub_cats];
        }

        // 商品 热销 精品 新品 筛选
        if(isset($params['sid']) && is_numeric($params['sid']))
        {
          $sid=$params['sid'];
          $where .= " and find_in_set($sid,goods_status)";
        }
        // 条件筛选排序

        $search_sales=$this->request->get('sales');
        $search_price=$this->request->get('price');
        $search_news =$this->request->get('news');

        if(isset($search_sales) && !empty($search_sales))
        {
          $search_sales === "1" ? $order['num']='asc': $order['num']='desc';
        }
        if(isset($search_price) && !empty($search_price))
        {
          $search_price === "1" ? $order['gprice']='asc': $order['gprice']='desc';
        }
        if(isset($search_news) && !empty($search_news))
        {
          $search_news === "1" ? $order['createtime']='asc': $order['createtime']='desc';
        }
        $order=isset($order) ? $order :  ['gid'=>'desc'];
        $goodsModel=new GoodsModel();
        $list=$goodsModel ->alias('g')
                          ->field('g.gid,g.gname,g.gprice,g.goods_image,s.num')
                          ->join('__SALES__ s','g.gid = s.goods_id','LEFT')
                          ->where($where)
                          ->where($map)
                          ->order($order)
                          ->paginate();
        $this->assign("list", $list);
        $this->assign('page', $list->render());
        return $this->fetch('/list');
    }

}
