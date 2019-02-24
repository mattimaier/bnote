<?php

/**
 * Custom controller for Repertoire module, because Genre is a submodule.
 * @author Matti
 *
 */
class RepertoireController extends DefaultController {
	
	private $genreView;
	private $genreData;
	
	/**
	 * internal map for faster processing<br/>
	 * name_of_genre => id
	 * @var array
	 */
	private $genres = NULL;
	
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
				case 1: $msg = Lang::txt("errorFileMaxSize"); break;
				case 2: $msg = Lang::txt("errorFileMaxSize"); break;
				case 3: $msg = Lang::txt("errorFileAbort"); break;
				case 4: $msg = Lang::txt("errorNoFile"); break;
				default: $msg = Lang::txt("errorSavingFile"); break;
			}
			new BNoteError($msg);
		}
		if(!is_uploaded_file($_FILES["xlsfile"]["tmp_name"])) {
			new BNoteError(Lang::txt("errorUploadingFile"));
		}
		
		// read file
		require_once $GLOBALS['DIR_LIB'] . "PHPExcel/Classes/PHPExcel.php";
		$reader = PHPExcel_IOFactory::createReader('Excel2007');
		$reader->setReadDataOnly(true);
		
		$xls = $reader->load($_FILES["xlsfile"]["tmp_name"]);
		$sheet = $xls->getActiveSheet();
		
		$highestRow = $sheet->getHighestRow();
		$highestColumn = $sheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		
		$header = array();
		$rows = array();
		for ($rowIdx = 1; $rowIdx <= $highestRow; ++$rowIdx) {
			$row = array();
			for ($col = 0; $col <= $highestColumnIndex; ++$col) {
				$val = $sheet->getCellByColumnAndRow($col, $rowIdx)->getValue();
				if($rowIdx == 1) {
					// header
					$header[$col] = $val;
				}
				else {
					$row[$col] = $val;
				}
			}
			if($rowIdx > 1) {
				array_push($rows, $row);
			}
		}
		
		$this->getView()->xlsMapping($rows, $header);
	}
	
	function xlsImport() {
		// check if title is mapped
		if($_POST["col_title"] < 0) {
			new BNoteError("Wähle eine Spalte für den Titel deiner Stücke.");
		}
		$xlsData = json_decode(urldecode($_POST["xlsData"]));
		
		// load songs from database, then hash them to check for duplicates
		$songs = $this->getData()->findAllNoRef();
		$dict = Data::dbSelectionToDict($songs, "title", array("id"));
		$songs_dict = array();
		foreach($dict as $k => $v) {
			$songs_dict[strtolower($k)] = $v;
		}
		
		// find duplicates and empty rows
		$duplicates = array();
		$title_idx = $_POST["col_title"];
		$num_rows = 0;
		$empties = array();  # indices of empty rows
		foreach($xlsData as $rowIdx => $row) {
			$title = strtolower($row[$title_idx]);
			if(in_array($title, $songs_dict) && isset($songs_dict[$title])) {
				$row["duplicate_id"] = $songs_dict[$title];
				array_push($duplicates, $row);
			}
			if($title == "") {
				array_push($empties, $rowIdx);
			}
			else {
				$num_rows++;
			}
		}		
		$this->getView()->xlsImport($duplicates, $num_rows, $empties);
	}
	
	function xlsProcess() {		
		require_once $GLOBALS['DIR_LIB'] . "PHPExcel/Classes/PHPExcel.php";
		
		// go through data: ignore empties and handle duplicates
		$xlsData = json_decode(urldecode($_POST["xlsData"]));
		$empties = array();
		if($_POST["empties"] != "") {
			$empties = explode(",", $_POST["empties"]);
		}
		
		// process duplicates
		$duplicates = array();
		$duplicate_ids = array();
		$dup_prefix = "duplicate_";
		foreach($_POST as $k => $v) {
			if(Data::startsWith($k, $dup_prefix) && $v != -1) {
				$idx = substr($k, strlen($dup_prefix));
				array_push($duplicates, $idx);
				$duplicate_ids[$idx] = $v;
			}
		}
		
		// do the real data processing
		$updated = 0;
		$created = 0;
		foreach($xlsData as $rowIdx => $row) {
			if(in_array($rowIdx, $empties)) {
				// empty -> continue
				continue;
			}
			elseif(in_array($rowIdx, $duplicates)) {
				// duplicate -> map and update
				$id = $duplicate_ids[$rowIdx];
				$this->getData()->update($id, $this->xlsMap($row));
				$updated++;
			}
			else {
				// map and insert
				$this->getData()->create($this->xlsMap($row));
				$created++;
			}
		}
		$this->getView()->xlsProcessSuccess($updated, $created);
	}
	
	protected function xlsMap($row) {
		// handle non-mapped fields
		$bpm = "";
		if($_POST["col_tempo"] >= 0) {
			$bpm = $row[$_POST["col_tempo"]];
			if($bpm == "-") {
				$bpm = 0;
			}
		}
		$music_key = "";
		if($_POST["col_key"] >= 0) {
			$music_key = $row[$_POST["col_key"]];
		}
		$notes = "";
		if($_POST["col_notes"] >= 0) {
			$notes = $row[$_POST["col_notes"]];
		}
		$composer = "nicht angegeben";
		if($_POST["col_composer"] >= 0) {
			$composer = $row[$_POST["col_composer"]];
			if($composer == "") {
				$composer = "nicht angegeben";
			}
		}
		$genre = "";
		if($_POST["col_genre"] >= 0) {
			$genre = $row[$_POST["col_genre"]];
		}
		$length = "";
		if($_POST["col_length"] >= 0) {
			$length = $row[$_POST["col_length"]];
			if(is_numeric($length)) {
				// convert fraction of day to hh:mm:ss
				$dt = PHPExcel_Shared_Date::ExcelToPHPObject($length);
				$length = $dt->format("h:i:s");
			}
		}
		$setting = "";
		if($_POST["col_setting"] >= 0) {
			$setting = $row[$_POST["col_setting"]];
		}
		
		return array(
			"title" => $this->cleanSubject($row[$_POST["col_title"]]),
			"genre" => $this->mapGenre($genre),
			"bpm" => $bpm,
			"music_key" => $music_key,
			"status" => $_POST["status"],
			"notes" => $notes,
			"composer" => $this->cleanSubject($composer),
			"length" => $length,
			"setting" => $setting
		);
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