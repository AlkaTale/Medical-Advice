<?php
/**
 * Token生成/处理类
 * User: zxy96
 * Date: 2017/11/30
 * Time: 0:07
 */
namespace app\api\controller;

use app\api\model\User as UserModel;
use think\Controller;

class Token{

    public static function create($id, $password){
        $token = md5($id.$password.date("Y-m-d H:i:s"));
        return $token;
    }

    public static function update(UserModel $user,$token){
        //$id = $user->id;
        //$user = UserModel::get(1);
        $user['token'] = $token;
        $user['token_create_time'] = date("Y-m-d H:i:s");
        $user->save();
    }
}