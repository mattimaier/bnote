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
	 * These characters are removed when files are added so they don't mess up anything.
	 * @var array Characters to be removed from filenames.
	 */
	private $replace_chars = array("&", "+", "/", "\\", "#");
	
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
	private $viewmode = false;
	
	/**
	 * Access to system data.
	 * @var Systemdata
	 */
	private $sysdata;
	
	/**
	 * Application Data Provider.
	 * @var ApplicationDataProvider
	 */
	private $adp;
	
	/**
	 * Create a new filebrowser widget.
	 * @param String $root Root directory for the browser.
	 */
	function __construct($root, $sysdata, $adp) {
		$this->root = $root;
		$this->sysdata = $sysdata;
		$this->adp = $adp;
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
	
	protected function setCurrentPath() {
		// determine current location
		if(isset($_GET["path"])) {
			$this->path = urldecode($_GET["path"]);
		}
		else if(isset($_POST["path"])) {
			$this->path = urldecode($_POST["path"]);
		}
		else {
			$this->path = "";
		}
	}
	
	function write() {
		$this->setCurrentPath();
		
		// strip root if path contains root
		if(Data::startsWith($this->path, $this->root)) {
			$this->path = substr($this->path, strlen($this->root));
		}
		
		// make sure the path ends with a slash
		if(!Data::endsWith($this->path, "/")) {
			$this->path .= "/";
		}
		
		// check permission for folder to prevent URL hacks within the system
		if(!$this->adp->getSecurityManager()->canUserAccessFile($this->path)) {
			new BNoteError("Zugriff verweigert.");
		}
		
		// execute functions
		if(isset($_GET["fbfunc"]) && $_GET["fbfunc"] != "view") {
			$fbfunc = $_GET["fbfunc"];
			$this->$fbfunc();
		}
		else {
			$this->mainView();
		}
	}
	
	function showOptions() {
		$this->setCurrentPath();
		
		// show the add folder button if in write-mode
		if(!$this->viewmode) {
			$path = $this->path;
			if($path == "") {
				$path = "/";
			}
			
			$lnk = new Link($this->linkprefix("addFolderForm&path=" . urlencode($path)), Lang::txt("addFolder"));
			$lnk->addIcon("plus");
			$lnk->write();
				
			if($this->path != "") {
				AbstractView::buttonSpace();
				$lnk = new Link($this->linkprefix("addFileForm&path=" . urlencode($path)), Lang::txt("addFile"));
				$lnk->addIcon("plus");
				$lnk->write();
			}
				
		}
	}
	
	private function mainView() {		
		// show the folders and their contents
		?>
		<table id="filebrowser_content">
			<tr>
				<td id="filebrowser_folders">
				<div class="filebrowser_foldertopic"><?php echo Lang::txt("favorites"); ?></div>
				<?php $this->writeFavs(); ?>
				</td>
				<td id="filebrowser_files">
					<?php $this->writeFolderContent(); ?>
					
					<div id="fb-fileupload">
						<form id="fb-fileupload-form" action="<?php echo $this->linkprefix("addFile&path=" . $this->path); ?>" class="dropzone">
						  <div class="fallback">
						    <input name="file" type="file" multiple />
						  </div>
						  <div class="dz-message" data-dz-message><span>Dateien auf diesen Bereich ziehen um Sie dem Ordner hinzuzufügen</span></div>
						</form>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Writes user's home and group homes to this list
	 * as well as a link to the share root for common access.
	 */
	private function writeFavs() {
		// get favorite dirs
		$favs = array(
			Lang::txt("myFiles") => $this->sysdata->getUsersHomeDir(),
			Lang::txt("commonShare") => $this->root
		);
		
		if($this->adp->getSecurityManager()->isUserAdmin()) {
			$favs[Lang::txt("userFolder")] = $GLOBALS["DATA_PATHS"]["userhome"];
		}
		
		$groups = $this->adp->getUsersGroups();
		if($this->sysdata->isUserSuperUser()) {
			$groups = Database::flattenSelection($this->adp->getGroups(), "id"); // flatten
		}
		
		if($groups != null && count($groups) > 0) { 
			foreach($groups as $i => $gid) {
				$name = $this->sysdata->dbcon->getCell("`group`", "name", "id = $gid");
				$name = "<span style=\"font-style: italic;\">" . Lang::txt("groupFolder") . "</span>:<br/>" . $name;
				$favs[$name] = $this->root . "groups/group_" . $gid . "/";
			}
		}
		
		// show links
		foreach($favs as $caption => $loc) {
			$active = "";
			if(isset($_GET["path"]) && urlencode($loc) == $this->path) {
				$active = "_active";
			}
			?>
			<a href="<?php echo $this->linkprefix("view&path=" . urlencode($loc)); ?>">
				<div class="filebrowser_folderitem<?php echo $active; ?>"><?php echo $caption; ?></div>
			</a>
			<?php
		}
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
			Writing::p(Lang::txt("selectFolder"));
		}
		else {
			$caption = $this->getFolderCaption();
			Writing::h3($caption, "filebrowser_folder_header");
			
			// level up
			if(strpos($caption, "/") !== false) {
				$up = new Link($this->linkprefix("view&path=" . urlencode($this->levelUp())), Lang::txt("folderUp"));
				$up->addIcon("arrow_up");
				$up->write();
				
				if($this->sysdata->getDynamicConfigParameter("allow_zip_download") == "1") {
					// only allow downloads of subfolders, not the root-folders to prevent heavy load on server
					AbstractView::buttonSpace();
					$dl = new Link($this->linkprefix("download&path=" . urlencode($this->path)), Lang::txt("folderAsZip"));
					$dl->addIcon("arrow_down");
					$dl->write();
				}
			}
			
			// show table with files
			$table = new Table($this->getFilesFromFolder($this->root . $this->path));
			$table->renameHeader("name", Lang::txt("filename"));
			$table->renameHeader("size", Lang::txt("filesize"));
			$table->renameHeader("options", Lang::txt("fileoptions"));
			$table->write();
		}
	}
	
	private function addFolderForm() {
		$form = new Form(Lang::txt("createFolder"), $this->linkprefix("addFolder&path=" . $this->path));
		$form->addElement(Lang::txt("foldername"), new Field("folder", "", FieldType::CHAR));
		$form->addHidden("path", urlencode($_GET["path"]));
		$form->write();
	}
	
	private function addFileForm() {
		$form = new Form(Lang::txt("createFile"), $this->linkprefix("addFile&path=" . $this->path));
		$form->setMultipart();
		$form->addElement(Lang::txt("file"), new Field("file", "", FieldType::FILE));
		$form->addHidden("path", urlencode($_GET["path"]));
		$form->changeSubmitButton(Lang::txt("uploadFile"));
		$form->write();
	}
	
	/**
	 * Adds a file to the current location (path).
	 */
	private function addFile() {
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("noFileAddPermission"));
		}
		
		// validate upload
		if(!isset($_FILES["file"])) {
			new BNoteError(Lang::txt("errorWithFile"));
		}
		if($_FILES["file"]["error"] > 0) {
			switch($_FILES["file"]["error"]) {
				case 1: $msg = Lang::txt("errorFileMaxSize"); break;
				case 2: $msg = Lang::txt("errorFileMaxSize"); break;
				case 3: $msg = Lang::txt("errorFileAbort"); break;
				case 4: $msg = Lang::txt("errorNoFile"); break;
				default: $msg = Lang::txt("errorSavingFile"); break;
			}
			new BNoteError($msg);
		}
		if(!is_uploaded_file($_FILES["file"]["tmp_name"])) {
			new BNoteError(Lang::txt("errorUploadingFile"));
		}
		
		if(!$this->adp->getSecurityManager()->userFilePermission(SecurityManager::$FILE_ACTION_WRITE, $this->root . $this->path)) {
			new BNoteError(Lang::txt("noFileAddPermission"));
		}
		
		// copy file to target directory
		$target = $this->root . $this->path;
		$targetFilename = $_FILES["file"]["name"];
		
		foreach($this->replace_chars as $i => $char) {
			$targetFilename = str_replace($char, "", $targetFilename);
		}
		
		if(!copy($_FILES["file"]["tmp_name"], $target . "/" . $targetFilename)) {
			new BNoteError(Lang::txt("errorSavingFile"));
		}
		
		$this->mainView();
	}
	
	/**
	 * Deletes a file from the current location (path).
	 */
	private function deleteFile() {
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("errorDeletingFile"));
		}
		
		// decode filename
		if(!isset($_GET["file"])) {
			new BNoteError(Lang::txt("errorFileNotFound"));
		}
		$fn = urldecode($_GET["file"]);
		$fullpath = $this->root . $this->path . "/" . $fn; 
		
		// check permission to delete
		if(!$this->adp->getSecurityManager()->userFilePermission(SecurityManager::$FILE_ACTION_DELETE, $this->path . "/" . $fn)) {
			new BNoteError(Lang::txt("errorDeletingFile"));
		}
		
		if(is_dir($fullpath)) {
			rmdir($fullpath);
		}
		else {
			unlink($fullpath);
		}
		
		$this->mainView();
	}
	
	/**
	 * Adds a folder to the root directory.
	 */
	private function addFolder() {		
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("noFolderAddPermission"));
		}
		
		// validate name
		global $system_data;
		$system_data->regex->isName($_POST["folder"]);
		
		// prevent user from adding reserved directories to root folder
		if($_POST["folder"] == "users" || $_POST["folder"] == "groups") {
			new BNoteError(Lang::txt("errorReservedFolderNames"));
		}
		
		// create folder in root
		$fullpath = $this->root . $this->path . "/" . $_POST["folder"];
		mkdir($fullpath);
		
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

		// create directory if not present
		if(!file_exists($folder)) {
			$this->createFolder($folder);
		}
		
		// data body
		if($handle = opendir($folder)) {
			while(false !== ($file = readdir($handle))) {
				$fullpath = $folder . $file;
				
				if($this->fileValid($fullpath, $file)) {					
					// calculate size
					$size = filesize($fullpath);
					$size = ceil($size / 1000);
					$size = number_format($size, 0) . " kb";
					
					// create options
					if(is_dir($fullpath)) {
						$openLink = new Link($this->linkprefix("view&path=" . urlencode($fullpath . "/")), Lang::txt("open"));
						$openLink->addIcon("arrow_right");
						$show = $openLink->toString();
					}
					else {
						$sharePath = substr($fullpath, strlen($this->root)-1);
						$showLnk = new Link($this->sysdata->getFileHandler() . "?file=" . $sharePath, Lang::txt("download"));
						$showLnk->setTarget("_blank");
						$showLnk->addIcon("arrow_down");
						$show = $showLnk->toString();
					}
					
					if(!$this->viewmode) { 
						$delLnk = new Link($this->linkprefix("deleteFile&path=" . $this->path . "&file=" . urlencode($file)), Lang::txt("delete"));
						$delLnk->addIcon("remove");
						$delete = $delLnk->toString();
					}
					else {
						$delete = "";
					}
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
	
	private function createFolder($folder) {
		// check if base structure is present
		if(!file_exists($this->root)) {
			mkdir($this->root);
		}
		if(!file_exists($this->root . "groups")) {
			mkdir($this->root . "groups");
		}
		mkdir($folder);
	}
		
	private function getFolderCaption() {		
		if($this->root . $this->path == $this->sysdata->getUsersHomeDir() . "/") {
			return Lang::txt("myFiles");
		}
		else if(Data::startsWith($this->path, "groups")) {
			$gid = $this->getGroupIdFromPath();
			if($gid == null || $gid == "") $groupName = "";
			else $groupName = $this->adp->getGroupName($gid);
			return Lang::txt("groupFolder") . ": " . $groupName;
		}
		else if($this->path == "users/") {
			return Lang::txt("userFolder");
		}
		else if($this->path == "/") {
			return Lang::txt("commonShare");
		}
		else {
			return $this->path;
		}
	}
	
	private function getGroupIdFromPath() {
		$idstart = strpos($this->path, "/group_")+7;
		$idend = strpos($this->path, "/", $idstart);
		$len = $idend - $idstart;
		return substr($this->path, $idstart, $len);
	}
	
	static function fileValid($fullpath, $file) {		
		$fullpath = str_replace("//", "/", $fullpath);
		
		if($file == ".htaccess") return false;
		else if($file == ".") return false;
		else if($file == "..") return false;
		else if($fullpath . "/" == $GLOBALS["DATA_PATHS"]["userhome"]) return false;
		else if($fullpath . "/" == $GLOBALS["DATA_PATHS"]["grouphome"]) return false;
		else if($file == "_temp") return false;
		return true;
	}
	
	private function levelUp() {
		$lastSlash = strrpos($this->path, "/", -2);
		return substr($this->path, 0, $lastSlash+1);
	}
	
	public function download() {
		// get filename of zip-archive in temp
		/*
		 * Only one temporary zip -> not multiuser access to this function!
		 * Temporary file access via tmpname or alike is not possible, since the
		 * filehandler only supports the share-directory for security reasons.
		 * In case e.g. date('U') would be used as zip-Filename it would be sufficient
		 * for multi-user access, but there is no or a very complicated cleanup -
		 * thus this very simple solution.
		 */
		$zip_suffix = "_temp/download.zip";
		$zip_fname = $this->root . $zip_suffix;
		
		// check that _temp folder exists
		if(!is_dir($this->root . "_temp")) {
			mkdir($this->root . "_temp");
		}
		
		// initialize zip-archive
		$zip = new ZipArchive();
		$zip->open($zip_fname, ZipArchive::CREATE);
		$dir_basepath = $this->root . $this->path;
		$dir_basepath = str_replace("\\", "/", $dir_basepath);
		
		$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir_basepath),
				RecursiveIteratorIterator::LEAVES_ONLY);
		
		foreach($files as $name => $file) {
			$filename = str_replace("\\", "/", $file->getPathname());
			if(!Data::endsWith($filename, "/.") && !Data::endsWith($filename, "/..")) {
				$zip->addFile($filename);
			}
		}
		
		// create zip file by closing this archive
		$zip->close();
		
		Writing::p(Lang::txt("archiveCreated"));
		
		$link = new Link($this->sysdata->getFileHandler() . "?file=" . $zip_suffix, Lang::txt("downloadArchive"));
		$link->setTarget("_blank");
		$link->addIcon("arrow_down");
		$link->write();
		AbstractView::buttonSpace();
		
		$back = new Link($this->linkprefix("view&path=" . urlencode($this->path)), Lang::txt("back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
}

?>