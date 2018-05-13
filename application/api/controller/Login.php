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
use think\Db;

class Login extends Controller{
    
    public function index(Request $request){
        $result = Db::name('const')
            ->where('const_type', '=', 'max_login_attempt')
            ->find();
        $max_login_attempt = $result['const_value'];

        $result = Db::name('const')
            ->where('const_type', '=', 'login_minute')
            ->find();
        $login_minute = $result['const_value'];
        
        $count = Util::get_attempt_count($request, 'LOGIN', 0, $login_minute);
        if($count >= $max_login_attempt)
            return json(['succ' => 0,'error' => '短时间内登录尝试次数已达上限']);
        
        //todo:加密保存用户密码，暂用2次MD5，加密应在客户端进行！
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

        $user_identifier = (input('username') == null)?input('phone'):input('username');

        //登录成功，读取信息存入session
        if($user_loginbyname || $user_loginbyphone){
            if($user_loginbyphone) $user = $user_loginbyphone;
            else $user = $user_loginbyname;

            //token保存登录状态
            $token = Token::create($user->id,$user->password);
            Token::update($user,$token);
            Util::log_attempt($request,'LOGIN',1, $user_identifier);
            return json(['succ' => 1,'token' => $token, 'data' => $user]);
        }else{
            Util::log_attempt($request,'LOGIN',0, $user_identifier);
            $msg = '登录失败， 还剩'. ($max_login_attempt - $count - 1) .'次尝试机会';
            return json(['succ' => 0,'error' => $msg]);
        }
    }
    
    /*
     * 查询登录记录
     * 接口：api/Login/log
     * 参数：token, status
     */
    public function log(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $results = Db::name('attempt_log')
                ->where([
                    'user' => [['=', $user->msg->nickname],['=', $user->msg->phone],'or'],
                    'status' => ['=',$data['status']]
                ])
                ->order('id', 'desc')
                ->limit(10)
                ->select();

            //隐藏IP后两位
            $reg = '~(\d+)\.(\d+)\.(\d+)\.(\d+)~';
            $results1 = [];
            foreach ($results as $result){
                $result['ip'] = preg_replace($reg,"$1.$2.*.*",$result['ip']);
                $results1[] = $result;
            }

            return json(['succ' => 1, 'data' => $results1]);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }
}