<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/17
 * Time: 21:11
 */

namespace app\api\controller;

use app\api\model\User;
use think\Controller;
use think\Paginator;
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
     * 管理员查询投诉列表
     * 接口：api/Evaluation/complaint
     * 参数：token,name(患者/医生姓名)
     */
    public function complaint(Request $request){
        $data = $request->param();
        $name = $data['name'];

        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $results =  Db::view('evaluation','id,oid,score,report,comment,create_time')
            ->view('order',[],'order.id = evaluation.oid')
            ->view('user_profile',['name' => 'patient'],'order.profile_id = user_profile.id')
            ->view('doctor_profile',['name' => 'doctor'],'order.doctor_id = doctor_profile.id')
            ->where('evaluation.report','=',1)
            ->where(function ($query) use ($name) {
                $query->where('user_profile.name', 'like', '%'.$name.'%')->whereOr('doctor_profile.name', 'like', '%'.$name.'%');
            })
            ->select();
        return json(['succ' => 1, 'data' => $results]);
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

    /*
     * 管理员查询投诉回复
     * 接口：api/Evaluation/admin_replylist
     * 参数：token,eid
     */
    public function admin_replylist(Request $request){
        $data = $request->param();

        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $results =  Db::view('evaluation_reply','id,eid,replier,reply_type,reply,reply_time')
            ->view('user_type',['type'],'user_type.id = evaluation_reply.reply_type')
            ->where('eid','=',$data['eid'])
            ->order('reply_time')
            ->select();
        return json(['succ' => 1, 'data' => $results]);
    }

    /*
     * 管理员回复投诉
     * 接口：api/Evaluation/admin_reply
     * 参数：token,eid,reply
     */
    public function admin_reply(Request $request){
        $data = $request->param();

        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);
        $user = $admin->msg;
        $result =  $result = Db::name('evaluation_reply')
            ->insert(['eid' => $data['eid'],
                'replier' => $user['id'],
                'reply_type' => $user['type_id'],
                'reply' => $data['reply'],
                'reply_time' => date(date("Y-m-d H:i:s"))
            ]);
        if($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0, 'error' => '回复失败']);
    }

    /*
     * 医生查询回复
     * 接口：api/Evaluation/doctor_replylist
     * 参数：token,oid
     */
    public function doctor_replylist(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor_id = $doctor['id'];

                //验证是否有权限查看订单
                $order = Db::name('order')
                    ->where([
                        'order.id' => ['=',$data['oid']],
                        'order.doctor_id' => ['=',$doctor_id]
                    ])
                    ->find();
                if($order){
                    //查询对应的评价和评价回复
                    $evaluation = Db::name('evaluation')
                        ->where([
                            'oid' => ['=',$data['oid']]
                        ])
                        ->find();
                    if ($evaluation){
                        $eid = $evaluation['id'];
                        $results =  Db::view('evaluation_reply','id,eid,replier,reply_type,reply,reply_time')
                            ->view('user_type',['type'],'user_type.id = evaluation_reply.reply_type')
                            ->where('eid','=',$eid)
                            ->order('reply_time')
                            ->select();
                        return json(['succ' => 1, 'data' => $results]);
                    }
                    else
                        return json(['succ' => 0, 'error' => '订单未评价']);
                }
                else
                    return json(['succ' => 0, 'error' => '订单不存在或没有权限查看']);

            }else
                return json(['succ' => 0, 'error' => '医生不存在']);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }

    /*
     * 医生回复评价
     * 接口：api/Evaluation/doctor_reply
     * 参数：token,oid,reply
     */
    public function doctor_reply(Request $request){
        $data = $request->param();
        $user = Util::token_validate($data['token']);
        //验证token
        if ($user->succ) {
            $doctor = $user->msg->doctor_profile()->find();
            if ($doctor) {
                $doctor_id = $doctor['id'];

                //验证是否有权限查看订单
                $order = Db::name('order')
                    ->where([
                        'order.id' => ['=',$data['oid']],
                        'order.doctor_id' => ['=',$doctor_id]
                    ])
                    ->find();
                if($order){
                    //查询对应的评价和评价回复
                    $evaluation = Db::name('evaluation')
                        ->where([
                            'oid' => ['=',$data['oid']]
                        ])
                        ->find();
                    if ($evaluation){
                        $eid = $evaluation['id'];
                        $result =  $result = Db::name('evaluation_reply')
                            ->insert(['eid' => $eid,
                                'replier' => $user->msg['id'],
                                'reply_type' => $user->msg['type_id'],
                                'reply' => $data['reply'],
                                'reply_time' => date(date("Y-m-d H:i:s"))
                            ]);
                        if($result)
                            return json(['succ' => 1]);
                        else
                            return json(['succ' => 0, 'error' => '回复失败']);
                    }
                    else
                        return json(['succ' => 0, 'error' => '订单未评价']);
                }
                else
                    return json(['succ' => 0, 'error' => '订单不存在或没有权限查看']);

            }else
                return json(['succ' => 0, 'error' => '医生不存在']);
        }
        else
            return json(['succ' => 0, 'error' => '登录已失效']);
    }
}