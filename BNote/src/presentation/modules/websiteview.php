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
		Writing::h1("Website Inhalte");
		
		Writing::p("Klicke auf eine Seite um deren Inhalte zu bearbeiten.");
		
		$this->pageEditor();
	}
	
	function startOptions() {
		global $system_data;
		if($system_data->isGalleryFeatureEnabled()) {
			$g = new Link($this->modePrefix() . "gallery", "Galerien bearbeiten");
			$g->addIcon("gallery");
			$g->write();
		}
		if($system_data->isInfopageFeatureEnabled()) {
			if($system_data->isGalleryFeatureEnabled()) $this->buttonSpace();
			$n = new Link($this->modePrefix() . "infos", "Sonderseiten");
			$n->addIcon("copy_link");
			$n->write();
		}
	}
	
	private function pageEditor() {
		?>
		<table id="website_editor">
		 <tr>
		  <td id="website_pages">
		  	<div class="website_webpage_topic">Seiten</div>
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
		  	Writing::p("Bitte wähle eine Seite zum bearbeiten aus.");
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
			new BNoteError("Die HTML-Datei " . $filename . " existiert nicht.");
		}
		$html = file_get_contents($filename);
		$title = ucfirst($page) . " bearbeiten";
		$saveHref = $this->modePrefix() . "save&page=" . $page;
		
		// show tinyMCE editor
		Writing::h3($title);
		echo "<form action=\"$saveHref\" method=\"POST\">\n";
		echo '<input type="submit" value="speichern" />' . "\n";
		$this->verticalSpace();
		$editor = new HtmlEditor("html", $html);
		$editor->write();
		echo "</form>\n";
	}
	
	function save() {
		if(!isset($_GET["page"])) {
			new BNoteError("Bitte wähle eine Seite zum bearbeiten aus!");
		}
		if(isset($_POST["html"])) {
			$filename = $this->getController()->getFilenameFromPage($_GET["page"]);
			if(!file_put_contents($filename, $_POST["html"])) {
				new BNoteError("Die Seite konnte nicht gespeichert werden.");
			}
		}
		$this->start();
	}
	
	function saveOptions() {
		$this->startOptions();
	}
	
	function infos() {
		Writing::h2("Sonderseiten");
		Writing::p("Klicke auf eine Seite um diese zu bearbeiten.");
		
		// show available pages
		$infos = $this->getData()->getInfos();
		$table = new Table($infos);
		$table->setEdit("id");
		$table->changeMode("editInfo");
		$table->renameHeader("id", "ID");
		$table->renameHeader("createdon", "Erstellt am");
		$table->renameHeader("editedon", "Zuletzt bearbeitet am");
		$table->renameHeader("title", "Überschrift");
		$table->write();
	}
	
	function infosOptions() {
		$this->backToStart();
		$this->buttonSpace();
		
		$addlink = new Link($this->modePrefix() . "addInfo", "Seite hinzufügen");
		$addlink->addIcon("plus");
		$addlink->write();
	}
	
	function addInfo() {
		$form = new Form("Informationsseite hinzufügen", $this->modePrefix() . "processAddInfo");
		$form->addElement("Titel", new Field("title", "", 7));
		$form->addElement("Text", new Field("page", "", 98));
		$form->write();
	}
	
	function addInfoOptions() {
		$this->backToInfos();
	}
	
	function processAddInfo() {
		if(!$this->getData()->addInfo()) {
			new BNoteError("Die Informationsseite konnte nicht gespeichert werden.");
		}
		else {
			new Message("Seite gespeichert", "Die Seite wurde erfolgreich gespeichert.");
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
		$dv->addElement("Autor", $author);
		$dv->addElement("Erstellt am:", Data::convertDateFromDb($info["createdOn"]));
		$dv->addElement("Zuletzt bearbeitet am:", Data::convertDateFromDb($info["editedOn"]));
		$dv->write();
		
		// show edit form
		Writing::h3("Seiteninhalt bearbeiten");
		echo "<form action=\"" . $this->modePrefix() . "processEditInfo&id=" . $_GET["id"] . "\" method=\"POST\">\n";
		echo "<input type=\"submit\" value=\"speichern\" />\n";
		$this->verticalSpace();
		
		$html = new HtmlEditor("page", $page_content);
		$html->write();
		
		echo "</form>\n";
	}
	
	function editInfoOptions() {
		$this->backToInfos();
		$this->buttonSpace();
		
		$delBtn = new Link($this->modePrefix() . "deleteInfo&id=" . $_GET["id"], "Seite löschen");
		$delBtn->addIcon("remove");
		$delBtn->write();
	}
	
	function processEditInfo() {
		if(!$this->getData()->editInfo($_GET["id"])) {
			new BNoteError("Die Informationsseite konnte nicht gespeichert werden.");
		}
		else {
			echo '<p>Die Seite wurde erfolgreich gespeichert.</p>';
		}
	}
	
	function processEditInfoOptions() {
		$this->backToInfos();
	}
	
	function deleteInfo() {
		$this->getData()->deleteInfo($_GET["id"]);
		echo '<p>Die Seite wurde erfolgreich gelöscht.</p>';
	}
	
	function deleteInfoOptions() {
		$this->backToInfos();
	}
	
	function backToInfos() {
		$back = new Link($this->modePrefix() . "infos", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function gallery() {
		Writing::h2("Galerien");
		
		// show galleries
		Writing::p("Um eine Galerie zu bearbeiten, klicke auf diese.");
		$it = new ImageTable($this->getData()->getGalleries());
		$it->setPrefixPath($GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/");
		$it->setEditMode($this->galleryModePrefix("viewgallery"));
		$it->setIdBasedFilename();
		$it->setImageColumn("imagefile");
		$it->setImageIdColumn("imageid");
		$it->includeGalleryInPath();
		$it->write();
		
		// add gallery
		$form = new Form("Galerie hinzufügen", $this->galleryModePrefix("addgallery"));
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$form->write();
	}
	
	function gallery_addgallery() {
		$this->getData()->createGallery($_POST);
		new Message("Galerie erstellt", "Die Galerie wurde erfolgreiche erstellt.");
		$this->backToGallery();
	}
	
	function gallery_viewgallery() {
		$this->checkID();
		
		// get infos
		$gallery = $this->getData()->getGallery($_GET["id"]);
		$gid = $gallery["id"]; // convenience
		$images = $this->getData()->getGalleryImages($_GET["id"]);
		
		// write stuff
		Writing::h2("Galerie " . $gallery["name"]);
		Writing::p("Um ein Bild zu bearbeiten, klicke auf dieses.");		
		
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
					$add = new Link($this->galleryModePrefix("addImageForm", "&id=$gid"), "Bild hinzufügen");
					$add->addIcon("plus");
					$add->write();
					$this->buttonSpace();
					
					$edit = new Link($this->galleryModePrefix("editgallery", "&id=$gid"), "Galeriename ändern");
					$edit->addIcon("edit");
					$edit->write();
					$this->buttonSpace();
					
					$del = new Link($this->galleryModePrefix("deletegallery", "&id=$gid"), "Galerie löschen");
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
					$std = new Link($this->galleryModePrefix("setimageasgallerydefault", "&id=" . $_GET["id"]), "als Vorschaubild setzen");
					$std->addIcon("checkmark");
					$std->write();
					$this->buttonSpace();
					$edit = new Link($this->galleryModePrefix("editimage", "&id=" . $_GET["id"]), "Bildbeschreibung ändern");
					$edit->addIcon("edit");
					$edit->write();
					$this->buttonSpace();
					$del = new Link($this->galleryModePrefix("deleteimage", "&id=" . $_GET["id"]), "Bild löschen");
					$del->addIcon("remove");
					$del->write();
					$this->verticalSpace();
					break;
				case "setimageasgallerydefault":
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zurück");
					$back->addIcon("arrow_left");
					$back->write();
					break;
				case "editimage":
					$img = $this->getData()->getImage($_GET["id"]);
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zurück");
					$back->addIcon("arrow_left");
					$back->write();
					break;
				case "editimageprocess":
					$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zurück");
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
		$form = new Form("Bild hinzufügen", $this->galleryModePrefix("addimage", "&id=" . $_GET["id"]));
		$form->setMultipart();
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$form->addElement("Beschreibung", new Field("description", "", FieldType::TEXT));
		$form->addElement("Bild", new Field("file", "", FieldType::FILE));
		$form->write();
	}
	
	function gallery_editgallery() {
		$this->checkID();
		
		// collect data
		$gallery = $this->getData()->getGallery($_GET["id"]);
		$gid = $gallery["id"]; // convenience
		
		// show form		
		$form = new Form("Galerienamen ändern", $this->galleryModePrefix("editgalleryprocess", "&id=$gid"));
		$form->addElement("Name", new Field("name", $gallery["name"], FieldType::CHAR));
		$form->write();
	}
	
	function gallery_editgalleryprocess() {
		$this->checkID();
		$this->getData()->editGallery($_GET["id"], $_POST);
		
		// show success
		new Message("Galerie geändert", "Die Galerie wurde erfolgreich geändert.");
	}
	
	/**
	 * Confirm removal
	 */
	function gallery_deletegallery() {
		$this->checkID();
		$g = $this->getData()->getGallery($_GET["id"]);
		$name = $g["name"]; //convenience
		$m = "Bist du sicher, dass du die Galerie $name löschen möchtest?";
		$m .= "<br /><strong>";
		$m .= "Das löschen einer Galerie löscht alle Bilder und Daten zur Galerie!";
		$m .= "</strong>";
		new Message("Galerie $name löschen", $m);
		
		// show options
		$yes = new Link($this->galleryModePrefix("deletegalleryprocess", "&id=" . $_GET["id"]), "GALERIE LÖSCHEN");
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
		new Message("Galerie gelöscht", "Die Galerie wurde erfolgreich gelöscht.");
		$this->backToGallery();
	}
	
	function gallery_addimage() {
		$this->checkID();
		$this->getData()->addImageToGallery($_GET["id"]);
		
		new Message("Bild hinzugeügt", "Das Bild wurde erfolgreiche zur Galerie hinzugefügt.");
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
		$form = new Form("Bild ändern", $this->galleryModePrefix("editimageprocess", "&id=" . $_GET["id"]));
		$form->addElement("Name", new Field("name", $img["name"], FieldType::CHAR));
		$form->addElement("Beschreibung", new Field("description", $img["description"], FieldType::TEXT));
		$form->write();
	}
	
	function gallery_editimageprocess() {
		$this->checkID();
		$this->getData()->editImage($_GET["id"], $_POST);
		
		// show success
		new Message("Bild geändert", "Die Beschreibungen wurden geändert.");
	}
	
	function gallery_deleteimage() {
		$this->checkID();
		new Message("Bild wirklich löschen?", "Wollen Sie das Bild wirklich löschen?");
		$yes = new Link($this->galleryModePrefix("deleteimageprocess", "&id=" . $_GET["id"]), "BILD LöSCHEN");
		$yes->write();
		$this->buttonSpace();
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	function gallery_deleteimageprocess() {
		$this->checkID();
		$img = $this->getData()->getImage($_GET["id"]);
		$this->getData()->deleteImage($_GET["id"]);
		
		// show success
		new Message("Bild gelöscht", "Das Bild wurde gelöscht.");
	}
	
	function gallery_setimageasgallerydefault() {
		$this->checkID();
		$this->getData()->setImageAsGalleryDefault($_GET["id"]);
		
		// show success
		new Message("Bild als Vorschaubild gespeichert", "Das Bild wurde als Vorschau für diese Galerie gespeichert.");
	}
	
	private function backToGallery() {
		$back = new Link($this->modePrefix() . "gallery", "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	private function backToGalleryView($id) {
		$back = new Link($this->galleryModePrefix("viewgallery", "&id=$id"), "Zurück");
		$back->addIcon("arrow_left");
		$back->write();
	}
	
	private function galleryModePrefix($sub, $addition = "") {
		return $this->modePrefix() . "gallery&sub=$sub" . $addition;
	}
}

?>
