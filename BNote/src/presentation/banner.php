<!-- Banner -->
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow d-print-none">

	<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="?mod=1">
		<img src="style/images/BNote_Logo_white_transparent.svg" alt="BNote" height="32px" id="bnote-logo" />
		<span class="d-none d-md-inline-block"><?php echo $system_data->getCompany(); ?></span>
	</a>
	
	<div class="navbar-nav bnote-useradmin-bar ms-2">
		<div class="d-flex flex-row">
			<?php	
			if ($system_data->isUserAuthenticated()) {
			?>
      		<a href="?mod=<?php echo $system_data->getModuleId("Kontaktdaten"); ?>" class="p-2 text-light">
				<i class="bi-person-circle"></i>
	      	</a>
	      	<?php
	      	$adminModuleId = $system_data->getModuleId("Admin");
	      	if($system_data->userHasPermission($adminModuleId)) {
	      		?>
		      	<a href="?mod=<?php echo $adminModuleId; ?>&menu=admin" class="p-2 text-light">
					<i class="bi-gear-fill"></i>
		      	</a>
	      		<?php
	      	}
	      	?>
	      	<a href="?mod=<?php echo $system_data->getModuleId("Hilfe"); ?>" class="p-2 text-light">
				<i class="bi-question-circle"></i>
	      	</a>
      		<a href="?mod=<?php echo $system_data->getModuleId("Logout"); ?>" class="p-2 text-light">
				<i class="bi-box-arrow-right"></i>
			</a>
			<?php
			}
			?>
			<button class="navbar-toggler d-md-none collapsed"
				type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
				aria-controls="sidebarMenu" aria-expanded="false"
				aria-label="Toggle navigation">
				<i class="bi-list mobile-menu-toggler"></i>
			</button>
		</div>
	</div>

</header>
