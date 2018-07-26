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
use app\user\model\UserGroupModel;
use think\Validate;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $where   = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|user_email|mobile']    = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->alias('a')
                           ->join('__USER_GROUP__ b','a.group_id = b.id','left')
                           ->field('a.*,b.name as groupname')
                           ->whereOr($keywordComplex)
                           ->where($where)
                           ->where(["user_type"=>2])
                           ->order("create_time DESC")
                           ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("会员拉黑成功！", "adminIndex/index");
            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $this->success("会员启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }
    // 2018.5.31号 会员编辑
    public function edit()
    {
      $id = input('param.id', 0, 'intval');
      // 获取会员组信息
      $userGroupModel=new UserGroupModel();
      $groupInfo=$userGroupModel->field('id,name')->select();
      if($id)
      {
        $memberInfo= Db::name("user")->where('id',$id)->where('user_type',2)->find();
        $this->assign('memberinfo',$memberInfo);
        $this->assign('groupInfo',$groupInfo);
        // return dump($groupInfo);
        return $this->fetch();
      }else
      {
        $this->error('数据参数传递错误');
      }
    }
    // 会员修改
    public function post_edit()
    {
      if($this->request->isPost())
      {
        // 验证数据
        $data=$this->request->param();
        $result = $this->validate($data, 'User.edit');
        if ($result !== true) {
            $this->error($result);
        }else
        {
           unset($data['repassword']);
           $data['user_pass']=cmf_password($data['user_pass']);
           $res=Db::name("user")->update($data);
           if($res !==false)
           {
             $this->success("更新成功",url("AdminIndex/index"));
           }
        }
      }
    }
    // 添加会员
    public function add()
    {
      $userGroupModel=new UserGroupModel();
      $groupInfo=$userGroupModel->field('id,name')->select();
      if($this->request->isPost())
      {
        // 验证数据
        $data=$this->request->param();
        $result = $this->validate($data, 'User.add');
        if ($result !== true) {
            $this->error($result);
        }else
        {
             $data['user_type']=2; //会员类型
             $data['create_time']=time(); //会员类型
             $data['user_pass']=cmf_password($data['user_pass']);
             $res=Db::name("user")->insert($data);
             if($res !== false)
             {
                 $this->success("添加成功！", url("AdminIndex/index"));
             }
        }

      }else
      {
        $this->assign('groupInfo',$groupInfo);
        return $this->fetch();
      }
    }
    // 充值会员
    public function recharge()
    {
      if($this->request->isPost())
      {
        $arrData=$this->request->param();
        $is_true=Validate::make()
        ->rule('balance', 'require|number|gt:0')
        ->check($arrData);
        if($is_true == false)
        {
            $this->error("请正确填写充值金额");
        }
        Db::name('user')->where('id',$arrData['id'])
                        ->setInc('balance',$arrData['balance']);
        // 充值记录写入数据库
        $recordData['user_id']=$arrData['id'];
        $recordData['type']=0;
        $recordData['money']=$arrData['balance'];
        $recordData['log_name']="管理员充值";
        $recordData['remarks']=$arrData['remarks'];
        $recordData['time']=time();
        $recordData['admin_userid']=cmf_get_current_admin_id();
        Db::name('record')->insert($recordData);
        // 写入到 会员充值表
        Db::name('recharge')->insert(['userid'=>$arrData['id'],'money'=>$arrData['balance'],'inputtime'=>time(),'type'=>1]);
        $this->success("充值成功",url("AdminIndex/index"));
      }else
      {
        $id=$this->request->param('id');
        $memberinfo=Db::name("user")->where('id',$id)->find();
        $this->assign("id",$id);
        $this->assign("memberinfo",$memberinfo);
        return $this->fetch();
      }
    }
}
