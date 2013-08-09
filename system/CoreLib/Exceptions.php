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
		return __CLASS__.":[{$this->getCode()}]:File:[{$this->getFile()}]:Line:[{$this->getLine()}]";	
	}
}
//异常类管理
class Exceptions
{
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

	}
}

