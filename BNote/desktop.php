
<?php
# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
if (isset($_GET["mod"]) && $_GET["mod"] != "extGdpr") {
    include $GLOBALS["DIR_PRESENTATION"] . "optionsbar.php";
}
?>


<!-- Content Area -->
<div class="container-fluid" id="content_container"<?php if ($system_data->loginMode()) {echo 'class="login"';}?>>
<div class="row">
<?php
# Display Navigation

include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";
?>

	<main class="col-md-9 ml-sm-auto col-lg-10 px-4">
			<?php
$mainController->getController()->start();
?>
	</main>
	</div>
</div>