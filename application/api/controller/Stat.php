<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/06/29
 * Time: 12:26
 */
namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Stat extends Controller
{
    /*
    * 查询订单统计数据
    * 接口地址：api/stat/order
    * 参数：token,flag(0-单日-需提供参数date,1-近7天,2-近30天,3-按月)
    */
    public function order(Request $request){
        $data = $request->param();
        //管理员权限验证
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $flag = $data['flag'];

        if ($flag == 0){
            $date = $data['date'];
            $result = Db::name('stat_order_count')
                ->where('date', '=', $date)
                ->cache()
                ->find();
        }
        elseif ($flag == 1){
            $result = Db::name('stat_order_count')
                ->whereTime('date', '>=', date('Y-m-d', strtotime('-7 days')))
                ->order('date')
                ->cache()
                ->select();
        }
        elseif ($flag == 2){
            $result = Db::name('stat_order_count')
                ->whereTime('date', '>=', date('Y-m-d', strtotime('-30 days')))
                ->order('date')
                ->cache()
                ->select();
        }
        elseif ($flag == 3){
            $result = Db::name('stat_order_count')
                ->field('date_format(date, \'%Y-%m\') as date,SUM(new_orders) as new_orders,SUM(paid_orders) as paid_orders,
                SUM(cancel_orders) as cancel_orders,SUM(complaint_orders) as complaint_orders')
                ->group('date_format(date, \'%Y-%m\')')
                ->order('date_format(date, \'%Y-%m\')')
                ->cache()
                ->select();
        }
        else
            return json(['succ' => 0,'error' => '查询参数错误']);
        return json(['succ' => 1,'data' => $result]);
    }

    /*
    * 查询用户统计数据
    * 接口地址：api/stat/user
    * 参数：token,flag(0-单日-需提供参数date,1-近7天,2-近30天,3-按月)
    */
    public function user(Request $request){
        $data = $request->param();
        //管理员权限验证
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $flag = $data['flag'];

        if ($flag == 0){
            $date = $data['date'];
            $result = Db::name('stat_user_count')
                ->where('date', '=', $date)
                ->find();
        }
        elseif ($flag == 1){
            $result = Db::name('stat_user_count')
                ->whereTime('date', '>=', date('Y-m-d', strtotime('-7 days')))
                ->order('date')
                ->select();
        }
        elseif ($flag == 2){
            $result = Db::name('stat_user_count')
                ->whereTime('date', '>=', date('Y-m-d', strtotime('-30 days')))
                ->order('date')
                ->select();
        }
        elseif ($flag == 3){
            $result = Db::name('stat_user_count')
                ->field('date_format(date, \'%Y-%m\') as date,SUM(new_user) as new_user,SUM(new_patient) as new_patient,
                SUM(new_case) as new_case,MAX(all_user) as all_user')
                ->group('date_format(date, \'%Y-%m\')')
                ->order('date_format(date, \'%Y-%m\')')
                ->select();
        }
        else
            return json(['succ' => 0,'error' => '查询参数错误']);
        return json(['succ' => 1,'data' => $result]);
    }

    /*
    * 查询医生统计数据
    * 接口地址：api/stat/doctor
    * 参数：token,did(0-查询排行,大于0-查询具体医生数据),type
    */
    public function doctor(Request $request){
        $data = $request->param();
        //管理员权限验证
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $did = $data['did'];

        if ($did == 0){
            $month = Util::GetForwardMonth();
            $result = Db::view('stat_doctor','id,new_orders,complete_orders,report_orders,score,doctor_id')
                ->view('doctor_profile','name','doctor_profile.id = stat_doctor.doctor_id')
                ->where([
                    'date_format(stat_doctor.date, \'%Y-%m\')' => ['=', $month]
                ])
                ->limit(20)
                ->order($data['type'],'desc')
                ->cache()
                ->select();
        }
        elseif ($did > 0){
            $result = Db::name('stat_doctor')
                ->where('doctor_id', '=', $did)
                ->order('date')
                ->select();
        }
        else
            return json(['succ' => 0,'error' => '查询参数错误']);
        return json(['succ' => 1,'data' => $result]);
    }

    /*
   * 查询科室统计数据
   * 接口地址：api/stat/dep
   * 参数：token,date
   */
    public function dep(Request $request){
        $data = $request->param();
        //管理员权限验证
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $date = $data['date'];

        $result = Db::view('stat_department','id,new_orders,complete_orders,report_orders,department_id')
            ->view('department','name','department.id = stat_department.department_id')
            ->where([
                'date_format(stat_department.date, \'%Y-%m\')' => ['=', $date]
            ])
//            ->limit(20)
            ->order('department_id')
            ->cache()
            ->select();

        return json(['succ' => 1,'data' => $result]);
    }

    //返回有数据的月份
    public function depmonth(Request $request){
        $data = $request->param();
        //管理员权限验证
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $result = Db::name('stat_department')
            ->field('distinct date_format(date, \'%Y-%m\') as date')
            ->order('date','desc')
            ->cache()
            ->select();

        return json(['succ' => 1,'data' => $result]);
    }
}