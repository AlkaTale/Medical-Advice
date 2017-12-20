<?php

namespace app\api\model;
use think\Model;

//用户资料模型类
class UserProfile extends Model{

    protected $field = [
        'name','age','sex','history','create_time'
    ];

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function medical_records()
    {
        return $this->hasMany('MedicalRecord','profile_id');
    }
}