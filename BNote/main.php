<?php
/**
 * Main entry file for the web application.
 */

# debugging
#error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

# Make a few settings
date_default_timezone_set("Europe/Berlin");

# Language Correction
setlocale(LC_ALL, 'de_DE');
header("Content-type: text/html; charset=utf-8");

# Initialize System
include "dirs.php";
require_once $GLOBALS["DIR_LOGIC"] . "init.php";

# Login forward if necessary
if (isset($_GET["mod"]) && $_GET["mod"] === "login" && isset($_GET["mode"]) && $_GET["mode"] === "login") {
    require_once $GLOBALS["DIR_LOGIC"] . "defaultcontroller.php";
    require_once $GLOBALS["DIR_LOGIC_MODULES"] . "logincontroller.php";
    require_once $GLOBALS["DIR_DATA"] . "fieldtype.php";
    require_once $GLOBALS["DIR_DATA"] . "abstractdata.php";
    require_once $GLOBALS["DIR_DATA_MODULES"] . "logindata.php";
    $ctrl = new LoginController();
    $loginData = new LoginData();
    $ctrl->setData($loginData);
    $ctrl->doLogin();
}

require_once $GLOBALS["DIR_LOGIC"] . "controller.php";
$mainController = new Controller();
global $mainController;

?>

<!DOCTYPE html>
<HTML>

<?php
# Display HEAD
require_once $GLOBALS["DIR_PRESENTATION"] . "head.php";
?>

<BODY>

<?php
include "desktop.php";

# Display Footer
require_once $GLOBALS["DIR_PRESENTATION"] . "footer.php";
?>

</BODY>

<?php
//embed jQuery library
$jQuery_dir = $GLOBALS["DIR_LIB"] . "jquery/";
$MDBootstrap_dir = $GLOBALS["DIR_LIB"] . "MDBootstrap/";
?>

  <!-- Bootstrap tooltips -->
  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/popper.min.js"></script>
  <!-- Bootstrap core JavaScript -->
  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/bootstrap.min.js"></script>
  <!-- MDB core JavaScript -->
  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/mdb.min.js"></script>

  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/addons/datatables.min.js"></script>
  <script type="text/javascript" src="<?php echo $MDBootstrap_dir; ?>js/addons/dataTables.responsive.min.js"></script>

  <script type="text/javascript">
    function toggleSidebar(animated) {
        if (animated) {
            var transition = "all 0.3s";
            
            $("#sidebar").css("transition", transition);
            $("#sidebar-overlay").css("transition", transition);
            console.log(transition);


        } else {
            $("#sidebar").css("transition", "initial");
            $("#sidebar-overlay").css("transition", "initial");
        }


        $('#sidebar').toggleClass('active');
        $('#sidebar-overlay').toggleClass('active');

                if ($('#sidebar').hasClass("active")) {
                    var width = $("#sidebar").width();
                    $("#sidebar").css("margin-left", -width);

                } else {
                $("#sidebar").css("margin-left", 0);

                }
     }
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
        toggleSidebar(true);
            });

            if ($(document).width() < 576) {
                toggleSidebar(false);
            }

            var width = $("#action-menu").width();
            $("#action-menu").css("left", -width);
        });
    </script>
    
</HTML>
