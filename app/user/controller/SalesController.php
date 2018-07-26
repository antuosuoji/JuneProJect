<?php
namespace app\user\controller;
use cmf\controller\UserBaseController;
use think\Db;
class SalesController extends UserBaseController
{

  public function index()
  {
    // $user = cmf_get_current_user();
    $userid=cmf_get_current_user_id();
    $user=Db::name('user')->where(['id'=>$userid])->field('user_pass,user_url',true)->find();
    // 下线数量
    $level_setting=cmf_get_option('level_settings');
    $downline=getChildUserid($userid,1,$level_setting['level']);
    // 提现数量
    $put_num=Db::name('level_put')->where(['userid'=>$userid])->count();
    // 获取佣金笔数
    $comm_num=Db::name('level_commission')->where(['userid'=>$userid])->count();
    // 累计佣金
    $comm_total=Db::name('level_commission')->where(['userid'=>$userid])->sum('commission');
    // 佣金总计 ，提现，剩余
    $user_comm=Db::name('user_commission')->where(['userid'=>$userid])->find();
    $already_put=Db::name('level_put')->where(['userid'=>$userid,'status'=>1])->sum('money');//已提现
    $able_put=0;//可提现
    if($user_comm)
    {
      $able_put=$user_comm['total'];
    }
    $this->assign('comm_num', $comm_num);//佣金笔数
    $this->assign('comm_total', $comm_total);//分销总佣金
    $this->assign('downline',count($downline));//下线数量
    $this->assign('put',$put_num);//提现笔数
    $this->assign('already_put',$already_put);//成功提现
    $this->assign('able_put',$able_put);//可提现

    $this->assign($user);
    return  $this->fetch();
  }
  public function spread()
  {
      // 推广二维码
      $userid=cmf_get_current_user_id();
      $user =Db::name('user')->where(['id'=>$userid])->find();
      if(empty($user['mobile']))
      {
        return $this->fetch('empty_spread');
      }
      // $user = cmf_get_current_user();
      $this->assign($user);
      // 生成二维码
      // 二维码信息图片
      $base_url="http://qr.liantu.com/api.php?&w=150&text=";

      $params="http://".$this->request->server('SERVER_NAME')."/user/register/index/code/".$user['mobile'].".html";
      $url=$base_url.$params;
      $this->assign('url',$url);
      return $this->fetch();
  }
  public function commission()
  {
    // 佣金明细
    $user = cmf_get_current_user();
    $userid=cmf_get_current_user_id();
    $list=Db::name('level_commission')->where(['userid'=>$userid])->paginate();
    // 累计佣金
    $total=Db::name('level_commission')->where(['userid'=>$userid])->sum('commission');

    $this->assign("list", $list);
    $this->assign('page', $list->render());
    $this->assign('total', $total);

    $this->assign($user);
    return $this->fetch();
  }
  // 分销等级佣金
  public function levelCommission()
  {
    $userid=cmf_get_current_user_id();
    $level=$this->request->param('level');
    $where['child_level']=$level;
    $where['userid']     =$userid;
    $list=Db::name('level_commission')->where($where)->paginate();
    $level_1_num=Db::name('level_commission')->where(['userid'=>$userid,'child_level'=>1])->count();
    $level_2_num=Db::name('level_commission')->where(['userid'=>$userid,'child_level'=>2])->count();
    $level_3_num=Db::name('level_commission')->where(['userid'=>$userid,'child_level'=>3])->count();
    $this->assign('level_1',$level_1_num);
    $this->assign('level_2',$level_2_num);
    $this->assign('level_3',$level_3_num);
    $this->assign('list',$list);
    $this->assign('page',$list->render());
    return $this->fetch();
  }
  // 佣金提现
  public function put()
  {
    $userid=cmf_get_current_user_id();
    if($this->request->isPost())
    {
      $data=$this->request->param();
      if (is_numeric($data['money']) && !strpos($data['money'], '.') && $data['money'] >= 100)
      {
        // 判断佣金是否充足
        $has_comm=Db::name('user_commission')->where(['userid'=>$userid])->find();
        if($has_comm['total'] < $data['money']){
          $this->error('您的佣金余额不足');
        }
        // 判断当天申请次数
        $today = strtotime(date("Y-m-d"),time());  // 获取当天凌晨时间戳
        $end   = $today + 60*60*24;      //获取当天24点时间戳
        $count=Db::name('level_put')->where(['userid'=>['=',$userid],'inputtime'=>['>',$today],'inputtime'=>['<',$end]])->count();
        if($count < 5)
        {
          $info['userid']=$userid;
          $info['money'] =$data['money'];
          $info['inputtime']=time();
          $info['status'] =0;
          Db::name('level_put')->insert($info);
          // 减掉佣金
          Db::name('user_commission')->where(['userid'=>$userid])->setDec('total',$data['money']);
          Db::name('user_commission')->where(['userid'=>$userid])->setInc('already_put',$data['money']);
          $this->success('申请成功，待管理员审核',url('sales/index'));
        }else
        {
          $this->error('您当前申请已超过 5 次');
        }
      }else{
        $this->error('提现金额不合法');
      }
      return dump($data);
    }else
    {
      return $this->fetch();
    }
  }
  public function putforward()
  {
    // 佣金提现明细
    $user = cmf_get_current_user();
    $userid=cmf_get_current_user_id();
    $status=$this->request->param('status');
    $where['userid']=$userid;
    $where['status']=$status;
    $list=Db::name('level_put')->where($where)->paginate();

    $this->assign("list", $list);
    $this->assign('page', $list->render());
    $this->assign($user);

    return $this->fetch();
  }
  public function downline()
  {
    // 我的下线
    $user = cmf_get_current_user();
    $userid=cmf_get_current_user_id();
    $this->assign($user);
    // 获取下线数据
    // 获取分销级别
    $level_setting=cmf_get_option('level_settings');
    $list=getChildUserid($userid,1,$level_setting['level']);
    $level_1=array();
    $level_2=array();
    $level_3=array();
   foreach($list as $v)
   {
     if($v['level'] == 1)
     {
       $level_1[]=$v;
     }elseif($v['level'] ==2)
     {
       $level_2[]=$v;
     }elseif($v['level'] ==3)
     {
        $level_3[]=$v;
     }
   }
    $this->assign('level_1',$level_1);
    $this->assign('level_2',$level_2);
    $this->assign('level_3',$level_3);
    return $this->fetch();
  }
}
