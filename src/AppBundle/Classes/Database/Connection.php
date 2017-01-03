<?php
namespace AppBundle\Classes\Database;

/**
 * Database class for use with PDO
 *
 * @category    Llama
 * @package     Llama\Database
 * @subpackage  Connection
 * @author      Luke Doherty <nanolucas@gmail.com>
 * @version     1.0
 */
class Connection {
	private $_pdo_instance, $_pdo_statement,
			$_silent_error = false;

	public function __construct($connection_options) {
		if (isset($connection_options['silent_error'])) {
			$this->_silent_error = true;
		}

		if ($connection_result = $this->connect($connection_options) !== true) {
			$this->throw_error($connection_result);
			return false;
		}
	}

	private function connect($connection_options) {
		if (!isset($connection_options['db_name'], $connection_options['db_user'], $connection_options['db_user_password'])) {
			$this->throw_error('Connection options missing');
			return false;
		}

		if (!isset($connection_options['hostname'])) {
			$connection_options['hostname'] = 'localhost';
		}

		try {
			$this->_pdo_instance = new \PDO("mysql:host={$connection_options['hostname']};dbname={$connection_options['db_name']}", $connection_options['db_user'], $connection_options['db_user_password']);
			
			if (!empty($connection_options['charset'])) {
				$this->_pdo_instance->exec("SET NAMES '{$connection_options['charset']}';");
			}
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}

		return true;
	}

	public function query($query, &$parameters = null) {
		return $this->prepare($query, $parameters)->execute();
	}

	public function update($table_name, &$parameters, $conditions = '', $debug = false) {
		if (!is_array($parameters)) {
			return false;
		}

		$update_data = '';
		foreach ($parameters as $field => $value) {
			$update_data = ($update_data == '') ? $field . " = :$field" : $update_data . ', ' . $field . " = :$field";
		}

		$query = "UPDATE $table_name SET $update_data";
		if ($conditions != '') {
			$query .= ' WHERE ' . $conditions;
		}

		if ($debug) {
			echo $query . '<br />';
			return false;
		}

		return $this->query($query, $parameters);
	}

	public function delete($table_name, &$parameters, $conditions = '', $debug = false) {
		if ($conditions != '') {
			$query = "DELETE FROM $table_name WHERE $conditions";
		} else {
			$query = "TRUNCATE $table_name";
		}

		if ($debug) {
			echo $query . '<br />';
			return false;
		}

		return $this->query($query, $parameters);
	}

	public function insert($table_name, &$parameters, $ignore = false, $replace = false, $debug = false) {
		if (!is_array($parameters)) {
			return false;
		}

		if (isset($parameters[0]) && is_array($parameters[0])) {
			$fields = array_keys($parameters[0]);
			$values = array();

			foreach ($parameters as $data) {
				if ($fields != array_keys($data)) {
					$this->throw_error('Non-matching sets of data cannot be inserted');
					return false;
				}
				$values = array_merge($values, array_values($data));
			}

			//generate something like (?, ?, ?) then repeat that for each value set about to be inserted
			$row_places = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
			$complete_row_places = implode(', ', array_fill(0, count($parameters), $row_places));
		} else {
			$fields = array_keys($parameters);
			$values = array_values($parameters);
			$complete_row_places = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
		}

		$ignore_text = ($ignore) ? 'IGNORE' : '';

		$type = ($replace) ? 'REPLACE' : 'INSERT';
		$query = "$type $ignore_text INTO $table_name (`" . implode('`, `', $fields) . "`) VALUES $complete_row_places";

		if ($debug) {
			echo $query . '<br /><pre>';
			print_r($values);
			echo '</pre>';
			return false;
		}

		try {
			$pdo_statement = $this->_pdo_instance->prepare($query);
			$pdo_statement->execute($values);

			//if the query fails, make sure the error gets logged
			if ($pdo_statement->errorCode() !== '00000') {
				$error = $pdo_statement->errorInfo();
				$this->throw_error($error[2]);
			}
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}

		return true;
	}

	public function insert_id() {
		return $this->_pdo_instance->lastInsertId();
	}

	public function begin_transaction() {
		return $this->_pdo_instance->beginTransaction();
	}

	public function commit_transaction() {
		return $this->_pdo_instance->commit();
	}

	public function rollback_transaction() {
		return $this->_pdo_instance->rollBack();
	}

	public function prepare($query, &$parameters = null) {
		try {
			$prepared_parameters = array();

			//these need to be checked through before preparing the statement so that any no_quote values can be embedded into the query
			if (is_array($parameters)) {
				//PARAM_STR is default
				$parameter_types = array(
					'int' => \PDO::PARAM_INT,
					'boolean' => \PDO::PARAM_BOOL,
					'null' => \PDO::PARAM_NULL,
				);

				foreach ($parameters as $key => $value) {
					if (preg_match('/.*\\{(.*?)\\}/', $key, $matches)) {
						$parameter = str_replace($matches[0], '', $key);

						if ($matches[1] == 'no_quote') {
							//MAKE SURE THESE ARE SECURE as they wont be sanitized by pdo
							$query = str_replace("$key = :$key", "$parameter = $value", $query);
						} else {
							$prepared_parameters[] = array(':' . $parameter, $parameters[$key], $parameter_types[$matches[1]]);
						}
					} else {
						$prepared_parameters[] = array(":$key", $parameters[$key]);
					}
				}
			}

			$this->_pdo_statement = $this->_pdo_instance->prepare($query);

			if ($prepared_parameters != array()) {
				foreach ($prepared_parameters as $parameter_data) {
					if (!isset($parameter_data[2])) {
						$parameter_data[2] = \PDO::PARAM_STR;
					}

					$this->_pdo_statement->bindParam($parameter_data[0], $parameter_data[1], $parameter_data[2]);
				}
			}

			//allow chaining
			return $this;
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}
	}

	public function execute() {
		try {
			$this->_pdo_statement->execute();

			//if the query fails, make sure the error gets logged
			if ($this->_pdo_statement->errorCode() !== '00000') {
				$error = $this->_pdo_statement->errorInfo();
				$this->throw_error($error[2]);
			}

			return $this;
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}
	}

	public function fetch() {
		try {
			return $this->_pdo_statement->fetch(\PDO::FETCH_ASSOC);
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}
	}

	public function fetch_all($type = 'associative') {
		$fetch_type = ($type == 'numbered') ? \PDO::FETCH_NUM : \PDO::FETCH_ASSOC;
		try {
			return $this->_pdo_statement->fetchAll($fetch_type);
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}
	}

	public function fetch_value($query = null, &$parameters = null) {
		//this allows for checking num_rows before pulling the value from the result set
		if (is_null($query)) {
			$value = $this->_pdo_statement->fetch(\PDO::FETCH_NUM);
			return $value[0];
		}

		$this->query($query, $parameters);
		return $this->_pdo_statement->fetchColumn();
	}

	public function num_rows() {
		try {
			return $this->_pdo_statement->rowCount();
		} catch (\PDOException $error) {
			$this->throw_error($error->getMessage());
			return false;
		}
	}

	private function throw_error($reason) {
		echo $reason;exit;
		//prevent recursive failure logging in event there is an issue with the logging db
		if ($this->_silent_error) {
			return false;
		}

		$error_data = array(
			'reason' => $reason,
			'location' => $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
			'query' => !empty($this->_pdo_statement) ? $this->_pdo_statement->queryString : '',
			'backtrace' => print_r(debug_backtrace(), true)
		);
		print_r($error_data);exit;
		\AppBundle\Classes\Database::log_error($error_data);
	}
}
