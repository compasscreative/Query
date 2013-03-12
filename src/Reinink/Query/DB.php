<?php
/**
 * A database layer for developers who like writing SQL.
 *
 * @package  Query
 * @version  1.0
 * @author   Jonathan Reinink <jonathan@reininks.com>
 * @link     https://github.com/reinink/Query
 */

namespace Reinink\Query;

use \Exception;
use \PDO;

class DB
{
	public static $callback;
	private static $instance;
	private $queries;

	public static function mysql($host, $username, $password, $database)
	{
		self::$instance = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
		self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		self::$instance->exec('SET NAMES utf8');
	}

	public static function sqlite($file)
	{
		self::$instance = new PDO('sqlite:' . $file);
		self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public static function connection()
	{
		if (!isset(self::$instance))
		{
			throw new Exception('No database connection found.');
		}

		return self::$instance;
	}

	public static function query($sql, $bindings = array())
	{
		$statement = self::execute($sql, $bindings);
	}

	public static function rows($sql, $bindings = array(), $class = null)
	{
		$statement = self::execute($sql, $bindings);

		return $class ? $statement->fetchAll(PDO::FETCH_CLASS, $class) : $statement->fetchAll(PDO::FETCH_CLASS);
	}

	public static function row($sql, $bindings = array(), $class = null)
	{
		$statement = self::execute($sql, $bindings);

		return $class ? $statement->fetchObject($class) : $statement->fetchObject();
	}

	public static function field($sql, $bindings = array())
	{
		$statement = self::execute($sql, $bindings);

		return $statement->fetchColumn(0);
	}

	private static function execute($sql, $bindings = array())
	{
		if (is_callable(self::$callback))
		{
			$sql = call_user_func_array(self::$callback, array($sql));
		}

		$statement = self::$instance->prepare($sql);

		$start = microtime(true);

		if (!$statement->execute($bindings))
		{
			throw new Exception('Error executing query.');
		}

		self::$instance->queries[] = array
		(
			'sql' => $sql,
			'time' => ((microtime(true) - $start)*1000) . ' milliseconds'
		);

		return $statement;
	}
}