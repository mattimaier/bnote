<?php
# Display Banner
include $GLOBALS["DIR_PRESENTATION"] . "banner.php";
if (isset($_GET["mod"]) && $_GET["mod"] != "extGdpr") {
    include $GLOBALS["DIR_PRESENTATION"] . "optionsbar.php";
}
?>

<div class="container-fluid">
    <div class="row">
        <?php
        # Display Navigation
        include $GLOBALS["DIR_PRESENTATION"] . "navigation.php";
        ?>
        <main id="main" class="container-fluid">
            <?php
            $mainController->getController()->start();
            ?>
        </main>

    </div>
</div>