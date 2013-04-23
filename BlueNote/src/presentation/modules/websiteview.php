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
		
		global $system_data;
		if($system_data->isGalleryFeatureEnabled()) {
			$g = new Link($this->modePrefix() . "gallery", "Galerien bearbeiten");
			$g->write();
		}
		if($system_data->isInfopageFeatureEnabled()) {
			if($system_data->isGalleryFeatureEnabled()) $this->buttonSpace();
			$n = new Link($this->modePrefix() . "infos", "Sonderseiten");
			$n->write();
		}
		
		$this->pageEditor();
	}
	
	private function pageEditor() {
		?>
		<table id="website_editor">
		 <tr>
		  <td id="website_pages">
		<?php
		global $system_data;		
		// loop through pages and write them to the bar
		foreach($this->getData()->getPages() as $title => $file) {
			if(!$system_data->isGalleryFeatureEnabled() && $file == "galerie") continue;
			if(!$system_data->isInfopageFeatureEnabled() && $file == "infos") continue; 
			?>
			<div class="website_webpage_item"><a class="webpage" href="<?php
				echo $this->modePrefix() . "start&page=" . $file; ?>">
			<?php echo $title; ?></a></div>
			<?php
		}
		?>
		  </td>
		  <td>
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
		// show tinyMCE editor
		$form = new Form("Seite <i>" . ucfirst($page) . "</i> bearbeiten",
						$this->modePrefix() . "save&page=" . $page);
		$filename = $this->getController()->getFilenameFromPage($page);
		$html = file_get_contents($filename);
		$form->addElement("", new HtmlEditor("html", $html));
		$form->changeSubmitButton("Speichern");
		$form->write();
	}
	
	function save() {
		if(!isset($_GET["page"])) {
			new Error("Bitte w&auml;hle eine Seite zum bearbeiten aus!");
		}
		if(isset($_POST["html"])) {
			$filename = $this->getController()->getFilenameFromPage($_GET["page"]);
			if(!file_put_contents($filename, $_POST["html"])) {
				new Error("Die Seite konnte nicht gespeichert werden.");
			}
		}
		$this->start();
	}
	
	function infos() {
		Writing::h2("Sonderseiten");
		Writing::p("Klicke auf eine Seite um diese zu bearbeiten.");
		
		// options
		$addlink = new Link($this->modePrefix() . "addInfo", "Seite hinzuf&uuml;gen");
		$addlink->addIcon("add");
		$addlink->write();
		
		// show available pages
		$infos = $this->getData()->getInfos();
		$table = new Table($infos);
		$table->setEdit("id");
		$table->changeMode("editInfo");
		$table->renameHeader("id", "ID");
		$table->renameHeader("createdon", "Erstellt am");
		$table->renameHeader("editedon", "Zuletzt bearbeitet am");
		$table->renameHeader("title", "&Uuml;berschrift");
		$table->write();
		
		// back
		$this->verticalSpace();
		$this->backToStart();
	}
	
	function addInfo() {
		$form = new Form("Informationsseite hinzuf&uuml;gen", $this->modePrefix() . "processAddInfo");
		$form->addElement("Titel", new Field("title", "", 7));
		$form->addElement("Text", new Field("page", "", 98));
		$form->write();
		
		// back
		$this->backToInfos();
	}
	
	function processAddInfo() {
		if(!$this->getData()->addInfo()) {
			new Error("Die Informationsseite konnte nicht gespeichert werden.");
		}
		else {
			echo '<p>Die Seite wurde erfolgreich gespeichert.</p>';
		}
		
		// back
		$this->backToInfos();
	}
	
	function editInfo() {
		// get infopage
		$info = $this->getData()->getInfo($_GET["id"]);
		$author = $this->getData()->getUsername($info["author"]);
		$page_content = $this->getData()->getInfopage($_GET["id"]);
	
		// show edit information
		Writing::h2($info["title"]);
		
		$dv = new Dataview();
		$dv->addElement("Autor", $author);
		$dv->addElement("Erstellt am:", Data::convertDateFromDb($info["createdOn"]));
		$dv->addElement("Zuletzt bearbeitet am:", Data::convertDateFromDb($info["editedOn"]));
		$dv->write();
		
		// show edit form
		$form = new Form("Seiteninhalt bearbeiten", $this->modePrefix() . "processEditInfo&id=" . $_GET["id"]);
		$form->addElement("", new HtmlEditor("page", $page_content));
		$form->changeSubmitButton("speichern");
		$form->write();
	
		// show delete button
		$delBtn = new Link($this->modePrefix() . "deleteInfo&id=" . $_GET["id"], "Seite l&ouml;schen");
		$delBtn->write();
		
		// back
		$this->buttonSpace();
		$this->backToInfos();
	}
	
	function processEditInfo() {
		if(!$this->getData()->editInfo($_GET["id"])) {
			new Error("Die Informationsseite konnte nicht gespeichert werden.");
		}
		else {
			echo '<p>Die Seite wurde erfolgreich gespeichert.</p>';
		}
		
		// back
		$this->backToInfos();
	}
	
	function deleteInfo() {
		$this->getData()->deleteInfo($_GET["id"]);
		echo '<p>Die Seite wurde erfolgreich gel&ouml;scht.</p>';
		
		// back
		$this->backToInfos();
	}
	
	function backToInfos() {
		$this->verticalSpace();
		$back = new Link($this->modePrefix() . "infos", "Zur&uuml;ck");
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
		$form = new Form("Galerie hinzuf&uuml;gen", $this->galleryModePrefix("addgallery"));
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$form->write();
		
		// show backButton
		$this->backToStart();
		$this->verticalSpace();
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
		$it = new ImageTable($images);
		$it->setPrefixPath($GLOBALS["DATA_PATHS"]["gallery"] . "thumbs/$gid/");
		$it->setEditMode($this->galleryModePrefix("viewimage"));
		$it->setImageColumn("filename");
		$it->setIdBasedFilename();
		$it->write();
		$this->verticalSpace();
		
		// add image
		$form = new Form("Bild hinzuf&uuml;gen", $this->galleryModePrefix("addimage", "&id=$gid"));
		$form->setMultipart();
		$form->addElement("Name", new Field("name", "", FieldType::CHAR));
		$form->addElement("Beschreibung", new Field("description", "", FieldType::TEXT));
		$form->addElement("Bild", new Field("file", "", FieldType::FILE));
		$form->write();
		
		// show other options: edit, delete
		$edit = new Link($this->galleryModePrefix("editgallery", "&id=$gid"), "Galeriename &auml;ndern");
		$edit->write();
		$this->buttonSpace();
		$del = new Link($this->galleryModePrefix("deletegallery", "&id=$gid"), "Galerie l&ouml;schen");
		$del->write();
		$this->buttonSpace();
		
		// back button
		$this->backToGallery();
		$this->verticalSpace();
	}
	
	function gallery_editgallery() {
		$this->checkID();
		
		// collect data
		$gallery = $this->getData()->getGallery($_GET["id"]);
		$gid = $gallery["id"]; // convenience
		
		// show form		
		$form = new Form("Galerienamen &auml;ndern", $this->galleryModePrefix("editgalleryprocess", "&id=$gid"));
		$form->addElement("Name", new Field("name", $gallery["name"], FieldType::CHAR));
		$form->write();
		$this->verticalSpace();
		
		// back button
		$this->backToGalleryView($gid);
	}
	
	function gallery_editgalleryprocess() {
		$this->checkID();
		$this->getData()->editGallery($_GET["id"], $_POST);
		
		// show success
		new Message("Galerie ge&auml;ndert", "Die Galerie wurde erfolgreich ge&auml;ndert.");
		$this->backToGalleryView($_GET["id"]);
	}
	
	/**
	 * Confirm removal
	 */
	function gallery_deletegallery() {
		$this->checkID();
		$g = $this->getData()->getGallery($_GET["id"]);
		$name = $g["name"]; //convenience
		$m = "Bist du sicher, dass du die Galerie $name l&ouml;schen m&ouml;chtest?";
		$m .= "<br /><strong>";
		$m .= "Das l&ouml;schen einer Galerie l&ouml;scht alle Bilder und Daten zur Galerie!";
		$m .= "</strong>";
		new Message("Galerie $name l&ouml;schen", $m);
		
		// show options
		$yes = new Link($this->galleryModePrefix("deletegalleryprocess", "&id=" . $_GET["id"]), "GALERIE L&Ouml;SCHEN");
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
		new Message("Galerie gel&ouml;scht", "Die Galerie wurde erfolgreich gel&ouml;scht.");
		$this->backToGallery();
	}
	
	function gallery_addimage() {
		$this->checkID();
		$this->getData()->addImageToGallery($_GET["id"]);
		
		new Message("Bild hinzuge&uuml;gt", "Das Bild wurde erfolgreiche zur Galerie hinzugef&uuml;gt.");
		$this->backToGalleryView($_GET["id"]);
	}
	
	function gallery_viewimage() {
		$this->checkID();
		// get data
		$img = $this->getData()->getImage($_GET["id"]);
		
		// Show name
		Writing::h2($img["name"]);
		
		// show options
		$std = new Link($this->galleryModePrefix("setimageasgallerydefault", "&id=" . $_GET["id"]), "als Vorschaubild setzen");
		$std->write();
		$this->buttonSpace();
		$edit = new Link($this->galleryModePrefix("editimage", "&id=" . $_GET["id"]), "Bildbeschreibung &auml;ndern");
		$edit->write();
		$this->buttonSpace();
		$del = new Link($this->galleryModePrefix("deleteimage", "&id=" . $_GET["id"]), "Bild l&ouml;schen");
		$del->write();
		$this->verticalSpace();
		
		// show image
		$path = $this->getController()->getGalleryDir();
		$imgtype = substr($img["filename"], strrpos($img["filename"], ".")); // e.g. ".jpg"
		$src = $path . $img["gallery"] . "/" . $img["id"] . $imgtype; 
		Writing::img($src, $img["name"]);
		Writing::p($img["description"]);
		
		// show back button
		$this->verticalSpace();
		$this->backToGalleryView($img["gallery"]);
		$this->verticalSpace();
	}
	
	function gallery_editimage() {
		$this->checkID();
		// get data
		$img = $this->getData()->getImage($_GET["id"]);
		
		// show form
		$form = new Form("Bild &auml;ndern", $this->galleryModePrefix("editimageprocess", "&id=" . $_GET["id"]));
		$form->addElement("Name", new Field("name", $img["name"], FieldType::CHAR));
		$form->addElement("Beschreibung", new Field("description", $img["description"], FieldType::TEXT));
		$form->write();
		
		// show back button
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zur&uuml;ck");
		$back->write();
	}
	
	function gallery_editimageprocess() {
		$this->checkID();
		$this->getData()->editImage($_GET["id"], $_POST);
		
		// show success
		new Message("Bild ge&auml;ndert", "Die Beschreibungen wurden ge&auml;ndert.");
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zur&uuml;ck");
		$back->write();
	}
	
	function gallery_deleteimage() {
		$this->checkID();
		new Message("Bild wirklich l&ouml;schen?", "Wollen Sie das Bild wirklich l&ouml;schen?");
		$yes = new Link($this->galleryModePrefix("deleteimageprocess", "&id=" . $_GET["id"]), "BILD L&Ouml;SCHEN");
		$yes->write();
		$this->buttonSpace();
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zur&uuml;ck");
		$back->write();
	}
	
	function gallery_deleteimageprocess() {
		$this->checkID();
		$img = $this->getData()->getImage($_GET["id"]);
		$this->getData()->deleteImage($_GET["id"]);
		
		// show success
		new Message("Bild gel&ouml;scht", "Das Bild wurde gel&ouml;scht.");
		$this->backToGalleryView($img["gallery"]);
	}
	
	function gallery_setimageasgallerydefault() {
		$this->checkID();
		$this->getData()->setImageAsGalleryDefault($_GET["id"]);
		
		// show success
		new Message("Bild als Vorschaubild gespeichert", "Das Bild wurde als Vorschau f&uuml;r diese Galerie gespeichert.");
		$back = new Link($this->galleryModePrefix("viewimage", "&id=" . $_GET["id"]), "Zur&uuml;ck");
		$back->write();
	}
	
	private function backToGallery() {
		$back = new Link($this->modePrefix() . "gallery", "Zur&uuml;ck");
		$back->write();
	}
	
	private function backToGalleryView($id) {
		$back = new Link($this->galleryModePrefix("viewgallery", "&id=$id"), "Zur&uuml;ck");
		$back->write();
	}
	
	private function galleryModePrefix($sub, $addition = "") {
		return $this->modePrefix() . "gallery&sub=$sub" . $addition;
	}
}

?>
