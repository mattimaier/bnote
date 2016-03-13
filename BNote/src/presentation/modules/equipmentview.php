<?php

class EquipmentView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("equipment"));
	}
	
}

?>