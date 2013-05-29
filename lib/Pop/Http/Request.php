<?php

class Pop_Http_Request
{
	public static $types = array(
		'atom' =>'application/atom+xml',
		'cats' =>'application/atomcat+xml',
		'css' =>'text/css',
		'csv' =>'text/csv',
		'default' =>'text/html',
		'gif' =>'image/gif',
		'html' =>'text/html',
		'jpg' =>'image/jpeg',
		'json' =>'application/json',
		'kml' =>'application/vnd.google-earth.kml+xml',
		'mov' =>'video/quicktime',
		'mp3' =>'audio/mpeg',
		'mp4' =>'video/mp4',
		'oga' => 'audio/ogg',
		'ogv' => 'video/ogg',
		'png' =>'image/png',
		'pdf' =>'application/pdf',
		'txt' =>'text/plain',
		'uris' =>'text/uri-list',
		'uri' =>'text/uri-list',
		'xhtml' =>'application/xhtml+xml',
		'xml' =>'application/xml',
	);

	//members are variables 'set'
	private $members = array();
	private $params;
    private $cache;
	private $url_params = array();
	private $user;
	private $db;

	public function __construct()
	{
		$env['protocol'] = isset($_SERVER['HTTPS']) ? 'https' : 'http'; 
		$env['method'] = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : '';
		$env['_get'] = $_GET;
		$env['_post'] = $_POST;
		$env['_cookie'] = $_COOKIE;
		$env['_files'] = $_FILES;
		$env['htuser'] = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		$env['htpass'] = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		$env['request_uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$env['http_host'] =	isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$env['server_addr'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
		$env['query_string'] =	isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$env['script_name'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
		$env['slug'] = isset($_SERVER['HTTP_SLUG']) ? $_SERVER['HTTP_SLUG'] : '';
		$env['http_title'] = isset($_SERVER['HTTP_TITLE']) ? $_SERVER['HTTP_TITLE'] : '';
		$env['remote_addr'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$env['http_user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$env['app_root'] = trim($env['protocol'].'://'.$env['http_host'].dirname($env['script_name']),'/');
		//env is assign to this twice since it needs to be use in other methods
		$this->env = $env;
		$env['format'] = $this->getFormat();
		$env['handler'] = $this->getHandler(); 
		$env['path'] = $this->getPath();
		$env['response_mime_type'] = self::$types[$env['format']];
		$env['content_type'] = $this->getContentType();
		$this->env = $env;
	}

	public function setParams($params)
	{
		$this->params = $params;
	}


	public function __get( $var )
	{
		//first env
		if ( array_key_exists($var,$this->env)) {
			return $this->env[$var];
		}
		//second params
		if ( array_key_exists( $var, $this->members ) ) {
			return $this->members[ $var ];
		}
		//third getter
		$classname = get_class($this);
		$method = 'get'.ucfirst($var);
		if (method_exists($classname,$method)) {
			return $this->{$method}();
		}
	}

	public function getBody()
	{
		return file_get_contents("php://input");
	}

	public function getHandlerObject()
	{
		@include(HANDLER_PATH.'/'.$this->handler.'.php');
		$classname = 'Pop_Handler_'.Pop_Util::camelize($this->handler);
		if (class_exists($classname,true)) {
			return new $classname();
		} else {
			$this->renderRedirect(DEFAULT_HANDLER);
		}
	}

	public function getHandler()
	{

			$trimmed = trim($this->getPath(),'/');
			$str = array_shift(explode('/',$trimmed));
			//$str = array_shift($parts);
      return $str;
	}

	public function getHeaders() 
	{
		return apache_request_headers();
	}

	public function getHeader($name)
	{
		$headers = $this->getHeaders();
		if (isset($headers[$name])) {
			return $headers[$name];
		} else {
			return false;
		}
	}

	public function initCache()
	{
		$this->cache = Pop_Cache::get();
	}

	public function getCache()
	{
		return $this->cache;
	}

	public function getCacheId()
	{
		//cache buster deals w/ aggressive browser caching.  Not to be used on server (so normalized).
		$query_string = preg_replace("!cache_buster=[0-9]*!i",'cache_buster=stripped',$this->query_string);
		return $this->method.'|'.$this->path.'|'.$this->format.'|'.$query_string;
	}

	public function checkCache($ttl=null)
	{
		$content = $this->cache->getData($this->getCacheId(),$ttl);
		if ($content) {
			$this->renderResponse($content,false);
		}
	}

	public function getContentType() 
	{
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$header = $_SERVER['CONTENT_TYPE'];
		}
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$header = $_SERVER['HTTP_CONTENT_TYPE'];
		}
		if (isset($header)) {
			list($type,$subtype,$params) = Pop_Media::parseMimeType($header);
			if (isset($params['type'])) {
				return $type.'/'.$subtype.';type='.$params['type'];
			} else {
				return $type.'/'.$subtype;
			}
		}
	}

	public function getFormat()
	{
		//first check extension
		$pathinfo = pathinfo($this->getPath(false));
		if (isset($pathinfo['extension']) && $pathinfo['extension']) {
			$ext = $pathinfo['extension'];
			if (isset(self::$types[$ext])) {
				return $ext;
			}
		}
		//next, try 'format=' query param
		if ($this->get('format')) {
			if (isset(self::$types[$this->get('format')])) {
				return $this->get('format');
			}
		}	
		//default is html for get requests
		if ('get' == $this->env['method']) {
			return 'html';
		}
		return 'default';
	}

	public function getPath($strip_extension=true)
	{
		//returns full path w/o domain & w/o query string
		$path = $this->env['request_uri'];
		if (strpos($path,'..')) { //thwart the wily hacker
			throw new Pop_Http_Exception('no go');	
		}
		$base = trim(dirname($this->env['script_name']),'/');
		$path= preg_replace("!$base!",'',$path,1);
		$path= str_replace("index.php",'',$path);
		$path= trim($path, '/');
		/* Remove the query_string from the URL */
		if ( strpos($path, '?') !== FALSE ) {
			list($path,$query_string )= explode('?', $path);
		}
		if ($strip_extension) {
			if (strpos($path,'.') !== false) {
				$parts = explode('.', $path);
				$ext = array_pop($parts);
				if (isset(Pop_Http_Request::$types[$ext])) {
					$path = join('.',$parts);
				} else {	
					//path remains what it originally was
				}
			}
		}
		return $path;
	}

  public function setUser($user)
  {
    $this->user = $user;
  }

  public function getUser()
  {
    return $this->user;
  }

  public function setDb($user)
  {
    $this->db = $db;
  }

  public function getDb()
  {
    return $this->db;
  }

	public function get($key)
	{
			//precedence is post,get,url_param,set member
			if ($this->_filterPost($key) || '0' === $this->_filterPost($key)) {
				$value = $this->_filterPost($key);
			} else {
				$value = $this->_filterGet($key);
			}
			if (trim($value) || '0' === substr($value,0,1)) {
				return $value;
			} else {
				if (isset($this->params[$key])) {
					return $this->params[$key];
				}
				if (isset($this->members[$key])) {
					return $this->members[$key];
				}
				return false;
			}
	}

	public function getUrl() 
	{
		$this->path = $this->path ? $this->path : $this->getPath();
		return trim($this->path . '?' . $this->query_string,'?');
	}

	private function _filterArray($ar)
	{
		if (Pop_Util::getVersion() >= 520) {
			return filter_var_array($ar, FILTER_SANITIZE_STRING);
		} else {
			foreach ($ar as $k => $v) {
				$ar[$k] = strip_tags($v);
			}
			return $ar;
		}
	}

	private function _filterGet($key)
	{
		$get = $this->_get;
		if (Pop_Util::getVersion() >= 520) {
			return trim(filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING));
		} else {
			if (isset($get[$key])) {
				return trim(strip_tags($get[$key]));
			}
		}
		return false;
	}

	private function _filterPost($key)
	{
		$post = $this->_post;
		if (Pop_Util::getVersion() >= 520) {
			return trim(filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING));
		} else {
			if (isset($post[$key])) {
				if (is_array($post[$key])) {
					$clean_array = array();
					foreach ($post[$key] as $inp) {
						$inp = strip_tags($inp);
						$clean_array[] = $inp;
					}
					return $clean_array;
				} else {
					return strip_tags($post[$key]);
				}
			}
		}
		return false;
	}

	public function renderResponse($content,$set_cache=true,$status_code=null)
	{
		$response = new Pop_Http_Response($this);
		if ('get' != $this->method) {
			$set_cache = false;
		}
		$response->render($content,$set_cache,$status_code);
		exit;
	}

	public function renderOk($msg='')
	{
		$response = new Pop_Http_Response($this);
		$response->ok($msg);
		exit;
	}

	public function serveFile($path,$mime_type,$download=false)
	{
		$response = new Pop_Http_Response($this);
		$response->serveFile($path,$mime_type,$download);
		exit;
	}

	public function renderRedirect($path='',$params=null)
	{
		$response = new Pop_Http_Response($this);
		$response->redirect($path,$params);
		exit;
	}

	public function renderError($code,$msg='',$log_error=true)
	{
		$response = new Pop_Http_Response($this);
		$response->error($code,$msg,$log_error);
		exit;
	}
}

