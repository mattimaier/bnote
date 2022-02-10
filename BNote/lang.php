<?php

/**
 * Translation implementation.
 * @author mattimaier
 *
 */
class Lang {
	
	/**
	 * Parameter Placeholder.
	 * @var String
	 */
	public static $PARAMETER = "%p";
	
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
	private static $INSTANCE = null;
	
	protected static function getInstance() {
		if(Lang::$INSTANCE == null) {
			global $system_data;
			if($system_data != null) { 
				$l = $system_data->getLang();
			}
			else {
				$l = "de";
			}
			Lang::$INSTANCE = new Lang($l);
		}
		return Lang::$INSTANCE;
	}
	
	function __construct($lang) {
		$this->lang = $lang;
		if($this->lang == null) {
			$this->lang = "de";
		}
		require_once 'lang/lang_' . $this->lang . ".php";
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
		$nump = substr_count($txt, Lang::$PARAMETER);
		for($i = 0; $i < $nump; $i++) {
			$p = "";
			if(isset($params[$i])) {
				$p = $params[$i];
			}
			$txt = str_replace(Lang::$PARAMETER, $p, $txt);
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
	 * @return String Localized date(time)
	 */
	public static function dt($day, $month, $year, $hour, $minute) {
		$inst = Lang::getInstance();
		return $inst->langObj->formatDate($day, $month, $year, $hour, $minute);
	}
	
	/**
	 * Translates a language-specific date into a standardized database format date.
	 * @param String $formattedDate Formatted date.
	 * @return String Date in format YYYY-MM-DD [HH:ii]
	 */
	public static function dtdb($formattedDate) {
		$inst = Lang::getInstance();
		return $inst->langObj->formatDateForDb($formattedDate);
	}
	
	/**
	 * @return Array with keys 1-12 and the respective month names.
	 */
	public static function getMonths() {
		$inst = Lang::getInstance();
		return $inst->langObj->getMonths();
	}
	
	/**
	 * Converts the English name of the weekday to another language.
	 * @param String $wd [Mon/Tue/.../Sun]
	 * @return String Printable full name, e.g. "Samstag".
	 */
	public static function convertEnglishWeekday($wd) {
		$inst = Lang::getInstance();
		return $inst->langObj->convertEnglishWeekday($wd);
	}
	
	/**
	 * @return String Language-specific datetime format pattern like d.m.Y HH:ii.
	 */
	public static function getDateTimeFormatPattern() {
		$inst = Lang::getInstance();
		return $inst->langObj->getDateTimeFormatPattern();
	}
	
	/**
	 * @return String Language-specific date format pattern like d.m.Y.
	 */
	public static function getDateFormatPattern() {
		$inst = Lang::getInstance();
		return $inst->langObj->getDateFormatPattern();
	}
	
	/**
	 * Language-specific regular expressions.
	 * @param string $code Pattern code.
	 * @return string Language-specific regular expression.
	 */
	public static function regex($code) {
		$inst = Lang::getInstance();
		return $inst->langObj->getRegex($code);
	}
	
	/**
	 * Language-specific decimal representation to decimal in database format.
	 * @param String $decimal Language-string, e.g. "203.203,20"
	 * @return Double More or less float/double.
	 */
	public static function decimalToDb($decimal) {
		$inst = Lang::getInstance();
		return $inst->langObj->decimalToDb($decimal);
	}
	
	/**
	 * Database representation to language-specific representation of decimals. 
	 * @param float/String $dbDecimal A floating point number or a String in database decimal format, usually with "." as decimal separator.
	 * @return String Language-specific decimal.
	 */
	public static function formatDecimal($dbDecimal) {
		$inst = Lang::getInstance();
		return $inst->langObj->formatDecimal($dbDecimal);
	}
}