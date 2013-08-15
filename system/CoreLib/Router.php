<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//编写路由转换规则
class Router
{
	//配置对像
	public $config;
	//url配置
	public $url;
	//路由规则
	public $route;
	//类
	public $class;
	//方法
	public $method;
	//目录
	public $dir;
	//默认controller
	public $defaultController;
	//默认方法
	public $defaultMethod;
	//contrller目录	
	protected $controller = array('Ctl' => 'ctl');
	
	function __construct()
	{
		$this->config = Loader::CoreLib('Config' , 1);
		$this->url = Loader::CoreLib('Url' , 1);	
		logMessage('debug' , 'Router class init');
	}

	//设置路由
	protected function setRouter
	{
		$segments = array();	
		//如果启用?c=xx&m=xx
		if ($this->config->configItem('enableQueryStrings') == true && isset($_GET[$this->config->configItem('controllerTrigger'])))
		{
			if (isset($_GET[$this->config->configItem('directoryTrigger')]))
			{
				$dir = trim($this->url->filterUrl($_GET[$this->config->configItem('directoryTrigger')]));
				$this->setDir($dir);
				$segments[] = $this->getDir();
			}		
			if (isset($_GET[$this->config->configItem('controllerTrigger']))
			{
				$class = trim($this->url->filterUrl($_GET[$this->config->configItem('controllerTrigger')]));
				$this->setClass($class);
				$segments[] = $this->getClass();
			}
			if (isset($_GET[$this->config->configItem('functionTrigger')]))
			{
				$method = trim($this->url->filterUrl($_GET[$this->config->configItem('functionTrigger'])));
				$this->setMethod($method);
				$segments[] = $this->getMethod();
			}
		}
		$routes = $this->config->configItem('routes');
		$this->route = empty($routes) ? array() : $routes;
		$this->defaultController = $this->config->configItem('defaultController');
		$this->defaultMethod= $this->config->configItem('defaultMethod');
		if (empty($this->defaultController)) $this->defaultController = false;
		if (count($segments) > 0)
		{
			//处理url
			return $this->validateRequest($segments);	
		}
		//获取url
		$this->url->fetchUrl();
		$urlString = $this->url->getUrlPath();		
		if (empty($urlString))
		{
			$this->setDefaultController();		
		}
		$this->url->explodeSegments();	
		$this->parseRoute();
		$this->reIndexSegments();
	}
	
	//解析路由规则
	protected function parseRoute()
	{
		$segments = $this->url->getSegments();
		$this->setRequest($segments);
	}
	
	//设置默认Controller
	protected function setDefaultController()
	{
		if (empty($this->defaultController))
		{
			throw new JYException('Not have default Controller!');
		}
		if (strpos($this->defaultController,'/') !== false)
		{
			$c = explode('/' , $this->defaultController);
			$this->setClass($c[0]);
			$this->setMethod($c[1]);
			$this->validateRequest($c);
		} else {
			$this->setClass($this->defaultController);
			$this->setMethod($this->defaultMethod);
			$this->validateRequest(array($this->defaultController , $this->defaultMethod));
		}
		logMessage('debug' , 'no url request set default controller!');
	}

	protected function setRequest($segments = array)
	{
		$segments = $this->validateRequest($segments);
		if (count($segments) == 0)
		{
			$this->setDefaultController();
		}
		$this->setClass($segments[0]);	
		if (isset($segments[1]))
		{
			$this->setMethod($segments[1]);
		} else {
			$this->setMethod($this->defaultMethod);
		}
	}
	
	//验证请求
	protected function validateRequest($segments)
	{
		if(count($segments) == 0) return $segments;	
		$filePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.$this->controller['Ctl'].'/';
		if (file_exists($filePath.$segments[0].'.php'))
		{
			return $segments;
		}
		if (is_dir($filePath.$segments[0]))
		{
			$this->setDir($segments[0]);
			array_shift($segments);
			//获取类名与方法名
			if (count($segments) > 0)
			{
				$filePath = $filePath.$this->dir.$segments[0].'.php';
				//如果文件不存在
				if (!file_exists($filePath))
				{
					return $this->show404Controller($filePath);				
				}
			//如果获取到的数据等于0,就得使用默认的controller
			} else {
				if (strpos($this->defaultController,'/') !== false)
				{
					$c = explode('/' , $this->defaultController);
					$this->setClass($c[0]);
					$this->setMethod($c[1]);
				} else {
					$this->setClass($this->defaultController);
					$this->setMethod($this->defaultMethod);
				}
				$filePath = $filePath.$this->dir.$this->defaultController.'.php';
				if (!file_exists($filePath))
				{
					$this->setDir('');
					return array();
				}	
			}
			return $segments;
		}
		return $this->show404Controller($segments[0]);		
	}

	//404找不到controller
	private function show404Controller($file)
	{
		$error404 = $this->config->configItem('error404');				
		//如果没有自定义404
		if(empty($error404))
		{
			show404($file);	
		} else {
			if (is_string($error404))
			{
				$error404 = explode('/' , $error404);
			}
			if (count($error404) > 2)
			{
				$this->setDir($error404[0]);
				$this->setClass($error404[1]);
				$this->method($error404[2]);
				return $error404;
			}
			$this->setClass($error404[0]);
			$this->setMethod(isset($error404[1])?$error404[1]:$this->config->configItem('defaultFunction'));
			return $error404;
		}
	}

	//设置方法
	protected function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}
	//获取方法
	protected function getMethod()
	{
		return $this->method;
	}

	//获取类名
	protected function getClass()
	{
		return $this->class;
	}

	//设置类
	protected function setClass($class)
	{
		$class = trim($class);
		$class = str_replace(array('/' . '.') , '' , $class);
		$this->class = $class;
		return $this;	
	}	
	//设置目录
	protected function setDir($dir)
	{
		$dir = trim($dir);
		$dir = str_replace(array('/' , '.') , '' , $dir).'/';
		$this->dir = $dir;
		return $this;
	}

	//获取目录	
	protected function getDir()
	{
		return $this->dir;
	}	
}
	
