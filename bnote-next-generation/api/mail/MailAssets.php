<?php
declare(strict_types=1);

/**
 * Static assets for transactional mail (paths relative to bnote-next-generation root).
 */
final class MailAssets {
    public const LOGO_CID = 'bnote-logo';

    public static function logoPngPath(): ?string {
        $root = dirname(__DIR__, 2);
        $candidates = [
            $root . '/BNote_Logo_prebuilt.png',
            $root . '/frontend/public/BNote_Logo_prebuilt.png',
        ];
        foreach ($candidates as $p) {
            $rp = realpath($p);
            if ($rp !== false && is_readable($rp)) {
                return $rp;
            }
        }
        return null;
    }

    /**
     * @return list<array{path: string, cid: string}>
     */
    public static function defaultLogoEmbeds(): array {
        $path = self::logoPngPath();
        if ($path === null) {
            return [];
        }
        return [['path' => $path, 'cid' => self::LOGO_CID]];
    }

    public static function logoImgSrcForEmail(): string {
        return self::logoPngPath() !== null ? 'cid:' . self::LOGO_CID : '';
    }
}
