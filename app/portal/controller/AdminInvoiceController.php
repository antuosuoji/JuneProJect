<?php

namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\OrderModel;
use think\Db;
use think\validate;

class AdminInvoiceController extends AdminBaseController{

   //显示发票
    public function index() {

      $where   = "1 = 1";
      $where  .= " and i.is_evaluate !='2'";
      //发票类型
      if ($this->request->param('type') !='' && $this->request->param('type') !=3) {
        $type   = $this->request->param('type');
        $type   = $type === '0' || !isset($type) ? 0 : 1;
        $where .= " and i.type = '$type'";
      }
    //支付情况
      if ($this->request->param('pay_status') !='' && $this->request->param('pay_status') !=3) {
        $pay_status   = $this->request->param('pay_status');
        $pay_status   = $pay_status === '0' || !isset($pay_status) ? 0 : 1;
        $where       .= " and i.pay_status = '$pay_status'";
      }

    //手机号
      if ($this->request->param('tel') != ''){
        $tel          = $this->request->param('tel');
        $where       .= " and i.tel = '$tel'";
      }

      $invoice =  Db::name('order_invoice as i')
               -> where($where)
               -> order('id DESC')->paginate(10);

      $this -> assign('invoice',$invoice);
      $this -> assign('page',$invoice->render());
      return $this->fetch();
    }

   //查看发票详情

   public function look() {

     $id     = $this->request->param('id');
     $info   = Db::name('order_invoice')->where('id',$id)->find();
     $this -> assign('info',$info);
     return $this->fetch();

   }

}
