<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
class FCache 
{
	//过期时间
	public $expires = 3600;
	//默认可读写目录
	public $basePath = 'cache/data';	
	//哈希函数
	public $hashFun = 'md5';
	//目录层级
	public $hashLevel = 2;	
	//目录名称长度	
	public $hashNameLength = 4;
	//config配置类
	public $config = null;
	//服务器例表
	public $servers = array();
	//服务器哈希函数
	public $serverHash = 'fileHash';	

	//静态缓存数据
	private $staticCaches = array();	
		
	//当前时间
	public $currentTime;

	function __construct()
	{
		$this->config = Loader::CoreLib('Config' , 1);	
		$this->currentTime = time();
		logMessage('debug' , 'FCache class init');
	}	

	//加载需要的fcache配置	
	function load($fcacheName = 'fileCache')
	{
		$fcacheName = trim($fcacheName) ? $fcacheName : 'fileCache';
		//载入配置
		$this->config->load('stg.cfg' , true);	
		$config = $this->config->configItem('stg.cfg');	
		if (false === $config)
		{
			throw new JYException('Unable load FCache config file stg.cfg.php');
		}
		if (!isset($config[$fcacheName]))
		{
			throw new JYException('Cant find FCache configuration '.$fcacheName.' items');
		}
		$config = $config[$fcacheName];	
		//如果没有hosts
		if (!isset($config['hosts']) || !is_array($config['hosts']))
		{
			throw new JYException('Cant find FCache configuration '.$fcacheName.' hosts item!');
		}
		$this->servers = $config['hosts'];
		$serverHash = isset($config['hash']) && function_exists($config['hash']) ? $config['hash'] : 'fileHash';		
		$this->serverHash = $serverHash;
		return $this;
	}

	//写入数据
	public function set($name , $value , $expires = -1)
	{
		//选择一台服务器
		$this->selectServer($name);	
		$expires = intval($expires);
		if($expires < 0)
		{
			$expires = $this->currentTime + $this->expires;	
		} else if ($expires == 0) {
			$expires = $this->currentTime + 86400 * 10;
		} else {
			$expires = $this->currentTime + $expires;
		}
		//获取文件路径
		$filePath = $this->getDir($name);
		if($filePah === false)
		{
			logMessage('error' , "Cant get cache {$name} from by hash function retrun null!");	
			return false;
		}
		//创建目录
		if($this->makeDir($filePath) === false)
		{
			logMessage('error' , 'Cant write cache in dir '.$filePath);
			return false;	
		}
		//获取完整路径
		$filePath = $this->getAllPath($filePath);
		//当前进程缓存
		self::$staticCaches[$name] = $filePath;		
		$data = $this->formatData($value);
		$bytes = $this->writeCache($filePath , $data , $expires);
		return $bytes === false ? false : true;
	}
	

	//测试是不有效
	public function expireTest($name)
	{
		$filePath = $this->getDir($name);	
		if($filePah === false)
		{
			logMessage('error' , "Cant get cache {$name} from by hash function retrun null!");	
			return false;
		}
		$filePath = $this->getAllPath($filePath);
		if(file_exists($filePath) && is_readable($filePath))
		{
			if ($fp = @fopen($filePath , FOPEN_READ))
			{
				$bool = $this->isExpires(&$fp);
				fclose($fp);
				return $bool;
			}
		}
		return false;	
	}	

	//获取数据
	public function get($name)
	{
		//选择服务器
		$this->selectServer($name);	
		return $this->readCache($name);	
	}

	public function gets($name)
	{
		$data = array();
		if(!is_array($name)) return array($name => $this->get($name));
		foreach($name as $v)
		{
			$data[$v] = $this->get($v);	
		}
		return $data;
	}	
		

	//删除文件
	public function del($name)
	{
		//选择服务器
		$this->selectServer($name);	
		if (!is_array($name)) return $this->delCache($name);
		$dels = array_map(array($this , 'delCache') ,$name);	
		if (in_array(false , $dels)) return false;
		return true;
	}	


	//清空整个目录
	public function flush($dir = '')
	{
		$basePath = $this->basePath;		
		if (!is_dir($basePath))
		{
			$basePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.$basePath;
		}
		if(!is_dir($basePath)) return false;
		foreach(glob(rtrim($basePath,'/').'/*') as $file)
		{
			if(is_dir($file))
			{
				return $this->flush($file);
			} else {
				@unlink($file);
			}
		}	
		self::$staticCaches = array();
		return  rmdir($file);
	}

	//删除
	public function delCache($name)
	{
		if(isset(self::$staticCaches[$name])){
			$ret = @unlink(self::$staticCaches[$name]);
		 	unset(self::$staticCaches[$name]);
			return $ret;
		}
		$filePath = $this->getDir($name);	
		if($filePah === false)
		{
			logMessage('error' , "Cant get cache {$name} from by hash function retrun null!");	
			return false;
		}
		$filePath = $this->getAllPath($filePath);
		if (!file_exists($filePath)) return true;
		return @unlink($filePath);
	}

	//读取数据
	public function readCacheFile($filePath)
	{
		$data = false;
		if (file_exists($filePath) && is_readable($filePath))
		{
			if (!$fp = @fopen($filePath , FOPEN_READ))
			{
				return false;
			}
			flock($fp, LOCK_EX);
			if ($this->isExpires(&$fp))
			{
				$this->del($name);
				//清空缓存
				isset(self::$staticCaches[$name]) && unset(self::$staticCaches[$name]);
			}  else {
				$length = $this->dataLength(&$fp);
				if ($length == 0) return false;
				$data = fread($fp , $length);
			}	
			flock($fp , LOCK_UN);
			fclose($fp);
		}
		return $data == false ? false : eval($data);
	}

	//读取
	public function readCache($name)
	{
		if(isset(self::$staticCaches[$name]))
		{
			return $this->readCacheFile(self::$staticCaches[$name])); 
		}
		$data = false;
		$filePath = $this->getDir($name);
		if($filePah === false)
		{
			logMessage('error' , "Cant get cache {$name} from by hash function retrun null!");	
			return false;
		}
		$filePath = $this->getAllPath($filePath);
		if (file_exists($filePath) && is_readable($filePath))
		{
			if (!$fp = @fopen($filePath , FOPEN_READ))
			{
				return false;
			}
			flock($fp, LOCK_EX);
			if ($this->isExpires(&$fp))
			{
				$this->del($name);
				isset(self::$staticCaches[$name]) && unset(self::$staticCaches[$name]);
			}  else {
				$length = $this->dataLength(&$fp);
				if ($length == 0) return false;
				$data = fread($fp , $length);
			}	
			flock($fp , LOCK_UN);
			fclose($fp);
		}
		return $data === false ? false:eval($data); 
	}	

	//是否失效
	public function isExpires(&$fp)
	{
		if(!is_resource($fp)) return false;
		$time = fread($fp , 4);
		$time = unpack('I' , $time);
		return $time[1] < $this->currentTime;
	}

	public function dataLength(&$fp)
	{
		if(!is_resource($fp)) return 0;
		$totalLength = fread($fp , 4);
		$totalLength = unpack('I' , $totalLength);
		return $totalLength[1];
	}	

	//获取完整的路径
	public function getAllPath($filePath)
	{
		$basePath = $this->basePath;		
		if (!is_dir($basePath))
		{
			$basePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.$basePath;
		}	
		return rtrim($basePath , '/').'/'.ltrim($filePath , '/');
	}	

	//写入缓存数据	
	public function writeCache($filePath , $data , $expires = 3600)
	{
		if (!$fp = @fopen($filePath , FOPEN_WRITE_CREATE_APPEND))
		{
			return false;
		}
		$total = 0;
		flock($fp , LOCK_EX);
		//写入时间
		$total = fwrite($fp , pack(I , $expires));
		//写入长度	
		$total += fwrite($fp , pack(I , strlen($data));	
		$total += fwrite($fp , $data);
		flock($fp , LOCK_UN);
		fclose($fp);
		return $total; 
	}
	
	//格式化数据
	public function formatData($data)
	{
		$data = var_export($data ,true);	
		return "return {$data};";
	}
	
	//获取目录
	public function getDir($name)
	{
		$key = call_user_func_array($this->hashFun , array($name));
		if ($key === false) return false;
		$totalCount = $this->hashLevel * $this->hashNameLength;
		//如果字符串不够提取
		if (strlen($key) < $totalCount)
		{
			//重复一个字符串填充到指定的位数
			$n = ceil($totalCount / $key);
			$key = str_repeat($key , $n);
		}
		$filePath = '';
		$pos = 0;
		for($i = 0 ; $i < $this->hashLevel; $i++)
		{
			$dir = substr($key , $pos , $this->hashNameLength);	
			$filePath.= $dir .'/';
			$pos += $this->hashNameLength;
		}
		$filePath.= substr($key , $pos);	
		return $filePath;
	}
	
	//创建目录
	public function makeDir($filePath)
	{
		$basePath = $this->basePath;
		if (!is_dir($basePath))
		{
			$basePath = rtrim(BASE_DOCUMENT_ROOT,'/').'/'.$basePath;	
		}
		//根本不存在主目录
		if (!is_dir($basePath)) return false;	
		//主目录根本不可写	
		if (!isReallyWritable($basePath)) return false;
		$dir = substr($filePath , 0 , strrpos('/' , $filePath));
		return @mkdir(rtrim($basePath , '/').'/'.$dir, FILE_WRITE_MODE , true);
	}

	//选择服务器
	public function selectServer($name)
	{	//如果有选择的服务器
		if (!empty($this->servers))
		{
			//只有一台服务器
			if(!isset($this->server['path']) && count($this->server) > 0)
			{
				$server = call_user_func_array($this->serverHash , array($name , count($this->servers));	
			} else {
				$server = $this->servers; 
			}	
		} 
		//如果有选择的服务器
		if (isset($server) &&  !empty($server))
		{
			$this->expires = isset($server['expires']) ? $server['expires'] : $this->expires;		
			$this->basePath = isset($server['path']) ? $server['path'] : $this->basePath;		
			$this->hashFun = isset($server['hashFun']) && function_exists($server['hashFun'])? $server['hashFun'] : $this->hashFun;		
			$this->hashLevel = isset($server['hashLevel']) && $server['hashLevel'] > 0 ? $server['hashLevel'] : $this->hashLevel;		
			$this->hashNameLength= isset($server['hashNameLength']) && $server['hashNameLength'] > 0 ? $server['hashNameLength'] : $this->hashNameLength;		
		}
		$this->servers = array(
			'path' => $this->basePath , 
			'expires' => $this->expires,
			'hashFun' => $this->hashFun,
			'hashLevel' => $this->hashLevel,
			'hashNameLength' => $this->hashNameLength,
		);
		return $this->servers;	
	}
	
}
