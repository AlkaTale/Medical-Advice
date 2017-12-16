<?php
/**
 * 排班函数
 * User: zxy96
 * Date: 2017/12/014
 * Time: 21:23
 */
namespace app\api\controller;

use app\api\model\ErrMsg;
use app\api\model\Schedule as ScheduleModel;
use think\Controller;
use think\Request;
use think\Db;

class s_Doctor{
    public $doctor_id;
    public $free_num = 0;
    public $flag = 0;
}

class s_DutyTime{
    public $duty_time_id;
    public $min_count;
    public $free_doctors = array();
    public $duty_doctors = array();
}

class Schedule{
    /*
     * 参数：科室编号department_id
     */
    public function index(Request $request)
    {
        //获取请求参数
        $data = $request->param();
        $department_id = $data['department_id'];

        //初始化相关类
        $doctor_list = array();
        $duty_time_list = array();

        //总值班时间段数量
        $total_duty = 0;

        //医生数量
        $total_doctors = 0;

        //读取医生空闲时间
        $results = Db::view('doctor_profile','id,name,department_id')
            ->view('schedule','duty_time_id','schedule.doctor_id=doctor_profile.id')
            ->where(['status'=>0, 'department_id'=>$department_id])
            ->select();

        //读取所有值班时段
        $duty_results = Db::name('duty_time')->where('department_id','=',$department_id)->select();

        //初始化值班列表
        foreach ($duty_results as $value){
            $temp = new s_DutyTime();
            $temp->duty_time_id = $value['id'];
            $temp->min_count = $value['min_count'];
            $duty_time_list[$value['id']] = $temp;
            $total_duty += $value['min_count'];
        }

        //初始化医生列表
        foreach ($results as $value){
            $temp = new s_Doctor();
            $temp->doctor_id = $value['id'];
            $doctor_list[$value['id']] = $temp;
            $duty_time_list[$value['duty_time_id']]->free_doctors[] = $value['id'];
        }

        //计算每人最少值班时间
        $total_doctors = count($doctor_list);
        $min_duty = ceil($total_duty/$total_doctors);

        //统计医生空闲时间数量
        foreach ($results as $value){
            $doctor_list[$value['id']]->free_num++;
        }
        
        dump($doctor_list);

    }
}
