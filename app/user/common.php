<?php
use think\Config;
use think\Db;

//--------订单下级详情----------------

function  make_order_detail($orderid){

    //orderid订单号;

    $res = Db::name('order_detail')->where('orderid',$orderid)->select()->toArray();

    return $res;


}
