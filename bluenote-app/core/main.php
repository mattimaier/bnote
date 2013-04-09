<?php

require_once "config.php";
require_once "page.php";

/**
 * Application based on jQuery.
 * @author matti
 *
 */
class Main {
	
	private $navbar; // main menu
	
	/**
	 * Returns the pages of the app in the form:
	 * <page id> => [<page_name>, <is_subpage>]
	 */
	function pages() {
		return array(
			"login" => array(Config::$CAPTIONS["login"], false),
			"rehearsals" => array(Config::$CAPTIONS["rehearsals"], false),
			"concerts" => array(Config::$CAPTIONS["concerts"], false),
			"contacts" => array(Config::$CAPTIONS["contacts"], false),
			"about" => array(Config::$CAPTIONS["about"], false),
			"reason" => array(Config::$CAPTIONS["reason"], false)
		);
	}
	
	/**
	 * Application's main method.
	 */
	function __construct() {		
		// output
		include "head.php";
		
		foreach($this->pages() as $pid => $pinfo) {
			$p = new Page($pid, $this);
			if($pid == "login") $p->hideNavbar();
			$p->isSubpage($pinfo[1]);
			$p->write();
		}
			
		include "bottom.php";
	}
	
	function getPageCaption($pid) {
		$pages = $this->pages();
		return $pages[$pid][0];
	}
	
	function navbar() {
		if($this->navbar == null) {
			$this->navbar = new Navbar($this->pages());
		}
		return $this->navbar;
	}
}

