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
class LevelSettingController extends AdminBaseController
{

  public function set()
  {
    $levelSettings  = cmf_get_option('level_settings');
    $this->assign("level_settings",$levelSettings);
    return $this->fetch();
  }
  //入库
  public function setPost()
  {
    if($this->request->isPost())
    {
      $arrData=$this->request->param("level_settings/a");
      $is_true=Validate::make()
      ->rule("level", 'require|number')
      ->rule("first_score", 'number|gt:0')
      ->rule("second_score", 'number|gt:0')
      ->rule("third_score", 'number|gt:0')
      ->check($arrData);
      if($is_true == false)
      {
        $this->error("信息填写不正确");
      }
      switch ($arrData['level']) {
        case '1':
          $arrData['second_score']=0;
          $arrData['third_score']=0;
          break;
        case '2':
          $arrData['third_score']=0;
          break;
      }
      cmf_set_option('level_settings', $arrData);
      $this->success("设置成功");


    }
  }

}
