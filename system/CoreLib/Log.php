<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//日志处理类
class Log
{
	//读写路径
	private $logPath = 'log';
	//日志时间格式
	private $logFormatTime = 'Y-m-d';
	//写入的时间
	private $logWriteTime = 'Y-m-d H:i:s';
	//写入错误日志分类
	private $logLevel = array(
		'error' => 1 , 'debug' => 2 , 'info' => 3 , 'all' => 4
	);
	//当前需要写入的log类型
	private $currentLogLevel = array();
	//是否启用log
	private $writeEnabled = false;

	function __construct()
	{
		$this->logFormatTime = systemConfig('logFormatTime') ? systemConfig('logFormatTime') : $this->logFormatTime;	
		$this->logPath = systemConfig('logPath') ? systemConfig('logPath') : $this->logPath;   
		$currentWriteLogLevel = systemConfig('writeLogLevel');
		if (!empty($currentWriteLogLevel))
		{
			$currentWriteLogLevel = array_flip($currentWriteLogLevel);	
			$this->currentLogLevel = array_intersect_key($this->logLevel , $currentWriteLogLevel);
		}
		$this->logWriteTime = systemConfig('logWriteTime') ? systemConfig('logWriteTime') : $this->logWriteTime;
	}

	//写入日志
	//$level 写入日志级别 $msg 写入类容 $dir 写入目录 $phpError 是否是php错误
	function writeLogMsg($level = 'error' , $msg = '' , $dir = '' , $phpError = false)
	{
		$level = trim(strtolower($level));
		$writeDir = '';
		//如果没有设置写入日志类型
		if (empty($this->currentLogLevel)) return false;	
		//如果不存在写入的日志类型
		if (!isset($this->currentLogLevel[$level])) return false;
		if (empty($dir)) 
		{
			//两个目录都不存在
			if (empty($this->logPath)) return false;
			$writeDir = is_dir($this->logPath) ? $this->logPath : rtrim(BASE_DOCUMENT_ROOT , '/').'/'.$this->logPath;  
		} else {
			$writeDir = is_dir($dir) ? $dir: rtrim(BASE_DOCUMENT_ROOT , '/').'/'.$dir;  
		}
		//不是可写目录
		if (!isReallyWritable($writeDir))
		{
			return false;
		}		
		$message = '';
		$filePath = rtrim($writeDir,'/').'/log-'.$level.'-'.date($this->logFormatTime).'.php';	
		if (!file_exists($filePath))
		{
			$message .= "<"."?php  if ( ! defined('BASE_DOCUMENT_ROOT')) exit('Access Denied'); ?".">\n\n";	
		}
		 if (!$fp = @fopen($filePath, FOPEN_WRITE_CREATE))
        {   
            return false;
        }   
        $message .= $level.' -- '.date($this->logWriteTime). ' --> '.$msg."\n";
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($filePath, FILE_WRITE_MODE);
        return true;	
	}
	
	//临时写入日志文件
	public static function writeLogTmp($file , $msg , $dir = 'log')
	{
		$dir = is_dir($dir) ? $dir : rtrim(BASE_DOCUMENT_ROOT , '/').'/'.$dir;		
		if (!isReallyWritable($dir)) return false;
		$filePath = rtrim($dir , '/').'/'.$file;
		if (!$fp = fopen($filePath , FOPEN_WRITE_CREATE))
		{
			return false;
		}	
		$msg = 'INFO:' . $msg .'--'.date('Y-m-d H:i:s')."\r\n";
		flock($fp  , LOCK_EX);
		fwrite($fp , $msg);
		flock($fp , LOCK_UN);
		fclose($fp);
		return true;
	}
}

