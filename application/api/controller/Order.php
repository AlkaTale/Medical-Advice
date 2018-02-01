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
                foreach ($data['record_id'] as $item){
                    $o_data = [];
                    $o_data['order_id'] = $result['id'];
                    $o_data['record_id'] = $item;
                    OrderMrecord::create($o_data);
                }
                return json(['succ' => 1 ,'data' => $result]);
            }
            else
                return json(['succ' => 0, 'error' => '预约失败']);
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