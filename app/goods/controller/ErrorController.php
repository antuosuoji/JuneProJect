<?php
namespace app\goods\controller;
use cmf\controller\HomeBaseController;
/**
 * 空控制器
 */
class ErrorController extends HomeBaseController
{
  public function index()
    {
        $this->redirect('/');
    }
}
