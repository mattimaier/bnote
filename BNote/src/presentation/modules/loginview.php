<?php

/**
 * Login views.
 * @author matti
 *
 */
class LoginView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		include $GLOBALS["DIR_PRESENTATION"] . "logo.php";
	}
	
	function showOptions() {
		// do not show any options here
	}
	
	function login() {
		if(isset($_GET["fwd"])) {
			new Message(Lang::txt("LoginView_login.fwd_header"), Lang::txt("LoginView_login.fwd_message"));
		}
		
		Writing::p(Lang::txt("LoginView_login.message_2"), "text-dark mt-2");
		
		// login form
		$form = new Form("", $this->modePrefix() . "login");
		$form->addElement(Lang::txt("LoginView_login.login"), new Field("login", "", FieldType::CHAR));
		$form->addElement(Lang::txt("LoginView_login.password"), new Field("password", "", FieldType::PASSWORD));
		if(isset($_GET["fwd"])) {
			$form->addHidden("fwd", $_GET["fwd"]);
		}
		$form->changeSubmitButton(Lang::txt("navigation_Login"));
		$form->write();
	}
	
	function forgotPassword() {
		Writing::p(Lang::txt("LoginView_forgotPassword.message"));
		
		// forgotten password form
		$form = new Form("", $this->modePrefix() . "password");
		$form->addElement(Lang::txt("LoginView_forgotPassword.email"), new Field("email", "", FieldType::EMAIL), true);
		$form->write();
	}
	
	function registration() {
		/* check if user registration is on */
		$user_reg = $this->getData()->getSysdata()->getDynamicConfigParameter("user_registration");
		if($user_reg == 0) {
			new BNoteError(Lang::txt("LoginView_registration.registration_deactivated"));
		}
		
		$form = new Form("", $this->modePrefix() . "register");
		
		$form->addElement(Lang::txt("LoginView_registration.first_name"), new Field("name", "", FieldType::CHAR), true, 3);
		$form->addElement(Lang::txt("LoginView_registration.surname"), new Field("surname", "", FieldType::CHAR), true, 3);
		$form->addElement(Lang::txt("LoginView_registration.nickname"), new Field("nickname", "", FieldType::CHAR), false, 3);
		$form->addElement(Lang::txt("LoginView_registration.birthday"), new Field("birthday", "", FieldType::DATE), false, 3);
		
		$instruments = $this->getData()->getInstruments();
		$cats = $this->getData()->getSysdata()->getInstrumentCategories();
		$instrumentDropdown = new Dropdown("instrument");
		for($i = 1; $i < count($instruments); $i++) {
			// filter instruments of categories
			if(!in_array($instruments[$i]["cat"], $cats)) continue;
			$label = $instruments[$i]["category"] . ": " . $instruments[$i]["instrument"];
			$instrumentDropdown->addOption($label, $instruments[$i]["id"]);
		}
		$form->addElement(Lang::txt("LoginView_registration.instrument"), $instrumentDropdown);
		
		$form->addElement(Lang::txt("LoginView_registration.email"), new Field("email", "", FieldType::EMAIL), true, 3);
		$form->addElement(Lang::txt("LoginView_registration.phone"), new Field("phone", "", FieldType::CHAR), false, 3);
		$form->addElement(Lang::txt("LoginView_registration.mobile"), new Field("mobile", "", FieldType::CHAR), false, 3);		
		$form->addElement(Lang::txt("LoginView_registration.country"), $this->buildCountryDropdown(""));
		$form->addElement(Lang::txt("LoginView_registration.street"), new Field("street", "", FieldType::CHAR), true, 3);
		$form->addElement(Lang::txt("LoginView_registration.zip"), new Field("zip", "", FieldType::CHAR), true, 1);
		$form->addElement(Lang::txt("LoginView_registration.city"), new Field("city", "", FieldType::CHAR), true, 2);
				
		$form->addElement(Lang::txt("LoginView_registration.pw1"), new Field("pw1", "", FieldType::PASSWORD), true, 3);
		$form->addElement(Lang::txt("LoginView_registration.pw2"), new Field("pw2", "", FieldType::PASSWORD), true, 3);
		$termLabel = Lang::txt("LoginView_registration.terms_1") . '<a href="?mod=' . $this->getData()->getSysdata()->getModuleId("Terms") . 
			'" style="text-decoration: underline;" target="_blank">' . Lang::txt("LoginView_registration.terms_2") . '</a>' 
					. Lang::txt("LoginView_registration.terms_3");
		$form->addElement($termLabel, new Field("terms", "", FieldType::BOOLEAN));
		
		$form->changeSubmitButton(Lang::txt("LoginView_registration.register"));
		$form->write();
	}
	
	function impressum() {
		include "data/impressum.html";
	}
	
	function terms() {
		include "data/terms.html";
	}
	
	public function gdpr() {
		include "data/gdpr.php";
	}
	
	public function extGdpr() {
		?>
		<style> #content_insets { margin-left: 1%; } </style>
		<?php
		Writing::h2(Lang::txt("LoginView_extGdpr.title"));
		
		// validate code
		if(!isset($_GET["code"])) {
			new BNoteError(Lang::txt("LoginView_extGdpr.error"));
		}
		$code = $_GET["code"];
		
		// process approval
		if(isset($_GET["sub"]) && $_GET["sub"] == "ok") {
			$this->getData()->gdprOk($code);
			new Message(Lang::txt("LoginView_extGdpr.message_1"), Lang::txt("LoginView_extGdpr.message_2"));
			return;
		}
		
		// show acceptance
		$contact = $this->getData()->findContactByCode($code);
		if($contact == null) {
			new BNoteError(Lang::txt("LoginView_extGdpr.codeerror"));
		}
		
		Writing::p(Lang::txt("LoginView_extGdpr.codemessage"));
		$dv = new Dataview();
		$dv->addElement(Lang::txt("LoginView_extGdpr.name"), $contact["name"] . " " . $contact["surname"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.nickname"), $contact["nickname"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.phone"), $contact["phone"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.fax"), $contact["fax"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.mobile"), $contact["mobile"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.business"), $contact["business"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.email"), $contact["email"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.web"), $contact["web"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.street"), $contact["street"] . ", " . $contact["zip"] . " " . $contact["city"]);
		$dv->addElement(Lang::txt("LoginView_extGdpr.birthday"), $contact["birthday"]);
		$dv->write();
		
		$ok = new Link("?mod=extGdpr&sub=ok&code=$code", Lang::txt("LoginView_extGdpr.link"));
		$ok->write();
	}
}

?>