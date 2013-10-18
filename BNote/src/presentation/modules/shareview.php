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
		Writing::h1("Dateiverwaltung");
		$fb = new Filebrowser($GLOBALS["DATA_PATHS"]["share"], $this->getData()->getSysdata(), $this->getData()->adp());
		if(!$this->getData()->canUserEdit($_SESSION["user"])) {
			$fb->viewMode();
		}
		$fb->write();
	}
	
}

?>