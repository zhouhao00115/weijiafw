<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/14
 * Time: 21:17
 */
namespace app\index\controller;

use \think\Controller;
use \think\Request;
use app\index\model\UserShop;
use app\index\model\UserKehu;
use app\index\model\UserPicture;
class User extends Controller{
    /*店员添加页面*/
    public function addUser(){

        return $this->fetch();
    }
    /*将店员插入数据库中*/
    public function doAddUser(){
        $request = Request::instance();
        $usershop = new UserShop();

        if($request->isAjax()){
            $bool = $usershop->getUser($request->param('name'),$request->param('phone'));
            if($bool){
                $addtime = time();
                $usershop->username = $request->param('name');
                $usershop->phone = $request->param('phone');
                $usershop->type = $request->param('type');
                $usershop->shop = $request->param('shop');

                $usershop->addtime = time();

                $usershop->save();

                if($usershop->d_id){
                    echo json_encode(['errCode'=>'0000','errMsg'=>'添加店员成功']);
                }
            }else{
                echo json_encode(['errCode'=>'0000','errMsg'=>'此店员已经添加']);
            }
        }

    }

    /*分期装修协议中的添加客户*/
    public function insertKehufenqi(){

        $request = Request::instance();
        $kehu = new UserKehu();
        //客户信息
            $addtime = time();
            $kehu->name = $_POST['name'];
            $kehu->phone = $_POST['phone'];
            $kehu->idcard = $_POST['idcard'];
            $kehu->gtphone = $_POST['gtphone'];
            $kehu->shop_id = $_POST['dianpu'];
            $kehu->open_id = $_POST['openid'];
            $kehu->addtime = time();
            $kehu->save();
            if($kehu->uid){
                //客户图片
                $pic= new UserPicture();
                // print_r($_FILES['pic']);exit;
                //上传图片存放位置
                $dir=dirname(dirname(dirname(dirname(__FILE__))));
                $uploadpic=$dir."/public/static/uploads/kehu_pic";
                //原始路径
                $tmp_name=array();
                $tmp_name=$_FILES['pic']['tmp_name'];
                //原始图片name
                $name=array();
                $name=$_FILES['pic']['name'];
                $time=time();
                mkdir($uploadpic.'/'.$kehu->uid.$time);
                //上传到服务器制定文件夹下
                $sql='';
                foreach ($name as $key => $value) {
                   move_uploaded_file($tmp_name[$key], $uploadpic.'/'.$kehu->uid.$time.'/'.$value); 
                   $sql.=$kehu->uid.$time.'/'.$value.',';
                }
                $sql=substr($sql,0,-1);
                $pic->uid=$kehu->uid;
                $pic->pic=$sql;
                $pic->save();
                if($pic->id){
                    echo "<script>localStorage.setItem('kh_id',".$kehu->uid.");location.href ='?s=index/protocol/fenqiDetail';</script>";
                }else{
                    echo "<script>alert('客户信息保存失败');history.back();</script>";
                }
                
            }else{
                echo "<script>alert('客户信息保存失败');history.back();</script>";
            }
        
        
        


    }

    /*更新分期客户表里的一个字段kh_id*/
    public function upfenqiId(){

        $request = Request::instance();
        if($request->isAjax()){
            $bool = UserKehu::where('uid',$request->param('kh_id'))->update(['fq_id'=>$request->param('fq_id')]);
            if($bool){
                echo json_encode(['errCode'=>'0000','errMsg'=>'更新成功']);
            }
        }


    }



}