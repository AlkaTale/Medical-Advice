<?php
/**
 * 公用函数
 * User: zxy96
 * Date: 2017/12/01
 * Time: 17:03
 */
namespace app\api\controller;

use app\api\model\ErrMsg;
use app\api\model\User as UserModel;
use think\Controller;
use think\Request;
use think\Image;
use think\Db;

class Util{

    /*
     * 查询用户登录状态和账号信息
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
                    return new ErrMsg(false,'登录已过期');
                }
            }
            //验证子账号是否属于用户
            if($profile_id == -1){
                //无需验证子账号
                return new ErrMsg(true,$user);
            }
            else{
                //根据患者/医生作不同处理
                if($user->type_id == 1){
                    $profile = $user->user_profiles()->where('id',$profile_id)->find();
                    if($profile){
                        return new ErrMsg(true,$profile);
                    }
                    else
                        return new ErrMsg(false,'患者资料不存在');
                }
//                else if($user->type_id == 2){
//                    $profile = $user->doctor_profile()->find();
//                    if($profile){
//                        return new ErrMsg(true,$profile);
//                    }
//                    else
//                        return new ErrMsg(false,'医生不存在');
//                }
            }
        }
        else
            return new ErrMsg(false,'登录已过期');
    }

    /*
     * 管理员权限验证
     * 参数：token
     * 返回值:true（通过）、string（错误信息）
     */
    public static function admin_validate($token){
        $user = Util::token_validate($token);
        if(true !=$user->succ || $user->msg->type_id != 3)
            return new ErrMsg(false,'没有权限进行此操作');
        else
            return new ErrMsg(true,$user);
    }

    /**
     * 文件上传
     * 参数：request
     */
    public static function upload(Request $request)
    {
        $files = request()->file();
        if($files == null)
            return false;
        $results = [];
        foreach($files as $file){
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $results[] = new ErrMsg(true,$info->getSavename());
                //$file->thumb(150, 150)->save('./thumb.png');
            }else{
                // 上传失败获取错误信息
                $results[] = new ErrMsg(false,$file->getError());
            }
        }
        return $results;
    }
    /**
     * 记录尝试次数
     * 参数：request
     */
    public static function log_attempt(Request $request, $type, $status, $user = null)
    {
        $ip_address = $request->ip();
        if ($type == 'LOGIN')
            Db::name('attempt_log')
                ->insert(['ip' => $ip_address, 'type' => $type, 'time' => date("Y-m-d H:i:s"), 'status' => $status, 'user' => $user]);
    }

    /**
     * 查询尝试次数
     * 参数：request
     */
    public static function get_attempt_count(Request $request, $type, $status, $minute)
    {
        $ip_address = $request->ip();
        $count = Db::name('attempt_log')
            ->where([
                'ip' => ['=',$ip_address],
                'type' => ['=',$type],
                'status' => ['=',$status]
            ])
            ->whereTime('time', '>', '-'.$minute.' minutes')
            ->count();
        return $count;
    }
}