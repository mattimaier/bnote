<?php

/**
 * Custom controller for Repertoire module, because Genre is a submodule.
 * @author Matti
 *
 */
use Shuchkin\SimpleXLSX;

class RepertoireController extends DefaultController {
	
	private $genreView;
	private $genreData;
	
	/**
	 * internal map for faster processing<br/>
	 * name_of_genre => id
	 * @var array
	 */
	private $genres = NULL;
	
	/**
	 * internal map for faster processing<br/>
	 * name_of_status => id
	 * @var array
	 */
	private $statuses = NULL;
	
	function start() {
		if(isset($_GET["mode"]) && $_GET["mode"] == "genre") {
			$this->genre();
		}
		elseif(isset($_GET["mode"]) && $_GET["mode"] == "xlsMapping") {
			$this->xlsMapping();
		}
		elseif(isset($_GET["mode"]) && $_GET["mode"] == "xlsImport") {
			$this->xlsImport();
		}
		elseif(isset($_GET["mode"]) && $_GET["mode"] == "xlsProcess") {
			$this->xlsProcess();
		}
		else {
			parent::start();
		}
	}
	
	private function initGenre() {
		if($this->genreView == null) {
			require_once $GLOBALS["DIR_DATA_MODULES"] . "genredata.php";
			require_once $GLOBALS["DIR_PRESENTATION_MODULES"] . "genreview.php";
			
			$ctrl = new DefaultController();
			$this->genreData = new GenreData();
			$this->genreView = new GenreView($ctrl);
			$ctrl->setData($this->genreData);
			$ctrl->setView($this->genreView);
		}
	}
	
	function getGenreView() {
		$this->initGenre();
		return $this->genreView;
	}
	
	private function genre() {
		$this->initGenre();
		if(isset($_GET["func"])) {
			$func = $_GET["func"];
			$this->genreView->$func();
		}
		else {
			$this->genreView->start();
		}
	}
	
	private function xlsMapping() {		
		// validate upload
		if(!isset($_FILES["xlsfile"])) {
			new BNoteError(Lang::txt("errorWithFile"));
		}
		if($_FILES["xlsfile"]["error"] > 0) {
			switch($_FILES["xlsfile"]["error"]) {
				case 1: $msg = Lang::txt("RepertoireController_xlsMapping.errorFileMaxSize"); break;
				case 2: $msg = Lang::txt("RepertoireController_xlsMapping.errorFileMaxSize"); break;
				case 3: $msg = Lang::txt("RepertoireController_xlsMapping.errorFileAbort"); break;
				case 4: $msg = Lang::txt("RepertoireController_xlsMapping.errorNoFile"); break;
				default: $msg = Lang::txt("RepertoireController_xlsMapping.errorSavingFile"); break;
			}
			new BNoteError($msg);
		}
		if(!is_uploaded_file($_FILES["xlsfile"]["tmp_name"])) {
			new BNoteError(Lang::txt("RepertoireController_xlsMapping.errorUploadingFile"));
		}
		
		// read file
		$xlsxfilename = $_FILES["xlsfile"]["tmp_name"];
		if($xlsx = SimpleXLSX::parse($xlsxfilename)) {
			$header = $rows = [];
			foreach($xlsx->rows() as $k => $row) {
				if ( $k === 0 ) {
					$header = $row;
					continue;
				}
				$rows[] = array_combine( $header, $row );
			}
			$this->getView()->xlsMapping($rows, $header);
		}
		else {
			new BNoteError(SimpleXLSX::parseError());
		}
	}
	
	function xlsImport() {
		// check if title is mapped
		if($_POST["col_title"] < 0) {
			new BNoteError(Lang::txt("RepertoireController_xlsImport.error"));
		}
		$xlsData = json_decode(urldecode($_POST["xlsData"]));
		
		// find duplicates to update and empty rows to ignore
		$updateCandidates = array();
		$id_col = NULL;
		if(isset($xlsData->header[$_POST["col_id"]])) {
			$id_col = $xlsData->header[$_POST["col_id"]];
		}
		$title_col = $xlsData->header[$_POST["col_title"]];
		$numNonEmptyRows = 0;
		$empties = array();  # indices of empty rows
		foreach($xlsData->data as $rowIdx => $row) {
			if($id_col != NULL) {
				$rowSongId = $row->$id_col;
				if($rowSongId != "" && is_int($rowSongId) && intval($rowSongId) > 0) {
					$row->duplicate_id = $rowSongId;
					$row->file_index = $rowIdx;
					array_push($updateCandidates, $row);
				}
			}
			if($row->$title_col == "") {
				array_push($empties, $rowIdx);
			}
			else {
				$numNonEmptyRows++;
			}
		}
		$this->getView()->xlsImport($title_col, $updateCandidates, $numNonEmptyRows, $empties);
	}
	
	function xlsProcess() {
		// go through data: ignore empties and handle duplicates
		$xlsData = json_decode(urldecode($_POST["xlsData"]));
		$empties = array();
		if($_POST["empties"] != "") {
			$empties = explode(",", $_POST["empties"]);
		}
		
		// put together decision on duplicate rows (songs to update)
		$songsToUpdate = array();
		$dup_prefix = "duplicate_";
		foreach($_POST as $k => $v) {
			if(Data::startsWith($k, $dup_prefix) && $v != -1) {
				$rowIdx = substr($k, strlen($dup_prefix));
				$songId = $v;
				$songsToUpdate[$rowIdx] = $songId;
			}
		}
		
		// do the real data processing
		$updated = 0;
		$created = 0;
		foreach($xlsData->data as $rowIdx => $row) {
			if(in_array($rowIdx, $empties)) {
				// empty -> continue
				continue;
			}
			elseif(isset($songsToUpdate[$rowIdx])) {
				// duplicate -> map and update
				$songId = $songsToUpdate[$rowIdx];
				$this->getData()->update($songId, $this->xlsMap($row, $xlsData->header));
				$updated++;
			}
			else {
				// map and insert
				$this->getData()->create($this->xlsMap($row, $xlsData->header));
				$created++;
			}
		}
		$this->getView()->xlsProcessSuccess($updated, $created);
	}
	
	protected function xlsMap($row, $header) {
		// handle non-mapped fields
		$bpm = "";
		if($_POST["col_tempo"] >= 0) {
			$f = $header[$_POST["col_tempo"]];
			$bpm = $row->$f;
			if($bpm == "-") {
				$bpm = 0;
			}
		}
		$music_key = "";
		if($_POST["col_key"] >= 0) {
			$f = $header[$_POST["col_key"]];
			$music_key = $row->$f;
		}
		$status = $_POST["status"];  // default status
		if($_POST["col_status"] >= 0) {
			$f = $header[$_POST["col_status"]];
			$statusname = $row->$f;
			if($this->statuses == NULL) {
				$this->statuses = array();
			}
			if(in_array($statusname, $this->statuses)) {
				$status = $this->statuses[$statusname];
			}
			else {
				$status = $this->getData()->getStatusByName($statusname, $status);
				$this->statuses[$statusname] = $status;
			}
		}
		$notes = "";
		if($_POST["col_notes"] >= 0) {
			$f = $header[$_POST["col_notes"]];
			$notes = $row->$f;
		}
		$composer = "nicht angegeben";
		if($_POST["col_composer"] >= 0) {
			$f = $header[$_POST["col_composer"]];
			$composer = $row->$f;
			if($composer == "") {
				$composer = Lang::txt("RepertoireController_xlsMap.col_composer");
			}
		}
		$genre = "";
		if($_POST["col_genre"] >= 0) {
			$f = $header[$_POST["col_genre"]];
			$genre = $row->$f;
		}
		$length = "";
		if(isset($_POST["col_length"]) && $_POST["col_length"] >= 0) {
			$f = $header[$_POST["col_length"]];
			$length = $row->$f;
			if(is_numeric($length)) {
				// convert fraction of day to hh:mm:ss
				$length = gmdate("h:i:s", $length);
			}
			else if(strlen($length) > 8) {
				// possibly a datetime string was sent, use only last 8 characters
				$length = substr($length, -8);
			}
		}
		$setting = "";
		if($_POST["col_setting"] >= 0) {
			$f = $header[$_POST["col_setting"]];
			$setting = $row->$f;
		}
		$title_f = $header[$_POST["col_title"]];
		$active = "on";
		if($_POST["col_active"] >= 0) {
			$f = $header[$_POST["col_active"]];
			$activeVal = $row->$f;
			if(in_array(strtolower($activeVal), array("on", "1", "true", "yes", "ja", "oui", "x", "j", "y", "t"))) {
				$active = "on";
			}
			else {
				$active = "off";
			}
		}
		
		$song = array(
				"title" => $row->$title_f,
				"genre" => $this->mapGenre($genre),
				"bpm" => $bpm,
				"music_key" => $music_key,
				"status" => $status,
				"notes" => $notes,
				"composer" => $this->cleanSubject($composer),
				"length" => $length,
				"setting" => $setting,
				"is_active" => $active
		);
		
		// add custom fields
		$customFields = $this->getData()->getCustomFields('s');
		$i = 0;
		foreach($customFields as $field) {
			if($i++ == 0) continue;
			$colName = "col_" . $field["techname"];
			if(isset($_POST[$colName]) && $_POST[$colName] >= 0) {
				$f = $header[$_POST[$colName]];
				$song[$field["techname"]] = $row->$f;
			}
		}
		
		return $song;
	}

	protected function mapGenre($name) {
		// preload all genres for faster mapping
		if($this->genres == null) {
			$this->genres = array();
			$this->initGenre();
			$genres = $this->genreData->findAllNoRef();
			for($i = 1; $i < count($genres); $i++) {
				$this->genres[strtolower($genres[$i]["name"])] = $genres[$i]["id"];
			}
		}
		
		// check if name exists in genres
		$k = strtolower($name);
		if(array_key_exists($k, $this->genres)) {
			return $this->genres[$k];
		}
		return 0;
	}
	
	private function cleanSubject($subject) {
		$s = str_replace('"', "", $subject);
		$s = str_replace("'", "", $s);
		return $s;
	}
	
}

?>