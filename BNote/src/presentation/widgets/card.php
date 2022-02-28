<?php

/**
 * Show a little card with a title, a quick text with info and a link for more information
 * @author matti
 *
 */
class Card implements iWriteable {
	
	private $title;
	private $info;
	private $link;
	private $linkCaption;
	private $linkTarget;
	private $colSize;
	
	
	public function __construct($title, $info, $link, $linkCaption) {
		$this->title = $title;
		$this->info = $info;
		$this->link = $link;
		$this->linkCaption = $linkCaption;
		$this->linkTarget = NULL;  // default, means no target is set
		$this->colSize = 2;  // default
	}
	
	public function setTitle($title) {
		$this->title = $title;
	}
	
	public function setInfo($info) {
		$this->info = $info;
	}
	
	public function setColSize($size) {
		$this->colSize = $size;
	}
	
	public function setLinkTarget($target) {
		$this->linkTarget = $target;
	}
	
	public function write() {
		?>
		<div class="card col-md-<?php echo $this->colSize; ?> m-2">
		  <div class="card-body">
		    <h5 class="card-title"><?php echo $this->title; ?></h5>
		    <p class="card-text"><?php echo $this->info; ?></p>
		    <a href="<?php echo $this->link; ?>" class="btn btn-primary" <?php 
		    if($this->linkTarget != NULL) echo 'target="' . $this->linkTarget . '"';
		    ?>><?php echo $this->linkCaption; ?></a>
		  </div>
		</div>
		<?php
	}
	
	public function getName() {
		return $this->title;
	}
	
}

?>