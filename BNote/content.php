<?php

# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
?>

<!-- Content Area -->
<div class="container-fluid">
	<div class="row">
		<?php
		# Display Navigation
		include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";
		?>
		
		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
			<?php
			if(isset($_GET["mod"]) && $_GET["mod"] != "extGdpr") {
				include $GLOBALS["DIR_PRESENTATION"] . "optionsbar.php";
			}
			
			$mainController->getController()->start();
			?>
		</main>
	</div>
</div>
