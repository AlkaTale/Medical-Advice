<?php
/*
 * User类控制器
 * 2017/11/29
 */
namespace app\api\controller;

use app\api\model\User as UserModel;
use think\Controller;
use think\Request;

class User extends Controller{

    /*
     * 注册账户
     * 接口地址：api/User/create
     * 参数：nickname,password,phone,type_id
     */
    public function create(Request $request){
        $data = $request->param();
        //密码加密保存
        $data['password'] = md5(md5($data['password'] ));
        $data['create_time'] = date("Y-m-d H:i:s");
        //$result = UserModel::create($data);

        //注册成功，保存登录状态
        if(true !==$this->validate($data,'User')){
            $result = UserModel::create($data);
            $token = Token::create($result->id,$result->password);
            Token::update($result,$token);
            return json(['token' => $token, 'data' => $result]);
        }
    }
    

}