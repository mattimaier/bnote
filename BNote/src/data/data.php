<?php

/**
 * Provides functions for all data modules
**/
abstract class Data {
 
	/**
	 * Converts a german decimal formatted number x,xx to x.xx
	 * @param decimal $decimal Decimal number in the form of x,xx
	 */
	public static function convertToDb($decimal) {
		return Lang::decimalToDb($decimal);
	}
	
	/**
	 * Rounds a format to two decimal figures.
	 * @param float $decimal Decimal number in the form of x.xx
	 */
	public static function convertFromDb($decimal) {
		return Lang::formatDecimal($decimal);
	}
	
	/**
	 * Formats an integer.
	 * @param int $integer Number to format.
	 */
	public static function formatInteger($integer) {
		return number_format($integer, 0, ',', '.');
	}
	
	/**
	 * Converts an american formatted datetime YYYY-MM-DD H:i:s
	 * to a german formatted datetime value
	 * @param Date $date Date in format YYYY-MM-DD
	 * @return String in format d.m.Y H:i:s
	 */
	public static function convertDateFromDb($date) {
		if(strlen($date) < 8) return "-";
		
		if(strlen($date) > 10) {
			// with time in format yyyy-mm-dd hh:mm
			$year = substr($date, 0, 4);
			$month = substr($date, 5, 2);
			$day = substr($date, 8, 2);
			$hour = substr($date, 11, 2);
			$minute = substr($date, 14, 2);
			//$second = substr($date, 17, 2); // second is ignored!
			return Lang::dt($day, $month, $year, $hour, $minute);
		}
		
		// Because it's always in full format character lengths are constant
		$year = substr($date, 0, 4);
		$month = substr($date, 5, 2);
		$day = substr($date, 8, 2);
		return Lang::dt($day, $month, $year, null, null);
	}
	
	/**
	 * Converts a german formatted date to an american format of YYYY-MM-DD
	 * @param dedate $date Date in format DD.MM.YYYY or DD.MM.YYYY HH:ii
	 */
	public static function convertDateToDb($date) {
		$date = trim($date);
		return Lang::dtdb($date);
	}
	
	/**
	 * For debugging reasons a function to display a full array
	 * @param Array $array Any kind of array
	 */
	public static function viewArray($array) {
		echo "<pre>";
		print_r($array);
		echo "</pre>";
	}
	
	/**
	 * Adds $numDays days to $date
	 * @param Date $date Format dd.mm.yyyy
	 * @param integer $numDays Number of days to add
	 * @return New date in format dd.mm.yyyy
	 */
	public static function addDaysToDate($date, $numDays) {
		$date = Data::convertDateToDb($date);
		$newdate = strtotime("+$numDays day", strtotime($date));
		return date(Lang::getDateFormatPattern(), $newdate);
	}
	
	/**
	 * Subtracts $numDays days from $date
	 * @param Date $date Format dd.mm.yyyy 
	 * @param integer $numDays Number of days to subtract
	 * @return New date in format dd.mm.yyyy
	 */
	public static function subtractDaysFromDate($date, $numDays) {
		$date = Data::convertDateToDb($date);
		$newdate = strtotime("-$numDays day", strtotime($date));
		return date(Lang::getDateFormatPattern(), $newdate);
	}
	
	/**
	 * @return array All months in format [number] => [name], e.g. 6 => Juni.
	 */
	public static function getMonths() {
		return Lang::getMonths();
	}
	
	/**
	 * Converts the English name of the weekday to another language.
	 * @param String $wd [Mon/Tue/.../Sun]
	 * @return String Printable full name, e.g. "Samstag".
	 */
	public static function convertEnglishWeekday($wd) {
		return Lang::convertEnglishWeekday($wd);
	}
	
	/**
	 * Retrieves the weekday of the given date.
	 * @param String $dbdate Format YYYY-MM-DD
	 */
	public static function getWeekdayFromDbDate($dbdate) {		 
		 // this works with PHP <5.3 - we leave it for now
		 $t = strtotime($dbdate);
		 $dinfo = getdate($t);
		 return Data::convertEnglishWeekday($dinfo["weekday"]);
	}
	
	/**
	 * Adds the given number of minutes to the date and returns it.
	 * <strong>Requires PHP >5.2.0</strong>
	 * @param String $date Date in Format dd.mm.yyyy
	 * @param int $numMinutes Amount of minutes to add.
	 * @return String Formatted datetime dd.mm.yyyy hh:ii
	 */
	public static function addMinutesToDate($date, $numMinutes) {
		if($numMinutes == 0) return $date;
		$dt = new DateTime(Data::convertDateToDb($date));
		$dt->modify("+$numMinutes minutes");
		return $dt->format(Lang::getDateTimeFormatPattern());
	}
	
	/**
	 * Checks whether a string starts with a substring.
	 * @param string $haystack String to search in.
	 * @param string $needle Prefix string.
	 * @return boolean true when the string starts with the needle, otherwise false.
	 */
	public static function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}
	
	/**
	 * Checks whether a string ends with a substring.
	 * @param string $haystack String to search in.
	 * @param string $needle Suffix string.
	 * @return boolean True when the string ends with the needs, otherwise false.
	 */
	public static function endsWith($haystack, $needle) {
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
	
	/**
	 * Compares two dates.
	 * <strong>Requires PHP >5.2.0</strong>
	 * @param string $date1 Date in YYYY-MM-DD HH:ii:ss (Db-)format.
	 * @param string $date2 Date in YYYY-MM-DD HH:ii:ss (Db-)format.
	 * @return int 1=Date1 after Date2, 0=Date1 same as Date2, -1=Date1 before Date2
	 */
	public static function compareDates($date1, $date2) {
		$ts1 = strtotime($date1);
		$ts2 = strtotime($date2);
		
		if($ts1 < $ts2) return -1; // Date 1 before Date 2
		else if($ts1 == $ts2) return 0; // Date 1 is same than Date 2
		else return 1; // Date 1 after Date 2
	}
	
	/**
	 * @return Current date in YYYY-MM-DD HH:ii:ss (Db-)format.
	 */
	public static function getDateNow() {
		return date('Y-m-d H:i:s');
	}
	
	public static function dbSelectionToDict($selection, $k, $v_arr, $glue=" ") {
		$dict = array();
		foreach($selection as $i => $row) {
			if($i == 0) continue;
			$v_vals = array();
			foreach($v_arr as $vk) {
				array_push($v_vals, $row[$vk]);
			}
			$dict[$row[$k]] = join($glue, $v_vals);
		}
		return $dict;
	}
	
	// END OF CLASS
 }

?>