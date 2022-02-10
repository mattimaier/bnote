<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	<h2 class="h2">
		<?php echo $system_data->getModuleTitle($_GET["mod"]); ?>
	</h2>
	<div class="btn-toolbar mb-2 mb-md-0">
		<div class="btn-group me-2">
			<?php 
			$GLOBALS["mainController"]->getView()->showOptions();
			?>
		</div>
	</div>
</div>