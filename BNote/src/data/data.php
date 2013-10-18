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
		return str_replace(",", ".", $decimal);
	}
	
	/**
	 * Rounds a format to two decimal figures.
	 * @param float $decimal Decimal number in the form of x.xx
	 */
	public static function convertFromDb($decimal) {
		// round decimals to 2nd digit
		$decimal = round($decimal, 2);
		return number_format($decimal, 2, ',', '.');
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
		if(strlen($date) < 8) return "[not available]";
		
		if(strlen($date) > 10) {
			// with time in format yyyy-mm-dd hh:mm
			$year = substr($date, 0, 4);
			$month = substr($date, 5, 2);
			$day = substr($date, 8, 2);
			$hour = substr($date, 11, 2);
			$minute = substr($date, 14, 2);
			//$second = substr($date, 17, 2); // second is ignored!
			return "$day.$month.$year $hour:$minute";
		}
		
		// Because it's always in full format character lengths are constant
		$year = substr($date, 0, 4);
		$month = substr($date, 5, 2);
		$day = substr($date, 8, 2);
		return $day . "." . $month . "." . $year;
	}
	
	/**
	 * Converts a german formatted date to an american format of YYYY-MM-DD
	 * @param dedate $date Date in format DD.MM.YYYY
	 */
	public static function convertDateToDb($date) {
		$date = trim($date);
		if(strlen($date) > 10) {
			// datetime conversion
			$dot1 = strpos($date, ".");
			$dot2 = strpos($date, ".", $dot1+1);
			
			$time = substr($date, $dot2+6, 5);
			$year = substr($date, $dot2+1, 4);
			$month = substr($date, $dot1+1, $dot2-$dot1-1);
			$day = substr($date, 0, $dot1);
			return $year . "-" . $month . "-" . $day . " $time";
		}
		else {
			// standard conversion
			$dot1 = strpos($date, ".");
			$dot2 = strpos($date, ".", $dot1+1);
			$year = substr($date, $dot2+1);
			$month = substr($date, $dot1+1, $dot2-$dot1-1);
			$day = substr($date, 0, $dot1);
			return $year . "-" . $month . "-" . $day;
		}
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
		return date('d.m.Y', $newdate);
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
		return date('d.m.Y', $newdate);
	}
	
	/**
	 * @return array All months in format [number] => [name], e.g. 6 => Juni.
	 */
	public static function getMonths() {
		return array(
			1 => "Januar",
			2 => "Februar",
			3 => "M&auml;rz",
			4 => "April",
			5 => "Mai",
			6 => "Juni",
			7 => "Juli",
			8 => "August",
			9 => "September",
			10 => "Oktober",
			11 => "November",
			12 => "Dezember"
			);
	}
	
	/**
	 * Converts the English name of the weekday to another language.
	 * @param String $wd [Mon/Tue/.../Sun]
	 * @return String Printable full name, e.g. "Samstag".
	 */
	public static function convertEnglishWeekday($wd) {
		$res = "";
		switch($wd) {
			case "Mon": $res = "Montag"; break;
			case "Monday": $res = "Montag"; break;
			case "Tue": $res = "Dienstag"; break;
			case "Tuesday": $res = "Dienstag"; break;
			case "Wed": $res = "Mittwoch"; break;
			case "Wednesday": $res = "Mittwoch"; break;
			case "Thu": $res = "Donnerstag"; break;
			case "Thursday": $res = "Donnerstag"; break;
			case "Fri": $res = "Freitag"; break;
			case "Friday": $res = "Freitag"; break;
			case "Sat": $res = "Samstag"; break;
			case "Saturday": $res = "Samstag"; break;
			case "Sun": $res = "Sonntag"; break;
			case "Sunday": $res = "Sonntag"; break;
		}
		return $res;
	}
	
	/**
	 * Retrieves the weekday of the given date.
	 * @param String $dbdate Format YYYY-MM-DD
	 */
	public static function getWeekdayFromDbDate($dbdate) {
		/* PHP >5.3 necessary!!!
		
		 * $date = new DateTime($dbdate);
		 * return Data::convertEnglishWeekday($date->format('D'));
		 */
		 
		 // old PHP version
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
		return $dt->format("d.m.Y H:i");
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
	
	// END OF CLASS
 }

?>