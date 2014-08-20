<?php

/**
 * Central Mail Creation in BNote
 * @author Matti
 *
 */
class Mailing {
	
	private $from;
	private $encoding;
	private $to;
	private $bcc = null;
	private $subject;
	private $body;
	private $isHtml = false;
	
	private $sysdata;
	
	/**
	 * Creates a new mail with the given parameters and default from and encoding.<br/>
	 * <strong>Make sure to call {@link sendMail()} to actually send this mail.</strong>
	 * @param string $to Receipient; can be null.
	 * @param string $subject Message subject.
	 * @param string $body Message body.
	 */
	function __construct($to, $subject, $body) {
		$this->to = $to;
		$this->subject = $subject;
		$this->body = $body;
		
		// set default from as system-admin mail
		global $system_data;
		$this->sysdata = $system_data;
		$comp = $this->sysdata->getCompanyInformation();
		$this->from = $comp["Mail"];
		
		// set default encoding
		$this->encoding = "utf-8";
	}
	
	public function getFrom() {
		return $this->from;
	}
	
	public function setFrom($from) {
		$this->from = $from;
	}
	
	public function setFromUser($userId) {
		$contact = $this->sysdata->getUsersContact($userId);
		$this->from = $contact["name"] . " " . $contact["surname"] . " <" . $contact["email"] . ">";
	}
	
	public function getEncoding() {
		return $this->encoding;
	}
	
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	public function getTo() {
		return $this->to;
	}
	
	public function setTo($to) {
		$this->to = $to;
	}
	
	public function getBcc() {
		return $this->bcc;
	}
	
	public function setBcc($addresses) {
		$this->bcc = $addresses;
	}
	
	public function getSubject() {
		return $this->subject;
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	public function getBody() {
		return $this->body;
	}
	
	public function setBody($body) {
		$this->body = $body;
		$this->isHtml = false;
	}
	
	public function isHtmlBody() {
		return $this->isHtml;
	}
	
	/**
	 * Just give a plain HTML representation of your message.<br/>
	 * Do not worry about heading or this kinda stuff. Only body.
	 * @param string $html
	 */
	public function setBodyInHtml($html) {
		$this->isHtml = true;
		$this->body = $html;
	}
	
	/**
	 * Appends the given text to the body/message.<br/>
	 * <i>Can be used without initializing the message.</i>
	 * @param string $text Text to append.
	 */
	public function appendToBody($text) {
		if($this->body == null) {
			$this->body = "";
		}
		$this->body .= $text;
	}
	
	/**
	 * Call this method to send the email.<br/>
	 * <strong>Just by creating an object of this class, no mail is sent!</strong>
	 */
	public function sendMail() {
		// abort if in demo mode
		if($this->sysdata->inDemoMode()) {
			new Error("Das System ist im demonstrationsmodus und versendet daher keine E-Mails.");
			return false;
		}
		
		// building headers
		$headers  = "From: " . $this->from . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=' . $this->encoding . "\r\n";
		
		if($this->bcc != null) {
			$headers .= 'Bcc: ' . $this->bcc . "\r\n";
		}
		
		// building receipient
		if($this->to == null) {
			$to = "";
		}
		else {
			$to = $this->to;
		}
		
		// validation
		if($this->bcc == null && $this->to == null) {
			new Error("Bitte geben Sie BCC or AN or beide Felder an.");
		}
		if($this->body == null) {
			new Error("Es ist keine Nachricht angegeben.");
		}
		if($this->subject == null) {
			new Error("Es ist kein Betreff angegeben.");
		}
		
		// handle encoding
		$strenc = mb_detect_encoding($this->subject, 'UTF-8', true);
		if($strenc == false) {
			$subject = utf8_encode($this->subject);
		}
		else {
			$subject = $this->subject;
		}
		
		$strenc = mb_detect_encoding($this->body, 'UTF-8', true);
		if($strenc == false) {
			$body = utf8_encode($this->body);
		}
		else {
			$body = $this->body;
		}
		
		if($this->isHtmlBody()) {
			$htmlBody = $body;
			$body = "<html><head>";
			$body .= '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->encoding . '" />';
			$body .= "<title>" . $subject . "</title>";
			$body .= "</head><body>";
			$body .= $htmlBody;
			$body .= "</body></html>";
		}
		
		// sending mail
		return mail($to, $subject, $body, $headers);
	}
	
	/**
	 * Calls the {@link sendMail()} method and throws and error if it returns false.
	 */
	public function sendMailWithFailError() {
		if($this->sendMail() === false) {
			new Error("Die E-Mail konnte nicht gesendet werden.");
		}
	}
}

?>