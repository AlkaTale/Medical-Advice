<?php
/**
 * 病历图片上传&展示
 * User: zxy96
 * Date: 2017/12/07
 * Time: 22:36
 */

namespace app\api\controller;

use app\api\model\RecordImage as RecordImageModel;
use app\api\model\UserProfile;
use think\Controller;
use think\Request;

class Recordimage extends Controller{

    /*
     * 上传
     * 调用：api/Recordimage/upload
     * 参数：image,profile_id,token,record_id,type_id
     */
    public function upload(Request $request){
        $data = $request->param();
        $data['create_time'] = date("Y-m-d H:i:s");

        $msg = Util::token_validate($data['token'],$data['profile_id']);
        if($msg->succ){
            $profile = $msg->msg;
            $record = $profile->medical_records()->where('id',$data['record_id'])->find();
            
            if($record){
                $results = Util::upload($request);
                foreach ($results as $result){
                    if (false !== $result->succ){
                        $data['link'] = $result->msg;
                        RecordImageModel::create($data);
                    }
                }
                return json(['succ' => 1, 'data' => $results]);
            }
            else
                return json(['succ' => 0, 'error' => '病历不存在']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }

    /*
     * 查询
     * 调用：api/Recordimage/
     * 参数：profile_id,token,record_id
     */
    public function index(Request $request){
        $data = $request->param();
        $msg = Util::token_validate($data['token'],$data['profile_id']);

        if($msg->succ){
            $profile = $msg->msg;
            $record = $profile->medical_records()->where('id',$data['record_id'])->find();

            if($record){
                $results = RecordImageModel::get(['record_id' => $data['record_id']]);
                if ($results)
                    return json(['succ' => 1, 'data' => $results]);
                else
                    return json(['succ' => 0, 'error' => '暂无病历图片']);
            }
            else
                return json(['succ' => 0, 'error' => '病历不存在']);
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }
}