<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/20
 * Time: 23:23
 */
namespace app\index\model;
use think\Model;
use think\Db;

class UserShop extends Model {

    /*添加的时候查询库里有没有这条数据，防止重复添加*/
    public  function getUser($openid){

        $result = $this->where(['openid'=>"$openid"])->find();
        if(empty($result)){
            return true;
        }else{
            return false;
        }
    }

    /*获取所有店员信息*/

    /*根据发送给微信号的手机号，来查询到user_shop表里的店员id*/
    public function getUserShopId($phone){

        $result = $this->where(['phone'=>$phone])->find();
        return $result;
    }


    /*向数据库中插入店员的信息*/
    public function insertShopUser($arr,$openid){

        $time = time();
        if(count($arr) == 4){

            $this->username = $arr[0];
            $this->phone = $arr[1];
            $this->position = $arr[2];
            $this->shop = $arr[3];
            $this->openid = "$openid";
            $this->addtime = $time;

            $this->save();

            return true;
        }
        else{
            return false;
        }


    }





}