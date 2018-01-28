<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/01/25
 * Time: 16:13
 */

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Department extends Controller{

    /*
     * 获取科室列表
     * 调用：api/department
     * 参数：
     */
    public function index(){
        $result = Db::name('department')->field('id,name')->select();
        return json($result);
    }
}