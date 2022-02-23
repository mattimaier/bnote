<?php
if(file_exists("../../lang.php")) {
	require_once('../../lang.php');
}

/**
 * Provides functions for all data modules
**/
abstract class Data {
 
	const DATE_UNIFORMAT = "Y-m-d";
	const DATETIME_UNIFORMAT = "Y-m-d H:i:s";
	
	
	/**
	 * Converts a german decimal formatted number x,xx to x.xx
	 * @param Double $decimal Decimal number in the form of x,xx
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
	 * @param String $date Date in format YYYY-MM-DD
	 * @return String in language format
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
	 * @param String $date Date in format DD.MM.YYYY or DD.MM.YYYY HH:ii
	 */
	public static function convertDateToDb($date) {
		$date = trim($date);
		return Lang::dtdb($date);
	}
	
	public static function dateTimeTstd($datetime) {
		if(strpos($datetime, " ") !== FALSE) {
			return str_replace(" ", "T", trim($datetime));
		}
		return $datetime;
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
	 * @param String $date Format Y-m-d
	 * @param integer $numDays Number of days to add
	 * @return String New date
	 */
	public static function addDaysToDate($date, $numDays) {
		return date(Data::DATE_UNIFORMAT, strtotime("+$numDays day", strtotime($date)));
	}
	
	/**
	 * Subtracts $numDays days from $date
	 * @param String $date Format Y-m-d
	 * @param integer $numDays Number of days to subtract
	 * @return String New date
	 */
	public static function subtractDaysFromDate($date, $numDays) {
		return date(Data::DATE_UNIFORMAT, strtotime("-$numDays day", strtotime($date)));
	}
	
	/**
	 * Subtracts $numMonths months from $date
	 * @param String $date Format Y-m-d
	 * @param integer $numDays Number of months to subtract
	 * @return String New date
	 */
	public static function subtractMonthsFromDate($date, $numMonths) {
		return date(Data::DATE_UNIFORMAT, strtotime("-$numMonths month", strtotime($date)));
	}
	
	/**
	 * Adds $numMonths months from $date
	 * @param String $date Format Y-m-d
	 * @param integer $numDays Number of months to add
	 * @return String New date
	 */
	public static function addMonthsToDate($date, $numMonths) {
		return date(Data::DATE_UNIFORMAT, strtotime("+$numMonths month", strtotime($date)));
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
	 * @param String $date Date in Format Y-m-d
	 * @param int $numMinutes Amount of minutes to add.
	 * @return String Y-m-d H:i:s formatted date
	 */
	public static function addMinutesToDate($date, $numMinutes) {
		if($numMinutes == 0) return $date;
		$dt = new DateTime($date);
		$dt->modify("+$numMinutes minutes");
		return $dt->format(Data::DATETIME_UNIFORMAT);
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
	 * @return String Current date in YYYY-MM-DD HH:ii:ss (Db-)format.
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
	
	/**
	 * Determines if the given string representing a date(time) is in the future.
	 * @param string $dbDate Date in standard format (DB format).
	 * @return bool True when the given date is in the future.
	 */
	public static function isDateInFuture($dbDate) {
		$now = Data::getDateNow();
		return $now < strtotime($dbDate);
	}
	
	/**
	 * Adds an array to another array, but with the keys having a prefix.
	 * @param Array $array Original array. 
	 * @param Array $addition Array to add.
	 * @param Array $prefix Prefix to add in front of keys of additional array.
	 * @return Array completely merged array.
	 */
	public static function arrayMergeWithPrefix($array, $addition, $prefix) {
		$add = array();
		foreach($addition as $k => $v) {
			$add[$prefix . $k] = $v;
		}
		return array_merge($array, $add);
	}
	
	/**
	 * Converts a decimal or minute-second value into a time-string for the database.
	 * @param string $minsec A value like "5:23" meaning 5min and 23sec or "3.5" meaning 3min and 30sec.
	 * @return string Time-representation in HH:mm:ss for the database to save properly.
	 */
	public static function convertMinSecToDb($minsec) {
		if($minsec != null && strlen($minsec) > 0) {
			$h = 0; $m = 0; $s = 0;
			if(strpos($minsec, ":") !== FALSE) {
				$parts = explode(":", $minsec);
				if(count($parts) == 1) {
					$m = intval($parts[0]);
				}
				else if(count($parts) == 2) {
					$m = intval($parts[0]);
					$s = intval($parts[1]);
				}
				else {
					$h = intval($parts[0]);
					$m = intval($parts[1]);
					$s = intval($parts[2]);
				}
			}
			else if(is_int($minsec)) {
				$m = intval($minsec);
			}
			else {
				// if decimal: convert fraction to seconds
				$d = floatval(Lang::decimalToDb($minsec));
				$s = ceil(($d - floor($d)) * 60);
				$m = floor($d);
			}
			if($m > 59) {
				$h2 = floor($m / 60);
				$m2 = $m % 60;
				$h += $h2;
				$m = $m2;
			}
			return sprintf('%02d', $h) . ":" . sprintf('%02d', $m) . ":" . sprintf('%02d', $s);
		}
		return "00:00:00";
	}
	
	/**
	 * Converts a database time string representation into a minute and second based representation.
	 * @param string $dbTime Time-representation in HH:mm:ss from the database.
	 * @return string "m:ss" representation for view and edit.
	 */
	public static function convertMinSecFromDb($value) {
		// convert 00:00:00 to 0:00
		$h = 0;
		$m = 0;
		$s = 0;
		if(strlen($value) > 2) {
			$h = substr($value, 0, 2);
			if(strlen($value) > 4) {
				$col_pos = strpos($value, ":");
				$m = substr($value, $col_pos+1, 2);
				if(strlen($value) > 5) {
					$col_rpos = strrpos($value, ":");
					$s = substr($value, $col_rpos+1);
				}
			}
		}
		return (intval($h) * 60 + intval($m)) . ":" . $s;
	}
 }

?>