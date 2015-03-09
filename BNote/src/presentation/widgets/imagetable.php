<?php

class ImageTable implements iWriteable {
	
	private $thumbwidth;
	private $thumbheight;
	
	private $data;
	private $imagecol;
	private $imageidcol;
	private $imagepath;
	private $editmode;
	private $ipbasedfn;
	private $includeGallery;
	
	/**
	 * Creates a new table-like structure showing an image
	 * in front of a text.
	 * @param unknown_type $data
	 */
	function __construct($data) {
		$this->data = $data;
		
		// set default edit mode
		global $system_data;
		$this->editmode = "?mod=" . $system_data->getModuleId() . "&mode=edit";
		
		// set default thumb size
		$this->thumbwidth = "";
		$this->thumbheight = "50px";
		
		// set other defaults
		$this->imagecol = "filename";
		$this->imageidcol = "id";
		$this->ipbasedfn = false;
		$this->includeGallery = false;
	}
	
	/**
	 * Sets the column name of the database table
	 * column where the filename of the image is contained.
	 * @param String $col Name of the column.
	 */
	function setImageColumn($col) {
		$this->imagecol = $col;
	}
	
	/**
	 * This can be set along with the id based setting as the
	 * id columns name (image id).
	 * @param String $imageidcol Name of the image ID column.
	 */
	function setImageIdColumn($imageidcol) {
		$this->imageidcol = $imageidcol;
	}
	
	/**
	 * Sets a path in front of the image-filename. Make sure this path
	 * links to a thumbnail directory, since the image size will be
	 * displayed in 50x50px.
	 * @param String $path Path ending with "/".
	 */
	function setPrefixPath($path) {
		$this->imagepath = $path;
	}
	
	/**
	 * The mode where the edit view will be. The class attaches
	 * an "&id=?" string to the mode.
	 * @param String $mode Mode string, e.g. "?mod=10&mode=edit";
	 */
	function setEditMode($mode) {
		$this->editmode = $mode;
	}
	
	/**
	 * Sets the size of the thumbs.
	 * @param String $w Width, e.g. "50px".
	 * @param String $h Height, e.g. "50px".
	 */
	function setThumbsize($w, $h) {
		$this->thumbwidth = $w;
		$this->thumbheight = $h;
	}
	
	/**
	 * Sets whether the filename on the server is id based.
	 * @param bool $bool True by default.
	 */
	function setIdBasedFilename($bool = true) {
		$this->ipbasedfn = $bool;
	}
	
	/**
	 * Prefixes the (gallery's) ID (id column) as the directory name
	 * the image itself is contained in.
	 * @param bool $bool True (by default), false otherwise.
	 */
	function includeGalleryInPath($bool = true) {
		$this->includeGallery = $bool;
	}
	
	function write() {
		for($i = 1; $i < count($this->data); $i++) {
			$entry = $this->data[$i];
			$imgsrc = "";
			$imgalt = "kein Bild";
			
			// custom column name
			$imgsrc = $entry[$this->imagecol];

			// convert to id-based filename
			if($this->ipbasedfn) {
				$imgtype = substr($imgsrc, strrpos($imgsrc, ".")); // e.g. ".jpg"
				$imgsrc = $entry[$this->imageidcol] . $imgtype;
			}

			// check for additional path
			if(isset($this->imagepath) && $this->imagepath != "") {
				if($this->includeGallery) {
					$imgsrc = $entry["id"] . "/" . $imgsrc;
				}
				$imgsrc = $this->imagepath . $imgsrc;
			}
			
			?>
			<a class="imagetable" href="<?php echo $this->editmode . "&id=" . $entry["id"]; ?>">
			<div class="imagetable">
				<img class="imagetable" width="<?php echo $this->thumbwidth; ?>" height="<?php echo $this->thumbheight; ?>" src="<?php echo $imgsrc; ?>" alt="<?php echo $imgalt; ?>" /><br/>
				<span class="imagetable_label"><?php echo $entry["name"]; ?></span>
			</div></a>
			<?php
		}
		
		if(count($this->data) < 2) {
			?>
			<i><?php echo Lang::txt("noEntries"); ?></i>
			<?php
		}
	}
}

?>