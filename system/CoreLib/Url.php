<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//处理url转换
class Url
{
	protected $urlString = '';
	protected $config;
	public $segments = array();
	function __construct()
	{
		$this->config = Loader::CoreLib('Config' , 1);	
		logMessage('debug' , 'Url class init');
	}
	
	//获取url地址
	protected function fetchUrl()
	{
		// a/b
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');	
		if (trim($path , '/') != '' && $path != "/".DEFAULT_URL) {
		{
			$this->setUrlPath($path);	
			return true;
		}
		//?aa=bb
		$path = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if (trim($path , '/') != '')
		{
			$this->setUrlPath($path);
			return true;
		}
		//_GET['a'] = b
		if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET) , '/') !='')
		{
			$this->setUrlPath(key($_GET));
			return true;
		}
		$this->urlString = '';
		return false;
	}

	//过滤
	protected function filterUrl($str)
	{
		$bad    = array('$','(',')','%28','%29');
        $good   = array('&#36;','&#40;','&#41;','&#40;','&#41;');
        return str_replace($bad, $good, $str);
	}	
	
	//分解url
	public function explodeSegments()
	{
		foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->urlString)) as $val)
		{
			$val = trim($this->filterUrl($val));
			if ($val != '')
			{
				$this->segments[] = $val;
			}
		}		
	}

	//重新排序
	function reIndexSegments()
    {
        array_unshift($this->segments, NULL);
        unset($this->segments[0]);
    }
	
	//获取当前url
	protected function currentUrl()
	{
		return $this->urlString;		
	}
	
	//转换路由
	protected function getSegments()
	{
		return $this->segments;	
	}
	
	//获取链接地址
	protected function getUrlPath()
	{
		return $this->urlString;
	}

	//设置url	
	protected function setUrlPath($path)
	{
		$this->urlString = ($path == '/') ? '' :$path;		
		return $this;
	}
}

