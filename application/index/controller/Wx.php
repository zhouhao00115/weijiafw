<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/8
 * Time: 22:24
 */
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Model;
//use app\doctor\model\Hospitals;
use \think\Request;

Class Wx extends Controller{

    const TOKEN = 'weijia';

    public function check(){
        $request = Request::instance();
        $echoStr = $request->param('echostr');

        if($this->checkSignature($request)){
            echo $echoStr;
            exit;
        }
    }
    /*
     * 微信验证token
     * */
    private function checkSignature(){
        $request = Request::instance();
        $signature = $request->param('signature');
        $timestamp = $request->param('timestamp');
        $nonce = $request->param('nonce');

        $token = self::TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 微信平台发送的事件
     */
    public function responseMsg(){
        //$postStr = file_get_contents("php://input");
        //$postStr = file_get_contents($request);
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data

        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            switch ($postObj->MsgType){
                case 'text':
                    $this->msgText($postObj);
                    //$this->msgNews($postObj);
                    break;
                case 'event':
                    //file_put_contents('/usr/share/nginx/html/weijiafw/public/tt.txt','56565');
                    $this->msgEvent($postObj);
            }
        }else {
            echo "";
            exit;
        }
    }

    /**
     * 被动回复文本
     * @param unknown $postObj
     */
    private function msgText($postObj){
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
        if(!empty( $postObj->Content )){
            //file_put_contents('/usr/share/nginx/html/testshop/appserver/public/tt.txt',$postObj->Content);
            switch($postObj->Content){

                case '帮助':
                    $contentStr = '1.门诊挂号确认-肾内科/血液透析中心;'."\n".'2.门诊医生开病毒筛查体检单/血液净化室医生开具;'."\n".'3.门诊缴费 采血室采血化验;'."\n".'4.等待筛查结果。回透析室和医生确认透析时间及本次透析缴费问题;'."\n".'5.做好透析上机前准备!'."\n".'6.如需其他帮助,工作时间(周一至周五早9点到晚5点半)请拨打国医通官方电话:400-825-6708,'."\n".'其余时间请添加工作人员微信:Drhuohuo';
                    break;

                default:
                    $this->sendKF($postObj);exit;

                    break;

            }

            $resultStr = sprintf($textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $postObj->MsgType ,$contentStr);
            echo $resultStr;
        }else{
            echo "Input something...";
        }
    }

    /**
     * 被动回复图文
     */
    private function msgNews($postObj){
        $newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<ArticleCount>1</ArticleCount>
					<Articles>
					  <item>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<PicUrl><![CDATA[%s]]></PicUrl>
						<Url><![CDATA[%s]]></Url>
					  </item>
					</Articles>
					</xml>";

        //file_put_contents('/usr/share/nginx/html/testshop/appserver/public/tt.txt',$postObj->Content);
        $titleStr = "手机在线预约异地透析床位";
        $descStr = '操作步骤:点击预约--绿色通道--填写信息--修改资料--预约完成!我们会第一时间为您联系相关服务!'."\n"."如需了解异地透析挂号流程,请回复\"帮助\"!";

        $resultStr = sprintf($newsTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'news' ,$titleStr,$descStr, getenv('SHOP_URL') . '/h5/image/follow-us.jpg', getenv('SHOP_URL') . '/wx.php?act=appointment');
        echo $resultStr;

    }
    /**
     * 发送客服消息
     * @param unknown $postObj
     */
    protected function sendKF($postObj){
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccessToken();
        $j = '<xml>
			     <ToUserName><![CDATA[%s]]></ToUserName>
			     <FromUserName><![CDATA[%s]]></FromUserName>
			     <CreateTime>%s</CreateTime>
			     <MsgType><![CDATA[transfer_customer_service]]></MsgType>
			 </xml>';
        $resultStr = sprintf($j, $postObj->FromUserName, $postObj->ToUserName, time());
        echo $resultStr;
    }
    /**
     * 关注公众号事件
     * @param unknown $postObj
     */
    private function msgEvent($postObj){
        //关注公众号事件
        if($postObj->Event == 'subscribe'){
            $this->msgNews($postObj);
            $info = $this->getWxUserInfo($postObj->FromUserName);//用户关注后获取关注者信息。
            //$info = '{"subscribe":1,"openid":"om15-v9zLwEMsVxsg-Rk935ANwp8","nickname":"linxr25","sex":1,"language":"zh_CN","city":"Tongzhou","province":"Beijing","country":"China","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/PiajxSqBRaEJf2o8EbRRum6W6Sc5cEfCRaAPDN5Qx8J1wN3PQlE5ricyic6eyxicblibV6Yic0xftpK9Iq17vibTO1urA\/0","subscribe_time":1484883144,"remark":"","groupid":0,"tagid_list":[]}';
            $info = json_decode($info,true);
            $url = config('gyt.api_path') . '/v2/ecapi.user.guanzhu';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($info));//curl发送post array
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);
        }
    }

    /**
     * 获取关注者的个人信息
     * @param unknown $openId
     * @return mixed
     */
    private function getWxUserInfo($openId){
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->getAccessToken() . '&openid=' . $openId . '&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        //$jsoninfo = json_decode($output, true);
        return $output;
    }
    //获取access_token
    private function getAccessToken(){
        $model = DB::table('wx_token');
        $token = $model->where('app_id',config('gyt.wx.AppId'))->first();//查看库里保存的token有没有过期
        if($token->expires > time()){
            return $token->access_token;//没有过期，返回
        }else{
            //token过期后的处理，向微信平台获取新token，然后保存入库
            //https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx273d8b629023add5&secret=3c1175d3a48985e8424e551681bb7af2
            $this->updateAccessToken($model,$token);
        }
    }

    //token过期后的处理，向微信平台获取新token，然后保存入库
    private function updateAccessToken($model,$token){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config('gyt.wx.AppId')."&secret=".config('gyt.wx.AppSecret');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $jsoninfo = json_decode($output, true);

        $defaultid = $model->where('token_id',$token->token_id)
            ->update([
                'access_token' => $jsoninfo['access_token'],
                'expires' => time() + 6900
            ]);
        return $jsoninfo['access_token'];

    }




}
