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
		Writing::p("Du hast folgende aktive Abstimmungen ausgeschrieben:");
	}

	function showAllTable() {
		$votes = $this->getData()->getUserActiveVotes();
		$table = new Table($votes);
		$table->setEdit("id");
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
		$form->write();
	}
	
	function add() {
		// validate
		$this->getData()->validate($_POST);
		
		// process
		$vid = $this->getData()->create($_POST);
		
		// write success
		new Message($this->getEntityName() . " gespeichert",
				"Der Eintrag wurde erfolgreich gespeichert.");
		
		// show options link
		$lnk = new Link($this->modePrefix() . "options&id=$vid", "Optionen hinzufügen");
		$lnk->write();
		$this->buttonSpace();
		
		// show group link
		$grp = new Link($this->modePrefix() . "group&id=$vid", "Stimmberechtigte hinzufügen");
		$grp->write();
	}
	
	function options() {
		$this->checkID();
		$vote = $this->getData()->findByIdNoRef($_GET["id"]);
		
		// add a new element if posted
		if(isset($_POST["name"]) || isset($_POST["odate"])) {
			$this->getData()->addOption($_GET["id"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - Optionen");
		$options = $this->getData()->getOptions($_GET["id"]);
		
		Writing::p("Klicke auf eine Option um diese zu löschen.");
		
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
			$form->addElement("Datum", new Field("odate", "", FieldType::DATETIME));
		}
		else {
			$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		}
		$form->write();
		
		// back button
		$back = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Zurück zur Abstimmung");
		$back->write();
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
		$opt->write();
		$this->buttonSpace();
		
		// users
		$grp = new Link($this->modePrefix() . "group&id=" . $_GET["id"], "Abstimmungsberechtigte");
		$grp->write();
		$this->buttonSpace();
		
		// result
		$res = new Link($this->modePrefix() . "result&id=" . $_GET["id"], "Ergebnis");
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
		
		// add a new element if posted
		if(isset($_POST["user"])) {
			$this->getData()->addToGroup($_GET["id"], $_POST["user"]);
		}
		
		// show options that are already present
		Writing::h2($vote["name"] . " - Abstimmungsberechtigte");
		$group = $this->getData()->getGroup($_GET["id"]);
		
		Writing::p("Klicke auf einen Benutzer um diesen zu löschen.");
		
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
		$form = new Form("Abstimmungsberechtigten hinzufügen", $this->modePrefix() . "group&id=" . $_GET["id"]);
		$users = $this->getData()->getUsers();
		$dd = new Dropdown("user");
		for($i = 1; $i < count($users); $i++) {
			$dd->addOption($users[$i]["name"] . " " . $users[$i]["surname"], $users[$i]["id"]);
		}
		$form->addElement("Abstimmungsberechtigter", $dd);
		$form->write();
		
		// back button
		$back = new Link($this->modePrefix() . "view&id=" . $_GET["id"], "Zurück zur Abstimmung");
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
		
		if($vote["is_multi"] == 1) {
			Writing::p("Mehrere Antworten waren möglich.");
		}
		else {
			Writing::p("Jeder Abstimmungsberechtigte konnte nur eine Stimme abgeben.");
		}
		
		$result = $this->getData()->getResult($_GET["id"]);
		$dv = new Dataview();
		$dv->autoAddElements($result);
		$dv->write();
		
		$this->backToViewButton($_GET["id"]);
	}
}

?>