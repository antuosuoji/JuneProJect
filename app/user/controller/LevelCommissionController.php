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
use app\user\model\LevelCommissionModel;
use think\Validate;

/**
 * 分销等级设置
 * )
 */
class LevelCommissionController extends AdminBaseController
{
  public function _initialize()
  {
      parent::_initialize();
      $this->levelCommissionModel=new LevelCommissionModel();
  }
  public function index()
  {
    $keyword=$this->request->param('keyword');

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
    $list           = $this->levelCommissionModel
                                    ->alias('a')
                                    ->where($where)
                                    ->field('a.*,u.user_login')
                                    ->join('__USER__ u','a.userid = u.id')
                                    ->paginate(10);
    $this->assign("list",$list);
    $this->assign('page', $list->render());
    // 获取分销级别
    $level_setting=cmf_get_option('level_settings');
    // 获取userid对应的分销会员
    $res=getParentUserid(10,1,$level_setting['level']);
    // dump($res);
    return $this->fetch();
  }
  public function delete()
  {
    $param           = $this->request->param();
    // 单个删除
    if(isset($param['id']))
    {
      $id           = $this->request->param('id', 0, 'intval');
      $this->levelCommissionModel->where(['id'=>$id])->delete();
      $this->success("删除成功");
    }
    // 批量删除
    if(isset($param['ids']))
    {
        $ids     = $this->request->param('ids/a');
        $result=$this->levelCommissionModel->where(['id' => ['in', $ids]])->delete();
        if ($result)
        {
            $this->success("删除成功！", '');
        }
    }
  }


}
