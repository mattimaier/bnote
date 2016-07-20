<?php
/*
ATrigger PHP Library 
Version: 0.1.1
We appreciate bug reports to hello@atrigger.com
*/
require(dirname(__FILE__) . '/Dependency/ClientKernel.php');

define("atrigger_apiServerDefault", "api.atrigger.com");
define("atrigger_apiTimeoutDefault", 5);
define("atrigger_debugDefault", false);
define("atrigger_asyncDefault", true);

class ATrigger {
		private static $client;

		/*First Init*/
		public static function init($key, $secret, $Async = atrigger_asyncDefault, $Debug = atrigger_debugDefault, $APITimeout = atrigger_apiTimeoutDefault, $APIServer = atrigger_apiServerDefault) {
			if (!$key || !$secret){
				throw new Exception("ATrigger::init Key and Secret parameter is required");
	  		}
			self::$client = new ClientKernel($key, $secret, $Async, $Debug, $APITimeout, $APIServer);
		}


	public static function doCreate($_timeSlice, $_url, $_tags, $_first = "", $_count = 1, $_retries = -1, $_postData = null)
        {
        	self::$client->doCreate($_timeSlice, $_url, $_tags, $_first, $_count, $_retries, $_postData);
        }


        public static function doDelete($_tags) 
        {
        	return self::$client->doDelete($_tags);
        }

        public static function doPause($_tags) 
        {
        	return self::$client->doPause($_tags);
        }

        public static function doResume($_tags) 
        {
        	return self::$client->doResume($_tags);
        }

        public static function doGet($_tags) 
        {
        	return self::$client->doGet($_tags);
        }

        public static function errorsCount($_tags) 
        {
        	return self::$client->errorsCount($_tags);
        }

        public static function verifyRequest($_requestIP) 
        {
                return self::$client->verifyRequest($_requestIP);
        }

}