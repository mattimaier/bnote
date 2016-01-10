<?php

# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
?>

<!-- Content Area -->
<div id="content_container">
	<?php
	include $GLOBALS["DIR_PRESENTATION"] . "optionsbar.php"; 
	?>
	<?php
	# Display Navigation
	include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";
	?>
	<div id="content_insets">
		<div id="content">
			<?php
			$mainController->getController()->start();
			?>
		</div>
	</div>
</div>

?>