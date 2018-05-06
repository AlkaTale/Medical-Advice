<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/02/01
 * Time: 2:22
 */
namespace app\api\controller;

use app\api\model\Order as OrderModel;
use app\api\model\OrderMrecord;
use think\Controller;
use think\Db;
use think\Request;


class Order extends Controller{

    /*
     * 新增订单
     * 接口地址：api/order/create
     * 参数：token,profile_id...
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
     * 参数：token,profile_id...
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
                $order = Db::view('order','id,appointment_date,disease_input,price,record,advice,create_time')
                    ->view('user_profile',['name' => 'username'],'user_profile.id = order.profile_id')
                    ->view('doctor_profile',['name' => 'doctorname'],'doctor_profile.id = order.doctor_id')
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
                return json(['succ' => 1 ,'data' => $order]);
            }
            else{
                $order = Db::view('order','id,appointment_date,disease_input,price,record,advice,create_time')
                    ->view('user_profile',['name' => 'username'],'user_profile.id = order.profile_id')
                    ->view('doctor_profile',['name' => 'doctorname'],'doctor_profile.id = order.doctor_id')
                    ->view('doctor_type',['type'=>'typename'],'doctor_profile.type = doctor_type.id')
                    ->view('schedule',['time_range_id'],'schedule.id = order.appointment_time')
                    ->view('time_range',['range'],'time_range.id = schedule.time_range_id')
                    ->view('order_status',['status'],'order_status.id = order.status')
                    ->view('department',['name'=>'department'],'department.id = doctor_profile.department_id')
                    ->where([
                        'order.profile_id' => ['=',$data['profile_id']]
                    ])
                    ->select();
                return json(['succ' => 1 ,'data' => $order]);
            }
        }
        else {
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