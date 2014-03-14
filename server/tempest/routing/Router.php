<?php

namespace tempest\routing;

use \tempest\routing\Route;
use \tempest\routing\Request;


class Router
{

	private $default;
	private $request;
	private $routes = array();


	public function __construct($map)
	{
		$this->request = new Request(strlen(REQUEST_URI) === 0 ? DEFAULT_RESPONSE_NAME : REQUEST_URI);

		foreach($map as $pattern => $handler)
		{
			$route = new Route($pattern, $handler);
			$this->routes[] = $route;

			if($route->getPattern() === DEFAULT_RESPONSE_NAME)
			{
				$this->default = $route;
			}
		}
	}


	public function getRoute()
	{
		if(strlen(REQUEST_URI) === 0)
		{
			// Use default Route if the request is to the site root.
			return $this->default;
		}


		return $this->request->findMatch($this);
	}


	public function getDefault(){ return $this->default; }
	public function getRequest(){ return $this->request; }
	public function getRoutes(){ return $this->routes; }

}