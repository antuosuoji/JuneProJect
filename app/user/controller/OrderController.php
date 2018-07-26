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
use app\user\model\UserModel;
use think\Db;
use think\Validate;
use think\Cache;

class OrderController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
    }

	public function index() {

    $userId     = cmf_get_current_user_id();   //获取当前id

    $where      = "1 =1";
    $where     .= " and o.is_del != '1'";
    $where     .= " and o.userid = '$userId'";

    if ($this->request->param('pay_status')!='' && $this->request->param('pay_status')!=5) {
        $pay_status  = $this->request->param('pay_status');
        $where      .= " and o.pay_status = '$pay_status'";
    }

    if ($this->request->param('order_status') !='') {

        $order_status= $this->request->param('order_status');
        $where      .= " and o.order_status = '$order_status'";
    }

    if ($this->request->param('post_status')!='' && $this->request->param('post_status')!=5) {
        $post_status = $this->request->param('post_status');
        $post_status = $post_status  == '0' ? '0' :($post_status === '1' || !isset($post_status) ? 1 : 2);
        $where     .= " and o.post_status = '$post_status' and o.pay_status != 2 and o.pay_status != 0";
    }

    $order      = Db::name('order as o')
                ->where($where)
                ->join('order_detail d','d.orderid = o.orderid')
                ->join('goods g','g.gid = d.goods_id')
                ->field('o.*,g.gname,d.goods_attr_value,d.num,d.is_evaluate,d.goods_price,g.goods_image,g.gid,g.category_id,d.id')
                ->select();
  //订单编号做为父级
    $orderid     = Db::name('order o')->where($where)->order('id','desc')->paginate(10);



    $this->assign('orderid',$orderid);
    $this->assign('page',$orderid->render());
    $this->assign('order', $order);

    return $this->fetch();
	}
  // --------创建订单---2018.6.15
  public function buildOrder()
  {
    $userid=cmf_get_current_user_id();
    $params=$this->request->param();
    $url=$this->request->url();
    $voice=Cache::get('voice-'.cmf_get_current_user_id());
    // $url=substr($url,0,(strrpos($url,'&')));
    cookie('order_back_url',$url);
    //获取默认收货地址
    if($this->request->param('useradd'))
    {
      $useradd=$this->request->param('useradd');
      $default_address=Db::name('user_address')->where(['id'=>$useradd])->find();
    }else
    {
      $default_address=Db::name('user_address')->where(['user_id'=>$userid,'isdefault'=>1])->find();
      $useradd=$default_address['id'];
    }
    // 商品信息

    $total=0;


    if($this->request->param('cid') && $this->request->param('num'))
    {
      // 购物车购买
      $cid=$this->request->param('cid');
      $cid=array_filter(explode(",",$cid));
      // 购买数量
      $num=$this->request->param('num');
      $num=array_filter(explode(",",$num));
      $goods=Db::name('cart')->where(['id'=>['in',$cid]])->select();
      foreach($goods as $k => $v){
        $goods_info[$k]['id']=$v['goods_id'];
        $goods_info[$k]['number'] = $num[$k];
        $goods_info[$k]['goods_image']=getGoodsInfoByid($v['goods_id'])['goods_image'];
        $goods_info[$k]['price']=getGoodsInfoByid($v['goods_id'])['gprice'];
        $goods_info[$k]['gname']=getGoodsInfoByid($v['goods_id'])['gname'].$v['goods_attr_value'];
        $total += $goods_info[$k]['number'] * $goods_info[$k]['price'];
      }
    }else
    {
      // 直接购买
      $goods_id=explode(',',$params['id']);

      foreach($goods_id as $kk=>$vv)
      {

        $goods_attr=explode('---',$params['goods']);
        $goods_attr_value="";
        foreach($goods_attr as $v)
        {
          $attrs_arr=explode(',',$v);//0 属性名称，1属性id，2属性值
          $goods_attr_value .= $attrs_arr[0].":".$attrs_arr[2].",";
        }
        $goods_attr_value='('.rtrim($goods_attr_value,',').')';

        $goods_info[$kk]['id']     = $params['id'];
        $goods_info[$kk]['number'] = $params['number'];
        $goods_info[$kk]['goods_image']=getGoodsInfoByid($params['id'])['goods_image'];
        $goods_info[$kk]['price']=getGoodsInfoByid($params['id'])['gprice'];
        $goods_info[$kk]['gname']=getGoodsInfoByid($params['id'])['gname'].$goods_attr_value;
        $total += $goods_info[$kk]['number'] * $goods_info[$kk]['price'];
      }
    }
    // 判断有误折扣
      $discount_info=Db::name('user')->alias('u')
                      ->where(['u.id'=>cmf_get_current_user_id()])
                      ->join('__USER_GROUP__ g','u.group_id=g.id')
                      ->field('u.id,u.group_id,g.discount,g.is_show')
                      ->find();
     if($discount_info['is_show'] == 1)
     {
       $total=($discount_info['discount']/100)*$total;
     }

    // 计算运费
      $freight_setting     = cmf_get_option('freight_setting');//运费
      $freight=$freight_setting['money'];
      $is_freight=false;
      if($total < $freight_setting['full'])
      {
        $is_freight=true;
        $total=$total+ $freight;
      }
      $total=round($total,2);

    // 计算积分，获取积分比例
      $present_manage     = cmf_get_option('present_manage');

      $point=ceil($total * ($present_manage['point']));
    // 查询可用优惠券
    $now=time();
    $coupon=Db::name('user_coupon') ->alias('a')
                            ->where(['a.owner'=>$userid,'a.status'=>0])
                            ->join('__COUPON__ c','a.type_id = c.id','LEFT')
                            ->field('a.*,c.money,c.least_money,c.start_time,c.end_time')
                            ->where(['c.start_time'=>['<',$now],'c.end_time'=>['>',$now]])
                            ->where(['c.least_money'=>['<=',$total]])
                            ->select();
   //发票缓存是否写出?

   $this->assign('voice',$voice);

    $this->assign('coupon',$coupon);
    $this->assign('point',$point);
    // 积分比例
    $this->assign('proportion',$present_manage['point']);
    // 收货地址id
    $this->assign('useradd',$useradd);
    $this->assign('total',$total);
    $this->assign('list',$goods_info);
    $this->assign('address',$default_address);
    $this->assign('freight',$freight);
    $this->assign('is_freight',$is_freight);
    $this->assign('freight_setting',$freight_setting);
    $this->assign('discount_info',$discount_info);


    return  $this->fetch();

  }




  // 提交订单
  public function postOrder()
  {
    // post提交订单
    if($this->request->isPost())
    {
      $data=$this->request->param();
      // 计算总价,单个商品
      if(isset($data['cid']) && isset($data['num']))
      {
        // 购物车购买
        $cid=$data['cid'];
        $cid=array_filter(explode(",",$cid));
        // 购买数量
        $num=$data['num'];
        $num=array_filter(explode(",",$num));
        $goods=Db::name('cart')->where(['id'=>['in',$cid]])->select();
        $total=0;
        foreach($goods as $k => $v){
          $goods_info[$k]['id']=$v['goods_id'];
          $goods_info[$k]['cid']=$v['id'];//购物车商品id号
          $goods_info[$k]['number'] = $num[$k];
          $goods_info[$k]['price']=getGoodsInfoByid($v['goods_id'])['gprice'];
          $goods_info[$k]['goods_attr_value']=$v['goods_attr_value'];
          $goods_info[$k]['goods_attr_id']=$v['goods_attr_id'];
          $total += $goods_info[$k]['number'] * $goods_info[$k]['price'];
        }
      }else
      {
        //直接购买
        $goodsinfo=getGoodsInfoByid($data['gid']);
        $total= $goodsinfo['gprice'] * $data['number'];
      }
      // 判断有误折扣
      $discount_info=Db::name('user')->alias('u')
                      ->where(['u.id'=>cmf_get_current_user_id()])
                      ->join('__USER_GROUP__ g','u.group_id=g.id')
                      ->field('u.id,u.group_id,g.discount,g.is_show')
                      ->find();
      $discount=0;
      if($discount_info['is_show'] == 1)
      {
        $discount=(100-$discount_info['discount'])/100 * $total;
      }
      // 收货人信息
      $useradd=Db::name('user_address')->where(['id'=>['=',$data['useradd']]])->find();

      // 判断是否有用优惠券
      $coupon= $data['coupon'] ? explode(',',$data['coupon'])[1]: '';
      $total_actual=$total-$discount;
      $coupon_money=0;
      if($coupon)
      {
        // 判断一下优惠群的有效期 金额等
        $coupon_info=Db::name('user_coupon')->alias('a')
                                            ->field('a.*,c.money,c.start_time,c.end_time,c.least_money')
                                            ->where(['code'=>['=',$coupon]])
                                            ->join('__COUPON__ c','a.type_id = c.id')
                                            ->where(['c.start_time'=>['<',time()]])
                                            ->where(['c.end_time'=>['>',time()]])
                                            ->find();
        $coupon_money=$coupon_info['money'];
        $total_actual = isset($coupon_info) ? ($total_actual - $coupon_money) : $total_actual;
      }
      // 计算运费
        $freight_setting     = cmf_get_option('freight_setting');//运费
        $freight=$freight_setting['money'];
        $is_freight=false;
        if($total_actual < $freight_setting['full'])
        {
          $total_actual=$total_actual + $freight;//计算运费
          $is_freight=true;
        }
      // 计算积分
      $present_manage     = cmf_get_option('present_manage');

      $point=ceil($total_actual * ($present_manage['point']));

      // 商品入订单库
      $order['userid'] = cmf_get_current_user_id();
      $order['orderid']=date('Ymd').sprintf("%04d", rand(1,9)).date('His').rand(100000,999999);
      $order['inputtime']=time();
      $order['pay_status'] = 0;
      $order['total_price'] =$total;
      $order['coupon'] =$coupon_money;
      $order['points'] =$point;
      $order['receiver_name'] =$useradd['name'];
      $order['receiver_province'] =$useradd['receiver_province'];
      $order['receiver_city'] =$useradd['receiver_city'];
      $order['receiver_area'] =$useradd['receiver_area'];
      $order['receiver_address'] =$useradd['address'];
      $order['receiver_mobile'] =$useradd['phone'];
      $order['message'] =$data['message'];
      $order['total_actual'] = $total_actual;//实际价格
      $order['discount'] =$discount;//折扣
      $order['freight'] = $is_freight ? $freight : 0;//运费

      $res=Db::name('order')->insert($order);
      // 更新优惠券状态
      if($coupon){
        Db::name('user_coupon')->where(['code'=>$coupon,'owner'=>cmf_get_current_user_id()])->update(['status'=>1,'order_id'=>$order['orderid'],'use_time'=>time()]);
      }
      if($res)
      {
        if(isset($data['cid']) && isset($data['num']))
        {
          // 购物车购买
          foreach($goods_info as $k=>$v)
          {
            $order_detail[$k]['orderid']=$order['orderid'];
            $order_detail[$k]['goods_id']=$v['id'];
            $order_detail[$k]['goods_attr_id']=$v['goods_attr_id'];
            $order_detail[$k]['goods_attr_value']=$v['goods_attr_value'];
            $order_detail[$k]['goods_price']=$v['price'];
            $order_detail[$k]['num']=$v['number'];
            // 删除购物车
            Db::name('cart')->where(['id'=>$v['cid']])->delete();
          }
          // 批量插入多条
          Db::name('order_detail')->insertAll($order_detail);
        }else
        {
          // 单个商品购买   商品属性
          $goods_attr=explode('---',$data['goods']);
          $goods_attr_value="";
          $goods_attr_ids="";
          foreach($goods_attr as $v)
          {
            $attrs_arr=explode(',',$v);//0 属性名称，1属性id，2属性值
            $goods_attr_value .= $attrs_arr[0].":".$attrs_arr[2].",";
            $goods_attr_ids .= $attrs_arr[1].",";
          }
          $goods_attr_value='('.rtrim($goods_attr_value,',').')';
          // 商品信息入库
          $order_detail['orderid']=$order['orderid'];
          $order_detail['goods_id']=$goodsinfo['gid'];
          $order_detail['goods_attr_id']=$goods_attr_ids;
          $order_detail['goods_attr_value']=$goods_attr_value;
          $order_detail['goods_price']=$goodsinfo['gprice'];
          $order_detail['num']=$data['number'];
          Db::name('order_detail')->insert($order_detail);
        }
        // 发票信息入库
        $voice=Cache::get('voice-'.cmf_get_current_user_id());
        if($voice){
          $voice=unserialize($voice);
          $voice['order_id']=$order['orderid'];
          Db::name('order_invoice')->insert($voice);
          // 入库后将发票缓存清除
          Cache::rm('voice-'.cmf_get_current_user_id());
        }
      }
      // $this->success("订单提交成功",url('user/Pay/index',array('orderid'=>$order['orderid'])));
      $this->redirect('user/Pay/index', ['orderid' => $order['orderid']]);
    }

  }
  public function invoice()
  {

    return   $this->fetch();
  }
  // 将发票信息
  public function postInvoice()
  {
    $userid=cmf_get_current_user_id();
    $params=$this->request->param();
    $rule = [
         ['company','require|max:25','公司名或姓名必须填写!~|公司或姓名最多不能超过25个字符!~'],
         ['description','max:200','发票内容请控制在200字之内!~'],
        'email|邮箱'          => 'email',
        'tel|手机号'          => ['regex'=>'/^1[3|5|6|7|8|9][0-9]{9}$/i'],
        'tel|手机号'          =>'require',

    ];
     $validate= Validate::make();
    if($params['type'] == 0)
    {
      //个人
     $res=$validate->rule($rule)->check($params);
    if(!$res)
    {
     $this->error($validate->getError());
    }
    // 将信息存入缓存
    Cache::set('voice-'.$userid,serialize($params),7200);
    $this->success("添加成功",url(cookie('order_back_url')));
    }elseif($params['type'] == 1)
    {

      $rules = [
           ['company','require|max:25','公司名或姓名必须填写!~|公司或姓名最多不能超过25个字符!~'],
           ['number','require|max:25','纳税人识别号不能为空!~|纳税人识别号规格不符!~'],
           ['description','max:200','发票内容请控制在200字之内!~'],
          'email|邮箱'          => 'email',
          'tel|手机号'          => ['regex'=>'/^1[3|5|6|7|8|9][0-9]{9}$/i'],
          'tel|手机号'          =>'require',
      ];
      // 公司
      $res=$validate->rule($rules)->check($params);
     if(!$res)
     {
      $this->error($validate->getError());
     }
     Cache::set('voice-'.$userid,serialize($params),7200);
     $this->success("添加成功",url(cookie('order_back_url'),'',false));


    }

  }
  public function open(){
    $id  = $this->request->param('id');
    $data        = Db::name('order')->where('id',$id)->find();
    $orderid     = $data['orderid'];
    $res         = Db::name('order')->where('id',$id)->update(['pay_status'=>'2','order_status'=>'2']);
    $res         = Db::name('order_invoice')->where('order_id',$orderid)->update(['is_evaluate'=>'3']);
    $this->success('订单取消成功');
  }

  public function delete(){

    $id          = $this->request->param('id');
    $data        = Db::name('order')->where('id',$id)->find();
    $orderid     = $data['orderid'];
    $res         = Db::name('order')->where('id',$id)->delete();
    $res         = Db::name('order_invoice')->where('order_id',$orderid)->delete();
    $this->success('订单删除成功');
  }

  /*7.2*/
  public function is_del(){

    $id  = $this->request->param('id');
    $res         = Db::name('order')->where('id',$id)->update(['is_del'=>'1','post_status'=>'4']);
    $this->success('此订单信息清除!');

  }

  /*7.6*/
  public function is_collect(){

    $id =   $this->request->param('pid');
    $time = time();
    $res=   Db::name('order')->where('id',$id)->update(['post_status'=>2,'post_times'=>$time]);
    $res=   Db::name('order_bill')->where('oid',$id)->update(['l_status'=>2,'l_times'=>$time]);
    if($res){
      $this->success("已确认收货!");
       }
  }

  /*订单详情*/
  public function detail()
  {
    //订单编号
    $orderidd     = $this->request->param('id');
    $order        = Db::name('order o')->where('id',$orderidd)->find();

    //订单商品查询
   $where         = ' 1 =1';
   $orderid       = $order['orderid'];
   $where        .= " and d.orderid = '$orderid'";

   $order_detail  = Db::name('order_detail d')
                  ->where($where)
                  ->join('goods g','g.gid = d.goods_id')
                  ->field('d.*,g.gname,g.goods_image')
                  ->select();
   //订单发表查询
   $invoice       = Db::name('order_invoice')->where('order_id',$orderid)->find();
   //商品数量
   $num           = Db::name('order_detail')->where('orderid',$orderid)->select();
  //订单发货表
   $bill          = Db::name('order_bill')->where('l_order',$orderid)->find();

    foreach ($num as $k => $v) {
      $arr[] = $v['num'];
    }
   $num           = array_sum($arr);

    $this->assign('num',$num);              //商品数量
    $this->assign('invoice',$invoice);
    $this->assign('orderid',$orderid);
    $this->assign('order_detail',$order_detail);
    $this->assign('order',$order);
    $this->assign('bill',$bill);
    $this -> assign('order',$order);//订单基本信息


    return $this->fetch();
  }

  /*退款*/
  public function refund() {

    $orderid      = $this->request->param('orderid');  //订单号;
    $total_actual = $this->request->param('total_actual');  //订单总价;

    $info         = Db::name('order')->where('orderid',$orderid)->find();
    $this->assign('info',$info);
    $this->assign('total_actual',$total_actual);
    $this->assign('orderid',$orderid);
    return $this->fetch();
  }
  //验证退款信息;
   public function Dorefund() {

     if ($this->request->isPost()) {

       $ArrDate = $this->request->post();

       $rule     = [
         ['name','require|max:25','姓名必须填写!~|名字最多不能超过25个字符!~'],
         'phone|手机号'    => ['regex'=>'/^1[3|5|6|7|8|9][0-9]{9}$/i'],
         'phone|手机号'    =>'require',
         ['order_explain','require|max:300','请您填写退款原因~|退款说明要在300字之内!~'],
       ];

       $validate = Validate::make();

       $res      = $validate->rule($rule)->check($ArrDate);

       if (!$res) {

         $this->error($validate->getError());

       }


        $data['orderid']      = $ArrDate['orderid'];
        $data['username']     = $ArrDate['name'];
        $data['tel']          = $ArrDate['phone'];
        $data['order_money']  = $ArrDate['order_money'];
        $data['order_explain']= $ArrDate['order_explain'];
        $data['is_evaluate']  = '1';
        $data['createtime']   = time();


      $uid      = cmf_get_current_user_id();                                    //获取当前用户的id
      $user     = Db::name('user')->where('id',$uid)->find();                   //获取当前用户的信息


        $datas['type']        = '3';
        $datas['user_id']     = $uid;
        $datas['money']       = $ArrDate['order_money'];
        $datas['log_name']    = '申请退款';
        $datas['time']        = time();

      $res = Db::name('record')->insert($datas);                                                              //插入用户操作表状态
      $res = Db::name('order_refund')->insert($data);                                                         //插入订单退款表     状态受理中
      $res = Db::name('order')->where('orderid',$ArrDate['orderid'])->update(['order_status'=>'3','pay_status'=>'3','post_status'=>'3']);          //更改订单表         状态受理中
      $res = Db::name('order_bill')->where('l_order',$ArrDate['orderid'])->update(['is_evaluate'=>'1']);      //更改发货状态       退款状态
      $res = Db::name('order_invoice')->where('order_id',$ArrDate['orderid'])->update(['is_evaluate'=>'1']);  //更改发票状态       退款状态
      $this->success('订单退款受理中...请耐心等候',url('user/order/index'),'',5);

     }

   }

}
