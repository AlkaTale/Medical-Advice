<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/20
 * Time: 21:19
 */

namespace app\api\controller;

use app\api\model\User as UserModel;
use app\api\model\DoctorProfile;
use think\Controller;
use think\Request;
use think\Db;


class Importxls extends Controller{

    /*
     * 导入医生账号和资料
     * 接口地址：api/Importxls
     * 参数：token,excel,type（导入数据类型：doctor、department、doctor_type）
     */
    public function index(Request $request){
        //管理员权限验证
        //todo：是否需要记录管理员操作日志？
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        vendor("PHPExcel.PHPExcel"); //方法一
        $objPHPExcel = new \PHPExcel();

        //获取表单上传文件
        $file = request()->file('excel');
        $info = $file->validate(['size'=>15678,'ext'=>'xlsx,xls,csv'])->move(ROOT_PATH . 'public' . DS . 'excel');
        if($info){
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $exclePath;   //上传文件的地址
            $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
            $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
            $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);

            $failed_data = [];
            $i=0;

            if($data['type'] == 'doctor'){
                foreach($excel_array as $k=>$v) {
                    $data_doctor = [];
                    $data_profile = new DoctorProfile();

                    //组装医生账号数据
                    $data_doctor['nickname'] = $v[0];
                    //todo：密码加密
                    $data_doctor['password'] = md5(md5($v[1]));
                    $data_doctor['phone'] = $v[2];
                    $data_doctor['type_id'] = 2;
                    $data_doctor['create_time'] = date("Y-m-d H:i:s");

                    $user = UserModel::create($data_doctor);

                    if($user){
                        //组装医生资料数据
                        $data_profile->name = $v[3];
                        $data_profile->department_id = $v[4];
                        $data_profile->type = $v[5];
                        $data_profile->introduction = $v[6];
                        $data_profile->update_time = date("Y-m-d");
                        $user->doctor_profile()->save($data_profile);
                    }
                    else
                        $failed_data[] = $data_doctor;
                    $i++;
                }
                return json(['succ' => 1,'count' => $i, 'failed' => $failed_data]);
            }
            elseif ($data['type'] == 'department'){
                $data_department = [];
                foreach($excel_array as $k=>$v) {
                    //组装科室数据
                    $data_department[$k]['name'] = $v[0];
                    $data_department[$k]['description'] = $v[1];
                    $i++;
                }
                $success = Db::name('department')->insertAll($data_department);
                return json(['succ' => 1,'count' => $success, 'failed' => $i-$success]);
            }
            elseif ($data['type'] == 'doctor_type'){
                $data = [];
                foreach($excel_array as $k=>$v) {
                    //组装科室数据
                    $data[$k]['type'] = $v[0];
                    $data[$k]['price'] = $v[1];
                    $i++;
                }
                $success = Db::name('doctor_type')->insertAll($data);
                return json(['succ' => 1,'count' => $success, 'failed' => $i-$success]);
            }
            else
                return json(['succ' => 0,'error' => '导入类型错误']);



        }else{
            // 上传失败获取错误信息
            return json(['succ' => 0,'error' => $file->getError()]);
        }

    }
}