<?php

namespace app\index\controller;

use app\index\model\UserShop;
use \think\Controller;
use \think\Request;
use think\Wechat;
use think\WechatAuth;
use app\index\model\WxToken;
use app\index\model\UserWx;
class Index extends Controller
{

    public function index()
    {
        try {
            $appid = 'wx956daf8b541d9260'; //AppID(应用ID)
            $token = 'weijia'; //微信后台填写的TOKEN
            $crypt = 'xE2OT1TOMRAjIbPU9CWpvkHbEvrMFj81XW4n6EBAvf6'; //消息加密KEY（EncodingAESKey）

            /* 加载微信SDK */
            $wechat = new Wechat($token, $appid, $crypt);

            /* 获取请求信息 */
            $data = $wechat->request();


            if ($data && is_array($data)){
                //记录微信推送过来的数据
                // //file_put_contents('data.json', json_encode($data));

                //根据不同的事件调用不通的接口
                $this->responseMsg();

            }
        } catch (\Exception $e) {
            file_put_contents('error.json', json_encode($e->getMessage()));
        }


    }


     /*设置自定义菜单*/
    public function setMenu(){
        $jsonmenu = '{
	        "button":[{
				"type": "view",
	            "name": "商城",
	            "url": "https://mp.weixin.qq.com/s?__biz=MzAwMDkyNzU5MQ==&tempkey=OTIxX0VFRURuS2pJajdjaExvaDhpYlluQ0w2MWxrejgxWlh6b3dHOEUzb3JwM2NIVkcwMk5xY1VZS0REUE1Ib2ZLUjFZOXMyYURTYkwwOFRfWERlV1ZpYkI3akpKbmxaR1pwMEczd3k3V09oYUxSNWJhamlHUXppOHdNOWNKMGRGVEl6c2dXcGt4YlRRRlljc291UG1nWHdYTm1BeV83cXpWR3h5Tkx3NFF%2Bfg%3D%3D&#rd"
	        },{
	            "name": "产品推荐",
				"sub_button":[{
			            "type": "view",
			            "name": "分期装修",
			            "url": "http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/isHaveFenqi"
			        },{
			            "type": "view",
			            "name": "功能",
			            "url": "http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/login/login"
			        }]
	        },{
				"type": "view",
	            "name": "管理",
	            "url": "http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/manage/guanli"
	        }]
	    }';

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccessToken();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonmenu);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        echo $output;
    }


    /*获取accessToken*/
    private function getAccessToken(){

        $token = WxToken::getAccessToken();//查看库里保存的token有没有过期
        if($token['expires'] > time()){
            return $token['access_token'];//没有过期，返回
        }else{
            //token过期后的处理，向微信平台获取新token，然后保存入库

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config('appid')."&secret=".config('appsecret');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            $jsoninfo = json_decode($output, true);

            $bool = WxToken::updateAccessToken($jsoninfo['access_token']);
            if($bool){
                return $jsoninfo['access_token'];
            }

        }
    }


    /*回复信息*/
    public function responseMsg(){
        $postStr = file_get_contents("php://input");

        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            switch ($postObj->MsgType){
                case 'text':
                    $this->msgText($postObj);
                    //$this->msgNews($postObj);
                    break;
                case 'event':
                    //file_put_contents('/usr/share/nginx/html/testshop/appserver/public/tt.txt','56565');
                    $this->msgEvent($postObj);
            }
        }else {
            echo "";
            exit;
        }

    }



     /* 关注公众号事件 */
    private function msgEvent($data){
        //关注公众号事件
        if($data->Event == 'subscribe'){
            $this->msgNews($data);
            $info = $this->getWxUserInfo($data->FromUserName);//用户关注后获取关注者信息。
           //$info = '{"subscribe":1,"openid":"om15-v9zLwEMsVxsg-Rk935ANwp8","nickname":"linxr25","sex":1,"language":"zh_CN","city":"Tongzhou","province":"Beijing","country":"China","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/PiajxSqBRaEJf2o8EbRRum6W6Sc5cEfCRaAPDN5Qx8J1wN3PQlE5ricyic6eyxicblibV6Yic0xftpK9Iq17vibTO1urA\/0","subscribe_time":1484883144,"remark":"","groupid":0,"tagid_list":[]}';
            //file_put_contents('cc.txt',$info);
            //$info = json_decode($info,true);
            $userwx = new UserWx();
            $userwx->addNoticer($info);//将新关注的用户插入数据库
        }
    }


    /*获取关注者的个人信息*/
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


    /*被动回复图文*/
    private function msgNews($data){
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
        $titleStr = '维家智能家居';
        $descStr = '感谢您关注维家装饰,维家装饰以专业的服务为客户提供室内整体设计施工、为您打造环保、智能、温馨的家居环境!预约量房电话0318-3350777!';
        $resultStr = sprintf($newsTpl, $data->FromUserName, $data->ToUserName, time(), 'news' ,$titleStr,$descStr, config('interfaceurl').'image/guanzhu.jpg', '');
        echo $resultStr;

    }



    /*回复消息*/
    private function msgText($data){

        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
       /* $exp = '/^1[34578][0-9]{9}$/';
        $typeArr = ["职位","老板","监理管理","设计管理","销售管理","材料员","财务","预算员","工长","监理","设计师助理","设计师","销售","工长管理"];
        $shopArr = ["店铺","维家武强店","维家深州店","华庭深州店","维家衡水店"];
        if(preg_match($exp,substr($data->Content,0,11))){

            $usershop = new UserShop();
            $dianyuan = $usershop->getUserShopId(substr($data->Content,0,11));
            if(empty($dianyuan)){
                $contentStr = "您还未被添加至公司员工";
            }else{
                $bool = UserWx::updateWxdianyuan ($data->FromUserName,$dianyuan->d_id);
                //file_put_contents('sql.txt',$userwx->getLastSql()); ;public 目录下
                if($bool){
                    $contentStr = "您已绑定为".$shopArr[$dianyuan->shop]."的".$typeArr[$dianyuan->type]."职位";
                }
            }


        }*/
        $arr = explode(' ',$data->Content);
        $usershop = new UserShop();
        if($usershop->getUser($data->FromUserName)){

                $bool = $usershop->insertShopUser($arr,$data->FromUserName);
                if($bool){
                    $contentStr = "您已绑定为".$arr[3]."的".$arr[2]."职位";
                }else{
                    $contentStr = "绑定失败,请检查格式重新绑定！";
                }


        }else{
            $contentStr = "您已绑定店内员工,无须重新绑定";
        }

        $resultStr = sprintf($textTpl, $data->FromUserName, $data->ToUserName, time(), $data->MsgType ,$contentStr);
        echo $resultStr;

    }

    /*发送客服消息*/
    protected function sendKF($data){
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccessToken();
        $j = '<xml>
			     <ToUserName><![CDATA[%s]]></ToUserName>
			     <FromUserName><![CDATA[%s]]></FromUserName>
			     <CreateTime>%s</CreateTime>
			     <MsgType><![CDATA[transfer_customer_service]]></MsgType>
			 </xml>';
        $resultStr = sprintf($j, $data->FromUserName, $data->ToUserName, time());
        echo $resultStr;
    }

    /*获取关注者列表*/
    private function getGuanzhuList(){
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->getAccessToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        //file_put_contents('nnn.txt',$output);
        $jsoninfo = json_decode($output, true);
        return $jsoninfo['data']['openid'];

    }

    /*将关注者都插入到数据库中*/
    public function insertIntoTable(){
        $userwx = new UserWx();
        //将新关注的用户插入数据库
        $arr2 = array();
        $arr = $this->getGuanzhuList();
        for ($i = 0;$i < count($arr);$i++){
            $jsonData = $this->getWxUserInfo($arr[$i]);
            $arr2[$i] = $jsonData;
           // $userwx->addNoticer($jsonData);
        }
        //print_r($arr2);
        $userwx->saveAllGuanzhu($arr2);
    }

}