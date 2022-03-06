<?php

/**
 * Outfits view class.
 */
class OutfitsView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("OutfitsView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("OutfitsView_construct.addEntityName"));
	}
	
	function addEntityForm() {
		$form = new Form("", $this->modePrefix() . "add");
		$form->addElement(Lang::txt("OutfitsData_construct.name"), new Field("name", "", FieldType::CHAR), true, 12);
		$form->addElement(Lang::txt("OutfitsData_construct.description"), new Field("description", "", FieldType::TEXT), false, 12);
		$form->write();
	}
	
	function editTitle() {
		return Lang::txt("OutfitsView_construct.editEntityName");
	}
	
	function editEntityForm() {
		$outfit = $this->getData()->findByIdNoRef($_GET["id"]);
		$action = $this->modePrefix() . "edit_process&" . $this->idParameter . "=" . $_GET[$this->idParameter];
		$form = new Form("", $action);
		$form->addElement(Lang::txt("OutfitsData_construct.name"), new Field("name", $outfit["name"], FieldType::CHAR), true, 12);
		$form->addElement(Lang::txt("OutfitsData_construct.description"), new Field("description", $outfit["description"], FieldType::TEXT), false, 12);
		$form->write();
	}
}

?>