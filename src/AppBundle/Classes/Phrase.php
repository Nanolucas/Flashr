<?php
namespace AppBundle\Classes;

/**
 * For retrieving data about a particular phrase
 *
 * @category    Flashr
 * @package     Flashr
 * @author      Luke Doherty <nanolucas@gmail.com>
 * @version     1.0
 */
class Phrase {
	private static $config_data;
	
	public static function set_config_data($name, $config_data) {
		self::$config_data[$name] = $config_data;
	}

	public static function get_by_id($phrase_id) {
		$database = Database::get_instance('default', array(
			'db_name' => self::$config_data['database']['name'],
			'db_user' => self::$config_data['database']['user'],
			'db_user_password' => self::$config_data['database']['password'],
			'charset' => 'utf8',
		));

		$query = '	SELECT p.phrase, p.translation, p.translation_seo, p.phonetic, l.name AS language_name
					FROM phrase p
						INNER JOIN language l ON p.new_language_id = l.language_id
					WHERE p.phrase_id = :phrase_id';
		$parameters = [
			'phrase_id' => $phrase_id,
		];
		$database->query($query, $parameters);

		if ($database->num_rows() != 1) {
			throw new \Exception('Matching phrase could not be found by ID');
		}

		return $database->fetch();
	}

	public static function get_by_translation($translation, $language_code = 'default') {
		if (empty($translation)) {
			throw new \InvalidArgumentException('URL must be not empty string');
		}

		$language_id = Language::get_id_by_code($language_code);

		$database = Database::get_instance('default', array(
			'db_name' => self::$config_data['database']['name'],
			'db_user' => self::$config_data['database']['user'],
			'db_user_password' => self::$config_data['database']['password'],
			'charset' => 'utf8',
		));

		$query = '	SELECT p.phrase, p.translation, p.translation_seo, p.phonetic, l.name AS language_name
					FROM phrase p
						INNER JOIN language l ON p.new_language_id = l.language_id
					WHERE p.translation_seo = :translation
						AND p.new_language_id = :language_id';
		$parameters = [
			'translation' => $translation,
			'language_id' => $language_id,
		];
		$database->query($query, $parameters);

		if ($database->num_rows() != 1) {
			throw new \Exception('Matching phrase could not be found by translation');
		}

		return $database->fetch();
	}

	public static function get_random($language_code = 'default') {
		$language_id = Language::get_id_by_code($language_code);

		$database = Database::get_instance('default', array(
			'db_name' => self::$config_data['database']['name'],
			'db_user' => self::$config_data['database']['user'],
			'db_user_password' => self::$config_data['database']['password'],
			'charset' => 'utf8',
		));

		$query = '	SELECT p.phrase_id, p.phrase, p.translation, p.translation_seo, p.phonetic, l.name AS language_name
					FROM phrase p
						INNER JOIN language l ON p.new_language_id = l.language_id
					WHERE p.new_language_id = :language_id
					ORDER BY RAND()
					LIMIT 1';
		$parameters = [
			'language_id' => $language_id,
		];
		$database->query($query, $parameters);

		if ($database->num_rows() != 1) {
			throw new \Exception('Matching phrase could not be found');
		}

		return $database->fetch();
	}

	public static function get_random_alternative_answers($phrase_id) {
		$database = Database::get_instance('default', array(
			'db_name' => self::$config_data['database']['name'],
			'db_user' => self::$config_data['database']['user'],
			'db_user_password' => self::$config_data['database']['password'],
			'charset' => 'utf8',
		));

		$query = '	SELECT p2.phrase_id, p2.phrase, p2.translation, p2.translation_seo, p2.phonetic, l.name AS language_name
					FROM phrase p1
						INNER JOIN phrase p2 ON p2.new_language_id = p1.new_language_id
							AND p2.base_language_id = p1.base_language_id
							AND p2.phrase_id != p1.phrase_id
						INNER JOIN language l ON p2.new_language_id = l.language_id
					WHERE p1.phrase_id = :phrase_id
					#if this phrase has a category, show results from the same category, otherwise from no category in particular
					ORDER BY IF(COALESCE(p1.category_id, 1) = COALESCE(p2.category_id, 1), 1, 0) DESC, RAND()
					LIMIT 3';
		$parameters = [
			'phrase_id' => $phrase_id,
		];
		$database->query($query, $parameters);

		if ($database->num_rows() == 0) {
			throw new \Exception('Matching phrase could not be found');
		}

		return $database->fetch_all();
	}
}
