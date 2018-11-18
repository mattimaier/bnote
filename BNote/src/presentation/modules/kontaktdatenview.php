<?php
require_once $GLOBALS["DIR_PRESENTATION"] . "crudreflocationview.php";

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
		$contact = $this->getData()->getContactForUser($_SESSION["user"]);
		if($contact <= 0) {
			Writing::p("Ihrem Benutzer wurde kein Kontakt zugeordnet.");
			return;
		}
		$cid = $contact["id"];
		
		$form = new Form("Persönliche Daten ändern", $this->modePrefix() . "savePD");
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $cid);
		$form->removeElement("id");
		$form->removeElement("notes");
		$form->removeElement("address");
		$form->removeElement("status");
		$form->removeElement("is_conductor");
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$address = $this->getData()->getAddress($contact["address"]);
		$this->addAddressFieldsToForm($form, $address);
		
		// custom data
		$this->appendCustomFieldsToForm($form, 'c', $contact, true);
		
		$form->write();
	}
	
	function startOptions() {
		$chPw = new Link($this->modePrefix() . "changePassword", "Passwort ändern");
		$chPw->addIcon("key");
		$chPw->write();
		$this->buttonSpace();
		
		$settings = new Link($this->modePrefix() . "settings", "Meine Einstellungen");
		$settings->addIcon("settings");
		$settings->write();
	}
	
	function savePD() {
		$this->getData()->update($_SESSION["user"], $_POST);		
		new Message("Daten gespeichert", "Die Änderungen wurden gespeichert.");
	}
	
	function changePassword() {		
		// change password
		$pwNote = "Bitte gebe mindestens 6 Zeichen und keine Leerzeichen ein um dein Passwort zu ändern.";
		
		$form2 = new Form("Passwort ändern<br/><p style=\"font-weight: normal;\">$pwNote</p>", $this->modePrefix() . "password");
		$form2->addElement("Neues Passwort", new Field("pw1", "", FieldType::PASSWORD));
		$form2->addElement("Passwort Wiederholen", new Field("pw2", "", FieldType::PASSWORD));
		$form2->write();
	}
	
	function password() {
		$this->getData()->updatePassword();
		new Message("Passwort geändert", "Das Passwort wurde geändert.<br />Ab sofort bitte mit neuem Passwort anmelden.");
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