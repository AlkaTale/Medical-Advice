<?php

namespace app\api\model;
use think\Model;

//用户模型类
class User extends Model{

    public function user_profiles(){
        //用户和用户资料的一对多关系
        return $this->hasMany('UserProfile');
    }

    public function doctor_profile(){
        //医生和医生资料的一对一关系
        return $this->hasOne('DoctorProfile');
    }

    public function user_type(){
        return $this->hasOne('UserType');
    }

    //phone读取器
    protected function getPhoneAttr($phone){
        return substr_replace($phone, '******', 3, 6);
    }
}