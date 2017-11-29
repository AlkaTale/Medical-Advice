<?php

namespace app\api\model;
use think\Model;

//用户模型类
class User extends Model{

    public function profile(){
        //用户和用户资料的一对一关系
        return $this->hasOne('Profile');
    }
}