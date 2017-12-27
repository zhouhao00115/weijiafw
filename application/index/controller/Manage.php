<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/17
 * Time: 20:26
 */
namespace app\index\controller;

use \think\Controller;
use think\Db;
use app\index\model\UserShop;
class Manage extends Controller{

    public function manage(){

        return $this->fetch();
    }

    /*给参数openid*/
    public function guanli(){

        $customeUrl ='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $result = $this->baseAuth($customeUrl);

       foreach($result as $key => $value){
          if($key=='openid'){

               //根据openid查看是否是店员，如果是则进去管理页面内
              $sh = new UserShop();
              $result = $sh->where(['openid'=>$value])->find();
              if(!empty($result)){
                  header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/manage/manage&openid=".$value);

              }else{

              }


          }
       }
    }



    //设置网络请求配置
    public function _request($curl,$https=true,$method='GET',$data=null){
        // 创建一个新cURL资源
        $ch = curl_init();

        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $curl);    //要访问的网站
        curl_setopt($ch, CURLOPT_HEADER, false);    //启用时会将头文件的信息作为数据流输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //将curl_exec()获取的信息以字符串返回，而不是直接输出。

       /* if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //FALSE 禁止 cURL 验证对等证书（peer's certificate）。
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  //验证主机
        }*/
        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);  //发送 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  //全部数据使用HTTP协议中的 "POST" 操作来发送。
        }


        // 抓取URL并把它传递给浏览器
        $content = curl_exec($ch);
        if ($content  === false) {
            return "网络请求出错: " . curl_error($ch);
            exit();
        }
        //关闭cURL资源，并且释放系统资源
        curl_close($ch);

        return $content;
    }


    /**
     * 获取用户的openid
     * @param  string $openid [description]
     * @return [type]         [description]
     */
    public function baseAuth($redirect_url){

        //1.准备scope为snsapi_base网页授权页面
        $baseurl = urlencode($redirect_url);
        $snsapi_base_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.config('appid').'&redirect_uri='.$baseurl.'&response_type=code&scope=snsapi_base&state=weijia123#wechat_redirect';

        //2.静默授权,获取code
        //页面跳转至redirect_uri/?code=CODE&state=STATE

        //$code = $_GET['code'];
        if(!isset($_GET['code'])){
            header('Location:'.$snsapi_base_url);
        }

        //var_dump(input('code'));
        //3.通过code换取网页授权access_token和openid
        $curl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.config('appid').'&secret='.config('appsecret').'&code='.input('code').'&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $curl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $re = curl_exec($ch);
        $json_obj = json_decode($re,true);
        return $json_obj;
    }


    public function testPay(){

        return $this->fetch();
    }



}