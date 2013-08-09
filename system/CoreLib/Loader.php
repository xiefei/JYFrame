<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//载入相关文件类
//载入各层文件分类
class Loader
{
	private static  $loader = null;
	//网站目录
	private $baseRoot = '';
	//系统文件目录
	private $systemRoot = '';

	//缓存类文件
	private static $_classes = array();

	//文件目录分类
	private $fileDocType = array(
		'Ctl' => 'ctl', 
		'Stg' => 'stg', 
		'Mdl' => 'mdl', 
		'Cache' => 'cache', 
		'Data' => 'data', 
		'Cfg' => 'config', 
		'Lib' => 'lib', 
		'Comm' => 'comm', 
		'TMP' => 'template', 
		'CoreLib' =>'CoreLib',
		'CoreStg' =>'CoreStg',
		'CoreComm' =>'CoreComm',
	);

	function __construct($baseRoot = BASE_DOCUMENT_ROOT , $systemRoot = SYSTEM_ROOT)
	{
		$this->baseRoot 	= 	$baseRoot;		
		$this->systemRoot	= 	$systemRoot;		
	}
	
	//获取对像静态实例
 	public static function Instance()
	{
		if (self::$loader == NULL)
		{
			self::$loader = new Loader();
		}
		return self::$loader;
	}

	//动态调用常用方法
	public function __call($funName , $funArguments)
	{
		$funName = trim($funName);
		$className = false;
		if (!isset($this->fileDocType[$funName]) || empty($funArguments))
		{
			throw new JYException("can't load $funName class file" , E_ERROR);
		}
		list($fileName , $type) = $funArguments;	
		$fileName = trim($fileName);
		$classKeyName = strtolower($fileName);
		if (isset(self::$_classes[$classKeyName])) return self::$_classes[$classKeyName];
		$fileType = $this->fileDocType[$funName];
		$basePath = empty($type) ? $this->baseRoot : $this->systemRoot;
		$filePath = $basePath.'/'.$fileType.'/'.$fileName.'.php';
		if (file_exists($filePath))
		{
			$content = require($filePath);
			var_dump($config);
			$className = explode('.' , $fileName);
			$className = ucfirst($className[0]);
			if (class_exists($className , false))
			{
				self::$_classes[$classKeyName] = new $className;
				return self::$_classes[$classKeyName];
			}
			return $content; 
		}
		return false;
	}

	//静态调用一个方法使用时 5.3
	public static function __callStatic($classFunName , $classFunArguments)
	{
		$loader =  self::Instance();
		return $loader->__call($classFunName , $classFunArguments);
	}
	
	//自动载入器类
	public static function autoload($className)
	{
		var_dump($className);	
	}

	//获取所有载入的类
	public static function classes()
	{
		return self::$_classes;
	}

	//配置选项
	public static function Config($file)
	{
		$loader =  self::Instance();
		$loader->__call('Cfg' , array($file));
		var_dump($config);
		if (isset($config))
		{
			return $config;
		}
	}

	//数据选项
	public static function Data()
	{
		
	}
}

