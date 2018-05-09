<?php
/**
 * Created by PhpStorm.
 * User: qinhao
 * Date: 2017/12/25
 * Time: 14:33
 */

namespace app\api\controller;

use app\api\model\DoctorProfile as DoctorProfileModel;
use app\api\model\User;
use think\Controller;
use think\Request;
use think\Db;

class Doctorprofile extends Controller
{
    /*
     * 查询
     * 医生信息公开，任何人都能查询，无需登录
     * 接口地址：api/Doctorprofile
     * 参数：profile_id
     */
    public function index(Request $request){
        $did = $request->param()['profile_id'];

        $data = DoctorProfileModel::get(['id' => $did]);
        if ($data)
            return json(['succ' => 1,'data' => $data]);
        else
            return json(['succ' => 0,'error' => '医生不存在']);
    }
    /*
    * 医生增加（doctor_profile表）
    * 接口地址：api/Doctorprofile
    * 参数：token,id，name,sex，age,history,
    */
    //todo:增加医生资料应由管理员批量导入
    public function create(Request $request){

    }
    /*
     * todo:删除医生资料应由管理员操作
     * 医生删除（doctor_profile表）
     * 接口地址：api/Doctorprofile
     * 参数：token, doctor_profile_id
     */
    public function delete(Request $request)
    {
        $data = $request->param();

    }

    /*
     * 医生改（doctor_profile表）
     * 医生本人只能修改个人介绍
     * todo:管理员修改医生信息
     * 接口地址：api/Doctorprofile/update
     * 参数：token，introduction
     */
    public function update(Request $request)
    {
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor->introduction = $data['introduction'];
                $doctor->update_time = date("Y-m-d");
                $doctor->save();
                return json(['succ' => 1]);
            }else
                return json(['succ' => 0, 'error' => '医生不存在']);

        } else {
            return json(['succ' => 0, 'error' => '登录已失效']);
        }

    }

    /*
    * 医生列表和排班查询
    * 接口地址：api/Doctorprofile/dutylist
    * 参数：department_id(为0则全部选择)
    */
    public function dutylist(Request $request)
    {
        $result = [];
        $r_data = $request->param();
        $d_id = $r_data['department_id'];
        $page = $r_data['page'];
        if($d_id == 0){
            $dp_list =  Db::view('doctor_profile','id,name,department_id,introduction,photo,type')
                ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
                ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                ->page($page)
                ->limit(10)
                ->select();
            $count = Db::view('doctor_profile','id,name,department_id,introduction,photo,type')->count();
        }
        else{
            $dp_list =  Db::view('doctor_profile','id,name,department_id,introduction,photo,type')
                ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
                ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                ->where('department_id','=',$d_id)
                ->page($page)
                ->limit(10)
                ->select();
            $count = Db::view('doctor_profile','id,name,department_id,introduction,photo,type')
                    ->where('department_id','=',$d_id)
                    ->count();
        }
        foreach ($dp_list as $dp){
            $time_list = Db::view('schedule','id,doctor_id,day,number')
                ->view('time_range',['range','flag'],'schedule.time_range_id = time_range.id')
                ->where([
                    'doctor_id' => ['=',$dp['id']],
                    'schedule.status' => ['=',1]
                ])
                ->select();
            $dp['time_list'] = $time_list;
            $result[] = $dp;
        }
        return json(['count' => $count/10 + 1, 'data' => $result]);
    }

    /*
    * 单个医生一周排班查询
    * 接口地址：api/Doctorprofile/weekduty
    * 参数：doctor_id
    */
    public function weekduty(Request $request)
    {
        $r_data = $request->param();
        $d_id = $r_data['doctor_id'];
        $dp =  Db::view('doctor_profile','id,name,department_id,introduction,photo,type')
                ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
                ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                ->where('doctor_profile.id','=',$d_id)
                ->find();



        $time_list = Db::view('schedule','id,doctor_id,day,number')
                ->view('time_range',['range','flag'],'schedule.time_range_id = time_range.id')
                ->where([
                    'doctor_id' => ['=',$d_id],
                    'schedule.status' => ['=',1]
                ])
                ->select();
        $dp['time_list'] = $time_list;
        $result = $dp;

        return json(['data' => $result]);
    }

    /*
    * 单个医生单日排班查询（用于提交订单时选择时间段）
    * 接口地址：api/Doctorprofile/doctorduty
    * 参数：doctor_id、day、flag(上/下午)
    */
    public function doctorduty(Request $request)
    {
        $r_data = $request->param();
        $d_id = $r_data['doctor_id'];
        $flag = $r_data['flag'];
        $day = $r_data['day'];
        $dp =  Db::view('doctor_profile','id,name,department_id,introduction,photo,type')
            ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
            ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
            ->where('doctor_profile.id','=',$d_id)
            ->find();

        $time_list = Db::view('schedule','id,doctor_id,day,number')
            ->view('time_range',['range','flag'],'schedule.time_range_id = time_range.id')
            ->where([
                'doctor_id' => ['=',$d_id],
                'schedule.status' => ['=',1],
                'day' => ['=',$day],
                'flag' => ['=',$flag],
                'number' => ['>', 0]
            ])
            ->select();
        $dp['time_list'] = $time_list;

        return json($dp);
    }
}