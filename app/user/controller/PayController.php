<?php
namespace app\user\controller;

use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use think\Db;
use think\Validate;
use think\Cache;
// 微信支付类
use think\WxPayApi;
use think\WxPayUnifiedOrder;
use think\JsApiPay;
use think\WxPayNotify;
use think\WxPayJsApiPay;
// 支付控制器
class PayController extends UserBaseController
{
   public function index()
   {
    // 判断订单id
     $userid=cmf_get_current_user_id();
     $orderid=$this->request->param('orderid');
     $result=Db::name('order')->where(['orderid'=>['=',$orderid],'userid'=>['=',$userid],'pay_status'=>['=',0]])->find();
     if($result)
     {
       $this->assign('info',$result);
       return $this->fetch();
     }else
     {
       $this->error('您的订单ID有误',url('portal/Index/index'));
     }
   }
   // 支付
   public function pay()
   {
     $userid=cmf_get_current_user_id();
     $data=$this->request->param();
     $result=Db::name('order')->where(['orderid'=>['=',$data['orderid']],'userid'=>['=',$userid],'pay_status'=>['=',0]])->find();
     if($result && $data['pay'] === 'yue')
     {
       // 余额支付 判断余额是否充足
       $yue=getUserInfoById($userid)['balance'];
       if($result['total_actual'] > $yue)
       {
         $this->error('您的账户余额不足，请充值');
       }else
       {
         //完成支付，变更 订单状态 、用户积分、发票 订单支付状态
         Db::name('order')->where(['orderid'=>['=',$data['orderid']],'userid'=>['=',$userid]])
                          ->update(['pay_status'=>1,'pay_style'=>2,'pay_time'=>time()]);
         Db::name('user')->where(['id'=>['=',$userid]])->setDec('balance',$result['total_actual']);
         Db::name('user')->where(['id'=>['=',$userid]])->setInc('score',$result['points']);
         Db::name('order_invoice')->where(['order_id'=>['=',$data['orderid']]])->update(['pay_status'=>1]);
         // 交易记录入库
         Db::name('record')->insert(['type'=>2,'user_id'=>$userid,'money'=>$result['total_actual'],'log_name'=>'会员购买商品','time'=>time()]);
         // 分销数据入库
         $this->userCommission($data['orderid'],$result['total_actual']);
         // 销量写入库存
         $this->salesVolume($data['orderid']);
         // 检测会员等级
         $this->checkUserGrade();
         $this->success('恭喜您支付成功',url('user/pay/pay_success',array('orderid'=>$data['orderid'])));
       }

     }elseif($result && $data['pay'] === 'wechat')
     {
       $this->wxpay($data['orderid'],$result['total_actual']*100);
       $this->assign('orderid',$data['orderid']);
       return $this->fetch();
     }
   }
   public function wxpay($orderid,$total)
   {
     // // 微信支付 引入文件
     require_once "../simplewind/vendor/wx_pay/lib/WxPay.Api.php";
     require_once "../simplewind/vendor/wx_pay/WxPay.JsApiPay.php";
     //②、统一下单
     $input = new WxPayUnifiedOrder();
     $tools = new JsApiPay();
     // $openId = $tools->GetOpenid();

     $input->SetBody("芊芊商城-商品购买");//商品描述
     $input->SetAttach("北京总部");//附加数据 在查询API和支付通知中原样返回，可作为自定义参数使用
     $input->SetOut_trade_no($orderid); //商户订单号
     $input->SetTotal_fee($total);//交易总金额 。单位为分
     $input->SetTime_start(date("YmdHis"));//订单生成时间
     $input->SetTime_expire(date("YmdHis", time() + 600)); //交易失效时间 交易结束时间（非必填)
     // $input->SetGoods_tag("test"); //订单优惠标记，使用代金券或立减优惠功能时需要的参数
     $input->SetNotify_url($this->request->domain()."/".url('user/Notify/buy'));//异步接收微信支付结果通知的回调地址
     $input->SetTrade_type("JSAPI");//JSAPI 公众号支付
     // 获取openid
     $openid=Db::name('third_party_user')->where(['user_id'=>cmf_get_current_user_id()])->find();
     $input->SetOpenid($openid['openid']);//支付用户openid  非必填
     $order = WxPayApi::unifiedOrder($input);
     $jsApiParameters = $tools->GetJsApiParameters($order);
     $this->assign('jsApiParameters',$jsApiParameters);
   }

   // 支付成功页面
   public function pay_success()
   {
     $userid=cmf_get_current_user_id();
     $orderid=$this->request->param('orderid');
     $info=Db::name('order')->where(['orderid'=>$orderid,'pay_status'=>1,'userid'=>$userid])->find();
     if($info)
     {
       $this->assign('info',$info);
       return $this->fetch();
     }else
     {
        $this->error('您的订单ID有误',url('portal/Index/index'));
     }
   }
   // 处理分销数据
   public function userCommission($orderid,$total_actual)
   {
      $userid=cmf_get_current_user_id();
      $level_setting=cmf_get_option('level_settings');
      // 佣金写入数据库
      $users=getParentUserid($userid,1,$level_setting['level']);
      foreach($users as $v)
      {
        $info['userid']=$v['userid'];

        $info['child_userid']=$userid;
        $info['child_level']=$v['level'];
        $info['orderid']=$orderid;
        $info['inputtime']=time();
        switch ($v['level']) {
          case '1':
            $info['commission']=($level_setting['first_score']/100)*$total_actual;
            break;
          case '2':
            $info['commission']=($level_setting['second_score']/100)*$total_actual;
            break;
          case '3':
            $info['commission']=($level_setting['third_score']/100)*$total_actual;
            break;
        }
        $info['commission']=round($info['commission'],2);
        Db::name('level_commission')->insert($info);
        // 佣金写入总佣金表
        $is_has=Db::name('user_commission')->where(['userid'=>$info['userid']])->find();
        if($is_has)
        {
          Db::name('user_commission')->where(['userid'=>$info['userid']])->setInc('total',$info['commission']);
        }else
        {
          Db::name('user_commission')->insert(['userid'=>$info['userid'],'total'=>$info['commission']]);
        }
      }
   }
   // 销量写入库存
   public function salesVolume($orderid)
   {
     $info=Db::name('order_detail')->field('goods_id,num')->where(['orderid'=>$orderid])->select();
     foreach($info as $v)
     {
       $is_has=Db::name('sales')->where(['goods_id'=>$v['goods_id']])->find();
       if($is_has)
       {
         Db::name('sales')->where(['goods_id'=>$v['goods_id']])->setInc('num',$v['num']);
       }else
       {
         Db::name('sales')->insert($v);
       }
     }
   }
   // 支付完成检测会员等级  2018.7.3号
   private function checkUserGrade()
   {
     $userid=cmf_get_current_user_id();
     $userinfo=Db::name('user')->field('score')->where(['id'=>$userid])->find();
     $info=Db::name('user_group')->select();
     foreach($info as $k=>$v)
     {
       $group[$v['id']]=$v;
     }
     if($userinfo['score'] >=  $group[4]['lower_point'])
     {
        Db::name('user')->where(['id'=>$userid])->setField('group_id',4);
     }elseif($userinfo['score'] >= $group[2]['lower_point'])
     {
        Db::name('user')->where(['id'=>$userid])->setField('group_id',2);
     }
   }
}

?>
