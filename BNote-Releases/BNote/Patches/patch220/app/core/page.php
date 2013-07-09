<?php
require_once "navbar.php";

class Page {

	private $name;
	private $isSubpage;
	private $caption = "";
	private $hideNavbar = false;
	
	/**
	 * Application object.
	 * @var Main
	 */
	private $app;
	
	function __construct($name, $app) {
		$this->name = $name;
		$this->app = $app;
	}
	
	function write() {
		?>
		<div data-role="page" id="<?php echo $this->name; ?>">
		<div data-role="header" data-position="fixed">
			<h1><?php
			if(!$this->isSubpage && $this->caption == "") {
				echo $this->app->getPageCaption($this->name);
			}
			else {
				echo $this->caption;
			}
		    ?></h1>
		</div>
		<?php
		if(!$this->hideNavbar) {
			$navbar = $this->app->navbar();
			$navbar->write();
		}
		?>
		<div data-role="content">
		<?php
		$pfile = Config::$DIR["pages"] . $this->name . ".php";
		if(file_exists($pfile)) {
			include $pfile;
		}
		else {
			echo "<p>Page not found</p>\n";
		}
		?>
		</div>
		<?php
		$this->footer(); 
		?>
		</div><?php
	}
	
	/**
	 * Contains the footer of the app.
	 */
	function footer() {
		?>
		<div data-role="footer" data-position="fixed" style="bottom: 0px;">
			<p align="center">by Matti Maier Internet Solutions</p>
		</div>
		<?php
	}
	
	function isSubpage($v = true) {
		$this->isSubpage = $v;
	}
	
	function setCaption($caption) {
		$this->caption = $caption;
	}
	
	function hideNavbar($hide = true) {
		$this->hideNavbar = $hide;
	}
}

?>