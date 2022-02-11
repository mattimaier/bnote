<!-- Banner -->
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">

	<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">
		<img src="style/images/BNote_Logo_white_transparent_44px.png" alt="BNote" height="31px" id="bnote-logo" />
		<?php echo $system_data->getCompany(); ?>
	</a>
	
	<button class="navbar-toggler position-absolute d-md-none collapsed"
		type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
		aria-controls="sidebarMenu" aria-expanded="false"
		aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<?php	
	if ($system_data->isUserAuthenticated()) {
		?> 
		<div class="navbar-nav">
			<div class="nav-item text-nowrap">
				<a href="main.php?mod=" class="d-flex align-items-center text-white text-decoration-none">
	        		<i class="bi-person-circle"></i>
	      		</a>
	      		<a href="main.php?mod=logout" class="d-flex align-items-center text-white text-decoration-none">
					<i class="bi-box-arrow-right"></i>
				</a>
			</div>
		</div>
	<?php
	}
	?>
</header>
