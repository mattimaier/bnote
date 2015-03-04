<?php

/**
 * View to manage the user's personal data.
 * @author matti
 *
 */
class KontaktdatenView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {		
		// personal data
		$contact = $this->getData()->getContactForUser($_SESSION["user"]);
		if($contact <= 0) {
			Writing::p("Ihrem Benutzer wurde kein Kontakt zugeordnet.");
			return;
		}
		$form1 = new Form("Pers&ouml;nliche Daten &auml;ndern", $this->modePrefix() . "savePD");
		$form1->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $contact["id"]);
		$form1->removeElement("id");
		$form1->removeElement("notes");
		$form1->removeElement("address");
		$form1->removeElement("status");
		$form1->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$address = $this->getData()->getAddress($contact["address"]);
		$form1->addElement("Stra&szlig;e", new Field("street", $address["street"], FieldType::CHAR));
		$form1->addElement("Stadt", new Field("city", $address["city"], FieldType::CHAR));
		$form1->addElement("PLZ", new Field("zip", $address["zip"], FieldType::CHAR));
		
		$form1->write();
	}
	
	function startOptions() {
		$chPw = new Link($this->modePrefix() . "changePassword", "Passwort ändern");
		$chPw->addIcon("key");
		$chPw->write();
		$this->buttonSpace();
		
		$settings = new Link($this->modePrefix() . "settings", "Meine Einstellungen");
		$settings->addIcon("settings");
		$settings->write();
		$this->verticalSpace();
	}
	
	function savePD() {
		$this->getData()->update($_SESSION["user"], $_POST);
		new Message("Daten gespeichert", "Die &Auml;nderungen wurden gespeichert.");
	}
	
	function changePassword() {		
		// change password
		$pwNote = "Bitte gebe mindestens 6 Zeichen und keine Leerzeichen ein um dein Passwort zu ändern.";
		
		$form2 = new Form("Passwort &auml;ndern<br/><p style=\"font-weight: normal;\">$pwNote</p>", $this->modePrefix() . "password");
		$form2->addElement("Neues Passwort", new Field("pw1", "", FieldType::PASSWORD));
		$form2->addElement("Passwort Wiederholen", new Field("pw2", "", FieldType::PASSWORD));
		$form2->write();
	}
	
	function password() {
		$this->getData()->updatePassword();
		new Message("Passwort ge&auml;ndert", "Das Passwort wurde ge&auml;ndert.<br />Ab sofort bitte mit neuem Passwort anmelden.");
	}
	
	function settings() {		
		$form = new Form("Einstellungen ändern", $this->modePrefix() . "saveSettings");
		
		// E-Mail Notification
		$default = $this->getData()->getSysdata()->userEmailNotificationOn() ? "1" : "0";
		$form->addElement("E-Mail Benachrichtigung an", new Field("email_notification", $default, FieldType::BOOLEAN));
		
		$form->write();
	}
	
	function saveSettings() {
		$this->getData()->saveSettings($_SESSION["user"]);
		
		new Message("Einstellungen gespeichert", "Deine Einstellungen wurden gesperichert.");
	}
}