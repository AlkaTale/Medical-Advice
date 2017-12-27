<?php
/**
 * Created by PhpStorm.
 * User: qinhao
 * Date: 2017/12/25
 * Time: 14:33
 */

namespace app\api\controller;

use app\api\model\DoctorProfile as DoctorProfileModel;
use app\api\model\User;
use think\Controller;
use think\Request;

class Doctorprofile extends Controller
{
    /*
         * 查询
         * 接口地址：api/Doctorprofile
         * 参数：token,profile_id(0）
         */
    public function index(Request $request){
        $data = $request->param();

        //单个
        if($data['profile_id'] > 0){
            //验证token
            $msg = Util::token_validate($data['token'],$data['profile_id']);
            if($msg->succ){
                $profile = $msg->msg;
                return json($profile);
            }
            else{
                return json(['error' => '登录已失效']);
            }
        }
        //列表
        else{
            //验证token
            if(Util::token_validate($data['token'])){
                $profile = DoctorProfileModel::get(['id' => $data['profile_id']]);
                $list = $profile->doctor_profile()->selectOrFail();
                return json($list);
            }
            else{
                return json(['error' => '登录已失效']);
            }
        }
    }
    /*
    * 医生增加（doctor_profile表）
    * 接口地址：api/Doctorprofile
    * 参数：token,id，name,sex，age,history,
    */
    public function create(Request $request){
        $data = $request->param();
        $data['create_time'] = date("Y-m-d H:i:s");
        //$result = UserModel::create($data);

        $msg = Util::token_validate($data['token']);
        if(true !== $msg->succ){
            return json(['succ' => 0,'error' => $msg->msg]);
        }
        else{
            $result = DoctorProfileModel::create($data);

            return json(['succ' => 1, 'data' => $result]);//'token' => $token,删除
        }
    }
    /*
    *医生删除（doctor_profile表）
    * 接口地址：api/Doctorprofile
    * 参数：token,id，name,sex，
    */
    public function delete(Request $request)
    {
        $data = $request->param();
        $msg = Util::token_validate($data['token'],$data['profile_id']);
        //验证token
        if ($msg->succ) {
            $user = DoctorProfileModel::get(['id' => $data['profile_id']]);

            if($user){
                $user->delete();
                return json(['succ' => 1]);
            }
            else
                return json(['succ' => 0, 'error' => '用户不存在']);

        } else {
            return json(['error' =>  $msg->msg]);
        }
    }

    /*
  * 医生改（doctor_profile表）
  * 接口地址：api/Doctorprofile
  * 参数：token,id，
  */
    public function update(Request $request)
    {
        $data = $request->param();//
        //验证token
        if (Util::token_validate($data['token'], $data['profile_id'])) {
            $user = DoctorProfileModel::get(['id' => $data['profile_id']]);
            if ($user) {
                $user->allowField(['name','department_id','introduction'])->save($_POST);
                return json(['succ' => 1]);
            }else
                return json(['succ' => 0, 'error' => '该医生不存在']);

        } else {
            return json(['error' => '登录已失效']);
        }

    }


}