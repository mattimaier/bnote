<?php

/**
 * View for members module.
 * @author matti
 *
 */
class MitspielerView extends AbstractView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		Writing::h1("Mitspieler");
		
		$members = $this->getData()->getMembers();
		
		$table = new Table($members);
		$table->renameAndAlign($this->getData()->getFields());
		$table->renameHeader("fullname", "Name");
		$table->write();
	}
}

?>