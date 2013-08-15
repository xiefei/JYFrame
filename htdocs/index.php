<?php

error_reporting(E_ALL);
//定义版本环境
define('ENVIRONMENT' , 'test');
//执行默认的ctl
define('DEFAULT_CTL' , 'index');
//系统文件目录
define('SYSTEM_ROOT' , '../system/');
//基础文件目录
define('BASE_DOCUMENT_ROOT', '../');
//请求的默认文件地址
define('DEFAULT_URL' , pathinfo(__FILE__,PATHINFO_BASENAME));
//启动执行文件
require_once(SYSTEM_ROOT.'/Run.php');
//执行的默认ctl
Run::runServer(DEFAULT_CTL);
