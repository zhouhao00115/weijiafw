<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/26
 * Time: 20:31
 */
namespace app\index\controller;

use \think\Controller;

class Login extends Controller
{
    //登录界面
    public function login(){

        return $this->fetch();
    }




}
