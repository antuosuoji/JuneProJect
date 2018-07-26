<?php

namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\OrderModel;
use think\Db;
use think\validate;

class AdminDeliverController extends AdminBaseController{

   //显示订单
    public function index() {

      $where    = "1 =1";
      $where   .= " and o.is_evaluate !='2'";
      //订单编号
      if ($this->request->param('orderid'))
      {
      $orderid  = $this->request->param('orderid');
      $where   .= " and o.l_order like '%$orderid%'";
      }

      //收货人
      if ($this->request->param('receiver_name'))
      {
      $receiver_name = $this->request->param('receiver_name');
      $where   .= " and o.l_name like '%$receiver_name%'";
      }
      $bill =  Db::name('order_bill as o')->where($where)->order("lid DESC")->paginate(10);
      $this -> assign('bill',$bill);
      $this -> assign('page',$bill->render());
      return $this->fetch();
    }
    //查看订单
    public function look() {

      //订单发货表
      $lid    = $this->request->param('lid');
      $bill   = Db::name('order_bill b')->where('b.lid',$lid)->find();
      //订单商品查询
     $where         = ' 1 =1';
     $orderid       = $bill['l_order'];
     $where        .= " and d.orderid = '$orderid'";
     $order_detail  = Db::name('order_detail d')
                    ->join('goods g','g.gid = d.goods_id')
                    ->field('d.*,g.gname')
                    ->where($where)->select();

    //订单信息详情表
     $order         = Db::name('order o')->where('orderid',$orderid)->field('o.total_price,o.discount,o.coupon,o.freight,o.total_actual')->find();

      $this -> assign('order_detail',$order_detail);
      $this -> assign('order',$order);
      $this -> assign('bill',$bill);
      return $this->fetch();
    }

  //接收ajax后台lid改变收货状态

    public function open(){

      $lid   = $this->request->param('lid');
      $data  = Db::name('order_bill')->where('lid',$lid)->find();
      $oid   = $data['oid'];
      $res   = Db::name('order_bill')->where('lid',$lid)->update(['l_status'=>2]);
      $res   = Db::name('order')->where('id',$oid)->update(['post_status'=>2]);
      if($res){
        $this->success('收货成功');
      }
    }

    /*7.9日订单查询*/

    public function dquery() {

      $data = $this->request->param();
      $this->assign('data',$data);
      return $this->fetch();

    }

    public function doquery(){

      $typeCom = $this->request->param('com');//快递公司
      $typeNu  = $this->request->param('nu');//快递单号

      $AppKey='7498496113';//请将XXXXXX替换成您在http://kuaidi100.com/app/reg.html申请到的KEY
      $url ='http://api.kuaidi100.com/api?id='.$AppKey.'&com='.$typeCom.'&nu='.$typeNu.'&show=2&muti=1&order=asc';

      //请勿删除变量$powered 的信息，否者本站将不再为你提供快递接口服务。
      $powered = '查询数据由：<a href="http://kuaidi100.com" target="_blank">KuaiDi100.Com （快递100）</a> 网站提供 ';

      //优先使用curl模式发送数据
      if (function_exists('curl_init') == 1){
        $curl = curl_init();
        curl_setopt ($curl, CURLOPT_URL, $url);
        curl_setopt ($curl, CURLOPT_HEADER,0);
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
        curl_setopt ($curl, CURLOPT_TIMEOUT,5);
        $get_content = curl_exec($curl);
        curl_close ($curl);
      }else{
        vendor('express/Snoopy');
        $snoopy = new snoopy();
        $snoopy->referer = 'http://www.google.com/';//伪装来源
        $snoopy->fetch($url);
        $get_content = $snoopy->results;
      }
      print_r($get_content . '<br/>' . $powered);
      exit();

    }



























}
