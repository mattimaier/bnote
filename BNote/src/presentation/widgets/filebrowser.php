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
	 * This member contains the path without trailing slash to avoid double slashes
	 * when concatenating $root and $path.
	 * @var String
	 */
	private $root;

	/**
	 * Current folder location.
	 * This member is an absolute path starting and ending with a slash, where "/"
	 * refers to the server path stored in $root.
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
		
		// remove trailing slash to avoid double slash in $this->root .$this->path
		if(Data::endsWith($this->root, "/")) {
			$this->root = substr($this->root, 0, -1);
		}
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

		// strip root if path contains root
		if(Data::startsWith($this->path, $this->root)) {
			$this->path = substr($this->path, strlen($this->root));
		}

		// make sure the path ends with a slash
		if(!Data::endsWith($this->path, "/")) {
			$this->path .= "/";
		}
	}

	function write() {
		$this->setCurrentPath();

		// check permission for folder to prevent URL hacks within the system
		if(!$this->adp->getSecurityManager()->canUserAccessFile($this->path)) {
			new BNoteError(Lang::txt("Filebrowser_write.error"));
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
			$lnk = new Link($this->linkprefix("addFolderForm&path=" . urlencode($this->path)), Lang::txt("Filebrowser_showOptions.addFolderForm"));
			$lnk->addIcon("plus");
			$lnk->write();

			$lnk = new Link($this->linkprefix("addFileForm&path=" . urlencode($this->path)), Lang::txt("Filebrowser_showOptions.addFileForm"));
			$lnk->addIcon("plus");
			$lnk->write();
		}

		// display zip-download button
		if($this->sysdata->getDynamicConfigParameter("allow_zip_download") == "1") {
			// only allow downloads of subfolders, not the root-folders to prevent heavy load on server
			$dl = new Link($this->linkprefix("download&path=" . urlencode($this->path)), Lang::txt("Filebrowser_showOptions.download"));
			$dl->addIcon("download");
			$dl->write();
		}
	}

	protected function isCurrentPathMainFolder() {
		$mainpathlen = strlen($this->root);

		$userfolder = substr($this->sysdata->getUsersHomeDir(), $mainpathlen);
		if(Data::endsWith($this->path, "/") && !Data::endsWith($userfolder, "/")) {
			$userfolder .= "/";
		}

		$groupfolders = array();
		$groups = $this->adp->getUsersGroups();
		foreach($groups as $groupId) {
			$dir = $this->sysdata->getGroupHomeDir($groupId);
			$dir = substr($dir, $mainpathlen) . "/";
			array_push($groupfolders, $dir);
		}
		if($this->path == "" || $this->path == "/" || $this->path == "users/" || $this->path == $userfolder || in_array($this->path, $groupfolders)) {
			return True;
		}
		return False;
	}

	private function mainView() {
		// show the folders and their contents
		?>
		<div class="nav nav-tabs">
			<?php $this->writeFavs(); ?>
		</div>

		<div style="margin-top: 10px;">
			<?php $this->writeFolderContent(); ?>
		</div>

		<div id="fb-fileupload">
			<form id="fb-fileupload-form" action="<?php echo $this->linkprefix("addFile&path=" . $this->path); ?>" class="dropzone">
			  <div class="fallback">
			    <input name="file" type="file" multiple />
			  </div>
			  <div class="dz-message" data-dz-message><span><?php echo Lang::txt("Filebrowser_mainView.addFile"); ?></span></div>
			</form>
		</div>
		<?php
	}

	/**
	 * Writes user's home and group homes to this list
	 * as well as a link to the share root for common access.
	 */
	private function writeFavs() {
		// get favorite dirs
		$favs = array(
			Lang::txt("Filebrowser_writeFavs.myFiles") => $this->sysdata->getUsersHomeDir() ."/",
			Lang::txt("Filebrowser_writeFavs.commonShare") => $GLOBALS["DATA_PATHS"]["share"]
		);

		if($this->adp->getSecurityManager()->isUserAdmin()) {
			$favs[Lang::txt("Filebrowser_writeFavs.userFolder")] = $GLOBALS["DATA_PATHS"]["userhome"];
		}

		$groups = $this->adp->getUsersGroups();
		if($this->sysdata->isUserSuperUser()) {
			$groups = Database::flattenSelection($this->adp->getGroups(), "id"); // flatten
		}

		if($groups != null && count($groups) > 0) {
			foreach($groups as $gid) {
				$name = $this->sysdata->dbcon->colValue("SELECT name FROM `group` WHERE id = ?", "name", array(array("i", $gid)));
				$favs[$name] = $this->root . "/groups/group_" . $gid . "/";
			}
		}

		// show links
		foreach($favs as $caption => $loc) {
			$active = "";
			$current_loc = substr($loc, strlen($GLOBALS["DATA_PATHS"]["share"])-1);
			if(isset($_GET["path"]) && $current_loc == $this->path) {
				$active = "active";
			}
			?>
			<div class="nav-item">
				<a class="nav-link <?php echo $active; ?>" href="<?php echo $this->linkprefix("view&path=" . urlencode($current_loc)); ?>"><?php echo $caption; ?></a>
			</div>
			<?php
		}
	}

	/**
	 * Writes the folder contents.
	 */
	private function writeFolderContent() {
		if($this->path == "") {
			Writing::p(Lang::txt("Filebrowser_writeFolderContent.message"));
		}
		else {
			$caption = $this->getFolderCaption();
			$dirname = $caption;
			if(Data::startsWith($dirname, "/")) {
				$dirname = substr($dirname, 1);
			}
			if(Data::endsWith($dirname, "/")) {
				$dirname = substr($dirname, 0, strlen($dirname)-1);
			}
			$dirname = str_replace("/", " > ", $dirname);
			Writing::h4($dirname, "filebrowser_folder_header");

			// show table with files
			$content = $this->getFilesFromFolder();
			echo '			<div class="filebrowser_filepanel">';

			// show folders as list items
			for($i = 1; $i < count($content); $i++) {
				$item = $content[$i];
				if($item["directory"] === true)
					$this->writeFolderContentListItem($item);
			}

			// show files as list items
			for($i = 1; $i < count($content); $i++) {
				$item = $content[$i];
				if(($item["directory"] === false) && ($item["tile"] === false))
					$this->writeFolderContentListItem($item);
			}

			// show files as tile items
			for($i = 1; $i < count($content); $i++) {
				$item = $content[$i];
				if(($item["directory"] === false) && ($item["tile"] === true))
					$this->writeFolderContentTileItem($item);
			}
			echo '			</div>';
		}
	}

	private function writeFolderContentListItem($item) {
			/*
			 * All infomation to be shown is copied into simple variables that can
			 * be used in a HEREDOC string.
			 */
			$name = $item["name"];
			$link = $item["show"];
			$icon = $item['icon'];
			$delete_link = $item["delete"];
			$size = $item["size"];

			/*
			 * For items that can't be deleted, the trash link is still generated,
			 * but it's hidden through style attributes defined through the style
			 * class "no_delete".
			 */
			$class = "filebrowser_list_item";
			if($this->viewmode || $item["name"] == "..") {
				$class = $class . " no_delete";
			}

			/*
			 * Finally the HTML code to display the folder content item is generated
			 * here. All parameters are taken from variables that have been defined
			 * above.
			 */
			echo <<< STRING_END
				<div class="$class">
					<i class="bi-$icon" class="filebrowser_icon"></i>
					<a href="./$link" class="filebrowser_item">$name</a>
					<a href="./$delete_link" class="filebrowser_trash"><i class="bi-trash3"></i></a>
					<span class="filebrowser_item_size">$size</span>
				</div>
STRING_END;
	}

	private function writeFolderContentTileItem($item) {
			/*
			 * All infomation to be shown is copied into simple variables that can
			 * be used in a HEREDOC string.
			 */
			$name = $item["name"];
			$link = $item["show"];
			$icon = "style/icons/" . $item['icon'] . ".png";
			$delete_link = $item["delete"];
			$size = $item["size"];
			$thumbnail = $item["thumbnail"];

			/*
			 * For items that can't be deleted, the trash link is still generated,
			 * but it's hidden through style attributes defined through the style
			 * class "no_delete".
			 */
			$class = "filebrowser_tile_item";
			if($this->viewmode || $item["name"] == "..") {
				$class = $class . " no_delete";
			}

			/*
			 * Depending on the file type the tile content is generated
			 * here, which is inserted later into the item template.
			 */
			if($item["icon"] == "music") {
				$mime = strtolower(substr($link, strrpos($link, ".")+1));
				if($mime == "mp3") {
					$audioType = "mpeg";
				}
				else {
					$audioType = $mime;
				}
				$class = $class . " music";
				$tile_content = <<< STRING_END
					<img src="$icon" height="50px">
					<audio controls preload="none">
						<source src="$link" type="audio/$audioType">
						Unsupported media type
					</audio>
STRING_END;
			}
			else if($item["icon"] == "gallery") {
				$class = $class . " gallery";
				$tile_content = <<< STRING_END
					<img src="$thumbnail">
STRING_END;
			} else {
				$tile_content = <<< STRING_END
					<img src="$icon" height="50px">
STRING_END;
			}
			
			/*
			 * Finally the HTML code to display the folder content item is generated
			 * here. All parameters are taken from variables that have been defined
			 * above.
			 */
			echo <<< STRING_END
				<div class="$class">
					<div class="filebrowser_tile">
					  $tile_content
					</div>
					<a href="./$link" class="filebrowser_item">$name</a><br>					
					<a href="./$delete_link" class="filebrowser_trash"><i class="bi-trash3"></i></a>					
					<span class="filebrowser_item_size">$size</span>					
				</div>
STRING_END;
	}

	private function addFolderForm() {
		$form = new Form(Lang::txt("Filebrowser_addFolderForm.addFolder"), $this->linkprefix("addFolder&path=" . urlencode($this->path)));
		$form->addElement(Lang::txt("Filebrowser_addFolderForm.foldername"), new Field("folder", "", FieldType::CHAR));
		$form->addHidden("path", urlencode($_GET["path"]));
		$form->write();
	}

	private function addFileForm() {
		$form = new Form(Lang::txt("Filebrowser_addFileForm.createFile"), $this->linkprefix("addFile&path=" . urlencode($this->path)));
		$form->setMultipart();
		$form->addElement(Lang::txt("Filebrowser_addFileForm.File"), new Field("file", "", FieldType::FILE));
		$form->addHidden("path", urlencode($_GET["path"]));
		$form->changeSubmitButton(Lang::txt("Filebrowser_addFileForm.uploadFile"));
		$form->write();
	}

	/**
	 * Adds a file to the current location (path).
	 */
	private function addFile() {
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("Filebrowser_addFile.error_1"));
		}

		// validate upload
		if(!isset($_FILES["file"])) {
			new BNoteError(Lang::txt("errorWithFile"));
		}
		if($_FILES["file"]["error"] > 0) {
			switch($_FILES["file"]["error"]) {
				case 1: $msg = Lang::txt("Filebrowser_addFile.errorFileMaxSize"); break;
				case 2: $msg = Lang::txt("Filebrowser_addFile.errorFileMaxSize"); break;
				case 3: $msg = Lang::txt("Filebrowser_addFile.errorFileAbort"); break;
				case 4: $msg = Lang::txt("Filebrowser_addFile.errorNoFile"); break;
				default: $msg = Lang::txt("Filebrowser_addFile.errorSavingFile"); break;
			}
			new BNoteError($msg);
		}
		if(!is_uploaded_file($_FILES["file"]["tmp_name"])) {
			new BNoteError(Lang::txt("Filebrowser_addFile.error_2"));
		}

		if(!$this->adp->getSecurityManager()->userFilePermission(SecurityManager::$FILE_ACTION_WRITE, $this->root . $this->path)) {
			new BNoteError(Lang::txt("Filebrowser_addFile.error_3"));
		}

		// security check for executable script
		require_once($GLOBALS["DIR_DATA"] . "/abstractfile.php");
		$mime = getFileMimeType($_FILES["file"]["tmp_name"]);
		if(strpos($mime, "php") !== FALSE) {
			new BNoteError(Lang::txt("Filebrowser_addFile.error_security"));
		}

		// copy file to target directory
		$target = $this->root . $this->path;
		$targetFilename = $_FILES["file"]["name"];

		foreach($this->replace_chars as $char) {
			$targetFilename = str_replace($char, "", $targetFilename);
		}

		if(!copy($_FILES["file"]["tmp_name"], $target . $targetFilename)) {
			new BNoteError(Lang::txt("Filebrowser_addFile.error_4"));
		}

		$this->mainView();
	}

	/**
	 * Deletes a file from the current location (path).
	 */
	private function deleteFile() {
		$fullpath = $this->deleteFileChecks();

		// remove file or folder
		if(is_dir($fullpath)) {
			rmdir($fullpath);
		}
		else {
			unlink($fullpath);
			
			$file = urldecode($_GET["file"]);
			if ($this->getFiletype($file) == "gallery") {
				unlink($this->root . $this->path . ".thumbnails/" .$file);
			}
		}

		// show main view
		$this->mainView();
	}

	private function deleteFileRequest() {
		$this->deleteFileChecks();

		// Ask if really to delete?
		Writing::p(Lang::txt("Filebrowser_deleteFile.requestMessage", array($_GET["file"])));

		// Options
		$yes = new Link($this->linkprefix("deleteFile&path=" . $this->path . "&file=" . $_GET["file"]), Lang::txt("Filebrowser_deleteFile.approveDelete"));
		$yes->addIcon("remove");
		$yes->write();

		$no = new Link($this->linkprefix("mainView"), Lang::txt("Filebrowser_deleteFile.abort"));
		$no->addIcon("arrow_left");
		$no->write();
	}

	private function deleteFileChecks() {
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("Filebrowser_deleteFile.error_1"));
		}

		// decode filename
		if(!isset($_GET["file"])) {
			new BNoteError(Lang::txt("Filebrowser_deleteFile.error_2"));
		}
		$file = $this->path . urldecode($_GET["file"]);

		// check permission to delete
		if(!$this->adp->getSecurityManager()->userFilePermission(SecurityManager::$FILE_ACTION_DELETE, $file)) {
			new BNoteError(Lang::txt("Filebrowser_deleteFile.error_3"));
		}

		return $this->root . $file;
	}

	/**
	 * Adds a folder to the root directory.
	 */
	private function addFolder() {
		// check permission
		if($this->viewmode) {
			new BNoteError(Lang::txt("Filebrowser_addFolder.error_1"));
		}

		// validate name
		global $system_data;
		$system_data->regex->isName($_POST["folder"]);

		// prevent user from adding reserved directories to root folder
		if($_POST["folder"] == "users" || $_POST["folder"] == "groups") {
			new BNoteError(Lang::txt("Filebrowser_addFolder.error_2"));
		}

		// create folder in root
		$fullpath = $this->root . $this->path . $_POST["folder"];
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
		$script = "main.php";
		if(Data::endsWith($_SERVER["PHP_SELF"], "embed.php")) {
			$script = "embed.php";
		}
		return $script . "?mod=" . $system_data->getModuleId() . "$mode&fbfunc=$ext";
	}

	/**
	 * @return Array with database selection like array with the contents of the folder.
	 */
	private function getFilesFromFolder() {
		$result = array();

		// header
		$result[0] = array(
			"name", "size", "show", "thumbnail", "delete"
		);

		// create directory if not present
		if(!file_exists($this->root . $this->path)) {
			$this->createFolder($this->root . $this->path);
		}

		// data body
		$files = scandir($this->root . $this->path, SCANDIR_SORT_ASCENDING);
		foreach($files as $file) {
			$sharepath = $this->path . $file;
			$fullpath = $this->root . $sharepath;

			if(!$this->fileValid($fullpath, $file)) {
				continue;
			}
			// calculate size
			$size = filesize($fullpath);
			$size = ceil($size / 1000);
			$size = number_format($size, 0) . " kb";

			// create options
			$showLink = "";
			$thumbLink = "";
			$delLink = "";
			$iconName = "folder";
			$isDir = false;
			$hasTileView = false;
			if(is_dir($fullpath)) {
				# folder
				$isDir = true;

				if($file == "..") {
					if($this->levelUp() != null) {
						$showLink = $this->linkprefix("view&path=" . urlencode($this->levelUp()));
						$iconName = "arrow_up";
					}
					else {
						continue;
					}
				}
				else {
					$showLink = $this->linkprefix("view&path=" . urlencode($sharepath . "/"));
				}
			}
			else {
				# file
				$showLink = $this->sysdata->getFileHandler() . "?file=" . urlencode($sharepath);

				$filetype = $this->getFiletype($file);
				$iconName = $filetype;
				$hasTileView = (($filetype == "music") || ($filetype == "gallery"));
			}

			if(!$this->viewmode) {
				$delLink = $this->linkprefix("deleteFileRequest&path=" . urlencode($this->path) . "&file=" . urlencode($file));
			}

			// add to result array
			$row = array(
				"name" => $file,
				"size" => $size,
				"show" => $showLink,
				"delete" => $delLink,
				"icon" => $iconName,
				"directory" => $isDir,
				"tile" => $hasTileView,
				"thumbnail" => $thumbLink
			);
			array_push($result, $row);
		}

		return $result;
	}

	private function getFiletype($file) {
		if(strrpos($file, ".") !== false) {
			$end = strtolower(substr($file, strrpos($file, ".")+1));
			$music = array("mp3", "ogg", "acc", "wav");
			$image = array("jpg", "jpeg");
			if(in_array($end, $music)) {
				return "file-music";
			}
			if($end == "pdf") {
				return "filetype-pdf";
			}
			if(in_array($end, $image)) {
				return "file-image";
			}
		}
		return "file-earmark";
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
			return Lang::txt("Filebrowser_getFolderCaption.myFiles");
		}
		else if(Data::startsWith($this->path, "/groups")) {
			$gid = $this->getGroupIdFromPath();
			if($gid == null || $gid == "") $groupName = "";
			else $groupName = $this->adp->getGroupName($gid);
			return $groupName;
		}
		else if($this->path == "/users/") {
			return Lang::txt("Filebrowser_getFolderCaption.userFolder");
		}
		else if($this->path == "/") {
			return Lang::txt("Filebrowser_getFolderCaption.commonShare");
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
		if($file == ".htaccess") return false;
		else if($file == ".") return false;
		else if($file == ".thumbnails") return false;
		else if($fullpath . "/" == $GLOBALS["DATA_PATHS"]["userhome"]) return false;
		else if($fullpath . "/" == $GLOBALS["DATA_PATHS"]["grouphome"]) return false;
		else if($file == "_temp") return false;
		return true;
	}

	private function levelUp() {
		if(!$this->isCurrentPathMainFolder()) {
			$lastSlash = strrpos($this->path, "/", -2);
			return substr($this->path, 0, $lastSlash+1);
		}
		return null;
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
		$temp_dir = $this->root . "_temp";
		$zip_fname = $temp_dir . "/download.zip";

		// check that _temp folder exists
		if(!is_dir($temp_dir)) {
			mkdir($temp_dir);
		}

		// initialize zip-archive
		$zip = new ZipArchive();
		$zip->open($zip_fname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$dir_basepath = $this->root . $this->path;
		$dir_basepath = str_replace("\\", "/", $dir_basepath);

		$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir_basepath),
				RecursiveIteratorIterator::LEAVES_ONLY);

		foreach($files as $file) {
			$filename = str_replace("\\", "/", $file->getPathname());
			if(!Data::endsWith($filename, "/.") && !Data::endsWith($filename, "/..")) {
				$zip->addFile($filename);
			}
		}

		// create zip file by closing this archive
		$zip->close();

		Writing::p(Lang::txt("Filebrowser_download.archiveCreated"));

		$link = new Link($this->sysdata->getFileHandler() . "?mode=all&file=" .
		                 urlencode($zip_fname),
		                 Lang::txt("Filebrowser_download.downloadArchive"));
		$link->setTarget("_blank");
		$link->addIcon("arrow_down");
		$link->write();

		$back = new Link($this->linkprefix("view&path=" . urlencode($this->path)),
		                 Lang::txt("Filebrowser_download.back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	public function getName() { return NULL; }

}

?>