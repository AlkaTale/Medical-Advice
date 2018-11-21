<?php
/**
 * Created by PhpStorm.
 * User: zxy96
 * Date: 2018/01/25
 * Time: 16:13
 */

namespace app\api\controller;

use think\Controller;
use think\Request;
use think\Db;

class Department extends Controller{

    /*
     * 获取科室列表
     * 调用：api/department
     * 参数：
     */
    public function index(){
        $result = Db::name('department')->field('id,name')->cache()->select();
        return json(['succ' => 1, 'data' => $result]);
    }

    public function sortlist(){
        $data = Db::name('department')->field('id,name')->cache()->select();
        $data = (new Character)->groupByInitials($data, 'name');
        return json(['succ' => 1, 'data' => $data]);

    }

    public function detaillist(){
        $result = Db::name('department')->select();
        return json(['succ' => 1, 'data' => $result]);
    }

    /*
     * 手动添加科室
     * 调用：api/department/create
     * 参数：token,name,description
     */
    public function create(Request $request){
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if(true !=$admin->succ)
            return json(['succ' => 0,'error' => $admin->msg]);

        $result = Db::name('department')
            ->insert(['name' => $data['name'], 'description' => $data['description']]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '添加失败']);
    }

    /*
     * 修改科室
     * 调用：api/department/update
     * 参数：token,name,description,department_id
     */
    public function update(Request $request)
    {
        $data = $request->param();
        $admin = Util::admin_validate($data['token']);
        if (true != $admin->succ)
            return json(['succ' => 0, 'error' => $admin->msg]);

        $result = Db::name('department')
            ->where([
                'id' => ['=', $data['department_id']],
            ])
            ->update(['name' => $data['name'],'description' => $data['description']]);
        if ($result)
            return json(['succ' => 1]);
        else
            return json(['succ' => 0,'error' => '修改失败']);
    }
}