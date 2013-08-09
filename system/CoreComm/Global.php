<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');

//公共全局函数和配置

if (!function_exists('GetLoaderClass'))
{
	//获取载入的类
	function GetLoaderClass()  
	{
		$loader = Loader::Instance();	
		return $loader->classes();
	}
}

if (!function_exists('_errorHandler'))
{
	//错误处理函数
	function _errorHandler($errNo, $errStr, $errFile,$errLine)
	{
		
	}
}

if (!function_exists('_exceptionHandler'))
{
	//异常处理函数
	function _exceptionHandler($exception)
	{
		if ($exception instanceof JYException)
		{
			//根据配置文件是否写入日志
			$log = &Loader::CoreLib('Log' , 1);	
		}
		_errorShow($exception->getMessage());
	}
}

if (!function_exists('_errorShow'))
{
	//错误显示
	function _errorShow($msg)
	{
		echo $msg;	
		exit();
	}
}

