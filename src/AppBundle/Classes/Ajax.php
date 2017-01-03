<?php
namespace AppBundle\Classes;

/**
 * Ajax helper class
 *
 * HowTo:
 * Ajax::init();
 *
 * if (<something_wrong>) {
 * 		Ajax::error('Error text here');
 * }
 *
 * Ajax::success(['Success message here'][, array('custom_data' => 'here')]);
 *
 * If we need frontend redirect page or refresh it:
 * Ajax::redirect('http://redirect/URL'); // redirect to specific URL
 * Ajax::redirect(); // refresh the page
 *
 * Answer for validation of forms have a strict format, described in wiki.
 *
 * @link http://wiki.secureapi.com.au/index.php?title=AJAX_Requests_Standard
 */

/**
 * Handling ajax requests
 *
 */
class Ajax {
	/**
	 * Name of validation list in "data" property
	 */
	const VALIDATION_PROPERTY = 'validation';
	/**
	 * Custom response data
	 * @var array
	 */
	protected static $data;
	/**
	 * Response status
	 * @var bool
	 */
	protected static $status;
	/**
	 * Redirect url
	 * @var string
	 */
	protected static $redirect;
	/**
	 * Custom message to user
	 * @var string
	 */
	protected static $message;
	/**
	 * Holds debug info
	 * @var array
	 */
	protected static $debug;

	/**
	 * Check for AJAX request and init AJAX environment and headers.
	 * In LIVE environment, if not an xmlhttprequest - die with "405 Method Not Allowed"
	 */
	public static function init() {
		if (defined('AJAX_REQUEST')) {
			return;
		}

		if (!self::$debug) {
			if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
				header('HTTP/1.0 405 Method Not Allowed');
				exit;
			}
		}

		header('Content-Type: application/json');
		define('AJAX_REQUEST', true);
	}

	/**
	 * Exit script with error response.
	 *
	 * @param string $message - plain error message
	 * @param array $data - not required additional data in response
	 */
	public static function error($message = null, array $data = null) {
		self::$status = false;

		if(is_array($message)){
			self::$data = $message;
		} else {
			if (!is_null($data)) {
				self::$data = $data;
			}

			self::$message = $message;
		}

		self::response();
	}

	/**
	 * Exit script with success response.
	 *
	 * @param string $message - not required plain success message
	 * @param array $data - not required additional data in response
	 */
	public static function success($message = null, array $data = null) {
		self::$status = true;
		self::$message = $message;

		if (!is_null($data)) {
			self::$data = $data;
		}

		self::response();
	}

	/**
	 * Set "data" property in the response
	 *
	 * @param array $data
	 */
	public static function set_data(array $data) {
		self::$data = $data;
	}

	/**
	 * Set one parameter in "data" property of response
	 *
	 * @param string $parameter_name
	 * @param mixed $value
	 */
	public static function set_data_value($parameter_name, $value) {
		if (is_null(self::$data)) {
			self::$data = array();
		}

		self::$data[(string)$parameter_name] = $value;
	}

	/**
	 * Set validation errors. Validation response has strict format:
	 * array(
	 * 		'field_name_1' => 'validation rule message',
	 * 		'field_name_2' => 'validation rule message',
	 * );
	 *
	 * @param array $data - list of validation errors
	 */
	public static function set_validations(array $data) {
		if (is_null(self::$data)) {
			self::$data = array();
		}

		self::$data[self::VALIDATION_PROPERTY] = $data;
	}

	/**
	 * Set validation errors. Validation response has strict format:
	 * array(
	 * 		'field_name_1' => 'validation rule message',
	 * 		'field_name_2' => 'validation rule message',
	 * );
	 *
	 * @param string $field_name - attribute name of the form field
	 * @param string $rule_error - validation error message
	 */
	public static function set_validation_error($field_name, $rule_error) {
		if (is_null(self::$data)) {
			self::$data = array();
		}

		if (!isset(self::$data[self::VALIDATION_PROPERTY])) {
			self::$data[self::VALIDATION_PROPERTY] = array();
		}

		self::$data[self::VALIDATION_PROPERTY][(string)$field_name] = (string)$rule_error;
	}

	/**
	 * Set redirect url
	 * This url will be used by frontend.
	 * If $url is empty string, frontend must reload the page (refresh)
	 * This function just set "redirect" property of the response and does not exit the script.
	 * Use Ajax::error() or Ajax::success() for sending response and exit script.
	 *
	 * @param string $url
	 */
	public static function redirect($url = '') {
		self::$redirect = (string)$url;
	}

	/**
	 * Add debug info
	 * @param mixed $data
	 * @param string $comment
	 */
	public static function debug($data, $comment = '') {
		if (is_null(self::$debug)) {
			self::$debug = array();
		}

		self::$debug[] = array(
			'comment' => (string)$comment,
			'data' => $data,
		);
	}

	/**
	 * Clear all debug info
	 */
	public function clear_debug() {
		self::$debug = null;
	}

	/**
	 * Return response and exit script.
	 */
	private static function response() {
		// is ajax initiated?
		if (!defined('AJAX_REQUEST')) {
			throw new LogicException('Ajax is not initiated');
		}

		// building response
		$ajax_answer = array(
			'status' => self::$status,
		);

		if (!is_null(self::$redirect)) {
			$ajax_answer['redirect'] = self::$redirect;
		}

		if (!is_null(self::$data)) {
			$ajax_answer['data'] = self::$data;
		}

		if (!is_null(self::$message)) {
			$ajax_answer['message'] = self::$message;
		}

		if (!empty(self::$debug)) {
			$ajax_answer['debug'] = array();

			foreach (self::$debug as $debug_row) {
				if (($debug =@ json_encode($debug_row['data'])) !== false) {
					$ajax_answer['debug'][] = $debug_row;
				}
			}
		}

		exit(json_encode($ajax_answer));
	}
}
