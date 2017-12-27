<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/22
 * Time: 14:10
 */
namespace app\index\controller;

use \think\Controller;

use \think\Request;
use \think\JSSDK;
use \think\UnifiedOrderPub;

class Wxpay extends Controller
{
    public function jsapi(){

        return $this->fetch('jsapistart');
    }

    //微信支付类
    public function jsapiStart()
    {
        $request = Request::instance();
        if($request->isAjax()){
            $jssdk           = new JSSDK(config('appid'),config('appsecret'));
            $signPackage     = $jssdk->getSignPackage($request->param('url'));
            $appId           = $signPackage["appId"];
            $nonceStr        = $signPackage["nonceStr"];
            $timestamp       = $signPackage["timestamp"];
            //print_r($jssdk);exit;
            $unifiedOrder = new UnifiedOrderPub();
            //print_r($unifiedOrder);exit;
            //$unifiedOrder->setParameter("openid",'oezeKuLZ_2Kgmj9o-gK_zzU88JRQ');//用户openId
            $unifiedOrder->setParameter("openid",$request->param('openId'));//用户openId
            $goodname      = $request->param('body');//商品描述
            $total_fee     = $request->param('fee');//0.01;
            $out_trade_no  = $request->param('no');//商户订单号
            $notifyUrl     = $request->param('notify');//通知地址

            $unifiedOrder->setParameter("body", $goodname);//商品描述，文档里写着不能超过32个字符，否则会报错，经过实际测试，临界点大概在128左右，稳妥点最好按照文档，不要超过32个字符$_GET["total_fee"]*
            $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
            $unifiedOrder->setParameter("total_fee",$total_fee * 100);//总金额,单位为分
            $unifiedOrder->setParameter("notify_url",$notifyUrl);//通知地址
            $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
            $unifiedOrder->setParameter("nonce_str", $signPackage["nonceStr"]);//随机字符串
            //echo '<pre>';
           //print_r($unifiedOrder->parameters);exit;

            $prepayId = $unifiedOrder->getPrepayId();
            //echo $prepayId;exit;
            // 计算paySign
            $payPackage = array(
                "appId"=>$appId,
                "nonceStr" => $nonceStr,
                "package" => "prepay_id=" . $prepayId,
                "signType" => "MD5",
                "timeStamp" => strval($timestamp)
            );
            $paySign = $unifiedOrder->getSign($payPackage);
            $payPackage['paySign'] = $paySign;

            echo  json_encode(['payPackage' => $payPackage,'signPackage' => $signPackage,'err' => '000']);
            //file_put_contents('./Payment/log/pay.txt', $file_data);
             //$file_data;$file_data =

        }


    }

}

/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */












