<?php namespace Tempest;

use Closure;

/**
 * General utilities.
 *
 * @author Marty Wallace
 */
class Utility {

	/**
	 * Evaluate a dot delimited string, digging into descendants and calling methods along the way.
	 *
	 * @param mixed $instance The instance to dig for data.
	 * @param string $query A dot (.) delimited query representing the tree to follow when digging for a value.
	 * @param mixed $fallback A fallback value to provide if the descendant did not exist.
	 *
	 * @return mixed
	 */
	public static function evaluate($instance, $query, $fallback = null) {
		if (!empty($instance)) {
			$query = array_filter(explode('.', $query), function($value) {
				// Remove any sneaky empty values.
				return strlen(trim($value)) !== 0;
			});

			if (!empty($query)) {
				$target = $instance;

				foreach ($query as $prop) {
					if (is_array($target) && array_key_exists($prop, $target)) $target = $target[$prop];
					else if (is_object($target) && property_exists($target, $prop)) $target = $target->{$prop};
					else if (is_object($target) && method_exists($target, $prop)) $target = $target->{$prop}();

					else return $fallback;
				}

				return $target;
			}
		}

		return $fallback;
	}

	/**
	 * Convert a string into kebab format e.g. "The quick brown fox" becomes "the-quick-brown-fox".
	 *
	 * @param string $value The input value.
	 * @param bool $upper Whether or no to uppercase the first letter of each segment e.g. "The-Quick-Brown-Fox".
	 *
	 * @return string
	 */
	public static function kebab($value, $upper = false) {
		$base = preg_replace('/[^A-Za-z\s\-]+/', '', $value);
		$base = preg_replace('/[\s\-]+/', '-', $base);
		$base = strtolower($base);

		if ($upper) {
			$base = implode('-', array_map(function ($part) {
				return ucfirst($part);
			}, explode('-', $base)));
		}

		return trim(trim($base, '-'));
	}

	/**
	 * Buffer the output of a function call and return the snapshot as a string.
	 *
	 * @param Closure $closure The function to call.
	 *
	 * @return string
	 */
	public static function buffer(Closure $closure) {
		ob_start();
		$closure();

		return ob_get_clean();
	}

	/**
	 * Generates a random string of specified length by combination of {@link random_bytes} and {@link bin2hex}.
	 *
	 * @param int $length The amount of characters the string should contain.
	 *
	 * @return string
	 */
	public static function randomString($length = 16) {
		return substr(bin2hex(random_bytes(ceil($length / 2))), 0, $length);
	}

}