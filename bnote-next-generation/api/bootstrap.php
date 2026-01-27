<?php
/**
 * BNote Next Generation - API Bootstrap
 *
 * Copyright (C) 2026 BNote Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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

// Some data classes (e.g. UserData, Systemdata, AbstimmungData) require
// LoginController, which itself extends DefaultController. In the classic
// web entrypoints, DefaultController is loaded early by main.php/controller.php.
// The Next API runs without those entrypoints, so we must ensure
// DefaultController is available before any LoginController includes happen
// (otherwise PHP will throw "Class \"DefaultController\" not found").
require_once $GLOBALS['DIR_LOGIC'] . 'defaultcontroller.php';
