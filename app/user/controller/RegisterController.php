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

use cmf\controller\HomeBaseController;
use think\Validate;
use app\user\model\UserModel;

class RegisterController extends HomeBaseController
{

    /**
     * 前台用户注册
     */
    public function index()
    {
        $redirect = $this->request->post("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            $redirect = base64_decode($redirect);
        }
        session('login_http_referer', $redirect);

        if (cmf_is_user_login()) {
            return redirect($this->request->root() . '/');
        } else {
            return $this->fetch(":register");
        }
    }

    /**
     * 前台用户注册提交
     */
    public function doRegister()
    {
        if ($this->request->isPost()) {

            $rules = [
                'username|手机号'         =>   'require|unique:user,mobile',
                'captcha|图形验证码'  => 'require',
                'verification_code|手机验证码'  => 'require',
                'invitation_code' => 'require',
                'password'        => 'require|min:6|max:32|confirm',
            ];
            $isOpenRegistration = cmf_is_open_registration(); //是否注册验证，如果是 返回false

            if ($isOpenRegistration) {
                unset($rules['code']);
            }

            $validate = new Validate($rules);
            $validate->message([
                'username.unique' => '手机号已经被注册',
                'password.require' => '密码不能为空',
                'password.max'     => '密码不能超过32个字符',
                'password.min'     => '密码不能小于6个字符',
                'invitation_code.require' =>'邀请码不能为空!',
                'password.confirm' => '两次密码不一致',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            //验证 验证码的正确性
            $captchaId = empty($data['_captcha_id']) ? '' : $data['_captcha_id'];
            if (!cmf_captcha_check($data['captcha'], $captchaId)) {
                $this->error('图形验证码错误');
            }

            //验证短信正确性
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }


            $register          = new UserModel();
            $user['user_pass'] = $data['password'];
            $user['invitation_code'] = $data['invitation_code'];//邀请码
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $register->register($user,3);
            } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
              // 验证邀请码格式
               if(preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['invitation_code'])){

                 $user['mobile'] = $data['username'];
                 $log            = $register->register($user,2);
               }else
               {
                 $log = 3;
               }
            } else {
                $log = 2;
            }
            $sessionLoginHttpReferer = session('login_http_referer');
            $redirect                = empty($sessionLoginHttpReferer) ? cmf_get_root() . '/' : $sessionLoginHttpReferer;
            switch ($log) {
                case 0:
                    $this->success('注册成功', $redirect);
                    break;
                case 1:
                    $this->error("您的账户已注册过");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                case 3:
                    $this->error("邀请码输入不合法");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        } else {
            $this->error("请求错误");
        }

    }
}
