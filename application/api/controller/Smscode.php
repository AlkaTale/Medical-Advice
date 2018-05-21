<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/14
 * Time: 1:07
 */
namespace app\api\controller;

use app\api\model\User as UserModel;
use think\Controller;
use think\Request;
use think\Db;


class Smscode extends Controller{
    
    public static function send(Request $request){

        $data = $request->param();

        //发送短信前先检查注册信息
        //补充参数：nickname,password,phone
        if ($data['action'] == 'REG'){
            //加载验证器
            $user = new User();
            $valid_result = $user->validate_reg($data);
            if(true !== $valid_result){
                return json(['succ' => 0,'error' => $valid_result]);
            }
        }
        //更改手机号前验证现手机号是否正确
        elseif ($data['action'] == 'CHANGEPHONE'){
            $msg = Util::token_validate($data['token']);
            if($msg->succ){
                $user = Db::name('user')
                    ->where([
                        'token' => ['=', $data['token']],
                    ])
                    ->find();
                if($user['phone'] != $data['old_phone'])
                    return json(['succ' => 0,'error' => '原绑定手机号不正确']);
            }
            else
                return json(['succ' => 0, 'error' => $msg->msg]);

            //验证换绑手机号是否重复
            $user = Db::name('user')
                ->where([
                    'phone' => ['=', $data['phone']],
                ])
                ->find();
            if ($user != null)
                return json(['succ' => 0,'error' => '换绑手机号已被其他账号绑定']);
        }
        else
            return json(['succ' => 0,'error' => '参数错误']);

        $code = rand('100000','999999');

        Db::name('sms_code')
            ->insert(['code' => $code, 'phone' => $data['phone'], 'time' => date("Y-m-d H:i:s"), 'action' => $data['action']]);

//        return json(['succ' => 1,'msg' => '发送成功']);

        $response = SmsUtil::sendSms($data['phone'],$code);

        if ($response->Code == "OK")
            return json(['succ' => 1,'msg' => '发送成功']);
        else
            return json(['succ' => 0,'msg' => '发送失败']);
    }
}
