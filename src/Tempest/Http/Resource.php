<?php namespace Tempest\Http;

/**
 * A HTTP resource.
 *
 * @property-read string $uri The URI this resource deals with.
 *
 * @author Marty Wallace
 */
abstract class Resource {

	/** @var string */
	private $_uri = '';

	/** @var string[][] */
	private $_middleware = [];

	/**
	 * Uri constructor.
	 *
	 * @param string $uri
	 */
	public function __construct($uri) {
		$this->_uri = $uri;
	}

	public function __get($prop) {
		if ($prop === 'uri') return $this->_uri;

		return null;
	}

	/**
	 * Prepend a value to the URI.
	 *
	 * @param string $value THe value to prepend.
	 *
	 * @return $this
	 */
	public function prependUri($value) {
		$this->_uri = '/' . trim(trim($value, '/\\') . $this->_uri, '/\\');

		return $this;
	}

	/**
	 * Prepend before middleware to this resource.
	 *
	 * @param string $class The name of the middleware class.
	 * @param string $method The name of the method within the middleware class to trigger.
	 */
	public function prependMiddleware($class, $method) {
		array_unshift($this->_middleware, [$class, $method]);
	}

	/**
	 * Attach before middleware to this resources.
	 *
	 * @param string $class The name of the middleware class.
	 * @param string $method The name of the method within the middleware class to trigger.
	 *
	 * @return $this
	 */
	public function middleware($class, $method) {
		$this->_middleware[] = [$class, $method];

		return $this;
	}

	/**
	 * Get all registered before middleware.
	 *
	 * @return string[][]
	 */
	public function getMiddleware() {
		return $this->_middleware;
	}

}