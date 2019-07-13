<?php

/**
 * Outfits view class.
 */
class OutfitsView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("OutfitsView_construct.EntityName"));
		$this->setaddEntityName(Lang::txt("OutfitsView_construct.addEntityName"));
	}
}

?>