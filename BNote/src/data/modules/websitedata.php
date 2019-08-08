<?php

/**
 * Data processing class.
 * @author matti
 *
 */
class WebsiteData extends AbstractData {
	
	/**
	 * Controller for the CMS module.
	 * @var WebsiteController
	 */
	private $controller;
	
	private $thumb_dir;
	private $gallery_dir;
	
	/**
	 * Create a new data processor for the cms module.
	 */
	function __construct() {
		$this->fields = array(
			"id" => array(Lang::txt("WebsiteData_construct.id"), FieldType::INTEGER),
			"author" => array(Lang::txt("WebsiteData_construct.author"), FieldType::REFERENCE),
			"createdOn" => array(Lang::txt("WebsiteData_construct.createdOn"), FieldType::DATETIME),
			"editedOn" => array(Lang::txt("WebsiteData_construct.editedOn"), FieldType::DATETIME),
			"title" => array(Lang::txt("WebsiteData_construct.title"), FieldType::CHAR)
		);
		
		$this->references = array(
			"author" => "user"
		);
		
		$this->table = "infos";
	
		$this->init();
	}
	
	function setController($ctrl) {
		$this->controller = $ctrl;
		
		$this->thumb_dir = $ctrl->getThumbnailDir();
		$this->gallery_dir = $ctrl->getGalleryDir(); 
	}

	/**
	 * @return Array All available pages.
	 */
	function getPages() {
		return $this->getSysdata()->getConfiguredPages();
	}
	
	function getInfos() {
		return $this->findAllJoined($this->references);
	}
	
	function getInfo($id) {
		return $this->findByIdNoRef($id);
	}
	
	function getInfopage($id) {
		return file_get_contents($this->controller->getFilenameForInfo($id));
	}
	
	function getUsername($user_id) {
		$query = "SELECT surname, name FROM contact c, user u WHERE u.id = " . $user_id;
		$query .= " AND c.id = u.contact";
		$un = $this->database->getRow($query);
		return $un["name"] . " " . $un["surname"];
	}
	
	function addInfo() {
		$_POST["author"] = $_SESSION["user"];
		$_POST["createdOn"] = date("d.m.Y H:i:s");
		
		// save to database
		$id = $this->create($_POST);
		
		if($id > 0) {
			// save document
			$filename = $this->controller->getFilenameForInfo($id);
			if(file_put_contents($filename, $_POST["page"]) !== false) {
				return true;
			}
		}
		
		return false;
	}
	
	function editInfo($id) {
		// update the edit date and author
		$query = "UPDATE " . $this->table;
		$query .= " SET author = " . $_SESSION["user"] . ", ";
		$query .=    "editedOn = \"" . date("Y-m-d H:i:s") . "\" ";
		$query .= " WHERE id = $id";
		$this->database->execute($query);
	
		// and replace the content
		$filename = $this->controller->getFilenameForInfo($id);
		if(file_put_contents($filename, $_POST["page"]) !== false) {
			return true;
		}
		
		return false;
	}
	
	function deleteInfo($id) {
		$this->delete($id);
		unlink($this->controller->getFilenameForInfo($id));
	}
	
	function getGalleries() {
		$query = "SELECT g.id as id, g.name as name, gi.filename as imagefile, g.previewimage as imageid";
		$query .= " FROM gallery g LEFT JOIN ";
		$query .= "galleryimage gi ON g.previewimage = gi.id";
		return $this->database->getSelection($query);
	}
	
	function createGallery($values) {
		$this->regex->isName($values["name"]);
		
		// create database entry
		$query = "INSERT INTO gallery (name) VALUES (\"" . $values["name"] . "\")";
		$gid = $this->database->execute($query);
		
		// create directories in gallery root and thumbs
		mkdir($this->thumb_dir . $gid);
		mkdir($this->gallery_dir . $gid);
		
		return $gid;
	}
	
	function getGallery($id) {
		$query = "SELECT * FROM gallery WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	function getGalleryImages($id) {
		$query = "SELECT * FROM galleryimage WHERE gallery = $id";
		return $this->database->getSelection($query);
	}
	
	/**
	 * Removes gallery and all its associates.<br />
	 * <i>This method is NOT transaction secure!</i>
	 * @param int $id ID of Gallery.
	 */
	function deleteGallery($id) {
		// remove folders
		rmdir($this->thumb_dir . $id);
		rmdir($this->gallery_dir . $id);
		
		// delete images from db
		$query = "DELETE FROM galleryimage WHERE gallery = $id";
		$this->database->execute($query);
		
		// delete gallery
		$query = "DELETE FROM gallery WHERE id = $id";
		$this->database->execute($query);
	}
	
	function editGallery($id, $values) {
		$this->regex->isName($values["name"]);
		
		$query = "UPDATE gallery SET name = \"" . $values["name"] . "\" WHERE id = $id";
		$this->database->execute($query);
	}
	
	/**
	 * Adds an image to the given gallery. Assumes that
	 * data is stored in POST array.
	 * @param int $gid ID of the gallery where the image should be added.
	 * @return int Image ID.
	 */
	function addImageToGallery($gid) {
		// validate name and description
		$this->regex->isName($_POST["name"]);
		if(isset($_POST["description"]) && $_POST["description"] != "") {
			$this->regex->isText($_POST["description"]);
		}
		
		// validate upload
		if($_FILES["file"]["error"] > 0) {
			new BNoteError(Lang::txt("WebsiteData_addImageToGallery.error_1"));
		}
		if(!is_uploaded_file($_FILES["file"]["tmp_name"])) {
			new BNoteError(Lang::txt("WebsiteData_addImageToGallery.error_2"));
		}
		if(!getimagesize($_FILES["file"]["tmp_name"])) {
			new BNoteError(Lang::txt("WebsiteData_addImageToGallery.error_3"));
		}
		
		// create database entry
		$filename = $_FILES["file"]["name"];
		$imgtype = substr($filename, strrpos($filename, ".")); // e.g. ".jpg"
		$description = "";
		if(isset($_POST["description"]) && $_POST["description"] != "") {
			$description = $_POST["description"];
		}
		
		$query = "INSERT INTO galleryimage (filename, name, description, gallery) ";
		$query .= " VALUES (";
		$query .= "\"$filename\", \"" . $_POST["name"] . "\", \"$description\", ";
		$query .= $gid;
		$query .= ")";
		$iid = $this->database->execute($query);
		
		// resize image and save it in gallery folder
		$img = new SimpleImage();
		$img->load($_FILES["file"]["tmp_name"]);
		$img->resizeToWidth($this->controller->getDefaultImageWidth());
		$img->save($this->gallery_dir . "$gid/" . $iid . $imgtype);
		
		// create thumbnail and save it in thumbnail folder 
		$thumb = new SimpleImage();
		$thumb->load($_FILES["file"]["tmp_name"]);
		$thumb->resizeToHeight($this->controller->getDefaultThumbnailHeight());
		$thumb->save($this->thumb_dir . "$gid/" . $iid . $imgtype);
		
		// return id
		return $iid;
	}
	
	function getImage($id) {
		$query = "SELECT * FROM galleryimage WHERE id = $id";
		return $this->database->getRow($query);
	}
	
	function editImage($id, $values) {
		// validate name and description
		$this->regex->isName($_POST["name"]);
		if(isset($_POST["description"]) && $_POST["description"] != "") {
			$this->regex->isText($_POST["description"]);
		}
		
		// udpate db
		$query = "UPDATE galleryimage SET ";
		$query .= 'name = "' . $values["name"] . '", ';
		$query .= 'description = "' . $values["description"] . '" ';
		$query .= " WHERE id = $id";
		$this->database->execute($query);
	}
	
	function deleteImage($id) {
		// get info
		$img = $this->getImage($id);
		$imgtype = substr($img["filename"], strrpos($img["filename"], ".")); // e.g. ".jpg"
		
		$thumb = $this->controller->getThumbnailDir() . $img["gallery"] . "/";
		$thumb .= $img["id"] . $imgtype;
		
		$image = $this->controller->getGalleryDir() . $img["gallery"] . "/";
		$image .= $img["id"] . $imgtype;
		
		// remove files from folders
		unlink($thumb);
		unlink($image);
		
		// remove database entry
		$query = "DELETE FROM galleryimage WHERE id = $id";
		$this->database->execute($query);
		
		// check whether image was gallery thumb, if so set that to null
		$g = $this->getGallery($img["gallery"]);
		if($g["previewimage"] == $id) {
			$query = "UPDATE gallery SET previewimage = NULL WHERE id = " . $img["gallery"];
			$this->database->execute($query);
		}
	}
	
	function setImageAsGalleryDefault($id) {
		// get gallery from image
		$img = $this->getImage($id);
		
		// set this image as the gallery's previewimage
		$query = "UPDATE gallery SET previewimage = $id WHERE id = " . $img["gallery"];
		$this->database->execute($query);
		
	}
}

?>