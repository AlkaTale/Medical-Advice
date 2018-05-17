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
}
