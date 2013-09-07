<?php

/**
 * Extension of CrudView. This class allows its subclasses to conduct all
 * CRUD operations on objects with references.
 * @author matti
 *
 */
abstract class CrudRefView extends CrudView {
	
	/**
	 * @var Array Column names of attribute replacements, e.g. "name".
	 */
	private $joinedAttributes;
	
	protected function addEntityForm() {
		$form = new Form($this->getEntityName() ." hinzuf&uuml;gen", $this->modePrefix() . "add");
		$form->autoAddElementsNew($this->getData()->getFields());
		$form->removeElement("id");
		foreach($this->joinedAttributes as $field => $cols) {
			
			$caption = "";
			foreach($cols as $i => $col) {
				$caption .= $col . ", ";
			}
			$caption = substr($caption, 0, strlen($caption)-2);
			
			$form->setForeign($field, $this->getData()->getReferencedTable($field),
						"id", $caption, -1);
		}
		$form->write();
	}
	
	protected function showAllTable() {
		// show table rows
		$table = new Table($this->getData()->findAllJoined($this->joinedAttributes));
		$table->setEdit("id");
		$table->renameAndAlign($this->getData()->getFields());
		$table->write();
	}
	
	protected function viewDetailTable() {
		$entity = $this->getData()->findByIdJoined($_GET["id"], $this->joinedAttributes);
		$details = new Dataview();
		$details->autoAddElements($entity);
		$details->autoRename($this->getData()->getFields());
		$details->write();
	}
	
	/**
	 * Sets the columns which will be replaced for the foreign key.
	 * @param Array $jA Format of array: [foreign-key] => {col1, col2, ...}
	 */
	public function setJoinedAttributes($jA) {
		$this->joinedAttributes = $jA;
	}

	/**
	 * @return Array Format of array: [foreign-key] => {col1, col2, ...}
	 */
	public function getJoinedAttributes() {
		return $this->joinedAttributes;
	}
}

?>