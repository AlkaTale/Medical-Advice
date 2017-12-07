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
        $data['create_time'] = date("Y-m-d H:i:s");
        //$result = UserModel::create($data);

        //加载验证器
        $valid_result = $this->validate($data,'User');
        if(true !== $valid_result){
            return json(['succ' => 0,'error' => $valid_result]);
        }
        else{
            //密码加密保存
            $data['password'] = md5(md5($data['password'] ));
            $result = UserModel::create($data);
            $token = Token::create($result->id,$result->password);
            Token::update($result,$token);
            return json(['succ' => 1,'token' => $token, 'data' => $result]);
        }
    }
}