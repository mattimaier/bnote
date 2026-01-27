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
	private $hideCols = array();
	private $controlButtons = array();
	private $colsSortedByDate = array();

	private $dataRowSpan = 0;
	private $allowContentWrap = true;
	private $showFilter = true;
	private $isPaginated = false;
	private $offset = 0;
	private $limit;
	private $paginationLinkPrev;
	private $paginationLinkNext;
	private $allowRowReorder = false;
	private $allowRowSorting = true;
	private $reorderPostUrl;
	
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
	
	function disableForwardNav() {
		$this->edit = FALSE;
	}

	function changeMode($mode) {
		$this->mode = $mode;
	}

	function setForeign($field, $table, $idcolumn, $namecolumn) {
		$this->foreign[$field] = array($table, $idcolumn, $namecolumn);
	}

	/**
	 * Adds a line at the end of the table with all cells merged except the last and displays the value in the last cell
	 * @param String $label Text in the merged cell
	 * @param String $value Value of the last cell
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
	 * @param String $format One of the following formats: INT, DECIMAL, CURRENCY, TEXT, DATE, BOOLEAN
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
	 * @return String The value that will be written.
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
	 * @param Array $tabData Database Selection which is used in this table class.
	 * @param String $delHref Link to the action of the buttons, format: "...&contactid=". The id of the item will be appended.
	 * @param String $delColName Name of the column in the data, by default "delete".
	 * @param String $delColCaption Caption of the column, by default "Löschen".
	 * @param String $icon Icon to use, by default "remove".
	 * @param String $idcol Name of the ID column, most often "id" which is the default
	 * @return Table data with delete column.
	 */
	public static function addDeleteColumn($tabData, $delHref, $delColName = "delete", $delColCaption = "Löschen", $icon="trash3", $idcol="id") {
		$tabData[0][$delColName] = $delColCaption;
		for($i = 1; $i < count($tabData); $i++) {
			$btn = new Link($delHref . $tabData[$i][$idcol], "");
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
	
	function allowRowReorder($allow, $postUrl) {
		$this->allowRowReorder = $allow;
		$this->reorderPostUrl = $postUrl;
	}
	
	function allowRowSorting($allow = true) {
		$this->allowRowSorting = $allow;
	}
	
	function hideColumn($colName) {
		array_push($this->hideCols, $colName);
	}

    /**
     * Set column sort order by DATE dd.mm.yyyy hh:mm even if the field's data format is not DATE
     * This is useful if the field string has more info appended: "01.01.1970 13:00 - 14:00"
     */
	function sortColumnByDate($colName) {
		array_push($this->colsSortedByDate, $colName);
	}

	/**
	 * Add global control buttons to the table.
	 * @param String $buttonId Examples: 'print', 'csvHtml5'
	 */
	function addControlButton($buttonId) {
		array_push($this->controlButtons, $buttonId);
	}
	
	function write() {
		echo '<div class="table-responsive">';
		// generate id for each table to apply the javascript DataTable function later
		$identifier = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		echo '<table id="'. $identifier . '" style="width: 100%" class="table">';

		$head = true;
		$empty = false;
		$colcount = 0;

		# Check for empty Table
		if(count($this->data) == 1) $empty = true;

		# Table
		$regex = new Regex();

		# mapping of columns to local indices (after removing columns)
		$colIndex = 0;
		$hideColsLocal = array();
		$colsSortedByDateLocal = array();

		$rowSpanCount = 0;
		foreach($this->data as $row) {
			if($head) {
				echo "<thead>\n";
			}
			
			$rowHref = "";
			if(!$head && $this->edit) {
				$rowHref = ' data-href="?mod=' . $this->modid . '&mode=' . $this->mode . '&' . $this->edit_id_field . '=' . $row[$this->primkey] . '"';
			}
			
			echo ' <tr' . $rowHref . '>';
			
			$firstVisibleColumn = true;
			foreach($row as $id => $value) {
				if($head) {
					// skip removed columns
					if(in_array(strtolower($value), $this->remove)) {
						continue;
					}

					if (in_array(strtolower($value), $this->hideCols)) {
						array_push($hideColsLocal, $colIndex);
					}

					if ( isset($this->formats[strtolower($value)]) && $this->formats[strtolower($value)] == "DATE" ||
							in_array(strtolower($value), $this->colsSortedByDate) ) {
						array_push($colsSortedByDateLocal, $colIndex);
					}
					$colIndex = $colIndex + 1;

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
					
					echo '  <th class="' . $cssClasses . '" scope="col">' . $headerLabel . '</td>' . "\n";
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
					echo '  <td class="';
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
						if($this->formats[$id] == "INT" || $this->formats[$id] == "DECIMAL" || $this->formats[$id] == "CURRENCY") echo ' align="right"';
					}

					echo '>';

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
						if($this->formats[$id] == "DECIMAL" || $this->formats[$id] == "CURRENCY") {
							$value = Data::convertFromDb($value);
							if($value == "") $value = "0,00";
						}
						if($this->formats[$id] == "DATE") {
							$value = Data::convertDateFromDb($value);
						}
						if($this->formats[$id] == "BOOLEAN") {
							if($value == 1) $value = Lang::txt("Table_write.yes");
							else $value = Lang::txt("Table_write.no");
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
		echo '</div>';
		
		if($this->isPaginated) {
			?>
			<a href="<?php echo $this->paginationLinkPrev; ?>"><div class="DataTable_prevpage"><?php echo Lang::txt("Table_write.prevpage"); ?></div></a>
			<a href="<?php echo $this->paginationLinkNext; ?>"><div class="DataTable_nextpage"><?php echo Lang::txt("Table_write.nextpage"); ?></div></a>
			<?php
		}

		if(!$empty) {
			?>
			<script>
<?php
			if(count($colsSortedByDateLocal) > 0) {
?>
			function parseDate(dateStr) {
				var parts = dateStr.split(/[\s\.\:]+/);
				if (parts.length < 3) return null;
				var day = parseInt(parts[0]);
				var mon = parseInt(parts[1]);
				var yea = parseInt(parts[2]);
				var hou = 0;
				var min = 0;
				if (parts.length >= 5) {
					var hou = parseInt(parts[3]);
					var min = parseInt(parts[4]);
				}
				if (isNaN(day) || day<0 || day>31 || isNaN(mon) || mon<1 || mon>12 ||
					isNaN(yea) || isNaN(hou) || hou < 0 || hou > 23 ||
					isNaN(min) || min < 0 || min >= 60) { return null; }
				return new Date(yea, mon-1, day, hou, min);
			}

			function dateCmp(date1, date2) {
				return date1.getFullYear() < date2.getFullYear() ? 1 :
					date1.getFullYear() > date2.getFullYear() ? -1 :
					date1.getMonth() < date2.getMonth() ? 1 :
					date1.getMonth() > date2.getMonth() ? -1 :
					date1.getDate() < date2.getDate() ? 1 :
					date1.getDate() > date2.getDate() ? -1 :
					date1.getHours() < date2.getHours() ? 1 :
					date1.getHours() > date2.getHours() ? -1 :
					date1.getMinutes() < date2.getMinutes() ? 1 :
					date1.getMinutes() > date2.getMinutes() ? -1 : 0;
			}
<?php
			}
?>
			// convert table to javasript DataTable
			$(document).ready(function() {
<?php
			if(count($colsSortedByDateLocal) > 0) {
?>
				$.extend( $.fn.dataTable.ext.type.order, {
					"sortByDate-desc": function ( val_1, val_2 ) {
						var date1 = parseDate(val_1);
						var date2 = parseDate(val_2);
						if (date1 == null || date2 == null) {
							return val_1 < val_2 ? 1 : val_1 > val_2 ? -1 : 0;
						}
						return dateCmp(date1, date2);
					},
					"sortByDate-asc": function ( val_1, val_2 ) {
						var date1 = parseDate(val_1);
						var date2 = parseDate(val_2);
						if (date1 == null || date2 == null) {
							return val_1 > val_2 ? 1 : val_1 < val_2 ? -1 : 0;
						}
						return dateCmp(date2, date1);
					}
				} );
<?php
			}
?>
				var identifier = "#<?php echo $identifier; ?>"
	    		var table = $(identifier).DataTable({
					 "paging": false, 
					 "info": false,  
					 "responsive": true,
					 "searching": <?php echo $this->showFilter ? "true" : "false"; ?>,
					 <?php 
					 if($this->allowRowReorder) {
					 ?>
					 "rowReorder": {
					 	dataSrc: 1  // rank index
					 },
					 "orderFixed": [ 1, 'asc' ],  // by rank ascending
					 <?php 
					 }
					 if(!$this->allowRowSorting) {
					 	?>
					 	"rowReorder": {
						 	enable: false
					 	},
					 	<?php
					 }
					 ?>
					 "oLanguage": {
				 		 "sEmptyTable":  "<?php echo Lang::txt("Table_write.sEmptyTable"); ?>",
						 "sInfoEmpty":  "<?php echo Lang::txt("Table_write.sInfoEmpty"); ?>",
						 "sZeroRecords":  "<?php echo Lang::txt("Table_write.sZeroRecords"); ?>",
	        			 "sSearch": "<?php echo Lang::txt("Table_write.sSearch"); ?>"
			 		 },
			 		 <?php 
			 		 if(count($this->controlButtons) > 0) {
			 		 ?>
			 		 "dom": 'Bfrtip',
			 		 "buttons": <?php echo json_encode($this->controlButtons); ?>
			 		 <?php 
					 }
					 else {
					 // define default buttons
					 ?>
					 "buttons": []
					 <?php
				if(count($colsSortedByDateLocal) > 0) {
					?>
					, "columnDefs": [
						{ "type": "sortByDate", targets: <?php echo json_encode($colsSortedByDateLocal); ?> }
					]
					<?php
				}
			}
					?>
					});
			<?php
			// hide columns
			if(count($hideColsLocal) > 0) {
				?>
				table.columns(<?php echo json_encode($hideColsLocal); ?>).visible(false);
				<?php
			}
			
			// Allow reordering rows, e.g. for program sorting - disable other features like sorting and click-to-open
			if($this->allowRowReorder) {
			?>
			table.rowReorder.enable();
			table.on('row-reordered', function ( e, diff, edit ) {
				var reqData = table.data().toArray();
				$.ajax({
					url: "<?php echo $this->reorderPostUrl; ?>",
					method: "POST",
					contentType: "application/json",
					data: JSON.stringify(reqData)
				}).done(function() {
					console.log("Autosave successful");
				}).fail(function() {
					alert("Unable to autosave. Please check logs.");
				});
			} );
		<?php
			}
			else if($this->edit) {
			?>
			$(identifier).on('click', 'tbody tr', function() {
				window.location.href = $(this).data('href');
			});
			$('tr').css('cursor','pointer');
			<?php
			}
		?>
		});
		</script>
		<?php
		}
		return $identifier;
	}

	public function getName() { return NULL; }
}
?>
