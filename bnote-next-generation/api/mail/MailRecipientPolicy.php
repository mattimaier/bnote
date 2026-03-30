<?php
/**
 * Rules for outbound mail recipients (non-deliverable / reserved domains).
 */

class MailRecipientPolicy {

    /**
     * @param string|null $email
     * @return bool True if outbound mail must not be sent to this address.
     */
    public static function shouldSkipOutboundDelivery($email) {
        if ($email === null || $email === '') {
            return false;
        }
        $email = strtolower(trim($email));
        $pos = strrpos($email, '@');
        if ($pos === false) {
            return false;
        }
        $host = substr($email, $pos + 1);
        if ($host === 'example.com') {
            return true;
        }
        $hlen = strlen($host);
        if ($hlen > 12 && substr($host, -12) === '.example.com') {
            return true;
        }
        return false;
    }
}
