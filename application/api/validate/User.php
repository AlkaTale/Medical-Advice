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
        ['nickname' , 'require|min:5|unique:user|/^[a-zA-Z][a-zA-Z0-9_]*$/', '昵称必须|昵称不短于5个字符|昵称已存在|昵称必须以字母开头，且只能包含字母、数字、下划线'],
        ['phone', 'require|/^1[34578]\d{9}$/|unique:user', '手机号必须|手机号格式错误|手机号已存在'],
        ['password' , 'require|min:8|', '密码必须|密码不短于8个字符'],
    ];
}
