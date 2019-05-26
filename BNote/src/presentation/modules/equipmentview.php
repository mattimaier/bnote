<?php

class EquipmentView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("EquipmentView_construct.EntityName"));
		$this->setaddEntityName(Lang::txt("EquipmentView_construct.addEntityName"));
	}
	
	function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllEquipment());
		$table->setEdit($this->idField);
		$table->setEditIdField($this->idParameter);
		$table->renameAndAlign($this->getData()->getFieldsWithCustomFields(EquipmentData::$CUSTOM_DATA_OTYPE));
		$table->showFilter();
		$table->removeColumn("id");
		$table->write();			  
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
	
	function editEntityForm() {
		$this->checkID();
		
		$eq = $this->getData()->findEquipmentById($_GET["id"]);
		
		$form = new Form(Lang::txt("edit", array($this->getEntityName())),
				$this->modePrefix() . "edit_process&" . $this->idParameter . "=" . $_GET[$this->idParameter]);
		
		$form->autoAddElements($this->getData()->getFields(),
				$this->getData()->getTable(), $_GET[$this->idParameter]);
		
		$this->appendCustomFieldsToForm($form, EquipmentData::$CUSTOM_DATA_OTYPE, $eq, false); 
		$form->removeElement($this->idField);
		$form->write();
	}
	
	function viewDetailTable() {
		$this->checkID();
		
		$dv = new Dataview();
		$eq = $this->getData()->findEquipmentById($_GET["id"]);
		foreach($this->getData()->getFieldsWithCustomFields(EquipmentData::$CUSTOM_DATA_OTYPE) as $dbf => $info) {
			# format values
			$val = $eq[$dbf];
			if($info[1] == FieldType::DATE || $info[1] == FieldType::DATETIME) {
				$val = Data::convertDateFromDb($val);
			}
			elseif ($info[1] == FieldType::DECIMAL) {
				$val = Lang::formatDecimal($val);
			}
			elseif ($info[1] == FieldType::BOOLEAN) {
				$val = $val == 1 ? Lang::txt("yes") : Lang::txt("no");
			}
			$dv->addElement($info[0], $val);
		}
		$dv->write();
	}
	
}

?>