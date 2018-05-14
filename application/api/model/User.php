<?php

namespace app\api\model;
use think\Model;

//用户模型类
class User extends Model{

    protected $field = [
        'nickname','password','avatar','phone','create_time','type_id','token','token_create_time','token_valid_time'
    ];

    public function user_profiles(){
        //用户和用户资料的一对多关系
        return $this->hasMany('UserProfile');
    }

    public function doctor_profile(){
        //医生和医生资料的一对一关系
        return $this->hasMany('DoctorProfile');
    }

    public function user_type(){
        return $this->hasOne('UserType');
    }

    //phone读取器
    protected function getPhoneAttr($phone){
        return substr_replace($phone, '******', 3, 6);
    }

    //password读取器
    protected function getPasswordAttr($password){
        return '';
    }
}