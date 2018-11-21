<?php
    /*
     * Userprofile类控制器
     * 2017/12
     */
    namespace app\api\controller;

    use app\api\model\UserProfile as UserProfileModel;
    use app\api\model\User;
    use think\Controller;
    use think\Request;
    use think\Db;


    class Userprofile extends Controller{

        /*
         * 查询
         * 接口地址：api/Userprofile
         * 参数：token,profile_id(0）
         */
        public function index(Request $request){
            $data = $request->param();

            //单个
            if($data['profile_id'] > 0){
                //验证token
                $msg = Util::token_validate($data['token'],$data['profile_id']);
                if($msg->succ){
//                    $profile = $msg->msg;
                    $qstring = "call query_user_profile({$data['profile_id']},-1)";
                    $result = Db::query($qstring);
                    return json(['succ' => 1, 'data' => $result[0]]);
                }
                else{
                    return json(['error' => $msg->msg]);
                }
            }
            //列表
            else{
                //验证token
                $msg = Util::token_validate($data['token']);
                if($msg->succ){
                    $user = $msg->msg;
//                    $list = $user->user_profiles()->selectOrFail();
                    $qstring = "call query_user_profile(0,{$user['id']})";
                    $result = Db::query($qstring);
                    return json(['succ' => 1, 'data' => $result[0]]);
                }
                else{
                    return json(['error' => $msg->msg]);
                }
            }
        }
        /*
    * 子用户增加（profile表）
    * 接口地址：api/Userprofile
    * 参数：token,id，name,sex，
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
                //1、查询该类型最大数量
                $result = Db::name('user_type')
                    ->where('id', '=', $msg->msg->type_id)
                    ->find();
                $max_count = $result['max_count'];
                //2、判断是否已超出最大数量
                $list = $msg->msg->user_profiles()->select();
                if (sizeof($list) >= $max_count)
                    return json(['succ' => 0,'error' => '患者资料超过上限']);

                $data['user_id'] = $msg->msg['id'];
                $valid_result = $this->validate($data,'UserProfile');
                if(true !== $valid_result){
                    return json(['succ' => 0,'error' => $valid_result]);
                }
//                $result = UserProfileModel::create($data)
                $qstring = "call insert_user_profile('{$data['name']}',{$data['sex']},'{$data['birth']}','{$data['address']}','{$data['phone']}',{$data['user_id']},{$data['relation']})";
                Db::query($qstring);
                return json(['succ' => 1/*, 'data' => $result*/]);//'token' => $token,删除
            }
        }
        /*
        * 子用户删除（profile表）
        * 接口地址：api/Userprofile
        * 参数：token,id，name,sex，
        */
        public function delete(Request $request)
        {
            $data = $request->param();
            $msg = Util::token_validate($data['token'],$data['profile_id']);
                //验证token
                if ($msg->succ) {
                    $user = UserProfileModel::get(['id' => $data['profile_id']]);

                    if($user){
                        $user->delete();
                        return json(['succ' => 1]);
                    }
                    else
                        return json(['succ' => 0, 'error' => '子用户不存在']);

                } else {
                    return json(['error' =>  $msg->msg]);
                }
            }

        /*
      * 子用户改（profile表）
      * 接口地址：api/Userprofile/update
      * 参数：token,id，name,sex，
      */
        public function update(Request $request)
        {
            $data = $request->param();//
                //验证token
                if (Util::token_validate($data['token'], $data['profile_id'])) {
                    $user = UserProfileModel::get(['id' => $data['profile_id']]);
                    if ($user) {
                        $valid_result = $this->validate($data,'UserProfile');
                        if(true !== $valid_result){
                            return json(['succ' => 0,'error' => $valid_result]);
                        }

                        $user->name = $data['name'];
                        $user->birth = $data['birth'];
                        $user->sex = $data['sex'];
                        $user->relation = $data['relation'];

                        if(false != $user->allowField(['name','birth','sex','address','relation'])->save($_POST))
                            return json(['succ' => 1]);
                        else
                            return json(['succ' => 0]);
                    }else
                        return json(['succ' => 0, 'error' => '子用户不存在']);

                } else {
                    return json(['error' => '登录已失效']);
                }

            }
        public function update_phone(Request $request)
        {
            $data = $request->param();//
            //验证token
            if (Util::token_validate($data['token'], $data['profile_id'])) {
                $user = UserProfileModel::get(['id' => $data['profile_id']]);
                if ($user) {
                    $valid_result = $this->validate($data,'UserProfile');
                    if(true !== $valid_result){
                        return json(['succ' => 0,'error' => $valid_result]);
                    }
                    $qstring = "call update_user_profile({$data['profile_id']},'{$data['name']}',{$data['sex']},'{$data['birth']}','{$data['address']}','{$data['phone']}')";
                    Db::query($qstring);

//                        if(false != $user->allowField(['name','birth','sex','address','phone'])->save($_POST))
                    return json(['succ' => 1]);
//                        else
//                            return json(['succ' => 0]);
                }else
                    return json(['succ' => 0, 'error' => '子用户不存在']);

            } else {
                return json(['error' => '登录已失效']);
            }

        }
    }