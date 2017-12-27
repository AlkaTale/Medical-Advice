<?php
/*
 * User类控制器
 * 2017/11/29
 */
namespace app\api\controller;

use app\api\model\User as UserModel;
use think\Controller;
use think\Request;
use think\Image;


class User extends Controller{

    /*
     * 获取用户信息
     * 接口地址：api/User/
     * 参数：token
     */
    public function index(Request $request){
        $data = $request->param();
        //检查登录状态
        $msg = Util::token_validate($data['token']);
        if($msg->succ){
            return json(['succ' => 1, 'data' => $msg->msg]);
        }
        else
            return json(['succ' => 0, 'error' => $msg->msg]);
    }

    /*
     * 注册账户
     * 接口地址：api/User/create
     * 参数：nickname,password,phone,type_id
     */
    public function create(Request $request){
        $data = $request->param();
        $data['create_time'] = date("Y-m-d H:i:s");
        //$result = UserModel::create($data);

        //加载验证器
        $valid_result = $this->validate($data,'User');
        if(true !== $valid_result){
            return json(['succ' => 0,'error' => $valid_result]);
        }
        else{
            //密码加密保存
            $data['password'] = md5(md5($data['password'] ));
            $result = UserModel::create($data);
            $token = Token::create($result->id,$result->password);
            Token::update($result,$token);
            return json(['succ' => 1,'token' => $token, 'data' => $result]);
        }
    }

    /*
     * 上传头像
     * 接口地址：api/User/updateavatar
     * 参数：token
     */
    public function updateavatar(Request $request){
        $data = $request->param();
        //检查登录状态
        $msg = Util::token_validate($data['token']);
        if($msg->succ){
            //调用公共函数保存原图
            $results = Util::upload($request);
            if($results[0]->succ){
                //根据参数裁剪原图
                $image = Image::open('./public/uploads/'.$results[0]->msg);
                //解析参数
                $avatar_data = json_decode($data['avatar_data']);
                $image->crop($avatar_data->height,$avatar_data->width,$avatar_data->x,$avatar_data->y);
                $image->rotate($avatar_data->rotate);
                //保存裁剪后图片
                $image->save(ROOT_PATH . 'public/uploads/' . $results[0]->msg);
                //保存到数据库
                $user = $msg->msg;
                $user->avatar = $results[0]->msg;
                if (false !== $user->save()) {
                    return json(['succ' => 1, 'result' => $results[0]->msg]);
                } else {
                    return json(['succ' => 0, 'error' => '更新头像失败']);
                }
            }
            else{
                return json(['succ' => 0, 'error' => $results[0]->msg]);
            }
        }
        else{
            return json(['succ' => 0, 'error' => $msg->msg]);
        }
    }
}