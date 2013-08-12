<?php if (!defined('BASE_DOCUMENT_ROOT')) exit('Access Denied'); 
class JYExceptions extends Exceptions
{
	function __construct()
	{
		parent::__construct();
	}

	function __toString()
	{
		echo 'test';
	}
}
