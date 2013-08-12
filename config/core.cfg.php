<?php
if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied');

//网站地址
$config['baseUrl'] = 'http://www.bak.com';
//网站静态文件地址
$config['staticUrl'] = 'http://www.bak.com/static';
//默认访问的页面地址
$config['indexPage'] = 'index.php';
//是否正常请求地址,?xxx=aaa
$config['enableQueryStrings'] = true;

//日志路径
$config['logPath'] = 'log';
//日志时间格式
$config['logFormatTime'] = 'Y-m-d';
//显示的写入时间
$config['logWriteTime'] = 'Y-m-d H:i:s';
//写入日志级别 , false不写入，写的级别分类
$config['writeLogLevel'] = array('error', 'debug');


//模板
//模板内空是否缓存
$config['tplCached'] = false;
//缓存时间
$config['tplCacheTime'] = 60;
//缓存目录
$config['tplCachePath'] = '';
//开启短标签
$config['shortTags'] = true;


//重载系统类的目录
$config['core'] = 'core';
//核心类扩展的前缀名称
$config['subClassPrefix'] = 'JY';
