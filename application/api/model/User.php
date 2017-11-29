<?php

namespace application\api\model;
use think\model;

//用户模型类
class User extends model{
    public function profile(){
        //用户和用户资料的一对一关系
        return $this->hasOne('Profile');
    }
}