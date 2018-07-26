<?php
use think\Config;
/**
 * 字符截取 支持UTF8/GBK
 * @param $string
 * @param $length
 * @param $dot
 */
 use think\Db;
 function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
  {
       if(function_exists("mb_substr"))
        {
               if($suffix)
               return mb_substr($str, $start, $length, $charset)."...";
               else
                    return mb_substr($str, $start, $length, $charset);
          }
          elseif(function_exists('iconv_substr')) {
              if($suffix)
                   return iconv_substr($str,$start,$length,$charset)."...";
              else
                   return iconv_substr($str,$start,$length,$charset);
          }
          $re['utf-8']   = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef]
                   [x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
          $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
          $re['gbk']    = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
          $re['big5']   = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
          preg_match_all($re[$charset], $str, $match);
          $slice = join("",array_slice($match[0], $start, $length));
          if($suffix) return $slice."…";
          return $slice;
}
// ----------2018.6.3 生成优惠券------------
function make_coupon_card()
{
        // mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
        return $uuid;
}

function getGoodsContent($attrId,$goodsId)
{
  $Db=Db::name('goods_attr')->where("goods_property_id",$attrId)->where('goods_id',$goodsId)->find();
  return  $Db['content'];
}

//---------获取下级三级分销数据----------
// $level_setting=cmf_get_option('level_settings');
function getTreeUserid($data,$pid=0,$level=1,$distribution=3)
{
  $res=array();
  foreach($data as $v)
  {
    if($v['parentid'] == $pid)
    {
      $v['level']=$level;

      $res[]=$v;
      if($level < $distribution){
      $res=array_merge($res,getTreeUserid($data,$v['userid'],$level+1,$distribution));
    }
    }
  }
  return $res;

}
// --------------获取上级三级会员------------
function getParentUserid($userid,$level=1,$distribution=3)
{
  $res=array();
  $info=Db::name("level_user")->where('userid',$userid)->find();
  if($info['parentid'] != 0)
  {
    $res[]=['userid'=>$info['parentid'],'level'=>$level];
    if($level < $distribution)
    {
      $res=array_merge($res,getParentUserid($info['parentid'],$level+1,$distribution));
    }
  }
  return $res;
}
//--------- 获取下级 三级会员------------------
function getChildUserid($userid,$level=1,$distribution=3)
{
  $res=array();
  $info=Db::name("level_user")->select();
  foreach($info as $v)
  {
    if($v['parentid'] == $userid)
    {
        $v['level']=$level;
        $res[]=$v;
        if($level < $distribution){
        $res=array_merge($res,getTreeUserid($info,$v['userid'],$level+1,$distribution));
      }
    }
  }
  return $res;

}
// ---------------根据会员iD 获取会员其他信息-------
function getUserInfoById($userid)
{
   $info=Db::name('user')->where('id',$userid)->field('id,user_login,user_nickname,mobile,balance')->find();
   return $info;
}
// --------------根据属性id 获取 属性名  属性值类型-----------
function getAttrInfoByid($id)
{
  $info=Db::name('attribute')->where('id',$id)->find();
  return ['name'=>$info['name'],'attr_type'=>$info['attr_type']];
}
// ------------判断商品是否关注-----------------
function checkGoodsIsFollow($gid)
{
    $userid=cmf_get_current_user_id();
    $has=Db::name('user_favorite')->where(['user_id'=>$userid,'object_id'=>$gid])->find();
    if($has)
    {
      return true;
    }else
    {
      return false;
    }
}
// 获取分类 的所有子分类
function getChildCategorys($id)
{
    $catModel=new  app\portal\model\PortalCategoryModel;
    $info=$catModel->where(['delete_time'=>0])->select();
    $cats=array();
    foreach($info as $v)
    {
       if($v['parent_id'] == $id)
       {
         $cats[]=$v['id'];
         $cats=array_merge($cats,getChildCategorys($v['id']));
       }
    }
    return $cats;
}
//根据商品ID获取商品其他信息
function getGoodsInfoByid($id)
{
  $info=Db::name('goods')->where(['gid'=>$id])->find();
  return $info;
}
// curl 请求
/**
  *_request():发出请求
  *@curl:访问的URL
  *@https：安全访问协议
  *@method：请求的方式，默认为get
  *@data：post方式请求时上传的数据
**/
 function _request($curl, $https=true, $method='get', $data=null){
  $ch = curl_init();//初始化
  curl_setopt($ch, CURLOPT_URL, $curl);//设置访问的URL
  curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
  if($https){
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证
  }
  if($method == 'post'){
    curl_setopt($ch, CURLOPT_POST, true);//设置请求是POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置POST请求的数据
  }
  $str = curl_exec($ch);//执行访问，返回结果
  curl_close($ch);//关闭curl，释放资源
  return $str;
}
/* 分销数据*/
function user_commission($userid,$orderid,$total_actual)
{
   $level_setting=cmf_get_option('level_settings');
   // 佣金写入数据库
   $users=getParentUserid($userid,1,$level_setting['level']);
   foreach($users as $v)
   {
     $info['userid']=$v['userid'];

     $info['child_userid']=$userid;
     $info['child_level']=$v['level'];
     $info['orderid']=$orderid;
     $info['inputtime']=time();
     switch ($v['level']) {
       case '1':
         $info['commission']=($level_setting['first_score']/100)*$total_actual;
         break;
       case '2':
         $info['commission']=($level_setting['second_score']/100)*$total_actual;
         break;
       case '3':
         $info['commission']=($level_setting['third_score']/100)*$total_actual;
         break;
     }
     $info['commission']=round($info['commission'],2);
     Db::name('level_commission')->insert($info);
     // 佣金写入总佣金表
     $is_has=Db::name('user_commission')->where(['userid'=>$info['userid']])->find();
     if($is_has)
     {
       Db::name('user_commission')->where(['userid'=>$info['userid']])->setInc('total',$info['commission']);
     }else
     {
       Db::name('user_commission')->insert(['userid'=>$info['userid'],'total'=>$info['commission']]);
     }
   }
}
/*销量数据入库*/
function sales_volume($orderid)
{
 $info=Db::name('order_detail')->field('goods_id,num')->where(['orderid'=>$orderid])->select();
 foreach($info as $v)
 {
   $is_has=Db::name('sales')->where(['goods_id'=>$v['goods_id']])->find();
   if($is_has)
   {
     Db::name('sales')->where(['goods_id'=>$v['goods_id']])->setInc('num',$v['num']);
   }else
   {
     Db::name('sales')->insert($v);
   }
 }
}
/* 支付完成 检测会员等级 （根据积分来定）*/
function check_usergrade($userid)
{
  $userinfo=Db::name('user')->field('score')->where(['id'=>$userid])->find();
  $info=Db::name('user_group')->select();
  foreach($info as $k=>$v)
  {
    $group[$v['id']]=$v;
  }
  if($userinfo['score'] >=  $group[4]['lower_point'])
  {
     Db::name('user')->where(['id'=>$userid])->setField('group_id',4);
  }elseif($userinfo['score'] >= $group[2]['lower_point'])
  {
     Db::name('user')->where(['id'=>$userid])->setField('group_id',2);
  }
}

function search($keyword)
{
  $where   = "1 =1";
  $where  .= " and g.gname like '%$keyword%'";
  $info    = Db::name('goods g')->where($where)->select();
  return $info;


}
