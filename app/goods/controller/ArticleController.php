<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\goods\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\GoodsModel;
use think\Db;
use think\Cookie;

class ArticleController extends HomeBaseController
{
    public function index()
    {


        $id=$this->request->param("id");
        $goodsModel=new GoodsModel();
        $info=$goodsModel->where('gid','=',$id)->find();
        if(empty($info))
        {
           abort(404, '商品不存在!');
        }
        // 处理商品相册
         if (strstr($info['goods_images'],',')) {
             $imgaaa = explode(',',$info['goods_images']);
             foreach ($imgaaa as $key => $value) {
                  $name_url[] = explode('@!@',$value);
             }
             //对数据进行再次切割
             foreach ($name_url as $key => $value) {
                 $imga[] = ['url'=>$value[1],'name'=>$value[0]];
             }
         } else if(empty($info['goods_images'])) {
              $imga = null;
         } else {
             $name_url = explode('@!@',$info['goods_images']);
             $imga[] = ['url'=>$name_url[1],'name'=>$name_url[0]];
         }
         // 获取商品属性
         $attrs=Db::name('goods_attr')->where('goods_id','=',$info['gid'])->select();
         // 唯一属性
         $attrs_only=array();
         // 可选属性
         $attrs_optional=array();
         foreach($attrs as $v)
         {
            $attr=getAttrInfoByid($v['goods_property_id']);
            $attr['content']=$v['content'];
            $attr['attr_id']=$v['goods_property_id'];
            if($attr['attr_type'] === '1')
            {
              $attrs_optional[]=$attr;
            }else
            {
              $attrs_only[]=$attr;
            }
         }
         // 获取商品评价
        $goods_comment= Db::name('goods_comment')->where(['goods_id'=>$id,'verify'=>'1'])->paginate();
        $this->assign("goods_comment", $goods_comment);
        $this->assign('page', $goods_comment->render());
        // 好评率
        $comments=Db::name('goods_comment')->field('score')->where(['goods_id'=>$id,'verify'=>'1'])->select();
        $num=0;
        foreach($comments as $v)
        {
          if($v['score'] >= 3){
            $num++;
          }
        }
        if(count($comments) > 0)
        {
          $comment_rate=$num / count($comments);
          $comment_rate=sprintf("%.2f",$comment_rate) * 100;
        }else
        {
          $comment_rate=0;
        }
        // 获取购物车商品数量
        if(cmf_is_user_login())
        {
          $userid=cmf_get_current_user_id();
          $num=Db::name('cart')->where('userid',$userid)->count();
        }else
        {
           $cartinfo=unserialize(Cookie::get('cart'));
           $num=$cartinfo === false ? 0 : count($cartinfo);
        }

        // 组图
        $this->assign('imga',$imga);
        // 商品信息
        $this->assign('info',$info);
        // 购物车商品数量
        $this->assign('num',$num);
        $this->assign('attrs_only',$attrs_only);
        $this->assign('attrs_optional',$attrs_optional);
        $this->assign('comment_rate',$comment_rate);
        return $this->fetch("/article");
    }

    // 文章点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');


        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('portal_post')->where(['id' => $articleId])->setInc('post_like');

            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

}
