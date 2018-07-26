<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:kane < chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\portal\model;

use think\Model;

class AttributeModel extends Model
{
    //自动获取
    public function getInputtypeAttr($value)
    {
        $status = [0=>'手工录入',1=>'列表选择'];
        return $status[$value];
    }
    // 可选值
    public function getContentAttr($value)
    {
      if(!empty($value))
      {
        $arr=explode(PHP_EOL,$value);
        return implode(",",$arr);
      }
    }

}
