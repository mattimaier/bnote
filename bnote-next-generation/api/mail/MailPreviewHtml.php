<?php
declare(strict_types=1);

require_once __DIR__ . '/MailAssets.php';

/**
 * Browser preview: replace CID logo with a data URI (cid: is not resolved in browsers).
 */
final class MailPreviewHtml {
    public static function replaceCidLogoWithDataUri(string $html): string {
        $needle = 'src="cid:' . MailAssets::LOGO_CID . '"';
        if (!str_contains($html, $needle)) {
            return $html;
        }
        $path = MailAssets::logoPngPath();
        if ($path === null) {
            return str_replace($needle, 'src="" alt=""', $html);
        }
        $bin = file_get_contents($path);
        if ($bin === false) {
            return $html;
        }
        $data = 'data:image/png;base64,' . base64_encode($bin);
        return str_replace($needle, 'src="' . $data . '"', $html);
    }
}
