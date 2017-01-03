<?php
namespace AppBundle\Classes;

/**
 * Regarding specific languages
 *
 * @category    Flashr
 * @package     Flashr
 * @author      Luke Doherty <nanolucas@gmail.com>
 * @version     1.0
 */
class Language {
	const DEFAULT_LANGUAGE_ID = 3;
	const DEFAULT_LANGUAGE_CODE = 'ru';

	private static $languages = [
		'en' => 1,
		'de' => 2,
		'ru' => 3,
	];
	
	public static function get_id_by_code($code) {
		if ($code == 'default') {
			$code = self::get();
		}
		
		if (!array_key_exists($code, self::$languages)) {
			throw new \InvalidArgumentException('Invalid language selected');
		}
		
		return self::$languages[$code];
	}

	public static function get() {
		$language = Session::get('language');

		if (empty($language)) {
			$language = self::DEFAULT_LANGUAGE_CODE;
			Session::set('language', $language);
		}

		return $language;
	}

	public static function set($code) {
		if (!array_key_exists($code, self::$languages)) {
			throw new \InvalidArgumentException('Invalid language selected');
		}

		Session::set('language', $code);
	}
}
