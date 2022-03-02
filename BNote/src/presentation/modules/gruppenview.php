<?php

class GruppenView extends CrudView {
	
	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("GruppenView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("GruppenView_construct.addEntityName"));
	}
	
	/**
	 * Extended version of modePrefix for sub-module.
	 */
	function modePrefix() {
		return "?mod=" . $this->getModId() . "&mode=groups&func=";
	}
	
	function start() {
		Writing::h1(Lang::txt("GruppenView_start.Title"));
		$explanation = Lang::txt("GruppenView_start.explanation");
		Writing::p($explanation);
		
		$groups = $this->getData()->getGroups();
		$table = new Table($groups);
		$table->renameAndAlign($this->getData()->getFields());
		$table->setEdit("id");
		$table->changeMode("groups&func=view");
		$table->write();
	}
	
	function showOptions() {
		if(!isset($_GET["func"]) || $_GET["func"] == "start") {
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
	
	function startOptions() {
		$backBtn = new Link("?mod=" . $this->getModId() . "&mode=start", Lang::txt("GruppenView_startOptions.Back"));
		$backBtn->addIcon("arrow_left");
		$backBtn->write();
		
		$new = new Link($this->modePrefix() . "addEntity", Lang::txt("GruppenView_startOptions.addEntity"));
		$new->addIcon("plus");
		$new->write();
	}
	
	function backToStart() {
		$back = new Link($this->modePrefix() . "start", Lang::txt("GruppenView_backToStart.Back"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function view() {
		$this->checkID();
		
		$group = $this->getData()->findByIdNoRef($_GET["id"]);
		Writing::h2(Lang::txt("GruppenView_view.Title") . $group["name"]);
		
		// group information
		$dv = new Dataview();
		$dv->autoAddElements($group);
		$dv->autoRename($this->getData()->getFields());
		$dv->write();
		
		// group members
		Writing::h3(Lang::txt("GruppenView_view.GroupMembers"));
		
		$members = $this->getData()->getGroupMembers($_GET["id"]);
		$table = new Table($members);
		$table->write();
	}
	
	function viewOptions() {
		$this->backToStart();
		
		if($_GET["id"] != KontakteData::$GROUP_ADMIN && $_GET["id"] != KontakteData::$GROUP_MEMBER) {
			// show buttons to edit and delete
			$edit = new Link($this->modePrefix() . "edit&id=" . $_GET["id"],
					$this->getEntityName() . Lang::txt("GruppenView_viewOptions.edit"));
			$edit->addIcon("edit");
			$edit->write();
			
			$del = new Link($this->modePrefix() . "delete_confirm&id=" . $_GET["id"],
					$this->getEntityName() . Lang::txt("GruppenView_viewOptions.remove"));
			$del->addIcon("remove");
			$del->write();
		}
	}
}

?>