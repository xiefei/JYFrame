<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
class View
{
	//模板方法
	protected $tplDocument =  array('Tpl' => 'template'); 
	//模板路径	
	protected $tplPath = '';
	//系统模板
	protected $systemTplPath = '';
	//模板缓存时间
	protected $tplCacheTime = 60;
	//模板缓存目录
	protected $tplCachePath = '';
	//是否缓存
	protected $tplCached = true;
	//模板是否采用第三方插件
	protected $tplExt = null;
	//模板传递的变量
	protected $tplVars = array();
	//缓存的层级
	protected $tplLevel;	
	//模板数据
	protected $templateData ;
	function __construct()
	{
		$this->tplPath = rtrim(BASE_DOCUMENT_ROOT , '/').'/'.$this->tplDocument['Tpl'];	
		$this->systemTplPath = rtrim(BASE_DOCUMENT_ROOT , '/').'/CoreTpl';
		$this->tplCacheTime = empty(systemConfig('tplCacheTime')) ? $this->tplCacheTime : systemConfig('tplCacheTime');
		$this->tplCachePath = empty(systemConfig('tplCachePath')) ? $this->tplCachePath: systemConfig('tplCachePath');
		if (empty($this->tplCachePath) || empty(systemConfig('tplCached'))
		{
			$this->tplCached = false; 
		}
		$this->tplLevel = ob_get_level();
	}


	//设置模板数氢
	protected function set($name , $value)
	{
		$this->templateData[$name] = $value;
	}

	//获取模板设置的数据
	protected function get($name = '')
	{
		if (isset($this->templateData[$name]))
		{
			return $this->templateData[$name];
		}
		return $this->templateData;
	}
	
	//设置数据块
	protected function setChunks($name , $file , $argments = array())
	{
		$trunk = $this->load($file , $arguments , true);
		$this->set($name , $trunk);
		return $this;
	}

	//设置公共页面的tpl
	protected function loadTpl($template , $file , $arguments =array() , $retrun = false)
	{
		$this->set('content', $this->load($file, array_merge($this->templateData, $arguments), TRUE));
       	return $this->load($template, $this->templateData, $return);	
	}


	//载入模板
	protected function load($file , $arguments = array(), $return = false)
	{
		return $this->loadView($file , $argments , $return);
	}
	
	//载入系统目录的模板
	protected function systemLoad($file , $arguments  = array() , $return = false)
	{
		return $this->loadView($file , $arguments , $return , 1);
	}

	//设置模板过期时间
	protected function setCacheExpries($time = 0)
	{
		$this->tplCacheTime = is_numeric($time) ? $time : 0;	
		return $this;
	}
	
	//载入模板
	protected function loadView($file , $args ,  $return = false , $isSystem = 0)
	{
		$dir = (int) $isSystem == 0 ? $this->tplPath : $this->systemTplPath;	
		$filePath =  $dir.'/'.$file;
		$fileExt = pathinfo($filePath , PATHINFO_EXTENSION);
		$filePath = $fileExt == '' ? '.php' : $filePath;
		if (!is_file($filePath))
		{
			throw new JYException('Unable to load requested file:'.$filePath.'!');	
		}
		if (is_array($args))
		{
			$this->tplVars = array_merge($this->tplVars , $args);
		}	
		extract($this->tplVars);
		//开始写入缓存
		ob_start();
		if ((bool) @ini_get('short_open_tag') === FALSE AND systemConfig('shortTags') == TRUE)
        {    
            echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($filePath))));
        }
        else
        {
			if ($this->tplCached && $this->tplCacheTime > 0 && $return == true)
			{
				$conent = $this->_readCache($filePath);
				if ($content === false) {
					include($filePath);
				} else {
					echo $content;
				}
			} else {
            	include($filePath);         
			}
		}
        logMessage('debug', 'File loaded: '.$filePath);	
		if ($return == true)
		{
			$buffer = ob_get_contents();
			if ($this->tplCached && $this->tplCacheTime > 0)
			{
				$this->_cache($filePath , $buffer);
			}
			@ob_end_clean();
			return $buffer;
		}
		if (ob_get_level() > $this->tplLevel+1)
		{
			ob_end_flush();
		} else {
			 $output = Loader::CoreLib('Output' , 1);
			 $output->appendContent(ob_get_contents());
			@ob_end_clean();
		}	
	}

	protected function _readCache($tplName)
	{
		if ($this->_cachePath === false) return false;	
		$name = md5($tplPath); 
		$filePath = $this->tplCachePath.'/'.$name;
		//如果缓存的模板存在
		if (file_exists($filePath))
		{
			if (!@$fp = fopen($filePath , FOPEN_READ))
			{
				logMessage('error' , 'Cant read cache tpl '.$filePath);
				return false;
			}
			$cache = ''; 
			if (filesize($filePath) > 0)
			{   
				$cache = fread($fp, filesize($filePath));
			}   
			flock($fp, LOCK_UN);
			fclose($fp);
			if (!preg_match("/(\d+TE--->)/", $cache, $match))
			{   
				return false;
			}   
			if (time() >= trim(str_replace('TE--->', '', $match['1'])))
			{   
				if (isReallyWritable($this->tplCachePath))
				{   
					@unlink($filePath);
					logMessage('debug', "Tpl Cache file has expired. File deleted");
					return false;
				}   
			}
			return str_replace($match['0'], '', $cache);
		}
		return false;
	}

	//模板缓存
	protected function _cache($tplName , $content = '')
	{
		if ($this->_cachePath === false) return false;	
		$name = md5($tplPath); 
		$filePath = $this->tplCachePath.'/'.$name;
		$expire = time() + $this->tplCacheTime * 60;
		if (!$fp = fopen($filePath , FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			logMessage('error' , 'Unable to write cache tpl '.$filePath);	
			return false;
		}
		flock($fp , LOCK_EX)
		fwrite($fp , $expire.'TE--->'.$content);
		flock($fp , LOCK_UN);
		fclose($fp);
	}

	protected function _cachePath()
	{
		if (empty($this->tplCachePath))
		{
			$this->tplCachePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/cache/tpl';
		} else {
			if (!is_dir($this->tplCachePath))
			{
				$this->tplCachePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.ltrim($this->tplCachePath,'/'); 
			}
		}	
		if(!isReallyWritable($this->tplCachePath))
		{
			logMessage('error' , 'Tpl cache document don\'t write!' . $this->tplCachePath);
			return false;	
		}
		return rtrim($this->tplCachePath,'/');
	}

	//对像转换成数组	
	protected function objectToArray($obj)
	{
		return is_object($obj) ? get_object_vars($obj) : $obj;
	}
}


