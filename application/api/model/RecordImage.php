<?php

namespace app\api\model;
use think\Model;

//病历图片模型类
class RecordImage extends Model{

    protected $field = [
        'link','type_id','create_time','record_id'
    ];

    public function medical_record()
    {
        return $this->belongsTo('MedicalRecord');
    }
}