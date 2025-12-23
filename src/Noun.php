<?php
/**
 * This file is part of the holonet common library
 * (c) Matthias Lantsch.
 *
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

use RuntimeException;

/**
 * Utils class for string utility concerning nouns (singularise / pluralise).
 */
class Noun {
	/**
	 * @var array IRREGULAR Array with irregular plural forms
	 */
	private const IRREGULAR = array(
		'move' => 'moves',
		'foot' => 'feet',
		'goose' => 'geese',
		'sex' => 'sexes',
		'child' => 'children',
		'man' => 'men',
		'tooth' => 'teeth',
		'person' => 'people'
	);

	/**
	 * @var array PLURAL Array with pluralization mappings
	 */
	private const PLURAL = array(
		'/(quiz)$/i' => '$1zes',
		'/^(ox)$/i' => '$1en',
		'/([m|l])ouse$/i' => '$1ice',
		'/(matr|vert|ind)ix|ex$/i' => '$1ices',
		'/(x|ch|ss|sh)$/i' => '$1es',
		'/([^aeiouy]|qu)y$/i' => '$1ies',
		'/(hive)$/i' => '$1s',
		'/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
		'/(shea|lea|loa|thie)f$/i' => '$1ves',
		'/sis$/i' => 'ses',
		'/([ti])um$/i' => '$1a',
		'/(tomat|potat|ech|her|vet)o$/i' => '$1oes',
		'/(bu)s$/i' => '$1ses',
		'/(alias)$/i' => '$1es',
		'/(octop)us$/i' => '$1i',
		'/(cris|ax|test)is$/i' => '$1es',
		'/(us)$/i' => '$1es',
		'/s$/i' => 's',
		'/$/' => 's'
	);

	/**
	 * @var array SINGULAR Array with singularization mappings
	 */
	private const SINGULAR = array(
		'/(quiz)zes$/i' => '$1',
		'/(matr)ices$/i' => '$1ix',
		'/(vert|ind)ices$/i' => '$1ex',
		'/^(ox)en$/i' => '$1',
		'/(alias)es$/i' => '$1',
		'/(octop|vir)i$/i' => '$1us',
		'/(cris|ax|test)es$/i' => '$1is',
		'/(shoe)s$/i' => '$1',
		'/(o)es$/i' => '$1',
		'/(bus)es$/i' => '$1',
		'/([m|l])ice$/i' => '$1ouse',
		'/(x|ch|ss|sh)es$/i' => '$1',
		'/(m)ovies$/i' => '$1ovie',
		'/(s)eries$/i' => '$1eries',
		'/([^aeiouy]|qu)ies$/i' => '$1y',
		'/([lr])ves$/i' => '$1f',
		'/(tive)s$/i' => '$1',
		'/(hive)s$/i' => '$1',
		'/(li|wi|kni)ves$/i' => '$1fe',
		'/(shea|loa|lea|thie)ves$/i' => '$1f',
		'/(^analy)ses$/i' => '$1sis',
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
		'/([ti])a$/i' => '$1um',
		'/(n)ews$/i' => '$1ews',
		'/(h|bl)ouses$/i' => '$1ouse',
		'/(corpse)s$/i' => '$1',
		'/(us)es$/i' => '$1',
		'/(us|ss)$/i' => '$1',
		'/s$/i' => ''
	);

	/**
	 * @var array UNCOUNTABLE Array with uncountable nouns
	 */
	private const UNCOUNTABLE = array(
		'sheep',
		'fish',
		'deer',
		'series',
		'species',
		'money',
		'rice',
		'information',
		'equipment'
	);

	/**
	 * uses the internal mapping arrays to pluralise the given noun.
	 * @param string $noun The noun given to pluralise
	 * @return string the plural form of the given noun
	 */
	public static function pluralise(string $noun): string {
		// save some time in the case that singular and plural are the same
		if (in_array(mb_strtolower($noun), self::UNCOUNTABLE)) {
			return $noun;
		}

		// check for irregular singular forms
		if (isset(self::IRREGULAR[$noun])) {
			return self::IRREGULAR[$noun];
		}

		// check for matches using regular expressions
		foreach (self::PLURAL as $pattern => $result) {
			if (preg_match($pattern, $noun) > 0) {
				return preg_replace($pattern, $result, $noun) ?? throw new RuntimeException("Error during pluralisation of noun '{$noun}'.");
			}
		}

		return $noun;
	}

	/**
	 * uses the internal mapping arrays to singularise the given noun.
	 * @param string $noun The noun given to singularise
	 * @return string the singular form of the given noun
	 */
	public static function singularise(string $noun): string {
		// save some time in the case that singular and plural are the same
		if (in_array(mb_strtolower($noun), self::UNCOUNTABLE)) {
			return $noun;
		}

		// check for irregular plural forms
		if (is_string($ret = array_search(mb_strtolower($noun), self::IRREGULAR))) {
			return $ret;
		}

		// check for matches using regular expressions
		foreach (self::SINGULAR as $pattern => $result) {
			if (preg_match($pattern, $noun) > 0) {
				return preg_replace($pattern, $result, $noun) ?? throw new RuntimeException("Error during singularisation of noun '{$noun}'.");
			}
		}

		return $noun;
	}
}
