<?php

namespace Tempest;

use Exception;
use Tempest\Rendering\TwigComponent;


/**
 * Tempest's core, extended by your core application class.
 *
 * @property-read string $root The framework root directory.
 * @property-read TwigComponent $twig A reference to the inbuilt Twig component, used to render templates with Twig.
 *
 * @package Tempest
 * @author Marty Wallace
 */
abstract class Tempest extends Element implements IConfigurationProvider
{

    /** @var Tempest */
    private static $_instance;


    /**
     * Instantiate the application.
     * @param string $root The framework root directory.
     * @param string $configPath The application configuration file path, relative to the application root.
     * @param array $autoloadPaths A list of paths to attempt to autoload classes from.
     * @return Tempest
     */
    public static function instantiate($root, $configPath = null, Array $autoloadPaths = null)
    {
        if (self::$_instance === null)
        {
            self::$_instance = new static($root, $configPath);

            foreach ($autoloadPaths as $path)
            {
                self::$_instance->addAutoloadDirectory($path);
            }
        }

        return self::$_instance;
    }


    /** @var string */
    private $_root;

    /** @var Configuration */
    private $_config;


    /**
     * Constructor. Should not be called directly.
     * @see Tempest::instantiate() To create a new instance instead.
     * @param string $root The application root directory.
     * @param string $configPath The application configuration file path, relative to the application root.
     */
    public function __construct($root, $configPath = null)
    {
        $this->_root = $root;

        if ($configPath !== null)
        {
            // Initialize configuration.
            $this->_config = new Configuration($root . '/' . trim($configPath, '/'));
        }

        error_reporting($this->_config->dev ? E_ALL : 0);
    }


    public function __get($prop)
    {
        if ($prop === 'root') return $this->_root;

        return parent::__get($prop);
    }


    /**
     * Get application configuration data.
     * @param string $prop The configuration data to get.
     * @param mixed $fallback A fallback value to use if the specified data does not exist.
     * @return mixed
     */
    public function config($prop, $fallback = null)
    {
        return $this->_config->get($prop, $fallback);
    }


    /**
     * Register an autoloader to run in a given directory.
     * @param string $path The directory to add.
     * @throws Exception
     */
    public function addAutoloadDirectory($path)
    {
        $this->_attempt(function() use ($path) {
            $path = ROOT . '/' . trim($path, '/') . '/';

            if (is_dir($path))
            {
                spl_autoload_register(function($class) use ($path) {
                    $file = str_replace('\\', '/', $class) . '.php';

                    if (is_file($file))
                    {
                        require_once $file;

                        if (!class_exists($class))
                        {
                            throw new Exception('Could not find class ' . $class . '.');
                        }
                    }
                    else
                    {
                        throw new Exception('Could not load file ' . $file . '.');
                    }
                });
            }
            else
            {
                throw new Exception('Directory ' . $path . ' does not exist.');
            }
        });
    }


    /**
     * Attempt to execute a block of code. If any exceptions are thrown in the attempted block, they will be caught and
     * displayed in Tempest's exception page.
     * @param callable $callable Block of code to attempt to execute.
     */
    private function _attempt($callable)
    {
        try
        {
            $callable();
        }
        catch (Exception $exception)
        {
            if ($this->_config->dev)
            {
                die($this->twig->render('@tempest/exception.html', array(
                    'exception' => $exception
                )));
            }
        }
    }


    /**
     * Start running the application.
     */
    public function start()
    {
        $this->_attempt(function() {
            $this->addComponent('twig', new TwigComponent());

            $this->setup();
        });
    }


    /**
     * Set up the application.
     * @throws Exception
     */
    protected abstract function setup();

}