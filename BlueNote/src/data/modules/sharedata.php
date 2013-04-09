<?php

/**
 * Data provider for share module.
 * @author matti
 *
 */
class ShareData extends AbstractData {
	
	/**
	 * Create a new data provider for the share module.
	 */
	function __construct() {
		$this->init();
	}
	
	function canUserEdit($uid) {
		$cid = $this->database->getCell("user", "contact", "id = $uid");
		$status = $this->database->getCell("contact", "status", "id = $cid");
		
		$group = $GLOBALS["system_data"]->getShareEditGroup();
		if($group == $status) {
			return true;
		}
		else if($status == "ADMIN") {
			return true;
		}
		return false;
	}
}

?>