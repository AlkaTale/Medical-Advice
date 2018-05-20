<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/17
 * Time: 21:11
 */

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Evaluation extends Controller
{
    /*
     * 查询评价列表
     * 1、查询医生所有评价（列表）
     * 2、查询订单评价（单个）
     * 接口：api/Evaluation
     * 参数：did（可选）, oid
     */
    public function index(Request $request){
        $data = $request->param();
        

        $oid = $data['oid'];

        if($oid == 0){
            $did = $data['did'];
            $results =  Db::view('evaluation','id,oid,score,report,comment,create_time')
                ->view('order',['id'],'order.id = evaluation.oid')
                ->where([
                    'order.doctor_id' => ['=',$did],
                ])
                ->select();
            return json(['succ' => 1, 'data' => $results]);
        }
        else{
            $result =  Db::view('evaluation','id,oid,score,report,comment,create_time')
                ->where([
                    'oid' => ['=',$oid],
                ])
                ->find();
            return json(['succ' => 1, 'data' => $result]);
        }

    }

    /*
    * 新增评价
    * 接口地址：api/Evaluation/create
    * 参数：token,profile_id,oid,score,comment,report
    */
    public function create(Request $request)
    {
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $order = Db::view('order','id')
                ->where([
                    'id' => ['=',$data['oid']],
                    'profile_id' => ['=',$data['profile_id']],
                    'status' => ['=', '4'], //todo:待评价
                ])
                ->find();
            if (false != $order){
                $result = Db::name('evaluation')
                    ->insert(['oid' => $order['id'],
                        'score' => $data['score'],
                        'comment' => $data['comment'],
                        'report' => $data['report'],
                        'create_time' => date(date("Y-m-d H:i:s"))
                    ]);
                if(false != $result){
                    Db::name('order')
                        ->where([
                            'id' => ['=', $order['id']],
                        ])
                        ->update(['status' => '5']); //todo:已完成
                    return json(['succ' => 1]);
                }
                else
                    return json(['succ' => 0, 'error' => '评价失败']);

            }
            else
                return json(['succ' => 0, 'error' => '订单不存在']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }
}