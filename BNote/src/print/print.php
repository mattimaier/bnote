<?php

/**
 * Basic class for pdf creation
 * @author matti
 */

require($GLOBALS["DIR_LIB"] . "fpdf.php");

class PrintPDF extends FPDF {
	
	/* CONSTANTS */
	// Font
	public $font_family = "Arial"; // General font family
	public $font_size = 12; // General font size
	public $footerFontSize = 8; // Font size for footer
	public $lineHeight = 5; // default height of a line
	
	// Margins
	public $topMargin = 15; // 1.5cm
	public $leftMargin = 17; // 1.7cm
	public $rightMargin = 17; // 1.7cm
	
	// Address field
	public $addressFromTop = 54; // distance from top
	public $addressFromLeft = 24; // distance from left
	public $addressWidth = 85; // width of field 
	
	public $addressHeaderFromTop = 49; // distance from top
	public $addressHeaderHeight = 4; // height of the cell
	
	/**
	 * Constructor
	 */
	function PrintPDF() {
		parent::__construct('P','mm','A4'); // Portrait, Millimeter, A4 
		$this->AliasNbPages(); // numerate all pages
		$this->SetAutoPageBreak(true); // automatically start new pages when page full
		$this->AddPage();
		$this->SetFont($this->font_family, '', $this->font_size);
		$this->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
	}
	
	/**
	 * Displays a footer on every page,
	 * overwrites FPDF function
	 */
	function Footer() {
	    $this->SetY(-15); //Position 1.5cm from bottom
	    $this->SetFont($this->font_family, '', $this->footerFontSize);
	    
	    // print <page number> / <all pages>
	    $this->Cell(0, 10, 'Seite '.$this->PageNo().' / {nb}', 0, 0, 'R');
	}
	
	function Write($h, $txt) {
		$txt = utf8_decode($txt);
		parent::Write($h, $txt);
	}
	
	function Cell($w, $h = 0, $txt = "", $border = 0, $ln = 0, $align = 'L', $fill = 0, $link = null) {
		$txt = utf8_decode($txt);
		parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
	}
	
	function MultiCell($w, $h, $txt, $border = 0, $ln = 0, $align = 'L', $fill = 0) {
		$txt = utf8_decode($txt);
		parent::MultiCell($w, $h, $txt, $border, $align, $fill);
	}
	
	/**************** CUSTOM FUNCTIONS ******************/
	/**
	 * Saves the file into the specified path and sends it to the browser
	 * @param String $dest Path with name and mime-type where the file will be saved
	 */
	function finish($dest) {
		$this->Output($dest, 'F');
	}
	
	/**
	 * Prints an address field on the page
	 * @param String $compname Name of the company the bill is for
	 * @param String $person Name of the person the bill is for
	 * @param String $street Name of the street
	 * @param String $zipNcity zip code and city
	 */
	function printAddress($compname, $person, $street, $zipNcity) {
		$this->SetXY($this->addressFromLeft, $this->addressFromTop);
		
		if(empty($person)) {
			$person = $compname;
			$compname = "";
		}
		
		$text = "$compname\n$person\n\n$street\n$zipNcity";
		
		$this->MultiCell($this->addressWidth, $this->lineHeight, $text);
	}
	
	/**
	 * Prints a small cell above the header width the address of the sender
	 * @param String $compname Name of the sender
	 * @param String $street Street with number of the sender
	 * @param String $zipNcity Zipcode and city of the sender
	 */
	function printAddressHeader($compname, $street, $zipNcity) {
		$this->SetFont($this->font_family, '', 8);
		$this->SetXY($this->addressFromLeft, $this->addressHeaderFromTop);
		
		$text = "$compname " . chr(183) . " $street " . chr(183) . " $zipNcity";
		
		$this->Cell($this->addressWidth, $this->addressHeaderHeight, $text, 'B', 0, 'L');
		$this->setFontStandard();
	}
	
	/**
	 * Changes the font style to bold
	 */
	function setFontBold() {
		$this->SetFont($this->font_family, 'B', $this->font_size);
	}
	
	/**
	 * Sets the font style to standard
	 */
	function setFontStandard() {
		$this->SetFont($this->font_family, '', $this->font_size);
	}
	
	/**
	 * Prints the letterhead with the information given in the array
	 * @param Array $info Array of the format <key> => <value>,
	 * 						required keys: Name, Street, City, Zip, Phone, Mail; optional: Fax, Web 
	 */
	function writeLetterhead($info) {
		// write boldy name
		$this->SetFont($this->font_family, 'B', 18);
		$this->Write(13, $info["Name"]);
		$this->Ln();
		
		// write address
		$this->SetFont($this->font_family, '', 14);
		$text = $info["Street"] . " " . chr(183) . " " . $info["Zip"] . " " . $info["City"];
		$this->Write($this->lineHeight, $text);
		$this->Ln();
		
		// write phone and optional fax, if no fax, write mail
		if(isset($info["Fax"])) {
			$text = "Tel. " . $info["Phone"] . " " . chr(183) . " Fax " . $info["Fax"];
		}
		else {
			$text = "Tel. " . $info["Phone"] . " " . chr(183) . " eMail " . $info["Mail"];
		}
		$this->Write($this->lineHeight, $text);
		$this->Ln();
		
		// if fax was set write mail and web, if only web was set write web
		if(isset($info["Fax"])) {
			$text = "eMail " . $info["Mail"];
		}
		if(isset($info["Web"])) {
			$web = "Web " . $info["Web"];
			if(isset($info["Fax"])) $text .= " " . chr(183) . " " . $web;
			 else $text = $web;
		}
		$this->Write($this->lineHeight, $text);
		
		$this->setFontStandard();
	}
	
	/**
	 * Prints the given date at the given place
	 * @param String $date The formatted date.
	 * @param String $place Where the document is issued
	 */
	function writeDate($date, $place) {
		$this->setFontStandard();
		$text = $place . ", " . $date;
		$this->Write($this->lineHeight, $text);
	}
	
	// END OF CLASS
}


?>