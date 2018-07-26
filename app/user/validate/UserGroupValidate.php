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
namespace app\user\validate;

use think\Validate;

class UserGroupValidate extends Validate
{
    protected $rule = [

        'name' => 'require',
        'lower_point' => 'require|number',
        'upper_point'   => 'require|number',
        'discount'   => 'require|number|between:1,100',
    ];
    protected $message = [
        'name.require'    => '会员等级不能为空!',
        'lower_point' => '积分下限必须是数字!',
        'upper_point' => '积分上限必须是数字!',
        'discount'=>'折扣率请填写1 - 100之间数字'
    ];

    protected $scene = [
    ];

}
