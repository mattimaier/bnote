<?php

/**
 * Translation implementation.
 * @author mattimaier
 *
 */
class Lang {
	
	/**
	 * The language that has been set.
	 * @var String
	 */
	private $lang;
	
	/**
	 * Language Object.
	 * @var BNoteTranslation
	 */
	private $langObj;
	
	/**
	 * Singleton.
	 * @var Lang
	 */
	private static $INSTANCE;
	
	protected static function getInstance() {
		if(Lang::$INSTANCE == null) {
			global $system_data;
			$l = $system_data->getLang();
			Lang::$INSTANCE = new Lang($l);
		}
		return Lang::$INSTANCE;
	}
	
	function __construct($lang) {
		$this->lang = $lang;
		require_once 'lang/lang_' . $lang . ".php";
		$this->langObj = new Translation();
	}
	
	/**
	 * Translate according to language settings.<br/>
	 * You can use %p as a placeholder for parameters.
	 * @param String $code Code to translate.
	 * @param Array $params Array with parameters that are replaced in the text in the order of input
	 * @return String Translated text or the code if the text was not found.
	 */
	public static function txt($code, $params = array()) {
		$inst = Lang::getInstance();
		$txt = $inst->langObj->getText($code);
		if($txt == null) return $code;
		$i = 0;
		while(($pos = strpos($txt, "%p")) !== null) {
			$txt = str_replace("%p", $params[$i++], $txt, 1);
		}
		return $txt;
	}
	
	/**
	 * Formats the given date(time) fields in local format.
	 * @param int $day Day in month
	 * @param int $month Month in year
	 * @param int $year Year (4 digits)
	 * @param int $hour Hour of day
	 * @param int $minute Minute of hour
	 * @return Localized date(time) string.
	 */
	public static function dt($day, $month, $year, $hour, $minute) {
		$inst = Lang::getInstance();
		return $inst->dt($day, $month, $year, $hour, $minute);
	}
	
	/**
	 * Translates a language-specific date into a standardized database format date.
	 * @param String $formattedDate Formatted date.
	 * @return String Date in format YYYY-MM-DD [HH:ii]
	 */
	public static function dtdb($formattedDate) {
		$inst = Lang::getInstance();
		return $inst->formatDateForDb($formattedDate);
	}
}

?>