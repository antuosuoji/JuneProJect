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

use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Cookie;
class IndexController extends HomeBaseController
{

    /**
     * 前台用户首页(公开)
     */
    public function index()
    {
        $id        = $this->request->param("id", 0, "intval");
        $userModel = new UserModel();
        $user      = $userModel->where('id', $id)->find();
        if (empty($user)) {
            $this->error("查无此人！");
        }
        $this->assign($user->toArray());
        $this->assign('user',$user);
        return $this->fetch(":index");
    }

    /**
     * 前台ajax 判断用户登录状态接口
     */
    function isLogin()
    {
        if (cmf_is_user_login()) {
            $this->success("用户已登录",null,['user'=>cmf_get_current_user()]);
        } else {
            $this->error("此用户未登录!");
        }
    }

    /**
     * 退出登录
    */
    public function logout()
    {
        session("user", null);//只有前台用户退出
        return redirect("/");
    }
    // 关注
    function follow()
    {
      if (cmf_is_user_login())
      {
        // 关注入库
        $userid=cmf_get_current_user_id();
        $gid=$this->request->param('gid');
        $cid=$this->request->param('cid');
        $data['user_id']=$userid;
        $data['object_id']=$gid;
        $data['object_cid']=$cid;
        $data['create_time']=time();
        $has=Db::name('user_favorite')->where(['user_id'=>$userid,'object_id'=>$gid,'object_cid'=>$cid])->find();
        if($has)
        {
          return json(['code'=>2]);
        }else
        {
          Db::name('user_favorite')->insert($data);
          return  json(['code'=>1]);
        }
      } else
      {
          return json(['code'=>0]);
      }
    }
    // 购物车 2018.6.13
    public function cart()
    {
        if(cmf_is_user_login())
        {
          // 获取数据库中购物车数据
          $userid=cmf_get_current_user_id();
          $list=Db::name('cart')->alias('a')
                                ->where(['userid'=>['=',$userid]])
                                ->join('__GOODS__ g','a.goods_id=g.gid')
                                ->field('a.*,g.gname,g.gprice,g.goods_image')
                                ->paginate();
        }else
        {
          // 从cookie中获取
          $carts=array();
          $info=unserialize(Cookie::get('cart'));
          // 判断有无数据
          if($info && count($info) > 0)
          {

            foreach ($info as $key => $value) {
              $l_arr=explode('---',$value);
              $carts[]=$l_arr;
            }
            // 拼凑二维数组
            foreach($carts as $k=>$v)
            {
              $list[$k]['goods_id']=$v[0];
              $list[$k]['gname']=getGoodsInfoByid($v[0])['gname'];
              $list[$k]['goods_attr_value']=$v[2];
              $list[$k]['goods_attr_id']=$v[1];
              $list[$k]['num']=$v[3];
              $list[$k]['goods_image']=getGoodsInfoByid($v[0])['goods_image'];
              $list[$k]['gprice']=getGoodsInfoByid($v[0])['gprice'];
              $list[$k]['id']=$k;
            }
          }else
          {
            $list=array();
          }
        }
        $this->assign('list',$list);
        if(cmf_is_user_login())
        {
          $this->assign('page',$list->render());
        }
        return $this->fetch(":cart");
    }
    // 加入购物车
    public function postCart()
    {
        $info=$this->request->param();
        $goods_attr=   $info['goods'];
        $id        =   $info['id'];
        $number    =   $info['number'];

        $attrs=explode('---',$goods_attr);
        $attrs=array_filter($attrs);
        $goods_attr_value="";
        $goods_attr_ids="";
        foreach($attrs as $v)
        {
          $attrs_arr=explode(',',$v);//0 属性名称，1属性id，2属性值
          $goods_attr_value .= $attrs_arr[0].":".$attrs_arr[2].",";
          $goods_attr_ids   .=$attrs_arr[1].",";
        }
        $goods_attr_value='('.rtrim($goods_attr_value,',').')';
        $goods_attr_ids  = rtrim($goods_attr_ids,',');

        if(!isset($id) || !isset($goods_attr) || !isset($number))
        {
          return $this->error("参数有误");
        }else
        {
          // 判断是否登录,登录写入数据表
          if(cmf_is_user_login())
          {
            $userid=cmf_get_current_user_id();
            $cartinfo['userid']    =   $userid;
            $cartinfo['goods_id']   =   $id;
            $cartinfo['goods_attr_id']=$goods_attr_ids;
            $cartinfo['goods_attr_value']=$goods_attr_value;
            $cartinfo['num']   =  $number;
            Db::name('cart')->insert($cartinfo);
            return $this->success("添加成功");
          }else
          {
            // 未登录，写入cookie
            $data= Cookie::has('cart') ? unserialize(Cookie::get('cart')) : array();
            $info=$id.'---'.$goods_attr_ids.'---'.$goods_attr_value.'---'.$number;
            $data[]=$info;
            $data=serialize($data);
            Cookie::set('cart',$data,config('cart_expire'));
            return $this->success("添加成功");
          }
        }
    }
    // 购物车删除
    public function delete()
    {
      $id=$this->request->param('id');
      if(cmf_is_user_login())
      {
        //删除数据库
        Db::name('cart')->where(['id'=>$id])->delete();
        $this->success("删除成功");
      }else
      {

        // 删除cookie
        $info=unserialize(Cookie::get('cart'));
        unset($info[$id]);
        $info=serialize($info);
        Cookie::set('cart',$info,config('cart_expire'));
        $this->success("删除成功");
      }
    }

}
