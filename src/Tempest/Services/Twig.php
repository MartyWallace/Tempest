<?php namespace Tempest\Services;

use Tempest\App;
use Tempest\Service;
use Tempest\Utility;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Extension_Debug;
use Twig_SimpleFilter;

/**
 * The Twig service exposes all of Twig's functionality.
 *
 * @author Marty Wallace
 */
class Twig extends Twig_Environment implements Service {

	public function __construct() {
		$loader = new Twig_Loader_Filesystem();
		parent::__construct($loader, ['debug' => App::get()->dev]);

		foreach ($this->getTemplatePaths() as $path) {
			$loader->prependPath($path);
		}

		$this->addGlobal('app', App::get());

		$this->addFilter(new Twig_SimpleFilter('dig', [Utility::class, 'dig']));
		$this->addFilter(new Twig_SimpleFilter('kebab', [Utility::class, 'kebab']));

		if (App::get()->dev) {
			$this->addExtension(new Twig_Extension_Debug());
		}
	}

	/**
	 * Get all template paths.
	 *
	 * @return string[]
	 */
	protected function getTemplatePaths() {
		$inbuilt = [realpath(__DIR__ . '/../../../templates')];
		$custom = App::get()->config('templates', ['templates']);

		if (!is_array($custom)) {
			$custom = [$custom];
		}

		return array_merge($inbuilt, array_map(function($path) {
			return App::get()->root . '/' . ltrim($path, '/\\');
		}, $custom));
	}

}