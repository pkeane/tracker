<?php

class Pop_Handler_Exception extends Exception {
}

/** this class is always subclassed by a request-specific handler */

class Pop_Handler {

	protected $request;

	public function dispatch($r)
	{
		foreach ($this->resource_map as $uri_template => $resource) {
			//first, translate resource map uri template to a regex
			$uri_template = trim($r->handler.'/'.$uri_template,'/');
			$uri_regex = $uri_template;

			//skip regex template stuff if uri_template is a plain string
			if (false !== strpos($uri_template,'{')) {
				//stash param names into $template_matches
				$num = preg_match_all("/{([\w]*)}/",$uri_template,$template_matches);
				if ($num) {
					$uri_regex = preg_replace("/{[\w]*}/","([\w-,.]*)",$uri_template);
				}
			}
			//second, see if uri_regex matches the request uri (a.k.a. path)
			if (preg_match("!^$uri_regex\$!",$r->path,$uri_matches)) {
				//create parameters based on uri template and request matches
				if (isset($template_matches[1]) && isset($uri_matches[1])) { 
					array_shift($uri_matches);
					$params = array_combine($template_matches[1],$uri_matches);
					$r->setParams($params);
				}
				$method = $this->determineMethod($resource,$r);
				if (method_exists($this,$method)) {
					$r->resource = $resource;
					$this->setup($r);
					$this->{$method}($r);
				} else {
					$r->renderError(404,'no handler method');
				}
			}
		}
		$r->renderError(404,'no such resource');
	}

	protected function determineMethod($resource,$r)
	{
		if ('post' == $r->method) {
			$method = 'postTo';
		} else {
			$method = $r->method;
		}
		if (('html'==$r->format) || ('get' != $r->method)) {
			$format = '';
		} else {
			$format = ucfirst($r->format);
		}
		//camel case
		$resource = Pop_Util::camelize($resource);

		$handler_method = $method.$resource.$format;
		return $handler_method;
	}

	protected function setup($r)
	{
	}
}
