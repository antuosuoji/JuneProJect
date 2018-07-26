<?php
namespace app\user\controller;
use vendor\weixin\Wechat;
use cmf\controller\HomeBaseController;
use cmf\controller\PluginBaseController;
/**
 *
 */
class WxController extends HomeBaseController
{
   public function index()
   {
     vendor('wx_sdk/Wechat');
     $options = array(
         'appid' => 'wx0b8c59b90c5c1874', // 填写高级调用功能的appid
         'appsecret' => 'c271d11f397c1d497dd9248445a7d5d7' // 填写高级调用功能的密钥
     );
      $weObj = new Wechat($options);
      $type = $weObj->getRev()->getRevType();
      dump($type);



   }

}
