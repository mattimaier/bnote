<?php
/**
 * API Bootstrap - Loads all required base classes
 * This ensures all dependencies are available before loading module-specific classes
 * 
 * Based on the loading order from src/logic/controller.php
 * 
 * Note: Database and Regex are loaded by Systemdata (in init.php)
 * ApplicationDataProvider is loaded conditionally by abstractdata.php
 */

// Load field types (used by all data classes to define field types)
require_once $GLOBALS['DIR_DATA'] . 'fieldtype.php';

// Load abstract base classes (dependency order matters)
// AbstractData loads ApplicationDataProvider conditionally
require_once $GLOBALS['DIR_DATA'] . 'abstractdata.php';
require_once $GLOBALS['DIR_DATA'] . 'abstractlocationdata.php';
