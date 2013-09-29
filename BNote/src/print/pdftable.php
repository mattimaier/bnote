<?php

class PDFTable {
	
	/* Attributes */
	private $headerBackgroundColor = array(220, 220, 220); // RGW Color for Hex Color #DCDCDC

	private $data;
	private $widths = array();
	private $lastlines = array();
	private $header = array();
	private $ignoreCols = array();
	private $colHeaders = array();
	private $colType = array();
	private $border = 0;
	private $spacing = 2; // 2mm vertical spacing inbetween lines
	private $pageBreakThreshold = 175; // value at which the page is broken
	
	/**
	 * Constructor
	 * @param Array $data The data content of the table, e.g. from a selection result of the database class
	 */
	function PDFTable($data) {
		$this->data = $data;
	}
	
	/**
	 * Sets the width for a column
	 * @param integer $colnumber Number of the column starting at 0
	 * @param integer $width Width in mm of the column
	 */
	function setColumnWidth($colnumber, $width) {
		$this->widths[$colnumber] = $width;
	}
	
	/**
	 * Adds a line at the end of the table with only two columns.
	 * The first column is every column merged, but the last;
	 * the second column is the last column, e.g. for numbers
	 * @param String $label
	 * @param String $value
	 * @param integer $border Set to 1 for single bottom line, 0 for no specific line and 2 for background colored line
	 */
	function addSumLine($label, $value, $border) {
		$this->lastlines[$label] = array($value, $border);
	}
	
	/**
	 * Changes the name of the column
	 * @param integer $colnumber Number of the column starting at 0
	 * @param String $label Name of the column 
	 */
	function changeColumnLabel($colnumber, $label) {
		$this->header[$colnumber] = $label;
	}
	
	/**
	 * Doesn't print column with the given column number
	 * @param integer $colnumber Column not to print, numbering starts with 0
	 */
	function ignoreColumn($colnumber) {
		array_push($this->ignoreCols, $colnumber);
		$this->widths[$colnumber] = 0;
	}
	
	/**
	 * Sets content of column $headerCol as the header of the column $textCol 
	 * @param integer $headerCol Number of the column which contains the header
	 * @param integer $textCol Number of the column which contains the text to add the header for
	 */
	function markHeaderForColumn($headerCol, $textCol) {
		$this->ignoreColumn($headerCol);
		$this->colHeaders[$textCol] = $headerCol;
	}
	
	/**
	 * Sets the FieldType for a specific column.
	 * @param String $col Identifier of the column
	 * @param FieldType $type Type attribute.
	 */
	function setColumnType($col, $type) {
		$this->colType[$col] = $type;
	}
	
	/**
	 * Sets the border for all cells.
	 * @param String $border 0 = no border, 1 = full border, L = left, T = top, R = right, B = bottom.
	 */
	function setCellBorder($border) {
		$this->border = $border;
	}
	
	/**
	 * Set the vertical space inbetween lines.
	 * @param int $spacing Spacing in mm.
	 */
	function setVerticalSpacing($spacing) {
		$this->spacing = $spacing;
	}
	
	/**
	 * Sets the Y coordinate at which the table breaks for a new page. 
	 * @param Integer $threshold Y coordinate, for landscape by default 175.
	 */
	function setPageBreakThreshold($threshold) {
		$this->pageBreakThreshold = $threshold;
	}
	
	/**
	 * Write the table to the pdf document
	 * @param PrintPDF $doc FPDF Document Object
	 */
	function write($doc) {
		$regex = new Regex();
		
		// standard width for each column, if not specified otherwise
		$defaultWidth = 17 / (count($this->data[0])-1); // 20.5 (A4 width) - 2x 1.7 (borders left and right) = 17.1 -> 17
		
		$fill = 0; // standard not filled -> transparent
		$align = 'L'; // default align left
		
		$widthLastCol = 0;
		$totalWidth = 0;
		$lineY = $doc->GetY();
		$lineX = $doc->GetX();
		
		// write table from data
		foreach($this->data as $row => $info) {

			// set row's attributes
			$border = $this->border;
			$height = $doc->lineHeight;
			$spacing = $this->spacing;
			
			// write each cell 
			foreach($info as $col => $value) {
				if(!is_numeric($col)) continue; // because every column has a num and key value, one has to be omitted
				if(in_Array($col, $this->ignoreCols)) continue; // ignore cols, e.g. don't print id column

				// Set align right, when values are decimal
				if(isset($this->data[1]) && $regex->isNumber($this->data[1][$col]) &&
					(!isset($this->colType[$col]) || $this->colType[$col] == FieldType::DECIMAL
						 || $this->colType[$col] == FieldType::INTEGER)) {
					$align = 'R';
					if($regex->isInteger($value)) {
						$value = Data::formatInteger($value);
					}
					else {
						$value = Data::convertFromDb($value);
					}
				}
				else {
					$align = 'L';
				}
				
				// format dates
				if(isset($this->data[1]) && $regex->isDatabaseDateQuiet($this->data[1][$col])) {
					$value = Data::convertDateFromDb($value);
				}
				
				// Header
				if($row == 0) {
					$doc->setFontBold();
					$doc->SetFillColor($this->headerBackgroundColor[0], $this->headerBackgroundColor[1], $this->headerBackgroundColor[2]);
					$fill = 1;
					$spacing = 0;
					if(isset($this->header[$col])) $value = $this->header[$col]; // change value if header was renamed
				}
				else {
					$doc->setFontStandard();
					$fill = 0;
				}
				
				// change standard width if it was set differently
				if(isset($this->widths[$col])) $width = $this->widths[$col];
				 else $width = $defaultWidth;
				if($row == 0) $totalWidth += $width; 
				
				
				// check whether another column is header for the current
				if(isset($this->colHeaders[$col]) && $row != 0) {
					$doc->setFontBold();
					$doc->Cell($width, $doc->lineHeight, $this->data[$row][$this->colHeaders[$col]], 0, 1);
					$doc->setFontStandard();
					
					// calc the X position of the following text
					$w = 0;
					foreach($this->data[0] as $id => $colname) {
						if($id >= $col) break;
						if(isset($this->widths[$id])) $w += $this->widths[$id];
						 else $w += $defaultWidth;
					}
					$doc->SetX($w + $doc->leftMargin);
				}
				
				// write cell
				if($row != 0 && $doc->GetStringWidth($value) > $width) {	
					// write multiline text if the width is too wide or the text contains breaks
					$currentX = $doc->GetX();
					$currentY = $doc->GetY();
					$doc->MultiCell($width, $doc->lineHeight, $value, $border, 'L');
					if($lineY < $doc->GetY()) $lineY = $doc->GetY() - $doc->lineHeight;
					$doc->SetXY($currentX + $width, $currentY);
				}
				else {
					$doc->Cell($width, $doc->lineHeight, $value, $border, 0, $align, $fill);
					if($lineY < $doc->GetY()) $lineY = $doc->GetY();
				}
				
				// set last column's width
				$widthLastCol = $width;
			}
			
			// at the end of the row set cursor to correct position
			if($lineY > $this->pageBreakThreshold) {
				$doc->addPage();
				$lineY = $doc->topMargin; 
			}
			$doc->setXY($lineX, $lineY+$doc->lineHeight+$spacing);
		}
		$doc->Ln($doc->lineHeight +2);
		
		// write "no data" of if the table is empty, except header
		if(count($this->data) == 1)	{
			$doc->setFontStandard();
			$doc->Write($height, utf8_encode("Keine Einträge verfügbar."));
			$doc->Ln($doc->lineHeight*1.5);
		}
		
		// write last lines
		foreach($this->lastlines as $label => $info) {
			// calc widths
			$wCell1 = $totalWidth - $widthLastCol;
			$wCell2 = $widthLastCol;
			
			// set styles
			$align = 'R';
			$doc->setFontBold();
			
			$fill = 0;
			if($info[1] == 1) $border = 'B';
			elseif($info[1] == 2) {
				$border = 'T';
				$doc->SetFillColor($this->headerBackgroundColor[0], $this->headerBackgroundColor[1], $this->headerBackgroundColor[2]);
				$fill = 1;
			}
			else $border = 0; 
			
			// format values
			if($regex->isNumber($info[0])) {
				if($regex->isInteger($info[0])) {
					$info[0] = Data::formatInteger($info[0]);
				}
				else {
					$info[0] = Data::convertFromDb($info[0]);
				}
			}
			
			// write lines
			$doc->Cell($wCell1, $height, $label, $border, 0, $align, $fill);
			$doc->Cell($wCell2, $height, $info[0], $border, 1, $align, $fill);
			
			$doc->setFontStandard();
		}
		
	}
	
	// END OF CLASS
}

?>