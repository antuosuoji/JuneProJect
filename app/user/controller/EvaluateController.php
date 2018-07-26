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

use cmf\controller\UserBaseController;

use think\Db;

class EvaluateController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
    }

	public function lists() {

    $orderid = $this->request->param('orderid');  //订单id

    $res     = Db::name('order_detail')->where('orderid',$orderid)->select();

    foreach ($res as $k => $v) {

      $ress[] = $v['is_evaluate'];

    }
    $res = in_array('0',$ress);




    $where   = " 1 =1";
    $where  .= " and d.orderid = '$orderid'";

    $detail  = Db::name('order_detail d')
             ->where($where)
             ->join('goods g','g.gid = d.goods_id')
             ->field('d.goods_attr_value,d.orderid,d.goods_price,d.num,d.is_evaluate,d.id,g.gname,g.goods_image,g.gid,g.category_id')
             ->select();

    $this -> assign('orderid',$orderid);
    $this -> assign('detail',$detail);
    return $this->fetch();




  }


	public function index() {

        $id     = cmf_get_current_user_id();
        $where  = " 1 =1";
        $did    = $this->request->param('id');
        $where .= " and d.id = '$did'";
        $com    = Db::name('order_detail as d')
                    ->where($where)
                    ->join('goods g','g.gid = d.goods_id')
                    ->join('order o','o.orderid = d.orderid')
                    ->field('d.goods_attr_value,d.num,d.goods_attr_value,d.goods_price,d.id,g.gname,g.goods_image,g.gid,o.orderid')
                    ->find();
        $this->assign('com',$com);
				return $this->fetch();
	}

  public function comment(){

      if ($this->request->isPost()){

          $ArrData = $this->request->param();
          if (mb_strlen($ArrData['content']) >= 50) {
            $this->error('请您评论字数控制在50字之内~');
          }
          $orderid            = $ArrData['orderid'];
          $did                = $ArrData['id'];
          $data['goods_id']   = $ArrData['goods_id'];
          $data['score']      = $ArrData['score'];
          $data['content']    = $ArrData['content'];
          $data['userid']     = cmf_get_current_user_id();
          $data['inputtime']  = time();
          $data['ip']         = get_client_ip();

          $res = Db::name('goods_comment')->insert($data);
          $res = Db::name('order_detail')->where('id',$did)->update(['is_evaluate'=>'1']);

          //判断detail数组
          $eva = Db::name('order_detail')->where('orderid',$orderid)->select();
          //判断detail数组中是否存在0
          foreach ($eva as $k => $v) {
            $evas[] = $v['is_evaluate'];
          }

          if (in_array('0',$evas) === false) {
            $res = Db::name('order')->where('orderid',$orderid)->update(['is_evaluate'=>'1']);
          }


          if ($res) {
            $this->success('评论完成',url('user/order/index'));
          }

      }
  }





















}
