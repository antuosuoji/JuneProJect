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

use cmf\controller\AdminBaseController;
use think\Db;

use think\Validate;

/**
 * 分销等级设置
 * )
 */
class LevelUserController extends AdminBaseController
{
  public function index()
  {
    $keyword=$this->request->get('keyword');
    $where=array();
    if(isset($keyword))
    {
      if(preg_match('/^1[3|5|6|7|8|9][\d]{9}$/',$keyword,$match))
      {
        $where['mobile']=$keyword;
      }else{
        $where['user_login']=['like',"%$keyword%"];
      }
    }
    $list=Db::name("user")->where(['user_type'=>2,'user_status'=>1])->where($where)->paginate(10)->each(function($item,$key){
    $item['level_user']=$this->levelUser($item['id']);
       return $item;
    });
    $this->assign('list',$list);
    $this->assign('page',$list->render());
    return $this->fetch();
  }
  //获取制定用户分销会员
  public function detail()
  {
    $data=$this->request->param();
    $list=$this->levelUser($data['userid']);
    $this->assign('list',$list['level_'.$data['level']]);
    return $this->fetch();
  }
  /*
   获取分销会员
  */
  private function levelUser($userid)
  {
      $level_setting=cmf_get_option('level_settings');
      $level_user=getChildUserid($userid,1,$level_setting['level']);
      $users['level_1']=array();
      $users['level_2']=array();
      $users['level_3']=array();
     foreach($level_user as $v)
     {
       if($v['level'] == 1)
       {
         $users['level_1'][]=$v;
       }elseif($v['level'] ==2)
       {
         $users['level_2'][]=$v;
       }elseif($v['level'] ==3)
       {
         $users['level_3'][]=$v;
       }
     }
     return $users;

  }

}
