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

	//载入模板
	protected function load($file , $arguments = array(), $return = false)
	{
		
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
            include($filePath);         
		}
        logMessage('debug', 'File loaded: '.$filePath);	
		if ($return == true)
		{
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
		if (ob_get_level() > $this->tplLevel+1)
		{
			ob_end_flush();
		} else {
			$buffer = ob_get_contents();
			@ob_end_clean();
		}	
	}

	//对像转换成数组	
	protected function objectToArray($obj)
	{
		return is_object($obj) ? get_object_vars($obj) : $obj;
	}
}


