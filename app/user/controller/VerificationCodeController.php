<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use think\Validate;
use think\Sms;
class VerificationCodeController extends HomeBaseController
{
    public function send()
    {
        vendor('aliyun_sdk/Sms');//引入发送短信类
        $validate = new Validate([
            'username' => 'require',
            // 'captcha'  => 'require',
        ]);

        $validate->message([
            'username.require' => '请输入手机号或邮箱!',
            // 'captcha.require'  => '图片验证码不能为空',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        // 注册时  找回密码 需要验证 图形验证码
        if( (isset($data['type']) && $data['type'] == 'register') || (isset($data['type']) && $data['type'] == 'find_password'))
        {

          $captchaId = empty($data['captcha_id']) ? '' : $data['captcha_id'];
          if (!cmf_captcha_check($data['captcha'], $captchaId, false)) {
              $this->error('图片验证码错误!');
          }

          $registerCaptcha = session('register_captcha');

          session('register_captcha', $data['captcha']);

          if ($registerCaptcha == $data['captcha']) {
              cmf_captcha_check($data['captcha'], $captchaId, true);
              $this->error('请输入新图片验证码!');
          }
        }



        $accountType = '';

        if (Validate::is($data['username'], 'email')) {
            $accountType = 'email';
        } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['username'])) {
            $accountType = 'mobile';
        } else {
            // $this->error("请输入正确的手机或者邮箱格式!");
            $this->error("请输入正确的手机号!");
        }

        if (isset($data['type']) && $data['type'] == 'register') {
            if ($accountType == 'email') {
                $findUserCount = db('user')->where('user_email', $data['username'])->count();
            } else if ($accountType == 'mobile') {
                $findUserCount = db('user')->where('mobile', $data['username'])->count();
            }

            if ($findUserCount > 0) {
                $this->error('账号已注册！');
            }
        }

        //TODO 限制 每个ip 的发送次数，获取随机产生的验证码

        $code = cmf_get_verification_code($data['username']);
        if (empty($code)) {
            $this->error("验证码发送过多,请明天再试!");
        }

        if ($accountType == 'email') {

            $emailTemplate = cmf_get_option('email_template_verification_code');

            $user     = cmf_get_current_user();
            $username = empty($user['user_nickname']) ? $user['user_login'] : $user['user_nickname'];

            $message = htmlspecialchars_decode($emailTemplate['template']);
            $message = $this->display($message, ['code' => $code, 'username' => $username]);
            $subject = empty($emailTemplate['subject']) ? 'ThinkCMF验证码' : $emailTemplate['subject'];
            $result  = cmf_send_email($data['username'], $subject, $message);

            if (empty($result['error'])) {
                cmf_verification_code_log($data['username'], $code);
                $this->success("验证码已经发送成功!");
            } else {
                $this->error("邮箱验证码发送失败:" . $result['message']);
            }

        } else if ($accountType == 'mobile') {
           $sms_type=$this->request->param('type');

            switch ($sms_type)
            {
              case 'editmobile':
                $sms_tpl='SMS_138145022';
                break;
              case 'register':
                $sms_tpl='SMS_138145024';
                break;
              case 'find_password':
                $sms_tpl='SMS_138072425';
                break;
              default:
                $sms_tpl='SMS_138145027';
                break;
            }


            $param  = ['mobile' => $data['username'], 'code' => $code];
            // $result = hook_one("send_mobile_verification_code", $param);
            // 发送短信
            $sms=new Sms($data['username'],$sms_tpl,$code);
            $result=$sms->sendSms();
            if($result->Code == 'OK')
            {
              $expireTime =time()+60*10;//有效期10分钟
              cmf_verification_code_log($data['username'], $code, $expireTime);
              $this->success('验证码已经发送成功!');
            }else
            {
              $this->error('您今天获取次数过多');
            }

            cmf_verification_code_log($data['username'], $code, $expireTime);
        }


    }

}
