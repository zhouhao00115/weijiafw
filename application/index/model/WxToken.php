<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/9
 * Time: 18:34
 */
namespace app\index\model;
use think\Model;
use think\Db;

class WxToken extends Model{

    //获取accessToken
    public static function getAccessToken(){
        $result = Db::table('wx_token')
            ->where('app_id','=',config('appid'))
            ->find();
       // print_r($result);
        return $result;

    }

    //更新accessToken
    public static function updateAccessToken($token){

        $res = Db::table('wx_token')->where('app_id',config('appid'))->update(['access_token' => $token, 'expires' => time() + 7150]) ;

        return $res;
    }



}