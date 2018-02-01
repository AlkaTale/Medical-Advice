<?php
//todo:此函数暂时废弃
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

    public $doctor_list;
    public $duty_time_list;

    /*
     * 参数：科室编号department_id
     */
//    public function index(Request $request)
//    {
//
//        //获取请求参数
//        $data = $request->param();
//        $department_id = $data['department_id'];
//
//        //初始化相关类
//        $this->doctor_list = array();
//        $this->duty_time_list = array();
//
//        //总值班时间段数量
//        $total_duty = 0;
//
//        //医生数量
//        $total_doctors = 0;
//
//        //读取医生空闲时间
//        $results = Db::view('doctor_profile','id,name,department_id')
//            ->view('schedule','duty_time_id','schedule.doctor_id=doctor_profile.id')
//            ->where(['status'=>0, 'department_id'=>$department_id])
//            ->select();
//
//        //读取所有值班时段
//        $duty_results = Db::name('duty_time')->where('department_id','=',$department_id)->select();
//
//        //初始化值班列表
//        foreach ($duty_results as $value){
//            $temp = new s_DutyTime();
//            $temp->duty_time_id = $value['id'];
//            $temp->min_count = $value['min_count'];
//            $this->duty_time_list[$value['id']] = $temp;
//            $total_duty += $value['min_count'];
//        }
//
//        //初始化医生列表
//        foreach ($results as $value){
//            $temp = new s_Doctor();
//            $temp->doctor_id = $value['id'];
//            $this->doctor_list[$value['id']] = $temp;
//            $this->duty_time_list[$value['duty_time_id']]->free_doctors[] = $value['id'];
//        }
//
//        //计算每人最少值班时间
//        $total_doctors = count($this->doctor_list);
//        $min_duty = ceil($total_duty/$total_doctors);
//
//        //统计医生空闲时间数量
//        foreach ($results as $value){
//            $this->doctor_list[$value['id']]->free_num++;
//        }
//
//        //外层循环：排班轮次
//        for($w = 0;$w < 3; $w++){
//            //排序，优先可用人数少的时段
//            usort($this->duty_time_list,array($this,'compare_duty'));
//            //遍历时间段
//            for($i = 0;$i < count($this->duty_time_list);$i++){
//                //若该时段已排满或无人值班，跳过
//                if($this->duty_time_list[$i]->min_count == 0 || count($this->duty_time_list[$i]->free_doctors) == 0)
//                    continue;
//                //否则对可用医生排序
//                usort($this->duty_time_list[$i]->free_doctors,array($this,'compare_free_num'));
//                //将排序最前且值班数量未满的加入值班列表
//                for ($j = 0; $j < count($this->duty_time_list[$i]->free_doctors); $j++){
//                    $first = $this->duty_time_list[$i]->free_doctors[$j];
//                    //若此人排班未满，则进行安排
//                    if($this->doctor_list[$first]->flag < $min_duty){
//                        //安排并记录已排次数
//                        $this->doctor_list[$first]->flag++;
//                        $this->duty_time_list[$i]->duty_doctors[] = $first;
//
//                        //更新此人剩余空闲时间和该时间段所需人数
//                        $this->doctor_list[$first]->free_num--;
//                        $this->duty_time_list[$i]->min_count--;
//
//                        //将此人从该时段的可用医生列表中删除
//                        $key = array_search($first ,$this->duty_time_list[$i]->free_doctors);
//                        array_splice($this->duty_time_list[$i]->free_doctors,$key,1);
//
//                        break;
//                    }
//                }
//
////                foreach ($this->duty_time_list as $item){
////                    if (in_array($first,$item->free_doctors)){
////                        $key = array_search($first ,$item->free_doctors);
////                        array_splice($item->free_doctors,$first,1);
////                    }
////                }
//            }
//        }
//        dump($min_duty);
//        dump($this->doctor_list);
//        dump($this->duty_time_list);
//    }

    //自定义排序函数
    function compare_free_num($a,$b){
        //按free_num升序排列
//        if($a->free_num > $b->free_num)
//            return 1;
        //将可用医生按空闲时间数量升序排列
        if($this->doctor_list[$a]->free_num > $this->doctor_list[$b]->free_num)
            return 1;
    }

    function compare_duty(s_DutyTime $a,s_DutyTime $b){
        //按可用人数升序
        if(count($a->free_doctors) > count($b->free_doctors))
            return 1;
    }

}
