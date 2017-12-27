<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/23
 * Time: 20:43
 */
namespace think;
class JSSDK {
    private $appId;
    private $appSecret;
    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function getSignPackage($url) {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        //$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //$url = "http://testshop.gytcare.com/h5/bingli.html";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => strval($timestamp),
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket() {
       $sql = 'SELECT token_id,access_token,expires FROM wx_token WHERE type = "ticket" and app_name = "jsapi"';
        $jsapiTicket = Db::query($sql)[0];
       // print_r($jsapiTicket['expires']);
       // print_r($jsapiTicket);
        //print_r('**************************');
        if ($jsapiTicket['expires'] + 7150 < time()) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            if($res->ticket){
                $expire = time();
                $sql = 'UPDATE wx_token SET access_token="' . $res->ticket .'",expires="' . $expire . '"  WHERE token_id=' . $jsapiTicket['token_id'];
                Db::query($sql);
                $ticket = $res->ticket;
            }
        } else {
            $ticket = $jsapiTicket['access_token'];
        }
        return $ticket;
        /*$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=3D_aGs1TsufzAl9Z0y23bXT8C7BfwlcF86DLo2ov1AeQl8EF-StPz4K_PayMfbdvDOZqPBNRgx3zPfbgows0DJaNLXY5yIr4Dvjc347nEjRHEcTl-ECC0GnyDjyFPOgYBMNjAEAHST";
        $res = json_decode($this->httpGet($url));
        echo json_encode(['aaa'=>$res->ticket]);exit;*/
        /*
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $jsapiTicket = file_get_contents('./wechat/jsapi_ticket.json');
        $jsapiTicket = json_decode($jsapiTicket,true);
        if ($jsapiTicket['expire_time'] + 7150 < time()) {
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            if($res->ticket){
                $data['expire_time']   = time();
                $data['ticket']  = $res->ticket;
                file_put_contents('./wechat/jsapi_ticket.json',json_encode($data));
                $ticket = $res->ticket;
            }
        } else {
            $ticket = $jsapiTicket['ticket'];
        }
        return $ticket;*/
    }

    public function getAccessToken() {
        $sql = 'SELECT token_id,access_token,expires FROM wx_token wx_token  WHERE type = "ticket_token" and app_name = "access"';

        $jsonToken = Db::query($sql)[0];
        if($jsonToken['expires'] + 7150 < time()){
            //如果上次获取access_token的过期时间加上7150秒（相当于1小时59分）小于当前时间，则重新获取access_token，并保存缓存文件中
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
            $jsoninfo = json_decode($this->httpGet($url));

            if($jsoninfo->access_token){
                $expire = time();
                $sql = 'UPDATE  wx_token SET access_token="' . $jsoninfo->access_token .'",expires="' . $expire . '"  WHERE token_id=' . $jsonToken['token_id'];
                Db::query($sql);
                return $jsoninfo->access_token;
            }
        }else{
            return $jsonToken['access_token'];
        }

        /*
        $jsonToken = file_get_contents('./wechat/access_token.json');
        $jsonToken = json_decode($jsonToken,true);
        if($jsonToken['expire_time'] + 7150 < time()){
            //如果上次获取access_token的过期时间加上7150秒（相当于1小时59分）小于当前时间，则重新获取access_token，并保存缓存文件中
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
            $jsoninfo = json_decode($this->httpGet($url));

            if($jsoninfo->access_token){
                $data['expire_time']   = time();
                $data['access_token']  = $jsoninfo->access_token;
                file_put_contents('./wechat/access_token.json',json_encode($data));
                return $jsoninfo->access_token;
            }
        }else{
            return $jsonToken['access_token'];
        }*/
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}

