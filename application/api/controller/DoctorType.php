<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/05/22
 * Time: 11:08
 */

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Doctortype extends Controller{

    /*
     * 获取医生类型列表
     * 调用：api/doctortype
     * 参数：
     */
    public function index(){
        $result = Db::name('doctor_type')->select();
        return json(['succ' => 1, 'data' => $result]);
    }

    /*
     * 手动添加医生类型
     * 调用：api/doctortype/create
     * 参数：token,type,price
     */
    public function create(Request $request){
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $result = Db::name('doctor_type')
            ->insert(['type' => $data['type'], 'price' => $data['price']]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '添加失败']);
    }

    /*
     * 修改医生类型
     * 调用：api/doctortype/update
     * 参数：token,type,price,type_id
     */
    public function update(Request $request)
    {
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if (true != $admin->succ)
            return json(['succ' => 0, 'error' => $admin->msg]);

        $result = Db::name('doctor_type')
            ->where([
                'id' => ['=', $data['type_id']],
            ])
            ->update(['type' => $data['type'],'price' => $data['price']]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '修改失败']);
    }
}