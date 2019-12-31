<?php

class ProgramView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("ProgramView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("ProgramView_startOptions.addEntity"));
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=programs&sub=";
	}
	
	function isSubModule($mode) {
		if($mode == "programs") return true;
		return false;
	}
	
	function subModuleOptions() {
		$subOptionFunc = isset($_GET["sub"]) ? $_GET["sub"] . "Options" : "startOptions";
		if(method_exists($this, $subOptionFunc)) {
			$this->$subOptionFunc();
		}
		else {
			$this->defaultOptions();
		}
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getModId() . "&mode=programs", Lang::txt("ProgramView_backToStart.back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	function startOptions() {
		$back = new Link("?mod=" . $this->getModId() . "&mode=start", Lang::txt("ProgramView_startOptions.back"));
		$back->addIcon("arrow_left");
		$back->write();
		
		$this->buttonSpace();
		$add = new Link($this->modePrefix() . "addEntity", Lang::txt("ProgramView_startOptions.addEntity"));
		$add->addIcon("plus");
		$add->write();
		
		$this->buttonSpace();
		$addTpl = new Link($this->modePrefix() . "addFromTemplate", Lang::txt("ProgramView_startOptions.addFromTemplate"));
		$addTpl->addIcon("plus");
		$addTpl->write();
	}
	
	function writeTitle() {
		Writing::h2(Lang::txt("ProgramView_writeTitle.title"));
		Writing::p(Lang::txt("ProgramView_writeTitle.message"));
	}
	
	function addFromTemplate() {
		// add the form to insert a program from a template		
		$form = new Form(Lang::txt("ProgramView_addFromTemplate.form"), $this->modePrefix() . "addWithTemplate");
		$form->addElement(Lang::txt("ProgramView_addFromTemplate.name"), new Field("name", "", FieldType::CHAR));
		$dd = new Dropdown("template");
		$templates = $this->getData()->getTemplates();
		for($i = 1; $i < count($templates); $i++) {
			$dd->addOption($templates[$i]["name"], $templates[$i]["id"]);
		}
		$form->addElement(Lang::txt("ProgramView_addFromTemplate.template"), $dd);
		$form->write();
	}
	
	function showAllTable() {
		$table = new Table($this->getData()->getProgramme());
		$table->removeColumn("id");
		$table->setEdit("id");
		$table->changeMode("programs&sub=view");
		$table->renameHeader("istemplate", Lang::txt("ProgramView_addFromTemplate.ask"));
		$table->setColumnFormat("isTemplate", "BOOLEAN");
		$table->write();
	}
	
	function viewDetailTable() {		
		// actual track list
		$table = new Table($this->getData()->getSongsForProgram($_GET["id"]));
		$table->removeColumn("song");
		$table->removeColumn("psid");
		$table->renameHeader("rank", Lang::txt("ProgramView_viewDetailTable.rank"));
		$table->renameHeader("title", Lang::txt("ProgramView_viewDetailTable.title"));
		$table->renameHeader("composer", Lang::txt("ProgramView_viewDetailTable.title"));
		$table->renameHeader("length", Lang::txt("ProgramView_viewDetailTable.length"));
		$table->renameHeader("notes", Lang::txt("ProgramView_viewDetailTable.notes"));
		$table->write();
		$this->writeProgramLength();
		
		// references - usage in concerts
		Writing::h2(Lang::txt("ProgramView_view.gigReferences"));
		$concerts = $this->getData()->getConcertsWithProgram($_GET["id"]);
		?><ul><?php
		foreach($concerts as $i => $concert) {
			if($i == 0) continue;
			?>
			<li><a href="<?php echo "?mod=" . $this->getModId() . "&mode=view&id=" . $concert["id"]; ?>"><?php echo $concert["title"] . " - " . Data::convertDateFromDb($concert["begin"]); ?></a></li>
			<?php
		}
		?></ul><?php
	}
	
	private function writeProgramLength() {
		$tt = $this->getData()->totalProgramLength($_GET["id"]);
		Writing::p(Lang::txt("ProgramView_writeProgramLength.message_1") . "<span style=\"font-weight: 600;\">" . $tt . "</span>" . Lang::txt("ProgramView_writeProgramLength.message_2"));		
	}
	
	public function view() {
		$this->checkID();
		
		// heading
		$program = $this->getData()->findByIdNoRef($_GET["id"]);
		$name = $program["name"];
		if(intval($program["isTemplate"]) == 1) {
			$name .= " (" . Lang::txt("ProgramView_view.templateHeader") . ")";
		}
		Writing::h1($name);
		if($program["notes"] != null && $program["notes"] != "") {
			Writing::p($program["notes"]);
		}
		
		// show the details and tracks
		$this->viewDetailTable();
	}
	
	function additionalViewButtons() {
		$lnk = new Link($this->modePrefix() . "editList&id=" . $_GET["id"], Lang::txt("ProgramView_additionalViewButtons.edit"));
		$lnk->addIcon("edit");
		$lnk->write();
		$this->buttonSpace();
		
		$lnk = new Link($this->modePrefix() . "printList&id=" . $_GET["id"], Lang::txt("ProgramView_additionalViewButtons.printer"));
		$lnk->addIcon("printer");
		$lnk->write();
		$this->buttonSpace();
		
		$lnk = new Link("src/export/programm.csv?id=" . $_GET["id"], Lang::txt("ProgramView_additionalViewButtons.export"));
		$lnk->addIcon("arrow_down");
		$lnk->setTarget("_blank");
		$lnk->write();
	}
	
	function editList() {
		Writing::h2($this->getData()->getProgramName($_GET["id"]));
		Writing::p(Lang::txt("ProgramView_editList.message"));
		
		// Track D'n'd
		$tracks = $this->getData()->getSongsForProgram($_GET["id"]);
		echo "<form action=\"" . $this->modePrefix() . "saveList&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		echo "<ul id=\"sortable\">\n";
		for($i = 1; $i < count($tracks); $i++) {
			$text = $tracks[$i]["length"] . "&nbsp;" . $tracks[$i]["title"] . " (" . $tracks[$i]["composer"] . ")";
			$text .= "<input type=\"hidden\" name=\"tracks[]\" value=\"" . $tracks[$i]["psid"] . "\" />\n";
			echo "<li class=\"ui-state-default\">" . '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
			echo $text . "</li>\n";
		}
		echo "</ul>\n";
		$this->writeProgramLength();
		echo "<input type=\"submit\" value=\"SPEICHERN\" />\n";
		echo "</form>\n";
		
		// add tracks
		$addTarget = $this->modePrefix() . "addSong&id=" . $_GET["id"];
		$songs = $this->getData()->getAllSongs();
		$optionsAdd = array();
		for($i = 1; $i < count($songs); $i++) {
			$song_title = $songs[$i]["title"];
			$song_id = $songs[$i]["id"];
			$optionsAdd[$song_id] = $song_title;
		}
		$this->trackBox($addTarget, Lang::txt("ProgramView_editList.addSong"), "plus", Lang::txt("ProgramView_editList.add"), "song", $optionsAdd);
		
		// remove tracks
		$delTarget = $this->modePrefix() . "delSong&id=" . $_GET["id"];
		$optionsRemove = array();
		for($i = 1; $i < count($tracks); $i++) {
			$optionsRemove[$tracks[$i]["song"]] = $tracks[$i]["title"];
		}
		$this->trackBox($delTarget, Lang::txt("ProgramView_editList.removeSong"), "remove", Lang::txt("ProgramView_editList.remove"), "song", $optionsRemove);
		
		// add tracks from template
		$addFromTemplate = $this->modePrefix() . "addSongsFromTemplate&id=" . $_GET["id"];
		$templates = $this->getData()->getTemplates();
		$templateOptions = array();
		for($i = 1; $i < count($templates); $i++) {
			$templateOptions[$templates[$i]["id"]] = $templates[$i]["name"];
		}
		$this->trackBox($addFromTemplate, Lang::txt("ProgramView_editList.addFromTemplate"), "setlist", Lang::txt("ProgramView_editList.template"), "template", $templateOptions);
	}
	
	private function trackBox($target, $title, $icon, $buttonLabel, $selectName, $options) {
		?>
		<div class="trackbox"><form action="<?php echo $target ?>" method="POST">
			<div class="trackbox_header"><?php $this->writeIcon($icon); echo $title; ?></div>
			<?php 
			$dd = new Dropdown($selectName);
			$dd->setStyleClass("trackbox");
			foreach($options as $value => $label) {
				$dd->addOption($label, $value);
			}
			echo $dd->write();
			?>
			<input type="submit" value="<?php echo $buttonLabel; ?>" />
		</form></div>
		<?php
	}
	
	protected function editListOptions() {
		$back = new Link($this->modePrefix() . "view&id=" . $_GET["id"], Lang::txt("ProgramView_editListOptions.back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function saveList() {
		foreach ($_POST["tracks"] as $i => $psid) {
			$this->getData()->updateRank($_GET["id"], $psid, $i+1);
			$i++;
		}
		$this->editList();
	}
	
	protected function saveListOptions() {
		$this->editListOptions();
	}
	
	function addSong() {
		$this->getData()->addSongToProgram($_GET["id"]);
		$this->editList();
	}
	
	protected function addSongOptions() {
		$this->editListOptions();
	}
	
	function addSongsFromTemplate() {
		$this->getData()->copySongsFromProgram($_GET["id"], $_POST["template"]);
		$this->editList();
	}
	
	protected function addSongsFromTemplateOptions() {
		$this->editListOptions();
	}
	
	function delSong() {
		$this->getData()->deleteSongFromProgram($_GET["id"], $_POST["song"]);
		$_GET["id"] = $_GET["id"];
		$this->editList();
	}
	
	protected function delSongOptions() {
		$this->editListOptions();
	}
	
	function addWithTemplate() {
		$id = $this->getData()->addProgramWithTemplate();
		$_GET["id"] = $id;
		$this->view();
	}
	
	function printList() {
		// heading
		$program = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2($program['name']);
		if($program['notes'] != "") {
			Writing::p($program['notes']);
		}
		
		// print table
		$songs = $this->getData()->getSongsForProgramPrint($_GET["id"]);
		$tab = new Table($songs);
		$tab->renameHeader("title", Lang::txt("ProgramView_printList.title"));
		$tab->renameHeader("notes", Lang::txt("ProgramView_printList.notes"));
		$tab->renameHeader("length", Lang::txt("ProgramView_printList.length"));
		$tab->addSumLine(Lang::txt("ProgramView_printList.totalProgramLength"), $this->getData()->totalProgramLength());  // automatically uses $_GET["id"]
		$tab->write();
	}
	
	protected function printListOptions() {
		$this->backToViewButton($_GET["id"]);
		$this->buttonSpace();
		
		$print = new Link("javascript:print()", Lang::txt("ProgramView_printListOptions.print"));
		$print->addIcon("printer");
		$print->write();
	}
	
	private function writeIcon($name) {
		echo "<img src=\"" . $GLOBALS["DIR_ICONS"] . $name . ".png\" height=\"15px\" alt=\"\" border=\"0\" />&nbsp;";
	}
}