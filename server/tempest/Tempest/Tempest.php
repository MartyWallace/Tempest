<?php namespace Tempest;

use Tempest\HTTP\Router;
use Tempest\HTTP\Status;
use Tempest\HTTP\Request;
use Tempest\HTTP\Response;
use Tempest\Output\BaseOutput;
use Tempest\MySQL\Database;
use Tempest\Twig\Twig;


/**
 * The core class of Tempest - the first to be initialized, managing configuration and router setup.
 * @author Marty Wallace.
 */
class Tempest
{

	/**
	 * @var Tempest
	 */
	protected static $instance;


	/**
	 * Gets the current Tempest instance.
	 * @return Tempest
	 */
	public static function getInstance()
	{
		return static::$instance;
	}


	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var int
	 */
	private $status = Status::OK;

	/**
	 * @var BaseOutput
	 */
	private $output = null;

	/**
	 * @var Error[]
	 */
	private $errors = array();

	/**
	 * @var Service[]
	 */
	private $services = array();


	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->config = new Config();

		static::$instance = $this;
	}


	/**
	 * Start the application.
	 */
	public function start()
	{
		$this->services = $this->defineServices();

		$this->router = new Router();
		$this->setup($this->router);

		$request = $this->router->getRequest();
		$match = $this->router->getMatch();

		if($match !== null)
		{
			// Create the response class.
			$response = Response::create(preg_replace('/\.+/', '\\', 'Responses\\' . $match->getHandlerClass()), $this);
			$method = $match->getHandlerMethod();

			if($response !== null)
			{
				if(method_exists($response, $method))
				{
					$response->setup($request);
					$this->setOutput($response->finalize($response->$method($request)));
				}
				else
				{
					// Response class was constructed successfully, but the target method was not defined.
					trigger_error("Response class <code>" . get_class($response) . "</code> does not have the method <code>$method()</code>.");
				}
			}
		}
		else
		{
			// No matching routes.
			$this->status = Status::NOT_FOUND;
			trigger_error("Input route <code>{$request}</code> not handled.");
		}
		
		$this->finalize();
	}


	/**
	 * Returns configuration data.
	 *
	 * @param string $prop The configuration property to get.
	 * @param mixed $fallback A fallback value to use if the property is not found.
	 *
	 * @return mixed
	 */
	public function config($prop, $fallback = null)
	{
		return $this->config->data($prop, $fallback);
	}


	/**
	 * Defines the services used by the application.
	 *
	 * @return array
	 */
	protected function defineServices()
	{
		return array(
			'twig' => new Twig($this),
			'db' => new Database($this)
		);
	}


	/**
	 * Abort the application.
	 * @param $code int The HTTP status code.
	 */
	public function abort($code = 400)
	{
		$this->status = $code;
		$this->finalize();
	}


	/**
	 * Handles an error triggered by the application. Errors are queued and presented together.
	 *
	 * @param $number int The error number.
	 * @param $string string The error text.
	 * @param $file string The file triggering the error.
	 * @param $line int The line number triggering the error.
	 * @param $context Array The error context.
	 */
	public function error($number, $string, $file, $line, $context)
	{
		$error = new Error($number, $string, $file, $line, $context);
		$this->errors[] = $error;
	}


	/**
	 * Finalize the application setup.
	 */
	private function finalize()
	{
		if($this->status <= 300 && count($this->errors) > 0)
		{
			// If the HTTP Response code is in the OK range but there are errors.
			$this->status = Status::INTERNAL_SERVER_ERROR;
		}

		if($this->status >= 300)
		{
			// If the HTTP Response code does not fall in the OK range, transform the output to an alternate value.
			// The alternate value is defined in App::showErrors().
			$this->setOutput($this->errorOutput($this->router->getRequest(), $this->status));
		}

		// Send the HTTP status, content-type and final output.
		http_response_code($this->status);
		header("Content-Type: {$this->output->getMime()}; charset={$this->output->getCharset()}");

		if($this->output !== null)
			echo $this->output->getFinalOutput($this, $this->router->getRequest());

		exit;
	}


	/**
	 * Defines alternate output for HTTP status codes that are not in the 2xx range.
	 *
	 * @param $request Request The request made.
	 * @param $code int The HTTP status code - used to determine what the result should be.
	 *
	 * @return string|BaseOutput The resulting output.
	 */
	protected function errorOutput(Request $request, $code)
	{
		if($code === Status::NOT_FOUND)
		{
			return '404 - Page not found.';
		}

		if($code >= 500)
		{
			if($this->config->data('dev'))
			{
				// Server-side errors.
				return $this->twig->render('tempest/errors.html', array(
					"title" => "Application Error",
					"version" => TEMPEST_VERSION,
					"uri" => $request,
					"get" => count($request->data(GET)) > 0 ? json_encode($request->data(GET), JSON_PRETTY_PRINT) : "-",
					"post" => count($request->data(POST)) > 0 ? json_encode($request->data(POST), JSON_PRETTY_PRINT) : "-",
					"named" => count($request->data(NAMED)) > 0 ? json_encode($request->data(NAMED), JSON_PRETTY_PRINT) : "-",
					'errors' => $this->errors
				));
			}
		}

		return null;
	}


	/**
	 * Magic getter. Attempts to return a Service if a property was not found with the specified name.
	 *
	 * @param string $prop
	 *
	 * @return Service
	 */
	public function __get($prop)
	{
		if(array_key_exists($prop, $this->services))
		{
			// Returns a service with the name.
			return $this->services[$prop];
		}

		return null;
	}


	/**
	 * Sets the application output. If the input is not an instance of BaseOutput, it will be converted to one.
	 * @param $value string|BaseOutput The output value.
	 */
	protected function setOutput($value)
	{
		if(!is_a($value, 'Tempest\Output\BaseOutput'))
		{
			// Transform existing output to an output object, if not already.
			$value = new BaseOutput('text/plain', $value);
		}

		$this->output = $value;
	}


	/**
	 * Called by <code>start()</code> after the configuration and router have been initialized.
	 * Override for custom initialization logic in <code>App</code>.
	 *
	 * @param $router Router The application router.
	 */
	protected function setup(Router $router){ /**/ }


	/**
	 * Returns the active Router.
	 *
	 * @return Router
	 */
	protected function getRouter(){ return $this->router; }


	/**
	 * Returns the list of defined services.
	 *
	 * @return Service[]
	 */
	public function getServices(){ return $this->services; }

}