
<nav class="navbar  navbar-dark primary-color justify-content-between position-sticky" id="banner">
<?php if (!$system_data->loginMode()) {
    ?>
<button id="sidebarCollapse" class="navbar-toggler nav-link " type="button"><span class="dark-blue-text"><i
        class="fas fa-bars fa-1x"></i></span></button> 
<?php
}
?>
<span class="navbar-brand">
<?php 

if (!$system_data->loginMode()) {
echo $system_data->getModuleTitle(-1, false);
} else {
  echo $system_data->getCompany(); 
}
?>
</span>
<ul class="navbar-nav">
		<?php
if (!$system_data->loginMode()) {
    ?>

	<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true"
      aria-expanded="false" ><i class="fas fa-ellipsis-v"></i></a>

      
    <div class="dropdown-menu" id="action-menu">
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

