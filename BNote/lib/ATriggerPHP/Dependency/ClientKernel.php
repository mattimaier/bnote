<?php

class ClientKernel {
        private $_isInited = false;
        private $_key;
        private $_secret;
        private $_apiServer;
        private $_apiTimeout;
        private $_debug;
        private $_async;
        private $_errors = 0;
        private $_ssl = true; //You are allowed to change this value if your host doesn't support php_openssl
        private $socket_failed;

      public function __construct($key, $secret, $Async, $Debug, $APITimeout, $APIServer) {
            $this->_isInited = true;
                $this->_key = $key;
                $this->_secret = $secret;
                $this->_apiServer = $APIServer;
                $this->_apiTimeout = $APITimeout;
                $this->_debug = $Debug;
                $this->_async = $Async;
                $this->_errors = 0;
      }

    public function doCreate($_timeSlice, $_url, $_tags, $_first = "", $_count = 1, $_retries = -1, $_postData = null)
        {
            try
            {
            if (!$this->_isInited) throw new Exception("Please initialize ATrigger first before using.");

            $_timeSlice = urlencode($_timeSlice);
            $_first = urlencode($this->toISO8601($_first));
            $_url = urlencode($_url);

            //Creating tags query array
            $tagsRaw = "";
            if ($_tags != null)
            {
                $tagsRaw = $this->dictionary2string($_tags, "tag_");
            }


            $urlQueries = "timeSlice=" . $_timeSlice .
                "&url=" . $_url .
                "&first=" . $_first . 
                "&count=" . $_count .
                "&retries=" . $_retries . 
                "&" . $tagsRaw;


            $this->callATrigger("tasks/create", $urlQueries , $_postData);
            }
            catch (Exception $e)
            {
                $this->handleError("doCreate", $e->getMessage());
            }
        }


        public function doDelete($_tags) 
        {
            return $this->actionUsingTags("tasks/delete", $_tags);
        }


        public function doPause($_tags) 
        {
            return $this->actionUsingTags("tasks/pause", $_tags);
        }


        public function doResume($_tags) 
        {
            return $this->actionUsingTags("tasks/resume", $_tags);
        }

        public function doGet($_tags) 
        {
            return $this->actionUsingTags("tasks/get", $_tags);
        }

        public function errorsCount()
        {
            return $this->$_errors;
        }

        public function verifyRequest($_requestIP)
        {

          $_out = "";
            try {
              $_out = $this->httpRequest("ipverify", "ip=" . urlencode($_requestIP), null);
              if (strpos($_out, "\"OK\"")) {
                   return true;
                } else {
                   return false;
                };


            }  catch (Exception $e) {
              if (strpos($e->getMessage(), "\"ERROR\"") && strpos($e->getMessage(), "\"INVALID IP ADDRESS.\"")) {
                return false;
              } else {
                $this->handleError("httpRequest", $e->getMessage());
              }
            }
            return false;
        }

       /**
       * Make an async request to our API. Fork a curl process, immediately send
       * to the API. If debug is enabled, we wait for the response.
       */
      private function httpRequest($urlType, $urlQueries, $postData) {

        try {

                $queries = "?key=" . urlencode($this->_key) . "&secret=" . urlencode($this->_secret) . "&" . $urlQueries;
                $payload = $this->dictionary2string($postData);

                $socket = $this->createSocket();

                if (!$socket)
                  return;

                $body = $this->createBody($this->_apiServer, $urlType, $queries, $payload);
                return $this->makeRequest($socket, $body);

        } catch (Exception $e) {
                $this->handleError("httpRequest", $e->getMessage());
        }
      }

         private function createSocket() {

            if ($this->socket_failed)
              return false;

            $protocol = $this->ssl() ? "ssl" : "tcp";
            $host = $this->_apiServer;
            $port = $this->ssl() ? 443 : 80;
            $timeout = $this->_apiTimeout;

            try {
              # Open our socket to the API Server.
              $socket = pfsockopen($protocol . "://" . $host, $port, $errno,
                                   $errstr, $timeout);

              # If we couldn't open the socket, handle the error.
              if ($errno != 0) {
                $this->handleError($errno, $errstr);
                $this->socket_failed = true;
                return false;
              }

              return $socket;

            } catch (Exception $e) {
              $this->handleError($e->getCode(), $e->getMessage());
              $this->socket_failed = true;
              return false;
            }
          }

          /**
           * Attempt to write the request to the socket, wait for response if debug
           * mode is enabled.
           * @param  stream  $socket the handle for the socket
           * @param  string  $req    request body
           * @return boolean $success
           */
          private function makeRequest($socket, $req, $retry = true) {

            $bytes_written = 0;
            $bytes_total = strlen($req);
            $closed = false;

            # Write the request
            while (!$closed && $bytes_written < $bytes_total) {
              try {
                $written = fwrite($socket, $req);
              } catch (Exception $e) {
                $this->handleError($e->getCode(), $e->getMessage());
                $closed = true;
              }
              if (!isset($written) || !$written) {
                $closed = true;
              } else {
                $bytes_written += $written;
              }
            }

            # If the socket has been closed, attempt to retry a single time.
            if ($closed) {
              fclose($socket);

              if ($retry) {
                $socket = $this->createSocket();
                if ($socket) return $this->makeRequest($socket, $req, false);
              }
              return false;
            }


            $success = true;

            #echo $req;

            if (!$this->_async || strpos($req, "/v1/ipverify?")) {
              
              $res = $this->parseResponse(fread($socket, 2048));
              if ($res["status"] != "200" || strpos($res["message"], "\"ERROR\"")) {
                $this->handleError($res["status"], $res["message"]);
                $success = false;
                return $res["message"];
              } else {
                return $res["message"];
              }
            }

            return $success;
          }


          private function createBody($host, $urlType, $urlQueries, $content) {

            $req = "";
            $req.= "POST /v1/" . $urlType . $urlQueries . " HTTP/1.1\r\n";
            $req.= "Host: " . $host . "\r\n";
            $req.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $req.= "Accept: */*\r\n";
            $req.= "Content-length: " . strlen($content) . "\r\n";
            $req.= "\r\n";
            $req.= $content;
            #echo $req;
            return $req;
          }


          private function parseResponse($res) {

            $contents = explode("\n", $res);

            # Response comes back as HTTP/1.1 200 OK
            # Final line contains HTTP response.
            $status = explode(" ", $contents[0], 3);
            $result = $contents[count($contents) - 1];

            return array(
              "status"  => isset($status[1]) ? $status[1] : null,
              "message" => $result
            );
          }


    private function actionUsingTags($urlType, $_tags)
        {
            if (!$this->_isInited) throw new Exception("Please initialize ATrigger first before using.");
            try
            {
                //Creating tags query array
                $urlQueries = $this->dictionary2string($_tags, "tag_");
                return $this->callATrigger($urlType, $urlQueries, null);
            }
            catch (Exception $e)
            {
                $this->handleError("actionUsingTags", $e->getMessage());
            }
            return "";
        }


        private function callATrigger($urlType, $urlQueries, $postData)
        {
             return $this->httpRequest($urlType, $urlQueries, $postData);
        }


    //Convert a dictionary object to escaped URL ready string
        private function dictionary2string($in, $preKeyName = "")
        {
            $output = "";
            if($in !== null) {
                foreach($in as $key=>$value)
              $output .= $preKeyName . urlencode ($key) . "=" . urlencode($value) . "&";
            $output = trim($output, "&");
            }
            return $output;
        }



      private function toISO8601($inDate)
        {
          if(gettype($inDate) == "string") {
            if(strpos($inDate, " ") || strpos($inDate, "/")) {
                //Make ISO format
                return date("c", strtotime($inDate));
            }
          } else {
            return $inDate->format('c');
          }
          return $inDate;
        }

      protected function ssl() {
        return isset($this->_ssl) ? $this->_ssl : true;
      }

      protected function handleError($code, $msg) {
        $this->_errors += 1;
        if($this->_debug) {
            throw new Exception("ATrigger:" . $code . " - " . $msg);
        }
        /*if (isset($this->options['error_handler'])) {
          $handler = $this->options['error_handler'];
          $handler($code, $msg);
        }

        if ($this->debug()) {
          error_log("[Analytics][" . $this->type . "] " . $msg);
        }*/
      }

}