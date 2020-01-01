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
	const CURRENCY = 15;
	const MINSEC = 16;  // Minutes and seconds
	const TEXT = 0;
	
	/**
	 * Returns the name of the type which is equals to the fields name.
	 * @param int $id ID of the type.
	 */
	public static function getTypeForId($id) {
		switch($id) {
			case FieldType::INTEGER: return "INTEGER";
			case FieldType::DECIMAL: return "DECIMAL";
			case FieldType::CHAR: return "CHAR";
			case FieldType::DATE: return "DATE";
			case FieldType::TIME: return "TIME";
			case FieldType::DATETIME: return "DATETIME";
			case FieldType::REFERENCE: return "REFERENCE";
			case FieldType::EMAIL: return "EMAIL";
			case FieldType::PASSWORD: return "PASSWORD";
			case FieldType::BOOLEAN: return "BOOLEAN";
			case FieldType::ENUM: return "ENUM";
			case FieldType::FILE: return "FILE";
			case FieldType::LOGIN: return "LOGIN";
			case FieldType::SET: return "SET";
			case FieldType::CURRENCY: return "CURRENCY";
			case FieldType::MINSEC: return "MINSEC";
			default: return "TEXT";
		}
	}
}