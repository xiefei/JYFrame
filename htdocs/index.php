<?php
//执行默认的ctl
define('DEFAULT_CTL' , 'index');

//系统文件目录
define('SYSTEM_ROOT' , '../system');

//基础文件目录
define('BASE_DOCUMENT_ROOT', '../');

//启动执行文件
require_once(SYSTEM_ROOT.'/Run.php');

//执行的默认ctl
Run::Ctl(DEFAULT_CTL);
