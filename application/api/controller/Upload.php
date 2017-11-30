<?php
/**
 * 文件上传
 * 参数：request
 * User: zxy96
 * Date: 2017/12/01
 * Time: 0:20
 */
namespace app\api\controller;
use think\Request;

class Upload
{
    public static function upload(Request $request)
    {
        // 获取表单上传文件
        $file = $request->file('file');
        if (empty($file)) {
            return 0;
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            return $info->getRealPath();
        } else {
            // 上传失败获取错误信息
            //$this->error($file->getError());
        }
    }
}