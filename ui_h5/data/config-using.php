<?php
/**
 * Created by PhpStorm.
 * User: Beneware_web
 * Date: 2016/12/9
 * Time: 12:55
 */
//服务器数据库登录信息
$config['host']='127.0.0.1';
$config['id'] = 'root';
$config['pwd']='';
$config['db']='jiamigou';
$config['port']='3306';

$conn = mysqli_connect($config['host'],$config['id'],$config['pwd'],$config['db'],$config['port']);

$sql = "SET NAMES UTF8";
mysqli_query($conn,$sql);

?>