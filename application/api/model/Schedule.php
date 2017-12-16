<?php

namespace app\api\model;
use think\Model;


class Schedule extends Model{

    public function doctor_profile()
    {
        return $this->belongsTo('DoctorProfile');
    }

}