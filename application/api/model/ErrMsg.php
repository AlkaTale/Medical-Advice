<?php
/**
 * 公用函数返回信息
 * User: zxy96
 * Date: 2017/12/07
 * Time: 17:25
 */

namespace app\api\model;


class ErrMsg
{
    public $succ;
    public $msg;

    function __construct($succ, $msg){
        $this->succ= $succ;
        $this->msg = $msg;
    }
}