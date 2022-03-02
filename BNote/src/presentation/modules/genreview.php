<?php

/**
 * Managing Genres.
 * @author Matti
 *
 */
class GenreView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("GenreView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("GenreView_construct.addEntityName"));
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=genre&func=";
	}
	
	function backToStart() {
		$link = new Link("?mod=" . $this->getModId() . "&mode=genre", Lang::txt("GenreView_backToStart.Back"));
		$link->addIcon("arrow_left");
		$link->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllNoRef());
		$table->changeMode("genre&func=view");
		$table->setEdit("id");
		$table->write();
	}
	
	function startOptions() {
		$back = new Link("?mod=" . $this->getModId(), Lang::txt("GenreView_startOptions.Back"));
		$back->addIcon("arrow_left");
		$back->write();
		
		parent::startOptions();
	}
	
	protected function isSubModule($mode) {
		return true;
	}
	
	protected function subModuleOptions() {
		if(!isset($_GET["func"])) {
			$this->startOptions();
		}
		else {
			$subOptionFunc = $_GET["func"] . "Options";
			if(method_exists($this, $subOptionFunc)) {
				$this->$subOptionFunc();
			}
			else {
				$this->defaultOptions();
			}
		}
	}
}

?>