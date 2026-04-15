<?php
/**
 * User registration for the Next.js JSON API without invoking legacy BNoteError / HTML error paths.
 * Mirrors LoginController::register / LoginData validation (same crypt salt, queries, mail flow).
 *
 * Copyright (C) 2026 BNote Contributors
 * GNU GPL v3+
 */

require_once BNOTE_ROOT . "/src/data/regex.php";

class NextGenRegistration
{
  /** Must match LoginController::ENCRYPTION_HASH */
  const CRYPT_SALT = "BNot3pW3ncryp71oN";

  /**
   * @param array $body Parsed JSON body from API (same keys as AuthModule previously mapped to $_POST)
   * @return array{user:int,contact:int,address:int,mailOk:bool,message:string}
   */
  public static function execute(array $body): array
  {
    global $system_data;

    $get = static function ($key, $default = "") use ($body) {
      if (!isset($body[$key]) || $body[$key] === null) {
        return $default;
      }
      if (is_bool($body[$key])) {
        return $body[$key] ? "1" : $default;
      }
      if (is_int($body[$key]) || is_float($body[$key])) {
        return (string) $body[$key];
      }
      return is_string($body[$key]) ? $body[$key] : $default;
    };

    $terms = $body["terms"] ?? false;
    if (!($terms === true || $terms === 1 || $terms === "1" || $terms === "on")) {
      Response::error("register_terms_required", 400);
    }

    $sc = Regex::$SPECIALCHARACTERS;
    $reName = "/^[[:alnum:]" . $sc . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,50}$/';

    $name = trim($get("name"));
    $surname = trim($get("surname"));
    $nickname = trim($get("nickname"));
    $birthday = trim($get("birthday"));
    $email = trim($get("email"));
    $phone = trim($get("phone"));
    $mobile = trim($get("mobile"));
    $street = trim($get("street"));
    $zip = trim($get("zip"));
    $city = trim($get("city"));
    $country = trim($get("country"));
    $state = trim($get("state"));

    $instrumentRaw = $get("instrument");
    if (isset($body["instrument"]) && is_int($body["instrument"])) {
      $instrumentRaw = (string) $body["instrument"];
    }
    $instrument = preg_replace("/\D/", "", (string) $instrumentRaw);

    $pw1 = isset($body["pw1"]) && is_string($body["pw1"]) ? $body["pw1"] : "";
    $pw2 = isset($body["pw2"]) && is_string($body["pw2"]) ? $body["pw2"] : "";

    $len = static function (string $value): int {
      return function_exists("mb_strlen") ? mb_strlen($value, "UTF-8") : strlen($value);
    };

    if (!preg_match($reName, $name) || !preg_match($reName, $surname)) {
      Response::error("register_validation", 400);
    }
    if ($len($name) > 50 || $len($surname) > 50) {
      Response::error("register_validation", 400);
    }
    if ($nickname !== "" && (!preg_match($reName, $nickname) || $len($nickname) > 20)) {
      Response::error("register_validation", 400);
    }
    if ($len($email) > 45) {
      Response::error("register_validation", 400);
    }
    if ($phone !== "" && !preg_match('/^[0-9\+\-\/\ \(\)]{1,29}$/', $phone)) {
      Response::error("register_validation", 400);
    }
    if ($mobile !== "" && !preg_match('/^[0-9\+\-\/\ \(\)]{1,29}$/', $mobile)) {
      Response::error("register_validation", 400);
    }
    if (!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,4})$/', $email)) {
      Response::error("register_validation", 400);
    }

    $reStreet = "/^[[:alpha:]" . $sc . '0-9\ \.\,\-\/\(\)]{1,45}$/';
    if (!preg_match($reStreet, $street)) {
      Response::error("register_validation", 400);
    }
    if ($zip === "" || !preg_match('/^[[:alpha:]0-9\s]{4,7}$/', $zip)) {
      Response::error("register_validation", 400);
    }
    if (!preg_match("/^[[:alpha:]" . $sc . '0-9\ \.\,\-]{1,45}$/', $city)) {
      Response::error("register_validation", 400);
    }
    if ($country !== "" && !preg_match("/^[[:alnum:]" . $sc . '\ \.\-\,\;\:\_\+\&\#\'\/\(\)\?]{1,45}$/', $country)) {
      Response::error("register_validation", 400);
    }
    if ($birthday !== "") {
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
        Response::error("register_validation", 400);
      }
      $parts = explode("-", $birthday);
      if (count($parts) !== 3) {
        Response::error("register_validation", 400);
      }
      $year = (int) $parts[0];
      $month = (int) $parts[1];
      $day = (int) $parts[2];
      if (!checkdate($month, $day, $year)) {
        Response::error("register_validation", 400);
      }
    }

    $rePw = '/^.{6,45}$/';
    if (!preg_match($rePw, $pw1) || !preg_match($rePw, $pw2)) {
      Response::error("register_validation", 400);
    }
    if ($pw1 !== $pw2) {
      Response::error("register_password_mismatch", 400);
    }
    if (!preg_match('/^\d{1,12}$/', $instrument) || (int) $instrument < 1) {
      Response::error("register_validation", 400);
    }

    $db = $system_data->dbcon;
    $dupQ = "SELECT count(u.id) as cnt FROM user u JOIN contact c ON u.contact = c.id WHERE c.email = ?";
    $ct = $db->colValue($dupQ, "cnt", [["s", $email]]);
    if ((int) $ct > 0) {
      Response::error("register_email_in_use", 400);
    }

    $passwordEnc = crypt($pw1, self::CRYPT_SALT);

    $addrQ = "INSERT INTO address (street, city, zip, state, country) VALUES (?, ?, ?, ?, ?)";
    $aid = $db->prepStatement($addrQ, [["s", $street], ["s", $city], ["s", $zip], ["s", $state], ["s", $country]]);

    $bdVal = $birthday !== "" ? $birthday : null;
    // Terms accepted on this path → mirror explicit GDPR consent (legacy bug: left 0).
    $contactQ =
      "INSERT INTO contact (surname, name, nickname, phone, mobile, email, address, instrument, birthday, gdpr_ok) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $cid = $db->prepStatement($contactQ, [
      ["s", $surname],
      ["s", $name],
      ["s", $nickname],
      ["s", $phone],
      ["s", $mobile],
      ["s", $email],
      ["i", $aid],
      ["i", (int) $instrument],
      ["s", $bdVal],
      ["i", 1],
    ]);

    $defaultGroup = $system_data->getDynamicConfigParameter("default_contact_group");
    if ($defaultGroup === null || $defaultGroup === "") {
      $defaultGroup = 2;
    }
    $db->execute("INSERT INTO contact_group (contact, `group`) VALUES (?, ?)", [
      ["i", $cid],
      ["i", (int) $defaultGroup],
    ]);

    $userQ = "INSERT INTO user (login, password, isActive, contact) VALUES (?, ?, 0, ?)";
    $uid = $db->prepStatement($userQ, [["s", $email], ["s", $passwordEnc], ["i", $cid]]);

    $mods = $system_data->getDefaultUserCreatePermissions();
    if (!is_array($mods)) {
      $mods = [];
    }
    $mods = array_values(
      array_filter(
        array_map(static function ($m) {
          $t = trim((string) $m);
          return $t === "" ? null : $t;
        }, $mods),
      ),
    );
    if (count($mods) < 1) {
      $mods = [(string) $system_data->getStartModuleId()];
    }
    $tuples = [];
    $privParams = [];
    foreach ($mods as $mod) {
      $tuples[] = "(?, ?)";
      $privParams[] = ["i", $uid];
      $privParams[] = ["s", $mod];
    }
    $privQuery = "INSERT INTO privilege (user, module) VALUES " . implode(",", $tuples);
    $db->execute($privQuery, $privParams);

    $dp = isset($GLOBALS["dir_prefix"]) ? $GLOBALS["dir_prefix"] : "";
    $userhome = $GLOBALS["DATA_PATHS"]["userhome"] ?? "data/share/users/";
    $userPath = $dp . $userhome . $email;
    if (!is_dir($userPath)) {
      @mkdir($userPath, 0775, true);
    }

    $outMsg = Lang::txt("LoginController_register.outMsg");
    $mailOk = true;

    if ($system_data->autoUserActivation()) {
      if ($system_data->inDemoMode()) {
        $mailOk = false;
        $outMsg = Lang::txt("Mailing_sendMail.BNoteError_1");
      } else {
        $linkurl =
          $system_data->getSystemURL() .
          "/src/export/useractivation.php?uid=" .
          $uid .
          "&email=" .
          rawurlencode($email);
        if (substr($linkurl, 0, 4) !== "http") {
          $linkurl = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https://" : "http://") . $linkurl;
        }
        $subject = Lang::txt("LoginController_register.subject");
        $message =
          Lang::txt("LoginController_register.message_1") .
          '<a href="' .
          $linkurl .
          '">' .
          Lang::txt("LoginController_register.message_2") .
          "</a>";

        $logicPrefix = isset($GLOBALS["dir_prefix"]) ? $GLOBALS["dir_prefix"] : "";
        require_once $logicPrefix . $GLOBALS["DIR_LOGIC"] . "mailing.php";
        $mail = new Mailing($subject, $message);
        $mail->setTo($email);
        if (!$mail->sendMail()) {
          $mailOk = false;
          $outMsg = Lang::txt("LoginController_register.message_3");
        } else {
          $outMsg = Lang::txt("LoginController_register.message_4");
        }
      }
    } else {
      $mailOk = false;
      $outMsg = Lang::txt("LoginController_register.message_5");
    }

    require_once __DIR__ . "/mail/RegistrationAdminNotifier.php";
    RegistrationAdminNotifier::sendSafe($system_data, [
      "userId" => (int) $uid,
      "contactId" => (int) $cid,
      "name" => $name,
      "surname" => $surname,
      "email" => $email,
      "login" => $email,
      "autoUserActivation" => (bool) $system_data->autoUserActivation(),
    ]);

    return [
      "user" => (int) $uid,
      "contact" => (int) $cid,
      "address" => (int) $aid,
      "mailOk" => $mailOk,
      "message" => $outMsg,
    ];
  }
}
