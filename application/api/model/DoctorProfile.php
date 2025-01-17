<?php

namespace app\api\model;
use think\Model;

//医生资料模型类
class DoctorProfile extends Model{
    protected $field = [
        'name','department_id','introduction','update_time','photo','live_link','type','user_id'
    ];
    public function user()
    {
        return $this->belongsTo('User');
    }
    public function schedule()
    {
        return $this->hasMany('Schedule');
    }
}