<?php
namespace app\index\controller;

use \think\Controller;
use \think\Request;
use \think\Db;
use app\index\model\OrderFq;
use app\index\model\RepayFq;
class Protocol extends Controller{




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



    /*判断用户是否有过分期装修*/
    public function isHaveFenqi(){

        //$openid = input('openid');
        $customeUrl ='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $result = $this->baseAuth($customeUrl);

        foreach($result as $key => $value){
            if($key=='openid'){

                //header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/manage/manage&openid=".$value);

                $ofq = new OrderFq();
                $info = $ofq->where(['open_id' => $value])->order('fq_id desc')->select();
                if($info){
                    //得到此分期订单的状态
                    $status = $info[0]['status'];
                    if($status==4){
                        header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/huankuanFirst&openid=".$value);exit;
                    }
                    if($status == 3){
                        /*还款完成提示信息*/
                        header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/finishPay");exit;
                    }

                    header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/bossChecking&status=".$status);
                }else{
                    //如果没有，去协议页面
                    header("Location: http://www.weijiazhuang.top/weijiafw/public/index.php?s=index/protocol/index&openid=".$value);
                }





            }
        }



    }


    /*分期协议的首页*/
    public function index(){


        return $this->fetch();
    }

    /*分期装修页面的添加客户*/
    public function addFenqiOrder(){

        return $this->fetch();
    }
    /*分期装修页面的分期信息*/
    public function fenqiDetail(){
        return $this->fetch();
    }

    /*还款完成提示信息*/
    public function finishPay(){
        return $this->fetch();
    }


    /*将分期装修的信息添加到表中*/
    public function insertfenqiDetail(){

        $request = Request::instance();
        $orderfq = new OrderFq();

        //print_r($request);exit();
        if($request->isAjax()){

            $orderfq->cost = $request->param('cost');
            $orderfq->howlong = $request->param('howlong');
            $orderfq->type = $request->param('type');
            $orderfq->monthpay = $request->param('monthpay');
            $orderfq->totallixi = $request->param('totallixi');
            $orderfq->kh_id = $request->param('kh_id');
            $orderfq->open_id = $request->param('openid');
            $orderfq->addtime = time();
            $orderfq->status = 0;
            $orderfq->save();

                if($orderfq->fq_id){
                    echo json_encode(['errCode'=>'0000','errMsg'=>'添加分期信息成功','fq_id'=>$orderfq->fq_id]);
                }
            }else{
                echo json_encode(['errCode'=>'0000','errMsg'=>'添加分期信息失败']);
            }
        }

    /*客户从菜单按钮点击进去的页面*/
    public function kehufenqizhuang(){

        return $this->fetch('myfenqi');

    }

    /*老板点击后台分期审核按钮进来界面*/
    public function checkall(){

        $sql = 'select fq_id from order_fq where status = 0';
        $data = Db::query($sql);

        print_r($data);


    }


    /*老板点击通知进来审核页面*/
    public function bossCheck(){
        $fq_id=isset($_GET['fq_id']) ?$_GET['fq_id']:'';
       // $fq_id=199;
        //客户信息和分期的一些信息
        $sql="SELECT *,CASE shop_id 
                WHEN 1 THEN '维家武强店'
                WHEN 2 THEN '维家深州店'
                WHEN 3 THEN '华庭深州店'
                WHEN 4 THEN '维家衡水店'
                ELSE '其他' END AS shop,
                CASE type
                WHEN 1 THEN '分期支付'
                WHEN 2 THEN '定期支付'
                ELSE '其他' END  AS fukuan
         FROM `order_fq` LEFT JOIN `user_kehu` ON `order_fq`.kh_id=`user_kehu`.uid WHERE `order_fq`.fq_id=".$fq_id;
        $data=Db::query($sql);
        $this->assign('detail',$data[0]);
        // print_r($data);exit;
        //客户图片
        $sql="SELECT pic FROM `user_picture` WHERE uid=".$data[0]['uid'];
        $pic=Db::query($sql);
        $array=array();
        $array=explode(',',$pic[0]['pic']);
        foreach ($array as $key => $value) {
            $this->assign('pic'.$key,$value);
        }

        
       return $this->fetch();
    }

    /*老板点击通知进来审核界面的数据*/
    public function getCheckData(){

        $request = Request::instance();
        if($request->isAjax()){
            $sql = 'SELECT o.type,o.cost,o.howlong,o.monthpay,o.totallixi,u.shop_id,u.name,u.phone,u.idcard,u.gtphone,p.pic
				FROM user_kehu u
				LEFT JOIN order_fq o ON u.uid = o.kh_id
				LEFT JOIN user_picture p ON u.uid = p.uid
				WHERE o.fq_id = ?
				ORDER BY o.addtime DESC';

            $info = Db::query($sql,[$request->param('fq_id')])[0];
            if($info['type'] == 1){
                $info['type'] = '分期月付';
            }else{
                $info['type'] = '定期支付';
            }
           // $shopArr = array('维家武强店','维家深州店','华庭深州店','维家衡水店');


            echo json_encode(['errcode'=>'0000','info'=>$info]);
        }

    }

    /*审核通过，更新数据库的订单状态*/
    public function checkPass(){

        $request = Request::instance();
        if($request->isAjax()){
            $res = Db::table('order_fq')->where('fq_id',$request->param('fq_id'))->update(['status' => 2,'sh_time'=>time()]);
            if($res){
                echo json_encode(['errCode'=>'0000','errMsg'=>'审核状态更新成功']);
            }

        }

    }

    /*审核不过，也更新数据库的订单状态*/
    public function checkNotPass(){

        $request = Request::instance();
        if($request->isAjax()){
            $res = Db::table('order_fq')->where('fq_id',$request->param('fq_id'))->update(['status' => 1,'sh_remark' => $request->param('remark'),'sh_time'=>time()]);
            if($res){
                echo json_encode(['errCode'=>'0000','errMsg'=>'审核状态更新成功']);
            }

        }

    }

    /*客户提交完协议以后，还在审核状态的一个提示页面*/
    public function bossChecking(){
        return $this->fetch('checking');
    }


    /*客户提交了信息，审核失败，点进去前去修改*/
    public function editKhMessage(){

        return $this->fetch('editKehu');

    }






    /*还款首页*/
    public function huankuanFirst(){
        $openid=isset($_GET['openid'])?$_GET['openid']:'';
        // $openid="oZ4XesoQ5a1wD-mtB8ymi1-e7N58";
        if(!empty($openid)){ 
            $sql=" SELECT * FROM `order_fq` WHERE open_id='".$openid."' and status = 4";
            $data=Db::query($sql);
            if(!empty($data) && $data[0]['type']==1){//分期
                $fq_id=$data[0]["fq_id"];
                $sql='SELECT * FROM `repay_fq` WHERE fq_id='.$fq_id;
                $arr=Db::query($sql);
                $num=0;
                if(!empty($arr)){
                    $num=count($arr);//还了几期了
                    $lixi=$data[0]['totallixi']/$data[0]['howlong'];//每月利息
                    $data[0]['cost']=$data[0]['cost']-($data[0]['monthpay']-$lixi)*$num;//未还本金
                }
                $totalnum=$data[0]['howlong']-$num;
                $this->assign('num',$totalnum);
                $this->assign('cost',$data[0]['cost']);
                $this->assign('monthpay',$data[0]['monthpay']);
                $this->assign('fq_id',$data[0]['fq_id']);
                return $this->fetch('huankuan');
            }
            else if(!empty($data) && $data[0]['type']==2){//定期付款
                $this->assign('data',$data[0]);
                $date=date('Y-m-d',strtotime($data[0]['howlong'].' months',$data[0]['sh_time']));//定期还款期限
                $this->assign('date',$date);//还款日期
                return $this->fetch('dqhuankuan');
            }
        }
        
    }

    /*分期支付页面*/
    public function zhifu(){
        $fq_id=isset($_GET['fq_id'])?$_GET['fq_id']:'';

        $sql='SELECT * FROM `order_fq` WHERE fq_id='.$fq_id;
        $data=Db::query($sql);
        if(!empty($data)){
            $lixi=$data[0]['totallixi']/$data[0]['howlong'];
            $benjin=$data[0]['cost']/$data[0]['howlong'];
            $zonge=$data[0]['monthpay'];
            $openid=$data[0]['open_id'];
            $howlong=$data[0]['howlong'];
            $sh_time = $data[0]['sh_time'];//审核时间
             //还款截止日期
            //先查询还款表里面有没有fq_id这条id对应的还款项目
            $sql1 = 'select fq_id, count(fq_id) as count from repay_fq where fq_id='.$fq_id;
            $data1=Db::query($sql1)[0];

            //查出的为空
            if($data1['count'] == 0){
                //查出的为空，说明没有还款，需在审核时间的下个月还
                $m = date("m",$sh_time);
                $y = date("Y",$sh_time);
                if($m + 1 < 13){
                    $m = $m + 1;
                }else{
                    $m = 1;
                    $y = $y + 1;
                }
                $first = $y.'-'.$m.'-10';
                $datetime = $first;
            }else{
                //查出的不为空,说明库里面已经有数据了，只需要查询最近应该还款的字段，比如2017-11-10 那么下次应该还款时间就是2017-12-10
                $sql2 = 'select should_time from `repay_fq` where fq_id='.$fq_id.' order by rp_id desc limit 1';
                $data2 = Db::query($sql2);

                $should = $data2[0]['should_time'];
                $shouldArr = explode("-",$should);
                $yy = $shouldArr[0];
                $mm = $shouldArr[1];
                if($mm + 1 < 13){
                    $mm = $mm + 1;

                }else{
                    $mm = 1;
                    $yy = $yy + 1;

                }
                //print_r($mm);
                //拼接字符串
                $should = $yy.'-'.$mm.'-10';
                $datetime = $should;
            }

        }
        $this->assign('datetime',$datetime);
        $this->assign('howlong',$howlong);
        $this->assign('benjin',$benjin);
        $this->assign('lixi',$lixi);
        $this->assign('zonge',$zonge);
        $this->assign('fq_id',$fq_id);
        $this->assign('openid',$openid);

        return $this->fetch();
    }
    /*定期支付页面*/
    public function dqzhifu(){
        $fq_id=isset($_GET['fq_id'])?$_GET['fq_id']:'';
        $sql='SELECT * FROM `order_fq` WHERE fq_id='.$fq_id;
        $data=Db::query($sql)[0];
        $end_time=date('Y-m-d',strtotime($data['howlong'].' months',$data['sh_time']));//定期还款期限
        $allpay = $data['cost'] + $data['totallixi'];

        $start = date('Y-m-d',$data['sh_time']);

        $this->assign('cost',$data['cost']);//借多少
        $this->assign('start_time',$start);//审核时间，即开始时间
        $this->assign('end_time',$end_time);//结束时间
        $this->assign('totallixi',$data['totallixi']);//总利息
        $this->assign('allPay',$allpay);//总共要支付的费用
        $this->assign('openid',$data['open_id']);
        $this->assign('fq_id',$data['fq_id']);

        return $this->fetch();
    }




    /*提前支付页面*/
    public function tqzhifu(){
        $fq_id=isset($_GET['fq_id'])?$_GET['fq_id']:'';
        $whcost = isset($_GET['whcost'])?$_GET['whcost']:'';
        $sql='SELECT * FROM `order_fq` WHERE fq_id='.$fq_id;
        $data=Db::query($sql);
        //分期
        if(!empty($data) && $data[0]['type']==1){
            $sql1 = 'SELECT should_time,actual_time FROM `repay_fq` where fq_id = ? ORDER BY rp_id DESC;';
            $data1 = Db::query($sql1,[$fq_id]);
            $count = count($data1);//还了几期了

            if($count != 0){
                $last = $data1[0]['should_time'];//上次的某个月的十号

                $cha=round((time()-strtotime($last))/86400); //当前时间距离还款时间有几天

                $lixi=round(0.0002 * $cha * $data[0]['cost']);//每天利息 * 总钱数 * 差了几天 = 总利息

                //本金未还 的还有 上个页面传来的$whcost

                $total = $lixi + $whcost;


                $this->assign('zonge',$total);
                $this->assign('benjin',$whcost);
                $this->assign('lixi',$lixi);
                $this->assign('fq_id',$fq_id);
                $this->assign('openid',$data[0]['open_id']);

            }else {

                $sh_time = $data[0]['sh_time'];
                $now = time();

                $cha = round(($now - $sh_time) / 86400);//当前时间距离审核时间差多少天

                //一个月还款免息1500420636  1509420636
                if($cha < 32){
                    $total = $whcost;
                    $lixi = 0;
                }else {
                    $lixi=round(0.0002 * $cha * $data[0]['cost']);
                    $total = $whcost + $lixi;

                }
                $this->assign('zonge',$total);
                $this->assign('benjin',$whcost);
                $this->assign('lixi',$lixi);
                $this->assign('fq_id',$fq_id);
                $this->assign('openid',$data[0]['open_id']);

            }
            return $this->fetch('tqzhifu');
        }
      //定期
        if(!empty($data) && $data[0]['type']==2){

            $end_time=date('Y-m-d',strtotime($data[0]['howlong'].' months',$data[0]['sh_time']));//定期还款期限
            $lixi=round((time()-$data[0]['sh_time'])/86400*($data[0]['totallixi']/$data[0]['howlong']/30));//利息
            $sh_time = $data[0]['sh_time'];
            $now = time();

            $cha = round(($now - $sh_time) / 86400);//当前时间距离审核时间差多少天

            //一个月还款免息1500420636  1509420636
            if($cha < 32){
                $total = $data[0]['cost'];
                $lixi = 0;
            }else {
                //$lixi=round(0.0002 * $cha * $data[0]['cost']);
                $total = $data[0]['cost'] + $lixi;

            }


            $this->assign('pay',$total);//实际应还 本金 + 利息
            $this->assign('cost',$data[0]['cost']); //借的本金
            $this->assign('lixi',$lixi); //利息

            $this->assign('start_time',date("Y-m-d",$data[0]['sh_time']));//借款开始时间
            $this->assign('today',date("Y-m-d"));//当前日期
            $this->assign('endtime',$end_time); //定期支付到期时间

            $this->assign('fq_id',$data[0]['fq_id']);
            $this->assign('openid',$data[0]['open_id']);


            return $this->fetch('tqdqzhifu');

        }



    }
    /*点击前去还款状态修改为4*/
    public function changestatus(){
        $openid=isset($_POST['openid']) ?$_POST['openid']:'';
        $sql="select * from `order_fq` where status=2 and open_id='".$openid."' limit 1";
        $data=Db::query($sql);
        if(!empty($data)){
            if(!empty($openid)){
                $res = Db::table('order_fq')->where('fq_id',$data[0]['fq_id'])->update(['status' => 4]) ;
            }
        }
        
        echo 1;
    }
    /*分期支付，付款成功，插入到数据库*/
    public function fenqiRepay(){

        $repay = new RepayFq();
        $request = Request::instance();
        if($request->isAjax()){
            if($request->param('tiqian') == 0){
                $repay->fq_id = $request->param('fq_id');
                $repay->should_time = $request->param('should_time');
                $repay->pay = $request->param('payFee');
                $repay->actual_time = date("Y-m-d H:i:s");
                $repay->addtime = time();

                $repay->is_finish = 0;

                $repay->save();

                $sql1 = 'select fq_id,count(fq_id) as count from repay_fq where fq_id ='.$request->param('fq_id');
                $data1=Db::query($sql1)[0];
                $count = $data1['count'];//查询出数据中有fq_id的有多少条数据

                $sql2 = 'select howlong from order_fq where fq_id = '.$request->param('fq_id');
                $data2 = Db::query($sql2)[0];
                $howlong = $data2['howlong'];//查询出一共有多少期

                //如果数据库中的数据小于存的多少期
                if($count < $howlong) {

                    echo json_encode(['errCode' => '0000', 'errMsg' => '插入数据库成功', 'count' => $count]);

                }else{
                    //如果$count不小于分期数，那就更新数据库中的状态
                    $repay->where('fq_id',$request->param('fq_id'))->update(['is_finish' => 1]);

                    $order_fq = new OrderFq();

                    $order_fq->where('fq_id',$request->param('fq_id'))->update(['status' => 3]);

                    echo json_encode(['errCode' => '0000','errMsg' => '该分期还款已完成','count' => $count]);

                }


            }else{

                $repay->fq_id = $request->param('fq_id');
                $repay->should_time = 'tiqian';
                $repay->pay = $request->param('payFee');
                $repay->actual_time = date("Y-m-d H:i:s");
                $repay->addtime = time();

                $repay->is_finish = 0;

                $repay->save();

                //分期提前还款，将is_finish 字段都更新成1
                $repay->where('fq_id',$request->param('fq_id'))->update(['is_finish' => 1]);

                $order_fq = new OrderFq();

                $order_fq->where('fq_id',$request->param('fq_id'))->update(['status' => 3]);

                echo json_encode(['errCode' => '0000','errMsg' => '提前还款已完成']);


            }
        }




    }

    /*定期支付，付款成功，插入数据库的接口*/
    public function dingqiRepay(){

        //echo json_encode(['errCode'=>'0000','errMsg'=>'查看状态']);
        $repay = new RepayFq();
        $request = Request::instance();
        if($request->isAjax()){
            //定期非提前还款
            if($request->param('tiqian') == 0){
                $repay->fq_id = $request->param('fq_id');
                $repay->should_time = $request->param('should_time');
                $repay->pay = $request->param('payFee');
                $repay->actual_time = date("Y-m-d H:i:s");
                $repay->addtime = time();
                $repay->is_finish = 1; //定期非提前还完就完成了

                $repay->save();


                //定期非提前还款，更新order_fq表status表字段
                $order_fq = new OrderFq();

                $order_fq->where('fq_id',$request->param('fq_id'))->update(['status' => 3]);

                echo json_encode(['errCode' => '0000','errMsg' => '该定期还款已完成']);

            }else{

                $repay->fq_id = $request->param('fq_id');
                $repay->should_time = 'tiqian';
                $repay->pay = $request->param('payFee');
                $repay->actual_time = date("Y-m-d H:i:s");
                $repay->addtime = time();

                $repay->is_finish = 1;

                $repay->save();

                $order_fq = new OrderFq();

                $order_fq->where('fq_id',$request->param('fq_id'))->update(['status' => 3]);

                echo json_encode(['errCode' => '0000','errMsg' => '定期还款提前已完成']);


            }

        }


    }








}