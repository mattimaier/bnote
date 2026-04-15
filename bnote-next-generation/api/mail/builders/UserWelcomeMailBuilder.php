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

final class UserWelcomeMailBuilder
{
  public static function build(
    $system_data,
    string $locale,
    string $toEmail,
    string $recipientFirstName = "",
  ): NextGenMailMessage {
    $company = method_exists($system_data, "getCompany") ? (string) $system_data->getCompany() : "";
    $headline = htmlspecialchars(
      MailI18n::t("mail.shell.headlineUserWelcome", $locale),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $companyLine = MailBranding::bnoteBandLine($locale, $company);
    $footer = MailI18n::interpolate(MailI18n::t("mail.footer.generic", $locale), [
      "sender" => $companyLine,
    ]);

    $base = MailEnv::nextgenPublicBaseUrl();
    $loginUrl = $base !== "" ? $base . "/login/" : "";
    $ctaLabel = htmlspecialchars(
      MailI18n::t("mail.userWelcome.ctaOpenLogin", $locale),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $successStrong = htmlspecialchars(
      MailDesignTokens::get("participationSuccess"),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $successBg = htmlspecialchars(
      MailDesignTokens::get("participationSuccessBgMuted"),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $successBorder = htmlspecialchars(
      MailDesignTokens::get("participationSuccessBorderMuted"),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $successTitle = htmlspecialchars(
      MailI18n::t("mail.userWelcome.successTitle", $locale),
      ENT_QUOTES | ENT_SUBSTITUTE,
      "UTF-8",
    );
    $successIcon =
      '<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#ffffff" aria-hidden="true" style="display:block;">' .
      '<circle cx="12" cy="12" r="10" fill="' .
      $successStrong .
      '" stroke="' .
      $successStrong .
      '" />' .
      '<path d="M7 12.5l3 3 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />' .
      "</svg>";
    $successHero =
      '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid ' .
      $successBorder .
      ";background:" .
      $successBg .
      ';border-radius:14px;">' .
      '<tr><td align="center" style="padding:18px 12px 14px;">' .
      $successIcon .
      '<div style="margin-top:10px;font-size:18px;line-height:1.3;font-weight:700;color:' .
      $successStrong .
      ';">' .
      $successTitle .
      "</div>" .
      "</td></tr></table>";
    $inner =
      $successHero .
      MailGreeting::htmlLeadParagraph($locale, $recipientFirstName) .
      '<p class="em-lead" style="margin:0 0 12px;">' .
      htmlspecialchars(MailI18n::t("mail.userWelcome.intro", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</p>" .
      '<p class="em-text-secondary" style="margin:0 0 12px;font-size:14px;">' .
      htmlspecialchars(MailI18n::t("mail.userWelcome.readyToGo", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
      "</p>";
    if ($loginUrl === "") {
      $inner .=
        '<p class="em-muted" style="margin:0;font-size:13px;">' .
        htmlspecialchars(MailI18n::t("mail.userWelcome.noDeepLink", $locale), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
        "</p>";
    }

    $html = MailHtmlShell::wrapTransactional(
      $headline,
      $companyLine,
      $inner,
      $footer,
      $locale,
      $loginUrl !== "" ? htmlspecialchars($loginUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : null,
      $loginUrl !== "" ? $ctaLabel : null,
      MailAssets::logoImgSrcForEmail(),
    );

    $subject = MailI18n::interpolate(MailI18n::t("mail.userWelcome.subject", $locale), [
      "orgPrefix" => MailSubject::orgPrefix($company),
    ]);

    $plain = trim(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $inner)));
    if ($loginUrl !== "") {
      $plain .= "\n\n" . MailI18n::t("mail.userWelcome.ctaOpenLogin", $locale) . ": " . $loginUrl;
    }
    $plain = MailBodyText::appendInstanceLink($plain, $locale);

    return new NextGenMailMessage(
      [trim($toEmail)],
      [],
      $subject,
      $html,
      $plain,
      "user_welcome",
      MailAssets::defaultLogoEmbeds(),
    );
  }
}
