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
use think\rndChinaName;
use think\Db;
use think\Model;

class UserModel extends Model
{
    protected $type = [
        'more' => 'array',
    ];

    public function doMobile($user)
    {
        $result = $this->where('mobile', $user['mobile'])->where('user_type','2')->find();


        if (!empty($result)) {
            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {
                //拉黑判断。
                if ($result['user_status'] == 0) {
                    return 3;
                }
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $this->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
        return 2;
    }

    public function doName($user)
    {
        $result = $this->where('user_login', $user['user_login'])->where('user_type','2')->find();
        if (!empty($result)) {
            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {
                //拉黑判断。
                if ($result['user_status'] == 0) {
                    return 3;
                }
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $result->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
        return 2;
    }

    public function doEmail($user)
    {

        $result = $this->where('user_email', $user['user_email'])->find();

        if (!empty($result)) {
            $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
            $hookParam             = [
                'user'                    => $user,
                'compare_password_result' => $comparePasswordResult
            ];
            hook_one("user_login_start", $hookParam);
            if ($comparePasswordResult) {

                //拉黑判断。
                if ($result['user_status'] == 0) {
                    return 3;
                }
                session('user', $result->toArray());
                $data = [
                    'last_login_time' => time(),
                    'last_login_ip'   => get_client_ip(0, true),
                ];
                $this->where('id', $result["id"])->update($data);
                $token = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        $hookParam = [
            'user'                    => $user,
            'compare_password_result' => false
        ];
        hook_one("user_login_start", $hookParam);
        return 2;
    }

    public function register($user, $type)
    {
        switch ($type) {
            case 1:
                $result = Db::name("user")->where('user_login', $user['user_login'])->find();
                break;
            case 2:
                $result = Db::name("user")->where('mobile', $user['mobile'])->find();
                break;
            case 3:
                $result = Db::name("user")->where('user_email', $user['user_email'])->find();
                break;
            default:
                $result = 0;
        }

        $userStatus = 1;

        if (cmf_is_open_registration()) {
            $userStatus = 2; //用户注册状态为 ：未验证
        }
        // 用户状态手动调为1 -----2018.6.28
        $userStatus = 1;

        if (empty($result)) {
          vendor('chinaname/rndChinaName');
          $name = new rndChinaName();
          $nick = $name->getName();
            $data   = [
                'user_login'      => empty($user['user_login']) ? $user['mobile'] : $user['user_login'],
                'user_email'      => empty($user['user_email']) ? '' : $user['user_email'],
                'mobile'          => empty($user['mobile']) ? '' : $user['mobile'],
                'user_nickname'   => $nick,
                'user_pass'       => cmf_password($user['user_pass']),
                'last_login_ip'   => get_client_ip(0, true),
                'create_time'     => time(),
                'last_login_time' => time(),
                'user_status'     => $userStatus,
                "user_type"       => 2,//会员
            ];
            // 验证邀请码是否真实
            $is_true=Db::name("user")->where('mobile',$user['invitation_code'])->find();
            if(!$is_true)
            {
              return 3;
            }
            $parentid=$is_true['id'];
            $userId = Db::name("user")->insertGetId($data);
            $data   = Db::name("user")->where('id', $userId)->find();
            // 数据写入会员体系表
            Db::name('level_user')->insert(['userid'=>$userId,'parentid'=>$parentid,'inputtime'=>time()]);
            cmf_update_current_user($data);//更新当前登录用户信息
            $token = cmf_generate_user_token($userId, 'web');//生成用户token
            if (!empty($token)) {
                session('token', $token);
            }
            return 0;
        }
        return 1;
    }

    /**
     * 通过邮箱重置密码
     * @param $email
     * @param $password
     * @return int
     */
    public function emailPasswordReset($email, $password)
    {
        $result = $this->where('user_email', $email)->find();
        if (!empty($result)) {
            $data = [
                'user_pass' => cmf_password($password),
            ];
            $this->where('user_email', $email)->update($data);
            return 0;
        }
        return 1;
    }

    /**
     * 通过手机重置密码
     * @param $mobile
     * @param $password
     * @return int
     */
    public function mobilePasswordReset($mobile, $password)
    {
        $userQuery = Db::name("user");
        $result    = $userQuery->where('mobile', $mobile)->find();
        if (!empty($result)) {
            $data = [
                'user_pass' => cmf_password($password),
            ];
            $userQuery->where('mobile', $mobile)->update($data);
            return 0;
        }
        return 1;
    }

    public function editData($user)
    {
        $userId = cmf_get_current_user_id();

        if (isset($user['birthday'])) {
            $user['birthday'] = strtotime($user['birthday']);
        }

        $field = 'user_nickname,sex,birthday,user_url,signature,more';

        if ($this->allowField($field)->save($user, ['id' => $userId])) {
            $userInfo = $this->where('id', $userId)->find();
            cmf_update_current_user($userInfo->toArray());
            return 1;
        }
        return 0;
    }

    /**
     * 用户密码修改
     * @param $user
     * @return int
     */
    public function editPassword($user)
    {
        $userId    = cmf_get_current_user_id();
        $userQuery = Db::name("user");
        if ($user['password'] != $user['repassword']) {
            return 1;
        }
        $pass = $userQuery->where('id', $userId)->find();
        if (!cmf_compare_password($user['old_password'], $pass['user_pass'])) {
            return 2;
        }
        $data['user_pass'] = cmf_password($user['password']);
        $userQuery->where('id', $userId)->update($data);
        return 0;
    }

    public function comments()
    {
        $userId               = cmf_get_current_user_id();
        $userQuery            = Db::name("Comment");
        $where['user_id']     = $userId;
        $where['delete_time'] = 0;
        $favorites            = $userQuery->where($where)->order('id desc')->paginate(10);
        $data['page']         = $favorites->render();
        $data['lists']        = $favorites->items();
        return $data;
    }

    public function deleteComment($id)
    {
        $userId              = cmf_get_current_user_id();
        $userQuery           = Db::name("Comment");
        $where['id']         = $id;
        $where['user_id']    = $userId;
        $data['delete_time'] = time();
        $userQuery->where($where)->update($data);
        return $data;
    }

    /**
     * 绑定用户手机号
     */
    public function bindingMobile($user)
    {
        $userId          = cmf_get_current_user_id();
        // 判断手机号是否已经注册过
        $is_reg=Db::name('user')->where(['mobile'=>$user['username']])->find();
        if($is_reg)
        {
          //注册过，更新第三方 userid，并删除user表当前userid数据
          Db::name('third_party_user')->where(['user_id'=>$userId])->update(['user_id'=>$is_reg['id']]);
          Db::name('user')->where(['id'=>$userId])->delete();
          $data['user_pass'] = cmf_password($user['password']);
          Db::name('user')->where(['mobile'=>$user['username']])->update($data);
          cmf_update_current_user($is_reg); //更新当前登录用户信息
        }else
        {
          $data['mobile'] = $user['username'];
          $data['user_pass'] = cmf_password($user['password']);
          Db::name('user')->where(['id'=>$userId])->update($data);
        }
        return 0;
    }

    /**
     * 绑定用户邮箱
     */
    public function bindingEmail($user)
    {
        $userId              = cmf_get_current_user_id();
        $data ['user_email'] = $user['username'];
        Db::name("user")->where('id', $userId)->update($data);
        $userInfo = Db::name("user")->where('id', $userId)->find();
        cmf_update_current_user($userInfo);
        return 0;
    }
}
