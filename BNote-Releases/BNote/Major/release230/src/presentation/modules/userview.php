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
		$table->write();
	}
	
	function addUser() {
		// add form for new user
		$form = new Form("Neuer Benutzer", $this->modePrefix() . "add");
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
		
		// get current user
		$usr = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		
		// show header
		if(isset($usr["contactsurname"])) {
			$title = $usr["login"]  . " / " . $usr["contactsurname"] . ", " . $usr["contactname"];
		}
		else {
			$usr = $this->getData()->findByIdNoRef($_GET["id"]);
			$title = "User " . $usr["login"];
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
			if($id != "password") {
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
		$form->addElement("Login", new Field("login", $user["login"], FieldType::CHAR));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->addHidden("isActive", $user["isActive"]);
		$dd = $this->contactDropdown();
		$dd->setSelected($user["contact"]);
		$form->addElement("Kontakt", $dd);
		$form->write();
		Writing::p("Wird das Passwort-Feld leer gelassen, bleibt das aktuelle Passwort g&uuml;ltig.");
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
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"],
							"Go back");
		$usrView->write();
	}
	
	function privileges_process() {
		$this->checkID();
		$this->getData()->updatePrivileges($_GET["id"]);
		
		new Message("&Auml;nderungen gespeichert.", "Die Benutzerdaten wurden erfolgreich gespeichert.");
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"],
							"Go back");
		$usrView->write();
	}
	
	function activate() {
		$this->checkID();
		
		// change status of user and send email in case the user was activated
		if($this->getData()->changeUserStatus($_GET["id"])) {
			global $system_data;
			$to = $this->getData()->getUsermail($_GET["id"]);
			$subject = "Benutzerkonto freigeschaltet.";
			$body = "Dein " . $system_data->getCompany() . " Benutzerkonto wurde aktiviert. ";
			$body .= "Du kannst dich nun unter " . $system_data->getSystemURL() . " anmelden.";
			if(!mail($to, $subject, $body)) {
				new Message("Aktivierungsemail fehlgeschlagen",
						"Das Senden der Aktivierungsemail war nicht erfolgreich. Bitte benachrichtigen Sie den Benutzer selbst.");
			}
		}
		
		// simply show the user view again
		$this->view();
	}
}

?>