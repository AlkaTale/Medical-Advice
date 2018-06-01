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
use app\api\controller\User as UserController;
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
     * 通过姓名查询
     * 医生信息公开，任何人都能查询，无需登录
     * 接口地址：api/Doctorprofile/name
     * 参数：name
     */
    public function name(Request $request){
        $name = $request->param()['name'];

        $data =  Db::view('doctor_profile','id,name,department_id,introduction,type')
            ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
            ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
            ->where('doctor_profile.name','like','%'.$name.'%')
            ->select();
        if ($data)
            return json(['succ' => 1,'data' => $data]);
        else
            return json(['succ' => 0,'error' => '医生不存在']);
    }
    /*
     * 通过token查询
     * 接口地址：api/Doctorprofile/tokenquery
     * 参数：token
     */
    public function tokenquery(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $result =  Db::view('doctor_profile','id,name,department_id,introduction,photo,type,live_link')
                    ->view('doctor_type',['type'=>'typename','price'],'doctor_profile.type = doctor_type.id')
                    ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                    ->where('doctor_profile.id','=',$doctor['id'])
                    ->find();
                return json(['succ' => 1, 'data' => $result]);
            }else
                return json(['succ' => 0, 'error' => '医生不存在']);

        } else {
            return json(['succ' => 0, 'error' => '登录已失效']);
        }
    }
    /*
    * 手动创建医生账号和资料（user和doctor_profile表）
    * 需要管理员权限
    * 接口地址：api/Doctorprofile/create
    * 参数：token,(nickname,password,phone, name,deid,type,introduction)
    */
    public function create(Request $request){
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $data_profile = new DoctorProfileModel();

        //组装医生账号数据
        $data['token'] = '';
        $data['type_id'] = 2;
        $data['create_time'] = date("Y-m-d H:i:s");

        //检查账号是否符合要求
        $user = new UserController();
        $valid_result = $user->validate_reg($data);
        if(true !== $valid_result){
            return json(['succ' => 0,'error' => $valid_result]);
        }
        $user = User::create($data);

        if($user){
            //组装医生资料数据
            $data_profile->name = $data['name'];
            $data_profile->department_id = $data['deid'];
            $data_profile->type = $data['type'];
            $data_profile->introduction = $data['introduction'];
            $data_profile->update_time = date("Y-m-d");
            $user->doctor_profile()->save($data_profile);
            return json(['succ' => 1]);
        }
        else
            return json(['succ' => 0,'error' => '新建账号失败']);
    }
    /*
     * todo:删除医生资料应由管理员操作
     * 医生删除（doctor_profile表）
     * 接口地址：api/Doctorprofile
     * 参数：token, doctor_profile_id
     */
    public function delete(Request $request)
    {
        //$data = $request->param();



    }

    /*
     * 医生改（doctor_profile表）
     * 医生本人只能修改个人介绍
     * 管理员修改医生信息
     * 接口地址：api/Doctorprofile/update
     * 参数：token，introduction
     */
    public function update(Request $request)
    {
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            //管理员操作
            //附加参数：doctor_id.name,department_id.type,introduction
            if($user->msg->type_id == 3){
                $doctor = DoctorProfileModel::get(['id' => $data['doctor_id']]);
                if ($doctor) {
                    $doctor->name = $data['name'];
                    $doctor->department_id = $data['department_id'];
                    $doctor->type = $data['type'];
                    $doctor->introduction = $data['introduction'];
                    $doctor->update_time = date("Y-m-d");
                    if (false != $doctor->save())
                        return json(['succ' => 1, 'msg' => '修改成功']);
                    else
                        return json(['succ' => 0, 'msg' => '修改失败']);
                }else
                    return json(['succ' => 0, 'msg' => '医生不存在']);
            }
            else{
                $doctor = $user->msg->doctor_profile()->find();
                if ($doctor) {
                    $doctor->introduction = $data['introduction'];
                    $doctor->update_time = date("Y-m-d");
                    if (false != $doctor->save())
                        return json(['succ' => 1, 'msg' => '修改成功']);
                    else
                        return json(['succ' => 0, 'msg' => '修改失败']);
                }else
                    return json(['succ' => 0, 'msg' => '医生不存在']);
            }
        } else {
            return json(['succ' => 0, 'msg' => '登录已失效']);
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


    /*
    * 单个医生半天排班查询（管理员用）
    * 接口地址：api/Doctorprofile/dayduty
    * 参数：doctor_id、day、flag(上/下午)
    */
    public function dayduty(Request $request)
    {
        $r_data = $request->param();
        $d_id = $r_data['doctor_id'];
        $flag = $r_data['flag'];
        $day = $r_data['day'];

        $time_list = Db::view('schedule','id,doctor_id,day,time_range_id,number,status,max_count')
            //注：添加两个多余的text属性，方便前端存储经过文字处理后的星期几和排班状态
            ->view('time_range',['range','flag','flag'=>'text_day','range'=>'text_status'],'schedule.time_range_id = time_range.id')
            ->where([
                'doctor_id' => ['=',$d_id],
                'day' => ['=',$day],
                'flag' => ['=',$flag],
            ])
            ->select();

        return json(['succ' => 1, 'data' => $time_list]);
    }


    /*
    * 添加排班（管理员用）
     * todo:排班时间段冲突检查
    * 接口地址：api/Doctorprofile/addduty
    * 参数：token、doctor_id、day、range、max_count
    */
    public function addduty(Request $request)
    {
        $r_data = $request->param();

        $admin = Util::admin_validate($r_data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $d_id = $r_data['doctor_id'];
        $range = $r_data['range'];
        $day = $r_data['day'];
        $max = $r_data['max_count'];

        $result = Db::name('schedule')
            ->insert(['doctor_id' => $d_id, 'day' => $day, 'time_range_id' => $range, 'max_count' => $max, 'number' => $max]);

        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '添加失败']);
    }

    /*
    * 编辑排班（管理员用）
     * 仅能修改最大数量和是否有效
    * 接口地址：api/Doctorprofile/editduty
    * 参数：token、sid、max_count、status
    */
    public function editduty(Request $request)
    {
        $r_data = $request->param();

        $admin = Util::admin_validate($r_data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $sid = $r_data['sid'];
        $status = $r_data['status'];
        $max = $r_data['max_count'];

        $result = Db::name('schedule')
            ->where([
                'id' => ['=', $sid],
            ])
            ->update(['status' => $status,'max_count' => $max]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '修改失败']);
    }
}