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

        if(Util::token_validate($data['token'],$data['profile_id'])){
            $result = MedicalRecordModel::create($data);
            if($result)
                return json(['succ' => 1]);
            else
                return json(['succ' => 0, 'error' => '新建病历失败']);
        }
        else{
            return json(['succ' => 0, 'error' => '登录已失效']);
        }
    }
}