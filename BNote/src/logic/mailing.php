<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Central Mail Creation in BNote
 * @author Matti
 *
 */
class Mailing {
	
	private $fromId = null;
	private $to;
	private $bcc = null;
	private $subject;
	private $body;
	private $isHtml = false;
	private $attachments = array();
	
	private $sysdata;
	
	/**
	 * Creates a new mail with the given parameters and default from and encoding.<br/>
	 * <strong>Make sure to call {@link sendMail()} to actually send this mail.</strong>
	 * @param string $to Receipient; can be null.
	 * @param string $subject Message subject.
	 * @param string $body Message body.
	 */
	function __construct($subject, $body) {
		$this->subject = $subject;
		$this->body = $body;
		
		// set default from as system-admin mail
		global $system_data;
		$this->sysdata = $system_data;
	}
	
	public function setFromUser($userId) {
		$this->fromId = $userId;
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
	
	public function addAttachment($attachment, $name) {
		array_push($this->attachments, array($attachment, $name));
	}
	
	/**
	 * Call this method to send the email.<br/>
	 * <strong>Just by creating an object of this class, no mail is sent!</strong>
	 */
	public function sendMail() {
		// abort if in demo mode
		if($this->sysdata->inDemoMode()) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_1"));
			return false;
		}
		
		// building receipient
		if($this->to == null) {
			$to = "";
		}
		else {
			$to = $this->to;
		}
		
		// building sender information
		$fromEmail = $this->sysdata->getCompanyInformation()["Mail"];
		if($this->fromId == null) {
			$fromName = "BNote";
		}
		else {
			$contact = $this->sysdata->getUsersContact($this->fromId);
			$fromName =  $contact["name"] . " " . $contact["surname"] . " via BNote";
			$replyTo = $contact["email"];
			if($to == "") {
				$to = $contact["email"];
			}
		}
		
		// validation
		if($this->bcc == null && $this->to == null) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_2"));
		}
		if($this->body == null) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_3"));
		}
		if($this->subject == null) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_4"));
		}
		
		// handle charset
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
		
		// load template
		$tpl_path = "data/mail_template.html";
		$dir_prefix = "";
		if(isset($GLOBALS['dir_prefix'])) {
			$dir_prefix = $GLOBALS["dir_prefix"];
		}
		$template = file_get_contents($dir_prefix . $tpl_path);
		
		// replace placeholders
		$tpl_mail = str_replace("%encoding%", 'utf-8', $template);
		
		$tpl_mail = str_replace("%title%", $subject, $tpl_mail);
		$tpl_mail = str_replace("%content%", $body, $tpl_mail);
		$link = $this->sysdata->getSystemURL();
		$tpl_mail = str_replace("%link%", $link, $tpl_mail);
		$tpl_mail = str_replace("%link_name%", $this->sysdata->getCompany(), $tpl_mail);
		$tpl_mail = str_replace("%footer%", Lang::txt("mail_footerText"), $tpl_mail);
		
		// sending mail
		$mail = new PHPMailer(true);
		try {
			$mail->isMail();  // use mail() function from PHP
			$mail->CharSet = PHPMailer::CHARSET_UTF8;
			$mail->setFrom($fromEmail, $fromName);
			if(isset($replyTo)) {
				$mail->addReplyTo($replyTo);
			}
			$mail->addAddress($to);
			if($this->bcc != NULL) {
				foreach($this->bcc as $address) {
					if($address != "") {
						$mail->addBCC($address);
					}
				}
			}
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $tpl_mail;
			
			if(count($this->attachments) > 0) {
				foreach($this->attachments as $atmt) {
					$mail->addAttachment($atmt[0], $atmt[1]);
				}
			}
			
			return $mail->send();
		} catch (Exception $e) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_5") . " {$mail->ErrorInfo}");
		}
		return False;
	}
	
	/**
	 * Calls the {@link sendMail()} method and throws and error if it returns false.
	 */
	public function sendMailWithFailError() {
		if($this->sendMail() === false) {
			new BNoteError(Lang::txt("Mailing_sendMail.BNoteError_6"));
		}
	}
}