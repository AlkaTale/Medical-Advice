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
                if(Util::token_validate($data['token'],$data['profile_id'])){
                    $user = User::get(['token' => $data['token']]);
                    $profile = $user->user_profiles()->where('id',$data['profile_id'])->find();
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
                    $user = User::get(['token' => $data['token']]);
                    $list = $user->user_profiles()->selectOrFail();
                    return json($list);
                }
                else{
                    return json(['error' => '登录已失效']);
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

            //加载验证器
            $valid_result = $this->validate($data,'User');
            if(true !== $valid_result){
                return json(['succ' => 0,'error' => $valid_result]);
            }
            else{
                //密码加密保存
    //            $data['password'] = md5(md5($data['password'] ));
                //这里有改动
                $result = UserProfileModel::create($data);

    //            $token = Token::create($result->id,$result->password);
    //            Token::update($result,$token);
                return json(['succ' => 1, 'data' => $result]);//'token' => $token,删除
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
            if ($data['profile_id'] > 0) {
                //验证token
                if (Util::token_validate($data['token'], $data['profile_id'])) {
                    $user = User::get(['token' => $data['token']]);
                    $profile = $user->user_profiles()->where('id', $data['profile_id'])->find();
                    return json($profile);
                } else {
                    return json(['error' => '登录已失效']);
                }
            } else {
                $user = UserprofileModel::get($data['profile_id']);
                if ($user) {
                    $user->delete();
                    return '删除用户成功';
                } else {
                    return '删除的用户不存在';
                }
            }
        }
        /*
      * 子用户改（profile表）
      * 接口地址：api/Userprofile
      * 参数：token,id，name,sex，
      */
        public function update(Request $request)
        {
            $data = UserprofileModel::get($request);//
            if ($data['profile_id'] > 0) {
                //验证token
                if (Util::token_validate($data['token'], $data['profile_id'])) {
                    $user = User::get(['token' => $data['token']]);
                    $profile = $user->user_profiles()->where('id', $data['profile_id'])->find();
                    return json($profile);
                } else {
                    return json(['error' => '登录已失效']);
                }
            } else {
                $this->update($request);

                if (false !== $data->save()) {
                    return '更新用户成功';
                } else {
                    return $data->getError();
                }
            }
        }
    }