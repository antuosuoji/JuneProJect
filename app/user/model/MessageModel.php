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
namespace app\user\model;

use think\Db;
use think\Model;

class MessageModel extends Model
{

  public function getContentAttr($value)
  {
      return cmf_replace_content_file_url(htmlspecialchars_decode($value));
  }

  /**
   * post_content 自动转化
   * @param $value
   * @return string
   */
  public function setContentAttr($value)
  {
      return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
  }

  public function getUseridsAttr($value)
  {
    return explode(",",$value);
  }

}





?>
