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
	 * @return Localized date(time) string.
	 */
	public function formatDate($day, $month, $year, $hour, $minute);
	
	/**
	 * Translates a language-specific date into a standardized database format date.
	 * @param String $formattedDate Formatted date.
	 * @return String Date in format YYYY-MM-DD [HH:ii]
	 */
	public function formatDateForDb($formattedDate); 
	
}

?>