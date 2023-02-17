<?php

/**
 * View for CMS module.
 * @author matti
 *
 */
class WebsiteView extends AbstractView {
	
	/**
	 * Build the controller for the CMS module.
	 * @param DefaultController $ctrl The default controller.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	function start() {
		$this->pageEditor();
	}
	
	function startOptions() {
		global $system_data;
		if($system_data->isInfopageFeatureEnabled()) {
			$n = new Link($this->modePrefix() . "infos", Lang::txt("WebsiteView_startOptions.copy_link"));
			$n->addIcon("copy_link");
			$n->write();
		}
	}
	
	private function pageEditor() {
		?>
		<div class="row">
		 <div class="col-md-2" id="website_pages">
		 	<div class="start_box_heading p-2 mb-1"><?php echo Lang::txt("WebsiteView_pageEditor.pages"); ?></div>
		<?php
		global $system_data;		
		
		// loop through pages and write them to the bar
		foreach($this->getData()->getPages() as $title => $file) {
			if(!$system_data->isInfopageFeatureEnabled() && $file == "infos") continue; 
			
			$active = "";
			if(isset($_GET["page"]) && $_GET["page"] == $file) {
				$active = "_active";
			}
			?>
			<div class="card mb-1">
				<a href="<?php echo $this->modePrefix() . "start&page=" . $file; ?>">
					<div class="card-body p-2 ps-3">
						<div class="card-title"><?php echo $title; ?></div>
					</div>
				</a>
			</div>
			<?php
		}
		?>
		  </div>
		  <div class="col-md-10" id="website_page_editor">
		  <?php 
		  if(isset($_GET["page"])) {
		  	$this->editPage($_GET["page"]);
		  }
		  else {
		  	Writing::p(Lang::txt("WebsiteView_pageEditor.select_page"));
		  }
		  ?>
		  </div>
		</div>
		<?php
	}
	
	function editPage($page) {
		// setup
		$filename = $this->getController()->getFilenameFromPage($page);
		if(!file_exists($filename)) {
			new BNoteError(Lang::txt("WebsiteView_editPage.filename_1") . $filename . Lang::txt("WebsiteView_editPage.filename_2"));
		}
		$html = file_get_contents($filename);
		$title = ucfirst($page) . Lang::txt("WebsiteView_editPage.filename_3");
		$saveHref = $this->modePrefix() . "save&page=" . $page;
		
		// show tinyMCE editor
		Writing::h3($title);
		echo "<form action=\"$saveHref\" method=\"POST\">\n";
		echo '<input type="submit" class="btn btn-warning mb-3" value='.Lang::txt("WebsiteView_editPage.submit").' />' . "\n";
		$editor = new HtmlEditor("html", $html);
		$editor->write();
		echo "</form>\n";
	}
	
	function save() {
		if(!isset($_GET["page"])) {
			new BNoteError(Lang::txt("WebsiteView_save.Error_1"));
		}
		if(isset($_POST["html"])) {
			$filename = $this->getController()->getFilenameFromPage($_GET["page"]);
			if(!file_put_contents($filename, $_POST["html"])) {
				new BNoteError(Lang::txt("WebsiteView_save.Error_2"));
			}
		}
		$this->start();
	}
	
	function saveOptions() {
		$this->startOptions();
	}
	
	function infos() {		
		// show available pages
		$infos = $this->getData()->getInfos();
		$table = new Table($infos);
		$table->setEdit("id");
		$table->changeMode("editInfo");
		$table->renameHeader("id", Lang::txt("WebsiteView_infos.id"));
		$table->renameHeader("createdon", Lang::txt("WebsiteView_infos.createdon"));
		$table->renameHeader("editedon", Lang::txt("WebsiteView_infos.editedon"));
		$table->renameHeader("title", Lang::txt("WebsiteView_infos.title"));
		$table->write();
	}
	
	function infosTitle() {
		echo Lang::txt("WebsiteView_infos.title");
	}
	
	function infosOptions() {
		$this->backToStart();
		
		$addlink = new Link($this->modePrefix() . "addInfo", Lang::txt("WebsiteView_infosOptions.addInfo"));
		$addlink->addIcon("plus");
		$addlink->write();
	}
	
	function addInfo() {
		$form = new Form(Lang::txt("WebsiteView_addInfo.Form"), $this->modePrefix() . "processAddInfo");
		$form->addElement(Lang::txt("WebsiteView_addInfo.title"), new Field("title", "", 7));
		$form->addElement(Lang::txt("WebsiteView_addInfo.page"), new Field("page", "", 98));
		$form->write();
	}
	
	function addInfoOptions() {
		$this->backToInfos();
	}
	
	function processAddInfo() {
		if(!$this->getData()->addInfo()) {
			new BNoteError(Lang::txt("WebsiteView_processAddInfo.error"));
		}
		else {
			new Message(Lang::txt("WebsiteView_processAddInfo.message_1"), Lang::txt("WebsiteView_processAddInfo.message_2"));
		}
	}
	
	function processAddInfoOptions() {
		$this->backToInfos();
	}
	
	function editInfo() {
		// get infopage
		$info = $this->getData()->getInfo($_GET["id"]);
		$author = $this->getData()->getUsername($info["author"]);
		$page_content = $this->getData()->getInfopage($_GET["id"]);
	
		// show edit information
		Writing::h2($info["title"]);
		
		// details
		$dv = new Dataview();
		$dv->addElement(Lang::txt("WebsiteView_editInfo.author"), $author);
		$dv->addElement(Lang::txt("WebsiteView_editInfo.createdOn"), Data::convertDateFromDb($info["createdOn"]));
		$dv->addElement(Lang::txt("WebsiteView_editInfo.editedOn"), Data::convertDateFromDb($info["editedOn"]));
		$dv->write();
		
		// show edit form
		Writing::h3(Lang::txt("WebsiteView_editInfo.processEditInfo"));
		echo "<form action=\"" . $this->modePrefix() . "processEditInfo&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		echo '<input class="btn btn-warning mb-3" type="submit" value='.Lang::txt("WebsiteView_editInfo.processEditInfo").' />' . "\n";
		
		$html = new HtmlEditor("page", $page_content);
		$html->write();
		
		echo "</form>\n";
	}
	
	function editInfoOptions() {
		$this->backToInfos();
		
		$delBtn = new Link($this->modePrefix() . "deleteInfo&id=" . $_GET["id"], Lang::txt("WebsiteView_editInfoOptions.deleteInfo"));
		$delBtn->addIcon("remove");
		$delBtn->write();
	}
	
	function processEditInfo() {
		if(!$this->getData()->editInfo($_GET["id"])) {
			new BNoteError(Lang::txt("WebsiteView_processEditInfo.error"));
		}
		else {
			echo '<p>' . Lang::txt("WebsiteView_processEditInfo.message") . '</p>';
		}
	}
	
	function processEditInfoOptions() {
		$this->backToInfos();
	}
	
	function deleteInfo() {
		$this->getData()->deleteInfo($_GET["id"]);
		echo '<p>' . Lang::txt("WebsiteView_deleteInfo.message") . '</p>';
	}
	
	function deleteInfoOptions() {
		$this->backToInfos();
	}
	
	function backToInfos() {
		$back = new Link($this->modePrefix() . "infos", Lang::txt("WebsiteView_backToInfos.message"));
		$back->addIcon("arrow_left");
		$back->write();
	}
}