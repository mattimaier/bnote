<?php

class Navbar {
	
	private $pages;
	
	function __construct($pages) {
		$this->pages = $pages;
	}
	
	function write() {
		?>
		<div data-role="navbar">
		<ul>
		<?php
		foreach($this->pages as $pid => $pinfo) {
			if($pid == "login" || $pid == "reason") continue;
			echo "	<li><a href=\"#$pid\">" . $pinfo[0] . "</a></li>\n";
		}
		?>
		</ul>
		</div>
		<?php
	}
}

?>