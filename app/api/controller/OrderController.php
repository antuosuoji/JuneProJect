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
use think\Db;
use think\Cookie;

class OrderController{

  public function index(){

    $res  = Db::name('order')->where('pay_status',0)->select();
    $time = time();
    $hour = '7200';
    foreach ($res as $key => $val) {
        if (($time-$val['inputtime']) > $hour) {
          Db::name('order')->where('id',$val['id'])->update(['pay_status'=>'2','order_status'=>'2']);
          Db::name('order_invoice')->where('order_id',$val['orderid'])->update(['is_evaluate'=>'3']);
        }
    }
  }
}
