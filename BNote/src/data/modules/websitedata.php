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
		$query = "SELECT surname, name FROM contact c, user u WHERE u.id = ? AND c.id = u.contact";
		$un = $this->database->fetchRow($query, array(array("s", $user_id)));
		return $un["name"] . " " . $un["surname"];
	}
	
	function addInfo() {
		$_POST["author"] = $_SESSION["user"];
		$_POST["createdOn"] = date("Y-m-d H:i:s");
		
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
		$query = "UPDATE infos SET author = ?, editedOn = ? WHERE id = ?";
		$this->database->execute($query, array(
				array("s", $_SESSION["user"]), array("s", date("Y-m-d H:i:s")), array("s", $id)
		));
		
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
	
}