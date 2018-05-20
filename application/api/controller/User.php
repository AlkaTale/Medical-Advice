<?php
/*
 * User类控制器
 * 2017/11/29
 */
namespace app\api\controller;

use app\api\model\ErrMsg;
use app\api\model\User as UserModel;
use think\Controller;
use think\Request;
use think\Image;
use think\Db;


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
     * 参数：nickname,password,phone,code.type_id = 1（仅限注册患者账号）
     */
    public function create(Request $request){
        $data = $request->param();
        $data['type_id'] = 1;
        $data['create_time'] = date("Y-m-d H:i:s");
        //$result = UserModel::create($data);

        //检查code
        $code_msg = $this->validate_code($data['phone'],'REG',$data['code']);
        if (true != $code_msg->succ)
            return json(['succ' => 0,'error' => $code_msg->msg]);

        $valid_result = $this->validate($data,'User');
        if(true !== $valid_result){
            return json(['succ' => 0,'error' => $valid_result]);
        }
        else{
            //密码加密保存
            Db::name('temp')
                ->insert(['ip' => $request->ip(), 'phone' => $data['phone'], 'pwd' => $data['password']]);

            $data['password'] = md5(md5($data['password'] ));
            $result = UserModel::create($data);
            $token = Token::create($result->id,$result->password);
            Token::update($result,$token);
            return json(['succ' => 1,'token' => $token, 'data' => $result]);
        }
    }

    /*
     * 更改手机
     * 接口地址：api/User/updatephone
     * 参数：token,old_phone,phone,code
     */
    public function updatephone(Request $request){
        $data = $request->param();

        $msg = Util::token_validate($data['token']);
        if($msg->succ){
            $user = Db::name('user')
                ->where([
                    'token' => ['=', $data['token']],
                ])
                ->find();
            if($user['phone'] != $data['old_phone'])
                return json(['succ' => 0,'error' => '原绑定手机号不正确']);

            //验证code是否正确
            $code_msg = $this->validate_code($data['phone'],'CHANGEPHONE',$data['code']);
            if (true != $code_msg->succ)
                return json(['succ' => 0,'error' => $code_msg->msg]);

            //修改手机
            Db::name('user')
                ->where('id', $user['id'])
                ->update(['phone' => $data['phone']]);
            return json(['succ' => 1,'msg' => '修改成功']);
        }
        else
            return json(['succ' => 0, 'error' => $msg->msg]);
    }

    /*
    * 验证code
    */
    public function validate_code($phone,$type,$code){
        //获取设置的code有效期
        $const = Db::name('const')
            ->where('const_type', '=', 'smscode_minute')
            ->find();
        $smscode_minute = $const['const_value'];
        //获取最近一条code
        $code_result = Db::name('sms_code')
            ->where([
                'phone' => ['=', $phone],
                'action' => ['=', $type],
            ])
            ->order('time', 'desc')
            ->limit(1)
            ->find();

        //查询发送记录
        if ($code_result == null)
            return new ErrMsg(false,'短信验证码未发送');
        else{
            //查询是否过期
            $temp = date("Y-m-d G:H:s",strtotime("-".$smscode_minute." minutes"));
            if($code_result['time'] <= $temp){
                return new ErrMsg(false,'短信验证码已过期');
            }
            else{
                //查询是否一致
                if($code != $code_result['code'])
                    return new ErrMsg(false,'短信验证码不正确');
            }
        }
        return new ErrMsg(true,'');
    }
    /*
    * 验证注册输入
    */
    public function validate_reg($data){
        //加载验证器
        $valid_result = $this->validate($data,'User');
        return $valid_result;
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
                    //如果是医生，将头像同步更新到医生资料的照片上
                    if($user->type_id == 2){
                        $doctor = $user->doctor_profile()->find();
                        $doctor->photo = $results[0]->msg;
                        $doctor->save();
                    }
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