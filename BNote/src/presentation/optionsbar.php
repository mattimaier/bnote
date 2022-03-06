<div id="optionsbar" class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	<h4 class="h4">
		<?php
		if($GLOBALS["mainController"]->getView() != NULL) {
			echo $GLOBALS["mainController"]->getView()->getTitle();
		}
		else {
			echo $system_data->getModuleTitle($_GET["mod"]);
		}
		?>
	</h4>
	<div class="btn-toolbar mb-2 mb-md-0">
		<?php 
		// buttons
		$GLOBALS["mainController"]->getView()->showOptions();
		?>
	</div>
</div>