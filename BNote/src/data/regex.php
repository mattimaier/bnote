<?php
/**
 * This class offers methods to test data
**/
class Regex {

	public static $SPECIALCHARACTERS = "'`,-àáâãăäāåæćčçèéêĕëēìíîĭïðłñòóôõöőøœšùúûüűýÿþÀÁÂÃĂÄĀÅÆĆČÇÈÉÊĔËĒÌÍÎĬÏÐŁÑÒÓÔÕÖŐØŒŠÙÚÛÜŰÝÞß";
	
	private $regex;
	private $regex_js;
	
	function __construct() {
		$this->regex ["street"] = '/^[[:alpha:]' . Regex::$SPECIALCHARACTERS . '0-9\ \.\,\-\/\(\)]{1,100}$/';
		$this->regex_js ["street"] = '^[\\\w' . Regex::$SPECIALCHARACTERS . '\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,100}$';
		
		$this->regex ["zip"] = '/^[[:alpha:]0-9\s]{4,7}$/';
		$this->regex_js ["zip"] = '^\\\d{4,6}$';
		
		$this->regex ["city"] = '/^[[:alpha:]' . Regex::$SPECIALCHARACTERS . '0-9\ \.\,\-]{1,100}$/';
		$this->regex_js ["city"] = '^[\\\w' . Regex::$SPECIALCHARACTERS . '\\\s\\\.\\\,\\\-]{3,100}$';
		
		$this->regex ["phone"] = '/^[0-9\+\-\/\ \(\)]{1,29}$/';
		$this->regex_js ["phone"] = '^[0-9\\\+\\\-\\\/\\\s\\\(\\\)]{1,29}$';
		
		$this->regex ["email"] = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,4})$/';
		$this->regex_js ["email"] = '^[a-zA-Z0-9][\\\w\.-]*@(?:[a-zA-Z0-9][a-zA-Z0-9_-]+\.)+[A-Z,a-z]{2,5}$';
		
		$this->regex ["positive_amount"] = '/^\d{1,12}$/';
		$this->regex ["positive_decimal"] = '/^\d{0,8}\,\d{0,2}$/';
		$this->regex ["signed_amount"] = '/^-?\d{1,12}$/';
		$this->regex ["money"] = '/^-?\d{0,8}[,\d{1,2}]$/';
		$this->regex ["moneyEnglish"] = '/^-?\d{1,8}(\.\d{1,2})?$/';
		$this->regex ["moneyEnglishFull"] = '/^-?\d{0,8}\.\d{1,2}$/';
		
		$this->regex ["date"] = '/^\d{4}-\d{2}-\d{2}$/';
		$this->regex ["time"] = '/^\d{2}:\d{2}$/';
		$this->regex ["datetime"] = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/';
		$this->regex ["database_date"] = '/^\d{4}-\d{2}-\d{2}$/';
		$this->regex ["db_datetime"] = '/^\d{4}-\d{2}-\d{2}\ \d{2}:\d{2}:\d{2}$/';
		
		$this->regex ["subject"] = '/^[[:alnum:]' . Regex::$SPECIALCHARACTERS . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,255}$/';
		$this->regex ["name"] = '/^[[:alnum:]' . Regex::$SPECIALCHARACTERS . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,100}$/';
		$this->regex ["short_name"] = '/^[[:alnum:]' . Regex::$SPECIALCHARACTERS . '\ \.\,\-\(\)\?]{1,50}$/';
		$this->regex_js ["name"] = '^[\\\w' . Regex::$SPECIALCHARACTERS . '\\\s\\\.\\\,\\\-\\\/\\\(\\\)\?]{1,100}$';
		$this->regex_js ["short_name"] = '^[\\\w' . Regex::$SPECIALCHARACTERS . '\\\s\\\.\\\,\\\-\\\(\\\)\?]{1,50}$';
		
		$this->regex ["accountno"] = '/^[a-zA-Z0-9\ ]{5,30}$/';
		$this->regex ["bankno"] = '/^[a-zA-Z0-9\ ]{5,30}$/';
		
		$this->regex ["kdnr"] = '/^\d{4}-\d{3,6}$/';
		$this->regex ["password"] = '/^[[:alpha:]' . Regex::$SPECIALCHARACTERS . '0-9\ \.\-\,\;\:\_\+\&\#\'\/\!\$]{6,45}$/';
		$this->regex_js ["password"] = '^[\\\w' . Regex::$SPECIALCHARACTERS . '\\\s\\\.\\\-\\\,\\\;\\\:\\\_\\\+\\\&\\\\#\\\'\\\/\\\!\\\$]{6,45}$';
		
		$this->regex ["login"] = '/^[[:alnum:]\.\-\_]{3,45}$/';
		$this->regex_js ["login"] = '^[\\\w\\\.\\\-\\\_]{3,45}$';
		
		$this->regex ["dbid"] = '/^[0-9]{1,11}$/';
		$this->regex ["minsec"] = '/^([0-9]{1,2}\:)?[0-9]{1,2}\:[0-9]{1,2}$/';
		
		$this->regex ["dbitem"] = '/^[0-9a-zA-Z_-]{2,50}$/';
	}
	
	private function isCorrect($d, $type, $k = NULL) {
		$re = $this->regex [$type];
		$langTypes = array (
				"positive_amount",
				"positive_decimal",
				"signed_amount"
		);
		if(in_array($type, $langTypes)) {
			$re = Lang::regex ( $type );
		}
		
		if(empty($d) || !preg_match($re, $d)) {
			if($type == "password") $d = "-";
			$this->fail($d, $type, $k);
		} else {
			return true;
		}
	}
	
	// Default Methods to check against data fraud
	public function isStreet($d, $k=NULL) {
		return $this->isCorrect($d, "street", $k);
	}
	public function isZip($d, $k=NULL) {
		return $this->isCorrect ( $d, "zip", $k );
	}
	public function isCity($d, $k=NULL) {
		return $this->isCorrect ( $d, "city", $k );
	}
	public function isPhone($d, $k=NULL) {
		return $this->isCorrect ( $d, "phone", $k );
	}
	public function isEmail($d, $k=NULL) {
		return $this->isCorrect ( $d, "email", $k );
	}
	public function isPositiveAmount($d, $k=NULL) {
		return $this->isCorrect ( $d, "positive_amount", $k );
	}
	public function isSignedAmount($d, $k=NULL) {
		return $this->isCorrect ( $d, "signed_amount", $k );
	}
	public function isMoney($d, $k=NULL) {
		// receives a decimal in language format
		$dbDecimal = Data::convertToDb ( $d );
		$match = preg_match ( $this->regex ['moneyEnglish'], $dbDecimal );
		if ($match) {
			return true;
		} else if ($match == 0) {
			new BNoteError ( Lang::txt("Regex_isMoney.error") );
		} else {
			$this->fail($d, Lang::txt("Regex_isMoney.fail"), $k);
		}
		return false;
	}
	public function isMoneyEnglish($d, $k=NULL) {
		return $this->isCorrect($d, "moneyEnglish", $k);
	}
	public function isDate($d, $k=NULL) {
		return $this->isCorrect ( $d, "date", $k );
	}
	public function isTime($d, $k=NULL) {
		return $this->isCorrect ( $d, "time", $k );
	}
	public function isDateTime($d, $k=NULL) {
		return $this->isCorrect ( $d, "datetime", $k );
	}
	public function isSubject($d, $k=NULL) {
		return $this->isCorrect ( $d, "subject", $k );
	}
	public function isName($d, $k=NULL) {
		return $this->isCorrect ( $d, "name", $k );
	}
	public function isShortName($d, $k=NULL) {
		return $this->isCorrect ( $d, "short_name", $k );
	}
	public function isAccountNo($d, $k=NULL) {
		return $this->isCorrect ( $d, "accountno", $k );
	}
	public function isBankNo($d, $k=NULL) {
		return $this->isCorrect ( $d, "bankno", $k );
	}
	public function isKdnr($d, $k=NULL) {
		return $this->isCorrect ( $d, "kdnr", $k );
	}
	public function isPassword($d, $k=NULL) {
		return $this->isCorrect ( $d, "password", $k );
	}
	public function isPasswordQuiet($d, $k=NULL) {
		return preg_match($this->regex["password"], $d);
	}
	public function isLogin($d, $k=NULL) {
		return $this->isCorrect ( $d, "login", $k );
	}
	public function isDatabaseId($d, $k=NULL) {
		return $this->isCorrect ( $d, "dbid", $k );
	}
	public function isMinSec($d, $k=NULL) {
		if(strpos($d, ":") !== FALSE) {
			return $this->isCorrect($d, "minsec", $k);
		}
		return $this->isPositiveDecimalOrInteger($d);
	}
	
	// Methods for special testing
	/**
	 * Tests whether the given data is money in english format
	 * 
	 * @param data $d
	 *        	The data to test
	 */
	public function isMoneyQuiet($d) {
		if (empty ( $d ) || ! preg_match ( $this->regex ["moneyEnglishFull"], $d ))
			return false;
		else
			return true;
	}
	
	/**
	 * Tests whether the given data is a date/datetime in format Y-m-d H:i:s.
	 * 
	 * @param data $d
	 *        	The data to test.
	 */
	public function isDatabaseDateQuiet($d) {
		if (! empty ( $d )) {
			// data not empty
			if (preg_match ( $this->regex ["database_date"], $d ) || preg_match ( $this->regex ["db_datetime"], $d ))
				return true;
		}
		return false;
	}
	
	/**
	 * Tests whether text contains characters which should not be in a database query
	 * 
	 * @param data $d
	 *        	The data to test
	 */
	public function isText($d, $k=NULL) {
		$fail = array ();
		
		// $fail[0] = "'"; // check for '
		$fail[1] = "\""; // check for "
		$fail[2] = "\\"; // check for \
		                 
		// output / return
		foreach ( $fail as $char ) {
			if (strpos ( $d, $char ) === false)
				continue;
			else {
				$this->fail($d, "Text >" . $char . "<", $k);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Checks the input whether it is a decimal number with a max of 2 digits or
	 * whether it is an integer - either one is returned with true.
	 * 
	 * @param String $d
	 *        	Data to check
	 */
	public function isPositiveDecimalOrInteger($d) {
		return (! empty ( $d ) && (preg_match ( $this->regex ["positive_amount"], $d ) || preg_match ( $this->regex ["positive_decimal"], $d )));
	}
	
	/**
	 * Checks whether the number is a positive/negative integer or a decimal
	 * 
	 * @param String $d
	 *        	Data to check.
	 */
	public function isNumber($d) {
		return (! empty ( $d ) && (preg_match ( $this->regex ["signed_amount"], $d ) || preg_match ( $this->regex ["money"], $d )));
	}
	
	/**
	 * Checks whether the number is a positive/negative integer
	 * 
	 * @param String $d
	 *        	Data to check.
	 */
	public function isInteger($d) {
		return (! empty ( $d ) && preg_match ( $this->regex ["signed_amount"], $d ));
	}
	public function isLoginQuiet($d) {
		return (! empty ( $d ) && preg_match ( $this->regex ["login"], $d ));
	}
	
	public function isDbItem($d, $k=NULL) {
		return $this->isCorrect($d, "dbitem", $k);
	}
	
	/**
	 * Calls for an error and prints it
	 * 
	 * @param String $d
	 *        	The given data
	 * @param String $type
	 *        	The fieldtype which is wrong
	 */
	private function fail($d, $type, $k = NULL) {
		$msg = Lang::txt("Regex_fail.error") . "[$type / $d]";
		if($k != NULL) {
			$msg = Lang::txt("Regex_fail.error") . "[$k / $type]";
		}
		new BNoteError($msg);
	}
	
	/**
	 * Returns a string with JavaScript functions for form validation.
	 */
	public function getJSValidationFunctions() {
		$js = "";
		
		?>
 	function getRegexp(regex_name) {
	  switch(regex_name) {
	  <?php
		foreach ( $this->regex_js as $type => $regex ) {
			echo "	  case \"$type\": return \"$regex\";\n";
		}
		?>
	  default: return '^\w.+$';
	  }
  	}
  
  	function validateInput(element, type) {
	  var regex = new RegExp(getRegexp(type), 'g');
	  var val = element.value;
	  var isOk = regex.test(val); 
	  if(!isOk) {
		  element.style.border = "1px solid red";
	  }
	  else {
	  	  element.style.border = "1px solid #A0A0A0";
	  }
  	}
  	
  	function validateInputOptional(element, type) {
  	  var regex = new RegExp(getRegexp(type), 'g');
	  var val = element.value;
	  var isOk = regex.test(val); 
	  if(!isOk && val != '') {
		  element.style.border = "1px solid red";
	  }
	  else {
	  	  element.style.border = "1px solid #A0A0A0";
	  }
  	}
 	<?php
		return $js;
	}
}

?>