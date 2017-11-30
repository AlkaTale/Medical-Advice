<?php

namespace app\api\model;
use think\Model;

//用户类型模型类
class UserType extends Model{

    public function user()
    {
        return $this->belongsTo('User');
    }
}