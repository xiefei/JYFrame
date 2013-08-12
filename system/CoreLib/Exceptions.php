<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
class JYException extends Exception
{
	public function __construct($message = null , $code = 0)
	{
		parent::__construct($message , $code);
	}
	public function __toString()
	{
		return __CLASS__.":[{$this->getMessage()}][{$this->getCode()}]:File:[{$this->getFile()}]:Line:[{$this->getLine()}]";	
	}
}
//异常类管理
class Exceptions
{
	private $obLevel;
	public $levels = array(
			E_ERROR				=>	'Error',
			E_WARNING			=>	'Warning',
			E_PARSE				=>	'Parsing Error',
			E_NOTICE			=>	'Notice',
			E_CORE_ERROR		=>	'Core Error',
			E_CORE_WARNING		=>	'Core Warning',
			E_COMPILE_ERROR		=>	'Compile Error',
			E_COMPILE_WARNING	=>	'Compile Warning',
			E_USER_ERROR		=>	'User Error',
			E_USER_WARNING		=>	'User Warning',
			E_USER_NOTICE		=>	'User Notice',
			E_STRICT			=>	'Runtime Notice'
	);
	public function __construct()
	{
		$this->obLevel = ob_get_level();
	}

	//将错误写入日志
	function logExceptions($errNo , $errStr , $file , $line)
	{
		$errNo = isset($this->levels[$errNo]) ? $this->levels[$errNo] : $errNo;	
		//写入日志
		logMessage('error' , 'level:'.$errNo.'-->'.$errStr.' '.$file.' '.$line , true);
	}

	//404错误
	function showNotFind($page = '' , $logError = false)
	{
		$heading = '404 Page Not Found';
		$message = 'The page you requested was not found!';
		if($logError)
		{
			logMessage('error' , $heading.' -->'.$page);
		}
		echo $this->showError($heading , $message , 'error_404' , 404);
		exit();
	}

	//显示错误
	function showError($heading , $message , $tpl , $statusCode = 500)
	{
		//输出错误头
		setStatusHeader($statusCode);	
		$message = '<p>'.implode('</p><p>' , (!is_array($message))?array($message):$message).'</p>';
		if (ob_get_level() > $this->obLevel+1)
		{
			ob_end_flush();
		}
		$view = Loader::CoreLib('View',1);
		ob_start();

		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	//php错误
	function showPhpError($errNo , $errStr , $file , $line)
	{
		$errNo = isset($this->levels[$errNo]) ? $this->levels[$errNo] : $errNo;	
		$filePath = str_replace("\\" , '/' , $file);
		if (false !== strpos($filePath , '/'))
		{
			$x = explode('/' , $filePath);	
			$filePath = $x[count($x)-2].'/'.end($x);
		}
		if (ob_get_level() > $this->obLevel+1)
		{
			ob_end_flush();
		}
		$view = Loader::CoreLib('View',1);
		ob_start();
					
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}

