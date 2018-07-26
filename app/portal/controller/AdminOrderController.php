<?php
namespace app\portal\controller;
use cmf\controller\AdminBaseController;
use app\portal\model\OrderModel;
use think\Db;
use app\portal\model\GoodsModel;
use think\validate;

class AdminOrderController extends AdminBaseController {

  //显示页面
    public function index(){

      $jingping  = Db::name('goods')->where('goods_status','like','1%')->limit(0,2)->select()->toArray();

      $where  = "1 =1";
      $where .= " and o.is_refund != '1'";

      //
      //订单状态
      if ($this->request->param('order_status')!='' && $this->request->param('order_status')!=5)
      {
        $post_status = $this->request->param('order_status');
        $where      .= " and o.order_status = '$post_status'";
      }
      //支付情况
      if ($this->request->param('pay_status')!='' && $this->request->param('pay_status')!=3)
      {
      $pay_status = $this->request->param('pay_status');
      $where     .= " and o.pay_status = '$pay_status'";
      }
      //订单编号
      if ($this->request->param('orderid'))
      {
      $orderid    = $this->request->param('orderid');
      $where     .= " and o.orderid like '%$orderid%'";
      }
      //发货状态
      if ($this->request->param('post_status')!='' && $this->request->param('post_status')!=5)
      {
        $post_status = $this->request->param('post_status');
        $where      .= " and o.post_status = '$post_status'";
      }
      //收货人姓名
      if ($this->request->param('receiver_name'))
      {
      $receiver_name    = $this->request->param('receiver_name');
      $where           .= " and o.receiver_name like '%$receiver_name%'";
      }
      $order      = new OrderModel();
      $order      = $order ->where($where)
                           ->alias('o')
                           ->order("id DESC")
                           ->paginate(10);
      $this->assign('order', $order);
      $this->assign('page',$order->render());
      return $this->fetch();
    }


  //修改页面
    public function edit() {

      //接收用户id参数
      $oid = $this->request->param('id');
      //接收ajax后台pay_status 更新
      if($this->request->param('pid') !=''){
        $pid = $this->request->param('pid');
        Db::name('order')->where('userid',$oid)->update(['pay_status'=>$pid]);
      }

      //条件查询
      $order        = Db::name('order as o')
                    ->where('o.id',$oid)
                    ->join('user u','u.id = o.userid')
                    ->field('o.*,u.user_nickname')
                    ->find();


      //订单商品查询
     $where         = ' 1 =1';
     $orderid       = $order['orderid'];
     $where        .= " and d.orderid = '$orderid'";
     $order_detail  = Db::name('order_detail d')
                    ->join('goods g','g.gid = d.goods_id')
                    ->field('d.*,g.gname')
                    ->where($where)->select();
      $this->assign('order_detail',$order_detail);
      $this->assign('order',$order);
      return $this->fetch();

    }

    //确认发货订单
    public function fgoods(){

    //信息查询
    $uid            = $this->request->param('id');
    $goods          = Db::name('order as o')->where('o.id',$uid)->find();

    //商品详情查询
    $where          = ' 1 =1';
    $orderid        = $goods['orderid'];
    $where         .= " and d.orderid = '$orderid'";

    $order_detail   = Db::name('order_detail d')
                    ->join('goods g','g.gid = d.goods_id')
                    ->field('d.*,g.gname')
                    ->where($where)->select();




    // dump($goods);
    if ($this->request->isPOST()) {
    $time                     = time();
    $arrData                  = $this->request->param();
    $arrTwo                   = $this->request->param();

    // 验证
    $is_true=Validate::make()
    ->rule('post_logistics', 'require')
    ->rule('post_number','require')
    ->check($arrData);
    if($is_true == false)
    {
        $this->error("配送物流,快递单号不能为空");
    }

    /*order订单*/
    $uid                      = $arrData['oid'];              //自身id
    $data['post_logistics']   = $arrData['post_logistics'];   //配送物流
    $data['post_number']      = $arrData['post_number'];      //快递单号
    $data['post_time']        = $time;                        //发货时间
    $data['post_status']      = 1;

    /*order_bill发货单*/
    $bill['oid']              = $arrTwo['oid'];             //关联id
    $bill['l_name']           = $arrTwo['l_name'];          //收货人
    $bill['l_mobile']         = $arrTwo['receiver_mobile']; //联系电话
    $bill['l_number']         = $arrTwo['post_number'];    //快递单号
    $bill['l_logistics']      = $arrTwo['post_logistics']; //配送物流
    $bill['l_time']           = $time;                     //发货时间
    $bill['l_order']          = $arrTwo['orderid'];        //订单编号
    $bill['l_inputtime']      = $arrTwo['inputtime'];      //下单时间
    $bill['l_address']        = $arrTwo['l_address'];      //收货地址
    $bill['l_status']         = 1;

    $res = Db::name('order')->where('id',$uid)->update($data);
    $res = Db::name('order_bill')->insert($bill);
    if ($res) {
        $this->success(lang("SAVE_SUCCESS"),'portal/admin_order/index','',1);
    }
  }
    $this->assign('goods',$goods);
    $this->assign('order_detail',$order_detail);
    return $this->fetch();

    }

        // 启用支付
          public function open() {
          $id =   $this->request->param('pid');
        $time =  time();
          $res=  Db::name('order')->where('id',$id)->update(['pay_status'=>1,'pay_time'=>$time]);
          if($res){
            $this->success("启动成功");
          }
        }

        // 关闭支付
          public function close() {
          $id =   $this->request->param('pid');
          $res=  Db::name('order')->where('id',$id)->update(['pay_status'=>0,'pay_time'=>0]);
          if($res){
            $this->success("启动成功");
          }
        }

        // 开启确认订单
          public function order_status() {
          $id =   $this->request->param('pid');
         $res =  Db::name('order')->where('id',$id)->update(['order_status'=>1,'inputtime'=>time()]);
          if($res){
            $this->success("启动成功");
          }
        }
        // 取消确认订单
          public function order_close() {
          $id =   $this->request->param('pid');
          $res=  Db::name('order')->where('id',$id)->update(['order_status'=>0,'inputtime'=>0]);
          if($res){
            $this->success("启动成功");
          }
        }

        // 销毁订单
          public function order_destroy() {
          $id =   $this->request->param('pid');
          $res=  Db::name('order')->where('id',$id)->update(['order_status'=>2,'pay_status'=>2]);
          if($res){
            $this->success("启动成功");
          }
        }


      //已收货
         public function collect(){
        $id =   $this->request->param('pid');
        $time = time();
        $res=   Db::name('order')->where('id',$id)->update(['post_status'=>2,'post_times'=>$time]);
        $res=   Db::name('order_bill')->where('oid',$id)->update(['l_status'=>2,'l_times'=>$time]);
        if($res){
          $this->success("启动成功");
           }
         }

    //删除标签

    public function delete() {

      if ($this->request->isPost())
      {
            //批量删除
            $postArr = $this->request->param();
            if (count($postArr['selectid']) < 1) {
              $this->error("请您至少勾选一个");
            }
            //判断是否是销毁订单
            $res        = $postArr['selectid'];
            $arr        = array();
            foreach ($res as $key => $val){
            $data       = Db::name('order')->where('id',$res[$key])->find();
            $arr[$val]  = $data['order_status'];
            }
            $ree        = array_unique($arr);
            $res        = implode(',',$ree);

            if ($res === '2') {
                $css =  Db::name('order')->delete($postArr['selectid']);
                $this->success('删除成功');

            }else
            {
              $this->error('有正常订单不能删除!');
            }
      }elseif($this->request->isGet())
            {
            // 单条删除
            $orderid =  $this->request->param('id');
            // 查看订单状态
            $data    =  Db::name('order')->where('id',$orderid)->find();
            $status  =  $data['order_status'];
            //如果是销毁状态的的话
            if ($status == 2) {
              $res    =  Db::name('order')->where('id',$orderid)->delete();
              if ($res)
              {
                $this->success('删除成功');
              }
           }else
           {
             $this->error('该订单正常不能删除!');
           }
      }
    }
}
