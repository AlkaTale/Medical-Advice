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
                $temp = date("Y-m-d G:H:s",strtotime(time()) - $user->token_valid_time * 60);
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
            return new ErrMsg(true,$user->msg);
    }

    /**
     * 文件上传
     * 参数：request
     */
    public static function upload(Request $request)
    {
        $data = $request->param();
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
                //保存缩略图
                $imgpath = ROOT_PATH . 'public' . DS . 'uploads'. DS . $info->getSavename();
                $image = Image::open($imgpath);
                $path = ROOT_PATH . 'public' . DS . 'uploads'. DS .date('Ymd');
                $thumb_name = 'thumb_'.explode(DS,$info->getSavename())[1];
                $thumb_path = $path. DS .$thumb_name;
                $image->thumb(200, 200)->save($thumb_path);

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

    /**
     * 发送HTTP请求方法
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    public static function http($url, $params, $method = 'GET', $header = array(), $multi = false){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                return new ErrMsg(false, '不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error) return new ErrMsg(false, '请求发生错误：' . $error);
        return new ErrMsg(true, $data);
    }
}