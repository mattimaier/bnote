<?php

/**
 * Abstracts for all tangible translations.
 * @author mattimaier
 *
 */
abstract class BNoteTranslation {
	
	/**
	 * Returns a simple language-specific text for the given code.
	 * @param String $code Translation placeholder code.
	 */
	public function getText($code) {
		if(!isset($this->texts[$code])) return null;
		return $this->texts[$code];
	}
	
	/**
	 * Formats the given date(time) fields in local format.
	 * @param int $day Day in month
	 * @param int $month Month in year
	 * @param int $year Year (4 digits)
	 * @param int $hour Hour of day
	 * @param int $minute Minute of hour
	 * @return String Localized date(time) string
	 */
	public abstract function formatDate($day, $month, $year, $hour, $minute);
	
	/**
	 * Translates a language-specific date into a standardized database format date.
	 * @param String $formattedDate Formatted date.
	 * @return String Date in format YYYY-MM-DD [HH:ii]
	 */
	public abstract function formatDateForDb($formattedDate); 
	
	/**
	 * @return Array with keys 1-12 and the respective month names.
	 */
	public abstract function getMonths();
	
	/**
	 * Converts the English name of the weekday to another language.
	 * @param String $wd [Mon/Tue/.../Sun]
	 * @return String Printable full name, e.g. "Samstag".
	 */
	public abstract function convertEnglishWeekday($wd);
	
	/**
	 * @return String A language-specific datetime format pattern like d.m.Y HH:ii.
	 */
	public abstract function getDateTimeFormatPattern();
	
	/**
	 * @return String A language-specific date format pattern like d.m.Y.
	 */
	public abstract function getDateFormatPattern();
	
	/**
	 * Language-specific regular expressions to check dates, times, numbers, etc.
	 * @param string $patternCode Code of the pattern.
	 * @return string Language-specific regular expression.
	 */
	public abstract function getRegex($patternCode);
	
	/**
	 * Converts the given decimal in language-specific format to
	 * a database-conform decimal.
	 * @param String $decimal Decimal in language-format.
	 * @return Double more or less a float/double.
	 */
	public abstract function decimalToDb($decimal);
	
	/**
	 * Converts a database-formatted decimal to language-specific format.
	 * @param float/String $dbDecimal Number to convert.
	 * @return String (formatted)
	 */
	public abstract function formatDecimal($dbDecimal);
}

?>