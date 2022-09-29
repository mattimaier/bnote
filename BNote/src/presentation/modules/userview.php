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
		$this->setEntityName(Lang::txt("UserView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("UserView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
			"contact" => array("name", "surname")
		));
	}

	function start() {
		Writing::p(Lang::txt("UserView_start.message"));
		
		// show all users
		$table = new Table($this->getData()->getUsers());
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("contactsurname", Lang::txt("UserView_start.contactsurname"));
		$table->renameHeader("contactname", Lang::txt("UserView_start.contactname"));
		$table->renameHeader("isactive", Lang::txt("UserView_start.isactive"));
		$table->write();
	}
	
	function startOptions() {
		parent::startOptions();
		
		$gdpr = new Link($this->modePrefix() . "gdpr", Lang::txt("UserView_startOptions.question"));
		$gdpr->addIcon("question");
		$gdpr->write();
	}
	
	function addEntity() {
		// add form for new user
		$form = new Form(Lang::txt($this->getaddEntityName()), $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("lastlogin");
		$form->removeElement("isActive");
		$form->removeElement("contact");
		
		// manually add contacts
		$form->addElement(Lang::txt("UserView_addEntity.contactDropdown"), $this->contactDropdown());
		$form->write();
	}
	
	private function contactDropdown() {
		$dd = new Dropdown("contact");
		
		// add no-contact option
		$dd->addOption(Lang::txt("UserView_contactDropdown.no_contact"), 0);
		
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
					new BNoteError(Lang::txt("UserView_view.error"));
		}
		
		// get current user
		$usr = $this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes());
		
		// show header
		if(isset($usr["contactsurname"])) {
			$title = $usr["login"]  . " / " . $usr["contactsurname"] . ", " . $usr["contactname"];
		}
		else {
			$usr = $this->getData()->findByIdNoRef($_GET["id"]);
			$title = Lang::txt("UserView_view.user") . $usr["login"];
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
		$dv->renameElement("contactname", Lang::txt("UserView_view.contactname"));
		$dv->renameElement("contactsurname", Lang::txt("UserView_view.contactsurname"));
		$dv->write();
	}
	
	function additionalViewButtons() {
		$privs = new Link("?mod=" . $this->getModId() . "&mode=privileges&id=" . $_GET["id"], Lang::txt("UserView_additionalViewButtons.privileges"));
		$privs->addIcon("key");
		$privs->write();
		
		if($this->getData()->isUserActive($_GET["id"])) {
			$btnLbl = Lang::txt("UserView_additionalViewButtons.no_entry");
			$btnIcon = "no_entry";
		}
		else {
			$btnLbl = Lang::txt("UserView_additionalViewButtons.no_entry");
			$btnIcon = "checkmark";
		}
		$active = new Link($this->modePrefix() . "activate&id=" . $_GET["id"], $btnLbl);
		$active->addIcon($btnIcon);
		$active->write();
	}
	
	function editEntityForm($write=true) {
		$user = $this->getData()->findByIdNoRef($_GET["id"]);
		$form = new Form($this->getData()->getUsername($_GET["id"]) . Lang::txt("UserView_editEntityForm.edit_process"),
				$this->modePrefix() . "edit_process&id=" . $_GET["id"] . "&manualValid=true");
		$form->addElement(Lang::txt("UserView_editEntityForm.login"), new Field("login", $user["login"], 99), true, 6);
		$form->addElement(Lang::txt("UserView_editEntityForm.password"), new Field("password", "", FieldType::PASSWORD), true, 6);
		$form->addHidden("isActive", $user["isActive"]);
		$dd = $this->contactDropdown();
		$dd->setSelected($user["contact"]);
		$form->addElement(Lang::txt("UserView_editEntityForm.contact"), $dd, false, 6);
		$form->setFormCss("");
		$form->write();
		Writing::p(Lang::txt("UserView_editEntityForm.message"));
	}
	
	function edit_process() {
		if($_POST["contact"] == "0") unset($_POST["contact"]);
		parent::edit_process();
	}
	
	function privileges() {
		$this->checkID();
		
		global $system_data;
		$form = new Form(Lang::txt("UserView_privileges.form") . $this->getData()->getUsername($_GET["id"]),
							$this->modePrefix() . "privileges_process&id=" . $_GET["id"]);
		foreach($system_data->getModuleArray() as $mid => $modRow) {
			$selected = "";
			if($this->getData()->hasUserPrivilegeForModule($_GET["id"], $mid)) $selected = "checked";
			if($modRow["category"] == "public") continue; 
			$form->addElement($modRow["name"], new Field($mid, $selected, FieldType::BOOLEAN));
			$form->renameElement("Start", Lang::txt("UserView_privileges.Start"));
			$form->renameElement("Kontakte", Lang::txt("UserView_privileges.Contacts"));
			$form->renameElement("Proben", Lang::txt("UserView_privileges.Rehearsals"));
			$form->renameElement("Kommunikation", Lang::txt("UserView_privileges.Communication"));
			$form->renameElement("Kontaktdaten", Lang::txt("UserView_privileges.Contact_data"));
			$form->renameElement("Share", Lang::txt("UserView_privileges.Share"));
			$form->renameElement("Abstimmung", Lang::txt("UserView_privileges.Voting"));
			$form->renameElement("Aufgaben", Lang::txt("UserView_privileges.Tasks"));
			$form->renameElement("Probenphasen", Lang::txt("UserView_privileges.Rehearsal_phases"));
			$form->renameElement("Calendar", Lang::txt("UserView_privileges.Calendar"));
			$form->renameElement("Tour", Lang::txt("UserView_privileges.Tour"));
			$form->renameElement("Stats", Lang::txt("UserView_privileges.Evaluations"));
			$form->renameElement("User", Lang::txt("UserView_privileges.User"));
			$form->renameElement("Konzerte", Lang::txt("UserView_privileges.Performances"));
			$form->renameElement("Repertoire", Lang::txt("UserView_privileges.Repertoire"));
			$form->renameElement("Locations", Lang::txt("UserView_privileges.Locations"));
			$form->renameElement("Hilfe", Lang::txt("UserView_privileges.Help"));
			$form->renameElement("Mitspieler", Lang::txt("UserView_privileges.Members"));
			$form->renameElement("Nachrichten", Lang::txt("UserView_privileges.Messages"));
			$form->renameElement("Konfiguration", Lang::txt("UserView_privileges.Configuration"));
			$form->renameElement("Finance", Lang::txt("UserView_privileges.Finances"));
			$form->renameElement("Equipment", Lang::txt("UserView_privileges.Equipment"));
			$form->renameElement("Outfits", Lang::txt("UserView_privileges.Outfits"));
			$form->renameElement("Admin", Lang::txt("UserView_privileges.System_Information"));
		}
		$form->write();
	}
	
	protected function privilegesOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	function privileges_process() {
		$this->checkID();
		$this->getData()->updatePrivileges($_GET["id"]);
		
		new Message(Lang::txt("UserView_privileges_process.message_1"), Lang::txt("UserView_privileges_process.message_2"));
	}
	
	function privileges_processOptions() {
		$usrView = new Link($this->modePrefix() . "view&id=" . $_GET["id"], Lang::txt("UserView_privileges_processOptions.back"));
		$usrView->addIcon("arrow_left");
		$usrView->write();
	}
	
	function deleteConfirmationMessage($label, $linkDelete, $linkBack = null) {
		new Message(Lang::txt("UserView_deleteConfirmationMessage.message_1"), Lang::txt("UserView_deleteConfirmationMessage.message_2"));
		$yes = new Link($linkDelete, strtoupper($label) . Lang::txt("UserView_deleteConfirmationMessage.linkDelete"));
		$yes->addIcon("remove");
		$yes->write();
		
		$no = new Link($linkBack, Lang::txt("UserView_deleteConfirmationMessage.back"));
		$no->addIcon("arrow_left");
		$no->write();
	}
	
	function gdpr() {
		// Check if a user has not logged in for 24 months. In this case, show the list and let the current user decide.
		Writing::h1(Lang::txt("UserView_gdpr.title"));
		Writing::p(Lang::txt("UserView_gdpr.message_1"));
		$inactiveUsers = $this->getData()->getLongInactiveUsers();
		$inactiveUsersList = new Plainlist($inactiveUsers);
		$inactiveUsersList->setNameField("login");
		$inactiveUsersList->write();
		
		Writing::p(Lang::txt("UserView_gdpr.message_2"));
		$delData = array(Lang::txt("UserView_gdpr.account"), Lang::txt("UserView_gdpr.contact_details"), Lang::txt("UserView_gdpr.participation"), Lang::txt("UserView_gdpr.Invitations "));
		$delDataList = new Plainlist(Plainlist::simpleListToSelection($delData));
		$delDataList->write();
		
		$this->verticalSpace();
		
		$del = new Link($this->modePrefix() . "gdprDelete", Lang::txt("UserView_gdpr.date"));
		$del->addIcon("cancel");
		$del->write();
	}
	
	function gdprDelete() {
		Writing::h1(Lang::txt("UserView_gdprDelete.date"));
		$inactiveUsers = $this->getData()->getLongInactiveUsers();
		
		$this->getData()->deleteUsersFull($inactiveUsers);
		
		Writing::p(Lang::txt("UserView_gdprDelete.message"));
		$inactiveUsersList = new Plainlist($inactiveUsers);
		$inactiveUsersList->setNameField("login");
		$inactiveUsersList->write();
	}
}

?>