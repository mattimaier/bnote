<?php

/**
 * This class handles messages
 **/
class Message implements iWriteable {
	
	private $message;
	private $header;

	function __construct($h, $m) {
		$this->header = $h;
		$this->message = $m;
		echo $this->write();
	}

	function write() {
		?>
		<div class="message">
			<div class="messageHeader"><?php echo $this->header; ?></div>
			<?php echo $this->message; ?>
		</div>
		<?php
	}

	public function getName() {
		return NULL;
	}
}

?>