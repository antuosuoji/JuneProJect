<?php
namespace app\portal\controller;
use think\Request;

/**
 * 空控制器
 */
class ErrorController
{
  public function index(Request $request)
    {
        $this->redirect('/');
    }
}
