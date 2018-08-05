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
		$this->setEntityName(Lang::txt("user"));
		$this->setJoinedAttributes(array(
			"contact" => array("name", "surname")
		));
	}

	function start() {
		Writing::p("Hier werden Benutzer, die sich am System anmelden können, verwaltet.");
		
		// show all users
		$table = new Table($this->getData()->getUsers());
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("contactsurname", "Nachname");
		$table->renameHeader("contactname", "Vorname");
		$table->renameHeader("isactive", "Aktiver Benutzer");
		$table->setColumnFormat("lastlogin", "DATE");
		$table->write();
	}
	
	function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		
		$gdpr = new Link($this->modePrefix() . "gdpr", "Datenschutzprüfung");
		$gdpr->addIcon("question");
		$gdpr->write();
	}
	
	function addEntity() {
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
					new BNoteError("Zugriff verweigert.");
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
	}
	
	function additionalViewButtons() {
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
	}
	
	function editEntityForm($write=true) {
		$user = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form($this->getData()->getUsername($_GET["id"]) . " bearbeiten", $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->addElement("Login", new Field("login", $user["login"], 99));
		$form->addElement("Passwort", new Field("password", "", FieldType::PASSWORD));
		$form->addHidden("isActive", $user["isActive"]);
		$dd = $this->contactDropdown();
		$dd->setSelected($user["contact"]);
		$form->addElement("Kontakt", $dd);
		$form->write();
		Writing::p("Wird das Passwort-Feld leer gelassen, bleibt das aktuelle Passwort gültig.");
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
	}
	
	protected function privilegesOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function privileges_process() {
		$this->checkID();
		$this->getData()->updatePrivileges($_GET["id"]);
		
		new Message("Änderungen gespeichert.", "Die Benutzerdaten wurden erfolgreich gespeichert.");
	}
	
	function privileges_processOptions() {
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"], Lang::txt("back"));
		$usrView->addIcon("arrow_left");
		$usrView->write();
	}
	
	function deleteConfirmationMessage($label, $linkDelete, $linkBack = null) {
		new Message("Löschen?", "Wollen sie diesen Benutzer mit allen seinen Dateien wirklich löschen?");
		$yes = new Link($linkDelete, strtoupper($label) . " LÖSCHEN");
		$yes->addIcon("remove");
		$yes->write();
		$this->buttonSpace();
		
		$no = new Link($linkBack, Lang::txt("back"));
		$no->addIcon("arrow_left");
		$no->write();
	}
	
	function gdpr() {
		// Check if a user has not logged in for 24 months. In this case, show the list and let the current user decide.
		Writing::h1("Datenschutzprüfung");
		Writing::p("Die folgenden Benutzer haben dieses System innerhalb der letzten 24 Monate nicht genutzt:");
		$inactiveUsers = $this->getData()->getLongInactiveUsers();
		$inactiveUsersList = new Plainlist($inactiveUsers);
		$inactiveUsersList->setNameField("login");
		$inactiveUsersList->write();
		
		Writing::p("Gemäß EU Datenschutzgrundverordnung (EU DSGVO) sind Sie verpflichtet, die Daten der Benutzer zu löschen. Dies beinhaltet folgende Datensätze:");
		$delData = array("Benutzerkonto", "Kontaktinformationen", "Teilnahmen", "Einladungen zu Auftritten, Proben und Touren");
		$delDataList = new Plainlist(Plainlist::simpleListToSelection($delData));
		$delDataList->write();
		
		$this->verticalSpace();
		
		$del = new Link($this->modePrefix() . "gdprDelete", "DATEN LÖSCHEN");
		$del->addIcon("cancel");
		$del->write();
	}
	
	function gdprDelete() {
		Writing::h1("Daten löschen");
		$inactiveUsers = $this->getData()->getLongInactiveUsers();
		
		$this->getData()->deleteUsersFull($inactiveUsers);
		
		Writing::p("Die Daten der folgenden Benutzer wurden gelöscht:");
		$inactiveUsersList = new Plainlist($inactiveUsers);
		$inactiveUsersList->setNameField("login");
		$inactiveUsersList->write();
	}
}

?>