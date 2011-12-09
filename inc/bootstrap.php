<?php

ini_set('include_path',BASE_PATH.'/lib:'.BASE_PATH.'/models');

function popAutoload($class_name) {
	$class_name = str_replace('_','/',$class_name).'.php';
	@include ($class_name);
}

spl_autoload_register('popAutoload');

if (!file_exists(BASE_PATH.'/data/db.sqlite')) {
    include BASE_PATH.'/install.php';
    exit;
}

define('SQLITE_PATH',BASE_PATH.'/data/db.sqlite');
define('HANDLER_PATH',BASE_PATH.'/handlers');
define('TEMPLATE_PATH',BASE_PATH.'/templates');
define('CACHE_TYPE','file');
define('CACHE_PATH',BASE_PATH.'/cache');
define('TEMPLATE_COMPILE_PATH',BASE_PATH.'/cache');
define('DEFAULT_HANDLER','tasks');
define('MAIN_TITLE','Tracker');
