<?php

namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\OrderModel;
use think\Db;
use think\validate;
use think\rndChinaName;
use think\WxPayConfig;
// 微信
use think\WxPayApi;
use think\WxPayRefund;

class AdminRefundController extends AdminBaseController{

    //订单退款
    public function index(){

        $where = " 1 =1";

      if ($this->request->param('is_evaluate') !=0 && $this->request->param('is_evaluate') != null) {
          $is_evaluate = $this->request->param('is_evaluate');
          $where      .= " and r.is_evaluate = '$is_evaluate'";
      }

      if ($this->request->param('orderid') != null) {
          $orderid     = $this->request->param('orderid');
          $where      .= " and r.orderid = '$orderid'";
      }

      if ($this->request->param('tel') != null) {
          $orderid     = $this->request->param('tel');
          $where      .= " and r.tel = '$orderid'";
      }

      $refund = Db::name('order_refund r')->where($where)->order("id DESC")->paginate(10);
      $this->assign('page',$refund->render());
      $this->assign('refund',$refund);
      return $this->fetch();

    }

    //退款查看
    public function edit() {


      $id     = $this->request->param('id');
      $refund = Db::name('order_refund')->where('id',$id)->find();                            //退款详情表
      $order  = Db::name('order')->where('orderid',$refund['orderid'])->find();               //订单详情表
      $bill   = Db::name('order_bill')->where('l_order',$refund['orderid'])->find();          //商品属性
      $info   = Db::name('order_invoice')->where('order_id',$refund['orderid'])->find();      //发票表
      $user   = Db::name('user')->where('id',$order['userid'])->find();
      //订单商品查询
     $where         = ' 1 =1';
     $orderid       = $refund['orderid'];
     $where        .= " and d.orderid = '$orderid'";
     $order_detail  = Db::name('order_detail d')
                    ->join('goods g','g.gid = d.goods_id')
                    ->field('d.*,g.gname')
                    ->where($where)->select();

    //地址
      $this->assign('info',$info);
      $this->assign('bill',$bill);
      $this->assign('order',$order);
      $this->assign('user',$user);
      $this->assign('order_detail',$order_detail);
      $this->assign('refund',$refund);
      return $this->fetch();
    }


    public function upd() {

      $agree        = $this->request->param('agree');


      $admin_id     = cmf_get_current_admin_id();
      $admin        = Db::name('user')->where('id',$admin_id)->find();
      $admin_name   = $admin['user_nickname'];                                            //获取管理员姓名

      $orderid      = $this->request->param('orderid');                                   //退款操作订单号
      $order_money  = $this->request->param('money');                                     //退款操作金额
      $content      = $this->request->param('content');                                   //退款详细说明

      $order        = Db::name('order')->where('orderid',$orderid)->find();
      $money        = $order['total_actual'];                                              //价格
      $userid       = $order['userid'];                                                    //会员id
      $points       = $order['points'];                                                    //积分

      $time         = time();                                                              //时间
      $user         = Db::name('user')->where('id',$userid)->find();                       //会员信息表
      $balance      = $user['balance'];
      $score        = $user['score'];                                                      //获取会员积分;
      $moneys       = $balance + $money;
      $num          = $score - $points;

      if ($agree == "1") {

        /*插入用户记录表*/
        $data['type']         = '5';
        $data['user_id']      = $userid;
        $data['money']        = $money;
        $data['log_name']     = '取消退款';
        $data['remarks']      = '管理员'.$admin_name.':'.'同意操作';
        $data['time']         = $time;
        $data['admin_userid'] = $admin_id;

        $res  = Db::name('record')->insert($data);                                                                      //插入用户操作表状态
        $res  = Db::name('order')->where('orderid',$orderid)->update(['order_status'=>'0','pay_status'=>'1','post_status'=>'0']);          //更改订单表         状态受理中
        $res  = Db::name('order_bill')->where('l_order',$orderid)->update(['is_evaluate'=>'0']);      //更改发货状态       退款状态
        $res  = Db::name('order_invoice')->where('order_id',$orderid)->update(['is_evaluate'=>'0']);  //更改发票状态       退款状态
        $res  = Db::name('order_refund')->where('orderid',$orderid)->update(['is_evaluate'=>3,'updatetime'=>time(),'content'=>$content]);      //更新退款表状态

        $this->success("暂不退款执行成功!");

      }else{

        //最后积分
        //判断支付方式，余额支付 退到余额，微信支付退到微信
        if($order['pay_style'] ==1) //微信
        {
          /*微信退款*/
          $wxresult=$this->wxrefund($orderid,$order['total_actual']*100,($order['total_actual']-$order['freight'])*100);
          if($wxresult['result_code'] != 'SUCCESS' || $wxresult['return_code'] != 'SUCCESS')
          {
            $this->error('退款至微信失败');
          }
        }elseif($order['pay_style'] ==2)
        {
          $res          = Db::name('user')->where('id',$userid)->update(['score'=>$num,'balance'=>$moneys]);
        }

        /*插入用户记录表*/
        $data['type']         = '4';
        $data['user_id']      = $userid;
        $data['money']        = $money;
        $data['log_name']     = '退款处理';
        $data['remarks']      = '管理员'.$admin_name.':'.'同意操作';
        $data['time']         = $time;
        $data['admin_userid'] = $admin_id;

        $res          = Db::name('record')->insert($data);                                                                         //插入用户操作表状态
        $res          = Db::name('order_refund')->where('orderid',$orderid)->update(['is_evaluate'=>2,'updatetime'=>time(),'content'=>$content]);      //更新退款表状态
        $res          = Db::name('order_bill')->where('l_order',$orderid)->update(['is_evaluate'=>2]);                          //退款成功
        $res          = Db::name('order_invoice')->where('order_id',$orderid)->update(['is_evaluate'=>2]);                        //退款成功
        $res          = Db::name('order')->where('orderid',$orderid)->update(['is_refund'=>1]);                                 //更新订单表状态值
        //更改积分状态

        $this->success("退款完成...");

      }

    }
    // 微信退款
   public function wxrefund($orderid,$money,$refund_fee)
   {
        require_once "../simplewind/vendor/wx_pay/lib/WxPay.Api.php";
        $out_trade_no = $orderid;//商户订单号
        $total_fee = $money;  //订单总金额
       	//退款金额 $refund_fee
        $input = new WxPayRefund();
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no(WxPayConfig::MCHID.date("YmdHis"));
        $input->SetOp_user_id(WxPayConfig::MCHID);
        return WxPayApi::refund($input);
   }






















}
