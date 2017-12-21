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
            $token = Token::create($user->id,$user->password);
            Token::update($user,$token);
            //防止返回密码
            $user->password = "";
            return json(['succ' => 1,'token' => $token, 'data' => $user]);
        }else{
            return json(['succ' => 0,'error' => '登录失败']);
        }
    }
}