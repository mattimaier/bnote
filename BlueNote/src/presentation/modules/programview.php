<?php

class ProgramView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Programm");
	}
	
	function start() {
		parent::start();
		parent::backToStart();
		$this->verticalSpace();
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=programs&sub=";
	}
	
	function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=programs", "Zur&uuml;ck");
		$link->write();
	}
	
	function writeTitle() {
		Writing::h2("Programme");
		Writing::p("Klicke auf ein Programm um Details anzuzeigen und die St&uuml;cke zu bearbeiten.");
	}
	
	function showAllTable() {
		$table = new Table($this->getData()->getProgramme());
		$table->removeColumn("id");
		$table->setEdit("id");
		$table->changeMode("programs&sub=view");
		$table->renameHeader("istemplate", "Vorlage");
		$table->setColumnFormat("isTemplate", "BOOLEAN");
		$table->write();
		
		// add the form to insert a program from a template
		$this->verticalSpace();
		$form = new Form("Program aus Vorlage hinzuf&uuml;gen", $this->modePrefix() . "addWithTemplate");
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$dd = new Dropdown("template");
		$templates = $this->getData()->getTemplates();
		for($i = 1; $i < count($templates); $i++) {
			$dd->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form->addElement("Vorlage", $dd);
		$form->write();
	}
	
	function viewDetailTable() {
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdNoRef($_GET["id"]));
		$dv->autoRename($this->getData()->getFields());
		$dv->write();
		
		Writing::h2("Titel Liste");
		$table = new Table($this->getData()->getSongsForProgram($_GET["id"]));
		$table->removeColumn("song");
		$table->renameHeader("rank", "Nr.");
		$table->renameHeader("title", "Titel");
		$table->renameHeader("composer", "Komponist/Arrangeuer");
		$table->renameHeader("length", "L&auml;nge");
		$table->renameHeader("notes", "Notizen");
		$table->write();
		$this->writeProgramLength();
		$this->verticalSpace();
	}
	
	private function writeProgramLength() {
		$tt = $this->getData()->totalProgramLength($_GET["id"]);
		Writing::p("Das Programm hat eine Gesamtl&auml;nge von <strong>" . $tt . "</strong> Stunden.");
	}
	
	public function view() {
		$this->checkID();
		
		// heading
		Writing::h2($this->getData()->getProgramName($_GET["id"]));
		
		// show the details
		$this->viewDetailTable();
		
		// additional buttons
		$this->additionalViewButtons();
		
		// show buttons to edit and delete
		$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"],
							"Details bearbeiten");
		$edit->write();
		$this->buttonSpace();
		$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"],
							$this->getEntityName() . " l&ouml;schen");
		$del->write();
		$this->buttonSpace();
		
		// back button
		$this->verticalSpace();
		$this->backToStart();
	}
	
	function additionalViewButtons() {
		$lnk = new Link($this->modePrefix() . "editList&id=" . $_GET["id"], "Titelliste bearbeiten");
		$lnk->write();
		$this->buttonSpace();
		
		$lnk = new Link($this->modePrefix() . "printList&id=" . $_GET["id"], "Titelliste drucken");
		$lnk->write();
		$this->buttonSpace();
	}
	
	function editList() {
		Writing::h2($this->getData()->getProgramName($_GET["id"]));
		Writing::p("Schiebe die Titel in die Reihenfolge, die du m&ouml;chtest.");
		
		$tracks = $this->getData()->getSongsForProgram($_GET["id"]);
		echo "<form action=\"" . $this->modePrefix() . "saveList&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		echo "<ul id=\"sortable\">\n";
		for($i = 1; $i < count($tracks); $i++) {
			$text = $tracks[$i]["length"] . "&nbsp;" . $tracks[$i]["title"] . " (" . $tracks[$i]["composer"] . ")";
			$text .= "<input type=\"hidden\" name=\"tracks[]\" value=\"" . $tracks[$i]["song"] . "\" />\n";
			echo "<li class=\"ui-state-default\">" . '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
			echo $text . "</li>\n";
		}
		echo "</ul>\n";
		$this->verticalSpace();
		$this->writeProgramLength();
		echo "<input type=\"submit\" value=\"SPEICHERN\" />\n";
		echo "</form>\n";
		
		$this->verticalSpace();
		
		// Titel hinzufuegen
		$form = new Form("Titel hinzuf&uuml;gen", $this->modePrefix() . "addSong&id=" . $_GET["id"]);
		$songs = $this->getData()->getAllSongs();
		$dd = new Dropdown("song");
		for($i = 1; $i < count($songs); $i++) {
			$dd->addOption($songs[$i]["title"], $songs[$i]["id"]);
		}
		$form->addElement("Titel", $dd);
		$form->write();
		
		// Titel loeschen
		$form = new Form("Titel von Programm l&ouml;schen", $this->modePrefix()
							. "delSong&pid=" . $_GET["id"]);
		$songs = $this->getData()->getSongsForProgram($_GET["id"]);
		$dd = new Dropdown("song");
		for($i = 1; $i < count($songs); $i++) {
			$dd->addOption($songs[$i]["title"], $songs[$i]["song"]);
		}
		$form->addElement("Titel", $dd);
		$form->write();
		
		// back button
		$this->verticalSpace();
		$this->backToViewButton($_GET["id"]);
	}
	
	function saveList() {
		foreach ($_POST["tracks"] as $i => $sid) {
			$this->getData()->updateRank($_GET["id"], $sid, $i+1);
			$i++;
		}
		$this->editList();
	}
	
	function addSong() {
		$this->getData()->addSongToProgram($_GET["id"]);
		$this->editList();
	}
	
	function delSong() {
		$this->getData()->deleteSongFromProgram($_GET["pid"], $_POST["song"]);
		$_GET["id"] = $_GET["pid"];
		$this->editList();
	}
	
	function addWithTemplate() {
		$id = $this->getData()->addProgramWithTemplate();
		$_GET["id"] = $id;
		$this->view();
	}
	
	function printList() {
		Writing::h2("Programm drucken");
		
		// determine filename
		$filename = $GLOBALS["DATA_PATHS"]["programs"];
		$filename .= "Programm-" . $_GET["id"]. ".pdf";
		
		// create report
		require_once $GLOBALS["DIR_PRINT"] . "program.php";
		new ProgramPDF($filename, $this->getData(), $_GET["id"]);
		
		// show report
		echo "<embed src=\"$filename\" width=\"90%\" height=\"700px\" />\n";
		echo "<br /><br />\n";
		
		// back button
		$this->backToViewButton($_GET["id"]);
		$this->verticalSpace();
	}
}