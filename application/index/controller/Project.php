<?php
namespace app\index\controller;

use \think\Controller;
use app\index\model\Order;

class Project extends Controller{

    /*添加装修订单的页面*/
    public function addOrder(){

        return $this->fetch();
    }

    /*将新订单插入数据库*/
    public function doAddOrder($info){

        $info = json_decode($info,true);
        $bool = Order::addOrder($info);
        if($bool){
        echo json_encode(['errCode'=>'0000','errMsg'=>'添加订单成功']);
        }

    }

    /*装修进度*/
    public function decorationProcess(){


    }

    /*项目详情*/
    public function itemDetail(){

       return $this->fetch('item_manage');

    }


    /*店铺管理*/
    public function shopManage(){

       return $this->fetch('shop_manage');
    }

    /*调整成员*/
    public function dealUser(){
        return $this->fetch();
    }





}