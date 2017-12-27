<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/17
 * Time: 23:26
 */
namespace app\index\model;
use think\Model;
use think\Db;

class Order extends Model
{
    /*添加一张新订单*/
    public static function addOrder($info){
        $order = new Order;
        $order->wx_id = $info['wx_id'];
        $order->addtime = time();
        $order->order_name = $info['name'];
        $order->status = 1;

        $order->save();

        if($order->o_id){
            return true;
        }
    }
}
