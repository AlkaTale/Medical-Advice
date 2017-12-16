<?php

namespace app\api\model;
use think\Model;

//医生资料模型类
class DoctorProfile extends Model{

    public function user()
    {
        return $this->belongsTo('User');
    }
    public function schedule()
    {
        return $this->hasMany('Schedule');
    }
}