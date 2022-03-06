<?php

/**
 * Class which provides a full set of crud methods for a simple module.
 * @author matti
 */
abstract class CrudView extends AbstractView {
	
	protected $entityName;
	protected $addEntityName;
	
	/**
	 * Views all entities in a table.<br />
	 * <strong>Make sure to set the entity name!</storng>
	 * 
	 * (non-PHPdoc)
	 * @see AbstractView::start()
	 */
	public function start() {
		Writing::p(Lang::txt("CrudView_start.showAllTable"));		
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
		$add = new Link($this->modePrefix() . "addEntity", Lang::txt($this->getAddEntityName()));
		$add->addIcon("plus");
		$add->write();
	}
	
	public function addEntity() {
		$this->addEntityForm();
	}
	
	function addEntityTitle() {
		return Lang::txt($this->getAddEntityName());
	}
	
	protected function addEntityForm() {
		// add entry form
		$form = new Form("", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		$form->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllNoRef());
		$table->setEdit($this->idField);
		$table->setEditIdField($this->idParameter);
		$table->renameAndAlign($this->getData()->getFields());
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
		new Message(Lang::txt("CrudView_add.Message_1", array($this->entityName)), Lang::txt("CrudView_add.Message_2"));
	}
	
	public function viewTitle() { return Lang::txt("CrudView_view.Message", array($this->entityName)); }
	
	public function view() {
		$this->checkID();
		
		// show the details
		$this->viewDetailTable();
	}
	
	function viewOptions() {
		// back button
		$this->backToStart();
		
		// show buttons to edit and delete
		$edit = new Link($this->modePrefix() . "edit&" . $this->idParameter . "=" . $_GET[$this->idParameter],
				Lang::txt("CrudView_viewOptions.edit", array($this->entityName)));
		$edit->addIcon("pen");
		$edit->write();
		
		$del = new Link($this->modePrefix() . "delete_confirm&" . $this->idParameter . "=" . $_GET[$this->idParameter],
				Lang::txt("CrudView_viewOptions.delete_confirm", array($this->entityName)));
		$del->addIcon("trash3");
		$del->write();
		
		// additional buttons
		$this->additionalViewButtons();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdNoRef($_GET[$this->idParameter]);
		$details = new Dataview();
		foreach($this->getData()->getFields() as $dbf => $info) {
			# format values
			$val = $entity[$dbf];
			if($info[1] == FieldType::DATE || $info[1] == FieldType::DATETIME) {
				$val = Data::convertDateFromDb($val);
			}
			elseif ($info[1] == FieldType::DECIMAL || $info[1] == FieldType::CURRENCY) {
				$val = Lang::formatDecimal($val);
			}
			$details->addElement($info[0], $val);
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
		$this->backToViewButton($_GET[$this->idParameter]);
	}
	
	protected function editEntityForm() {
		$form = new Form(Lang::txt("CrudView_editEntityForm.delete_edit", array($this->entityName)),
				$this->modePrefix() . "edit_process&" . $this->idParameter . "=" . $_GET[$this->idParameter]);
		$form->autoAddElements($this->getData()->getFields(),
									$this->getData()->getTable(), $_GET[$this->idParameter]);
		$form->removeElement($this->idField);
		$form->write();
	}
	
	public function edit_process() {
		$this->checkID();
		
		// validate
		if(!isset($_GET["manualValid"]) || $_GET["manualValid"] != "true") {
			$this->getData()->validate($_POST);
		}
		
		// update
		$this->getData()->update($_GET[$this->idParameter], $_POST);
		
		// show success
		new Message($this->entityName, Lang::txt("CrudView_edit_process.delete_changed"));
	}
	
	public function delete_confirm() {
		$this->checkID();
		$this->deleteConfirmationMessage($this->getEntityName(),
					$this->modePrefix() . "delete&" . $this->idParameter . "=" . $_GET[$this->idParameter],
					$this->modePrefix() . "view&" . $this->idParameter . "=" . $_GET[$this->idParameter]);
	}
	
	public function delete_confirmOptions() {
		// none
	}
	
	public function delete() {
		$this->checkID();
		// remove
		$this->getData()->delete($_GET[$this->idParameter]);
		
		// show success
		new Message(Lang::txt("CrudView_delete.deleted_entity", array($this->entityName)),
						Lang::txt("CrudView_delete.entryDeleted"));
	}
	
	/**
	 * Writes a button which brings the user back to
	 * mode=view&id=<id>.
	 * @param int $id Usually $_GET["id"], but can be any id for the view mode.
	 */
	public function backToViewButton($id) {
		global $system_data;
		$btv = new Link($this->modePrefix() . "view&" . $this->idParameter . "=$id", Lang::txt("CrudView_backToViewButton.back"));
		$btv->addIcon("arrow-left");
		$btv->write();
	}
	
	public function setEntityName($name) {
		$this->entityName = $name;
	}
	
	public function getEntityName() {
		return $this->entityName;
	}
	
	public function setAddEntityName($name) {
		$this->addEntityName = $name;
	}
	
	public function getAddEntityName() {
		return $this->addEntityName;
	}
}