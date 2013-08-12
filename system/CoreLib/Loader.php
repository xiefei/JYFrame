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

	//级存comm
	private static $_comms = array();

	//系统文件
	protected $fileDocCore = array(
		'CoreLib' =>'CoreLib',
		'CoreStg' =>'CoreStg',
	);

	//系统公共函数目录
	protected $fileDocFun= array(
		'CoreComm' =>'CoreComm',
		'Comm' => 'comm',
	);

	//前端mvc文件格式
	protected $fileDocMvc = array(
		'Ctl' => 'ctl', 
		'Stg' => 'stg', 
		'Mdl' => 'mdl', 
		'Lib' => 'lib', 
		'Core' => 'core',
	);

	//静态资源文件
	protected $fileDocStatic = array(
		'Cache' => 'cache', 
		'Cfg' => 'config', 
		'Data' => 'data', 
		'Tpl' => 'template', 
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
			self::$loader->CoreLib(__CLASS__ , 1);
		}
		return self::$loader;
	}

	//动态调用常用方法
	public function __call($funName , $funArguments)
	{
		if (isset($this->fileDocFun[$funName]))
		{	
			list($file , $type) = $funArguments;
			return $this->loadCommClass($file , $type);
		}
		return $this->loadClass($funName , $funArguments);	
	}

	//载入类
	protected function loadClass($funName , $funArguments)
	{
		$funName = trim($funName);
		$className = false;
		$fileDocType = array_merge($this->fileDocCore , $this->fileDocMvc);
		if (!isset($fileDocType[$funName]) || empty($funArguments))
		{
			throw new JYException("can't load $funName class file" , E_ERROR);
		}
		list($fileName , $type) = $funArguments;	
		$fileName = trim($fileName);
		$name = explode('/' , $fileName);
		$name = explode(',' , $name[0]);
		$name = ucfirst($name[0]);
		$classKeyName = strtolower($name);
		if (isset(self::$_classes[$classKeyName])) return self::$_classes[$classKeyName];
		$fileType = $fileDocType[$funName];
		$basePath = empty($type) ? $this->baseRoot : $this->systemRoot;
		$basePath = rtrim($basePath , '/');
		$filePath = $basePath.'/'.$fileType.'/'.$fileName.'.php';
		if (file_exists($filePath))
		{
			$className = $name; 
			if (!class_exists($className , false))
			{
				require($filePath);
				logMessage('debug' , 'Loader load class:'.$filePath);
			}
		}
		//重载内核相关的类
		$appCore = systemConfig('core');
		if($type && $appCore) {
			if (!is_dir($appCore)) $appCore = rtrim($this->baseRoot , '/').'/'.$appCore;
			$name = systemConfig('subClassPrefix').$className;
			if (file_exists($appCore.'/'.$name.'.php'))
			{
				if (!class_exists($name ,false))
				{
					$className = $name;
					$classKeyName = strtolower($name);
					require($appCore.'/'.$name.'.php');	
					logMessage('debug' , 'Loader load class:'.$appCore.'/'.$name.'.php');
				}		
			}
		}
		if($className == false)
		{
			throw new JYException('cant loaded file '.$filePath.'!' , E_ERROR);
		}
		//如果是Loader对像本身的话
		if (strtolower($fileName) == strtolower(__CLASS__) && $className == __CLASS__)
		{
			self::$_classes[$classKeyName] = self::$loader; 
		} else {
			self::$_classes[$classKeyName] = new $className; 
		}
		return self::$_classes[$classKeyName];
	}

	//静态调用一个方法使用时 5.3
	public static function __callStatic($classFunName , $classFunArguments)
	{
		$loader =  self::Instance();
		return $loader->__call($classFunName , $classFunArguments);
	}

	//自动导入
	protected function autoLoadClass($className)
	{
		$fileDocType = array_merge($this->fileDocCore , $this->fileDocMvc);	
		$fileDocStr = array_keys($fileDocType);
		$fileDocStr = implode('|' , $fileDocStr);
		preg_match("/^.*?($fileDocStr)$/" , $className, $matches);
		if (empty($matches))
		{
			throw new JYException('cant loaded file '.$className.'.php!' , E_ERROR);	
		}
		$fileType = $matches[1];
		$fileType = $fileDocType[$fileType];
		foreach(array($this->baseRoot , $this->systemRoot) as $path)
		{
			$path = rtrim($path , '/');
			$path = $path.'/'.$fileType.'/'.strtolower($className).'.php';
			if (file_exists($path))
			{
				if (!class_exists($className , false))
				{
					require($path);	
					logMessage('debug' , 'Auto Load class:'.$path);
				}
			}
		}
		unset($fileDocType);
	}

	//自动载入器类
	public static function autoLoad($className)
	{	
		$loader =  self::Instance();
		$loader->autoLoadClass($className);
	}

	//获取所有载入的类
	public static function classes()
	{
		return self::$_classes;
	}

	//获取所有comm
	public static function comms()
	{
		return self::$_comms;
	}

	//载入Comm文件类型函数
	protected function loadCommClass($fileName = array() , $type = 0)
	{
		$fileDocFun = $this->fileDocFun;
		$fileNames = array();
		if(is_string($fileName)) {
			$fileName = strtolower(trim($fileName));
			$fileNames[] = $fileName;
		}
		$baseRoot = empty($type) ? rtrim($this->baseRoot , '/').'/comm/':rtrim($this->systemRoot,'/').'/CoreComm/';
		foreach($fileNames as $file)
		{
			$file = strtolower($file);
			//已经存在导入的文件
			if (isset(self::$_comms[$file])) continue;
			$path = $baseRoot.$file.'.php';
			if (!file_exists($path))
			{
				throw new JYException('Unable to load requested file:'.$path.'!');	
			}
			require_once($path);
			self::$_comms[$file] = true;
			logMessage('debug' , 'Comm loaded'.$path);
			continue;
		}
		return true;
	}
}
