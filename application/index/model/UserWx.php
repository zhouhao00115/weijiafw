<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/11
 * Time: 21:00
 */
namespace app\index\model;
use think\Model;
use think\Db;

class UserWx extends Model
{
    /*添加微信关注者到数据库中*/
    public  function addNoticer($info){
       //file_put_contents('aaa.txt',$info);
        //传来json字符串,将其转为数组
        $info = json_decode($info,true);


        //查询库里是否存了这条信息，如果没有就插入
        $result = $this->where('open_id',$info['openid'])->find();

        if(empty($result)){
            //file_put_contents('bbb.txt',$result);
            $addtime = time();
            $this->open_id = $info['openid'];
            $this->nickname = $info['nickname'];
            $this->sex = $info['sex'];
            $this->country = $info['country'];
            $this->province = $info['province'];
            $this->city = $info['city'];
            $this->language = $info['language'];
            $this->headimgurl = $info['headimgurl'];
            $this->subscribe_time = $info['subscribe_time'];
            $this->remark = $info['remark'];
            $this->add_time = $addtime;

            $this->save();
            //file_put_contents('ccc.txt',$this->wx_id);

            //echo $userwx->wx_id;返回新插入的那条id
        }
        else{
            //file_put_contents('dddd.txt',$result);
        }

    }

    /*将关注者列表批量导入数据库中*/
    public function saveAllGuanzhu($info){

//        $list = [
//            ['open_id'=>$info[1]->openid,'email'=>'thinkphp@qq.com'],
//            ['name'=>'onethink','email'=>'onethink@qq.com']
//        ];
        foreach ($info as $value){
            $arr = json_decode($value,true);
            $data = array('open_id'=>$arr['openid'],
                          'nickname'=>$arr['nickname'],
                          'sex' => $arr['sex'],
                          'country' => $arr['country'],
                          'province' => $arr['province'],
                          'city' => $arr['city'],
                          'language' => $arr['language'],
                          'headimgurl' => $arr['headimgurl'],
                          'subscribe_time' => $arr['subscribe_time'],
                          'remark' => $arr['remark'],
                          'add_time' => time(),
                );
            $newData[] = $data;
        }
        //print_r($newData);
       foreach($newData as $data){

           $this->data($data,true)->isUpdate(false)->save();

        }

    }

    /*绑定店员的微信*/
    public static function updateWxdianyuan($openid,$did){

        return self::where('open_id',"$openid")->update(['user_shop_id'=>$did]);

    }




}
