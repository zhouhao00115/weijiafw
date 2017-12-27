<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/9
 * Time: 22:02
 */
namespace app\index\controller;

use \think\Controller;
use \think\Request;
use \think\Db;
use app\index\model\WxToken;
use app\index\model\UserKehu;
class Notice extends Controller{

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

    //调接口发通知
    private function httpNotice($arr){


        foreach($arr as $key=>$val){
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->getAccessToken();
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $val);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);
            echo $output;
        }


    }



    /*有了分期订单后给老板发送通知信息*/
    public function sendfenqiCheckNotice(){
        //将客户表里的分期id更新一下
        $request = Request::instance();
        if($request->isAjax()){
            $bool = UserKehu::where('uid',$request->param('kh_id'))->update(['fq_id'=>$request->param('fq_id')]);

            $sql = 'SELECT u.name,u.phone,o.type,o.cost
				FROM user_kehu u
				LEFT JOIN order_fq o ON u.uid = o.kh_id
				WHERE u.uid = ?
				ORDER BY o.addtime DESC';

            $info = Db::query($sql,[$request->param('kh_id')])[0];
            //echo json_encode(['aa'=>$info]);
            if($info['type'] == 1){
                $info['type'] = '分期月付';
            }else{
                $info['type'] = '定期支付';
            }

        $det_url = "http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/bossCheck&fq_id=".$request->param('fq_id');
            //大斌openid{oZ4XesuWwL1EAEycGYrgLTceU5dA}
            //oZ4XesrGjgAejPVBvxJB4mi3zfBs 兴
        $jsontemplate = '{
	        "touser" : "oZ4XesrGjgAejPVBvxJB4mi3zfBs",
            "template_id" : "mWKYTBZXUI-VzI78XbeXqlL6q89uhyRMcshR9M1J5LQ",
            "url" : "' . $det_url . '",
            "data" : {
                "first" : {
                       "value" : "您好,有新客户申请分期装修请及时审核",
                       "color" : "#173177"
                 },
                 "keyword1" : {
                       "value" : "' . $info['name'] . '",
                       "color" : "#173177"
                 },
                 "keyword2" : {
                       "value" : "' . $info['phone'] . '",
                       "color" : "#173177"
                 },
                 "keyword3" : {
                       "value" : "' . $info['type'] . '",
                       "color" : "#173177"
                 },
                 "keyword4" : {
                       "value" : "' . $info['cost'] . '",
                       "color" : "#173177"
                 },
        		 "remark" : {
                       "value" : "下单时间:' . date('Y-m-d H:i:s') .  '",
                       "color" : "#173177"
                 }
             }
	    }';

        $template[] = $jsontemplate;

        $this->httpNotice($template);

        }
    }

    public function ppp(){

        echo 123;
    }

    /*审核通过的通知*/
    public function checkPassNotice(){
        //_JhEYWUTTzXBhu9cGkl7w--7XEoPGcYMVTsnQowG6Ls
        $request = Request::instance();
        if($request->isAjax()){
            $fq_id = $request->param('fq_id');
            $sql = 'select open_id from order_fq where fq_id = ?';
            $info = Db::query($sql,[$fq_id])[0];



       // $det_url = "http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/bossCheck&fq_id=".$request->param('fq_id');
            $det_url = 'http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/bossChecking&status=2&fq_id'.$request->param('fq_id');
            $jsontemplate = '{
                "touser" : "' . $info['open_id'] .'",
                "template_id" : "_JhEYWUTTzXBhu9cGkl7w--7XEoPGcYMVTsnQowG6Ls",
                "url" : "' . $det_url . '",
                "data" : {
                    "first" : {
                           "value" : "尊敬的客户您好,您提交的信息审核情况如下:",
                           "color" : "#173177"
                     },
                     "keyword1" : {
                           "value" : "通过",
                           "color" : "#173177"
                     },
                     "keyword2" : {
                           "value" : "提交的信息符合标准",
                           "color" : "#173177"
                     },
                     "keyword3" : {
                           "value" : "' . date('Y-m-d H:i:s') . '",
                           "color" : "#173177"
                     },
                     "remark" : {
                           "value" : "感谢您的支持!",
                           "color" : "#173177"
                     }
                 }
            }';

            $template[] = $jsontemplate;
            $this->httpNotice($template);

        }
    }

    /*审核不过的通知*/
    public function checkRefuseNotice(){
        $request = Request::instance();

        //oZ4XesrGjgAejPVBvxJB4mi3zfBs 兴
        //oZ4XesuWwL1EAEycGYrgLTceU5dA 赵

        if($request->isAjax()){
            $fq_id = $request->param('fq_id');

            $sql = 'select open_id from order_fq where fq_id = ?';
            $info = Db::query($sql,[$fq_id])[0];
            
            $det_url = 'http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/bossChecking&status=1&fq_id='.$fq_id;
            $jsontemplate = '{
	        "touser" : "' . $info['open_id'] .'",
            "template_id" : "_JhEYWUTTzXBhu9cGkl7w--7XEoPGcYMVTsnQowG6Ls",
            "url" : "' . $det_url . '",
            "data" : {
                "first" : {
                       "value" : "尊敬的客户您好,您提交的信息审核情况如下:",
                       "color" : "#173177"
                 },
                 "keyword1" : {
                       "value" : "失败",
                       "color" : "#173177"
                 },
                 "keyword2" : {
                       "value" : "' .$request->param('remark') . '",
                       "color" : "#173177"
                 },
                 "keyword3" : {
                       "value" : "' . date('Y-m-d H:i:s') . '",
                       "color" : "#173177"
                 },
        		 "remark" : {
                       "value" : "请您根据审核原因进行修改,感谢您的支持!",
                       "color" : "#173177"
                 }
             }
	    }';

            $template[] = $jsontemplate;
            $this->httpNotice($template);

        }

    }

    //001 002 003
    private function makell($num) {
        return '0000'.$num;
    }


    /*还款成功，给老板发送通知*/
    public function paySuccessToBoss(){
        $dparr = array('维家','维家武强店','维家深州店','华庭深州店','维家衡水店');
        $fdarr = array('维家','分期付款','定期还款');
        $tiqian=isset($_POST['tiqian']) ? $_POST['tiqian'] :'';
        //echo json_encode(['openid'=>input('openid'),'pay'=>input('payfee')]);$_POST['fq_id'][$_POST['fq_id']
        $openid=isset($_POST['openid']) ?$_POST['openid'] :'';
        //客户姓名 所属店铺
        $sql1 = 'select name,shop_id from user_kehu where open_id="'.$openid.'" order by uid  desc limit 1';
        $data1 = Db::query($sql1);
        $info=$data1[0];
        $name = $info['name'];
        $shopid = $info['shop_id'];

        //多长时间 和 分期类型
        $fq_id=isset($_POST['fq_id']) ?$_POST['fq_id'] :'';
        $sql2 = 'select howlong,type from order_fq where fq_id = ?';
        $data2 = Db::query($sql2,[$fq_id]);
        $info2 = $data2[0];
        $howlong = $info2['howlong'];
        $type = $info2['type'];

        //还的是第几期
        $sql3 = 'select fq_id, count(fq_id) as count from repay_fq where fq_id=?';
        $data3 = Db::query($sql3,[$fq_id]);
        $info3 = $data3[0];
        $num = $info3['count'];

        //大斌openid{oZ4XesuWwL1EAEycGYrgLTceU5dA}
        //oZ4XesrGjgAejPVBvxJB4mi3zfBs 兴
        $det_url = '';

        if($tiqian == 0){

            $jsontemplate = '{
                "touser" : "oZ4XesrGjgAejPVBvxJB4mi3zfBs",
                "template_id" : "DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs",
                "url" : "' . $det_url . '",
                "data" : {
                    "first" : {
                           "value" : "'.$dparr[$shopid].'的客户: '.$name.' 已还第'.$num.'期款项，还款类型:'.$fdarr[$type].'，共'.$howlong.'期",
                           "color" : "#173177"
                     },
                     "keyword1" : {
                           "value" : "'.$this->makell($_POST['fq_id']).'",
                           "color" : "#173177"
                     },
                     "keyword2" : {
                           "value" : "维家装饰分期装修",
                           "color" : "#173177"
                     },
                     "keyword3" : {
                           "value" : "' . $_POST['payfee'] .'",
                           "color" : "#173177"
                     },
                     "keyword4" : {
                           "value" : "' . date("Y-m-d") . '",
                           "color" : "#173177"
                     },
                     "remark" : {
                           "value" : "请及时检查!",
                           "color" : "#173177"
                     }
                 }
            }';
        }else{
            $jsontemplate = '{
                "touser" : "oZ4XesrGjgAejPVBvxJB4mi3zfBs",
                "template_id" : "DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs",
                "url" : "' . $det_url . '",
                "data" : {
                    "first" : {
                           "value" : "'.$dparr[$shopid].'的客户: '.$name.' 已提前还清所有款项，还款类型:'.$fdarr[$type].'，共'.$howlong.'期",
                           "color" : "#173177"
                     },
                     "keyword1" : {
                           "value" : "'.$this->makell($_POST['fq_id']).'",
                           "color" : "#173177"
                     },
                     "keyword2" : {
                           "value" : "维家装饰分期装修",
                           "color" : "#173177"
                     },
                     "keyword3" : {
                           "value" : "' . $_POST['payfee'] .'",
                           "color" : "#173177"
                     },
                     "keyword4" : {
                           "value" : "' . date("Y-m-d") . '",
                           "color" : "#173177"
                     },
                     "remark" : {
                           "value" : "请及时检查!",
                           "color" : "#173177"
                     }
                 }
            }';


        }

        $template[] = $jsontemplate;
        $this->httpNotice($template);
    }

    /*还款成功后，提醒客户还款成功*/
    public function paySuccessToKehu(){

        $tiqian=isset($_POST['tiqian']) ? $_POST['tiqian'] :'';
        $det_url = '';


        if($tiqian == 0) {
            $jsontemplate = '{
                    "touser" : "' . $_POST['openid'] . '",
                    "template_id" : "DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs",
                    "url" : "' . $det_url . '",
                    "data" : {
                        "first" : {
                               "value" : "尊敬的客户您好,本期费用已还清:",
                               "color" : "#173177"
                         },
                         "keyword1" : {
                               "value" : "' . $this->makell($_POST['fq_id']) . '",
                               "color" : "#173177"
                         },
                         "keyword2" : {
                               "value" : "维家装饰分期装修",
                               "color" : "#173177"
                         },
                         "keyword3" : {
                               "value" : "' . $_POST['payfee'] . '",
                               "color" : "#173177"
                         },
                         "keyword4" : {
                               "value" : "' . date("Y-m-d") . '",
                               "color" : "#173177"
                         },
                         "remark" : {
                               "value" : "谢谢您的支持!",
                               "color" : "#173177"
                         }
                     }
                }';
            }else{

                $jsontemplate = '{
                        "touser" : "' . $_POST['openid'] . '",
                        "template_id" : "DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs",
                        "url" : "' . $det_url . '",
                        "data" : {
                            "first" : {
                                   "value" : "尊敬的客户您好,您已提前将所有装修费用还清:",
                                   "color" : "#173177"
                             },
                             "keyword1" : {
                                   "value" : "' . $this->makell($_POST['fq_id']) . '",
                                   "color" : "#173177"
                             },
                             "keyword2" : {
                                   "value" : "维家装饰分期装修",
                                   "color" : "#173177"
                             },
                             "keyword3" : {
                                   "value" : "' . $_POST['payfee'] . '",
                                   "color" : "#173177"
                             },
                             "keyword4" : {
                                   "value" : "' . date("Y-m-d") . '",
                                   "color" : "#173177"
                             },
                             "remark" : {
                                   "value" : "谢谢您的支持!",
                                   "color" : "#173177"
                             }
                         }
                    }';

                }
        $template[] = $jsontemplate;
        $this->httpNotice($template);

    }

    /*还款到期提醒如果是分期每月九号发送通知给客户，提醒客户还款，如果是定期，到日子的那天去还*/
    public function remindKehuPay(){

        $sql = 'select * from order_fq where status = ?';

        $info = Db::query($sql,[4]);

        foreach($info as $key => $value){

            $sql = 'select name from user_kehu where open_id= ? order by uid  desc limit 1';
            $data = Db::query($sql,[$value['open_id']]);
            $info=$data[0];
            $name = $info['name'];

            //分期还款$value['monthpay'] - ($value['totallixi']/$value['howlong'])
            if($value['type'] == 1){
                //DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs
                $det_url = '';
                $jsontemplate = '{
                "touser" : "' . $value['open_id'] .'",
                "template_id" : "arhCuCLi63LXbXQnP1FOdXKaV4nLrW-gaGSstC6XUwQ",
                "url" : "' . $det_url . '",
                "data" : {
                    "first" : {
                           "value" : "尊敬的客户您好,您本期有分期装修费用未还,请于本月10号及时还清:",
                           "color" : "#173177"
                     },
                     "keyword1" : {
                           "value" : "' . $this->makell($value['fq_id']) .'",
                           "color" : "#173177"
                     },
                     "keyword2" : {
                           "value" : "' . $name . '",
                           "color" : "#173177"
                     },
                     "keyword3" : {
                           "value" : "' . intval($value['cost'])  / intval($value['howlong']) . '",
                           "color" : "#173177"
                     },
                     "keyword4" : {
                           "value" : "' . intval($value['totallixi'])  / intval($value['howlong']) . '",
                           "color" : "#173177"
                     },
                      "keyword5" : {
                           "value" : "' . $value['monthpay'] . '",
                           "color" : "#173177"
                     },
                     "remark" : {
                           "value" : "感谢您的支持!",
                           "color" : "#173177"
                     }
                 }
            }';

                $template[] = $jsontemplate;
                $this->httpNotice($template);

            }else{
                //定期还款
                $a = $value['howlong'] * 30 * 24 * 60 * 60;
                $c =  $a + $value['addtime'] ; //这个是借款借到了哪天了
                $total = $value['cost'] + $value['totallixi'];
                $det_url = '';
                $jsontemplate = '{
                    "touser" : "' . $value['open_id'] .'",
                    "template_id" : "DYZcn0hOgAWjGlXSQYbR1ldmV3WDjyZIZB7FranXHPs",
                    "url" : "' . $det_url . '",
                    "data" : {
                        "first" : {
                               "value" : "尊敬的客户您好,您本期有定期装修费用未还:",
                               "color" : "#173177"
                         },
                         "keyword1" : {
                               "value" : "'.$this->makell($value['fq_id']).'",
                               "color" : "#173177"
                         },
                         "keyword2" : {
                               "value" : "维家家居定期装修",
                               "color" : "#173177"
                         },
                         "keyword3" : {
                               "value" : "' . $total . '",
                               "color" : "#173177"
                         },
                         "keyword4" : {
                               "value" : "'. date('Y-m-d',$c).'",
                               "color" : "#173177"
                         },
                         "remark" : {
                               "value" : "感谢您的支持!",
                               "color" : "#173177"
                         }
                     }
            }';
                $template[] = $jsontemplate;
                /*定期还款的通知有点问题*/
                if($c - time() == 86400){
                    //提前一天
                    $this->httpNotice($template);

               }

            }


        }

    }

    /*逾期未还款，提醒客户及时还款*/
    public function overduePayRemind()
    {
       /* {{first.DATA}}
        项目编号：{{keyword1.DATA}}
        客户姓名：{{keyword2.DATA}}
        还款金额：{{keyword3.DATA}}
        滞纳金额：{{keyword4.DATA}}
        总计：{{keyword5.DATA}}
        {{remark.DATA}}*/
        /*您好，您有一笔还款已逾期
        项目编号：XT-20160921
        客户姓名：张三
        还款金额：1000元
        滞纳金额：200元
        总计：1200元
        为避免造成不必要的损失，请按时还款
         *
         * */
        //repay表
        //jYrbxWrkG3ATqS-BH-MGntB8vc7DSPiSaJ4Swo1w79g 模板id
       /* select   id，* from   数据表 where houseno (select min(id) form 数据表 group by 重复记录字段

            having count(重复记录字段)>1 )*/
       // $sql = 'select fq_id from  repay_fq group by houseno having count(fq_id)>1';
       // $sql = 'select * from repay_fq  where fq_id in (select fq_id from repay_fq group by fq_id having count(fq_id) > 0)';
        //$sql = 'select * from repay_fq houseno (select max(id) from repay_fq group by fq_id having count(fq_id) > 0)';

        //$info = Db::query($sql);

        //print_r($info);
        /**/

    }



}