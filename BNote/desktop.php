<?php
# Display Navigation
include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";

# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
?>

<!-- Content Area -->
<div id="content_container"<?php if($system_data->loginMode()) { echo 'class="login"'; } ?>>
	<?php
	if(isset($_GET["mod"]) && $_GET["mod"] != "extGdpr") {
		include $GLOBALS["DIR_PRESENTATION"] . "optionsbar.php";
	}
	?>
	<div id="content_insets">
		<div id="content">
			<?php
			$mainController->getController()->start();
			?>
		</div>
	</div>
</div>