<?php

/**
 * Class which provides a full set of crud methods for a simple module.
 * @author matti
 */
abstract class CrudView extends AbstractView {
	
	private $entityName;
	
	/**
	 * Views all entities in a table.<br />
	 * <strong>Make sure to set the entity name!</storng>
	 * 
	 * (non-PHPdoc)
	 * @see AbstractView::start()
	 */
	public function start() {
		Writing::p(Lang::txt("selectEntryText"));		
		$this->showAllTable();
	}
	
	function showOptions() {
		if(!isset($_GET["mode"]) || $_GET["mode"] == "start") {
			$this->startOptions();
		}
		else if($this->isSubModule($_GET["mode"])) {
			$this->subModuleOptions();
		}
		else {
			$subOptionFunc = $_GET["mode"] . "Options";
			if(method_exists($this, $subOptionFunc)) { 
				$this->$subOptionFunc();
			}
			else {
				$this->defaultOptions();
			}
		}
	}
	
	/**
	 * Integrate submodules by overwriting this method not to return null.
	 * @param $mode String Name of mode
	 * @return True when the given mode belongs to a submodule, otherwise false.
	 */
	protected function isSubModule($mode) {
		return false;
	}
	
	/**
	 * Overwrite this method to implement submodule options.
	 */
	protected function subModuleOptions() {
		// blank by default
	}
	
	protected function defaultOptions() {
		$this->backToStart();
	}
	
	protected function startOptions() {
		$add = new Link($this->modePrefix() . "addEntity", $this->getEntityName() . " " . Lang::txt("add"));
		$add->addIcon("plus");
		$add->write();
	}
	
	public function addEntity() {
		$this->addEntityForm();
	}
	
	protected function addEntityForm() {
		// add entry form
		$form = new Form($this->entityName ." " . Lang::txt("add"), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		$form->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllNoRef());
		$table->setEdit("id");
		$table->write();
	}
	
	protected function writeTitle() {
		global $system_data;
		Writing::h2($system_data->getModuleTitle());
		Writing::p(Lang::txt("selectEntryText"));
	}
	
	public function add() {
		// validate
		if(!isset($_GET["manualValid"]) || $_GET["manualValid"] != "true") {
			$this->getData()->validate($_POST);
		}
		
		// process
		$this->getData()->create($_POST);
		
		// write success
		new Message($this->entityName . " " . Lang::txt("saved"),
						Lang::txt("entrySaved"));
	}
	
	public function view() {
		$this->checkID();
		
		// heading
		Writing::h2($this->entityName . " " . Lang::txt("details"));
		
		// show the details
		$this->viewDetailTable();
	}
	
	function viewOptions() {
		// back button
		$this->backToStart();
		$this->buttonSpace();
		
		// show buttons to edit and delete
		$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"],
				$this->entityName . " " . Lang::txt("edit"));
		$edit->addIcon("edit");
		$edit->write();
		$this->buttonSpace();
		$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"],
				$this->entityName . " " . Lang::txt("delete"));
		$del->addIcon("remove");
		$del->write();
		$this->buttonSpace();
		
		// additional buttons
		$this->additionalViewButtons();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdNoRef($_GET["id"]);
		$details = new Dataview();
		foreach($this->getData()->getFields() as $dbf => $info) {
			$details->addElement($info[0], $entity[$dbf]);
		}
		$details->write();
	}
	
	protected function additionalViewButtons() {
		// by default empty
	}
	
	public function edit() {
		$this->checkID();
		
		// show form
		$this->editEntityForm();
	}
	
	protected function editOptions() {
		$this->backToViewButton($_GET["id"]);
	}
	
	protected function editEntityForm() {
		$form = new Form($this->entityName . " " . Lang::txt("edit"),
							$this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->autoAddElements($this->getData()->getFields(),
									$this->getData()->getTable(), $_GET["id"]);
		$form->removeElement("id");
		$form->write();
	}
	
	public function edit_process() {
		$this->checkID();
		
		// validate
		if(!isset($_GET["manualValid"]) || $_GET["manualValid"] != "true") {
			$this->getData()->validate($_POST);
		}
		
		// update
		$this->getData()->update($_GET["id"], $_POST);
		
		// show success
		new Message($this->entityName . " " . Lang::txt("changed"),
						Lang::txt("entryChanged"));
	}
	
	public function delete_confirm() {
		$this->checkID();
		$this->deleteConfirmationMessage($this->getEntityName(),
					$this->modePrefix() . "delete&id=" . $_GET["id"],
					$this->modePrefix() . "view&id=" . $_GET["id"]);
	}
	
	public function delete_confirmOptions() {
		// none
	}
	
	public function delete() {
		$this->checkID();
		// remove
		$this->getData()->delete($_GET["id"]);
		
		// show success
		new Message($this->entityName . " " . Lang::txt("deleted"),
						Lang::txt("entryDeleted"));
	}
	
	/**
	 * Writes a button which brings the user back to
	 * mode=view&id=<id>.
	 * @param int $id Usually $_GET["id"], but can be any id for the view mode.
	 */
	public function backToViewButton($id) {
		global $system_data;
		$btv = new Link($this->modePrefix() . "view&id=$id", Lang::txt("back"));
		$btv->addIcon("arrow_left");
		$btv->write();
	}
	
	public function setEntityName($name) {
		$this->entityName = $name;
	}
	
	public function getEntityName() {
		return $this->entityName;
	}
}