<?php namespace Tempest\Services;

/**
 * Manages user sessions in the application.
 *
 * @property-read string $id The current session ID.
 *
 * @package Tempest\Services
 * @author Marty Wallace
 */
class SessionService extends Service {

	public function __construct() {
		session_save_path(app()->root . '/app/storage/sessions');
		session_start();
	}

	protected function setup() {
		// ...
	}

	public function __get($prop) {
		if ($prop === 'id') {
			return session_id();
		}

		return $this->get($prop);
	}

	public function __set($prop, $value) {
		$this->set($prop, $value);
	}

	/**
	 * Get some data saved in the current user session.
	 *
	 * @param string $prop The property name.
	 * @param mixed $fallback Fallback data to use if the property does not exist.
	 *
	 * @return mixed
	 */
	public function get($prop, $fallback = null) {
		return $this->exists($prop) ? $_SESSION[$prop] : $fallback;
	}

	/**
	 * Set some data in the current user session.
	 *
	 * @param string $prop The name associated with the data.
	 * @param mixed $value The value to allocate.
	 */
	public function set($prop, $value) {
		$_SESSION[$prop] = $value;
	}

	/**
	 * Determine whether some session information has been defined.
	 *
	 * @param string $prop The name of the data to check for.
	 *
	 * @return bool
	 */
	public function exists($prop) {
		return isset($_SESSION[$prop]);
	}

}