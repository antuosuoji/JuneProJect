<?php
namespace app\user\controller;
use think\Db;
use cmf\controller\HomeBaseController;


class NotifyController extends HomeBaseController
{
  // 充值回调
  public function recharge()
  {
     $postStr = file_get_contents("php://input");
     $orderxml = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
     $third_info=Db::name('third_party_user')->field('user_id')->where(['openid'=>$orderxml['openid']])->find();
     $userid=$third_info['user_id'];
     config('default_return_type','xml');
     if($orderxml['result_code'] == 'SUCCESS' && $orderxml['return_code'] =='SUCCESS')
     {
       // 反馈服务器
       $info['userid']=$userid;
       $info['orderid']=$orderxml['out_trade_no'];
       $info['money']=$orderxml['total_fee']/100;
       $info['inputtime']=time();
       $info['type'] =0;
       $info['transaction_id']=$orderxml['transaction_id'];
       Db::name('recharge')->insert($info); //记录入库
       Db::name('user')->where(['id'=>$info['userid']])->setInc('balance',$info['money']);//更新余额
       Db::name('record')->insert(['type'=>0,'user_id'=>$info['userid'],'money'=>$info['money'],'log_name'=>'会员充值','time'=>time()]);//交易记录入库
       // file_put_contents('./logs.txt',json_encode($orderxml).PHP_EOL,FILE_APPEND);
       $xml=array('return_code'=>'<![CDATA[SUCCESS]]>','return_msg'=>'<![CDATA[OK]]>');
       return $xml;
     }
  }
    // 商品购买回调方法
    public function buy()
    {
       $postStr = file_get_contents("php://input");
       $orderxml = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
       $third_info=Db::name('third_party_user')->field('user_id')->where(['openid'=>$orderxml['openid']])->find();
       $userid=$third_info['user_id'];
       config('default_return_type','xml');
       if($orderxml['result_code'] == 'SUCCESS' && $orderxml['return_code'] =='SUCCESS')
       {
         // 改变订单状态
         $orderinfo=Db::name('order')->where(['orderid'=>$orderxml['out_trade_no']])->find();
         Db::name('order')->where(['orderid'=>['=',$orderxml['out_trade_no']]])
                          ->update(['pay_status'=>1,'pay_style'=>1,'pay_time'=>time()]);
          file_put_contents('./logs.txt',json_encode($orderinfo).PHP_EOL,FILE_APPEND);
          file_put_contents('./logs.txt',$userid.PHP_EOL,FILE_APPEND);
          //积分增加
         Db::name('user')->where(['id'=>['=',$userid]])->setInc('score',$orderinfo['points']);
         // 订单发票状态
         Db::name('order_invoice')->where(['order_id'=>['=',$orderinfo['orderid']]])->update(['pay_status'=>1]);
         // 交易记录入库
         Db::name('record')->insert(['type'=>2,'user_id'=>$userid,'money'=>$orderinfo['total_actual'],'log_name'=>'会员购买商品','time'=>time()]);
         // 分销数据 $userid,$orderid,$total_actual
         user_commission($userid,$orderinfo['orderid'],$orderinfo['total_actual']);
         // 销量入库
         sales_volume($orderinfo['orderid']);
         // 检测会员的等级
         check_usergrade($userid);
         // 给微信服务器返回成功信息
         $xml=array('return_code'=>'<![CDATA[SUCCESS]]>','return_msg'=>'<![CDATA[OK]]>');
         return $xml;

       }
    }



}


?>
