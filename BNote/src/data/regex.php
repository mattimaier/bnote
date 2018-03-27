<?php
/**
 * This class offers methods to test data
**/
class Regex {

 private $regex;
 private $regex_js;

 function __construct() {
   $specialchars = "äüöÄÜÖß";
   $this->regex["street"] = '/^[[:alpha:]' . $specialchars . '0-9\ \.\,\-\/\(\)]{1,100}$/';
   $this->regex_js["street"] = '^[\\\w' . $specialchars . '\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,100}$';

   $this->regex["zip"] = '/^\d{4,6}$/';
   $this->regex_js["zip"] = '^\\\d{4,6}$';
   
   $this->regex["city"] = '/^[[:alpha:]' . $specialchars . '0-9\ \.\,\-]{1,100}$/';
   $this->regex_js["city"] = '^[\\\w' . $specialchars . '\\\s\\\.\\\,\\\-]{3,100}$';

   $this->regex["phone"] = '/^[0-9\+\-\/\ \(\)]{1,29}$/';
   $this->regex_js["phone"] = '^[0-9\\\+\\\-\\\/\\\s\\\(\\\)]{1,29}$';
   
   $this->regex["email"] = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,4})$/';
   $this->regex_js["email"] = '^[a-zA-Z0-9][\\\w\.-]*@(?:[a-zA-Z0-9][a-zA-Z0-9_-]+\.)+[A-Z,a-z]{2,5}$';

   $this->regex["positive_amount"] = '/^\d{1,12}$/';
   $this->regex["positive_decimal"] = '/^\d{0,8}\,\d{0,2}$/';
   $this->regex["signed_amount"] = '/^-?\d{1,12}$/';
   $this->regex["money"] = '/^-?\d{0,8}[,\d{1,2}]$/';
   $this->regex["moneyEnglish"] = '/^-?\d{1,8}(\.\d{1,2})?$/';
   $this->regex["moneyEnglishFull"] = '/^-?\d{0,8}\.\d{1,2}$/';

   $this->regex["date"] = '/^\d{1,2}.\d{1,2}.\d{4}$/';
   $this->regex["time"] = '/^\d{2}:\d{2}$/';
   $this->regex["datetime"] = '/^\d{1,2}.\d{1,2}.\d{4}\ \d{1,2}:\d{2}$/';
   $this->regex["database_date"] = '/^\d{4}-\d{2}-\d{2}$/';
   $this->regex["db_datetime"] = '/^\d{4}-\d{2}-\d{2}\ \d{2}:\d{2}:\d{2}$/';

   $this->regex["subject"] = '/^[[:alnum:]' . $specialchars . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,255}$/';
   $this->regex["name"] = '/^[[:alnum:]' . $specialchars . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,100}$/';
   $this->regex["short_name"] = '/^[[:alnum:]' . $specialchars . '\ \.\,\-\(\)\?]{1,50}$/';
   $this->regex_js["name"] = '^[\\\w' . $specialchars . '\\\s\\\.\\\,\\\-\\\/\\\(\\\)\?]{1,100}$';
   $this->regex_js["short_name"] = '^[\\\w' . $specialchars . '\\\s\\\.\\\,\\\-\\\(\\\)\?]{1,50}$';

   $this->regex["accountno"] = '/^[a-zA-Z0-9\ ]{5,30}$/';
   $this->regex["bankno"] = '/^[a-zA-Z0-9\ ]{5,30}$/';

   $this->regex["kdnr"] = '/^\d{4}-\d{3,6}$/';
   $this->regex["password"] = '/^[[:alpha:]' . $specialchars . '0-9\ \.\-\,\;\:\_\+\&\#\'\/]{6,45}$/';
   $this->regex_js["password"] = '^[\\\w' . $specialchars . '\\\s\\\.\\\-\\\,\\\;\\\:\\\_\\\+\\\&\\\\#\\\'\\\/]{6,45}$';
   
   $this->regex["login"] = '/^[[:alnum:]\.\-\_]{3,45}$/';
   $this->regex_js["login"] = '^[\\\w\\\.\\\-\\\_]{3,45}$';
   
   $this->regex["dbid"] = '/^[0-9]{1,11}$/';
 }

 private function isCorrect($d, $type) {
 	$re = $this->regex[$type];
 	$langTypes = array(
 			"positive_amount", "positive_decimal", "signed_amount",
 			"date", "datetime"
 	);
 	if(in_array($type, $langTypes)) {
 		$re = Lang::regex($type);
 	}
 	
  	if(empty($d) || !preg_match($re, $d)) {
  		if($type == "password") $d = "-";
  		$this->fail($d, $type);
  	}
  	else {
  		return true;
  	}
 }

 // Default Methods to check against data fraud
 public function isStreet($d) { return $this->isCorrect($d, "street"); }
 public function isZip($d) { return $this->isCorrect($d, "zip"); }
 public function isCity($d) { return $this->isCorrect($d, "city"); }
 public function isPhone($d) { return $this->isCorrect($d, "phone"); }
 public function isEmail($d) { return $this->isCorrect($d, "email"); }
 public function isPositiveAmount($d) { return $this->isCorrect($d, "positive_amount"); }
 public function isSignedAmount($d) { return $this->isCorrect($d, "signed_amount"); }
 
 public function isMoney($d) {
 	// receives a decimal in language format
 	$dbDecimal = Data::convertToDb($d);
 	$match = preg_match($this->regex['moneyEnglish'], $dbDecimal);
 	if($match) {
 		return true;
 	}
 	else if($match == 0) {
 		new BNoteError("Betrag nicht erkannt.");
 	}
 	else {
 		$this->fail($d, "Betrag");
 	}
 	return false;
 }
 
 public function isDate($d) { return $this->isCorrect($d, "date"); }
 public function isTime($d) { return $this->isCorrect($d, "time"); }
 public function isDateTime($d) { return $this->isCorrect($d, "datetime"); }
 public function isSubject($d) { return $this->isCorrect($d, "subject"); }
 public function isName($d) { return $this->isCorrect($d, "name"); }
 public function isShortName($d) { return $this->isCorrect($d, "short_name"); }
 public function isAccountNo($d) { return $this->isCorrect($d, "accountno"); }
 public function isBankNo($d) { return $this->isCorrect($d, "bankno"); }
 public function isKdnr($d) { return $this->isCorrect($d, "kdnr"); }
 public function isPassword($d) { return $this->isCorrect($d, "password"); }
 public function isLogin($d) { return $this->isCorrect($d, "login"); }
 public function isDatabaseId($d) { return $this->isCorrect($d, "dbid"); }
 
 // Methods for special testing
 /**
  * Tests whether the given data is money in english format
  * @param data $d The data to test
  */
 public function isMoneyQuiet($d) {
 	if(empty($d) || !preg_match($this->regex["moneyEnglishFull"], $d)) return false;
  	 else return true;
 }
 
 /**
  * Tests whether the given data is a date/datetime in format Y-m-d H:i:s.
  * @param data $d The data to test.
  */
 public function isDatabaseDateQuiet($d) {
 	if(!empty($d)) {
 		// data not empty
 		if(preg_match($this->regex["database_date"], $d) ||
 			preg_match($this->regex["db_datetime"], $d)) return true;
 	}
 	return false;
 }
 
 /**
  * Tests whether text contains characters which should not be in a database query
  * @param data $d The data to test
  */
 public function isText($d) {
 	$fail = array();
 	
 	//$fail[0] = "'";  // check for '
 	$fail[1] = "\""; // check for "
 	$fail[2] = "\\"; // check for \
 	
 	// output / return
 	foreach($fail as $char) {
 		if(strpos($d, $char) === false) continue;
 		 else {
 		 	$this->fail($d, "Text >" . $char . "<");
 		 	return false;
 		 }
 	}
 	return true;
 }
 
 /**
  * Checks the input whether it is a decimal number with a max of 2 digits or
  * whether it is an integer - either one is returned with true.
  * @param String $d Data to check
  */
 public function isPositiveDecimalOrInteger($d) {
 	return (!empty($d) && (preg_match($this->regex["positive_amount"], $d)
 		|| preg_match($this->regex["positive_decimal"], $d)));
 }

 /**
  * Checks whether the number is a positive/negative integer or a decimal
  * @param String $d Data to check.
  */
 public function isNumber($d) {
 	return (!empty($d) && (preg_match($this->regex["signed_amount"], $d)
 		|| preg_match($this->regex["money"], $d)));
 }
 
 /**
  * Checks whether the number is a positive/negative integer
  * @param String $d Data to check.
  */
 public function isInteger($d) {
 	return (!empty($d) && preg_match($this->regex["signed_amount"], $d));
 }
 
 public function isLoginQuiet($d) {
 	return (!empty($d) && preg_match($this->regex["login"], $d));
 }
 
 /**
  * Calls for an error and prints it
  * @param unknown_type $d The given data
  * @param unknown_type $type The fieldtype which is wrong
  */
 private function fail($d, $type) {
 	new BNoteError("Ein oder mehrere Felder enthalten ungültige Werte. ($type / $d)");
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
	  foreach($this->regex_js as $type => $regex) {
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