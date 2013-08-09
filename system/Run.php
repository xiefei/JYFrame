<?php if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
require_once(SYSTEM_ROOT.'/CoreComm/Global.php');
require_once(SYSTEM_ROOT.'/CoreLib/Exceptions.php');
require_once(SYSTEM_ROOT.'/CoreLib/Loader.php');
//错误处理
set_exception_handler('_exceptionHandler');
set_error_handler('_errorHandler');


class Run
{
	
}

function __autoload($className)
{
	
}

//自动装载函数
$autoSP = spl_autoload_functions();
//如果没有注册过自动载入器，则自行注册载入器
if($autoSP == false || in_array('__autoload' , $autoSP))
{
	spl_autoload_register(array('Loader' , 'autoload'));
}

$configs = Loader::Config('core.cfg');
var_dump($config);
//Loader::CoreLibs('Log' , 1);
