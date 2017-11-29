<?php
/**
 * User模型验证器
 * User: zxy96
 * Date: 2017/11/30
 * Time: 0:34
 */
namespace app\api\validate;

use think\Validate;

class User extends Validate
{
// 验证规则
    protected $rule = [
        //['nickname' , 'require|min:5|unique:user|/^([a-zA-Z0-9@*#])$/', '昵称必须|昵称不短于5个字符|昵称已存在|昵称必须包含字母和数字'],
       // ['phone', 'require|/^1[34578]\d{9}$/', '手机号必须|手机号格式错误'],
        //['' , 'require|min:5|unique:user', '昵称必须|昵称不短于5个字符|昵称已存在'],
        //['phone', 'require|unique:user', '手机号必须|手机号已存在'],
        'nickname'  =>  'require|min:5|unique:user',
    ];
}
