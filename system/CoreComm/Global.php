<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
define('FOPEN_WRITE_CREATE','ab');
define('FOPEN_WRITE_CREATE_STRICT','xb');
define('FILE_WRITE_MODE', 0666);
if (!function_exists('isPHP'))
{
	//php版本
	function isPHP($version = '5.0.0')
	{
		static $_isPHP;
		$version = (string)$version;
		if ( ! isset($_isPHP[$version]))
		{
			$_isPHP[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
		}
		return $_isPHP[$version];
	}
}
if (!function_exists('isReallyWritable'))
{
    function isReallyWritable($file)
    {   
        if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE)
        {   
            return is_writable($file);
        }   
        if (is_dir($file))
        {   
            $file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));
            if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
            {   
                return FALSE;
            }   
            fclose($fp);
            @chmod($file, DIR_WRITE_MODE);
            @unlink($file);
            return TRUE;
        }   
        elseif ( ! is_file($file) OR ($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
        {   
            return FALSE;
        }   
        fclose($fp);
        return TRUE;
    }   
}


if (!function_exists('setStatusHeader'))
{
	//设置输出头
	function setStatusHeader($code = 200, $text = '')
	{
		$stati = array(
				200	=> 'OK',
				201	=> 'Created',
				202	=> 'Accepted',
				203	=> 'Non-Authoritative Information',
				204	=> 'No Content',
				205	=> 'Reset Content',
				206	=> 'Partial Content',
				300	=> 'Multiple Choices',
				301	=> 'Moved Permanently',
				302	=> 'Found',
				304	=> 'Not Modified',
				305	=> 'Use Proxy',
				307	=> 'Temporary Redirect',
				400	=> 'Bad Request',
				401	=> 'Unauthorized',
				403	=> 'Forbidden',
				404	=> 'Not Found',
				405	=> 'Method Not Allowed',
				406	=> 'Not Acceptable',
				407	=> 'Proxy Authentication Required',
				408	=> 'Request Timeout',
				409	=> 'Conflict',
				410	=> 'Gone',
				411	=> 'Length Required',
				412	=> 'Precondition Failed',
				413	=> 'Request Entity Too Large',
				414	=> 'Request-URI Too Long',
				415	=> 'Unsupported Media Type',
				416	=> 'Requested Range Not Satisfiable',
				417	=> 'Expectation Failed',
				500	=> 'Internal Server Error',
				501	=> 'Not Implemented',
				502	=> 'Bad Gateway',
				503	=> 'Service Unavailable',
				504	=> 'Gateway Timeout',
				505	=> 'HTTP Version Not Supported'
				);

		if ($code == '' OR ! is_numeric($code))
		{
			showError('Status codes must be numeric', 500);
		}
		if (isset($stati[$code]) AND $text == '')
		{
			$text = $stati[$code];
		}
		if ($text == '')
		{
			showError('No status text available.  Please check your status code number or supply your own message text.', 500);
		}
		$serverProtocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;
		if (substr(php_sapi_name(), 0, 3) == 'cgi')
		{
			header("Status: {$code} {$text}", TRUE);
		}
		elseif ($serverProtocol == 'HTTP/1.1' OR $serverProtocol == 'HTTP/1.0')
		{
			header($serverProtocol." {$code} {$text}", TRUE, $code);
		}
		else
		{
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
	}
}


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
		if ($errNo == E_STRICT)
		{
			return;
		}
		$exception = Loader::CoreLib('Exceptions' , 1);
		if (($errNo & error_reporting()) == $errNo)
		{
			$exception->showPhpError($errNo , $errStr , $errFile , $errLine);
		}
		$logLevel = systemConfig('writeLogLevel');
		if (empty($logLevel)) return;
		$exception->logExceptions($errNo , $errStr , $errFile , $errLine);
	}
}

if (!function_exists('_exceptionHandler'))
{
	//异常处理函数
	function _exceptionHandler($exception)
	{
		if ($exception instanceof JYException)
		{
			$level = 'error';
			$logLevel = systemConfig('writeLogLevel');
			//根据配置文件是否写入日志
			if (!empty($logLevel) && in_array($level, $logLevel))
			{
				//写入日志
				$log = Loader::CoreLib('Log' , 1);
				$log->writeLogMsg($level, $exception, '' , true);
			}
		}
		_errorShow($exception);
	}
}

if(!function_exists('showError'))
{
	//显示错误
	function showError($message , $statusCode = 500, $heading = '')
	{
				
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

if(!function_exists('logMessage'))
{
	//常用日志记录
	function logMessage($level = 'error' , $message , $phpError = false)
	{
		$level = strtolower($level);
		$logLevel = systemConfig('writeLogLevel'); 	
		if (empty($logLevel)) return false;
		if (!in_array($level , $logLevel)) return false;
		$log = Loader::CoreLib('Log' , 1);	
		return $log->writeLogMsg($level , $message , '' , $phpError);
	}
}

if (!function_exists('systemConfig'))
{
	//获取系统配置
	function systemConfig($item)
	{
		$item = trim($item);
		static $configItem = array();
		if (!isset($configItem[$item]))
		{
			$config = &_config();
			if (!isset($config[$item]))
			{
				return false;
			}
			$configItem[$item] = $config[$item];	
		}
		return $configItem[$item];
	}
}

if(!function_exists('_config'))
{
	//载入系统配置
	function &_config($replace = array())
	{
		static $_config;	
		if (isset($_config)) return $_config[0];
		if (!defined('ENVIRONMENT') || !file_exists(BASE_DOCUMENT_ROOT.'config/'.ENVIRONMENT.'/core.cfg.php'))
		{
			$filePath = BASE_DOCUMENT_ROOT.'config/core.cfg.php';	
		}
		if (!file_exists($filePath))
		{
			throw new JYException('The core config file does not exists!');	
		}
		require($filePath);
		if (!isset($config) || !is_array($config))
		{
			throw new JYException('You core config file does not appear to be formatted correctly!');	
		}
		if (count($replace) > 0)
		{
			foreach($replace as $key => $value)
			{
				if(isset($config[$key]))
				{
					$config[$key] = $value;
				}
			}
		}
		return $_config[0] = &$config;
	}
}
