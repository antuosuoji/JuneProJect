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

class CouponController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
    }

	public function index() {

          $uid    = cmf_get_current_user_id();

		      $where  = "1 =1";
					$status=$this->request->param('status');
					$status = $status === '0' || !isset($status) ? 0 : 1;
					$where .= " and c.status = '$status'";
		      $where .= " and c.owner = '$uid'";
          // 查询数据库
		      $coupon = Db::name('user_coupon as c')
		              ->where($where)
		              ->join('coupon n','n.id = c.type_id')
		              ->join('user u','u.id = c.owner')
		              ->field('n.*,c.status,c.id')
									->paginate('10');

				$this->assign('page', $coupon->render());
				$this->assign('coupon',$coupon);
				return $this->fetch();
	}

  public function delete() {

        $id   =  $this->request->param('id');
        $res  =  Db::name('user_coupon')->where('id',$id)->update(['status'=>'3']);
        $this -> success('删除成功!');


  }


}
