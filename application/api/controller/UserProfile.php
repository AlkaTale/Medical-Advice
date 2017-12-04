<?php
/*
 * UserProfile类控制器
 * 2017/12
 */
namespace app\api\controller;

use app\api\model\UserProfile as UserProfileModel;
use app\api\model\User;
use think\Controller;
use think\Request;


class Userprofile extends Controller{

    /*
     * 查询
     * 接口地址：api/UserProfile
     * 参数：token,profile_id(0）
     */
    public function index(Request $request){
        $data = $request->param();

        //单个
        if($data['profile_id'] > 0){
            //验证token
            if(Util::token_validate($data['token'],$data['profile_id'])){
                $user = User::get(['token' => $data['token']]);
                $profile = $user->user_profiles()->where('id',$data['profile_id'])->find();
                return json($profile);
            }
            else{
                return json(['error' => '登录已失效']);
            }
        }
        //列表
        else{
            //验证token
            if(Util::token_validate($data['token'])){
                $user = User::get(['token' => $data['token']]);
                $list = $user->user_profiles()->selectOrFail();
                return json($list);
            }
            else{
                return json(['error' => '登录已失效']);
            }
        }
    }
}