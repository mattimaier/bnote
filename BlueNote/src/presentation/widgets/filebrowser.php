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
		
		$this->writeNaviBar();
		$this->writeFolderContent();
	}
	
	/**
	 * Writes the navigation bar.
	 */
	private function writeNaviBar() {
		?>
		<div class="filebrowser_navibar">
		<ul>
		<?php 
			// iterate through folder
			if($handle = opendir($this->root)) {
				while(false !== ($file = readdir($handle))) {
					if($file != "." && $file != ".."
						&& is_dir($this->root . $file)) {
			?>
			<li><a class="filebrowser_navibar" href="<?php echo $this->linkprefix("view&path=" . urlencode($file)); ?>"><?php echo $file; ?></a></li>
			<?php
					}
				 }
				closedir($handle);
			}
			?>
		</ul>
		<?php 
		if(!$this->viewmode) {
			?>
			<form class="filebrowser_addfolder" method="POST" action="<? echo $this->linkprefix("addFolder") ?>">
				Ordner erstellen<br />
				<input class="filebrowser_addfolder" type="text" size="20" name="folder" />
				<input class="filebrowser_addfolder" type="submit" value="Ordner erstellen" />
			</form>
			<?php
		}
		?>
		</div>
		<?php
	}
	
	/**
	 * Writes the folder contents.
	 */
	private function writeFolderContent() {
		$location = $this->path;
		?>
		<div class="filebrowser_content">
			<?php 
			if($location == "") {
				Writing::p("Bitte w&auml;hle einen Ordner.");
			}
			else {
				?>
				<div class="filebrowser_location"><?php echo $location; ?></div>
				<table class="filebrowser_content" cellpadding="0" cellspacing="0">
					<tr>
						<th class="filebrowser_content">Name</th>
						<th class="filebrowser_content">Gr&ouml;&szlig;e</th>
						<th class="filebrowser_content">Optionen</th>
					</tr>
					
					<?php 
					// iterate through folder
					$filecount = 0;
					if($handle = opendir($this->root . $location)) {
						while(false !== ($file = readdir($handle))) {
							if($file != "." && $file != ".." && !is_dir($this->root . $location . "/" . $file)) {
								$size = filesize($this->root . $location . "/$file");
								$size = ceil($size / 1000);
								$size = number_format($size, 0) . " kb";
								?>
								<tr>
									<td class="filebrowser_content"><?php echo $file; ?></td>
									<td class="filebrowser_content"><?php echo $size; ?></td>
									<td class="filebrowser_content">
										<a class="filebrowser_option" href="<?php echo $this->root . $location . "/" . $file; ?>">Download</a>&nbsp;&nbsp;
										<?php 
										if(!$this->viewmode) {
											?>
											<a class="filebrowser_option" href="<?php echo $this->linkprefix("deleteFile&path=" . urlencode($location) . "&file=" . urlencode($file)) ?>">L&ouml;schen</a>
											<?php
										} ?>
									</td>
								</tr>
								<?php
								$filecount++;
							}
						}
						closedir($handle);
					}
					
					if($filecount < 1) {
						?>
						<tr><td colspan="3">Der Ordner ist leer.</td></tr>
						<?php
					}
					?>
				</table>
				<?php
				if(!$this->viewmode) {
					$form = new Form("Datei hinzuf&uuml;gen", $this->linkprefix("addFile&path=$location"));
					$form->setMultipart();
					$form->addElement("Datei", new Field("file", "", FieldType::FILE));
					$form->changeSubmitButton("Datei hochladen");
					$form->write();
				}
			}
			?>
		</div>
		<?php
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
}

?>