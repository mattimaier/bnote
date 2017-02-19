<?php

/**
 * Outfits view class.
 */
class OutfitsView extends CrudView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(Lang::txt("outfit"));
	}
}

?>