<?php
/**
 * 公用函数
 * User: zxy96
 * Date: 2017/12/01
 * Time: 17:03
 */
namespace app\api\controller;

use app\api\model\ErrMsg;
use app\api\model\User as UserModel;
use think\Controller;
use think\Request;
use think\Image;
use think\Db;

class Util{

    /*
     * 查询用户登录状态和账号信息
     * 参数：token,profile_id（可选）
     * 返回值:true（通过）、string（错误信息）
     */
    public static function token_validate($token, $profile_id = -1){
        $user = UserModel::get(['token' => $token],true);
        //验证token是否存在
        if($user){
            //验证token是否过期
            if($user->token_valid_time != 0){
                $temp = date("Y-m-d G:H:s",strtotime(time()) - $user->token_valid_time * 60);
                if($user->token_create_time <= $temp){
                    return new ErrMsg(false,'登录已过期');
                }
            }
            //验证子账号是否属于用户
            if($profile_id == -1){
                //无需验证子账号
                return new ErrMsg(true,$user);
            }
            else{
                //根据患者/医生作不同处理
                if($user->type_id == 1){
                    $profile = $user->user_profiles()->where('id',$profile_id)->find();
                    if($profile){
                        return new ErrMsg(true,$profile);
                    }
                    else
                        return new ErrMsg(false,'患者资料不存在');
                }

//                else if($user->type_id == 2){
//                    $profile = $user->doctor_profile()->find();
//                    if($profile){
//                        return new ErrMsg(true,$profile);
//                    }
//                    else
//                        return new ErrMsg(false,'医生不存在');
//                }
            }
        }
        else
            return new ErrMsg(false,'登录已过期');
    }

    /*
     * 管理员权限验证
     * 参数：token
     * 返回值:true（通过）、string（错误信息）
     */
    public static function admin_validate($token){
        $user = Util::token_validate($token);
        if(true !=$user->succ || $user->msg->type_id != 3)
            return new ErrMsg(false,'没有权限进行此操作');
        else
            return new ErrMsg(true,$user->msg);
    }

    /**
     * 文件上传
     * 参数：request
     */
    public static function upload(Request $request)
    {
        $data = $request->param();
        $files = request()->file();
        if($files == null)
            return false;
        $results = [];
        foreach($files as $file){
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['ext'=>'jpg,jpeg,gif,png,bmp'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $results[] = new ErrMsg(true,$info->getSavename());
                //保存缩略图
                $imgpath = ROOT_PATH . 'public' . DS . 'uploads'. DS . $info->getSavename();
                $image = Image::open($imgpath);
                $path = ROOT_PATH . 'public' . DS . 'uploads'. DS .date('Ymd');
                $thumb_name = 'thumb_'.explode(DS,$info->getSavename())[1];
                $thumb_path = $path. DS .$thumb_name;
                $image->thumb(200, 200)->save($thumb_path);

            }else{
                // 上传失败获取错误信息
                $results[] = new ErrMsg(false,$file->getError());
            }
        }
        return $results;
    }
    /**
     * 记录尝试次数
     * 参数：request
     */
    public static function log_attempt(Request $request, $type, $status, $user = null)
    {
        $ip_address = $request->ip();
        if ($type == 'LOGIN')
            Db::name('attempt_log')
                ->insert(['ip' => $ip_address, 'type' => $type, 'time' => date("Y-m-d H:i:s"), 'status' => $status, 'user' => $user]);
    }

    /**
     * 查询尝试次数
     * 参数：request
     */
    public static function get_attempt_count(Request $request, $type, $status, $minute)
    {
        $ip_address = $request->ip();
        $count = Db::name('attempt_log')
            ->where([
                'ip' => ['=',$ip_address],
                'type' => ['=',$type],
                'status' => ['=',$status]
            ])
            ->whereTime('time', '>', '-'.$minute.' minutes')
            ->count();
        return $count;
    }

    /**
     * 发送HTTP请求方法
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    public static function http($url, $params, $method = 'GET', $header = array(), $multi = false){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                return new ErrMsg(false, '不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error) return new ErrMsg(false, '请求发生错误：' . $error);
        return new ErrMsg(true, $data);
    }

    public static function GetForwardMonth()
    {
        //得到系统的年月
        $tmp_date=date("Ym");
        //切割出年份
        $tmp_year=substr($tmp_date,0,4);
        //切割出月份
        $tmp_mon =substr($tmp_date,4,2);
//        $tmp_nextmonth=mktime(0,0,0,$tmp_mon+1,1,$tmp_year);
        $tmp_forwardmonth=mktime(0,0,0,$tmp_mon-1,1,$tmp_year);
//        if($sign==0){
//            //得到当前月的下一个月
//            return $fm_next_month=date("Ym",$tmp_nextmonth);
//        }else{
            //得到当前月的上一个月
            return $fm_forward_month=date("Y-m",$tmp_forwardmonth);
//        }
    }

    /**
     * 清理数据表查询缓存
     *
     * 用于解决数据表增删改操作无法刷新查询缓存(特别是cache(true)自动命名的缓存)的问题
     * 只需在增删改操作成功时,调用clear_table_caching($table);
     * 即可使对应此表的所有查询缓存失效.
     *
     * 默认在模型的add,save,delete,setField等操作中会自动调用
     *
     * @param string $table 数据表名称
     * @since 1.0 <2015-4-30> SoChishun Added;
     */
//    function clear_table_caching($table = '') {
//        $table_caching_keys = F('table_caching_keys');
//        if (!$table_caching_keys) {
//            return;
//        }
//// 清理指定表的缓存
//        if ($table) {
//            if (isset($table_caching_keys[$table])) {
//                $values = $table_caching_keys[$table];
//                foreach ($values as $id) {
//                    S($id, null);
//                }
//                unset($table_caching_keys[$table]);
//                F('table_caching_keys', $table_caching_keys);
//            }
//            return;
//        }
//// 清理所有表的缓存
//        foreach ($table_caching_keys as $values) {
//            foreach ($values as $id) {
//                S($id, null);
//            }
//        }
//        F('table_caching_keys', null);
//    }
//
//    /**
//     * 添加缓存键名到数据表查询集合中
//     *
//     * 用于提供clear_table_caching()方法的数据调用
//     *
//     * 默认在getField,select,find等方法中自动调用
//     *
//     * @param string $table 数据表名称
//     * @param string $id 缓存键名
//     * @since 1.0 <2015-4-30> SoChishun Added.
//     */
//    function log_table_cacheing($table, $id) {
//        $table_caching_keys = F('table_caching_keys');
//        if ($table_caching_keys && isset($table_caching_keys[$table]) && in_array($id, $table_caching_keys[$table])) {
//            return; // 如果已经存在则退出
//        }
//        $table_caching_keys[$table][] = $id;
//        F('table_caching_keys', $table_caching_keys);
//    }

    function getnews(Request $request){
        $data = self::http("http://mhos.jiankang51.cn/support/get_info?actionType=cmsInfoFacade&actionCode=getColumnNodeInfo&deepth=1&nodeId=93",[]);
        return json($data);
    }

}