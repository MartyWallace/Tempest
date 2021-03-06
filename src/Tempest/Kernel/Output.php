<?php namespace Tempest\Kernel;

/**
 * Output generated by a {@link Kernel}.
 *
 * @author Marty Wallace
 */
interface Output {

	/**
	 * Send the output from the application to the outer context.
	 *
	 * @return mixed
	 */
	public function send();

}