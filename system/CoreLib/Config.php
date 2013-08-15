<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
class Config
{
	//所有载入的配置选项
	protected static $configItems = array();	
	//所有载入的文件
	protected static $configFiles = array();	
	//配置目录	
	protected $configDocument = array('Cfg' => 'config');
	//初始化配置
	function __construct()
	{
		self::$configItems =&_config();		
		logMessage('debug','Config class init');
		if(self::$configItems['baseUrl'] == '')
		{
			//获取服务器请求头
			if (isset($_SERVER['HTTP_HOST']))
			{
				$webSiteUrl = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !=='off' ? 'https' : 'http';
				$webSiteUrl.='://'.$_SERVER['HTTP_HOST'];
				$webSiteUrl.= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			}
			else
			{
				$webSiteUrl = 'http://localhost/';
			}
			$this->setConfigItem('baseUrl' , $webSiteUrl);
		}	
		if (!isset(self::$configItems['indexPage'])) {
			$this->setConfigItem('indexPage' , 'index.php');
		}
	}
	
	//设置配置项
	protected function setConfigItem($item , $v , $index = '')
	{
		if ($index !== '' && isset(self::$configItems[$index]))
		{
			self::$configItems[$index][$item] = $v;		
		} else {
			self::$configItems[$item] = $v;		
		}
	}

	//批量置入数据
	protected function assignToConfig($items = array() , $index = '')
	{
		if(is_array($items))
		{
			foreach($items as $k => $v)
			{
				$this->setConfigItem($k , $v , $index);
			}	
		}	
	}

	//载入配置文件
	//$file 配置文件 $userSections 是否分模块配置 $failRetrun 载入没有的文件是否正常返回false
	protected function load($file  = '', $useSections = false , $failRetrun = false)
	{
		if (empty($file)) return false;
		$fileDir = defined('ENVIRONMENT') ? array($this->configDocument['Cfg'].'/'.ENVIRONMENT , $this->configDocument['Cfg']) : array($this->configDocument['Cfg']);	
		$loaded  = false;
		$found = false;
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		$fileName = empty($ex) ? $file.'.php' : $file;
		foreach($fileDir as $path)
		{
			$path = rtrim($fileDir , '/');
			$filePath = rtrim(BASE_DOCUMENT_ROOT , '/').'/'.$path.'/'.$file;	
			$filePath = empty($ext) ? $filePath.'.php' : $filePath;  
			if (in_array(self::$configFiles , $filePath , true))
			{
				$loaded = true;
				break;
			}
			if(file_exists($filePath)) {
				include($filePath);
				if (!isset($config) || !is_array($config))
				{
					if ($failRetrun === true) return false;
					throw new JYException('Your '.$fileName.' file does not appear to contain a valid configuration array.');	
				}
				if ($userSections === true)
				{
					if (isset($this->config[$file]))
					{
						self::$configItems[$file] = array_merge(self::$configItems[$file] , $config);
					}
					else
					{
						self::$configItems[$file] = $config;
					}
				}
				else
				{
					self::$configItems = array_merge(self::$configItems , $config);	
				}
				self::$configFiles[] = $filePath; 
				unset($config);
				$loaded = true;
				logMessage('debug' , 'Config file loaded '.$filePath);	
				break;
			}
		}
		if ($loaded == false)
		{
			if(true === $failRetrun) return false;
			throw new JYException('The configuration file '.$fileName.' does not exist.');		
		}
		return true;
	}	
	
	//获取配置项
	protected function configItem($item , $index = '')
	{
		if ($index === '')
		{
			return isset(self::$configItems[$item]) ? self::$configItems[$item] : false;
		} else {
			if (isset(self::$configItems[$index]) && isset(self::$configItems[$index][$item]))
			{
				return self::$configItems[$index][$item];
			}	
		}	
		return false;
	}

	//转换item
	protected function slashItem($item)
    {
        if (!isset(self::$configItems[$item]))
        {
            return false;
        }
        if(trim(self::$configItems[$item]) == '')
        {
            return '';
        }

        return rtrim(self::$configItems[$item], '/').'/';
    }
	
	//获取网站路径
	protected function siteUrl($uri = '')
	{
		if ($uri == '') return $this->slashItem('baseUrl').$this->configItem('indexPage');	
		if ($this->configItem('enableQueryStrings') == false)
		{
			return $this->slashItem('baseUrl').$this->slashItem('indexPage').$this->uriToString($uri);
		}
		return $this->slashItem('baseUrl').$this->configItem('indexPage').'?'.$this->uriToString($uri);	
	}	
	
	//获取网站访问地址
	protected function baseUrl($uri = '')
	{
		$uriStr = ltrim($this->uriToString($uri) , '/');
		if ($this->configItem('enableQueryStrings') == true && is_array($uri))
		{
			$uriStr = '?'.$uriStr; 
		}
		return $this->slashItem('baseUrl').$uriStr;	
	}

	//获取静态文件路径
	protected function staticUrl($uri = '' , $domain = false)
	{
		$uriStr = ltrim($this->uriToString($uri) , '/');
		if ($this->configItem('enableQueryStrings') == true && is_array($uri))
		{
			$uriStr = '?'.$uriStr; 
		}
		//如果有初始域传入
		if ($domain == true)
		{
			//进行静态文件cdn的处理
		}
		return $this->slashItem('staticUrl').$uriStr;	
	}

	//uri地址转换成字符串	
	protected function uriToString($uri)
    {
        if ($this->configItem('enableQueryStrings') == FALSE)
        {
            if (is_array($uri))
            {
                $uri = implode('/', $uri);
            }
            $uri = trim($uri, '/');
        }
        else
        {
            if (is_array($uri))
            {
				$uri = http_build_query($uri);
             }
        }
        return $uri;
    }	

}


