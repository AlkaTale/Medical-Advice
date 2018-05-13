<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/06
 * Time: 23:04
 */

namespace app\api\controller;

use app\api\model\MedicalRecord;
use app\api\model\User;
use think\Controller;
use think\Request;
use think\Db;

class Doctororders extends Controller
{
    /*
     * 查询订单列表
     * 1、查询历史订单（列表, flag = 1）
     * 2、查询已付款未咨询的订单（按日期: flag = 0）
     * 3、查询待给出咨询意见的订单（列表，flag = 2）
     * 接口：api/Doctororders
     * 参数：token, flag, date(可选)
     */
    public function index(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor_id = $doctor['id'];

                //flag = 1, 查询成功完成的订单
                //todo:分页
                if($data['flag'] == 1){
                    $orders =  Db::view('order','id,profile_id,appointment_date,price')
                        ->view('user_profile',['name','sex','birth'],'order.profile_id = user_profile.id')
                        ->view('order_status',['status'],'order_status.id = order.status')
                        ->where([
                            'order.doctor_id' => ['=',$doctor_id],
                            'order_status.status' => ['=','已完成']
                        ])
                        ->select();
                    return json(['succ' => 1, 'data' => $orders]);
                }

                //flag = 2, 查询待给出咨询意见的订单
                //todo:分页
                elseif ($data['flag'] == 2){
                    $orders =  Db::view('order','id,profile_id,appointment_date,price')
                        ->view('user_profile',['name','sex','birth'],'order.profile_id = user_profile.id')
                        ->view('order_status',['status'],'order_status.id = order.status')
                        ->where([
                            'order.doctor_id' => ['=',$doctor_id],
                            'order_status.status' => ['=','咨询中']
                        ])
                        ->select();
                    return json(['succ' => 1, 'data' => $orders]);
                }

                //flag = 0, 按日期查询已付款未咨询的订单
                elseif ($data['flag'] == 0){
                    $orders =  Db::view('order','id,profile_id,appointment_date,price')
                        ->view('user_profile',['name','sex','birth'],'order.profile_id = user_profile.id')
                        ->view('order_status',['status'],'order_status.id = order.status')
                        ->where([
                            'order.doctor_id' => ['=',$doctor_id],
                            'order_status.status' => ['=','已支付'],
                            'order.appointment_date' => ['=',$data['date']]
                        ])
                        ->select();
                    return json(['succ' => 1, 'data' => $orders]);
                }

                else
                    return json(['succ' => 0, 'error' => '参数错误']);

            }else
                return json(['succ' => 0, 'error' => '医生不存在']);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }

    /*
     * 查询订单详情
     * 接口：api/Doctororders/detail
     * 参数：token, oid
     */
    public function detail(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor_id = $doctor['id'];

                $order = Db::view('order','id,appointment_date,disease_input,price,advice,create_time')
                    ->view('user_profile',['name' => 'username', 'sex','birth'],'user_profile.id = order.profile_id')
                    ->view('schedule',['time_range_id'],'schedule.id = order.appointment_time')
                    ->view('time_range',['range'],'time_range.id = schedule.time_range_id')
                    ->view('order_status',['status'],'order_status.id = order.status')
                    ->where([
                        'order.id' => ['=',$data['oid']],
                        'order.doctor_id' => ['=',$doctor_id]
                    ])
                    ->find();

                $cases = Db::view('medical_record','id,visit_time,hospital,description')
                    ->view('order_mrecord',['order_id'],'order_mrecord.record_id = medical_record.id')
                    ->where('order_mrecord.order_id','=',$order['id'])
                    ->select();
                $order['cases'] = $cases;
                return json(['succ' => 1, 'data' => $order]);
            }else
                return json(['succ' => 0, 'error' => '医生不存在']);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }

    /*
     * 查询订单病历详情
     * 接口：api/Doctororders/casedetail
     * 参数：token, oid, cid
     */
    public function casedetail(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor_id = $doctor['id'];

                $cid = Db::view('order_mrecord','record_id')
                    ->view('order',[],'order.id = order_mrecord.order_id')
                    ->where([
                        'order_mrecord.record_id' => ['=',$data['cid']],
                        'order.doctor_id' => ['=',$doctor_id]
                    ])
                    ->find();
                if ($cid){
                    $case = MedicalRecord::get(['id' => $cid['record_id']]);
                    try{
                        $images = $case->record_images()->selectOrFail();
                    }catch (\Exception $e){
                        $images = [];
                    }

                    $case['images'] = $images;

                    return json(['succ' => 1, 'data' => $case]);
                }
                else
                    return json(['succ' => 0, 'error' => '病历不存在']);

            }else
                return json(['succ' => 0, 'error' => '医生不存在']);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }


}