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
                $msg = Util::token_validate($data['token'],$data['profile_id']);
                if($msg->succ){
                    $profile = $msg->msg;
                    return json(['succ' => 1, 'data' => $profile]);
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
                    $list = $user->user_profiles()->selectOrFail();
                    return json(['succ' => 1, 'data' => $list]);
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
                $data['user_id'] = $msg->msg['id'];
                $result = UserProfileModel::create($data);

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
      * 接口地址：api/Userprofile
      * 参数：token,id，name,sex，
      */
        public function update(Request $request)
        {
            $data = $request->param();//
                //验证token
                if (Util::token_validate($data['token'], $data['profile_id'])) {
                    $user = UserProfileModel::get(['id' => $data['profile_id']]);
                    if ($user) {
                        $user->allowField(['name','birth','sex','address','phone','create_time'])->save($_POST);
                        return json(['succ' => 1]);
                    }else
                        return json(['succ' => 0, 'error' => '子用户不存在']);

                } else {
                    return json(['error' => '登录已失效']);
                }

            }

    }