<?php
/**
 * 病历信息控制器
 * User: zxy96
 * Date: 2017/12/04
 * Time: 18:06
 */
namespace app\api\controller;

use app\api\model\MedicalRecord as MedicalRecordModel;
use app\api\model\UserProfile;
use think\Controller;
use think\Request;


class Medicalrecord extends Controller{

    /*
     * 新增病历记录
     * 接口地址：api/Medicalrecord/create
     * 参数：token,profile_id,visit_time,hospital,description
     */
    public function create(Request $request){
        $data = $request->param();
        $data['create_time'] = date("Y-m-d H:i:s");

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $result = MedicalRecordModel::create($data);
            if($result)
                return json(['succ' => 1 ,'data' => $result['id']]);
            else
                return json(['succ' => 0, 'error' => '新建病历失败']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
     * 删除病历记录
     * 接口地址：api/Medicalrecord/delete
     * 参数：token,profile_id,record_id
     */
    public function delete(Request $request){
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $profile = UserProfile::get($data['profile_id']);
            $record = $profile->medical_records()->where('id',$data['record_id'])->find();
            
            if($record){
                $record->delete();
                return json(['succ' => 1]);
        }
            else
                return json(['succ' => 0, 'error' => '病历不存在']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
     * 更新病历记录
     * 接口地址：api/Medicalrecord/update
     * 参数：token,visit_time,hospital,decription
     * 注意：未修改的属性也要将原值传回！
     */
    public function update(Request $request){
        $data = $request->param();

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $profile = UserProfile::get($data['profile_id']);
            $record = $profile->medical_records()->where('id',$data['record_id'])->find();

            if($record){
                $record->allowField(['visit_time','hospital','decription'])->save($_POST);
                return json(['succ' => 1]);
            }
            else
                return json(['succ' => 0, 'error' => '病历不存在']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
     * 查询病历记录
     * 接口地址：api/Medicalrecord/
     * 参数：token,profile_id,record_id(可选)
     * 注意：record_id不填则获取整个列表
     */
    public function index(Request $request){
        $data = $request->param();
        //单个
        if($data['record_id'] > 0){
            $msg = Util::token_validate($data['token'],$data['profile_id']);
            if($msg->succ){
                $profile = UserProfile::get(['id' => $data['profile_id']]);
                $record = $profile->medical_records()->where('id',$data['record_id'])->find();
                return json($record);
            }
            else{
                return json(['succ' => 0, 'error' => $msg->msg]);
            }
        }
        //列表
        else{
            $msg = Util::token_validate($data['token']);
            if($msg->succ){
                $profile = UserProfile::get(['id' => $data['profile_id']]);
                $list = $profile->medical_records()->selectOrFail();
                return json($list);
            }
            else{
                return json(['succ' => 0, 'error' => $msg->msg]);
            }
        }
    }
}