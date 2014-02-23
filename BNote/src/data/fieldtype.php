<?php

/**
 * Fieldtypes.
 * @author matti
 *
 */
class FieldType {
	
	// types
	const INTEGER = 1;
	const DECIMAL = 2;
	const CHAR = 3;
	const DATE = 4;
	const TIME = 5;
	const DATETIME = 6;
	const REFERENCE = 7;
	const EMAIL = 8;
	const PASSWORD = 9;
	const BOOLEAN = 10;
	const ENUM = 11;
	const FILE = 12;
	const LOGIN = 13;
	const SET = 14;
	
	const TEXT = 0;
	
	/**
	 * Returns the name of the type which is equals to the fields name.
	 * @param int $id ID of the type.
	 */
	public static function getTypeForId($id) {
		switch($id) {
			case 1: return "INTEGER";
			case 2: return "DECIMAL";
			case 3: return "CHAR";
			case 4: return "DATE";
			case 5: return "TIME";
			case 6: return "DATETIME";
			case 7: return "REFERENCE";
			case 8: return "EMAIL";
			case 9: return "PASSWORD";
			case 10: return "BOOLEAN";
			case 11: return "ENUM";
			case 12: return "FILE";
			case 13: return "LOGIN";
			case 14: return "SET";
			default: return "TEXT";
		}
	}
}