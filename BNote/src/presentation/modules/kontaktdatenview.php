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
		Writing::h1("Meine Kontaktdaten");
		
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
		
		// change password
		$pwNote = "Bitte gebe mindestens 6 Zeichen und keine Leerzeichen ein um dein Passwort zu ändern.";
		
		$form2 = new Form("Passwort &auml;ndern<br/><p style=\"font-weight: normal;\">$pwNote</p>", $this->modePrefix() . "password");
		$form2->addElement("Neues Passwort", new Field("pw1", "", FieldType::PASSWORD));
		$form2->addElement("Passwort Wiederholen", new Field("pw2", "", FieldType::PASSWORD));
		$form2->write();
		
		// show mobile PIN
		$pin = $this->getData()->getPIN($_SESSION["user"]);
		$form3 = new Form("Mobile PIN", "");
		$form3->addElement("Deine Mobile PIN:", new Field("pin", $pin, 99));
		$form3->removeSubmitButton();
		$form3->write();
	}
	
	function savePD() {
		$this->getData()->update($_SESSION["user"], $_POST);
		new Message("Daten gespeichert", "Die &Auml;nderungen wurden gespeichert.");
		$this->backToStart();
	}
	
	function password() {
		$this->getData()->updatePassword();
		new Message("Passwort ge&auml;ndert", "Das Passwort wurde ge&auml;ndert.<br />Ab sofort bitte mit neuem Passwort anmelden.");
		$this->backToStart();
	}
}