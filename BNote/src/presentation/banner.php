
<nav class="navbar  navbar-dark primary-color justify-content-between position-sticky">
<a class="navbar-brand" href="#">
<?php echo $system_data->getCompany(); ?>
</a>
<ul class="navbar-nav">
		<?php
if (!$system_data->loginMode()) {
    ?>

	<li class="nav-item dropdown dropdown-menu-left">
    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true"
      aria-expanded="false">...</a>
    <div class="dropdown-menu">
<?php	$GLOBALS["mainController"]->getView()->showOptions();?>

      <div class="dropdown-divider"></div>
	  <a class="dropdown-item"  id="Logout_link" href="main.php?mod=logout"><?php echo Lang::txt("banner_Logout.Logout"); ?></a>
    </div>
  </li>

		<?php
}
?>
</ul>
</nav>

