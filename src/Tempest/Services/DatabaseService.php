<?php namespace Tempest\Services;

use Exception;
use Tempest\Tempest;
use Illuminate\Database\Capsule\Manager;
use Tempest\Utils\ObjectUtil;

/**
 * Provides methods for interacting with a database via Illuminate\Database.
 *
 * @package Tempest\Services
 * @author Marty Wallace
 */
class DatabaseService extends Service {

	/** @var Manager */
	private $_capsule = null;

	protected function setup() {
		$config = Tempest::get()->config->get('db');

		if (!empty($config)) {
			$config = array_merge(array(
				'driver' => 'mysql',
				'charset' => 'utf8',
				'collation' => 'utf8_unicode_ci'
			), $config);

			$connection = $this->parseConnectionString(ObjectUtil::getDeepValue($config, 'connection'));
			$connection = array_merge($config, $connection);

			unset($connection['connection']);

			$this->_capsule = new Manager();
			$this->_capsule->addConnection($connection);
			$this->_capsule->setAsGlobal();
		} else {
			throw new Exception('No database connection details were provided by the application.');
		}
	}

	/**
	 * Extract login information from a connection string formatted <code>user:password@host/database</code>. Returns
	 * an array with the keys host, user, password and dbname.
	 *
	 * @param string $value The connection string.
	 *
	 * @return string[]
	 *
	 * @throws Exception If the connection string is not valid.
	 */
	public function parseConnectionString($value) {
		$value = trim($value);

		preg_match('/^(?<username>[^:@]+):?(?<password>.*)?@(?<host>[^\/]+)\/(?<database>.+)$/', $value, $matches);

		if (!empty($matches)) return $matches;
		else throw new Exception('The supplied connection string is invalid.');
	}

	/**
	 * Get a schema builder.
	 *
	 * @return \Illuminate\Database\Schema\Builder
	 */
	public function schema() {
		return Manager::schema();
	}

	/**
	 * Get a query builder.
	 *
	 * @param string $table The name of the table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function table($table) {
		return Manager::table($table);
	}

}