<?php namespace Tempest\Services;

use Exception;
use Tempest\{Service, Utility};
use SessionHandlerInterface;

/**
 * Manages application session.
 *
 * @author Marty Wallace
 */
class Session implements Service {

	const CSRF_TOKEN_NAME = 'CSRFToken';

	/**
	 * Start a session.
	 *
	 * @param SessionHandlerInterface $handler The handler responsible for managing the sessions.
	 * @param string $name The session name.
	 *
	 * @return bool Whether or not the session was started successfully.
	 *
	 * @throws Exception If sessions are not enabled.
	 * @throws Exception If there is already an active session.
	 */
	public function start(SessionHandlerInterface $handler, $name = 'SessionID') {
		if (session_status() === PHP_SESSION_DISABLED) throw new Exception('Cannot start session - sessions are disabled.');
		if (session_status() === PHP_SESSION_ACTIVE) throw new Exception('Cannot start session - there is already an active session.');

		session_set_save_handler($handler, true);

		return session_start([
			'name' => $name,
			'use_cookies' => true,
			'use_only_cookies' => true,
			'cookie_httponly' => true
		]);
	}

	/**
	 * Determine whether there is an active session.
	 *
	 * @return bool
	 */
	public function active() {
		return session_status() === PHP_SESSION_ACTIVE;
	}

	/**
	 * Run session garbage collection.
	 *
	 * @return int The total number of sessions that were deleted.
	 */
	public function gc() {
		$total = session_gc();
		return $total === false ? 0 : $total;
	}

	/**
	 * Regenerate the current session ID.
	 *
	 * @param bool $deleteOldSession Whether or not to delete the old session data.
	 *
	 * @return string The new session ID.
	 */
	public function regenerate($deleteOldSession = false) {
		session_regenerate_id($deleteOldSession);
		return session_id();
	}

	/**
	 * Destroy the current session, including all contained data.
	 */
	public function destroy() {
		session_destroy();
	}

	/**
	 * Add or overwrite session data.
	 *
	 * @param string $property The name of the data to add or overwrite.
	 * @param mixed $value The value to add.
	 */
	public function add($property, $value) {
		$_SESSION[$property] = $value;
	}

	/**
	 * Retrieve {@link Session::add() previously added} session data.
	 *
	 * @param string $property The data to retrieve. If not provided, returns the entire session.
	 * @param mixed $fallback A fallback value to provide if the property did not exist.
	 *
	 * @return mixed
	 */
	public function get($property = null, $fallback = null) {
		if (empty($property)) return $_SESSION;
		return Utility::dig($_SESSION, $property, $fallback);
	}

	/**
	 * Determine whether a property exists within the current session.
	 *
	 * @param string $property The property to check for.
	 *
	 * @return bool
	 */
	public function has($property) {
		return array_key_exists($property, $_SESSION);
	}

	/**
	 * Remove previously added data from the session.
	 *
	 * @param string $property The property to remove.
	 */
	public function remove($property) {
		unset($_SESSION[$property]);
	}

	/**
	 * Get the current CSRF token. Creates one if it does not exist.
	 *
	 * @return string
	 */
	public function getCsrfToken() {
		if (!$this->has(self::CSRF_TOKEN_NAME)) {
			$this->regenerateCsrfToken();
		}

		return $this->get(self::CSRF_TOKEN_NAME);
	}

	/**
	 * Generates a new CSRF token and adds it to the session.
	 *
	 * @return string
	 */
	public function regenerateCsrfToken() {
		$token = bin2hex(random_bytes(32));
		$this->add(self::CSRF_TOKEN_NAME, $token);

		return $token;
	}

}