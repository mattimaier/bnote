<?php

/**
 * View of the share module.
 * @author matti
 *
 */
class ShareView extends AbstractView {
	
	/**
	 * Create a new share module view.
	 * @param DefaultController $ctrl Controller of the module.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Tausch Ordner");
		
		//TODO create a single dir for each user to share stuff from the admin with the user
		
		$fb = new Filebrowser($GLOBALS["DATA_PATHS"]["share"]);
		if(!$this->getData()->canUserEdit($_SESSION["user"])) {
			$fb->viewMode();
		}
		$fb->write();
	}
	
}

?>