<?php
/**
 * 公用函数
 * User: zxy96
 * Date: 2017/12/01
 * Time: 17:03
 */
namespace app\api\controller;

use app\api\model\User as UserModel;
use think\Controller;
use think\Request;

class Util{

    /*
     * 检查用户登录状态
     * 参数：token,profile_id（可选）
     * 返回值:true（通过）、string（错误信息）
     */
    public static function token_validate($token, $profile_id = -1){
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

    /**
     * 文件上传
     * 参数：request
     */
    public static function upload(Request $request)
    {
        // 获取表单上传文件
        $file = $request->file('file');
        if (empty($file)) {
            return 0;
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            return $info->getRealPath();
        } else {
            // 上传失败获取错误信息
            //$this->error($file->getError());
        }
    }
}