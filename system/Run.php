<?php if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
require_once(SYSTEM_ROOT.'/CoreComm/Global.php');
require_once(SYSTEM_ROOT.'/CoreLib/Exceptions.php');
require_once(SYSTEM_ROOT.'/CoreLib/Loader.php');
class Run
{
	public static $runWeb = null;
	public function __construct()
	{
		
	}
	public static function Instance()
	{
		if(self::$runWeb == null)
		{
			self::$runWeb = new Run();
		}
		return self::$runWeb;
	}

	public static function runServer($defCtl)
	{
		//载入Exceptions
		Loader::CoreLib('Exceptions' , 1);
		//错误处理
		set_exception_handler('_exceptionHandler');
		set_error_handler('_errorHandler');

		$classes = Loader::classes();
		//var_dump($classes);
		$runWeb = self::Instance();
		$abc = $d;
		$myCtl = new MyCtld();
		//$configs = Loader::Comm('default');
				
	}
}
//自动装载函数
$autoSP = spl_autoload_functions();
//如果没有注册过自动载入器，则自行注册载入器
if($autoSP == false || in_array('__autoload' , $autoSP))
{
	spl_autoload_register(array('Loader' , 'autoLoad'));
}

