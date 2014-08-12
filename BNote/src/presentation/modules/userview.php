<?php
/**
 * View for user module.
 * @author matti
 *
 */
class UserView extends CrudRefView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Benutzer");
		$this->setJoinedAttributes(array(
			"contact" => array("name", "surname")
		));
	}

	function start() {
		Writing::h1("Benutzer");
		Writing::p("Hier k&ouml;nnen Benutzer verwaltet werden. Benutzer k&ouml;nnen sich am System anmelden.");
		
		// show button to add a user
		$addBtn = new Link($this->modePrefix() . "addUser", "Benutzer hinzufügen");
		$addBtn->addIcon("add");
		$addBtn->write();
		
		// show all users
		$table = new Table($this->getData()->getUsers());
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("contactsurname", "Nachname");
		$table->renameHeader("contactname", "Vorname");
		$table->renameHeader("isactive", "Akiver Benutzer");
		$table->setColumnFormat("lastlogin", "DATE");
		$table->write();
	}
	
	function addUser() {
		// add form for new user
		$form = new Form("Neuer Benutzer", $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("lastlogin");
		$form->removeElement("isActive");
		$form->removeElement("contact");
		
		// manually add contacts
		$form->addElement("Kontakt", $this->contactDropdown());
		$form->write();
		
		$this->verticalSpace();
		
		// back button
		$this->backToStart();
	}
	
	private function contactDropdown() {
		$dd = new Dropdown("contact");
		
		// add no-contact option
		$dd->addOption("[kein Kontakt]", 0);
		
		$contacts = $this->getData()->getContacts();
		for($i = 1; $i < count($contacts); $i++) {
			$label = $contacts[$i]["name"] . " " . $contacts[$i]["surname"];
			$instr = isset($contacts[$i]["instrumentname"]) ? $contacts[$i]["instrumentname"] : '';
			if($instr != "") $label .= " (" . $contacts[$i]["instrumentname"] . ")";
			$dd->addOption($label, $contacts[$i]["id"]);
		}
		
		return $dd;
	}
	
	function view() {
		$this->checkID();
		
		// restrict access to super user for non-super-users
		if(!$this->getData()->getSysdata()->isUserSuperUser()
				&& $this->getData()->getSysdata()->isUserSuperUser($_GET["id"])) {
			new Error("Zugriff verweigert.");
		}
		
		// get current user
		$usr = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		
		// show header
		if(isset($usr["contactsurname"])) {
			$title = $usr["login"]  . " / " . $usr["contactsurname"] . ", " . $usr["contactname"];
		}
		else {
			$usr = $this->getData()->findByIdNoRef($_GET["id"]);
			$title = "Benutzer " . $usr["login"];
		}
		Writing::h1($title);
		
		// show options
		$edit = new Link("?mod=" . $this->getModId() . "&mode=edit&id=" . $_GET["id"], "Benutzer bearbeiten");
		$edit->addIcon("edit");
		$edit->write();
		$this->buttonSpace();
		$privs = new Link("?mod=" . $this->getModId() . "&mode=privileges&id=" . $_GET["id"], "Rechte bearbeiten");
		$privs->addIcon("key");
		$privs->write();
		$this->buttonSpace();
		
		if($this->getData()->isUserActive($_GET["id"])) {
			$btnLbl = "Benutzer deaktivieren";
			$btnIcon = "no_entry";
		}
		else {
			$btnLbl = "Benutzer aktivieren";
			$btnIcon = "checkmark";
		}
		$active = new Link($this->modePrefix() . "activate&id=" . $_GET["id"], $btnLbl);
		$active->addIcon($btnIcon);
		$active->write();
		$this->buttonSpace();
		
		$delete = new Link("?mod=" . $this->getModId() . "&mode=delete_confirm&id=" . $_GET["id"], "Benutzer l&ouml;schen");
		$delete->addIcon("remove");
		$delete->write();
		$this->buttonSpace();
		
		// show user data
		$dv = new Dataview();
		foreach($usr as $id => $value) {
			if($id == "contact" && $value == "0") {
				$dv->addElement($id, "-");
			}
			else if($id != "password") {
				$dv->addElement($id, $value);
			}
		}
		$dv->autoRename($this->getData()->getFields());
		$dv->renameElement("contactname", "Vorname");
		$dv->renameElement("contactsurname", "Nachname");
		$dv->write();
		
		// back button
		$this->backToStart();
	}
	
	function editEntityForm() {
		$user = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form($this->getData()->getUsername($_GET["id"]) . " bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->addElement("Login", new Field("login", $user["login"], 99));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->addHidden("isActive", $user["isActive"]);
		$dd = $this->contactDropdown();
		$dd->setSelected($user["contact"]);
		$form->addElement("Kontakt", $dd);
		$form->write();
		Writing::p("Wird das Passwort-Feld leer gelassen, bleibt das aktuelle Passwort g&uuml;ltig.");
	}
	
	function edit_process() {
		if($_POST["contact"] == "0") unset($_POST["contact"]);
		parent::edit_process();
	}
	
	function privileges() {
		$this->checkID();
		
		global $system_data;
		$form = new Form("Privileges for " . $this->getData()->getUsername($_GET["id"]),
							$this->modePrefix() . "privileges_process&id=" . $_GET["id"]);
		foreach($system_data->getModuleArray() as $mid => $name) {
			$selected = "";
			if($this->getData()->hasUserPrivilegeForModule($_GET["id"], $mid)) $selected = "checked";
			$form->addElement($name, new Field($mid, $selected, FieldType::BOOLEAN));
		}
		$form->write();
		echo "<br /><br />\n";
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Zur&uuml;ck");
		$usrView->addIcon("arrow_left");
		$usrView->write();
	}
	
	function privileges_process() {
		$this->checkID();
		$this->getData()->updatePrivileges($_GET["id"]);
		
		new Message("&Auml;nderungen gespeichert.", "Die Benutzerdaten wurden erfolgreich gespeichert.");
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Zur&uuml;ck");
		$usrView->addIcon("arrow_left");
		$usrView->write();
	}
	
	function deleteConfirmationMessage($label, $linkDelete, $linkBack) {
		new Message("L&ouml;schen?", "Wollen sie diesen Benutzer mit allen seinen Dateien wirklich l&ouml;schen?");
		$yes = new Link($linkDelete, strtoupper($label) . " L&Ouml;SCHEN");
		$yes->addIcon("remove");
		$yes->write();
		$this->buttonSpace();
		
		$no = new Link($linkBack, "Zur&uuml;ck");
		$no->addIcon("arrow_left");
		$no->write();
	}
}

?>