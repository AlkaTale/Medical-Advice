<?php

namespace app\api\model;
use think\Model;
use think\Db;

//病历图片模型类
class RecordImage extends Model{

    protected $field = [
        'link','type_id','create_time','record_id'
    ];

    public function medical_record()
    {
        return $this->belongsTo('MedicalRecord');
    }

    public function getTypeIdAttr($type_id)
    {
        return Db::name('record_type')->where('id','=',$type_id)->value('type');
    }
}