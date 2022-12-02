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
		$this->setEntityName(Lang::txt("RepertoireView_construct.Song"));
		$this->setAddEntityName(Lang::txt("RepertoireView_construct.addEntityName"));
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
		
		$massChange = new Link($this->modePrefix() . "massUpdate", Lang::txt("RepertoireView_startOptions.massUpdate"));
		$massChange->addIcon("pen");
		$massChange->write();
		
		$xlsImport = new Link($this->modePrefix() . "xlsUpload", Lang::txt("RepertoireView_startOptions.xlsUpload"));
		$xlsImport->addIcon("upload");
		$xlsImport->write();
		
		$xlsExport = new Link($GLOBALS["DIR_EXPORT"] . "repertoire.csv", Lang::txt("RepertoireView_startOptions.repertoire"));
		$xlsExport->addIcon("filetype-csv");
		$xlsExport->write();
		
		$prt = new Link("javascript:print()", Lang::txt("RepertoireView_startOptions.print"));
		$prt->addIcon("printer");
		$prt->write();
		
		$genre_mod = new Link($this->modePrefix() . "genre&func=start", Lang::txt("RepertoireView_startOptions.start"));
		$genre_mod->addIcon("music-note-list");
		$genre_mod->write();
		
		$wipe = new Link($this->modePrefix() . "wipe", Lang::txt("RepertoireView_startOptions.wipe"));
		$wipe->addIcon("folder-x");
		$wipe->write();
	}
	
	protected function addEntityForm() {
		// init form
		$form = new Form("", $this->modePrefix() . "add&manualValid=true");
		$form->autoAddElementsNew($this->getData()->getFields());
		
		// order fields
		$order = array("title", "composer", "genre", "music_key", "bpm", "length", "is_active", "status", "setting", "notes");
		$customFields = $this->getData()->getCustomFields('s', false);
		for($i = 1; $i < count($customFields); $i++) {
			array_push($order, $customFields[$i]["txtdefsingle"]);
		}
		$form->orderElements($order);
		
		// adapt references
		$form->setForeign("genre", "genre", "id", "name", -1);
		$genreDropdown = $form->getForeignElement("genre");
		$genreDropdown->addOption("-", 0);							
		$form->setForeign("status", "status", "id", "name", -1);
		$composerField = new ListField("composer", $this->getData()->getComposers());
		$form->removeElement("composer");
		$form->addElement("composer", $composerField, true, 6);
		
		// add custom fields
		$this->appendCustomFieldsToForm($form, 's', null, false);
		
		// set sizes
		$form->setFieldColSize("genre", 3);
		$form->setFieldColSize("music_key", 3);
		$form->setFieldColSize("length", 3);
		$form->setFieldColSize("bpm", 3);
		$form->setFieldColSize("is_active", 1);
		$form->setFieldColSize("status", 2);
		$form->setFieldColSize("setting", 9);
		$form->setFieldColSize("notes", 12);
		
		$form->write();
	}
	
	public function start() {
		// Filters
		$filter = new Filterbox($this->modePrefix() . "start");
		$filterList = array(
				"title" => array(Lang::txt("RepertoireView_start.title"), FieldType::CHAR, "", 3),
				"composer" => array(Lang::txt("RepertoireView_start.composer"), FieldType::SET, $this->getData()->getComposers(), 3),
				"solist" => array(Lang::txt("RepertoireView_start.solist"), FieldType::SET, $this->getData()->getAllSolists(), 3),
				"genre" => array(Lang::txt("RepertoireView_start.genre"), FieldType::SET, $this->getData()->getGenres(), 3),
				"status" => array(Lang::txt("RepertoireView_start.status"), FieldType::SET, $this->getData()->getStatuses(), 2),
				"is_active" => array(Lang::txt("RepertoireView_start.is_active"), FieldType::BOOLEAN, True, 2),
				"music_key" => array(Lang::txt("RepertoireView_start.music_key"), FieldType::CHAR, "", 2)
		);
		foreach($filterList as $field => $info) {
			$colSize = isset($info[3]) ? $info[3] : 4;
			$filter->addFilter($field, $info[0], $info[1], $info[2], $colSize);
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
		<h1><?php echo urldecode($song["title"]); ?> <span class="repertoire_song_composer_title"> <?php echo $song["composername"]; ?></span></h1>
		
		<div class="row">
			<div class="col-md-3">
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
			<div class="col-md-3">
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
			<div class="col-md-3">
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.is_active"); ?></div>
					<div class="songbox_value"><?php echo $song["is_active"] == 1 ? Lang::txt("RepertoireView_view.is_active_yes") : Lang::txt("RepertoireView_view.is_archived"); ?></div>
				</div>
				<div class="songbox_entry">
					<div class="songbox_label"><?php echo Lang::txt("RepertoireView_view.notes"); ?></div>
				</div>
				<div class="songbox_areavalue"><?php echo urldecode($song["notes"]); ?></div>
			</div>
			<div class="col-md-3">
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
		
		<div class="row mt-4">
			<div class="col-md-4 mt-2">
				<?php 
				// Files
				if($this->getData()->getSysdata()->userHasPermission(12)) {
					$this->songFiles();
				}
				?>
			</div>
			<div class="col-md-4 mt-2">
				<h4><?php echo Lang::txt("RepertoireView_view.solists"); ?></h4>
				<ul>
					<?php 
					// Solists
					$solists = $this->getData()->getSolists($_GET["id"]);
					// add a link to the data to remove the solist from the list
					for($i = 1; $i < count($solists); $i++) {
						$sol = $solists[$i];
						$delLink = $this->modePrefix() . "delSolist&id=" . $_GET["id"] . "&solistId=" . $solists[$i]["id"];
						$btn = new Link($delLink, "");
						$btn->addIcon("trash3");
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
			<div class="col-md-4 mt-2">
				<h4><?php echo Lang::txt("RepertoireView_view.id"); ?></h4>
				
				<h5><?php echo Lang::txt("RepertoireView_view.rehearsals_song"); ?></h5>
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
				
				<h5><?php echo Lang::txt("RepertoireView_view.concerts_song"); ?></h5>
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
		</div>
		<?php
	}
	
	private function songFiles() {
		$songs = $this->getData()->getFiles($_GET["id"]);
		?>
		<div class="songfiles_box">
			<h4><?php echo Lang::txt("RepertoireView_songFiles.Files"); ?></h4>
			<ul>
			<?php
			// show files
			for($i = 1; $i < count($songs); $i++) {
				$file = $songs[$i]["filepath"];
				$href = "src/data/filehandler.php?file=/" . urlencode($file);
				$delHref = $this->modePrefix() . "removeSongFile&id=" . $_GET["id"] . "&songfile=" . $songs[$i]["id"];
				?>
				<li>
					<?php
					if(!Data::endsWith($file, "png") && !Data::endsWith($file, "jpg") && !Data::endsWith($file, "jpeg") && !Data::endsWith($file, "bmp")) {
						$preview = "style/icons/copy_link.png";
					}
					?>
					<span class=""><?php echo $file; ?> (<?php echo $songs[$i]["doctype_name"]; ?>)</span>
					<a class="btn btn-secondary btn-sm mx-1" href="<?php echo $href; ?>" target="_blank"><i class="bi-box-arrow-down"></i></a>
					<a class="btn btn-danger btn-sm mx-1" href="<?php echo $delHref; ?>"><i class="bi-file-earmark-minus"></i></a>
				</li>
				<?php
			}
			?>
			</ul>
			
			<h5><?php echo Lang::txt("RepertoireView_songFiles.addSongFile"); ?></h5>
			<p><?php echo Lang::txt("RepertoireView_songFiles.repertoire_filesearch"); ?></p>
			
			<form class="row gx-3 gy-2 align-items-center" action="<?php echo $this->modePrefix() . "addSongFile&id=" . $_GET["id"] ?>" method="POST">
				<div class="col-sm-6">
					<input type="text" class="form-control" id="repertoire_filesearch" name="file" list="searchresults" autocomplete="off" />
					<datalist id="searchresults"></datalist>
				</div>
				<div class="col-sm-3">
					<select name="doctype" class="form-select">
					<?php 
					$doctypes = $this->getData()->adp()->getDocumentTypes();
					for($j = 1; $j < count($doctypes); $j++) {
						echo '<option value="' . $doctypes[$j]["id"] . '">' . $doctypes[$j]["name"] . '</option>';
					}
					?>
					</select>
				</div>
				<div class="col-auto">
					<input type="submit" class="btn btn-secondary" value=<?php echo Lang::txt("RepertoireView_songFiles.submit"); ?> />
				</div>
			</form>
			
			<script>
			$(document).ready(function() {
				var sourceDataEndpoint =  "src/export/repertoire-files.php";
				$("#repertoire_filesearch").on("input", function(e) {
					var val = $(this).val();
					if(val.length < 3) return;
					
					$.get(sourceDataEndpoint, {term:val}, function(res) {
						var dataList = $("#searchresults");
						dataList.empty();
						if(res.length) {
							for(var i=0, len=res.length; i<len; i++) {
								var opt = $("<option></option>").attr("value", res[i]);
								dataList.append(opt);
							}
						}
					},"json");
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
		$song["title"] = urldecode($song["title"]);
		$song["notes"] = urldecode($song["notes"]);
		
		$form = new Form("Song bearbeiten", $this->modePrefix() . "edit_process&manualValid=true&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(), $this->getData()->getTable(), $_GET["id"]);
		$form->updateValueForElement("title", $song["title"]);
		$form->updateValueForElement("notes", $song["notes"]);
		$form->removeElement("id");
		$form->renameElement("length", Lang::txt("RepertoireView_addEntityForm.length"));
		
		// order fields
		$order = array("title", "composer", "genre", "music_key", "bpm", "length", "is_active", "status", "setting", "notes");
		$customFields = $this->getData()->getCustomFields('s', false);
		for($i = 1; $i < count($customFields); $i++) {
			array_push($order, $customFields[$i]["txtdefsingle"]);
		}
		$form->orderElements($order);
		
		
		// references
		$form->setForeign("genre", "genre", "id", "name", $song["genre"]);
		$genreDropdown = $form->getForeignElement("genre");
		$genreDropdown->addOption("-", 0);						
		$form->setForeign("status", "status", "id", "name", $song["status"]);
		
		$form->removeElement("composer");
		$composerField = new ListField("composer", $this->getData()->getComposers());
		$cid = $song["composer"];
		$composerField->setIdNameValue($cid, $this->getData()->getComposerName($cid));
		$form->addElement("composer", $composerField, true, 6);
		
		// add custom fields
		$this->appendCustomFieldsToForm($form, 's', $song, false);
		
		// set sizes
		$form->setFieldColSize("genre", 3);
		$form->setFieldColSize("music_key", 3);
		$form->setFieldColSize("length", 3);
		$form->setFieldColSize("bpm", 3);
		$form->setFieldColSize("is_active", 1);
		$form->setFieldColSize("status", 2);
		$form->setFieldColSize("setting", 9);
		$form->setFieldColSize("notes", 12);
		
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
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_id"), $this->columnSelector("col_id", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_title"), $this->columnSelector("col_title", $header), true);
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_composer"), $this->columnSelector("col_composer", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_key"), $this->columnSelector("col_key", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_length"), $this->columnSelector("col_length", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_tempo"), $this->columnSelector("col_tempo", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_setting"), $this->columnSelector("col_setting", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_genre"), $this->columnSelector("col_genre", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_active"), $this->columnSelector("col_active", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_notes"), $this->columnSelector("col_notes", $header));
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.col_status"), $this->columnSelector("col_status", $header));
		
		// Status
		$dd_status = new Dropdown("status");
		$stati = $this->getData()->getStatuses();
		for($i = 1; $i < count($stati); $i++) {
			$dd_status->addOption($stati[$i]["name"], $stati[$i]["id"]);
		}
		$form->addElement(Lang::txt("RepertoireView_xlsMapping.dd_status"), $dd_status);
		
		// add custom fields
		$fields = $this->getData()->getCustomFields('s');
		$i = 0;
		foreach($fields as $field) {
			if($i++ == 0) continue;
			$form->addElement($field["txtdefsingle"], $this->columnSelector("col_" . $field["techname"], $header));
		}
		
		// finalize form
		$form->changeSubmitButton(Lang::txt("RepertoireView_xlsMapping.submit"));
		$xlsData = urlencode(json_encode(array("header" => $header, "data" => $data)));
		$form->addHidden("xlsData", $xlsData);
		$form->write();
	}
	
	protected function columnSelector($fieldname, $header) {
		$dbField = Data::startsWith($fieldname, "col_") ? substr($fieldname, 4) : $fieldname;
		$preselection = "-1";
		$dd = new Dropdown($fieldname);
		
		$dd->addOption(Lang::txt("RepertoireView_columnSelector.import"), "-1");
		
		// preselection helper
		$selectionFields = array(
			"key" => array("music_key"),
			"composer" => array("composername"),
			"genre" => array("genrename"),
			"tempo" => array("bpm"),
			"active" => array("is_active"),
			"status" => array("statusname")
		);
		
		foreach($header as $idx => $name) {
			$n = $name;
			if($n == "") {
				$n = "(unnamed)";
			}
			$dd->addOption($n, $idx);
			$lowerDbField = strtolower($dbField);
			if(strtolower($name)==$lowerDbField || (isset($selectionFields[$lowerDbField]) && in_array(strtolower($name), $selectionFields[$lowerDbField]))) {
				$preselection = strval($idx);
			}
		}
		
		// try to preselect the right column		
		$dd->setSelected($preselection);
		
		return $dd;
	}
	
	function xlsImport($titleCol, $duplicates, $num_rows, $empties) {
		// show how many can be imported directly
		Writing::h2(Lang::txt("RepertoireView_xlsImport.import"));
		Writing::p("$num_rows " . Lang::txt("RepertoireView_xlsImport.message_1") . 
				count($empties) . " " .  Lang::txt("RepertoireView_xlsImport.message_2"));
		
		// show duplicates and ask to overwrite (use from sheet) or ignore (use from BNote) for each
		$form = new Form(Lang::txt("RepertoireView_xlsImport.Form"), $this->modePrefix() . "xlsProcess");
		foreach($duplicates as $row) {
			$name = $row->$titleCol;
			$rowIdx = $row->file_index;
			$element = new Dropdown("duplicate_$rowIdx");
			$element->addOption(Lang::txt("RepertoireView_xlsImport.duplicate_id"), $row->duplicate_id);
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
		new Message(Lang::txt("RepertoireView_xlsProcessSuccess.message_1"), "$created " . Lang::txt("RepertoireView_xlsProcessSuccess.message_2") . " $updated " . Lang::txt("RepertoireView_xlsProcessSuccess.message_3"));
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
	
	function wipeTitle() {
		return Lang::txt("RepertoireView_wipe.confirmHeader");
	}
	
	function wipe() {
		if(isset($_GET["choice"]) && $_GET["choice"] == "yes") {
			$this->getData()->wipe();
			new Message(Lang::txt("RepertoireView_wipe.doneHeader"), Lang::txt("RepertoireView_wipe.doneMessage"));
		}
		else {
			Writing::p(Lang::txt("RepertoireView_wipe.confirm"));
			$yes = new Link($this->modePrefix() . "wipe&choice=yes", Lang::txt("yes"));
			$yes->addIcon("check");
			$yes->write();
			
			$no = new Link($this->modePrefix() . "start", Lang::txt("no"));
			$no->addIcon("x");
			$no->write();
		}
	}
}

?>