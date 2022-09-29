<?php
/**
 * View for configuration module.
 * @author matti
 *
 */
class AufgabenView extends CrudRefView {

	/**
	 * Create the start view.
	 */
	function __construct($ctrl) {
		$this->setController($ctrl);
		$this->setEntityName(lang::txt("AufgabenView_construct.EntityName"));
		$this->setAddEntityName(Lang::txt("AufgabenView_construct.addEntityName"));
		$this->setJoinedAttributes(array(
				"created_by" => array("name", "surname"),
				"assigned_to" => array("name", "surname")
				));
	}
	
	function startOptions() {
		parent::startOptions();
		
		$grpTask = new Link($this->modePrefix() . "addGroupTask", Lang::txt("AufgabenView_startOptions.addGroupTask"));
		$grpTask->addIcon("plus");
		$grpTask->write();
		
		if(isset($_GET["table"]) && $_GET["table"] == "completed") {
			$showOpen = new Link($this->modePrefix() . "start&table=open", Lang::txt("AufgabenView_startOptions.open"));
			$showOpen->addIcon("tasks");
			$showOpen->write();
		}
		else {
			$showCompleted = new Link($this->modePrefix() . "start&table=completed", Lang::txt("AufgabenView_startOptions.completed"));
			$showCompleted->addIcon("archive");
			$showCompleted->write();
		}
	}
	
	protected function showAllTable() {
		// show table rows
		if(isset($_GET["table"]) && $_GET["table"] == "completed") {
			$data = $this->getData()->getTasks(false);
		}
		else {
			$data = $this->getData()->getTasks();
		}
		$table = new Table($data);
		$table->setEdit("id");
		$table->removeColumn("id");
		$table->renameHeader("creator", Lang::txt("AufgabenView_showAllTable.creator"));
		$table->renameHeader("assignee", Lang::txt("AufgabenView_showAllTable.assignee"));
		$table->removeColumn(4);
		$table->removeColumn("created_by");
		$table->removeColumn(6);
		$table->removeColumn("assigned_to");
		if(!isset($_GET["table"]) || $_GET["table"] == "open") {
			$table->removeColumn(8);
			$table->removeColumn("completed_at");
		}
		$table->renameAndAlign($this->getData()->getFields());
		$table->setColumnFormat("created_at", "DATE");
		$table->setColumnFormat("due_at", "DATE");
		$table->write();
	}
	
	private function getAddForm($mode = "add", $form_target=null, $tour=null) {
		$target = $this->modePrefix() . $mode;
		if($form_target != null) {
			$target = $form_target;
		}
		$form = new Form(Lang::txt($this->getaddEntityName()), $target);
		$form->addElement(Lang::txt("AufgabenView_getAddForm.title"), new Field("title", "", FieldType::CHAR), true, 12);
		$form->addElement(Lang::txt("AufgabenView_getAddForm.description"), new Field("description", "", FieldType::TEXT), false, 12);
		$form->addElement(Lang::txt("AufgabenView_getAddForm.due_at"), new Field("due_at", "", FieldType::DATETIME));
		if($tour != null) {
			$form->addHidden("tour", $tour);
		}
		return $form;
	}
	
	function addEntity($form_target=null, $tour=null) {
		$this->addEntityForm($form_target, $tour);
	}
	
	protected function addEntityForm($form_target=null, $tour=null) {
		$form = $this->getAddForm("add", $form_target, $tour);
		$form->addElement(Lang::txt("AufgabenView_add_editEntityForm.assigned_to"), new Field("assigned_to", "", FieldType::REFERENCE));
		$currContactId = $this->getData()->getSysdata()->getContactFromUser();
		$form->setForeign(Lang::txt("AufgabenView_add_editEntityForm.assigned_to"), "contact", "id", array("name", "surname"), $currContactId);
		$form->write();
	}
	
	function addGroupTask() {
		$form = $this->getAddForm("process_addGroupTask");
		$groups = $this->getData()->adp()->getGroups();
		$selector = new GroupSelector($groups, array(), "group");
		$form->addElement(Lang::txt("AufgabenView_addGroupTask.assigned_to"), $selector);
		$form->write();
	}
	
	function process_addGroupTask() {
		$groups = GroupSelector::getPostSelection($this->getData()->adp()->getGroups(), "group");
		$values = $_POST;
		$this->getData()->validate($values);
		
		foreach($groups as $gid) {
			$contacts = $this->getData()->adp()->getGroupContacts($gid);
			for($j = 1; $j < count($contacts); $j++) {
				$values[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")] = $contacts[$j]["id"];
				if($values[Lang::txt("AufgabenView_add_editEntityForm.assigned_to")] == "") continue;
				$this->getData()->create($values);
			}
		}
		new Message(Lang::txt("AufgabenView_process_addGroupTask.Message1"), Lang::txt("AufgabenView_process_addGroupTask.Message2"));
	}
	
	protected function viewDetailTable() {
		$dv = new Dataview();
		$task = $this->getData()->findByIdNoRef($_GET["id"]);
		$dv->autoAddElements($task);
		$dv->autoRename($this->getData()->getFields());
		$dv->removeElement("Erstellt von");
		$dv->addElement(Lang::txt("AufgabenView_viewDetailTable.created_by"), $this->getData()->getContactname($task["created_by"]));
		$dv->removeElement("Verantwortlicher");
		$dv->addElement(Lang::txt("AufgabenView_viewDetailTable.assigned_to"), $this->getData()->getContactname($task["assigned_to"]));
		$dv->write();
	}
	
	protected function additionalViewButtons() {		
		if($this->getData()->isTaskComplete($_GET["id"])) {
			$markTodo = new Link($this->modePrefix() . "markTask&as=open&id=" . $_GET["id"], Lang::txt("AufgabenView_additionalViewButtons.open"));
			$markTodo->addIcon("tasks");
			$markTodo->write();
		}
		else {
			$markComplete = new Link($this->modePrefix() . "markTask&as=complete&id=" . $_GET["id"], Lang::txt("AufgabenView_additionalViewButtons.complete"));
			$markComplete->addIcon("check");
			$markComplete->write();
		}
	}
	
	protected function editEntityForm($write=true) {
		$task = $this->getData()->findByIdNoRef($_GET["id"]);
		
		$form = new Form($this->getEntityName() .Lang::txt("AufgabenView_editEntityForm.edit"), $this->modePrefix() . "edit_process&id=" . $_GET["id"]);
		$form->addElement(Lang::txt("AufgabenView_editEntityForm.title"), new Field("title", $task["title"], FieldType::CHAR));
		$form->addElement(Lang::txt("AufgabenView_editEntityForm.description"), new Field("description", $task["description"], FieldType::TEXT));
		$form->addElement(Lang::txt("AufgabenView_editEntityForm.due_at"), new Field("due_at", $task["due_at"], FieldType::DATETIME));
		$form->addElement(Lang::txt("AufgabenView_add_editEntityForm.assigned_to"), new Field("assigned_to", "", FieldType::REFERENCE));
		$form->setForeign(Lang::txt("AufgabenView_add_editEntityForm.assigned_to"), "contact", "id", array("name", "surname"), $task["assigned_to"]);
		$form->write();
	}
	
	public function markTask() {
		if($_GET["as"] == "complete") {
			$is_complete = 1;
		}
		else {
			$is_complete = 0;
		}
		$tid = $_GET["id"];
		$this->getData()->markTask($tid, $is_complete);
		$this->view();
	}
}