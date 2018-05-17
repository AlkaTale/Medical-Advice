<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/02/01
 * Time: 2:18
 */

namespace app\api\model;
use think\Model;
use think\Db;

//病历图片模型类
class Order extends Model{

    protected $field = [
        'profile_id','doctor_id','appointment_date','appointment_time',
        'price','create_time','disease_input','code'
    ];
}