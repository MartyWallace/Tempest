<?php

/**
 * Your application configuration. The configuration will load '*' and then cascade with any blocks
 * that match the current SERVER_NAME property configured in Apache.
 *
 * Configuration data is used in your application via:
 *
 * <pre>
 *     tempest()->config(property, fallback)
 * </pre>
 *
 * Where 'property' is the key used here and 'fallback' is the value to use if no matching key is
 * found after cascading the data.
 */

return array(

	'*' => array(
		// The application timezone.
		'timezone' => 'Australia/Sydney',

		// Variables that are accessible within Twig templates via {{ config.<key> }}.
		// Do not put sensitive data in this block!
		'twig' => array(
			'title' => 'New App'
		),

		// Routes that the application can respond to.
		'routes' => array(
			'/' => 'AdaptivePage'
		)
	),

	'localhost' => array(
		// Dev mode shows more verbose errors.
		'dev' => true
	)

);