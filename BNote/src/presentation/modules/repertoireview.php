<?php
/**
 * View for repertoire module.
 * @author matti
 *
 */
class RepertoireView extends CrudRefView {
	//TODO check encoding and saving titles
	/**
	 * Create the repertoire view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Song");
		$this->setJoinedAttributes(RepertoireData::getJoinedAttributes());
	}
	
	function showOptions() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "genre") {
			$this->getController()->getGenreView()->showOptions();
		}
		else {
			parent::showOptions();
		}
	}
	
	protected function startOptions() {
		parent::startOptions();
		$this->buttonSpace();
		if(isset($_GET["showFilters"]) && $_GET["showFilters"] == "true") {
			$filterbox = new Link($this->modePrefix() . "start", "Filter ausblenden");
		}
		else {
			$filterbox = new Link($this->modePrefix() . "start&showFilters=true", "Filter anzeigen");
		}
		$filterbox->addIcon("filter");
		$filterbox->write();
		
		$this->buttonSpace();
		$genre_mod = new Link($this->modePrefix() . "genre&func=start", "Genres verwalten");
		$genre_mod->addIcon("music_folder");
		$genre_mod->write();
		
		$this->buttonSpace();
		$xlsImport = new Link($this->modePrefix() . "xlsUpload", "Excel Import");
		$xlsImport->addIcon("arrow_down");
		$xlsImport->write();
	}
	
	protected function addEntityForm() {
		?>
		<script type="text/javascript">
		$(function() {
			var composers = [
			    <?php
			    echo $this->getData()->listComposers();
			    ?>
			];

			$("#composer").autocomplete({
				source: composers
			});
		});	
		</script>
		<?php
		$form = new Form("Song hinzuf&uuml;gen", $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		
		$form->removeElement("id");
		$form->setForeign("genre", "genre", "id", "name", -1);
		$form->setForeign("status", "status", "id", "name", -1);
		
		$form->removeElement("composer");
		$composer = "<input type=\"text\" name=\"composer\" id=\"composer\" size=\"30\" />";
		$form->addElement("Komponist / Arrangeur", new TextWriteable($composer));
		
		$form->removeElement("length");
		$length = "<input type=\"text\" name=\"length\" size=\"6\" />&nbsp;min";
		$form->addElement("L&auml;nge", new TextWriteable($length));
		
		$form->write();
	}
	
	protected function showAllTable() {
		if(isset($_GET["showFilters"])) {
			$filter = new Filterbox($this->modePrefix() . "start&showFilters=true&filters=true");
			$filter->addFilter("genre", "Genre", FieldType::SET, $this->getData()->getGenres());
			$filter->addFilter("music_key", "Tonart", FieldType::CHAR, "");
			$filter->addFilter("solist", "Solist", FieldType::SET, $this->getData()->getAllSolists());
			$filter->setNameCols("solist", array("name", "surname"));
			$filter->addFilter("status", "Status", FieldType::SET, $this->getData()->getStatuses());
			$filter->addFilter("composer", "Komponist", FieldType::SET, $this->getData()->getComposers());
			$filter->write();
			$this->verticalSpace();
		}
		
		if(isset($_GET["filters"]) && $_GET["filters"] == "true") {
			$data = $this->getData()->getFilteredRepertoire($_POST);
		}
		else {
			$data = $this->getData()->findAllJoinedWhere($this->getJoinedAttributes(), "length >= 0 ORDER BY title");
		}
		
		$table = new Table($data);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("genrename", "Genre");
		$table->renameHeader("composername", "Komponist/Arrangeur");
		$table->renameHeader("statusname", "Status");
		$table->removeColumn("id");
		$table->write();
		
		$tt = $this->getData()->totalRepertoireLength();
		Writing::p("Das Reperatoire hat eine Gesamtl&auml;nge von <strong>" . $tt . "</strong> Stunden.");
	}
	
	protected function viewDetailTable() {
		$dv = new Dataview();
		$dv->autoAddElements($this->getData()->findByIdJoined($_GET["id"], $this->getJoinedAttributes()));
		$dv->autoRename($this->getData()->getFields());
		$dv->renameElement("genrename", "Genre");
		$dv->renameElement("composername", "Komponist / Arrangeur");
		$dv->renameElement("statusname", "Status");
		$dv->write();
		
		Writing::h3("Solisten");		
		$solists = $this->getData()->getSolists($_GET["id"]);
		// add a link to the data to remove the solist from the list
		$solists[0]["delete"] = "Löschen";
		for($i = 1; $i < count($solists); $i++) {
			$delLink = $this->modePrefix() . "delSolist&id=" . $_GET["id"] . "&solistId=" . $solists[$i]["id"];
			$btn = new Link($delLink, "");
			$btn->addIcon("remove");
			$solists[$i]["delete"] = $btn->toString();
		}
		
		$solTab = new Table($solists);
		$solTab->removeColumn("id");
		$solTab->renameHeader("surname", "Nachname");
		$solTab->renameHeader("name", "Vorname");
		$solTab->write();
		
		$this->verticalSpace();
	}
	
	protected function additionalViewButtons() {
		$addSol = new Link($this->modePrefix() . "addSolist&id=" . $_GET["id"], "Solist hinzufügen");
		$addSol->addIcon("plus");
		$addSol->write();
	}
	
	protected function editEntityForm() {
		$song = $this->getData()->findByIdNoRef($_GET["id"]);
		
		$form = new Form("Song bearbeiten", $this->modePrefix() . "edit_process&manualValid=true&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->renameElement("length", "L&auml;nge in Stunden");
		$form->setForeign("genre", "genre", "id", "name", $song["genre"]);
		$form->setForeign("status", "status", "id", "name", $song["status"]);
		$form->removeElement("composer");
		$form->addElement("Komponist/Arrangeur", new Field("composer",
					$this->getData()->getComposerName($song["composer"]), FieldType::CHAR));
		$form->write();
	}
	
	function addSolist() {
		$this->checkID();
		
		$form = new Form("Solisten auswählen", $this->modePrefix() . "process_addSolist&id=" . $_GET["id"]);
		$contacts = $this->getData()->adp()->getContacts();
		$selector = new GroupSelector($contacts, array(), "solists");
		$selector->setNameColumns(array("name", "surname"));
		$form->addElement("Solisten", $selector);
		$form->write();
	}
	
	function process_addSolist() {
		$this->getData()->addSolist($_GET["id"]);
		new Message("Solist hinzugefügt", "Der Solist wurde dem Stück hinzugefügt.");
		$this->backToViewButton($_GET["id"]);
	}
	
	function delSolist() {
		$this->getData()->deleteSolist($_GET["id"], $_GET["solistId"]);
		$this->view();
	}
	
	function xlsUpload() {
		// file upload
		$form = new Form("Excel Dateiupload", $this->modePrefix() . "xlsMapping");
		$form->addElement("XLSX Datei (Excel 2007+)", new Field("xlsfile", "", FieldType::FILE));
		$form->setMultipart(true);
		$form->changeSubmitButton("Hochladen und weiter");
		$form->write();
	}
	
	function xlsMapping($data, $header) {		
		// show which columns were detected and allow mapping
		$form = new Form("Spalten zuweisen", $this->modePrefix() . "xlsImport");
		
		// create column selector
		$form->addElement("Titel", $this->columnSelector("col_title", $header));
		$form->addElement("Komponist/Arrangeur", $this->columnSelector("col_composer", $header));
		$form->addElement("Tonart", $this->columnSelector("col_key", $header));
		$form->addElement("Tempo (BPM)", $this->columnSelector("col_tempo", $header));
		$form->addElement("Notizen", $this->columnSelector("col_notes", $header));
		
		// Status
		$dd_status = new Dropdown("status");
		$stati = $this->getData()->getStatuses();
		for($i = 1; $i < count($stati); $i++) {
			$dd_status->addOption($stati[$i]["name"], $stati[$i]["id"]);
		}
		$form->addElement("Status", $dd_status);
		
		// Genre
		$genres = $this->getData()->getGenres();
		$dd_genre = new Dropdown("genre");
		for($i = 1; $i < count($genres); $i++) {
			$dd_genre->addOption($genres[$i]["name"], $genres[$i]["id"]);
		}
		$form->addElement("Gerne", $dd_genre);
		
		// finalize form
		$form->changeSubmitButton("Weiter");
		$xlsData = urlencode(json_encode($data));
		$form->addHidden("xlsData", $xlsData);
		$form->write();
	}
	
	protected function columnSelector($fieldname, $header) {
		$dd = new Dropdown($fieldname);
		
		$dd->addOption("- Nicht importieren", "-1");
		foreach($header as $idx => $name) {
			$n = $name;
			if($n == "") {
				$n = "(unnamed)";
			}
			$dd->addOption($n, $idx);
		}
		
		$dd->setSelected("-1");
		return $dd;
	}
	
	function xlsImport($duplicates, $num_rows, $empties) {
		// show how many can be imported directly
		Writing::h2("Import");
		Writing::p("$num_rows Zeilen können direkt importiert werden. " . 
				count($empties) . " Zeilen enthalten keinen Titel und wurden als leer gekennzeichnet.");
		
		// show duplicates and ask to overwrite (use from sheet) or ignore (use from BNote) for each
		$form = new Form("Duplikate", $this->modePrefix() . "xlsProcess");
		foreach($duplicates as $idx => $row) {
			$name = $row[$_POST["col_title"]];
			$element = new Dropdown("duplicate_$idx");
			$element->addOption("Überschreiben", $row["duplicate_id"]);
			$element->addOption("Ignorieren", -1);
			$form->addElement($name, $element);
		}
		if(count($duplicates) == 0) {
			$form->addElement("Keine Duplikate erkannt", new Field("", "", 99));
		}
		$form->addHidden("empties", join(",", $empties));
		
		// add data from previous form
		foreach($_POST as $k => $v) {
			$form->addHidden($k, $v);
		}
		
		$form->write();
	}
	
	function xlsProcessSuccess() {
		new Message("Import erfolgreich", "Die Stücke wurden erfolgreich importiert.");
	}
}

?>