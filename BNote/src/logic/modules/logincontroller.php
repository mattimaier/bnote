<?php

/**
 * Handles all login requests.
 * @author Matti
 *
 */
class LoginController extends DefaultController {
	
	/**
	 * If you turn on this flag you can see the hash value of the password
	 * which was entered, e.g. to save it in a database manually.
	 * @var Boolean
	 */
	private $SHOW_PASSWORD_HASH = false;
	
	/**
	 * Globally used encryption hash for the passwords.
	 * @var String
	 */
	const ENCRYPTION_HASH = 'BNot3pW3ncryp71oN';
	
	private $current_page;
	
	function __construct() {
		if(isset($_GET["mod"])) {
			$this->current_page = $_GET["mod"];
		}
		else {
			$this->current_page = "login";
		}
	}
	
	function start() {
		// show approprivate page
		if(isset($_GET["mode"]) && $_GET["mode"] == "login") {
			$this->doLogin();
		}
		else if(isset($_GET["mode"]) && $_GET["mode"] == "password") {
			$this->pwForgot();
		}
		else if(isset($_GET["mode"]) && $_GET["mode"] == "register") {
			$this->register();
		}
		else {
			$view = $this->getView();
			$func = $this->current_page;
			if($view != null) {
				$view->$func();
			}
		}
	}
	
	/**
	 * This function is executed from without the context of the rest of this controller.
	 * This way it's not possible to call too many fancy things. Just forward on success
	 * and show an echo on failure.
	 * @param Boolean $quite When true, no output is made, but true or false is returned.
	 * @return (Optional) True (login ok), false (not ok).
	 */
	function doLogin($quite = false) {
		// verify information
		$this->getData()->validateLogin();
		$db_pw = $this->getData()->getPasswordForLogin($_POST["login"]);
		$password = crypt($_POST["password"], LoginController::ENCRYPTION_HASH);
		
		if($this->SHOW_PASSWORD_HASH) {
			echo Lang::txt("LoginController_doLogin.message") . $password . "</br>\n";
		}
		
		if($db_pw == $password) {
			if(strpos($_POST["login"], "@") !== false) {
				$_SESSION["user"] = $this->getData()->getUserIdForEMail($_POST["login"]);
			}
			else {
				$_SESSION["user"] = $this->getData()->getUserIdForLogin($_POST["login"]);
			}
			$this->getData()->saveLastLogin();
		
			// go to application
			if($quite) {
				return true;
			}
			else {
				header("Location: ?mod=" . $this->getData()->getStartModuleId());
			}
		}
		else {
			if($quite) {
				return false;
			}
			else {
				new BNoteError(Lang::txt("LoginController_doLogin.error"));
			}
		}
	}
	
	private function pwForgot() {
		// validate input
		$this->getData()->validateEMail($_POST["email"]);
		
		// get user's id for email address
		$uid = $this->getData()->getUserIdForEMail($_POST["email"]);
		if($uid < 1) {
			new BNoteError(Lang::txt("LoginController_pwForgot.error"));
		}
		$username = $this->getData()->getUsernameForId($uid);
		
		// generate new password
		$password = $this->generatePassword(6);
		
		// generate email
		$subject = Lang::txt("LoginController_pwForgot.subject");
		$body = Lang::txt("LoginController_pwForgot.body_1") . "$username" . "\r\n";
		$body .= Lang::txt("LoginController_pwForgot.body_2") . "$password";
		
		// only change password if mail was sent
		require_once($GLOBALS["DIR_LOGIC"] . "mailing.php");
		$mail = new Mailing($_POST["email"], $subject, $body);
		
		if(!$mail->sendMail()) {
			// talk to leader
			new BNoteError(Lang::txt("LoginController_pwForgot.sendMailerror"));
		}
		else {					
			// Change password in system only if mail has been sent.
			$pwenc = crypt($password, LoginController::ENCRYPTION_HASH);
			$this->getData()->saveNewPassword($uid, $pwenc);
					
			// success message
			new Message(Lang::txt("LoginController_pwForgot.message_1"), Lang::txt("LoginController_pwForgot.message_2"));
		}
	}
	
	public static function generatePassword($length) {
		$chars = "abcdefghijkmnpqrstuvwxyz123456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
		while ($i <= $length) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	
	public function register($writeOutput=true) {
		// check agreement to terms
		if(!isset($_POST["terms"])) {
			new BNoteError(Lang::txt("LoginController_register.error_1"));
		}
		
		// validate data
		$this->getData()->validateRegistration();
		
		// check for duplicate login
		if($this->getData()->duplicateLoginCheck()) {
			new BNoteError(Lang::txt("LoginController_register.error_2"));
		}
		
		// check passwords and encrypt it
		if($_POST["pw1"] != $_POST["pw2"]) {
			new BNoteError(Lang::txt("LoginController_register.error_3"));
		}
		$password = crypt($_POST["pw1"], LoginController::ENCRYPTION_HASH);
		
		// create entities for complete user
		$aid = $this->getData()->createAddress(); // address id
		$cid = $this->getData()->createContact($aid); // contact id
		$uid = $this->getData()->createUser($_POST["login"], $password, $cid); // user id
		$this->getData()->createDefaultRights($uid);
		
		$outMsg = Lang::txt("LoginController_register.outMsg");
		if($writeOutput) {
			// write success
			new Message(Lang::txt("LoginController_register.writeOutput"), $outMsg);
		}
		
		global $system_data;
		$mailMessage = null;
		$mailOk = true;
		if($system_data->autoUserActivation()) {
			// create link for activation
			$linkurl = $system_data->getSystemURL() . "/src/export/useractivation.php?uid=$uid&email=" . $_POST["email"];
			if(substr($linkurl, 0, 4) != "http") {
				if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
					$linkurl = "https://$linkurl";
				}
				else {
					$linkurl = "http://$linkurl";
				}
			}
			$subject = Lang::txt("LoginController_register.subject");
			$message = Lang::txt("LoginController_register.message_1") . "<a href=\"$linkurl\">" . Lang::txt("LoginController_register.message_2") . "</a>";
			
			// send email to activate account and write message
			$dir_prefix = "";
			if(isset($GLOBALS['dir_prefix'])) {
				$dir_prefix = $GLOBALS['dir_prefix'];
			}
			require_once($dir_prefix . $GLOBALS["DIR_LOGIC"] . "mailing.php");
			$mail = new Mailing($_POST["email"], $subject, $message);
			
			if(!$mail->sendMail()) {
				$mailMessage = Lang::txt("LoginController_register.message_3");
				$mailOk = false;
			}
			else {
				$mailMessage = Lang::txt("LoginController_register.message_4");
			}
		}
		else {
			$mailMessage = Lang::txt("LoginController_register.message_5");
			$mailOk = false;
			$outMsg = $mailMessage;
		}
		if($mailMessage != null && $writeOutput) {
			echo $mailMessage;
		}
		
		return array("user" => $uid, "contact" => $cid, "address" => $aid, "mailOk" => $mailOk, "message" => $outMsg);
	}
	
	public static function createPin($db, $uid) {
		// create a pin
		$lower_bound = 100000;
		$upper_bound = 999999;
		$pin = 0;
		$pin_exists = 1;
			
		while($pin_exists > 0) {
			$pin = rand($lower_bound, $upper_bound);
			$pin_exists = $db->getCell($db->getUserTable(), "count(id)", "pin = $pin");
		}
			
		// save new pin
		$query = "UPDATE " . $db->getUserTable() . " SET pin = $pin WHERE id = $uid";
		$db->execute($query);
		
		return $pin;
	}
}

?>