<?php

/**
 * View for vote module.
 * @author matti
 *
 */
class AbstimmungView extends CrudView {

	/**
	 * Create the locations view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Abstimmung");
	}
	
	function writeTitle() {
		Writing::h2("Deine Abstimmungen");
	}

	function showAdditionStartButtons() {
		$this->buttonSpace();
		
		$arc = new Link($this->modePrefix() . "archive", "Archiv");
		$arc->addIcon("clock");
		$arc->write();
	}
	
	function showAllTable() {
		$votes = $this->getData()->getVotesForUser();
		$table = new Table($votes);
		$table->setEdit("id");
		$table->changeMode("view&resultview=true");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->setColumnFormat("end", "DATE");		
		$table->write();
	}
	
	function addEntityForm() {
		$form = new Form($this->getEntityName() . " hinzuf&uuml;gen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->removeElement("author");
		$form->removeElement("is_finished");
		
		$groups = $this->getData()->adp()->getGroups();
		$gs = new GroupSelector($groups, array(), "group");
		$form->addElement("Abstimmungsberechtigte", $gs);
		
		$form->write();
	}
	
	function view() {
		$this->checkID();
		if(isset($_GET["resultview"]) && $_GET["resultview"] == "true") {
			$this->result();
		}
		else if(!$this->getData()->isUserAuthorOfVote($_SESSION["user"], $_GET["id"])
				&& !$this->getData()->getSysdata()->isUserSuperUser()) {
			$this->result();
		}
		else {		
			// heading
			Writing::h2("Abstimmungsdetails");
			
			// show buttons to edit and close
			$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"], "Abstimmung bearbeiten");
			$edit->addIcon("edit");
			$edit->write();
			$this->buttonSpace();
			
			$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"], "Abstimmung beenden");
			$del->addIcon("erase");
			$del->write();
			$this->buttonSpace();
			
			// additional buttons
			$this->additionalViewButtons();
			
			// show the details
			$this->viewDetailTable();
			
			// back button
			$this->backToStart();
		}
	}
	
	function add() {
		// validate
		$this->getData()->validate($_POST);
		
		// process
		$vid = $this->getData()->create($_POST);
		
		// write success
		new Message($this->getEntityName() . " gespeichert",
				"Die Abstimmung wurde erfolgreich gespeichert.");
		
		// show options link
		$lnk = new Link($this->modePrefix() . "options&id=$vid", "Optionen hinzufügen");
		$lnk->addIcon("add");
		$lnk->write();
		$this->buttonSpace();
	}
	
	function options() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// add a new element if posted
		if(isset($_POST["name"]) || isset($_POST["odate"])) {
			$this->getData()->addOption($_GET["id"]);
		}
		else if(isset($_POST["odate_from"]) && isset($_POST["odate_to"])) {
			$this->getData()->addOptions($_GET["id"], $_POST["odate_from"], $_POST["odate_to"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - Optionen");
		$options = $this->getData()->getOptions($_GET["id"]);
		
		Writing::p("Klicke auf eine Option um diese von der Liste zu löschen.");
		
		echo "<ul>";
		for($i = 1; $i < count($options); $i++) {
			$href = $this->modePrefix() . "delOption&oid=" . $options[$i]["id"] . "&id=" . $_GET["id"];
			if($vote["is_date"] == 1) {
				$val = Data::convertDateFromDb($options[$i]["odate"]);
			}
			else {
				$val = $options[$i]["name"];
			}
			echo " <li><a href=\"$href\">$val</a></li>";
		}
		echo "</ul>";
		if(count($options) < 2) {
			Writing::p("<i>Diese Abstimmung hat noch keine Optionen.</i>");
		}
		
		// show add options form
		$form = new Form("Option hinzufügen", $this->modePrefix() . "options&id=" . $_GET["id"]);
		if($vote["is_date"] == 1) {
			/* DATE VOTE -> show 2 Forms:
			 * a) add single datetimes
			 * b) add multiple datetimes (in between start and end)
			 */
			echo "<table>\n";
			echo " <tr>\n";
			echo "  <td>Eine Option hinzufügen</td>\n";
			echo "  <td>Mehrere Optionen hinzufügen</td>\n";
			echo " </tr>\n";
			echo " <tr>\n";
			echo "  <td>\n";
			
			// single form
			$form->setTitle("");
			$form->addElement("Datum", new Field("odate", "", FieldType::DATETIME));
			$form->write();
			
			echo "  </td>\n";
			echo "  <td>\n";
			
			// multiform
			$form->setTitle("");
			$form->removeElement("Datum");
			$form->addElement("Erster Tag", new Field("odate_from", "", FieldType::DATETIME));
			$form->addElement("Letzter Tag", new Field("odate_to", "", FieldType::DATE));
			$form->write();
			
			echo "  </td>\n";
			echo " </tr>\n";
			echo "</table>\n";
			
			$form->addElement("Datum", new Field("odate", "", FieldType::DATETIME));
		}
		else {
			$form->addElement("Name", new Field("name", "", FieldType::CHAR));
			$form->write();
		}
		
		// back button
		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}
	
	function delOption() {
		$this->checkID();
		$this->getData()->deleteOption($_GET["oid"]);
		$this->options();
	}
	
	function viewDetailTable() {
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		$dv = new Dataview();
		$dv->addElement("Titel", $vote["name"]);
		$dv->addElement("Abstimmungsende", Data::convertDateFromDb($vote["end"]));
		$checked = ($vote["is_date"] == 1) ? "checked" : "";
		$dv->addElement("Datumsabstimmung", "<input type=\"checkbox\" disabled $checked/>");
		$checked = ($vote["is_multi"] == 1) ? "checked" : "";
		$dv->addElement("Mehrere Optionen möglich", "<input type=\"checkbox\" disabled $checked/>");
		$dv->write();
	}
	
	function additionalViewButtons() {
		$this->verticalSpace();
		
		// options
		$opt = new Link($this->modePrefix() . "options&id=" . $_GET["id"], "Optionen");
		$opt->addIcon("list_unordered");
		$opt->write();
		$this->buttonSpace();
		
		// users
		$grp = new Link($this->modePrefix() . "group&id=" . $_GET["id"], "Abstimmungsberechtigte");
		$grp->addIcon("user");
		$grp->write();
		$this->buttonSpace();
		
		// notifications
		$emLink = "?mod=" . $this->getData()->getSysdata()->getCommunicationModuleId();
		$emLink .= "&mode=voteMail&preselect=" . $_GET["id"];
		$em = new Link($emLink, "Abstimmungsbenachrichtigung");
		$em->addIcon("email");
		$em->write();
		$this->buttonSpace();
		
		// result
		$res = new Link($this->modePrefix() . "result&id=" . $_GET["id"], "Ergebnis");
		$res->addIcon("note");
		$res->write();
		$this->buttonSpace();
	}
	
	function editEntityForm() {
		$form = new Form($this->getEntityName() . " bearbeiten",
				$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->removeElement("author");
		$form->removeElement("is_finished");
		$form->removeElement("is_date");
		$form->removeElement("is_multi");
		$form->write();
	}
	
	function group() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// add a set of users when requested
		if(isset($_GET["func"]) && $_GET["func"] == "addAllMembers") {
			$this->getData()->addAllMembersAndAdminsToGroup($_GET["id"]);
		}
		
		// add a new element if posted
		if(isset($_POST["user"])) {
			$this->getData()->addToGroup($_GET["id"], $_POST["user"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - Abstimmungsberechtigte");
		$group = $this->getData()->getGroup($_GET["id"]);
		
		Writing::p("Klicke auf einen Benutzer um diesen von der Liste zu löschen.");
		
		echo "<ul>";
		for($i = 1; $i < count($group); $i++) {
			$href = $this->modePrefix() . "delFromGroup&uid=" . $group[$i]["id"] . "&id=" . $_GET["id"];
			$val = $group[$i]["name"] . " " . $group[$i]["surname"];
			echo " <li><a href=\"$href\">$val</a></li>";
		}
		echo "</ul>";
		if(count($group) < 2) {
			Writing::p("<i>Diese Abstimmung hat noch keine Abstimmungsberechtigten.</i>");
		}
			
		// show add users form
		$form = new Form("Abstimmungsberechtigte hinzufügen", $this->modePrefix() . "group&id=" . $_GET["id"]);
		$users = $this->getData()->getUsers();
		$dd = new Dropdown("user");
		$amIinUsers = false;
		for($i = 1; $i < count($users); $i++) {
			$dd->addOption($users[$i]["name"] . " " . $users[$i]["surname"], $users[$i]["id"]);
			if($users[$i]["id"] == $_SESSION["user"]) {
				$amIinUsers = true;
			}
		}
		if(!$amIinUsers) {
			$contact = $this->getData()->getSysdata()->getUsersContact();
			$dd->addOption($contact["name"] . " " . $contact["surname"], $_SESSION["user"]);
		}
		$form->addElement("Abstimmungsberechtigter", $dd);
		$form->write();
		$this->verticalSpace();
		
		// back button
		$back = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Zurück zur Abstimmung");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function delFromGroup() {
		$this->checkID();
		$this->getData()->deleteFromGroup($_GET["id"], $_GET["uid"]);
		$this->group();
	}
	
	function result() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2($vote["name"] . " - Ergebnis");
				
		$hasButtons = false;
		// in case the user is the author or a superuser, he/she can edit the vote
		if($this->getData()->isUserAuthorOfVote($_SESSION["user"], $_GET["id"])
				|| $this->getData()->getSysdata()->isUserSuperUser()) {
			$editBtn = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Abstimmung bearbeiten");
			$editBtn->addIcon("edit");
			$editBtn->write();
			$this->buttonSpace();
			$hasButtons = true;
		}
		
		// in case vote isn't over yet, show button to view
		if($this->getData()->isVoteActive($_GET["id"])) {
			$voteBtn = new Link("?mod=1&mode=voteOptions&id=" . $_GET["id"], "Jetzt Abstimmen");
			$voteBtn->addIcon("checkmark");
			$voteBtn->write();
			$hasButtons = true;
		}
		
		if($hasButtons) {
			$this->verticalSpace();
		}
		
		if($vote["is_multi"] == 1) {
			Writing::p("Mehrere Antworten waren möglich.");
		}
		else {
			Writing::p("Jeder Abstimmungsberechtigte konnte nur eine Stimme abgeben.");
		}
		
		$result = $this->getData()->getResult($_GET["id"]);
		$table = new Table($result);
		$table->removeColumn("id");
		$table->renameHeader("votes", "Stimmen");
		$table->renameHeader("voters", "W&auml;hler");
		$table->write();
		
		if(isset($_GET["from"]) && $_GET["from"] == "history") {
			$lnk = new Link($this->modePrefix() . "archive", "Zurück");
			$lnk->addIcon("arrow_left");
			$lnk->write();
		}
		else {
			$this->backToStart();
		}
		
	}
	
	function archive() {
		Writing::h2("Abstimmungsarchiv");
		
		$this->backToStart();
		
		$votes = $this->getData()->getVotesForUser(false);
		$table = new Table($votes);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->removeColumn("id");
		$table->setColumnFormat("end", "DATE");
		$table->changeMode("result&from=history");
		$table->write();
	}
}

?>