<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/15
 * Time: 23:22
 */
namespace app\api\controller;

use think\Request;
use think\Controller;
use think\Db;


class Live extends Controller{

    public function validate_talker(Request $request){
        $data = $request->param();

        $order = Db::view('order','id,profile_id,code,status')
            ->view('user_profile',['name'],'user_profile.id = order.profile_id')
            ->view('doctor_profile',['live_link'],'doctor_profile.id = order.doctor_id')
            ->where([
                'order.code' => ['=',$data['viewertoken']],
                'user_profile.name' => ['=',$data['viewername']],
                'doctor_profile.live_link' => ['=',$data['roomid']],
                'order.status' => ['=','2']
            ])
            ->find();

        if (false != $order){
//        if ($data['viewertoken'] == '12345678'){
            $result['id'] = strval($order['id']);
            $result['name'] = strval($data['viewername']);
//            $result['avatar'] = '';
            return json(['result' => 'ok', 'user' => $result]);
        }
        else
            return json(['result' => false]);

    }

    /*
    * todo:医生创建直播间
    * 接口地址：api/live/create
    * 参数：token, pwd(直播间密码)
    */
//    public function create(Request $request){
//        $data = $request->param();
//        $user = Util::token_validate($data['token']);
//        //验证token
//        if ($user->succ) {
//            $doctor = $user->msg->doctor_profile()->find();
//            if ($doctor) {
//
//                //CC视频相关参数
//                //todo:替换为CC视频正式账号ID、API KEY
//                $userid = '5B85FDD2600912FE';
//                $salt = 'P0n4ee3kziZt9HE5fhbY0Q59irIjO1y3';
//                $room_type = 2;
////                $name = urlencode($doctor['name'].'的直播间');
//                $name = urlencode('test直播间');
//
//                $publisherpass = $data['pwd'];
//                $talker_authtype = 0;
//                $classtype = 3;
//                $max_users = 2;
//                $time = '1527583121';
//
//                //todo:替换为正式验证接口
//                $talker_pass = urlencode('http://bieke.cf:8080/ma/zxy/api/Live/validate_talker');
//
//                $origin_query_string = 'classtype='.$classtype.'&max_users='.$max_users.'&name='.$name.'&publisherpass='.$publisherpass.'&room_type='.$room_type.
//                    '&talker_authtype='.$talker_authtype.'&talker_pass='.$talker_pass.'&userid='.$userid.'&time='.$time;
//                $salt_qstring = $origin_query_string.'&salt='.$salt;
//                $md5 = strtoupper(md5($salt_qstring));
//                $qstring = $origin_query_string.'&hash='.$md5;
//
////                dump($md5);
////                dump($qstring);
//                $r_data['classtype'] = $classtype;
//                $r_data['max_users'] = $max_users;
//                $r_data['name'] = 'test直播间';
//                $r_data['publisherpass'] = $publisherpass;
//                $r_data['room_type'] = $room_type;
//                $r_data['talker_authtype'] = $talker_authtype;
//                $r_data['talker_pass'] = 'http://bieke.cf:8080/ma/zxy/api/Live/validate_talker';
//                $r_data['userid'] = $userid;
//                $r_data['time'] = $time;
//                $r_data['hash'] = $md5;
//
//                dump($qstring);
////                dump(Util::http('https://ccapi.csslcloud.net/api/room/create?',$r_data));
//                return Util::http('http://bieke.cf:8080/ma/zxy/api/Live/test?'.$qstring,[])->msg;
//
//            }else
//                return json(['succ' => 0, 'error' => '医生不存在']);
//
//        } else {
//            return json(['succ' => 0, 'error' => '登录已失效']);
//        }
//    }
//
//    public function test(Request $request)
//    {
//        $data = $request->param();
//        return json($data);
//    }
}
