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

use cmf\lib\Storage;
use think\Validate;
use think\Image;
use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use think\Db;

class ProfileController extends UserBaseController
{

    function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心首页
     */
    public function center()
    {

        // $user = cmf_get_current_user();
        $user=Db::name('user')->where(['id'=>cmf_get_current_user_id()])->find();
        unset($user['user_pass']);

        $userId = cmf_get_current_user_id();  //userid
        $order  = Db::name('order')->where('userid',$userId)->select();   //order表

        /*7.2*/
        $pcont = array();     //待付款
        $ycont = array();     //已发货
        $scont = array();     //已收货
        $qcont = array();     //已取消

        foreach ($order as $key => $val) {
          $pay[] = $val['pay_status'];                 //未付款
          $ord[] = $val['post_status'];                //发货状态:0:未发货,1:已发货,2:已收货,3:已取消
        }


        if (!isset($ord)) {

        $ycont == '0';
        $scont == '0';
        $qcont == '0';

      }else{

        foreach ($ord as $key => $val) {
          $val  == '1' ? ($ycont[] = $val) : $val;   //已发货
          $val  == '2' ? ($scont[] = $val) : $val;   //已收货
          $val  == '3' ? ($qcont[] = $val) : $val;   //已取消
        }
      }



        if (!isset($pay)) {
          $pcont == '0';
       }else{
        foreach ($pay as $key => $val) {
           $val  == '0' ? ($pcont[] = $val) : $val;
        }
      }



        $mess_num=Db::name('user_message')->where(['userid'=>$userId,'status'=>0])->count();
        $this->assign($user);
        $this->assign('mess_num',$mess_num);
        $this->assign('pcont',count($pcont));
        $this->assign('ycont',count($ycont));
        $this->assign('scont',count($scont));
        $this->assign('qcont',count($qcont));
        return $this->fetch();
    }

    /**
     * 编辑用户资料
     */
    public function edit()
    {
        // $user = cmf_get_current_user();
        $userid=cmf_get_current_user_id();
        $user=Db::name('user')->where(['id'=>$userid])->find();

        $this->assign($user);
        return $this->fetch('edit');
    }

    /**
     * 编辑用户资料提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'user_nickname' => 'max:32',
                'sex'           => 'between:0,2',
                'birthday'      => 'dateFormat:Y-m-d|after:-88 year|before:-1 day',
                'user_url'      => 'url|max:64',
                'signature'     => 'max:128',
            ]);
            $validate->message([
                'user_nickname.max'   => lang('NICKNAME_IS_TO0_LONG'),
                'sex.between'         => lang('SEX_IS_INVALID'),
                'birthday.dateFormat' => lang('BIRTHDAY_IS_INVALID'),
                'birthday.after'      => lang('BIRTHDAY_IS_TOO_EARLY'),
                'birthday.before'     => lang('BIRTHDAY_IS_TOO_LATE'),
                'user_url.url'        => lang('URL_FORMAT_IS_WRONG'),
                'user_url.max'        => lang('URL_IS_TO0_LONG'),
                'signature.max'       => lang('SIGNATURE_IS_TO0_LONG'),
            ]);
            $userid=cmf_get_current_user_id();
            $data   = $this->request->post();
            $name   = $data['user_nickname'];
            $res    = Db::name('user u')->where("id !=$userid")->where('user_nickname',$name)->count();
            if ($res > 0 ) {
              $this->error('该昵称已存在,请您换一个!~');
            }

            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $editData = new UserModel();
            if ($editData->editData($data)) {
                $this->success(lang('EDIT_SUCCESS'), "user/profile/center");
            } else {
                $this->error(lang('NO_NEW_INFORMATION'));
            }
        } else {
            $this->error(lang('ERROR'));
        }
    }

    /**
    *个人地址修改
    */

    public function address()
    {
      $user = cmf_get_current_user();
      $userId = cmf_get_current_user_id();
      $ress = Db::name('user_address')->where('user_id',$userId)->select()->toArray();
      $this->assign('ress',$ress);
      $this->assign($user);
      return $this->fetch();
    }

    /*删除*/

    public function adddle(){

      $id = $this->request->param('id');
      $res= Db::name('user_address')->where('id',$id)->delete();
      $this->success('删除成功!');

    }

    //默认地址

     public function open(){

       $id  = $this->request->param('id');
       $uid = cmf_get_current_user_id();
       $res = Db::name('user_address')->where('user_id',$uid)->update(['isdefault'=>'0']);
       $res = Db::name('user_address')->where('id',$id)->update(['isdefault'=>'1']);
      $this->success('默认选择成功');
     }


    /*个人地址编辑*/
    public function addressx(){

      $id     = $this->request->param('id');
      $ress   = Db::name('user_address')->where('id',$id)->find();
      $this->assign('ress',$ress);
      return $this->fetch();
    }

    public function addressPost(){

      if ($this->request->isPost()) {
          $data = $this->request->param();
          $rule = [
            ['address','require|max:30','收货地址还没有填写~|收货地址字数控制在30个字符!~'],
            ['name','require|max:25','姓名不能为空|姓名字数控制在25个字符!'],
            ['phone','require','手机号不能为空!'],
            'phone|手机号'          => ['regex'=>'/^1[3|5|6|7|8|9][0-9]{9}$/i'],
          ];
          $Validate = Validate::make();
          $res  = $Validate->rule($rule)->check($data);
          if (!$res) {
            $this->error($Validate->getError());
          }
          $res  = Db::name('user_address')->where('id',$data['id'])->update($data);
          $this->success('修改成功',url('user/profile/address'));
      }
    }

  /*个人地址添加*/

    public function addresss(){

      if ($this->request->isPost()){
        $rules  = [
               'name' => 'require',
              'phone' => 'require',
            'address' => 'require',
  'receiver_province' => 'require',
        ];

      $validate = new Validate($rules);

      $validate -> message([
        'name.require'      => '姓名不能为空',
        'phone.require'     => '手机号不能为空',
        'address.require'   => '地址不能为空',
      'receiver_province'   => '省份不能为空',
      ]);

      $data = $this->request->post();
      $uid  = cmf_get_current_user_id();
      $data['user_id'] = $uid;

      if (!(preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['phone']))) {
            $this->error('手机格式不正确');
      }
      if (!$validate->check($data)) {
          $this->error($validate->getError());
      }
      unset($data['from']);
      $res = Db::name('user_address')->insert($data);

      if ($res) {
        if($this->request->param('from')){
          $this->success('添加地址成功',url('user/profile/address',array('from'=>'order')));
        }else {
          $this->success('添加地址成功',url('user/profile/address'));
        }
      }


      }
      $user = cmf_get_current_user();
      $this->assign($user);
      return $this->fetch();
    }



      public function like() {

      $uid     = cmf_get_current_user_id();
      $where   = "1 =1";
      $where  .= " and f.user_id = '$uid'";
      $like    =Db::name('user_favorite as f')->where($where)
                             ->join('__GOODS__ g','g.gid =object_id')
                             ->field('f.*,g.gname,g.goods_image,g.gprice')
                             ->order("id DESC")
                             ->paginate(10);

     $this->assign('like',$like);
     $this->assign('page',$like->render());
      return $this->fetch();
      }

      public function likedle() {

        $id   = $this->request->param('id');

        $res  = Db::name('user_favorite')->where('id',$id)->delete();

        $this->success('删除成功!');


      }



    /**
     * 个人中心修改密码
     */
    public function password()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 个人中心修改密码提交
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'old_password' => 'require|min:6|max:32',
                'password'     => 'require|min:6|max:32',
                'repassword'   => 'require|min:6|max:32',
            ]);
            $validate->message([
                'old_password.require' => lang('old_password_is_required'),
                'old_password.max'     => lang('old_password_is_too_long'),
                'old_password.min'     => lang('old_password_is_too_short'),
                'password.require'     => lang('password_is_required'),
                'password.max'         => lang('password_is_too_long'),
                'password.min'         => lang('password_is_too_short'),
                'repassword.require'   => lang('repeat_password_is_required'),
                'repassword.max'       => lang('repeat_password_is_too_long'),
                'repassword.min'       => lang('repeat_password_is_too_short'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $login = new UserModel();
            $log   = $login->editPassword($data);
            switch ($log) {
                case 0:
                    $this->success(lang('change_success'));
                    break;
                case 1:
                    $this->error(lang('password_repeat_wrong'));
                    break;
                case 2:
                    $this->error(lang('old_password_is_wrong'));
                    break;
                default :
                    $this->error(lang('ERROR'));
            }
        } else {
            $this->error(lang('ERROR'));
        }

    }

    // 用户头像编辑
    public function avatar()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    // 用户头像上传
    public function avatarUpload()
    {
        $file   = $this->request->file('file');
        $result = $file->validate([
            'ext'  => 'jpg,jpeg,png',
            'size' => 1024 * 1024
        ])->move('.' . DS . 'upload' . DS . 'avatar' . DS);

        if ($result) {
            $avatarSaveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $avatar         = 'avatar/' . $avatarSaveName;
            // 对上传到图片进行等比裁剪
            $avatarPath = "./upload/" . $avatar;

            $avatarImg = Image::open($avatarPath);
            $avatarImg->thumb(400,400)->save($avatarPath);
            session('avatar', $avatar);

            return json_encode([
                'code' => 1,
                "msg"  => "上传成功",
                "data" => ['file' => $avatar],
                "url"  => ''
            ]);
        } else {
            return json_encode([
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ]);
        }
    }

    // 用户头像裁剪
    public function avatarUpdate()
    {
        $avatar = session('avatar');
        if (!empty($avatar)) {
            $w = $this->request->param('w', 0, 'intval');
            $h = $this->request->param('h', 0, 'intval');
            $x = $this->request->param('x', 0, 'intval');
            $y = $this->request->param('y', 0, 'intval');

            $avatarPath = "./upload/" . $avatar;

            $avatarImg = Image::open($avatarPath);
            $avatarImg->crop($w, $h, $x, $y)->save($avatarPath);

            $result = true;
            if ($result === true) {
                $storage = new Storage();
                $result  = $storage->upload($avatar, $avatarPath, 'image');

                $userId = cmf_get_current_user_id();
                Db::name("user")->where(["id" => $userId])->update(["avatar" => $avatar]);
                session('user.avatar', $avatar);
                $this->success("头像更新成功！");
            } else {
                $this->error("头像保存失败！");
            }

        }
    }

    /**
     * 绑定手机号或邮箱
     */
    public function binding()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        if(!$user['mobile'])
        {
          return $this->fetch();
        }else
        {
          $this->redirect('user/profile/center');
        }
    }

    /**
     * 绑定手机号
     */
    public function bindingMobile()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|number',
                'verification_code' => 'require',
                'password|密码'          =>  'require|length:6,25',
                'repassword|确认密码'=>'require|confirm:password'
            ]);
            $validate->message([
                'username.require'          => '手机号不能为空',
                'username.number'           => '手机号只能为数字',
                // 'username.unique'           => '手机号已存在',
                'verification_code.require' => '验证码不能为空',
                'repassword.confirm'        =>  '密码输入不一致',
            ]);
            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }

            $userModel = new UserModel();
            $log       = $userModel->bindingMobile($data);
            switch ($log) {
                case 0:
                    $this->success('手机号绑定成功',url('user/profile/center'));
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }
    /*
     * 修改手机号
    */
    public function editMobile()
    {
      if($this->request->isPost())
      {
        $validate = new Validate([
            'mobile'          => 'require|unique:user,mobile',
            'verification_code' => 'require',
        ]);
        $validate->message([
            'mobile.require'          => '手机号必须填写',
            'mobile.unique'           => '手机号已经被注册过了',
            'verification_code.require' => '验证码不能为空',
        ]);
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if (!preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $data['mobile']))
        {
          $this->error('手机格式填写不正确');
        }

        $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }
        $userid=cmf_get_current_user_id();
        Db::name('user')->where(['id'=>$userid])->update(['mobile'=>$data['mobile']]);
        $this->success('手机号修改成功',url('user/profile/center'));


      }else
      {
        $userid=cmf_get_current_user_id();
        $info=Db::name('user')->where(['id'=>$userid])->field('mobile')->find();
        $this->assign($info);
        return $this->fetch();
      }
    }
    /**
     * 绑定邮箱
     */
    public function bindingEmail()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|email|unique:user,user_email',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require'          => '邮箱地址不能为空',
                'username.email'            => '邮箱地址不正确',
                'username.unique'           => '邮箱地址已存在',
                'verification_code.require' => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log       = $userModel->bindingEmail($data);
            switch ($log) {
                case 0:
                    $this->success('邮箱绑定成功');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }

}
