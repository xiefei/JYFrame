<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');
//存储配置项

//文件存储
$config['fileCache'] = array(
	'hash' => 'fileHash', //获取hash服务器的地址
	'hosts' =>array(
		//path 文件目录 expires 文件有效期 hashLevel 缓存目录级别 hashNameLength 目录名长度 hashfun hash函数
		array('path' => 'cache' , 'expires' => 60000 , 'hashFun' => 'md5' , 'hashLevel' => 2 , 'hashNameLength' => 2); 	
	),
);


//数据存储
$config['database'] = array(
	'hash' => 'databaseHash',//如果为空就是随机取服务器，确保每台服务器的数据保持一样
	'servers' => array(
		array(
				'host' => 'localhost',
				'userName' => 'root',
				'passWord' => 'root',
				'dbName' => 'test',
				'splitStr' => '_',//库名分割表 test_00
				'splitDb' => array('00' , '01' , '02'), //分库名称
				'dbHash' => 'dbStgHash',//db哈希函数
				'dbDriver' => 'mysql',
				'pConnection' => false,
				'charSet' => 'utf8'
				'dbCollat' => 'utf8_general_ci'
				'maxSql' => 100,//记录执行最大的sql记录，已方便入log
				'debug' =>true, 
			),
	),
);

