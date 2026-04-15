<?php
/**
 * BNote Next Generation - Path Configuration
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
 * Path Configuration
 *
 * Defines the path to the original BNote codebase.
 * This allows bnote-next-generation to access the old codebase for:
 * - Database connections
 * - Data classes
 * - Business logic
 * - Configuration files
 *
 * Path is calculated relative to this file's location:
 * - This file: /path/to/bnote/bnote-next-generation/api/paths.php
 * - BNote root: /path/to/bnote/BNote/
 * - Relative: ../BNote
 */

// Calculate path to original BNote codebase
// From bnote-next-generation/api/paths.php -> go up to bnote/ -> then into BNote/
// Calculate root of bnote-next-generation folder
$bnoteNextGenRoot = __DIR__ . "/..";
// Calculate path to original BNote codebase (sibling folder)
$BNoteRoot = $bnoteNextGenRoot . "/../BNote";
$BNoteRoot = realpath($BNoteRoot) ?: $BNoteRoot; // Resolve to absolute path

// Make it available globally
if (!defined("BNOTE_ROOT")) {
  define("BNOTE_ROOT", $BNoteRoot);
}

// Also set as global variable for compatibility
if (!isset($GLOBALS["BNOTE_ROOT"])) {
  $GLOBALS["BNOTE_ROOT"] = $BNoteRoot;
}
