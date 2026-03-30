<?php
/**
 * Wraps inner HTML in a simple branded shell for all Next Gen transactional mail.
 */
declare(strict_types=1);

final class MailHtmlShell {
    public static function wrap(string $innerHtml, string $companyName, string $footerText): string {
        $h = htmlspecialchars($companyName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $f = htmlspecialchars($footerText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"/></head>'
            . '<body style="margin:0;padding:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:15px;line-height:1.5;color:#1a1a1a;background:#f4f4f5;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:8px;padding:28px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08);">'
            . '<tr><td style="font-size:18px;font-weight:700;padding-bottom:16px;border-bottom:1px solid #e5e5e5;">' . $h . '</td></tr>'
            . '<tr><td style="padding-top:20px;">' . $innerHtml . '</td></tr>'
            . '<tr><td style="padding-top:24px;font-size:12px;color:#666;border-top:1px solid #eee;margin-top:20px;">' . $f . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
