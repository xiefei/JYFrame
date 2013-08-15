<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//数据库操作
class Db
{
	//服务器hash
	public $serverHashFun = 'databaseHash';	
	//选择的服务器
	public $servers;
	//服务器地址
	public $host = '';	
	//用户名
	public $userName = '';	
	//用户密码
	public $passWord = '';
	//数据库名
	public $dbName;
	//数据库hash方法
	public $dbHash = 'dbStgHash';
	//分库例表
	public $splitDb = array();
	//分库连接字符串
	public $splitStr = "_";
	//数据库链接驱动
	public $dbDriver = 'Mysql';
	//是否常连接
	public $pConnection = false;
	//数据库字符集
	public $charSet ='utf8' ;	
	//列编码
	public $dbCollat = 'utf8_general_ci';
	//最大执行sql
	public $maxSql = 1000;
	//是否开启调式
	public $debug = false;
	//当前链接id
	public $linkId;
	//配置
	public  $config;

	//服务器缓存
	public static $serverCacheLinks = array();
	function __construct()
	{
		$this->config = Loader::CoreLib('Config' , 1);
		logMessage('debug' , 'Db class init');
	}

	//载入db
	protected function load($name = 'database')
	{
		$name = trim($name);
		$name = $name === '' ? 'database' : $name;
		//载入配置
		$this->config->load('stg.cfg' , true);	
		$config = $this->config->configItem('stg.cfg');	
		if (false === $config)
		{
			throw new JYException('Unable load Db config file stg.cfg.php');
		}
		if (!isset($config[$name]))
		{
			throw new JYException('Cant find Db configuration '.$name.' items');
		}
		$config = $config[$name];	
		//如果没有servers
		if (!isset($config['servers']) || !is_array($config['servers']))
		{
			throw new JYException('Cant find Db configuration '.$name.' hosts item!');
		}
		$this->servers = $config['servers'];
		$serverHash = isset($config['hash']) && function_exists($config['hash']) ? $config['hash'] : $this->serverHashFun;		
		$this->serverHashFun = $serverHash;
		return $this;
				'host' => 'localhost',
				'userName' => 'root',
				'passWord' => 'root',
				'dbName' => 'test',
				'splitStr' => '_',//库名分割表 test_00
				'splitDb' => array('00' , '01' , '02'), //分库名称
				'dbHash' => 'dbStgHash',//db哈希函数
				'dbDriver' => 'mysql',
				'pConnection' => false,
				'charSet' => 'utf-8'
				'dbCollat' => 'utf8_general_ci'
				'maxSql' => 100,//记录执行最大的sql记录，已方便入log
				'debug' =>true, 
	}
	
	//选择数据库服务器
	public function selectServer($key = '')
	{
		//如果有选择的服务器
		if (!empty($this->servers))
		{
			//只有一台服务器
			if(!isset($this->server['host']) && count($this->server) > 0)
			{
				$server = call_user_func_array($this->serverHashFun, array($key, count($this->servers));	
			} else {
				$server = $this->servers; 
			}	
		} 
		//如果有选择的服务器
		if (isset($server) &&  !empty($server))
		{
			
		}

	}

	//短链接
	protected function connection()
	{
	
	}
	//常链接
	protected function pconnection()
	{

	}
	
	//查询
	protected function query($sql)
	{
		
	}
	//执行
	protected function execute($sql)
	{
		
	}
	
	//查询
	protected function select()
	{

	}

	//入数据
	protected function insert()
	{
	
	}

	//插入的id
	protected function insertId()
	{
	
	}
	//删除
	protected function del()
	{

	}
	//更新
	protected function update()
	{
	
	}

	//开始事务	
	protected function beginTransaction()
	{
	
	}

	//提交事务	
	protected function commitTransaction()
	{

	}

	//回滚事务
	protected function rollBackTransaction()
	{
		
	}
	
	//报错
	protected function error()
	{

	}
}
