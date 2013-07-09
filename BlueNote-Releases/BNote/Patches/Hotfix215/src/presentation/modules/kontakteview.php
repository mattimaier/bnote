<?php

/**
 * View for contact module.
 * @author matti
 *
 */
class KontakteView extends CrudRefView {
	
	/**
	 * Create the contact view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Kontakt");
		$this->setJoinedAttributes(array(
			"address" => array("street", "city", "zip"),
			"instrument" => array("name")
		));
	}
	
	function start() {
		Writing::h1("Kontakte");
		
		// Options		
		$ext = new Link($this->modePrefix() . "showContacts&status=externals", "Externe Mitspieler");
		$ext->write();
		$this->buttonSpace();
		
		$ext = new Link($this->modePrefix() . "showContacts&status=applicants", "Bewerber");
		$ext->write();
		$this->buttonSpace();
		
		$other = new Link($this->modePrefix() . "showContacts&status=others", "Sonstige Kontakte");
		$other->write();
		$this->buttonSpace();
		
		$ext = new Link($this->modePrefix() . "showContacts&status=admins", "Administratoren");
		$ext->write();
		$this->verticalSpace();
		
		$add = new Link($this->modePrefix() . "addForm", "Kontakt hinzuf&uuml;gen");
		$add->write();
		$this->buttonSpace();
		
		$export = new Link($this->modePrefix() . "printMembers", "Mitspielerliste drucken");
		$export->write();
		$this->buttonSpace();
		
		$vc = new Link($GLOBALS["DIR_EXPORT"] . "kontakte.vcd", "Kontakte exportieren");
		$vc->setTarget("_blank");
		$vc->write();
		$this->verticalSpace();
		
		// show band members
		$_GET["status"] = "members";
		$this->showContacts();
	}
	
	function showContacts() {
		$title = "";
		$data = array();
		
		switch($_GET["status"]) {
			case "admins":		$title = "Administratoren";
								$data = $this->getData()->getAdmins();
								break;
			case "members":		$title = "Band Mitspieler";
								$data = $this->getData()->getMembers();
								break;
			case "externals":	$title = "Externe Mitspieler";
								$data = $this->getData()->getExternals();
								break;
			case "applicants":	$title = "Bewerber";
								$data = $this->getData()->getApplicants();
								break;
			case "others":		$title = "Sonstige Kontakte";
								$data = $this->getData()->getOthers();
								break;
		}
		
		// write
		Writing::h2($title);
		$this->showContactTable($data);
		
		$this->verticalSpace();
		if($_GET["status"] != "members") {
			$this->backToStart();
			$this->verticalSpace();
		}
	}
	
	private function showContactTable($data) {
		$table = new Table($data);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->renameHeader("street", "Stra&szlig;e");
		$table->renameHeader("city", "Stadt");
		$table->renameHeader("zip", "PLZ");
		$table->removeColumn("10");
		$table->removeColumn("instrument");
		$table->removeColumn("9");
		$table->removeColumn("status");
		$table->removeColumn("8");
		$table->removeColumn("address");
		$table->renameHeader("instrumentname", "Instrument");
		$table->write();
	}
	
	function addForm() {
		$form = new Form("Kontakt hinzuf&uuml;gen", $this->modePrefix() . "add");
		
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", -1);
		$form->addForeignOption("instrument", "[keine Angabe]", 0);
		
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", "", FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", "", FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", "", FieldType::CHAR));
		
		$form->removeElement("status");
		$dd = new Dropdown("status");
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_ADMIN), KontakteData::$STATUS_ADMIN);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_MEMBER), KontakteData::$STATUS_MEMBER);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_EXTERNAL), KontakteData::$STATUS_EXTERNAL);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_APPLICANT), KontakteData::$STATUS_APPLICANT);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_OTHER), KontakteData::$STATUS_OTHER);
		$dd->setSelected(KontakteData::$STATUS_MEMBER);
		$form->addElement("Status", $dd);
		
		$form->write();
	}
	
	function viewDetailTable() {
		$entity = $this->getData()->getContact($_GET["id"]);
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->removeElement("Instrument");
		$details->renameElement("instrumentname", "Instrument");
		$details->removeElement("Adresse");
		$details->renameElement("street", "Stra&szlig;e");
		$details->renameElement("zip", "PLZ");
		$details->renameElement("city", "Stadt");
		$details->write();
	}
	
	function additionalViewButtons() {
		// only show when it doesn't already exist
		if(!$this->getData()->hasContactUserAccount($_GET["id"])) {
			// show button
			$btn = new Link($this->modePrefix() . "createUserAccount&id=" . $_GET["id"],
						"Benutzerkonto erstellen");
			$btn->write();
			$this->buttonSpace();
		}
	}
	
	function editEntityForm() {
		$contact = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form("Kontakt hinzuf&uuml;gen", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->setForeign("instrument", "instrument", "id", "name", $contact["instrument"]);
		
		$address = $this->getData()->getAddress($contact["address"]);
		$form->removeElement("address");
		$form->addElement("Stra&szlig;e", new Field("street", $address["street"], FieldType::CHAR));
		$form->addElement("Stadt", new Field("city", $address["city"], FieldType::CHAR));
		$form->addElement("PLZ", new Field("zip", $address["zip"], FieldType::CHAR));
		
		$form->removeElement("status");
		$dd = new Dropdown("status");
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_ADMIN), KontakteData::$STATUS_ADMIN);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_MEMBER), KontakteData::$STATUS_MEMBER);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_EXTERNAL), KontakteData::$STATUS_EXTERNAL);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_APPLICANT), KontakteData::$STATUS_APPLICANT);
		$dd->addOption($this->getData()->statusCaption(KontakteData::$STATUS_OTHER), KontakteData::$STATUS_OTHER);
		$dd->setSelected($contact["status"]);
		$form->addElement("Status", $dd);
		
		$form->write();
	}
	
	function printMembers() {
		Writing::h2("Mitspielerliste");
		
		// determine filename
		$filename = $GLOBALS["DATA_PATHS"]["members"];
		$filename .= "Mitspielerliste-" . date('Y-m-d') . ".pdf";
		
		// create report
		require_once $GLOBALS["DIR_PRINT"] . "memberlist.php";
		new MembersPDF($filename, $this->getData(), KontakteData::$STATUS_MEMBER);
		
		// show report
		echo "<embed src=\"$filename\" width=\"90%\" height=\"700px\" />\n";
		echo "<br /><br />\n";
		
		// back button
		$this->backToStart();
		$this->verticalSpace();
	}
	
	function userCreatedAndMailed($username, $email) {
		$m = "Die Zugangsdaten wurden an $email geschickt.";
		new Message("Benutzer $username erstellt", $m);
		$this->backToViewButton($_GET["id"]);
	}
	
	function userCredentials($username, $password) {
		$m = "<br />Die Zugangsdaten konnten dem Benutzer nicht zugestellt werden ";
		$m .= "da keine E-Mail-Adresse hinterlegt ist oder die E-Mail nicht ";
		$m .= "versandt werden konnte. Bitte teile dem Benutzer folgende ";
		$m .= "Zugangsdaten mit:<br /><br />";
		$m .= "Benutzername <strong>$username</strong><br />";
		$m .= "Passwort <strong>$password</strong>";
		new Message("Benutzer $username erstellt", $m);
		$this->backToViewButton($_GET["id"]);
	}
}

?>