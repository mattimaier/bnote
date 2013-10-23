<?php

/**
 * Provides secured access to file system.
 * @author Matti
 *
 */
require_once("../../dirs.php");

class FileHandler {

	private $innerpath;
	private $filepath;
	
	/**
	 * Relative path to root directory.
	 */
	private $dir_prefix = "../../";
	
	function __construct() {
		if(!isset($_GET["mode"])) {	
			if(!isset($GLOBALS["DATA_PATHS"]["share"])) echo "Filesystem configuration missing";
		}
		
		$this->init();
		if($this->authorize()) {
			$this->giveFile();
		}
	}
	
	private function init() {
		if(!isset($_GET["file"])) exit;
		if(isset($_GET["mode"])) {
			$this->innerpath = urldecode($_GET["file"]);
		}
		else {
			$this->innerpath = $GLOBALS["DATA_PATHS"]["share"] . urldecode($_GET["file"]);
		}
		$this->filepath = $this->dir_prefix . $this->innerpath;
		$this->filepath = str_replace("\\'", "'", $this->filepath);
		
		if(!file_exists($this->filepath)) {
			header("HTTP/1.0 404 " . $this->innerpath . " not found");
			exit;
		}
		
		if(!is_file($this->filepath)) {
			header("HTTP/1.0 405 Only files are supported.");
			exit;
		}
	}
	
	private function authorize() {
		session_start();
		if(!isset($_SESSION["user"])) {
			header('HTTP/1.0 403 Forbidden');
			echo "No access to file.";			
			return false;
		}
		else {			
			// connect to application
			require_once("systemdata.php");
			$GLOBALS["DIR_WIDGETS"] = $this->dir_prefix . $GLOBALS["DIR_WIDGETS"];
			require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
			$GLOBALS["DIR_LOGIC"] = $this->dir_prefix . $GLOBALS["DIR_LOGIC"];
			require_once("applicationdataprovider.php");
			
			// Build Database Connection
			$db = new Database();
			$sysdata = new Systemdata($this->dir_prefix);
			$adp = new ApplicationDataProvider($db, new Regex(), $sysdata);
			$secManager = $adp->getSecurityManager();
			
			if(isset($_GET["mode"])) {
				return $secManager->modeAccess($this->innerpath, $_GET["mode"]);
			}
			
			return ($secManager->canUserAccessFile($this->innerpath));
		}
	}
	
	private function getGroupIdFromPath() {
		$idstart = strpos($this->innerpath, "/group_")+7;
		$len = strpos($this->innerpath, "/", $idstart) - $idstart;
		return substr($this->innerpath, $idstart, $len);
	}
	
	private function giveFile() {		
		// open the file in a binary mode
		$fp = fopen($this->filepath, 'rb');
		
		// send the right headers
		$type = FileHandler::getFileMimeType($this->filepath);
		
		header("Content-Type: $type");
		header("Content-Length: " . filesize($this->filepath));
		
		// dump the picture and stop the script
		fpassthru($fp);
		exit;
	}
	
	public static function getFileMimeType($file) {
		if (function_exists("finfo_file")) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
			return $mime;
		} else if (function_exists("mime_content_type")) {
			return mime_content_type($file);
		} else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
			$file = escapeshellarg($file);
			$mime = shell_exec("file -bi " . $file);
			return $mime;
		} else {
			return false;
		}
	}
}

new FileHandler();
?>