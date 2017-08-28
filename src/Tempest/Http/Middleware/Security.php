<?php namespace Tempest\Http\Middleware;

use Closure;
use Tempest\Http\{Handler, Header};

/**
 * Basic protective middleware.
 *
 * @author Marty Wallace
 */
class Security extends Handler {

	/**
	 * Adds some basic response headers that slightly improve application security.
	 *
	 * @param Closure $next
	 */
	public function headers(Closure $next) {
		$this->expect([
			'nosniff' => true,
			'denyFrames' => true,
			'xssProtection' => true
		]);

		if ($this->option('nosniff')) $this->response->setHeader(Header::X_CONTENT_TYPE_OPTIONS, 'nosniff');
		if ($this->option('denyFrames')) $this->response->setHeader(Header::X_FRAME_OPTIONS, 'sameorigin');
		if ($this->option('xssProtection')) $this->response->setHeader(Header::X_XSS_PROTECTION, '1; mode=block');

		$next();
	}

}