<?php

/**
 * A filebrowser widget that lets you work
 * with file within and below the given
 * root directory.
 * @author matti
 *
 */
class Filebrowser implements iWriteable {
	
	/*
	 * Comment
	 * -------
	 * Although this widget is kept under "presentation" which suggests
	 * this widget only contains presentation code, it also contains
	 * structures to control the flow and the data within the folders.
	 * Widgets are kept in as little files are possible to increase their
	 * usability. However, this makes the single files big and breaks the
	 * MVC structure.
	 */
	
	
	/**
	 * Root directory on the server.
	 * @var String
	 */
	private $root;
	
	/**
	 * Current folder location.
	 * @var String
	 */
	private $path;
	
	/**
	 * Whether this filebrowser should be set to view only.
	 * @var bool
	 */
	private $viewmode;
	
	/**
	 * Create a new filebrowser widget.
	 * @param String $root Root directory for the browser.
	 */
	function __construct($root) {
		$this->root = $root;
	}
	
	/**
	 * If this function is called without any parameter
	 * or with the parameter set to true, then the file
	 * browser will only allow the user to view the files
	 * and folders, not to add or edit them.
	 * @param bool $bool True to turn view mode on, false to turn it off.
	 */
	function viewMode($bool = true) {
		$this->viewmode = $bool;
	}
	
	function write() {
		// determine current location
		if(isset($_GET["path"])) {
			$this->path = urldecode($_GET["path"]);
		}
		else {
			$this->path = "";
		}
		
		// execute functions
		if(isset($_GET["fbfunc"]) && $_GET["fbfunc"] != "view") {
			$this->$_GET["fbfunc"]();
		}
		else {
			$this->mainView();
		}
	}
	
	private function mainView() {
		// show the add folder button if in write-mode
		if(!$this->viewmode) {
			$lnk = new Link($this->linkprefix("addFolderForm"), "Ordner hinzufügen");
			$lnk->addIcon("add");
			$lnk->write();
			
			if($this->path != "") {
				echo "&nbsp;&nbsp;";
				$lnk = new Link($this->linkprefix("addFileForm&path=" . $this->path), "Datei hinzufügen");
				$lnk->addIcon("add");
				$lnk->write();
			}
			
		}
		
		// show the folders and their contents
		?>
		<table id="filebrowser_content">
			<tr>
				<td id="filebrowser_folders">
				<div class="filebrowser_foldertopic">Ordner</div>
				<?php $this->writeFolders(); ?>
				</td>
				<td id="filebrowser_files">
				<?php $this->writeFolderContent(); ?>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Writes all folders on the screen.
	 */
	private function writeFolders() { 
		// iterate through folder
		if($handle = opendir($this->root)) {
			while(false !== ($file = readdir($handle))) {
				if($file != "." && $file != ".." && is_dir($this->root . $file)) {
					$active = "";
					if(isset($_GET["path"]) && urlencode($file) == $this->path) {
						$active = "_active";
					}
					?>
					<a href="<?php echo $this->linkprefix("view&path=" . urlencode($file)); ?>">
						<div class="filebrowser_folderitem<?php echo $active; ?>"><?php echo $file; ?></div>
					</a>
					<?php
				}
			 }
			closedir($handle);
		}
	}
	
	/**
	 * Writes the folder contents.
	 */
	private function writeFolderContent() {
		if($this->path == "") {
			Writing::p("Bitte w&auml;hle einen Ordner.");
		}
		else {
			Writing::h3($this->path);
				
			// show table with files
			$table = new Table($this->getFilesFromFolder($this->path));
			$table->renameHeader("name", "Dateiname");
			$table->renameHeader("size", "Größe");
			$table->renameHeader("options", "Optionen");
			$table->write();
		}
	}
	
	private function addFolderForm() {
		$form = new Form("Ordner erstellen", $this->linkprefix("addFolder"));
		$form->addElement("Ordnername", new Field("folder", "", FieldType::CHAR));
		$form->write();
	}
	
	private function addFileForm() {
		$form = new Form("Datei hinzuf&uuml;gen", $this->linkprefix("addFile&path=" . $this->path));
		$form->setMultipart();
		$form->addElement("Datei", new Field("file", "", FieldType::FILE));
		$form->changeSubmitButton("Datei hochladen");
		$form->write();
	}
	
	/**
	 * Adds a file to the current location (path).
	 */
	private function addFile() {
		// check permission
		if($this->viewmode) {
			new Error("Du hast keine Berechtigung eine Datei hinzuzuf&uuml;gen.");
		}
		
		// validate upload
		if(!isset($_FILES["file"])) {
			new Error("Es trat ein Fehler beim verarbeiten der Datei auf. Bitte versuche es noch einmal.");
		}
		if($_FILES["file"]["error"] > 0) {
			switch($_FILES["file"]["error"]) {
				case 1: $msg = "Die maximale Dateigr&ouml;&szlig;e wurde &uuml;berschritten."; break;
				case 2: $msg = "Die maximale Dateigr&ouml;&szlig;e wurde &uuml;berschritten."; break;
				case 3: $msg = "Die Datei wurde nur teilweise hochgeladen. Bitte &uuml;berpr&uuml;fe deine Internetverbindung."; break;
				case 4: $msg = "Es wurde keine Datei hochgeladen."; break;
				default: $msg = "Serverfehler beim Speichern der Datei."; break;
			}
			new Error($msg);
		}
		if(!is_uploaded_file($_FILES["file"]["tmp_name"])) {
			new Error("Die Datei konnte nicht hochgeladen werden.");
		}
		
		// copy file to target directory
		$target = $this->root . $this->path;
		if(!copy($_FILES["file"]["tmp_name"], $target . "/" . $_FILES["file"]["name"])) {
			new Error("Die Datei konnte nicht gespeichert werden.");
		}
		
		$this->mainView();
	}
	
	/**
	 * Deletes a file from the current location (path).
	 */
	private function deleteFile() {
		// check permission
		if($this->viewmode) {
			new Error("Du hast keine Berechtigung eine Datei zu l&uml;schen.");
		}
		
		// decode filename
		if(!isset($_GET["file"])) {
			new Error("Die Datei konnte nicht gefunden werden.");
		}
		$fn = urldecode($_GET["file"]);
		unlink($this->root . $this->path . "/" . $fn);
		
		$this->mainView();
	}
	
	/**
	 * Adds a folder to the root directory.
	 */
	private function addFolder() {
		// check permission
		if($this->viewmode) {
			new Error("Du hast keine Berechtigung einen Order hinzuzuf&uuml;gen.");
		}
		
		// validate name
		global $system_data;
		$system_data->regex->isName($_POST["folder"]);
		
		// create folder in root
		mkdir($this->root . $_POST["folder"]);
		
		$this->mainView();
	}
	
	/**
	 * Generates the prefix for the links used in this widget.
	 * @param String $ext Functional extension without the "&".
	 */
	private function linkprefix($ext) {
		global $system_data;
		if(isset($_GET["mode"])) {
			$mode = "&mode=" . $_GET["mode"];
		}
		else {
			$mode = "";
		}
		return "?mod=" . $system_data->getModuleId() . "$mode&fbfunc=$ext";
	}
	
	/**
	 * @param String $folder Folder to get the files and their infos from. 
	 * @return A database selection like array with the contents of the folder.
	 */
	private function getFilesFromFolder($folder) {
		$result = array();
		
		// header
		$result[0] = array(
			"name", "size", "options"
		);
		
		// data body
		if($handle = opendir($this->root . $folder)) {
			while(false !== ($file = readdir($handle))) {
				if($file != "." && $file != ".." && !is_dir($this->root . $folder . "/" . $file)) {
					$fullpath = $this->root . $folder . "/$file";
					// calculate size
					$size = filesize($fullpath);
					$size = ceil($size / 1000);
					$size = number_format($size, 0) . " kb";
					
					// create options
					$showLnk = new Link($fullpath, "Download");
					$showLnk->setTarget("_blank");
					$showLnk->addIcon("arrow_down");
					$show = $showLnk->toString();
					
					$delLnk = new Link($this->linkprefix("deleteFile&path=" . $this->path . "&file=" . urlencode($file)), "Löschen");
					$delLnk->addIcon("remove");
					$delete = $delLnk->toString();
					
					$options = $show . "&nbsp;&nbsp;" . $delete;
					
					// add to result array
					$row = array(
						"name" => $file,
						"size" => $size,
						"options" => $options
					);
					array_push($result, $row);
				}
			}
			closedir($handle);
		}
					
		return $result;
	}
		
}

?>