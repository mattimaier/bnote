<?php 
if(isset($_GET["mod"]) && is_numeric($_GET["mod"])) {
	?>
	<!-- Navigation -->
	<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
		<div class="position-sticky pt-3">
			<ul class="nav flex-column">
			
			<?php
			$modarr = $system_data->getModuleArray();
		
			// render menu
			foreach($modarr as $id => $name) {
		
				// don't show module if user doesn't have permission
				if(!$system_data->loginMode() && !$system_data->userHasPermission($id)) continue;
		
				if($id == $system_data->getModuleId()) {
					// current Module
					$selected = "_selected";
				}
				else $selected = "";
		
				$tecName = strtolower($name);
				$caption = Lang::txt("navigation_" . $system_data->getModuleTitle($id));
				?>
				<li class="nav-item">
		        	<a class="nav-link" href="?mod=<?php echo $id; ?>">
		        		<i class="bi-app"></i>
		          		<?php echo $caption; ?>
		        	</a>
		      	</li>
				<?php
			}
			?>
			</ul>
			
			<div class="badge bg-secondary text-wrap">
				BNote <?php echo $GLOBALS["system_data"]->getVersion(); ?>
			</div>
		</div>
	</nav>	
	<?php 
}
?>