<?php

class EquipmentView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("equipment"));
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt("add_entity", array($this->entityName)), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		$form->setFieldValue("purchase_price", "0,00");
		$form->setFieldValue("current_value", "0,00");
		$form->setFieldValue("quantity", "1");
		$form->write();
	}
	
}

?>