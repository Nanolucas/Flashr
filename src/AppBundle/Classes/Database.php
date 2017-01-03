<?php
namespace AppBundle\Classes;

/**
 * Database collection class
 *
 * @category    Llama
 * @package     Llama
 * @subpackage  Database
 * @author      Luke Doherty <nanolucas@gmail.com>
 * @version     1.0
 */
class Database {
	private static $_connections = array();

	public static function get_instance($connection_name = 'default', $connection_options = array()) {
		if (!isset(self::$_connections[$connection_name])) {
			self::$_connections[$connection_name] = new Database\Connection($connection_options);
		}

		return self::$_connections[$connection_name];
	}

	public static function log_error($error_data) {
		echo 'uh oh';exit;
		$query = '	SELECT error_id, error_count
					FROM sql_errors
					WHERE query = :query AND error_message = :error_message AND error_location = :error_location';

		$parameters = array(
			'query' => $error_data['query'],
			'error_message' => $error_data['reason'],
			'error_location' => $error_data['location']
		);

		$connection_options = array();
		//$connection_options = (new Configuration\Reader\INI(APPLICATION_PATH . '/configs/config.ini', APPLICATION_ENV))->database_developers->get_value();
		$connection_options['silent_error'] = 1;

		$database = self::get_instance('developers', $connection_options);
		$database->query($query, $parameters);

		if ($database->num_rows() == 1) {
			$row = $database->fetch();
			$update_data = array('error_count' => $row['error_count'] + 1);

			$database->update('sql_errors', $update_data, 'error_id = ' . $row['error_id']);
		} else {
			$insert_data = array(
				'query' => $error_data['query'],
				'error_message' => $error_data['reason'],
				'error_location' => $error_data['location'],
				'date_added' => date('Y-m-d H:i:s')
			);

			$database->insert('sql_errors', $insert_data);
		}

		$insert_data = array(
			'error_id' => isset($row['error_id']) ? $row['error_id'] : $database->insert_id(),
			'backtrace' => gzencode($error_data['backtrace'], 9)
		);
		$database->insert('sql_errors_details', $insert_data);

		return true;
	}
}
