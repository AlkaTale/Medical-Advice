<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/02/01
 * Time: 15:21
 */
namespace app\api\model;
use think\Model;
use think\Db;

//病历图片模型类
class OrderMrecord extends Model{

    protected $field = [
        'order_id','record_id'
    ];
}