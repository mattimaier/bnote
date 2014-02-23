<?php

/**
 * Managing Genres.
 * @author Matti
 *
 */
class GenreView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName("Genre");
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=genre&func=";
	}
	
	function backToStart() {
		global $system_data;
		$link = new Link("?mod=" . $system_data->getModuleId() . "&mode=genre", "Zur&uuml;ck");
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllNoRef());
		$table->changeMode("genre&func=view");
		$table->setEdit("id");
		$table->write();
		
		$this->verticalSpace();
		global $system_data;
		$back = new Link("?mod=" . $system_data->getModuleId(), "Zur&uuml;ck");
		$back->addIcon("arrow_left");
		$back->write();
	}
}

?>