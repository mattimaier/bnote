<?php

/**
 * Manages access to various resources, e.g. files.
 * @author Matti
 *
 */
class SecurityManager {

	/**
	 * @var Systemdata
	 */
	private $sysdata;
	
	/**
	 * @var Regex
	 */
	private $regex;
	
	/**
	 * @var Database
	 */
	private $database;
	
	/**
	 * @var ApplicationDataProvider
	 */
	private $adp;
	
	/**
	 * Read action on a file.
	 * @var char
	 */
	public static $FILE_ACTION_READ = "r";
	
	/**
	 * Write action on a file.
	 * @var char
	 */
	public static $FILE_ACTION_WRITE = "w";
	
	/**
	 * Initializes the security manager.
	 * @param SystemData $sysdata System Data Access.
	 * @param ApplicationDataProvider $adp Application Data Provider.
	 * @param Regex $regex Regular Expressions.
	 */
	function __construct($sysdata, $adp, $regex = null) {
		$this->sysdata = $sysdata;
		$this->database = $sysdata->dbcon;
		$this->adp = $adp;
		$this->regex = $regex;
	}
	
	/** 
	 * Managess access to filesystem.
	 * Permissions:
	 * - Group members of Administrators have access to all folders
	 * - Group members have access to their group folders
	 * - Every user has access to only his folder
	 * - Every user has access to general
	 *
	 * @param String $file complete path to file.
	 * @param Integer $uid optional: User ID, by default current User.
	 * @return boolean True when file can be accessed, otherwise false.
	 */
	public function canUserAccessFile($file, $uid = -1) {
		if($uid == -1) $uid = $_SESSION["user"];
		
		// give super user access to all files
		if($this->sysdata->isUserSuperUser()) {
			return true;
		}
		
		// determine whether user is in group admins
		$isAdmin = $this->isUserAdmin();
			
		// check where the file is and what permission is needed
		if(Data::startsWith($this->innerpath, $this->sysdata->getUsersHomeDir())) {
			return true;
		}
		else if(Data::startsWith($file, "groups")) {
			// check whether user is in group
			$gid = $this->getGroupIdFromPath($file);
			
			// deny access to folders the user is not member of
			return $this->adp->isGroupMember($gid);
		}
		else if(Data::startsWith($file, "users") && $isAdmin) {
			// give admins access to all user accounts
			return true;
		}
		else if(Data::startsWith($file, "users")) {
			// deny access to other users homes if not admins
			return false;
		}
		else {
			// give access to other files to everyone
			return true;
		}
	}
	
	/**
	 * Whether the user has access to read/write a file.
	 * @param char $action @see $FILE_ACTION_READ and $FILE_ACTION_WRITE.
	 * @param string $file full path to file
	 */
	public function userFilePermission($action, $file) {
		// simplest implementation: when user can read the file he/she can write it as well.
		$access = $this->canUserAccessFile($file);
		return $access;
	}
	
	private function getGroupIdFromPath($path) {
		$idstart = strpos($path, "/group_")+7;
		$len = strpos($path, "/", $idstart) - $idstart;
		return substr($path, $idstart, $len);
	}
	
	/**
	 * Checks whether a user is a member of the administrators group or a super user.
	 * @param Integer $uid optional: User ID, by default current User.
	 * @return True when the user is member of the administrators group or a super user, otherwise false.
	 */
	public function isUserAdmin($uid = -1) {
		if($this->sysdata->isUserSuperUser($uid)) return true;
		return in_array(1, $this->adp->getUsersGroups($uid));
	}
	
	/**
	 * Checks the access to resources based on the mode.
	 * @param string $path Relative path to file.
	 * @param string $mode Possible: "all" (every logged on user) or "module" (if access to module).
	 * @return True when access is granted, otherwise false.
	 */
	public function modeAccess($path, $mode) {
		if($mode == "all") {
			return true;
		}
		else if($mode == "module") {
			if(strpos($path, "members") !== false) {
				$contactModId = 3; // Kontakte
				return $this->sysdata->userHasPermission($contactModId);
			}
			else if(strpos($path, "programs") !== false) {
				$concertModId = 4; // Konzerte
				return $this->sysdata->userHasPermission($concertModId);
			}
		}
		return false;
	}
}

?>