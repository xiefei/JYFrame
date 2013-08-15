<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
class Output
{
	//最终输出内容
	protected $finalContent = '';
	//cache缓存时间
	protected $cacheExpiresTime = 0;
	//gzip
	protected $gzlib = false;
	//mime头
	protected $mimeTypes = array();

	//设置输出头
	protected $headers = array();
	//是否解析某些特殊的变量
	protected $parseExecVar = false;
	function __construct()
	{
		//gzip是否压缩
		$this->gzlib = @ini_get('zlib.output_compression');	
		$filePath = defined('ENVIRONMENT') ? rtrim(BASE_DOCUMENT_ROOT,'/').'/config/'.ENVIRONMENT.'/minmes.php'):rtrim(BASE_DOCUMENT_ROOT,'/').'/config/minmes.php';
		if (file_exists($filePath))
		{
			$this->mimeTypes = include($filePath);
		}
		$this->cacheExpiresTime = (int)systemConfig('webCacheTime');
		logMessage('debug' , 'Output class init');
	}

	//获取所有类容
	protected function getContent()
	{
		return $this->finalContent;
	}
	
	//设置输出内容
	protected function setContent($content)
	{
		$this->finalContent = $content;
		return $this;
	}
	
	//添加内容
	protected function appendContent($content)
	{
		if (empty($content)) 
		{
			$this->finalContent = $content;
		} else {
			$this->finalContent.=$content;	
		}
		return $this;
	}

	//设置cache时间
	protected function setCacheTime($time)
	{
		$this->cacheExpiresTime = (!is_numeric($time)) ? 0 : $time;
		return $this;
	}

	//设置服务器输出头
	protected function setHeader($header, $replace = TRUE)
    {
        if ($this->gzlib&& strncasecmp($header, 'content-length', 14) == 0)
        {
            return;
        }
        $this->headers[] = array($header, $replace);
        return $this;
    }

	protected function setContenType($mimeType)
    {
        if (strpos($mimeType, '/') === FALSE)
        {
            $extension = ltrim($mimeType, '.');
            if (isset($this->mimeTypes[$extension]))
            {
                $mimeType =& $this->mimeTypes[$extension];

                if (is_array($mimeType))
                {
                    $mimeType = current($mimeType);
                }
            }
        }
        $header = 'Content-Type: '.$mimeType;
        $this->headers[] = array($header, TRUE);
        return $this;
    }	

	protected function setStatusHeader($code = 200, $text = '')
    {
        setStatusHeader($code, $text);
        return $this;
    }

	//输出数据
	protected function display($content = '' , &$runServer = null)
	{
		if($content == '') $content = &$this->finalContent;				
		if (!is_object($runServer) || !$runServer instanceof Run)
		{
			$runServer = Run::Instance();
		}
		$controller = class_exists('Controller') ? Loader::CoreLib('Controller' , 1) : null;
		//如果contrller没有保留_display方法的话，就直接写入到cache中输出
		if ($this->cacheExpiresTime > 0 && $controller != null && !method_exists($controller , '_display'))
		{
			//写入cache
			$this->writeCache($content);	
		}
		//记录运行时间
		$excuteTime = $runServer->enumerateTime('totalExecuteTimeStart', 'totalExecuteTimeEnd');
		if ($this->parseExecVar == true)
		{
			$content = str_replace('{executeTime}' , $executeTime , $content);
			$content = str_replace('{memoryUsage}' , $runServer->enumerateMemory(), $content);
		}
		
		if (systemConfig('compressOutput') == true && $this->gzlib == false)
		{
			if (extension_loaded('zlib'))
			{
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'] , 'gzip'))
				{
					ob_start('ob_gzhandler');
				}
			}
		}

		//设置服务器输出头
		if (count($this->headers) > 0)
		{
			foreach ($this->headers as $header)
			{
				@header($header[0], $header[1]);
			}
		}

		if ($controller == null)
		{
			echo $content;
			logMessage('debug' , 'Final output sent to browsers');
			return true;
		}
		
		if (method_exists($controller , '_display'))
		{
			$controller->_display($content);
		}
		else
		{
			echo $content;	
			logMessage('debug' , 'Final output sent to browsers');
		}
	}

	//写入cache
	protected function writeCache($content)
	{
		$cachePath = systemConfig('webCachePath');						
		if (empty($cachePath)) 
		{
			$cachePath = rtrim(BASE_DOCUMENT_ROOT ,'/').'/cache/web';
		} else {
			if (!is_dir($cachePath)) $cachePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.ltrim($cachePath,'/');
		}
		if (!is_dir($cachePath) || !isReallyWritable($cachePath))
		{
			logMessage('error' , 'Unable to write cache file'.$cachePath);
			return false;
		}
		$url = Loader::CoreLib('Url',1);
		$uri = $url->currentUrl();
		$subDir = hashByMd5SubStr($uri);		
		$cachePath = rtrim($cachePath , '/').'/'.$subDir;
		if (!is_dir($cachePath)) {
			mkdir($cachePath ,FILE_WRITE_MODE); 
		}
		$uri = md5($uri);	
		$cachePath.='/'.$uri;
		if (!$fp = @fopen($cachePath,FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			logMessage('error' , 'Unable to write cache file'.$cachePath);
			return false;
		}
		$expire = time() + ($this->cacheExpiresTime*60);
		if (flock($fp , LOCK_EX))
		{
			fwrite($fp , $expire.'TE--->'.$content);
			flock($fp , LOCK_UN);
		}
		else
		{
			logMessage('error' , 'Unable to secure a file lock for file at :'.$cachePath);	
			return false;
		}
		fclose($fp);
		@chmod($cachePath , FILE_WRITE_MODE);
		logMessage('debug' , 'Cache file write'.$cachePath);
		return true;
	}
	
	//显示cache
	protected function displayCache()
	{
		$cachePath = systemConfig('webCachePath');						
		if (empty($cachePath)) 
		{
			$cachePath = rtrim(BASE_DOCUMENT_ROOT ,'/').'/cache/web';
		} else {
			if (!is_dir($cachePath)) $cachePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.ltrim($cachePath,'/');
		}
		$url = Loader::CoreLib('Url',1);
		$uri = $url->currentUrl();
		$subDir = hashByMd5SubStr($uri);		
		$cachePath = rtrim($cachePath , '/').'/'.$subDir;
		$filePath  = rtrim($cachePath , '/').'/'.md5($uri);
		if(!file_exists($filePath))
		{
			logMessage('error' , 'Cant find cache file'.$filePath);
			return false;
		}
		if(!$fp=@fopen($filePath , FOPEN_READ))
		{
			logMessage('error' , 'Cant read cache file'.$filePath);
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
			return FALSE;
		}
		if (time() >= trim(str_replace('TE--->', '', $match['1'])))
		{
			if (isReallyWritable($cachePath))
			{
				@unlink($filePath);
				logMessage('debug', "Cache file has expired. File deleted");
				return FALSE;
			}
		}
		$this->display(str_replace($match['0'], '', $cache));
		logMessage('debug', "Cache file is current. Sending it to browser.");
		return TRUE;
	}
}

