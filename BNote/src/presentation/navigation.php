<?php 
/**
 * Only show navigation when not logged in.
 */
if(isset($_GET["mod"]) && is_numeric($_GET["mod"])) {
?>

<!-- Navigation -->
<div id="navigation">

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
		$caption = Lang::txt("mod_" . $system_data->getModuleTitle($id));
		
		echo "<a class=\"navi\" href=\"?mod=$id\"><div class=\"navi_item$selected\">";
		echo "<img src=\"" . $GLOBALS["DIR_ICONS"] . $tecName . ".png\" alt=\"$tecName\" height=\"14px\" class=\"navi_item_icon\" />";
		echo $caption;
		echo "</div></a>\n";
	}


	?>
</div>

<?php 
}
?>