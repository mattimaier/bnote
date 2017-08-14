<?php

/**
 * View of the share module.
 * @author matti
 *
 */
class ShareView extends AbstractView {
	
	/**
	 * Main widget to manage files and folders.
	 * @var Filebrowser
	 */
	private $filebrowser;
	
	/**
	 * Create a new share module view.
	 * @param DefaultController $ctrl Controller of the module.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	private function initFilebrowser() {
		if($this->filebrowser == null) {
			$this->filebrowser = new Filebrowser($GLOBALS["DATA_PATHS"]["share"], $this->getData()->getSysdata(), $this->getData()->adp());
		}
	}
	
	function start() {
		$this->initFilebrowser();
		$viewMode = $this->getData()->getSysdata()->getDynamicConfigParameter("share_nonadmin_viewmode");
		if($viewMode == "1" && !$this->getData()->getSysdata()->isUserSuperUser()
				&& !$this->getData()->adp()->isGroupMember(1)) {
			$this->filebrowser->viewMode(true);
		}
		
		$this->filebrowser->write();
	}
	
	function startOptions() {
		$this->initFilebrowser();
		$this->filebrowser->showOptions();
	}
	
}

?>