<?php
/**
 * View for repertoire module.
 * @author matti
 *
 */
class RepertoireView extends CrudRefView {

	/**
	 * Create the repertoire view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Song");
		$this->setaddEntityName(Lang::txt("RepertoireView_construct.addEntityName"));
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

		$prt = new Link("javascript:print()", Lang::txt("RepertoireView_startOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
		
		$massChange = new Link($this->modePrefix() . "massUpdate", Lang::txt("RepertoireView_startOptions.massUpdate"));
		$massChange->addIcon("edit");
		$massChange->write();
		
		$genre_mod = new Link($this->modePrefix() . "genre&func=start", Lang::txt("RepertoireView_startOptions.start"));
		$genre_mod->addIcon("music_folder");
		$genre_mod->write();
		
		$xlsImport = new Link($this->modePrefix() . "xlsUpload", Lang::txt("RepertoireView_startOptions.xlsUpload"));
		$xlsImport->addIcon("arrow_down");
		$xlsImport->write();
		
		$xlsExport = new Link($GLOBALS["DIR_EXPORT"] . "repertoire.csv", Lang::txt("RepertoireView_startOptions.repertoire"));
		$xlsExport->addIcon("save");
		$xlsExport->write();
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
		$form = new Form(Lang::txt("RepertoireView_addEntityForm.Form"), $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		
		$form->removeElement("id");
		$form->setForeign("genre", "genre", "id", "name", -1);
		$genreDropdown = $form->getForeignElement("genre");
		$genreDropdown->addOption("-", 0);							
		$form->setForeign("status", "status", "id", "name", -1);
		
		$form->removeElement("composer");
		$composer = "<input type=\"text\" name=\"composer\" id=\"composer\" size=\"30\" />";
		$form->addElement(Lang::txt("RepertoireView_addEntityForm.composer"), new TextWriteable($composer));
		
		$form->removeElement("length");
		$length = "<input type=\"text\" name=\"length\" size=\"6\" />&nbsp;min";
		$form->addElement(Lang::txt("RepertoireView_addEntityForm.length"), new TextWriteable($length));
		
		$this->appendCustomFieldsToForm($form, 's', null, false);
		
		$form->write();
	}
	
	public function start() {
		// Filters
		$filter = new Filterbox($this->modePrefix() . "start");
		$filterList = array(
			"title" => array(Lang::txt("RepertoireView_start.title"), FieldType::CHAR, ""),
			"genre" => array(Lang::txt("RepertoireView_start.genre"), FieldType::SET, $this->getData()->getGenres()),
			"music_key" => array(Lang::txt("RepertoireView_start.music_key"), FieldType::CHAR, ""),
			"solist" => array(Lang::txt("RepertoireView_start.solist"), FieldType::SET, $this->getData()->getAllSolists()),
			"status" => array(Lang::txt("RepertoireView_start.status"), FieldType::SET, $this->getData()->getStatuses()),
			"composer" => array(Lang::txt("RepertoireView_start.composer"), FieldType::SET, $this->getData()->getComposers()),
			"is_active" => array(Lang::txt("RepertoireView_start.is_active"), FieldType::BOOLEAN, True)
		);
		foreach($filterList as $field => $info) {
			$filter->addFilter($field, $info[0], $info[1], $info[2]);
			if($field == "solist") {
				$filter->setNameCols("solist", array("name", "surname"));
			}
		}
		$filter->setCssClass("ignore_for_print");
		$filter->write();

		// pagination
		$offset = 0;
		$limit = 100;
		if(isset($_GET["offset"])) {
			$offset = $_GET["offset"];
			$this->getData()->getSysdata()->regex->isNumber($offset);
		}
		$filters = array();
		if(isset($_POST) && count($_POST) > 0) {
			$filters = $_POST;
		}
		$result = $this->getData()->getFilteredRepertoire($filters, $offset, $limit);
		
		// Table
		$table = new Table($result["data"]);
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("genrename", Lang::txt("RepertoireView_start.genrename"));
		$table->renameHeader("composername", Lang::txt("RepertoireView_start.composername"));
		$table->renameHeader("statusname", Lang::txt("RepertoireView_start.statusname"));
		$table->removeColumn("id");
		$table->removeColumn("notes");
		if($result["numFilters"] == 0 || $offset > 0) {
			$table->setPagination($offset, $limit, $this->modePrefix() . "start&offset=");
		}
		$table->showFilter(false);
		$table->write();
		
	}
	
	public function view() {
		$song = $this->getData()->getSong($_GET["id"]);
		?>
		<h1><?php echo $song["title"]; ?> <span class="repertoire_song_composer_title"> <?php echo $song["composername"]; ?></span></h1>
		
		<div class="repertoire_song_detailbox">
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.music_key"); ?></div>
					<div class="songbox_value"><?php echo $song["music_key"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.length"); ?></div>
					<div class="songbox_value"><?php echo $song["length"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.bpm"); ?></div>
					<div class="songbox_value"><?php echo $song["bpm"]; ?></div>
				</div>
			</div>
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.statusname"); ?></div>
					<div class="songbox_value"><?php echo $song["statusname"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.genrename"); ?></div>
					<div class="songbox_value"><?php echo $song["genrename"]; ?></div>
				</div>
				
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.setting"); ?></div>
					<div class="songbox_value"><?php echo $song["setting"]; ?></div>
				</div>
			</div>
			<div class="songbox_col">
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.is_active"); ?></div>
					<div class="songbox_value"><?php echo $song["is_active"] == 1 ? "ja" : "archiviert"; ?></div>
				</div>
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.notes"); ?></div>
				</div>
				<div class="songbox_areavalue"><?php echo $song["notes"]; ?></div>
			</div>
			<div class="songbox_col">
			<?php 
			$customFields = $this->getData()->getCustomFields('s');
			for($i = 1; $i < count($customFields); $i++) {
				$field = $customFields[$i];
				$techName = $field["techname"];
				$caption = $field["txtdefsingle"];
				?>
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo $caption; ?></div>
					<div class="songbox_value"><?php
					if($field["fieldtype"] == "BOOLEAN") {
						echo $song[$techName] == 1 ? Lang::txt("RepertoireView_view.yes") : Lang::txt("RepertoireView_view.no");
					}
					else {
						echo $song[$techName];
					}
					?></div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
		
		<div class="repertoire_song_extra">
			<div class="songextra_col">
				<h2><?php echo Lang::txt("RepertoireView_view.id"); ?></h2>
				
				<h3><?php echo Lang::txt("RepertoireView_view.rehearsals_song"); ?></h3>
				<ul>
					<?php 
					// References
					$references = $this->getData()->findReferences($_GET["id"]);
					for($i = 1; $i < count($references["rehearsals"]); $i++) {
						$reh = $references["rehearsals"][$i];
						echo "<li>" . Data::convertDateFromDb($reh["begin"]) . "</li>";
					}
					?>
				</ul>
				
				<h3><?php echo Lang::txt("RepertoireView_view.concerts_song"); ?></h3>
				<ul>
					<?php
					for($i = 1; $i < count($references["concerts"]); $i++) {
						$con = $references["concerts"][$i];
						$title = $con["title"];
						echo "<li>" . Data::convertDateFromDb($con["begin"]) . " / $title</li>";
					}
					?>
				</ul>
			</div>
			<div class="songextra_col">
				<?php 
				// Files
				if($this->getData()->getSysdata()->userHasPermission(12)) {
					$this->songFiles();
				}
				?>
			</div>
			<div class="songextra_col">
				<h2><?php echo Lang::txt("RepertoireView_view.solists"); ?></h2>
				<ul>
					<?php 
					// Solists
					$solists = $this->getData()->getSolists($_GET["id"]);
					// add a link to the data to remove the solist from the list
					for($i = 1; $i < count($solists); $i++) {
						$sol = $solists[$i];
						$delLink = $this->modePrefix() . "delSolist&id=" . $_GET["id"] . "&solistId=" . $solists[$i]["id"];
						$btn = new Link($delLink, "");
						$btn->addIcon("remove");
						echo "<li>" . $sol["name"] . " " . $sol["surname"] . " (" . $sol["instrument"] . ") " . $btn->toString() . "</li>";
					}
					if(count($solists) == 1) {
						?>
						<li><?php echo Lang::txt("RepertoireView_view.nosolists"); ?></li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
		<?php
	}
	
	private function songFiles() {
		$songs = $this->getData()->getFiles($_GET["id"]);
		?>
		<div class="songfiles_box">
			<h3>Dateien</h3>
			<ul>
			<?php
			// show files
			for($i = 1; $i < count($songs); $i++) {
				$file = $songs[$i]["filepath"];
				$href = "src/data/filehandler.php?file=/" . urlencode($file);
				$preview = $href;
				$delHref = $this->modePrefix() . "removeSongFile&id=" . $_GET["id"] . "&songfile=" . $songs[$i]["id"];
				$imgWidth = "50px";
				?>
				<li class="songfiles_filebox">
					<?php
					if(!Data::endsWith($file, "png") && !Data::endsWith($file, "jpg") && !Data::endsWith($file, "jpeg") && !Data::endsWith($file, "bmp")) {
						$preview = "style/icons/copy_link.png";
						$imgWidth = "32px";
					}
					?>
					<a href="<?php echo $href; ?>" target="_blank">
						<img class="songfiles_preview" src="<?php echo $preview; ?>" width="<?php echo $imgWidth; ?>">
					</a>
					<div class="songfiles_textbox">
						<a class="songfiles_filelink" href="<?php echo $href; ?>" target="_blank"><?php echo $file; ?></a><br/>
						<a class="songfiles_doctype"><?php echo $songs[$i]["doctype_name"]; ?></a>
						<a href="<?php echo $delHref; ?>"><?php echo Lang::txt("RepertoireView_songFiles.doctype_name"); ?></a>
					</div>
				</li>
				<?php
			}
			?>
			</ul>
			
			<form action="<?php echo $this->modePrefix() . "addSongFile&id=" . $_GET["id"] ?>" method="POST">
				<h3><?php echo Lang::txt("RepertoireView_songFiles.addSongFile"); ?></h3>
				<p><?php echo Lang::txt("RepertoireView_songFiles.repertoire_filesearch"); ?></p>
				<input type="text" id="repertoire_filesearch" name="file" />
				<select name="doctype">
				<?php 
				$doctypes = $this->getData()->adp()->getDocumentTypes();
				for($j = 1; $j < count($doctypes); $j++) {
					echo '<option value="' . $doctypes[$j]["id"] . '">' . $doctypes[$j]["name"] . '</option>';
				}
				?>
				</select>
				<input type="submit" value=<?php echo Lang::txt("RepertoireView_songFiles.submit"); ?> />
			</form>
			
			<script>
			$(document).ready(function() {
				$( "#repertoire_filesearch" ).autocomplete({
			      source: "src/export/repertoire-files.php",
			      minLength: 3
			    });
			});
			</script>
		</div>
		<?php
	}
	
	public function addSongFile() {
		$songId = $_GET["id"];
		$fullpath = $_POST["file"];
		// check file's existence
		$sys_path = $GLOBALS["DATA_PATHS"]["share"] . $fullpath;
		if($fullpath == "" || !file_exists($sys_path)) {
			new BNoteError(Lang::txt("RepertoireView_addSongFile.error"));
		}
		$this->getData()->addFile($songId, $fullpath, $_POST["doctype"]);
		$this->view();
	}
	
	function addSongFileOptions() {
		$this->viewOptions();
	}
	
	function removeSongFile() {
		$this->getData()->deleteFileReference($_GET["songfile"]);
		$this->view();
	}

	function removeSongFileOptions() {
		$this->viewOptions();
	}
	
	protected function additionalViewButtons() {
		$addSol = new Link($this->modePrefix() . "addSolist&id=" . $_GET["id"], Lang::txt("RepertoireView_additionalViewButtons.addSolist"));
		$addSol->addIcon("plus");
		$addSol->write();
	}
	
	protected function editEntityForm($write=true) {
		$song = $this->getData()->getSong($_GET["id"]);
		
		$form = new Form("Song bearbeiten", $this->modePrefix() . "edit_process&manualValid=true&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->renameElement("length", Lang::txt("RepertoireView_editEntityForm.length"));
		$form->setForeign("genre", "genre", "id", "name", $song["genre"]);
		$genreDropdown = $form->getForeignElement("genre");
		$genreDropdown->addOption("-", 0);						
		$form->setForeign("status", "status", "id", "name", $song["status"]);
		$form->removeElement("composer");
		$form->addElement(Lang::txt("RepertoireView_editEntityForm.composer"), new Field("composer",
					$this->getData()->getComposerName($song["composer"]), FieldType::CHAR));
		
		$this->appendCustomFieldsToForm($form, 's', $song, false);
		
		if($write) {
			$form->write();
		}
		return $form;
	}
	
	function addSolist() {
		$this->checkID();
		
		$form = new Form(Lang::txt("RepertoireView_addSolist.Form"), $this->modePrefix() . "process_addSolist&id=" . $_GET["id"]);
		$contacts = $this->getData()->adp()->getContacts();
		$selector = new GroupSelector($contacts, array(), "solists");
		$selector->setNameColumns(array("name", "surname"));
		$form->addElement(Lang::txt("RepertoireView_addSolist.selector"), $selector);
		$form->write();
	}
	
	function process_addSolist() {
		$this->getData()->addSolist($_GET["id"]);
		new Message(Lang::txt("RepertoireView_process_addSolist.message_1"), Lang::txt("RepertoireView_process_addSolist.message_2"));
		$this->backToViewButton($_GET["id"]);
	}
	
	function delSolist() {
		$this->getData()->deleteSolist($_GET["id"], $_GET["solistId"]);
		$this->view();
	}
	
	function xlsUpload() {
		// file upload
		$form = new Form(Lang::txt("RepertoireView_xlsUpload.Form"), $this->modePrefix() . "xlsMapping");
		$form->addElement(Lang::txt("RepertoireView_xlsUpload.xlsfile"), new Field("xlsfile", "", FieldType::FILE));
		$form->setMultipart(true);
		$form->changeSubmitButton(Lang::txt("RepertoireView_xlsUpload.submit"));
		$form->write();
	}
	
	function xlsMapping($data, $header) {		
		// show which columns were detected and allow mapping
		$form = new Form("Spalten zuweisen", $this->modePrefix() . "xlsImport");
		
		// create column selector
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_title"), $this->columnSelector("col_title", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_composer"), $this->columnSelector("col_composer", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_key"), $this->columnSelector("col_key", $header));
		$form->addElement("Tempo (BPM)", $this->columnSelector("col_tempo", $header));																				
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_tempo"), $this->columnSelector("col_tempo", $header));
		$form->addElement("Besetzung", $this->columnSelector("col_setting", $header));																				
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_notes"), $this->columnSelector("col_notes", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_genre"), $this->columnSelector("col_genre", $header));
		
		// Status
		$dd_status = new Dropdown("status");
		$stati = $this->getData()->getStatuses();
		for($i = 1; $i < count($stati); $i++) {
			$dd_status->addOption($stati[$i]["name"], $stati[$i]["id"]);
		}
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.dd_status"), $dd_status);
		
		// finalize form
		$form->changeSubmitButton(Lang::txt("RepertoireView_xlsMapping.submit"));
		$xlsData = urlencode(json_encode($data));
		$form->addHidden("xlsData", $xlsData);
		$form->write();
	}
	
	protected function columnSelector($fieldname, $header) {
		$dd = new Dropdown($fieldname);
		
		$dd->addOption(Lang::txt("RepertoireView_columnSelector.import"), "-1");
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
		Writing::h2(Lang::txt("RepertoireView_xlsImport.import"));
		Writing::p("$num_rows" . Lang::txt("RepertoireView_xlsImport.message_1") . 
				count($empties) .  Lang::txt("RepertoireView_xlsImport.message_2"));
		
		// show duplicates and ask to overwrite (use from sheet) or ignore (use from BNote) for each
		$form = new Form(Lang::txt("RepertoireView_xlsImport.Form"), $this->modePrefix() . "xlsProcess");
		foreach($duplicates as $idx => $row) {
			$name = $row[$_POST["col_title"]];
			$element = new Dropdown("duplicate_$idx");
			$element->addOption(Lang::txt("RepertoireView_xlsImport.duplicate_id"), $row["duplicate_id"]);
			$element->addOption(Lang::txt("RepertoireView_xlsImport.duplicate_ignore"), -1);
			$form->addElement($name, $element);
		}
		if(count($duplicates) == 0) {
			$form->addElement(Lang::txt("RepertoireView_xlsImport.duplicates"), new Field("", "", 99));
		}
		$form->addHidden("empties", join(",", $empties));
		
		// add data from previous form
		foreach($_POST as $k => $v) {
			$form->addHidden($k, $v);
		}
		
		$form->write();
	}
	
	function xlsProcessSuccess($updated, $created) {
		new Message(Lang::txt("RepertoireView_xlsProcessSuccess.message_1"), "$created" . Lang::txt("RepertoireView_xlsProcessSuccess.message_2") . "$updated" . Lang::txt("RepertoireView_xlsProcessSuccess.message_3"));
	}
	
	function massUpdate() {
		// setup form
		$form = new Form(Lang::txt("RepertoireView_massUpdate.form"), $this->modePrefix() . "process_massUpdate&manualValid=true");
		
		// select what to change
		$form->autoAddElementsNew($this->getData()->getFields());
		$toRemove = array("id", "notes", "title", "length", "composer");
		foreach($toRemove as $i => $field) {
			$form->removeElement($field);
		}
		
		$form->setForeign("genre", "genre", "id", array("name"), 0);
		$form->addForeignOption("genre", Lang::txt("RepertoireView_massUpdate.genre"), 0);
		
		$form->setForeign("status", "status", "id", array("name"), 0);
		$form->addForeignOption("status", Lang::txt("RepertoireView_massUpdate.status"), 0);
		
		// select the song
		$songs = $this->getData()->findAllJoined($this->getJoinedAttributes());
		$songSelector = new GroupSelector($songs, array(), "songs");
		$songSelector->setNameColumns(array("title"));
		$form->addElement(Lang::txt("RepertoireView_massUpdate.songSelector"), $songSelector);
		
		// show form
		$form->write();
	}
	
	function process_massUpdate() {
		$this->getData()->massUpdate();
		new Message(Lang::txt("RepertoireView_process_massUpdate.message_1"), Lang::txt("RepertoireView_process_massUpdate.message_2"));
	}
}

?>