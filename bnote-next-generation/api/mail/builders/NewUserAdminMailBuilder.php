<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/MailI18n.php";
require_once dirname(__DIR__) . "/MailEnv.php";
require_once dirname(__DIR__) . "/MailHtmlShell.php";
require_once dirname(__DIR__) . "/MailDesignTokens.php";
require_once dirname(__DIR__) . "/MailAssets.php";
require_once dirname(__DIR__) . "/MailBodyText.php";
require_once dirname(__DIR__) . "/MailSubject.php";
require_once dirname(__DIR__) . "/MailBranding.php";
require_once dirname(__DIR__) . "/MailGreeting.php";
require_once dirname(__DIR__) . "/NextGenMailMessage.php";

final class NewUserAdminMailBuilder
{
  /**
   * Reduce client auto-linking of addresses in intro text; still renders as @ in mail clients.
   */
  private static function textForIntro(string $raw): string
  {
    $e = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");

    return str_replace("@", "&#64;", $e);
  }

  /**
   * @param array{userId:int,contactId:int,name:string,surname:string,email:string,login:string,autoUserActivation:bool} $ctx
   * @param list<string> $to
   * @param list<string> $bcc
   * @param string $recipientFirstName Admin recipient contact `name`; greeting omitted when empty.
   */
  public static function build(
    $system_data,
    string $locale,
    array $ctx,
    array $to,
    array $bcc,
    string $recipientFirstName = "",
  ): NextGenMailMessage {
    $company = method_exists($system_data, "getCompany") ? (string) $system_data->getCompany() : "";
    $fullName = trim(($ctx["name"] ?? "") . " " . ($ctx["surname"] ?? ""));
    $email = (string) ($ctx["email"] ?? "");
    $login = (string) ($ctx["login"] ?? $email);
    $base = MailEnv::nextgenPublicBaseUrl();
    $dashboardUrl = $base !== "" ? $base . "/dashboard/" : "";
    $integrationUrl = $base !== "" ? $base . "/contacts/integration/" : "";
    $primary = MailDesignTokens::get("primary");
    $primaryEsc = htmlspecialchars($primary, ENT_QUOTES, "UTF-8");
    $linkStyle = "color:" . $primaryEsc . " !important;text-decoration:underline !important;font-weight:600;";

    $activationNote =
      $ctx["autoUserActivation"] ?? false
        ? MailI18n::t("mail.newUserAdmin.noteAuto", $locale)
        : MailI18n::t("mail.newUserAdmin.noteManual", $locale);

    $intro = MailI18n::interpolate(MailI18n::t("mail.newUserAdmin.intro", $locale), [
      "fullName" => htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"),
      "email" => self::textForIntro($email),
      "login" => self::textForIntro($login),
      "company" => htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"),
    ]);

    $uid = (string) (int) ($ctx["userId"] ?? 0);
    $detailList =
      '<ul class="em-list-plain" style="list-style:none;padding-left:0;margin:16px 0;">' .
      '<li style="margin:0 0 8px;"><strong>' .
      htmlspecialchars(MailI18n::t("mail.newUserAdmin.labelName", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</strong> " .
      htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</li>" .
      '<li style="margin:0 0 8px;"><strong>' .
      htmlspecialchars(MailI18n::t("mail.newUserAdmin.labelEmail", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</strong> " .
      self::textForIntro($email) .
      "</li>" .
      '<li style="margin:0 0 8px;"><strong>' .
      htmlspecialchars(MailI18n::t("mail.newUserAdmin.labelLogin", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</strong> " .
      self::textForIntro($login) .
      "</li>" .
      '<li style="margin:0 0 8px;"><strong>' .
      htmlspecialchars(MailI18n::t("mail.newUserAdmin.labelUserId", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</strong> " .
      htmlspecialchars($uid, ENT_QUOTES, "UTF-8") .
      "</li>" .
      "</ul>";

    $links = "";
    if ($dashboardUrl !== "") {
      $du = htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
      $links .=
        '<p style="margin:16px 0 0;"><a href="' .
        $du .
        '" style="' .
        $linkStyle .
        '">' .
        htmlspecialchars(
          MailI18n::t("mail.newUserAdmin.linkDashboard", $locale),
          ENT_QUOTES | ENT_SUBSTITUTE,
          "UTF-8",
        ) .
        "</a></p>";
    }
    if ($integrationUrl !== "") {
      $iu = htmlspecialchars($integrationUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
      $links .=
        '<p style="margin:12px 0 0;"><a href="' .
        $iu .
        '" style="' .
        $linkStyle .
        '">' .
        htmlspecialchars(
          MailI18n::t("mail.newUserAdmin.linkIntegration", $locale),
          ENT_QUOTES | ENT_SUBSTITUTE,
          "UTF-8",
        ) .
        "</a></p>";
    }
    if ($base === "") {
      $links .=
        '<p class="em-muted" style="font-size:13px;margin-top:16px;">' .
        htmlspecialchars(MailI18n::t("mail.newUserAdmin.noDeepLink", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
        "</p>";
    }

    $inner =
      MailGreeting::htmlLeadParagraph($locale, $recipientFirstName) .
      '<p class="em-lead" style="margin:0 0 12px;">' .
      $intro .
      "</p>" .
      $detailList .
      '<p class="em-lead" style="margin:16px 0 0;">' .
      htmlspecialchars($activationNote, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</p>" .
      $links;

    $senderLine = MailBranding::bnoteBandLine($locale, $company);
    $footer = MailI18n::interpolate(MailI18n::t("mail.footer.admin", $locale), [
      "sender" => $senderLine,
    ]);

    $headline = htmlspecialchars(
      MailI18n::t("mail.shell.headlineNewUserAdmin", $locale),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $companyLine = $senderLine;

    $html = MailHtmlShell::wrapTransactional(
      $headline,
      $companyLine,
      $inner,
      $footer,
      $locale,
      null,
      null,
      MailAssets::logoImgSrcForEmail(),
    );

    $subject = MailI18n::interpolate(MailI18n::t("mail.newUserAdmin.subject", $locale), [
      "orgPrefix" => MailSubject::orgPrefix($company),
      "fullName" => $fullName,
    ]);

    $plain = strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $inner));
    if ($dashboardUrl !== "") {
      $plain .= "\n\n" . MailI18n::t("mail.newUserAdmin.linkDashboard", $locale) . ": " . $dashboardUrl;
    }
    if ($integrationUrl !== "") {
      $plain .= "\n" . MailI18n::t("mail.newUserAdmin.linkIntegration", $locale) . ": " . $integrationUrl;
    }
    $plain = MailBodyText::appendInstanceLink($plain, $locale);

    return new NextGenMailMessage(
      $to,
      $bcc,
      $subject,
      $html,
      $plain,
      "new_user_admin",
      MailAssets::defaultLogoEmbeds(),
    );
  }
}
