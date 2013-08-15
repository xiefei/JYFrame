<?php if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
require_once(SYSTEM_ROOT.'/CoreComm/Global.php');
require_once(SYSTEM_ROOT.'/CoreLib/Exceptions.php');
require_once(SYSTEM_ROOT.'/CoreLib/Loader.php');
class Run
{
	public static $runWeb = null;
	//标记点
	public $marker = array();	
	public $router = null;	
	public function __construct()
	{
		$this->router = Loader::CoreLib('Router' , 1);	
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
		$runWeb->markPoint('totalExecuteTimeStart');
		$abc = $d;
		$myCtl = new MyCtld();

		$runWeb->excuteController();	
		$output = Loader::CoreLib('Output' , 1);
		$output->display('' , $runWeb);	
		//$configs = Loader::Comm('default');
				
	}
	public function excuteController()
	{
		$base = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.$this->router->getDir().$this->router->getClass().'.php';	
		include($base);
		$class = $this->router->getClass();
		$fun = $this->router->getMethod();
		$controller = new $class();
		$controller->$fun();
	}

	//记录数据点
	protected function markPoint($pointName)
	{
		$this->marker[$pointName] = microtime();
	}
	
	//计算两点之间的时间比较
	protected function enumerateTime($pointName = '' , $pointName2 = '' , $decimals = 4)
	{
		if ($pointName == '') return '{enumerateTime}';			
		if (!isset($this->marker[$pointName])) return '';
		if (!isset($this->marker[$pointName2])) $this->markPoint($pointName2);
		list($sm , $ss) = explode(' ',$this->marker[$pointName]);
		list($em , $es) = explode(' ',$this->marker[$pointName2]);
		return number_format(($em+$es) - ($sm+$ss) , $decimals);
	}

	//计算当前获取到的内存
	protected function enumerateMemory()
	{
		return (!function_exists('memory_get_usage')) ?'0':round(memory_get_usage()/1024/1024, 2).'MB'; 
	}
}
//自动装载函数
$autoSP = spl_autoload_functions();
//如果没有注册过自动载入器，则自行注册载入器
if($autoSP == false || in_array('__autoload' , $autoSP))
{
	spl_autoload_register(array('Loader' , 'autoLoad'));
}

