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
		Writing::h1(Lang::txt("WebsiteView_construct.title"));
		
		Writing::p(Lang::txt("WebsiteView_construct.message"));
		
		$this->pageEditor();
	}
	
	function startOptions() {
		global $system_data;
		if($system_data->isGalleryFeatureEnabled()) {
			$g = new Link($this->modePrefix() . "gallery", Lang::txt("WebsiteView_startOptions.gallery"));
			$g->addIcon("gallery");
			$g->write();
		}
		if($system_data->isInfopageFeatureEnabled()) {
			if($system_data->isGalleryFeatureEnabled()) $this->buttonSpace();
			$n = new Link($this->modePrefix() . "infos", Lang::txt("WebsiteView_startOptions.copy_link"));
			$n->addIcon("copy_link");
			$n->write();
		}
	}
	
	private function pageEditor() {
		?>
		<table id="website_editor">
		 <tr>
		  <td id="website_pages">
		  	<div class="website_webpage_topic"><?php echo Lang::txt("WebsiteView_pageEditor.pages"); ?></div>
		<?php
		global $system_data;		
		// loop through pages and write them to the bar
		foreach($this->getData()->getPages() as $title => $file) {
			if(!$system_data->isGalleryFeatureEnabled() && $file == "galerie") continue;
			if(!$system_data->isInfopageFeatureEnabled() && $file == "infos") continue; 
			
			$active = "";
			if(isset($_GET["page"]) && $_GET["page"] == $file) {
				$active = "_active";
			}
			
			echo "<a class=\"webpage\" href=\"" . $this->modePrefix() . "start&page=" . $file . "\">";
			echo "<div class=\"website_webpage_item$active\">$title</div></a>\n";
		}
		?>
		  </td>
		  <td id="website_page_editor">
		  <?php 
		  if(isset($_GET["page"])) {
		  	$this->editPage($_GET["page"]);
		  }
		  else {
		  	Writing::p(Lang::txt("WebsiteView_pageEditor.select_page"));
		  }
		  ?>
		  </td>
		 </tr>
		</table>
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
		echo '<input type="submit" value='.Lang::txt("WebsiteView_editPage.submit").' />' . "\n";
		$this->verticalSpace();
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
		Writing::h2(Lang::txt("WebsiteView_infos.title"));
		Writing::p(Lang::txt("WebsiteView_infos.message"));
		
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
	
	function infosOptions() {
		$this->backToStart();
		$this->buttonSpace();
		
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
		echo '<input type="submit" value='.Lang::txt("WebsiteView_editInfo.processEditInfo").' />' . "\n";
		$this->verticalSpace();
		
		$html = new HtmlEditor("page", $page_content);
		$html->write();
		
		echo "</form>\n";
	}
	
	function editInfoOptions() {
		$this->backToInfos();
		$this->buttonSpace();
		
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
	
	function gallery() {
		Writing::h2(Lang::txt("WebsiteView_gallery.title"));
		
		// show galleries
		Writing::p(Lang::txt("WebsiteView_gallery.message"));
		$it = new ImageTable($this->getData()->getGalleries());
		$it->setPrefixPath($GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/");
		$it->setEditMode($this->galleryModePrefix("viewgallery"));
		$it->setIdBasedFilename();
		$it->setImageColumn("imagefile");
		$it->setImageIdColumn("imageid");
		$it->includeGalleryInPath();
		$it->write();
		
		// add gallery
		$form = new Form(Lang::txt("WebsiteView_gallery.Form"), $this->galleryModePrefix("addgallery"));
		$form->addElement(Lang::txt("WebsiteView_gallery.name"), new Field("name", "", FieldType::CHAR));
		$form->write();
	}
	
	function gallery_addgallery() {
		$this->getData()->createGallery($_POST);
		new Message(Lang::txt("WebsiteView_gallery_addgallery.message_1"), Lang::txt("WebsiteView_gallery_addgallery.message_2"));
		$this->backToGallery();
	}
	
	function gallery_viewgallery() {
		$this->checkID();
		
		// get infos
		$gallery = $this->getData()->getGallery($_GET["id"]);
		$gid = $gallery["id"]; // convenience
		$images = $this->getData()->getGalleryImages($_GET["id"]);
		
		// write stuff
		Writing::h2(Lang::txt("WebsiteView_gallery_viewgallery.title") . $gallery["name"]);
		Writing::p(Lang::txt("WebsiteView_gallery_viewgallery.message"));		
		
		// show images
		$it = new ImageTable($images);
		$it->setPrefixPath($GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/$gid/");
		$it->setEditMode($this->galleryModePrefix("viewimage"));
		$it->setImageColumn("filename");
		$it->setIdBasedFilename();
		$it->write();
	}
	
	function galleryOptions() {
		if(isset($_GET["sub"])) {
			switch ($_GET["sub"]) {
				case "viewgallery": 
					$this->backToGallery();
					$this->buttonSpace();
					$gid = $_GET["id"]; // convenience
					
					// show options: add image, edit, delete
					$add = new Link($this->galleryModePrefix("addImageForm", "&id=$gid"), Lang::txt("WebsiteView_galleryOptions.addImageForm"));
					$add->addIcon("plus");
					$add->write();
					$this->buttonSpace();
					
					$edit = new Link($this->galleryModePrefix("editgallery", "&id=$gid"), Lang::txt("WebsiteView_galleryOptions.editgallery"));
					$edit->addIcon("edit");
					$edit->write();
					$this->buttonSpace();
					
					$del = new Link($this->galleryModePrefix("deletegallery", "&id=$gid"), Lang::txt("WebsiteView_galleryOptions.deletegallery"));
					$del->addIcon("remove");
					$del->write();
					break;
				case "addImageForm":
					$this->backToGalleryView($_GET["id"]);
					break;
				case "editgallery":
					$this->backToGalleryView($_GET["id"]);
					break;
				case "editgalleryprocess":
					$this->backToGalleryView($_GET["id"]);
					break;
				case "addimage":
					$this->backToGalleryView($_GET["id"]);
					break;
				case "viewimage":
					$img = $this->getData()->getImage($_GET["id"]);
					$this->backToGalleryView($img["gallery"]);
					$this->buttonSpace();
					
					// show options
					$std = new Link($this->galleryModePrefix("setimageasgallerydefault", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.setimageasgallerydefault"));
					$std->addIcon("checkmark");
					$std->write();
					$this->buttonSpace();
					$edit = new Link($this->galleryModePrefix("editimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.editimage"));
					$edit->addIcon("edit");
					$edit->write();
					$this->buttonSpace();
					$del = new Link($this->galleryModePrefix("deleteimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.deleteimage"));
					$del->addIcon("remove");
					$del->write();
					$this->verticalSpace();
					break;
				case "setimageasgallerydefault":
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.viewimage"));
					$back->addIcon("arrow_left");
					$back->write();
					break;
				case "editimage":
					$img = $this->getData()->getImage($_GET["id"]);
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.viewimage"));
					$back->addIcon("arrow_left");
					$back->write();
					break;
				case "editimageprocess":
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_galleryOptions.viewimage"));
					$back->addIcon("arrow_left");
					$back->write();
					break;
				case "deleteimage":
					// none;
					break;
				case "deleteimageprocess":
					$img = $this->getData()->getImage($_GET["id"]);
					$this->backToGalleryView($img["gallery"]);
					break;
				default: 
					$this->backToStart(); break;
			}
		}
		else {
			$this->backToStart();
		}
	}
	
	function gallery_addImageForm() {
		// add image
		$form = new Form(Lang::txt("WebsiteView_gallery_addImageForm.form"), $this->galleryModePrefix("addimage", "&id=" . $_GET["id"]));
		$form->setMultipart();
		$form->addElement(Lang::txt("WebsiteView_gallery_addImageForm.name"), new Field("name", "", FieldType::CHAR));
		$form->addElement(Lang::txt("WebsiteView_gallery_addImageForm.description"), new Field("description", "", FieldType::TEXT));
		$form->addElement(Lang::txt("WebsiteView_gallery_addImageForm.file"), new Field("file", "", FieldType::FILE));
		$form->write();
	}
	
	function gallery_editgallery() {
		$this->checkID();
		
		// collect data
		$gallery = $this->getData()->getGallery($_GET["id"]);
		$gid = $gallery["id"]; // convenience
		
		// show form		
		$form = new Form(Lang::txt("WebsiteView_gallery_editgallery.editgalleryprocess"), $this->galleryModePrefix("editgalleryprocess", "&id=$gid"));
		$form->addElement(Lang::txt("WebsiteView_gallery_editgallery.name"), new Field("name", $gallery["name"], FieldType::CHAR));
		$form->write();
	}
	
	function gallery_editgalleryprocess() {
		$this->checkID();
		$this->getData()->editGallery($_GET["id"], $_POST);
		
		// show success
		new Message(Lang::txt("WebsiteView_gallery_editgalleryprocess.message_1"), Lang::txt("WebsiteView_gallery_editgalleryprocess.message_2"));
	}
	
	/**
	 * Confirm removal
	 */
	function gallery_deletegallery() {
		$this->checkID();
		$g = $this->getData()->getGallery($_GET["id"]);
		$name = $g["name"]; //convenience
		$m = Lang::txt("WebsiteView_gallery_deletegallery.message_1") . $name . Lang::txt("WebsiteView_gallery_deletegallery.message_2");
		$m .= "<br /><strong>";
		$m .= Lang::txt("WebsiteView_gallery_deletegallery.message_3");
		$m .= "</strong>";
		new Message(Lang::txt("WebsiteView_gallery_deletegallery.message_4") . $name . Lang::txt("WebsiteView_gallery_deletegallery.message_5"), $m);
		
		// show options
		$yes = new Link($this->galleryModePrefix("deletegalleryprocess", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_gallery_deletegallery.deletegalleryprocess"));
		$yes->write();
		
		$this->buttonSpace();
		$this->backToGalleryView($_GET["id"]);
	}
	
	/**
	 * Delete Gallery, its folders and db entries
	 */
	function gallery_deletegalleryprocess() {
		$this->checkID();
		$this->getData()->deleteGallery($_GET["id"]);
		
		// show success
		new Message(Lang::txt("WebsiteView_gallery_deletegalleryprocess.message_1"), Lang::txt("WebsiteView_gallery_deletegalleryprocess.message_2"));
		$this->backToGallery();
	}
	
	function gallery_addimage() {
		$this->checkID();
		$this->getData()->addImageToGallery($_GET["id"]);
		
		new Message(Lang::txt("WebsiteView_gallery_addimage.message_1"), Lang::txt("WebsiteView_gallery_addimage.message_2"));
	}
	
	function gallery_viewimage() {
		$this->checkID();
		// get data
		$img = $this->getData()->getImage($_GET["id"]);
		
		// Show name
		Writing::h2($img["name"]);
		
		// show image
		$path = $this->getController()->getGalleryDir();
		$imgtype = substr($img["filename"], strrpos($img["filename"], ".")); // e.g. ".jpg"
		$src = $path . $img["gallery"] . "/" . $img["id"] . $imgtype; 
		Writing::img($src, $img["name"]);
		Writing::p($img["description"]);
	}
	
	function gallery_editimage() {
		$this->checkID();
		// get data
		$img = $this->getData()->getImage($_GET["id"]);
		
		// show form
		$form = new Form(Lang::txt("WebsiteView_gallery_editimage.Form"), $this->galleryModePrefix("editimageprocess", "&id=" . $_GET["id"]));
		$form->addElement(Lang::txt("WebsiteView_gallery_editimage.name"), new Field("name", $img["name"], FieldType::CHAR));
		$form->addElement(Lang::txt("WebsiteView_gallery_editimage.description"), new Field("description", $img["description"], FieldType::TEXT));
		$form->write();
	}
	
	function gallery_editimageprocess() {
		$this->checkID();
		$this->getData()->editImage($_GET["id"], $_POST);
		
		// show success
		new Message(Lang::txt("WebsiteView_gallery_editimageprocess.message_1"), Lang::txt("WebsiteView_gallery_editimageprocess.message_2"));
	}
	
	function gallery_deleteimage() {
		$this->checkID();
		new Message(Lang::txt("WebsiteView_gallery_deleteimage.message_1"), Lang::txt("WebsiteView_gallery_deleteimage.message_2"));
		$yes = new Link($this->galleryModePrefix("deleteimageprocess", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_gallery_deleteimage.deleteimageprocess"));
		$yes->write();
		$this->buttonSpace();
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), Lang::txt("WebsiteView_gallery_deleteimage.viewimage"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function gallery_deleteimageprocess() {
		$this->checkID();
		$img = $this->getData()->getImage($_GET["id"]);
		$this->getData()->deleteImage($_GET["id"]);
		
		// show success
		new Message(Lang::txt("WebsiteView_gallery_deleteimageprocess.message_1"), Lang::txt("WebsiteView_gallery_deleteimageprocess.message_2"));
	}
	
	function gallery_setimageasgallerydefault() {
		$this->checkID();
		$this->getData()->setImageAsGalleryDefault($_GET["id"]);
		
		// show success
		new Message(Lang::txt("WebsiteView_gallery_setimageasgallerydefault.message_1"), Lang::txt("WebsiteView_gallery_setimageasgallerydefault.message_2"));
	}
	
	private function backToGallery() {
		$back = new Link($this->modePrefix() . "gallery", Lang::txt("WebsiteView_backToGallery.gallery"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	private function backToGalleryView($id) {
		$back = new Link($this->galleryModePrefix("viewgallery", "&id=$id"), Lang::txt("WebsiteView_backToGalleryView.viewgallery"));
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	private function galleryModePrefix($sub, $addition = "") {
		return $this->modePrefix() . "gallery&sub=$sub" . $addition;
	}
}

?>
