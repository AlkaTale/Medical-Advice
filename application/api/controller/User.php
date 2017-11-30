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
            return json(['error' => $valid_result]);
        }
        else{
            //密码加密保存
            $data['password'] = md5(md5($data['password'] ));
            $result = UserModel::create($data);
            $token = Token::create($result->id,$result->password);
            Token::update($result,$token);
            return json(['token' => $token, 'data' => $result]);
        }
    }

    /*
     * 检查用户登录状态
     * 参数：token,profile_id（可选）
     * 返回值:true（通过）、string（错误信息）
     */
    public static function token_validate(Request $request, $profile_id = -1){
        $token = $request->param('token');
        $user = UserModel::get(['token' => $token]);
        //验证token是否存在
        if($user){
            //验证token是否过期
            if($user->token_valid_time != 0){
                $temp = date("Y-m-d G:H:s",strtotime("-".$user->token_valid_time." minutes"));
                if($user->token_create_time <= $temp){
                    return '登录已过期';
                }
            }
            //验证子账号是否属于用户
            if($profile_id == -1){
                //无需验证子账号
                return 1;
            }
            else{
                //根据患者/医生作不同处理
                if($user->type_id == 1){
                    $profile = $user->user_profiles()->where('id',$profile_id)->find();
                    if($profile){
                        return true;
                    }
                    else
                        return '子账号不存在';
                }
                else if($user->type_id == 2){
                    $profile = $user->doctor_profile()->where('id',$profile_id)->find();
                    if($profile){
                        return true;
                    }
                    else
                        return '子账号不存在';
                }
            }
        }
    }
}