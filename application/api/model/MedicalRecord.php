<?php

namespace app\api\model;
use think\Model;

//用户病历模型类
class MedicalRecord extends Model{

    protected $field = [
        'visit_time','hospital','description','profile_id','create_time','type'
    ];

    public function user_profile()
    {
        return $this->belongsTo('UserProfile');
    }

    public function record_images(){
        return $this->hasMany('RecordImage','record_id');
    }
}