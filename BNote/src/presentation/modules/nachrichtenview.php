<?php

/**
 * View for news module.
 * @author matti
 *
 */
class NachrichtenView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	public function start() {
		$content = $this->getData()->fetchContent();
		$form = new Form(Lang::txt("NachrichtenView_start.form"), $this->modePrefix() . "save");
		$form->addElement("", new Field("news", $content, FieldType::TEXT));
		$form->changeSubmitButton(Lang::txt("NachrichtenView_start.Submit"));
		$form->write();
	}
	
	public function save() {
		$this->getData()->storeContent($_POST["news"]);
		new Message(Lang::txt("NachrichtenView_save.message_1"), Lang::txt("NachrichtenView_save.message_2"));
	}
	
	function startOptions() {
		// none;
	}
}


?>