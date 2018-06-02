<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/02/01
 * Time: 2:22
 */
namespace app\api\controller;

use app\api\model\MedicalRecord as MedicalRecordModel;
use app\api\model\DoctorProfile as DoctorProfileModel;
use app\api\model\Order as OrderModel;
use app\api\model\OrderMrecord;
use think\Controller;
use think\Db;
use think\Request;


class Order extends Controller{

    /*
     * 新增订单
     * 接口地址：api/order/create
     * 参数：token,profile_id,appointment_time
     */
    public function create(Request $request){
        $data = $request->param();
        $data['create_time'] = date("Y-m-d H:i:s");

        $s_id = $data['appointment_time'];

        //检查人数是否已满
        $s_result = Db::name('schedule')->where('id','=',$s_id)->find();
        if ($s_result['number'] <= 0 || $s_result['status'] != 1){
            return json(['succ' => 0, 'error' => '预约失败，该时间段人数已满']);
        }
        $data['doctor_id'] = $s_result['doctor_id'];
        $data['appointment_date'] = $this->getDate($s_result['day']);
        //查询价格
        $p_result = Db::view('doctor_profile','id')
            ->view('doctor_type','price','doctor_profile.type = doctor_type.id')
            ->where('doctor_profile.id','=',$s_result['doctor_id'])
            ->find();
        $data['price'] = $p_result['price'];

        //生成八位预约密码
        $data['code'] = rand('10000000','99999999');

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $result = OrderModel::create($data);
            if($result){
                //病历
                try{
                    foreach ($data['record_id'] as $item){
                        $o_data = [];
                        $o_data['order_id'] = $result['id'];
                        $o_data['record_id'] = $item;
                        OrderMrecord::create($o_data);
                    }
                }catch (\Exception $e){
                    //todo:未提交病历
                }

                return json(['succ' => 1 ,'data' => $result['id']]);
            }
            else
                return json(['succ' => 0, 'error' => '预约失败']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
     * 查询订单
     * 接口地址：api/order/
     * 参数：token,profile_id,order_id
     */
    public function index(Request $request){
        $data = $request->param();

        try{
            $o_id = $data['order_id'];
        }catch (\Exception $e){
            $o_id = 0;
        }

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            if($o_id > 0){
                $order = Db::view('order','id,profile_id,appointment_date,disease_input,price,advice,create_time,code')
                    ->view('user_profile',['name' => 'username'],'user_profile.id = order.profile_id')
                    ->view('doctor_profile',['name' => 'doctorname','live_link'],'doctor_profile.id = order.doctor_id')
                    ->view('schedule',['time_range_id'],'schedule.id = order.appointment_time')
                    ->view('doctor_type',['type'=>'typename'],'doctor_profile.type = doctor_type.id')
                    ->view('time_range',['range'],'time_range.id = schedule.time_range_id')
                    ->view('order_status',['status'],'order_status.id = order.status')
                    ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                    ->where([
                        'order.id' => ['=',$o_id],
                        'order.profile_id' => ['=',$data['profile_id']]
                    ])
                    ->find();

                $cases = Db::view('medical_record','id,visit_time,hospital,description,profile_id')
                    ->view('order_mrecord',['order_id'],'order_mrecord.record_id = medical_record.id')
                    ->where('order_mrecord.order_id','=',$order['id'])
                    ->select();
                $order['cases'] = $cases;
                return json(['succ' => 1 ,'data' => $order]);
            }
            else{
                $order = Db::view('order','id,profile_id,appointment_date,disease_input,price,advice,create_time,code')
                    ->view('user_profile',['name' => 'username'],'user_profile.id = order.profile_id')
                    ->view('doctor_profile',['name' => 'doctorname'],'doctor_profile.id = order.doctor_id')
                    ->view('doctor_type',['type'=>'typename'],'doctor_profile.type = doctor_type.id')
                    ->view('schedule',['time_range_id'],'schedule.id = order.appointment_time')
                    ->view('time_range',['range'],'time_range.id = schedule.time_range_id')
                    ->view('order_status',['status'],'order_status.id = order.status')
                    ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                    ->where([
                        'order.profile_id' => ['=',$data['profile_id']],
                        'order.status' => ['>',-1]
                    ])
                    ->order('create_time','desc')
                    ->select();

                return json(['succ' => 1 ,'data' => $order]);
            }
        }
        else {
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }
    /*
     * 取消订单
     * 接口地址：api/order/cancel
     * 参数：token,profile_id,order_id
     */

    public function cancel(Request $request)
    {
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $result = Db::name('order')
                ->where([
                    'id' => ['=', $data['order_id']],
                    'profile_id' => ['=', $data['profile_id']],
                    'status' => ['=','0']               //todo:待付款
                ])
                ->update(['status' => '6']); //todo:已取消
            if(false!=$result)
                return json(['succ' => 1]);
            else
                return json(['succ' => 0, 'error' => '取消失败']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }


    /*
    * 删除订单
    * 接口地址：api/order/delete
    * 参数：token,profile_id,order_id
    */
    public function delete(Request $request)
    {
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $result = Db::name('order')
                ->where([
                    'id' => ['=', $data['order_id']],
                    'profile_id' => ['=', $data['profile_id']],
                    'status' => ['=','6']               //todo:已取消
                ])
                ->update(['status' => '-1']);
            if(false!=$result)
                return json(['succ' => 1]);
            else
                return json(['succ' => 0, 'error' => '删除失败']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
    * 生成线上咨询记录（病历）
    * 接口地址：api/order/createcase
    * 参数：token,profile_id,order_id
    */
    public function createcase(Request $request)
    {
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $order = Db::name('order')
                ->where([
                    'id' => ['=', $data['order_id']],
                    'profile_id' => ['=', $data['profile_id']],
                    'case_flag' => ['=', 0],
                    'status' => [['=',5],['=',4],'or']               //todo:待评价/已完成
                ])
                ->find();
            $doctor = DoctorProfileModel::get(['id' => $order['doctor_id']]);

            if($order){
                $case = [];
                $case['profile_id'] = $order['profile_id'];
                $case['type'] = 2;
                $case['visit_time'] = $order['appointment_date'];
                $case['hospital'] = '【线上咨询】医生：'.$doctor['name'];
                $case['description'] = '患者描述：'.$order['disease_input'].'；医生建议：'.$order['advice'];
                $case['create_time'] = date("Y-m-d H:i:s");

                $result = MedicalRecordModel::create($case);
                if ($result){
                    Db::name('order')
                        ->where([
                            'id' => ['=', $data['order_id']],
                        ])
                        ->update(['case_flag' => 1]);;
                    return json(['succ' => 1]);
                }

                else
                    return json(['succ' => 0, 'error' => '生成记录失败']);
            }
            else
                return json(['succ' => 0, 'error' => '订单不存在或记录已生成']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    public function getDate($day){
        $length = $day - date("w");
        if ($length <= 0)
            $length += 7;
        return date("Y-m-d",strtotime("+" . $length . " day"));
    }
}