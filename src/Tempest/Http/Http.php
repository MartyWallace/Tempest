<?php namespace Tempest\Http;

use Closure;
use Exception;
use Tempest\App;
use Tempest\Kernel\Kernel;
use Tempest\Kernel\Input;
use Tempest\Events\ExceptionEvent;
use Tempest\Http\Session\BaseSessionHandler;
use Tempest\Http\Session\Directive;
use Tempest\Validation\ValidationException;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

/**
 * The HTTP kernel deals with interpreting a HTTP {@link Request request} and generating a {@link Response response}.
 *
 * @author Marty Wallace
 */
class Http extends Kernel {

	/** @var Route[] */
	private $_routes;

	/** @var mixed[][] */
	private $_middleware = [];

	/** @var BaseSessionHandler */
	private $_sessionHandler;

	/**
	 * Http constructor.
	 *
	 * @param callable|string $routes Known routes to match the request against. Can either be a function accepting this
	 * HTTP instance or a string pointing to a PHP file that returns a function accepting this HTTP instance.
	 *
	 * @throws Exception
	 */
	public function __construct($routes) {
		parent::__construct($routes);

		$root = new Group();

		if ($this->getConfig()) {
			// Attach the routes to the root group.
			$root->add($this->getConfig());
		}

		$this->_routes = $root->flatten();
	}

	/**
	 * Dump output as a HTTP response.
	 *
	 * @param mixed $data The data to debug.
	 * @param string $format The debugging format.
	 *
	 * @return Response
	 *
	 * @throws Exception If there is no active kernel to handle the dump.
	 */
	public function dump($data, $format = App::DUMP_FORMAT_PRINT_R) {
		return Response::make()
			->setHeader(Header::CONTENT_TYPE, $format === App::DUMP_FORMAT_JSON ? ContentType::APPLICATION_JSON : ContentType::TEXT_PLAIN)
			->setBody(parent::dump($data, $format));
	}

	/**
	 * Handle an incoming {@link Request HTTP request} and generate a {@link Response response} for sending.
	 *
	 * @param Request|Input $request The request to handle.
	 *
	 * @return Response
	 */
	public function handle(Input $request) {
		$response = Response::make();

		// Bind the request and response to Twig.
		App::get()->twig->addGlobal('request', $request);
		App::get()->twig->addGlobal('response', $response);

		// If sessions are enabled, attach some information from the request to it.
		if ($this->_sessionHandler) {
			$this->_sessionHandler->attachRequest($request);
		}

		try {
			// Attempt to match a route.
			$info = \FastRoute\simpleDispatcher(function (RouteCollector $collector) {
				foreach ($this->_routes as $route) {
					$collector->addRoute($route->getMethod(), $route->getUri(), $route);
				}
			})->dispatch($request->getMethod(), $request->getUri());

			if ($info[0] === Dispatcher::FOUND) $this->found($request, $response, $info[1], $info[2]);
			else if ($info[0] === Dispatcher::NOT_FOUND) $this->notFound($response);
			else if ($info[0] === Dispatcher::METHOD_NOT_ALLOWED) $this->methodNotAllowed($request, $response, $info[1]);

		} catch (ValidationException $exception) {
			$response->setStatus(Status::BAD_REQUEST)->json([
				'message' => $exception->getMessage(),
				'fields' => $exception->getErrors()
			]);

		} catch (Exception $exception) {
			$this->dispatch(ExceptionEvent::EXCEPTION, new ExceptionEvent($exception));

			$response->setStatus(Status::INTERNAL_SERVER_ERROR)->render('500.html', [
				'exception' => $exception
			]);
		}

		return $response;
	}

	/**
	 * Attach one or more middleware to be called before requests resolve to a controller or template.
	 *
	 * @param array[] ...$actions The middleware actions.
	 *
	 * @return $this
	 */
	public function middleware(...$actions) {
		$this->_middleware = array_merge($this->_middleware, $actions);
		return $this;
	}

	/**
	 * Create a new {@link Route route}.
	 *
	 * @param string|string[] $method The HTTP method(s) to associate this route with.
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function route($method, $uri) {
		if (!is_array($method)) $method = [$method];

		$method = array_map(function($method) { return strtoupper($method); }, $method);

		return new Route($method, $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to GET.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function get($uri) {
		return $this->route('GET', $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to POST.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function post($uri) {
		return $this->route('POST', $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to PUT.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function put($uri) {
		return $this->route('PUT', $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to PATCH.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function patch($uri) {
		return $this->route('PATCH', $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to DELETE.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function delete($uri) {
		return $this->route('DELETE', $uri);
	}

	/**
	 * Create a new {@link Route route} with its method set to HEAD.
	 *
	 * @param string $uri The URI that will trigger this route.
	 *
	 * @return Route
	 */
	public function head($uri) {
		return $this->route('HEAD', $uri);
	}

	/**
	 * Create a new {@link Group group of routes} that will be {@link Group::flatten flattened down} recursively.
	 *
	 * @param string $uri The base URI that will be merged onto the head of each descendant route or group.
	 * @param Route[]|Group[] $routes One or more child routes or groups.
	 *
	 * @return Group
	 */
	public function group($uri, array $routes) {
		return new Group($uri, $routes);
	}

	/**
	 * Enable HTTP sessions, beginning a new one if there is not one already.
	 *
	 * @param BaseSessionHandler $handler The handler responsible for managing the sessions.
	 * @param array $directives The session {@link Directive directives}.
	 *
	 * @return $this
	 *
	 * @throws Exception If sessions are not enabled.
	 * @throws Exception If there is already an active session.
	 * @throws Exception If the session could not be successfully started.
	 */
	public function enableSessions(BaseSessionHandler $handler, $directives = []) {
		if (session_status() === PHP_SESSION_DISABLED) throw new Exception('Cannot start session - sessions are disabled.');
		if (session_status() === PHP_SESSION_ACTIVE) throw new Exception('Cannot start session - there is already an active session.');

		$this->_sessionHandler = $handler;

		session_set_save_handler($handler, true);

		$success = session_start(array_merge([
			Directive::NAME => 'SessionID',
			Directive::USE_COOKIES => true,
			Directive::USE_ONLY_COOKIES => true,
			Directive::COOKIE_HTTPONLY => true
		], $directives));

		if (!$success) {
			throw new Exception('Could not enable sessions.');
		}

		return $this;
	}

	/**
	 * Handle a successfully matched route.
	 *
	 * @param Request $request The request being handled.
	 * @param Response $response The response to be sent.
	 * @param Route $route The route that was matched.
	 * @param mixed[] $named Named arguments provided in the request, defined by the route.
	 *
	 * @throws Exception If the matched route does not perform any valid action.
	 */
	protected function found(Request $request, Response $response, Route $route, array $named) {
		foreach ($named as $property => $value) {
			// Attached all named route data.
			$request->attachParam($property, $value);
		}

		if ($route->getMode() === Route::MODE_UNDETERMINED) {
			throw new Exception('Route "' . $route->getUri() . '" does not perform a valid action');
		}

		if ($route->getMode() === Route::MODE_TEMPLATE || $route->getMode() === Route::MODE_CONTROLLER) {
			$resolution = function() use ($route, $request, $response) {
				if ($route->getMode() === Route::MODE_TEMPLATE) {
					$response->render($route->getTemplate());
				}

				if ($route->getMode() === Route::MODE_CONTROLLER) {
					$action = $route->getController();

					if (!class_exists($action[0])) {
						throw new Exception('Controller class "' . $action[0] . '" does not exist.');
					}

					$controller = new $action[0]($action[2]);

					if (!method_exists($controller, $action[1])) {
						throw new Exception('Controller class "' . $action[0] . '" does not contain a method "' . $action[1] . '".');
					}

					$controller->{$action[1]}($request, $response);
				}
			};

			$pipeline = array_merge(
				$this->_middleware,
				$route->getMiddleware(),
				[$resolution]
			);

			$pipeline = array_map(function($action) use ($request, $response, $resolution) {
				if ($action !== $resolution) {
					if (!class_exists($action[0])) {
						throw new Exception('Middleware class "' . $action[0] . '" does not exist.');
					}

					$middleware = new $action[0]($action[2]);

					if (!method_exists($middleware, $action[1])) {
						throw new Exception('Middleware class "' . $action[0] . '" does not contain a method "' . $action[1] . '".');
					}

					return Closure::fromCallable([$middleware, $action[1]]);
				}

				return $action;
			}, $pipeline);

			// Bind all next closures and call the first.
			$pipeline = $this->bindNext($pipeline, $request, $response);
			$pipeline();
		}
	}

	/**
	 * Handle no route match.
	 *
	 * @param Response $response The response to be sent.
	 */
	protected function notFound(Response $response) {
		$response->setStatus(Status::NOT_FOUND)
			->render('404.html');
	}

	/**
	 * Handle a matched route with an unsupported method.
	 *
	 * @param Request $request The request being handled.
	 * @param Response $response The response to be sent.
	 * @param string[] $allowed The allowed methods.
	 */
	protected function methodNotAllowed(Request $request, Response $response, array $allowed) {
		$response->setStatus(Status::METHOD_NOT_ALLOWED)
			->setHeader(Header::ALLOW, implode(', ', $allowed))
			->render('405.html', ['method' => $request->getMethod(), 'allowed' => $allowed]);
	}

	/**
	 * Recursively bind all input closures with a pointer to the next one in line, then return the first closure.
	 *
	 * @param Closure[] $pipeline THe closure pipeline.
	 * @param Request $request The request to push through the pipeline.
	 * @param Response $response The response to push through the pipeline.
	 * @param int $index The pipeline index to work from.
	 *
	 * @return Closure
	 */
	private function bindNext(array $pipeline, Request $request, Response $response, $index = 0) {
		$closure = $pipeline[$index];

		if ($index === count($pipeline) - 1) {
			// The last item doesn't need to point to a subsequent closure.
			return $closure;
		} else {
			return function() use ($closure, $pipeline, $request, $response, $index) {
				$closure($request, $response, $this->bindNext($pipeline, $request, $response, $index + 1));
			};
		}
	}

	/**
	 * Get all registered routes.
	 *
	 * @return Route[]
	 */
	public function getRoutes() {
		return $this->_routes;
	}

	/**
	 * Get all registered middleware.
	 *
	 * @return mixed[][]
	 */
	public function getMiddleware() {
		return $this->_middleware;
	}

	/**
	 * Get the active session handler, if sessions were enabled.
	 *
	 * @return BaseSessionHandler
	 */
	public function getSessionHandler() {
		return $this->_sessionHandler;
	}

}