<?php

/**
 * Translation implementation.
 * @author mattimaier
 *
 */
class Lang {
	
	/**
	 * The language that has been set.
	 * @var String
	 */
	private $lang;
	
	/**
	 * Language Object.
	 * @var Translation
	 */
	private $langObj;
	
	/**
	 * Singleton.
	 * @var Lang
	 */
	private static $INSTANCE;
	
	protected static function getInstance() {
		if(Lang::$INSTANCE == null) {
			global $system_data;
			$l = $system_data->getLang();
			Lang::$INSTANCE = new Lang($l);
		}
		return Lang::$INSTANCE;
	}
	
	function __construct($lang) {
		$this->lang = $lang;
		require_once 'lang/lang_' . $lang . ".php";
		$this->langObj = new Translation();
	}
	
	/**
	 * Translate according to language settings.
	 * @param String $code Code to translate.
	 * @return String Translated text or the code if the text was not found.
	 */
	public static function txt($code) {
		$inst = Lang::getInstance();
		$txt = $inst->langObj->getText($code);
		if($txt == null) return $code;
		return $txt;
	}
}

?>