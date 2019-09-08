<?php
/**
 * Displays a table with data
 **/
class Table implements iWriteable {

	private $data;
	private $edit;
	private $primkey;
	private $edit_id_field = "id";
	private $modid;
	private $mode;
	private $foreign;
	private $lastlines = array();
	private $remove = array();
	private $formats = array();
	private $headernames = array();
	private $optColumns = array();

	private $dataRowSpan = 0;
	private $allowContentWrap = true;
	private $showFilter = true;
	private $isPaginated = false;
	private $offset = 0;
	private $limit;
	private $paginationLinkPrev;
	private $paginationLinkNext;
	
	/**
	 * Creates a new table
	 * @param Array $data Table data, e.g. from a getSelection-Query
	 */
	function __construct($data) {
		$this->data = $data;
		$this->edit = false;

		global $system_data;
		$this->modid = $system_data->getModuleId();
		$this->mode = "view";
	}

	function setEdit($primkey) {
		$this->edit = true;
		$this->primkey = $primkey;
	}

	function changeMode($mode) {
		$this->mode = $mode;
	}

	function setForeign($field, $table, $idcolumn, $namecolumn) {
		$this->foreign[$field] = array($table, $idcolumn, $namecolumn);
	}

	/**
	 * Adds a line at the end of the table with all cells merged except the last and displays the value in the last cell
	 * @param $label Text in the merged cell
	 * @param $value Value of the last cell
	 */
	function addSumLine($label, $value) {
		$this->lastlines[$label] = $value;
	}

	/**
	 * Removes the given column from the table
	 * @param $name
	 */
	function removeColumn($name) {
		array_push($this->remove, $name);
	}

	/**
	 * Sets the format of a specific column.
	 * @param Integer $column Id of the column.
	 * @param String $format One of the following formats: INT, DECIMAL, TEXT, DATE, BOOLEAN
	 */
	function setColumnFormat($column, $format) {
		$this->formats[$column] = $format;
	}

	/**
	 * Renames the column headers and aligns the column headers according to the type.
	 * @param Array $fields Fields-Array as described in AbstractDAO.
	 */
	function renameAndAlign($fields) {
		foreach($fields as $f => $settings) {
			$this->headernames[$f] = $settings[0];
			switch($settings[1]) {
				case FieldType::INTEGER: $this->setColumnFormat($f, "INT"); break;
				default: $this->setColumnFormat($f, FieldType::getTypeForId($settings[1])); break;
			}
		}
	}

	/**
	 * Changes the name of the header.
	 * @param String $field Name of the field.
	 * @param String $newName New name of the field.
	 */
	function renameHeader($field, $newName) {
		$this->headernames[$field] = $newName;
	}

	/**
	 * Function that is called just before the value is written.
	 * This method can be overridden by subclasses to implment special behaviour.
	 * @param String $value Value before its written (usually formatted).
	 * @param String $col Name of the column.
	 * @return The value that will be written.
	 */
	protected function editValue($value, $col) {
		return $value;
	}

	/**
	 * Allows a line to wrap.
	 * @param boolean $bool True to wrap (default), false not to wrap (adds a <pre> to the content).
	 */
	public function allowWordwrap($bool) {
		$this->allowContentWrap = $bool;
	}
	
	/**
	 * If the number is greater than 1 every n-th row will be grouped at the first visible row.
	 * @param int $n Rowspan for first visible column.
	 */
	public function setDataRowSpan($n) {
		$this->dataRowSpan = $n;
	}
	
	/**
	 * Set the ID field that is used to plug the id in.
	 * @param String $edit_id_field Name of the field.
	 */
	public function setEditIdField($edit_id_field) {
		$this->edit_id_field = $edit_id_field;
	}
	
	/**
	 * Set a new module ID.
	 * @param int $modId Module ID.
	 */
	public function setModId($modId) {
		$this->modid = $modId;
	}
	
	/**
	 * Adds a column to the data with a link to delete the item.
	 * @param Selection $tabData Database Selection which is used in this table class.
	 * @param String $delHref Link to the action of the buttons, format: "...&contactid=". The id of the item will be appended.
	 * @param String $delColName Name of the column in the data, by default "delete".
	 * @param String $delColCaption Caption of the column, by default "Löschen".
	 * @param String $icon Icon to use, by default "remove".
	 * @return Table data with delete column.
	 */
	public static function addDeleteColumn($tabData, $delHref, $delColName = "delete", $delColCaption = "Löschen", $icon="remove") {
		$tabData[0][$delColName] = $delColCaption;
		for($i = 1; $i < count($tabData); $i++) {
			$btn = new Link($delHref . $tabData[$i]["id"], "");
			$btn->addIcon($icon);
			$tabData[$i][$delColName] = $btn->toString();
		}
		return $tabData;
	}

	/**
	 * Mark these columns as option columns so they can be ignored for print and won't be linked.
	 * @param Array $cols Column names in an array.
	 */
	function setOptionColumnNames($cols) {
		$this->optColumns = $cols;
	}
	
	/**
	 * Show the filter line and enable the table as a JS data table.
	 * @param string $show True or False (hides the filter and marks it not as a data table).
	 */
	function showFilter($show=true) {
		$this->showFilter = $show;
	}
	
	function setPagination($offset, $limit, $link) {
		$this->isPaginated = true;
		$this->offset = $offset;
		$this->limit = $limit;
		$this->paginationLinkPrev = $link . ($offset-$limit >= 0 ? $offset-$limit : 0);
		$this->paginationLinkNext = $link . ($offset+$limit);
	}
	
	function write() {
		// generate id for each table to apply the javascript DataTable function later
		$identifier = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		echo '<table id="';
		echo $identifier;
		echo '" class="table table-striped table-bordered table-sm BNoteTable" cellspacing="0" width="100%"';
		echo '>' . "\n";

		$head = true;
		$empty = false;
		$colcount = 0;

		# Check for empty Table
		if(count($this->data) == 1) $empty = true;

		# Table
		$regex = new Regex();

		$rowSpanCount = 0;
		foreach($this->data as $i => $row) {
			if($head) {
				echo "<thead>\n";
			}
			 
			echo ' <tr>' . "\n";
			
			$firstVisibleColumn = true;
			foreach($row as $id => $value) {
				if($head) {
					// skip removed columns
					if(in_array(strtolower($value), $this->remove)) {
						continue;
					}

					# Header
					if(isset($this->headernames[strtolower($value)])) {
						$headerLabel = $this->headernames[strtolower($value)];
					}
					else if(isset($this->headernames[$value])) {
						// technical debt
						$headerLabel = $this->headernames[$value];
					}
					else {
						$headerLabel = $value;
					}

					$cssClasses = "";
					if(!is_numeric($id) && in_array($id, $this->optColumns)) {
						$cssClasses = " bn-table-option-column";
					}
					
					echo '  <td class="DataTable_Header' . $cssClasses . '">' . $headerLabel . '</td>' . "\n";
					$colcount++;
				}
				else if(!is_numeric($id)) {
					// skip removed columns
					if(in_array(strtolower($id), $this->remove)) {
						continue;
					}
					if($firstVisibleColumn && $this->dataRowSpan > 0 && $rowSpanCount % $this->dataRowSpan > 0) {
						$rowSpanCount++;
						$firstVisibleColumn = false;
						continue;
					}
					
					# Data
					echo '  <td class="DataTable';
					if(in_array($id, $this->optColumns)) {
						echo ' bn-table-option-column';
					}
					
					echo '"';
					if($firstVisibleColumn && $this->dataRowSpan > 0) {
						echo ' rowspan="' . $this->dataRowSpan . '"';
						$rowSpanCount++;
					}

					// Check whether the value is a decimal -> if so, align right
					$isMoney = $regex->isMoneyQuiet($value);
					if($isMoney && !isset($this->formats[$id])) echo ' align="right"';

					// Check for special format requests
					if(isset($this->formats[$id])) {
						if($this->formats[$id] == "INT" || $this->formats[$id] == "DECIMAL") echo ' align="right"';
					}

					echo '>';

					// Check for primary keys
					if($this->edit && !in_array($id, $this->optColumns)) {
						$href = '?mod=' . $this->modid . '&mode=' . $this->mode . '&' . $this->edit_id_field . '=' . $row[$this->primkey];
						echo '<a class="silent" href="' . $href . '">';
					}

					// Check for foreign keys
					if(isset($this->foreign[$id]) && !empty($value)) {
						global $system_data;
						$arr = $system_data->dbcon->getForeign($this->foreign[$id][0], $this->foreign[$id][1], $this->foreign[$id][2]);
						$value = $arr[$value];
					}

					// Check whether the value is a decimal -> if so, change . to ,
					if($isMoney && !isset($this->formats[$id])) $value = Data::convertFromDb($value);

					// Check for special format requests
					if(isset($this->formats[$id])) {
						if($this->formats[$id] == "DECIMAL") {
							$value = Data::convertFromDb($value);
							if($value == "") $value = "0,00";
						}
						if($this->formats[$id] == "DATE") {
							$value = Data::convertDateFromDb($value);
						}
						if($this->formats[$id] == "BOOLEAN") {
							if($value == 1) $value = Lang::txt("Table_write.yes");
							else $value = Lang::txt("Table_write.yes");
						}
					}

					// Check whether the value is empty -> if so, change to -
					if(empty($value)) $value = "-";

					// Check whether the value is a textarea -> if so, display breaks, etc.
					if(!$this->allowContentWrap && strlen($value) > 100) $value = "<pre>$value</pre>";

					// Check for date values
					if($regex->isDatabaseDateQuiet($value) && !isset($this->formats[$id])) {
						$value = Data::convertDateFromDb($value);
					}

					// build in functionality to edit values for special cases
					$value = $this->editValue($value, $id);

					echo $value;

					if($this->edit && !in_array($id, $this->optColumns)) {
						echo '</a>';
					}
					echo '</td>' . "\n";
					$firstVisibleColumn = false;
				}
			}
			echo ' </tr>' . "\n";
			if($head) {
				echo "</thead>\n";
				echo "<tbody>\n";
				$head = false;
			}

			# Write empty message
			if($empty) {
				echo ' <TR><TD colspan="' . $colcount . '">[' . Lang::txt("Table_write.table_no_entries") . ']</TD></TR>' . "\n";
			}
		}
		
		// write last lines
		foreach($this->lastlines as $label => $value) {
			echo " <tr>\n";
			// last row for sums
			echo "  <td colspan=\"" . (count($this->data[0])-count($this->remove)-1). "\" class=\"DataTable_Sum\" align=\"right\">" . $label . "</td>\n";
			if($regex->isMoneyQuiet($value)) $value = Data::convertFromDb($value);
			echo "  <td class=\"DataTable_Sum\" align=\"right\">" . $value . "</td>\n";
			echo ' </tr>';
		}

		echo "</tbody>\n";
		echo "</table>\n";
		if($this->isPaginated) {
			?>
			<a href="<?php echo $this->paginationLinkPrev; ?>"><div class="DataTable_prevpage"><?php echo Lang::txt("Table_write.prevpage"); ?></div></a>
			<a href="<?php echo $this->paginationLinkNext; ?>"><div class="DataTable_nextpage"><?php echo Lang::txt("Table_write.nextpage"); ?></div></a>
			<?php
		}

		if($this->showFilter && !$empty) {
			?>
			<script>
			$(document).ready(function () {
				$('#<?php echo $identifier; ?>').DataTable({
					responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal( {
                    header: function ( row ) {
                        var data = row.data();
                        return 'Details for '+data[0]+' '+data[1];
                    }
                } ),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll( {
                    tableClass: 'table'
                } )
            }
        }
				});
				$('.dataTables_length').addClass('bs-select');
				});
			</script>
			<?php
		}
		return $identifier;
	}

}

?>