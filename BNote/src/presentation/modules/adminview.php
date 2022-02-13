<?php

class AdminView extends AbstractView {
	
	function __construct($ctrl) {
		$this->setController($ctrl);
	}
	
	public function start() {
		$dv = new Dataview();
		$dv->addElement("Version", $this->getData()->getSysdata()->getVersion());
		$dv->addElement("Super Users", implode(", ", $this->getData()->getSysdata()->getSuperUsers()));
		$dv->addElement("Start Module", $this->getData()->getSysdata()->getStartModuleId());
		$dv->addElement("Theme", $this->getData()->getSysdata()->getTheme());
		$dv->addElement("System URL", $this->getData()->getSysdata()->getSystemURL());
		$dv->addElement("Demo Mode", $this->getData()->getSysdata()->getSystemConfigParameter("DemoMode"));
		$dv->write();
	}
	
	public function startOptions() {
		// none
	}
	
}