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
namespace app\portal\controller;

use cmf\controller\AdminBaseController;
use app\portal\model\CouponModel;
use app\portal\model\UserCouponModel;
use think\Validate;
use think\Db;
class AdminCouponController extends AdminBaseController
{
    public function __construct()
    {
      parent::__construct();

      $this->couponModel=new CouponModel();
      $this->userCouponModel=new UserCouponModel();
    }

    public function index()
    {
      $coupons           = $this->couponModel->paginate();
      $this->assign("coupons", $coupons);
      $this->assign('page', $coupons->render());
      return $this->fetch();
    }
    // 编辑 修改
    public function edit()
    {
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();
        // 验证
        $is_true=Validate::make()
        ->rule('name', 'require')
        ->rule('money', 'require|number')
        ->rule('least_money', 'require|number')
        ->rule('start_time', 'require|date')
        ->rule('end_time', 'require|date')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("请您将信息填写规范后提交");
        }
        $arrData['start_time']=strtotime($arrData['start_time']);
        $arrData['end_time']=strtotime($arrData['end_time']);
        $this->couponModel->isUpdate(true)->allowField(true)->save($arrData);
        $this->success("修改成功",url("AdminCoupon/index"));
      }else
      {
        $Id=$this->request->param("id",'','intval');
        $info=$this->couponModel->get($Id);
        $this->assign("info",$info);
        return $this->fetch();
      }
    }
    // 添加优惠券
    public function add()
    {
      if($this->request->isPOST())
      {
        $arrData=$this->request->param();
        // 验证
        $is_true=Validate::make()
        ->rule('name', 'require')
        ->rule('money', 'require|number')
        ->rule('least_money', 'require|number')
        ->rule('start_time', 'require|date')
        ->rule('end_time', 'require|date')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("请您将信息填写规范后提交");
        }
        $arrData['start_time']=strtotime($arrData['start_time']);
        $arrData['end_time']=strtotime($arrData['end_time']);
        $this->couponModel->isUpdate(false)->allowField(true)->save($arrData);
        $this->success("添加成功",url("AdminCoupon/index"));

      }else
      {
        return $this->fetch();
      }
    }
    // 优惠券删除
    public function delete()
    {
        $Id=$this->request->param("id",0,'intval');
        if(empty($Id))
        {
          $this->error("类别ID错误");
        }else
        {
            $this->couponModel->where(['id'=>$Id])->delete();
        }
        $this->success("删除成功");

    }
    // 启用优惠券
    public function open()
    {
      $id=$this->request->param('id');
      $res=  $this->couponModel->isUpdate(true)->allowField(true)->save(['status'=>0],['id'=>$id]);
      if($res)
      {
        $this->success("启用成功");
      }
    }
    // 关闭优惠券
    public function close()
    {
      $id=$this->request->param('id');
      $res=  $this->couponModel->isUpdate(true)->allowField(true)->save(['status'=>1],['id'=>$id]);
      if($res)
      {
        $this->success("关闭成功");
      }
    }
    // 给会员发放优惠券
    public function grantCoupon()
    {
      if($this->request->isPost())
      {
        $arrData=$this->request->param();
        // 优惠券ID

        // 会员组发放
        if($arrData['mode'] == 0)
        {
          $user_=Db::name('user')->where('user_type',2)->where('group_id','in',$arrData['groups'])->select()->toArray();
          foreach($user_ as $v)
          {
            $users[]=$v['id'];
          }
        }else
        {
          $users=$arrData['userids'];
        }
        // 拼接一个二维数组，一次写入数据库
        $insertInfo=array();
        foreach($users as $k=>$v)
        {
          $card_id=make_coupon_card();
          $insertInfo[$k]['type_id']=$arrData['type_id'];
          $insertInfo[$k]['code']=$card_id;
          $insertInfo[$k]['owner']=$v;
        }
        $res=$this->userCouponModel->saveAll($insertInfo);
        if($res)
        {
          $this->success("发放成功",url("AdminCoupon/manageCoupon",['typeid'=>$arrData['type_id']]));
        }
      }else
      {
        $typeid=$this->request->param("typeid");
        $allTypes=$this->couponModel->getAllTypes();
        // 会员组
        $groupsInfo=Db::name('user_group')->select();
        // 会员
        $usersInfo=Db::name('user')->where('user_type',2)->select();
        $this->assign('groupsInfo',$groupsInfo);
        $this->assign('usersInfo',$usersInfo);
        $this->assign('allTypes',$allTypes);
        return $this->fetch();
      }
    }
    // 查看优惠券发放情况
    public function manageCoupon()
    {
      $typeid=$this->request->param("typeid");
      $where = $typeid ? " type_id = '$typeid' ": " 1 =1";
      $keyword=$this->request->param('keyword');
      $where .= $this->request->param('keyword') ? " and b.user_login like '%$keyword%'" : "";
      $status= $this->request->param('status');
      if($status === '0' || $status === '1')
      {
        $where .= " and status = '$status'";
      }
      $userCoupons           = $this->userCouponModel
                                    ->alias('a')
                                    ->join('__USER__ b','a.owner=b.id')
                                    ->field('a.*,b.user_login as name')
                                    ->where($where)
                                    ->paginate();
      $allTypes=$this->couponModel->getAllTypes();
      $this->assign("userCoupons", $userCoupons);
      $this->assign("allTypes", $allTypes);
      $this->assign('page', $userCoupons->render());
      return $this->fetch();
    }
    // 会员优惠券删除
    public function userCouponDel()
    {
      $param           = $this->request->param();
      // 单个删除
      if(isset($param['id']))
      {
        $id           = $this->request->param('id', 0, 'intval');
        $this->userCouponModel->where(['id'=>$id])->delete();
        $this->success("删除成功");
      }
      // 批量删除
      if(isset($param['ids']))
      {
            $ids     = $this->request->param('ids/a');
            $result=$this->userCouponModel->where(['id' => ['in', $ids]])->delete();
              if ($result)
              {
                  $this->success("删除成功！", '');
              }
      }

    }
}
