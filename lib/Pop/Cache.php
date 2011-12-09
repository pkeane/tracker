<?php

class Pop_Cache_Exception extends Exception {
}

class Pop_Cache
{
	private function __construct() {}

	public static function get($ttl=10)
	{
        $cache_type = CACHE_TYPE;
        $cache_path = CACHE_PATH;
		$class_name = 'Pop_Cache_'.ucfirst($cache_type);
		if (class_exists($class_name)) {
			return new $class_name($cache_path,$ttl);
		} else {
			throw new Pop_Cache_Exception("Error: $class_name is not a valid class!");
		}
	}

	//must be overridden:
	public function expire($cache_id) {}
	public function getData($cache_id,$ttl) {}
	public function expunge() {}
	public function setData($cache_id,$data) {}
}


