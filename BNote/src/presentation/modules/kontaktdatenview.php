<?php

/**
 * View to manage the user's personal data.
 * @author matti
 *
 */
class KontaktdatenView extends CrudRefLocationView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {		
		// personal data
		$contact = $this->getData()->getContactForUser($this->getUserId());
		if($contact <= 0) {
			Writing::p(Lang::txt("KontaktdatenView_start.message"));
			return;
		}
		
		$form = new Form(Lang::txt("KontaktdatenView_start.Form"), $this->modePrefix() . "savePD");
		
		$form->addElement(Lang::txt("KontakteData_construct.name"), new Field("name", $contact["name"], FieldType::CHAR), true, 4);
		$form->addElement(Lang::txt("KontakteData_construct.surname"), new Field("surname", $contact["surname"], FieldType::CHAR), true, 4);
		$form->addElement(Lang::txt("KontakteData_construct.nickname"), new Field("nickname", $contact["nickname"], FieldType::CHAR), false, 4);
		
		$form->addElement(Lang::txt("KontakteData_construct.email"), new Field("email", $contact["email"], FieldType::EMAIL), true, 4);
		$form->addElement(Lang::txt("KontakteData_construct.birthday"), new Field("birthday", $contact["birthday"], FieldType::DATE), false, 4);
		$form->addElement(Lang::txt("KontakteData_construct.instrument"), new Dropdown("instrument"), true, 4);
		$form->setForeign(Lang::txt("KontakteData_construct.instrument"), "instrument", "id", "name", $contact["instrument"]);
		
		# Website is not relevant for the most people who use the system, just for contacts
		# $form->addElement(Lang::txt("KontakteData_construct.web"), new Field("web", $contact["web"], FieldType::CHAR), false, 4);
				
		$address = $this->getData()->getAddress($contact["address"]);
		$this->addAddressFieldsToForm($form, $address);
		
		$form->addElement(Lang::txt("KontakteData_construct.phone"), new Field("phone", $contact["phone"], FieldType::CHAR), false, 3);
		$form->addElement(Lang::txt("KontakteData_construct.mobile"), new Field("mobile", $contact["mobile"], FieldType::CHAR), false, 3);
		$form->addElement(Lang::txt("KontakteData_construct.company"), new Field("company", $contact["company"], FieldType::CHAR), false, 3);
		$form->addElement(Lang::txt("KontakteData_construct.business"), new Field("business", $contact["business"], FieldType::CHAR), false, 3);
		
		// custom data
		$this->appendCustomFieldsToForm($form, 'c', $contact, true);
		
		// privacy settings
		$form->addElement(Lang::txt("KontaktdatenView_start.share_email"), new Field("share_email", $contact["share_email"], FieldType::BOOLEAN), false, 12);
		$form->addElement(Lang::txt("KontaktdatenView_start.share_address"), new Field("share_address", $contact["share_address"], FieldType::BOOLEAN), false, 12);
		$form->addElement(Lang::txt("KontaktdatenView_start.share_phones"), new Field("share_phones", $contact["share_phones"], FieldType::BOOLEAN), false, 12);
		$form->addElement(Lang::txt("KontaktdatenView_start.share_birthday"), new Field("share_birthday", $contact["share_birthday"], FieldType::BOOLEAN), false, 12);
		
		$form->write();
	}
	
	function startOptions() {
		$chPw = new Link($this->modePrefix() . "changePassword", Lang::txt("KontaktdatenView_startOptions.changePassword"));
		$chPw->addIcon("key");
		$chPw->write();
		
		$settings = new Link($this->modePrefix() . "settings", Lang::txt("KontaktdatenView_startOptions.settings"));
		$settings->addIcon("settings");
		$settings->write();
	}
	
	function savePD() {
		$this->getData()->update($this->getUserId(), $_POST);		
		new Message(Lang::txt("KontaktdatenView_savePD.Message_1"), Lang::txt("KontaktdatenView_savePD.Message_2"));
	}
	
	function changePassword() {		
		// change password
		$pwNote = Lang::txt("KontaktdatenView_changePassword.Message");
		
		$form2 = new Form(Lang::txt("KontaktdatenView_changePassword.Form"), $this->modePrefix() . "password");
		$form2->addElement("", new Field("", $pwNote, 99));
		$form2->addElement(Lang::txt("KontaktdatenView_changePassword.New"), new Field("pw1", "", FieldType::PASSWORD));
		$form2->addElement(Lang::txt("KontaktdatenView_changePassword.Repeat"), new Field("pw2", "", FieldType::PASSWORD));
		$form2->write();
	}
	
	function password() {
		$this->getData()->updatePassword();
		new Message(Lang::txt("KontaktdatenView_password.Message_1"), Lang::txt("KontaktdatenView_password.Message_2"));
	}
	
	function settings() {		
		$form = new Form(Lang::txt("KontaktdatenView_settings.saveSettings"), $this->modePrefix() . "saveSettings");
		
		// E-Mail Notification
		$default = $this->getData()->getSysdata()->userEmailNotificationOn() ? "1" : "0";
		$form->addElement(Lang::txt("KontaktdatenView_settings.email_notification"), new Field("email_notification", $default, FieldType::BOOLEAN));
		
		$form->write();
	}
	
	function saveSettings() {
		$this->getData()->saveSettings($this->getUserId());
		
		new Message(Lang::txt("KontaktdatenView_saveSettings.Message_1"), Lang::txt("KontaktdatenView_saveSettings.Message_2"));
	}
}