<?php
/*
 * 登录api
 * 2017/11/29
 * 接口地址：api/Login
 * 调用方法：GET/POST
 * 参数：username（用户名或手机号）、password
 * 返回格式：Json（token+data)
 */
namespace app\api\controller;

use think\Controller;
use app\api\model\User;
use think\Request;
use think\Session;

class Login extends Controller{
    public function index(Request $request){

        //todo:加密保存用户密码，暂用2次MD5
        $password = md5(md5(input('password')));

        //通过用户名/手机号登录
        $user_loginbyname = User::get([
            'nickname' => input('username'),
            'password' => $password
        ]);
        $user_loginbyphone = User::get([
            'phone' => input('phone'),
            'password' => $password
        ]);

        //登录成功，读取信息存入session
        if($user_loginbyname || $user_loginbyphone){
            //todo:子账号
            if($user_loginbyphone) $user = $user_loginbyphone;
            else $user = $user_loginbyname;

            //token保存登录状态
            $token = md5($user->id.$user->password.date("Y-m-d H:i:s"));
            $user->token = $token;
            $user->token_create_time = date("Y-m-d H:i:s");
            $user->save();
            
            return json(['token' => $token, 'data' => $user]);
        }else{
            return json(['error' => '登录失败'], 404);
        }
    }
}