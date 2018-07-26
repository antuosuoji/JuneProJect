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

class UserValidate extends Validate
{
    protected $rule = [

        'user_login' => 'require|unique:user,user_login',
        'group_id' => 'require|number',
        'score'   => 'require|number',
        'user_email'   => 'require|email|unique:user,user_email',
        'mobile'   => ['regex'=>'/^1[3|5|6|7|8|9][0-9]{9}$/i'],
        'mobile'   =>'unique:user,mobile',
        'repassword'=>'confirm:user_pass',
        'user_pass'=>'require',

    ];
    protected $message = [
        'user_login.require'    => '用户名不能为空!',
        'user_login.unique'    => '用户名已存在!',
        'group_id' => '请选择会员组!',
        'score' => '积分必须是数字',
        'user_email.require' => '邮箱格式不正确',
        'user_email.unique' => '邮箱已被使用',
        'repassword.confirm' =>'密码输入不一致',
        'mobile' => '手机格式不正确',
        'mobile.unique' => '手机号已被使用',
        'user_pass' => '密码不能为空',
    ];
    // 路由验证
    protected $scene = [
        'add'  => ['user_login', 'user_email','user_pass'],
        'edit' => ['user_login', 'group_id','score','user_email','mobile','repassword'],
    ];

}
