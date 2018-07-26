<?php

namespace app\portal\model;

use think\Model;

class OrderModel extends Model {

  public function show(){

    $order = $this->select();
    return $order;
  }

}
