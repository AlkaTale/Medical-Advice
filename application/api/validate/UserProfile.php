<?php
/**
 * User模型验证器
 * User: zxy96
 * Date: 2017/11/30
 * Time: 0:34
 */
namespace app\api\validate;

use think\Validate;

class UserProfile extends Validate
{
// 验证规则
    protected $rule = [
        ['name' , 'require|unique:user_profile', '姓名必须|患者已存在'],
        ['phone', 'require|/^1[34578]\d{9}$/', '手机号必须|手机号格式错误'],
    ];
}