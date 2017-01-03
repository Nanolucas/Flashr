<?php
namespace AppBundle\Classes;

/**
 * Work with session data
 *
 * @category    Flashr
 * @package     Flashr
 * @author      Luke Doherty <nanolucas@gmail.com>
 * @version     1.0
 */
class Session {
	private static $session_started = false;
	
	public static function init() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();

			self::$session_started = true;
		}
	}

	public static function get($name) {
		if (!self::$session_started) {
			self::init();
		}

		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}

	public static function set($name, $value) {
		if (!self::$session_started) {
			self::init();
		}

		$_SESSION[$name] = $value;
	}
}
