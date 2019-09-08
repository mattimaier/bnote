<?php
if (isset($_GET["mod"]) && is_numeric($_GET["mod"])) {
    ?>
    <!-- Sidebar -->
    <nav class="nav white lighten-4 .z-depth-1" id="sidebar">
        <div class="list-group flex-column" id="sidebar-list">
            <?php
            $modarr = $system_data->getModuleArray();

            // render menu
            foreach ($modarr as $id => $name) {

                // don't show module if user doesn't have permission
                if (!$system_data->loginMode() && !$system_data->userHasPermission($id)) {
                    continue;
                }

                if ($id == $system_data->getModuleId()) {
                    // current Module
                    $selected = "active primary-color";
                } else {
                    $selected = "";
                }

                $tecName = strtolower($name);
                $caption = Lang::txt("navigation_" . $system_data->getModuleTitle($id));
                ?>
                <a class="list-group-item list-group-item-action <?php echo $selected; ?>"
                   href="?mod=<?php echo $id; ?>">
                    <img src="<?php echo $GLOBALS["DIR_ICONS"] . $tecName . ".png"; ?>" alt="<?php echo $tecName ?>"
                         height="16px" class="navi_item_icon<?php echo $selected; ?>"/>
                    <span class="navi_item_caption<?php echo $selected; ?>"><?php echo $caption; ?></span>
                </a>
                <?php
            }

            ?>
            </div>

    </nav>

    <!-- Sidebar overlay -->
    <div id="sidebar-overlay" class="active">
    </div>
    <?php
}
?>