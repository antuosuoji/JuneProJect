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

use think\Db;

class IntegralController extends UserBaseController {

    function _initialize() {

        parent::_initialize();
    }

    public function index(){

      $user   = cmf_get_current_user();
      $user   = Db::name('user')->where('id',$user['id'])->find();

      $uid    = cmf_get_current_user_id();
      $where  = " 1 = 1";
      $where .= " and o.userid ='$uid'";
      $where .= " and o.pay_status ='1'";

      $inte  = Db::name('order o')
             ->where($where)
             ->field('o.points,o.inputtime,o.pay_status,o.is_refund')
             ->paginate(10);

      $this->assign($user);
      $this->assign('inte',$inte);
      $this->assign('page',$inte->render());

      return $this->fetch();

    }

}
