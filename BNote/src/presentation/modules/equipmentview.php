<?php

class EquipmentView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("EquipmentView_construct.EntityName"));
		$this->setaddEntityName(Lang::txt("EquipmentView_construct.addEntityName"));
	}
	
	function addEntityForm() {
		$form = new Form(Lang::txt($this->getaddEntityName()), $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement($this->idField);
		$form->setFieldValue("purchase_price", "0,00");
		$form->setFieldValue("current_value", "0,00");
		$form->setFieldValue("quantity", "1");
		$form->write();
	}
	
}

?>