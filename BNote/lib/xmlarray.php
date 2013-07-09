<?php

/**
 * Functions to convert a PHP array into a XML array
 * and the other way round.
 * @author matti
 *
 */
class XmlArray {

	/**
	 * Encodes an array in XML.
	 * @param array $array Array to encode.
	 * @param String $root The name of the root node, by default "root".
	 * @return String with the XML in it.
	 */
	public static function array_encode($array, $root = "root") {
		$xml = "<$root>\n";
		$xml .= XmlArray::array_encode_rec($array);
		$xml .= "</$root>\n";
		return $xml;
	}
	
	/**
	 * Helper function which creates the recursive array.
	 * @param array $array PHP array to encode.
	 * @return String with the XML in it, but without a root node.
	 */
	private static function array_encode_rec($array) {
		$xml = "";
		foreach($array as $key => $value) {
			if(is_numeric($key)) {
				$xml .= "<element><key>$key</key><value>";
			}
			else {
				$xml .= "<$key>";
			}
			if(is_array($value)) {
				$xml .= XmlArray::array_encode_rec($value);
			}
			else {
				$xml .= $value;
			}
			if(is_numeric($key)) {
				$xml .= "</value></element>\n";
			}
			else {
				$xml .= "</$key>\n";
			}
		}
		return $xml;
	}
	
	/**
	 * Converts the XML input into a PHP array.
	 * @param String $xml XML document contents as a string.
	 * @return The resulting array. Be careful if the array has
	 * been converted with numeric keys before.
	 */
	public static function array_decode($xml) {
		$sxe = simplexml_load_string($xml);
		return XmlArray::array_decode_rec($sxe);
	}
	
	/**
	 * Converts a SimpleXMLElement to an array.
	 * @param SimpleXMLElement $sxe The element/tree to convert.
	 */
	private static function array_decode_rec($sxe) {
		$res = array();
		foreach($sxe as $k => $v) {
			if($v->count() > 1) {
				if($k == "element") {
					if($v->value->children()->count() > 1) {
						$res["" . $v->key] = XmlArray::array_decode_rec($v->value);
					}
					else {
						$res["" . $v->key] = "" . $v->value;
					}
				}
				else if("$k" == "value") {
					$res = XmlArray::array_decode_rec($v); 
				}
				else {
					$res[$k] = XmlArray::array_decode_rec($v);
				}
			}
			else {
				$res["$k"] = "$v";
			}
		}
		
		return $res;
	}
}

?>