<?php
/*
* 每日定时计划任务
* 1、刷新剩余可预约数量
* 2、保存前一日统计信息
* 服务器中设置计划任务自动执行，不要把此文件放在web目录下！
*/
$time = date("Y-m-d H:i:s");
$date = date("Y-m-d");

//设置数据库连接
$db_host = '139.196.90.212';
$db_user = 'root';
$db_pwd = 'root';
$db_name = 'medical_advice';
$conn = mysqli_connect($db_host,$db_user,$db_pwd,$db_name);

//获取今天周几（重置该天的可用数量）
$weekday = date("w");
if ($weekday == 0)
    $weekday = 7;

//重置数量
$sql_reset_number = "update schedule set number=max_count where day={$weekday}";
$result = mysqli_query($conn,$sql_reset_number);

//插入日志
if ($result){
    $sql_log_reset = "insert into log VALUES (null,'{$time}','重置可用预约数量成功','RESET_NUMBER',1)";
}
else{
    $sql_log_reset = "insert into log VALUES (null,'{$time}','重置可用预约数量失败','RESET_NUMBER',0)";
}
mysqli_query($conn,$sql_log_reset);

//统计订单数量
$date_1 = date('Y-m-d', strtotime('-1 days'));
$sql_new_count = "select COUNT(*) FROM `order` WHERE DATE_FORMAT(create_time, \"%Y-%m-%d\") = date_sub(curdate(),interval 1 day)";
$result = mysqli_query($conn,$sql_new_count);
$count = mysqli_fetch_row($result)[0];

$sql_insert_stat = "insert into stat_order_count(date,new_orders) VALUES ('{$date_1}',{$count})";
mysqli_query($conn,$sql_insert_stat);
$conn->close();



