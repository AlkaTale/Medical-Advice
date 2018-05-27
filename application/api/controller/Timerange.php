<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/27
 * Time: 0:53
 */
namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Timerange extends Controller
{
    /*
     * 查询时间段列表
     * 接口地址：api/timerange
     * 参数：
     */
    public function index(Request $request)
    {
        $result = Db::name('time_range')->select();
        return json(['succ' => 1, 'data' => $result]);
    }

    /*
    * 手动添加时间段
    * 调用：api/timerange/create
    * 参数：token,begin,end
    */
    public function create(Request $request){
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $begin = $data['begin'];
        $end = $data['end'];
        $range = $begin.'-'.$end;
        if ((int)explode(':',$begin) < 12)
            $flag = 0;
        else
            $flag = 1;

        $result = Db::name('time_range')
            ->insert(['begin' => $begin, 'end' => $end, 'range' => $range, 'flag' => $flag]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '添加失败']);
    }
}